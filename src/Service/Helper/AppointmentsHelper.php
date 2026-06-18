<?php

namespace App\Service\Helper;

use App\Repository\DoctorBusinessSiteRepository;
use App\Repository\AppointmentsRepository;
use App\Entity\Doctors;
use App\Entity\BusinessSites;
use App\Entity\DoctorBusinessSite;

class AppointmentsHelper
{
    public function __construct(
        private DoctorBusinessSiteRepository $dbsRepository,
        private AppointmentsRepository $appointmentRepository
    ) {}

    /**
     * Retourne les créneaux disponibles pour une date, une plage [debut, fin] ou un tableau de dates.
     *
     * @param \DateTimeImmutable|array $date  DateTimeImmutable | [DateTimeImmutable, DateTimeImmutable] (plage) | [DateTimeImmutable, ...] (liste)
     * @return array Slots indexés par date si plusieurs dates, tableau de slots si date unique
     */
    public function getSlotsByDates(
        Doctors $doctor,
        BusinessSites $businessSite,
        \DateTimeImmutable|array $date,
        bool $excludeLunch = true
    ): array {
        $doctorBusinessSite = $this->dbsRepository->findOneBy([
            'doctor'       => $doctor,
            'businessSite' => $businessSite
        ]);

        if (!$doctorBusinessSite) {
            return [];
        }

        $dates = $this->resolveDates($date);
        if (empty($dates)) {
            return [];
        }

        if (count($dates) === 1) {
            return $this->getSlotsForDate($doctorBusinessSite, $dates[0], $excludeLunch);
        }

        $result = [];
        foreach ($dates as $dateObj) {
            $slots = $this->getSlotsForDate($doctorBusinessSite, $dateObj, $excludeLunch);
            if (!empty($slots)) {
                $result[$dateObj->format('Y-m-d')] = $slots;
            }
        }

        return $result;
    }

    /**
     * Convertit le paramètre $date en tableau de DateTimeImmutable.
     * - DateTimeImmutable        → [DateTimeImmutable]
     * - [debut, fin]             → toutes les dates de la plage inclus
     * - [DateTimeImmutable, ...] → tableau tel quel après filtrage
     *
     * @param \DateTimeImmutable|array $date
     * @return \DateTimeImmutable[]
     */
    private function resolveDates(\DateTimeImmutable|array $date): array
    {
        if ($date instanceof \DateTimeImmutable) {
            return [$date];
        }

        if (empty($date)) {
            return [];
        }

        // Plage [debut, fin]
        if (
            count($date) === 2 &&
            $date[0] instanceof \DateTimeImmutable &&
            $date[1] instanceof \DateTimeImmutable
        ) {
            $interval = new \DateInterval('P1D');
            $period   = new \DatePeriod($date[0], $interval, $date[1]->modify('+1 day'));

            $dates = [];
            foreach ($period as $day) {
                $dates[] = \DateTimeImmutable::createFromInterface($day);
            }
            return $dates;
        }

        // Tableau de dates quelconques
        return array_values(
            array_filter($date, fn($d) => $d instanceof \DateTimeImmutable)
        );
    }

    /**
     * Génère les créneaux disponibles pour une seule date.
     * Retourne [] si le médecin n'est pas associé au cabinet, le jour est fermé, ou la config est manquante.
     * Filtre les créneaux passés et applique un délai de 3h minimum pour les réservations du jour même.
     *
     * @param \DateTimeImmutable $dateObj
     * @return array [['start' => 'H:i', 'end' => 'H:i'], ...]
     */
    private function getSlotsForDate(
        DoctorBusinessSite $doctorBusinessSite,
        \DateTimeImmutable $dateObj,
        bool $excludeLunch
    ): array {
        $schedule = $doctorBusinessSite->getWorkingSchedule();
        $duration = $doctorBusinessSite->getConsultationDuration();

        if (!$schedule || !$duration) {
            return [];
        }

        $dayName = strtolower($dateObj->format('l'));

        if (!isset($schedule[$dayName]) || !$schedule[$dayName]['enabled']) {
            return [];
        }

        $date  = $dateObj->format('Y-m-d');
        $start = new \DateTimeImmutable($date . ' ' . $schedule[$dayName]['start']);
        $end   = new \DateTimeImmutable($date . ' ' . $schedule[$dayName]['end']);

        if ($start >= $end) {
            return [];
        }

        $breakStart = null;
        $breakEnd   = null;
        if ($excludeLunch) {
            $breakStart = $dateObj->setTime(12, 0);
            $breakEnd   = $dateObj->setTime(14, 0);
        }

        $appointments = $this->appointmentRepository->getScheduleByDate(
            $doctorBusinessSite->getDoctor(),
            $doctorBusinessSite->getBusinessSite(),
            $dateObj,
            $excludeLunch
        );

        $bookedSlots = [];
        foreach ($appointments as $appt) {
            $bookedSlots[] = [
                'start' => $appt->getStartTime(),
                'end'   => $appt->getEndTime()
            ];
        }

        // Calculer le temps minimum pour les réservations
        $now = new \DateTimeImmutable('now');
        $isToday = $dateObj->format('Y-m-d') === $now->format('Y-m-d');
        $minSlotTime = $isToday ? $now->modify('+3 hours') : null;

        $slots = [];
        while ($start < $end) {
            $slotEnd = $start->modify("+{$duration} minutes");

            if ($slotEnd > $end) {
                break;
            }

            if ($excludeLunch && $start < $breakEnd && $slotEnd > $breakStart) {
                $start = $slotEnd;
                continue;
            }

            // Filtrer les créneaux passés ou trop proches (moins de 3h)
            if ($minSlotTime && $start < $minSlotTime) {
                $start = $slotEnd;
                continue;
            }

            $isBooked = false;
            foreach ($bookedSlots as $booked) {
                if ($start < $booked['end'] && $slotEnd > $booked['start']) {
                    $isBooked = true;
                    break;
                }
            }

            if (!$isBooked) {
                $slots[] = [
                    'start' => $start->format('H:i'),
                    'end'   => $slotEnd->format('H:i')
                ];
            }

            $start = $slotEnd;
        }

        return $slots;
    }
}
