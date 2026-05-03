<?php

namespace App\Controller\Doctors;

use App\Entity\Appointments;
use App\Entity\Patients;
use App\Repository\DoctorsRepository;
use App\Repository\BusinessSitesRepository;
use App\Repository\PatientsRepository;
use App\Repository\UsersRepository;
use App\Service\Helper\AppointmentsHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DoctorsAppointment extends AbstractController
{
    public function getDoctorAvailableAppointment(
        int $id,
        string $startDate,
        string $endDate,
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

        $primaryBusinessSite = $businessSitesRepository->getPrimaryBusinessSite($doctor);
        if (!$primaryBusinessSite) {
            return $this->json([
                'status'  => false,
                'message' => 'Aucun cabinet principal défini pour ce médecin.'
            ], 404);
        }

        $availableSlots = $appointmentsHelper->getSlotsByDates(
            $doctor,
            $primaryBusinessSite,
            [$dateStart, $dateEnd],
            true
        );

        $response = [
            'status' => true,
            'data'   => [
                'doctorId'       => $doctor->getId(),
                'startDate'      => $dateStart->format('Y-m-d'),
                'endDate'        => $dateEnd->format('Y-m-d'),
                'availableSlots' => $availableSlots
            ]
        ];

        return $this->json($response, 200);
    }

    public function setAppointment(
        int $idDoctor,
        Request $request,
        DoctorsRepository $doctorsRepository,
        BusinessSitesRepository $businessSitesRepository,
        PatientsRepository $patientsRepository,
        UsersRepository $usersRepository,
        AppointmentsHelper $appointmentsHelper,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['status' => false, 'message' => 'Corps de requête invalide.'], 400);
        }

        $idUser = $data['idUser'] ?? null;
        $startDate = $data['startDate'] ?? null;
        $endDate   = $data['endDate'] ?? null;
        $notes   = $data['notes'] ?? null;

        if (!$idUser || !$startDate || !$endDate) {
            return $this->json([
                'status'  => false,
                'message' => 'Requete invalide.'
            ], 400);
        }

        // Vérifier le docteur
        $doctor = $doctorsRepository->find($idDoctor);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Requete invalide.'], 404);
        }

        // Vérifier l'user
        $user = $usersRepository->find($idUser);
        if (!$user) {
            return $this->json(['status' => false, 'message' => 'Requete invalide.'], 404);
        }

        // Récupérer ou créer le patient
        $patient = $patientsRepository->findOneBy(['user' => $user]);
        if (!$patient) {
            $patient = new Patients();
            $patient->setUser($user);
            $entityManager->persist($patient);
        }

        // Valider les dates
        try {
            $start = new \DateTimeImmutable($startDate);
            $end   = new \DateTimeImmutable($endDate);
        } catch (\Exception $e) {
            return $this->json([
                'status'  => false,
                'message' => 'Requete invalide.'
            ], 400);
        }

        if ($start >= $end) {
            return $this->json([
                'status'  => false,
                'message' => 'Requete invalide.'
            ], 400);
        }

        // Récupérer le cabinet principal
        $primaryBusinessSite = $businessSitesRepository->getPrimaryBusinessSite($doctor);
        if (!$primaryBusinessSite) {
            return $this->json([
                'status'  => false,
                'message' => 'Aucun cabinet principal défini pour ce médecin.'
            ], 404);
        }

        // Vérifier que le créneau est disponible
        $dateOnly  = new \DateTimeImmutable($start->format('Y-m-d'));
        $available = $appointmentsHelper->getSlotsByDates(
            $doctor,
            $primaryBusinessSite,
            $dateOnly,
            true
        );

        $slotKey   = $start->format('H:i');
        $slotEndKey = $end->format('H:i');
        $isAvailable = false;

        foreach ($available as $slot) {
            if ($slot['start'] === $slotKey && $slot['end'] === $slotEndKey) {
                $isAvailable = true;
                break;
            }
        }

        if (!$isAvailable) {
            return $this->json([
                'status'  => false,
                'message' => 'Ce créneau n\'est pas disponible.'
            ], 409);
        }

        // Créer le rendez-vous
        $appointment = new Appointments();
        $appointment->setDoctor($doctor);
        $appointment->setPatient($patient);
        $appointment->setBusinessSite($primaryBusinessSite);
        $appointment->setNotes($notes);
        $appointment->setStartTime(\DateTime::createFromImmutable($start));
        $appointment->setEndTime(\DateTime::createFromImmutable($end));
        $appointment->setStatus('scheduled');

        $entityManager->persist($appointment);
        $entityManager->flush();

        $response = [
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
                    'id'   => $primaryBusinessSite->getId(),
                    'name' => $primaryBusinessSite->getName(),
                ]
            ]
        ];

        return $this->json($response, 201);
    }
}
