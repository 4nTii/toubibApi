<?php

namespace App\Controller\SearchRequests;

use App\Entity\Doctors;
use App\Repository\BusinessSitesRepository;
use App\Repository\DoctorBusinessSiteRepository;
use App\Repository\DoctorsRepository;
use App\Repository\RegionsRepository;
use App\Repository\SpecialitiesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchRequestsController extends AbstractController
{
    public function searchDoctorsBusinessSitesSpecialty(
        Request $request,
        DoctorsRepository $doctorsRepository,
        BusinessSitesRepository $businessSitesRepository,
        SpecialitiesRepository $specialitiesRepository
    ): JsonResponse {
        $value = strtolower($request->query->get('value', ''));

        if (strlen($value) < 2) {
            return $this->json([
                'status' => true,
                'query' => $value,
                'data' => []
            ], 200);
        }

        $result = [];
        // voir la route elle n'est pas fini preparer toutes les information necessaire Docteur, etablissement, specialité
        $doctors = $doctorsRepository->searchByName($value, 3, true);
        $result['doctors'] = [];
        $businessSites = $businessSitesRepository->searchByName($value, 3);
        $result['businessSite'] = [];
        $specialities = $specialitiesRepository->searchByName($value, 3);
        $result['specialities'] = [];

        foreach ($doctors as $doctor) {
            $user = $doctor->getUser();

            $cities = array_unique(array_map(
                fn($dbs) => $dbs->getBusinessSite()->getVille(),
                $doctor->getDoctorBusinessSites()->toArray()
            ));

            $result['doctors'][] = [
                'id' => $doctor->getId(),
                'name' => $user->getFullName(),
                'gender' => $user->getGender(),
                'speciality' => $doctor->getSpeciality()?->getName(),
                'image' => $doctor->getProfilePicture(),
                'cities' => array_values($cities)
            ];
        }

        foreach ($businessSites as $businessSite) {
            $result['businessSite'][] = [
                'id' => $businessSite->getId(),
                'name' => $businessSite->getName(),
                'ville' => $businessSite->getVille(),
            ];
        }
        foreach ($specialities as $specialitie) {
            $result['specialities'][] = [
                'id' => $specialitie->getId(),
                'name' => $specialitie->getName(),
            ];
        }

        if (empty($doctors) && !empty($specialities)) {
            $specialitie = $specialities[0];
            $doctors = $doctorsRepository->findBySpecialty($specialitie);
            foreach ($doctors as $doctor) {
                if ($doctor->isActive()) {
                    $user = $doctor->getUser();

                    $cities = array_unique(array_map(
                        fn($dbs) => $dbs->getBusinessSite()->getVille(),
                        $doctor->getDoctorBusinessSites()->toArray()
                    ));

                    $result['doctors'][] = [
                        'id' => $doctor->getId(),
                        'name' => $user->getFullName(),
                        'gender' => $user->getGender(),
                        'speciality' => $doctor->getSpeciality()?->getName(),
                        'image' => $doctor->getProfilePicture(),
                        'cities' => array_values($cities)
                    ];
                }
            }
        }

        return $this->json([
            'status' => true,
            'query' => $value,
            'data' => $result
        ], 200);
    }

    public function searchRegionsVilles(
        Request $request,
        RegionsRepository $regionsRepository
    ): JsonResponse {
        $value = strtolower($request->query->get('value', ''));
        $result = [];
        if (strlen($value) < 2) {
            return $this->json([
                'status' => true,
                'query' => $value,
                'data' => []
            ]);
        }

        $regions = $regionsRepository->searchByName($value);
        foreach ($regions as $region) {
            $result['region'][] = [
                'id' => $region->getId(),
                'name' => $region->getName()
            ];
        }

        return $this->json([
            'status' => true,
            'query' => $value,
            'data' => $result
        ], 200);
    }
}
