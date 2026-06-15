<?php

namespace App\Controller\Doctors;

use App\Entity\Appointments;
use App\Repository\AppointmentsRepository;
use App\Repository\DoctorsRepository;
use App\Repository\BusinessSitesRepository;
use App\Repository\UsersRepository;
use App\Service\Helper\AppointmentsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DoctorsAppointment extends AbstractController
{
    public function getScheduledAppointments(
        AppointmentsRepository $appointmentsRepository,
        DoctorsRepository $doctorsRepository
    ): JsonResponse {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        $doctor = $doctorsRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Profil médecin introuvable'], 404);
        }

        $appointments = $appointmentsRepository->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.status = :status')
            ->setParameter('doctor', $doctor)
            ->setParameter('status', 'scheduled')
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();

        $data = array_map(function (Appointments $appt) {
            $patientUser = $appt->getPatient();
            $bs = $appt->getBusinessSite();

            return [
                'id'        => $appt->getId(),
                'startTime' => $appt->getStartTime()->format('Y-m-d H:i:s'),
                'endTime'   => $appt->getEndTime()->format('Y-m-d H:i:s'),
                'status'    => $appt->getStatus(),
                'notes'     => $appt->getNotes(),
                'patient'   => [
                    'id'        => $patientUser->getId(),
                    'firstName' => $patientUser->getFirstName(),
                    'lastName'  => $patientUser->getLastName(),
                    'email'     => $patientUser->getEmail(),
                    'phone'     => $patientUser->getPhone(),
                    'gender'    => $patientUser->getGender(),
                ],
                'businessSite' => [
                    'id'   => $bs->getId(),
                    'name' => $bs->getName(),
                ],
            ];
        }, $appointments);

        return $this->json(['status' => true, 'data' => $data]);
    }

    public function updateAppointmentStatus(
        int $id,
        Request $request,
        AppointmentsRepository $appointmentsRepository,
        DoctorsRepository $doctorsRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        $doctor = $doctorsRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Profil médecin introuvable'], 404);
        }

        $appointment = $appointmentsRepository->find($id);
        if (!$appointment || $appointment->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['status' => false, 'message' => 'Rendez-vous introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $newStatus = $data['status'] ?? null;

        if (!in_array($newStatus, ['confirmed', 'canceled'], true)) {
            return $this->json(['status' => false, 'message' => 'Statut invalide. Valeurs acceptées : confirmed, canceled'], 400);
        }

        $appointment->setStatus($newStatus);
        $em->flush();

        return $this->json([
            'status'  => true,
            'message' => $newStatus === 'confirmed' ? 'Rendez-vous confirmé' : 'Rendez-vous annulé',
            'data'    => ['id' => $appointment->getId(), 'status' => $newStatus],
        ]);
    }

    public function updateAppointment(
        int $id,
        Request $request,
        AppointmentsRepository $appointmentsRepository,
        DoctorsRepository $doctorsRepository,
        UsersRepository $usersRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        $doctor = $doctorsRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Profil médecin introuvable'], 404);
        }

        $appointment = $appointmentsRepository->find($id);
        if (!$appointment || $appointment->getDoctor()->getId() !== $doctor->getId()) {
            return $this->json(['status' => false, 'message' => 'Rendez-vous introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (!empty($data['idUser'])) {
            $targetUser = $usersRepository->find($data['idUser']);
            if (!$targetUser) {
                return $this->json(['status' => false, 'message' => 'Patient introuvable'], 404);
            }
            $appointment->setPatient($targetUser);
        }

        if (!empty($data['startDate']) && !empty($data['endDate'])) {
            try {
                $start = new \DateTimeImmutable($data['startDate']);
                $end   = new \DateTimeImmutable($data['endDate']);
                if ($start >= $end) {
                    return $this->json(['status' => false, 'message' => 'Dates invalides'], 400);
                }
                $appointment->setStartTime(\DateTime::createFromImmutable($start));
                $appointment->setEndTime(\DateTime::createFromImmutable($end));
            } catch (\Exception $e) {
                return $this->json(['status' => false, 'message' => 'Format de date invalide'], 400);
            }
        }

        if (array_key_exists('notes', $data)) {
            $appointment->setNotes($data['notes'] ?: null);
        }

        if (!empty($data['status']) && in_array($data['status'], ['scheduled', 'confirmed'], true)) {
            $appointment->setStatus($data['status']);
        }

        $em->flush();

        $confirmed = $appointment->getStatus() === 'confirmed';

        return $this->json([
            'status'  => true,
            'message' => $confirmed ? 'Rendez-vous modifié et confirmé.' : 'Rendez-vous modifié avec succès.',
            'data'    => ['id' => $appointment->getId()],
        ]);
    }

    public function getDoctorAvailableAppointment(
        int $id,
        string $startDate,
        string $endDate,
        Request $request,
        DoctorsRepository $doctorsRepository,
        BusinessSitesRepository $businessSitesRepository,
        AppointmentsHelper $appointmentsHelper
    ): JsonResponse {

        $doctor = $doctorsRepository->find($id);
        if (!$doctor) {
            return $this->json([
                'status'  => false,
                'message' => 'Médecin non trouvé.'
            ], 404);
        }

        try {
            $dateStart = new \DateTimeImmutable($startDate);
            $dateEnd   = new \DateTimeImmutable($endDate);
        } catch (\Exception $e) {
            return $this->json([
                'status'  => false,
                'message' => 'Format de date invalide. Utilisez le format Y-m-d (ex: 2026-05-01).'
            ], 400);
        }

        if ($dateStart > $dateEnd) {
            return $this->json([
                'status'  => false,
                'message' => 'La date de début doit être antérieure à la date de fin.'
            ], 400);
        }

        // Get businessSiteId from query parameter
        $businessSiteId = $request->query->getInt('businessSiteId');

        if ($businessSiteId) {
            // Use specified business site
            $businessSite = $businessSitesRepository->find($businessSiteId);
            if (!$businessSite) {
                return $this->json([
                    'status'  => false,
                    'message' => 'Cabinet non trouvé.'
                ], 404);
            }
            // Verify that this business site belongs to the doctor
            $found = false;
            foreach ($doctor->getDoctorBusinessSites() as $dbs) {
                if ($dbs->getBusinessSite()->getId() === $businessSiteId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return $this->json([
                    'status'  => false,
                    'message' => 'Ce cabinet n\'appartient pas à ce médecin.'
                ], 403);
            }
        } else {
            // Use primary business site
            $businessSite = $businessSitesRepository->getPrimaryBusinessSite($doctor);
            if (!$businessSite) {
                return $this->json([
                    'status'  => false,
                    'message' => 'Aucun cabinet principal défini pour ce médecin.'
                ], 404);
            }
        }

        $availableSlots = $appointmentsHelper->getSlotsByDates(
            $doctor,
            $businessSite,
            [$dateStart, $dateEnd],
            true
        );

        return $this->json([
            'status' => true,
            'data'   => [
                'doctorId'       => $doctor->getId(),
                'startDate'      => $dateStart->format('Y-m-d'),
                'endDate'        => $dateEnd->format('Y-m-d'),
                'availableSlots' => $availableSlots,
            ],
        ]);
    }

    public function setAppointment(
        int $idDoctor,
        Request $request,
        DoctorsRepository $doctorsRepository,
        BusinessSitesRepository $businessSitesRepository,
        UsersRepository $usersRepository,
        AppointmentsHelper $appointmentsHelper,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['status' => false, 'message' => 'Corps de requête invalide.'], 400);
        }

        $idUser        = $data['idUser'] ?? null;
        $startDate     = $data['startDate'] ?? null;
        $endDate       = $data['endDate'] ?? null;
        $notes         = $data['notes'] ?? null;
        $businessSiteId = $data['businessSiteId'] ?? null;

        if (!$idUser || !$startDate || !$endDate) {
            return $this->json(['status' => false, 'message' => 'Requete invalide.'], 400);
        }

        $doctor = $doctorsRepository->find($idDoctor);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Requete invalide.'], 404);
        }

        $patient = $usersRepository->find($idUser);
        if (!$patient) {
            return $this->json(['status' => false, 'message' => 'Requete invalide.'], 404);
        }

        try {
            $start = new \DateTimeImmutable($startDate);
            $end   = new \DateTimeImmutable($endDate);
        } catch (\Exception $e) {
            return $this->json(['status' => false, 'message' => 'Requete invalide.'], 400);
        }

        if ($start >= $end) {
            return $this->json(['status' => false, 'message' => 'Requete invalide.'], 400);
        }

        // Get business site
        if ($businessSiteId) {
            // Use specified business site
            $businessSite = $businessSitesRepository->find($businessSiteId);
            if (!$businessSite) {
                return $this->json(['status' => false, 'message' => 'Cabinet non trouvé.'], 404);
            }
            // Verify that this business site belongs to the doctor
            $found = false;
            foreach ($doctor->getDoctorBusinessSites() as $dbs) {
                if ($dbs->getBusinessSite()->getId() === $businessSiteId) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return $this->json(['status' => false, 'message' => 'Ce cabinet n\'appartient pas à ce médecin.'], 403);
            }
        } else {
            // Use primary business site
            $businessSite = $businessSitesRepository->getPrimaryBusinessSite($doctor);
            if (!$businessSite) {
                return $this->json(['status' => false, 'message' => 'Aucun cabinet principal défini pour ce médecin.'], 404);
            }
        }

        $dateOnly  = new \DateTimeImmutable($start->format('Y-m-d'));
        $available = $appointmentsHelper->getSlotsByDates(
            $doctor,
            $businessSite,
            $dateOnly,
            true
        );

        $isAvailable = false;
        foreach ($available as $slot) {
            if ($slot['start'] === $start->format('H:i') && $slot['end'] === $end->format('H:i')) {
                $isAvailable = true;
                break;
            }
        }

        if (!$isAvailable) {
            return $this->json(['status' => false, 'message' => 'Ce créneau n\'est pas disponible.'], 409);
        }

        $appointment = new Appointments();
        $appointment->setDoctor($doctor);
        $appointment->setPatient($patient);
        $appointment->setBusinessSite($businessSite);
        $appointment->setNotes($notes);
        $appointment->setStartTime(\DateTime::createFromImmutable($start));
        $appointment->setEndTime(\DateTime::createFromImmutable($end));
        $appointment->setStatus('scheduled');

        $entityManager->persist($appointment);
        $entityManager->flush();

        return $this->json([
            'status'  => true,
            'message' => 'Rendez-vous créé avec succès.',
            'data'    => [
                'appointmentId' => $appointment->getId(),
                'doctorId'      => $doctor->getId(),
                'patientId'     => $patient->getId(),
                'startTime'     => $start->format('Y-m-d H:i'),
                'endTime'       => $end->format('Y-m-d H:i'),
                'status'        => $appointment->getStatus(),
                'businessSite'  => [
                    'id'   => $businessSite->getId(),
                    'name' => $businessSite->getName(),
                ],
            ],
        ], 201);
    }
}
