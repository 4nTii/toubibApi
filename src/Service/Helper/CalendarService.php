<?php

namespace App\Service\Helper;

use App\Entity\Appointments;
use App\Entity\BusinessSites;
use App\Entity\Doctors;
use App\Repository\AppointmentsRepository;
use App\Repository\DoctorBusinessSiteRepository;

class CalendarService
{
    // La couleur du docteur connecté est gérée côté front (bleu fixe #3b82f6).
    // Ce tableau est pour les médecins partagés uniquement.
    private const COLORS = [
        '#10b981', // emerald
        '#f59e0b', // amber
        '#ef4444', // red
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#14b8a6', // teal
        '#f97316', // orange
        '#6366f1', // indigo
    ];

    public function __construct(
        private DoctorBusinessSiteRepository $doctorBusinessSiteRepo,
        private AppointmentsRepository $appointmentsRepo,
    ) {}

    /**
     * Liste des cabinets du docteur connecté avec flag isPrimary.
     */
    public function getBusinessSites(Doctors $doctor): array
    {
        $result = [];
        foreach ($this->doctorBusinessSiteRepo->findByDoctor($doctor) as $dbs) {
            $site = $dbs->getBusinessSite();
            $result[] = [
                'id'        => $site->getId(),
                'name'      => $site->getName(),
                'address'   => $site->getAddress(),
                'ville'     => $site->getVille(),
                'isPrimary' => $dbs->isPrimary(),
            ];
        }
        return $result;
    }

    /**
     * Cabinet principal du docteur (isPrimary=true), ou premier cabinet disponible.
     */
    public function resolvePrimaryBusinessSite(Doctors $doctor): ?BusinessSites
    {
        $primary = $this->doctorBusinessSiteRepo->findPrimaryByDoctor($doctor);
        if ($primary) {
            return $primary->getBusinessSite();
        }
        $first = $this->doctorBusinessSiteRepo->findByDoctor($doctor);
        return !empty($first) ? $first[0]->getBusinessSite() : null;
    }

    /**
     * Vérifie que le docteur appartient bien au cabinet demandé.
     */
    public function doctorBelongsToSite(Doctors $doctor, BusinessSites $site): bool
    {
        return $this->doctorBusinessSiteRepo->findOneByDoctorAndBusinessSite($doctor, $site) !== null;
    }

    /**
     * Données complètes pour le calendrier :
     *   - appointments : RDV du docteur connecté sur ce cabinet
     *   - sharedDoctors : autres médecins du cabinet avec share_calendar=1 et leurs RDV
     */
    public function getCalendarData(Doctors $connectedDoctor, BusinessSites $site): array
    {
        $ownAppointments = $this->formatAppointments(
            $this->appointmentsRepo->findAllByDoctorAndSite($connectedDoctor, $site)
        );

        $sharedDoctors = [];
        foreach ($this->doctorBusinessSiteRepo->findByBusinessSite($site) as $dbs) {
            $doctor = $dbs->getDoctor();
            if ($doctor->getId() === $connectedDoctor->getId()) {
                continue;
            }
            if (!$dbs->isShareCalendar()) {
                continue;
            }
            $doctorUser = $doctor->getUser();
            $sharedDoctors[] = [
                'doctor_id'      => $doctor->getId(),
                'doctor_name'    => 'Dr ' . $doctorUser->getLastName(),
                'share_calendar' => 1,
                'color'          => self::COLORS[$doctor->getId() % count(self::COLORS)],
                'appointments'   => $this->formatAppointments(
                    $this->appointmentsRepo->findAllByDoctorAndSite($doctor, $site)
                ),
            ];
        }

        return [
            'appointments'  => $ownAppointments,
            'sharedDoctors' => $sharedDoctors,
        ];
    }

    /**
     * Convertit des entités Appointments en tableau compatible Calendar.jsx.
     */
    private function formatAppointments(array $appointments): array
    {
        return array_map(function (Appointments $appt) {
            $patientUser = $appt->getPatient();
            $fullName    = $patientUser->getFirstName() . ' ' . $patientUser->getLastName();

            return [
                'id'               => $appt->getId(),
                'title'            => $fullName,
                'start'            => $appt->getStartTime()->format('Y-m-d\TH:i:s'),
                'end'              => $appt->getEndTime()->format('Y-m-d\TH:i:s'),
                'patientName'      => $fullName,
                'patientFirstName'  => $patientUser->getFirstName(),
                'patientLastName'   => $patientUser->getLastName(),
                'patientGender'     => $patientUser->getGender(),
                'patientUserId'     => $patientUser->getId(),
                'patientEmail'      => $patientUser->getEmail(),
                'patientPhone'      => $patientUser->getPhone(),
                'notes'             => $appt->getNotes(),
                'status'            => $appt->getStatus(),
                'businessSiteId'    => $appt->getBusinessSite()?->getId(),
                'businessSiteName'  => $appt->getBusinessSite()?->getName(),
            ];
        }, $appointments);
    }
}
