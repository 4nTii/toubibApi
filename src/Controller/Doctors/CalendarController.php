<?php

namespace App\Controller\Doctors;

use App\Repository\BusinessSitesRepository;
use App\Repository\DoctorBusinessSiteRepository;
use App\Repository\DoctorsRepository;
use App\Service\Helper\CalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Users;

class CalendarController extends AbstractController
{
    public function getCalendarData(
        Request $request,
        DoctorsRepository $doctorsRepository,
        BusinessSitesRepository $businessSitesRepository,
        DoctorBusinessSiteRepository $doctorBusinessSiteRepository,
        CalendarService $calendarService
    ): JsonResponse {
        /** @var Users $user */
        $user = $this->getUser();

        $doctor = $doctorsRepository->findOneBy(['user' => $user]);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Profil médecin introuvable'], 404);
        }

        $businessSitesList = $calendarService->getBusinessSites($doctor);
        if (empty($businessSitesList)) {
            return $this->json(['status' => false, 'message' => 'Aucun cabinet associé à ce compte'], 404);
        }

        // Résoudre le cabinet sélectionné
        $requestedId = $request->query->getInt('businessSiteId', 0);
        $selectedSite = null;

        if ($requestedId) {
            $candidate = $businessSitesRepository->find($requestedId);
            if ($candidate && $calendarService->doctorBelongsToSite($doctor, $candidate)) {
                $selectedSite = $candidate;
            }
        }

        if (!$selectedSite) {
            $selectedSite = $calendarService->resolvePrimaryBusinessSite($doctor);
        }

        if (!$selectedSite) {
            return $this->json(['status' => false, 'message' => 'Impossible de déterminer le cabinet actif'], 500);
        }

        $calendarData = $calendarService->getCalendarData($doctor, $selectedSite);

        return $this->json([
            'status' => true,
            'data'   => [
                'businessSites'          => $businessSitesList,
                'selectedBusinessSiteId' => $selectedSite->getId(),
                'appointments'           => $calendarData['appointments'],
                'sharedDoctors'          => $calendarData['sharedDoctors'],
            ],
        ]);
    }
}
