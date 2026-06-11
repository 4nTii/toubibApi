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

    public function searchResults(
        Request $request,
        DoctorsRepository $doctorsRepository
    ): JsonResponse {
        $searchValue = trim($request->query->get('searchValue', ''));
        $location    = trim($request->query->get('location', ''));
        $page        = max(1, (int) $request->query->get('page', 1));
        $limit       = min(50, max(1, (int) $request->query->get('limit', 10)));

        if ($searchValue === '' && $location === '') {
            return $this->json([
                'status'  => false,
                'message' => 'Au moins un paramètre (searchValue ou location) est requis.',
            ], 400);
        }

        $result = $doctorsRepository->searchWithFilters(
            $searchValue !== '' ? $searchValue : null,
            $location    !== '' ? $location    : null,
            $page,
            $limit
        );

        $data = [];
        foreach ($result['doctors'] as $doctor) {
            $user = $doctor->getUser();

            $businessSites = [];
            foreach ($doctor->getDoctorBusinessSites() as $dbs) {
                $bs = $dbs->getBusinessSite();
                $businessSites[] = [
                    'id'      => $bs->getId(),
                    'name'    => $bs->getName(),
                    'ville'   => $bs->getVille(),
                    'address' => $bs->getAddress(),
                    'phone'   => $bs->getPhone(),
                    'region'  => $bs->getRegion()?->getName(),
                ];
            }

            $data[] = [
                'id'                      => $doctor->getId(),
                'fullName'                => $user->getFullName(),
                'gender'                  => $user->getGender(),
                'speciality'              => $doctor->getSpeciality()?->getName(),
                'profilePicture'          => $doctor->getProfilePicture(),
                'acceptNewPatients'       => $doctor->isAcceptNewPatients(),
                'teleconsultationEnabled' => $doctor->isTeleconsultationEnabled(),
                'verified'                => $doctor->isVerified(),
                'businessSites'           => $businessSites,
            ];
        }

        return $this->json([
            'status'  => true,
            'message' => 'Résultats de la recherche',
            'data'    => $data,
            'meta'    => [
                'total' => $result['total'],
                'page'  => $result['page'],
                'limit' => $result['limit'],
                'pages' => $result['pages'],
            ],
        ]);
    }

    public function searchRegionsVilles(
        Request $request,
        RegionsRepository $regionsRepository,
        BusinessSitesRepository $businessSitesRepository
    ): JsonResponse {
        $value = strtolower($request->query->get('value', ''));

        if (strlen($value) < 2) {
            return $this->json([
                'status' => true,
                'query'  => $value,
                'data'   => [],
            ]);
        }

        $result = [];

        foreach ($regionsRepository->searchByName($value) as $region) {
            $result['regions'][] = $region->getName();
        }

        $villes = $businessSitesRepository->searchDistinctVilles($value);
        if (!empty($villes)) {
            $result['villes'] = $villes;
        }

        return $this->json([
            'status' => true,
            'query'  => $value,
            'data'   => $result,
        ]);
    }
}
