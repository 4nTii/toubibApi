<?php

namespace App\Controller\Doctors;

use App\Repository\DoctorsRepository;
use App\Repository\BusinessSitesRepository;
use App\Service\Helper\AppointmentsHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

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
}
