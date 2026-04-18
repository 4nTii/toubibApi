<?php

namespace App\Controller\SearchRequests;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SearchRequestsController extends AbstractController
{
    public function searchDoctorsBusinessSitesSpecialty(Request $request): JsonResponse
    {
        $value = strtolower($request->query->get('value', ''));

        $doctors = ['Dr Martin', 'Dr Durand', 'Dr Bernard'];
        $specialities = ['Cardiologie', 'Dermatologie', 'Pédiatrie'];
        $businessSites = ['Cabinet Central', 'Clinique Saint-Jean', 'Centre Médical Lyon'];
        $cities = ['Annecy', 'Lyon', 'Paris'];
        $countries = ['France'];
        $images = ['/img/1.png', '/img/2.png', '/img/3.png'];

        $data = [];

        for ($i = 0; $i < 5; $i++) {

            $type = ['doctor', 'speciality', 'businessSite'][array_rand([0, 1, 2])];

            if ($type === 'doctor') {
                $name = $doctors[array_rand($doctors)];
            } elseif ($type === 'speciality') {
                $name = $specialities[array_rand($specialities)];
            } else {
                $name = $businessSites[array_rand($businessSites)];
            }

            // fake "match" logic (simple)
            if ($value && stripos($name, $value) === false) {
                $name .= ' ' . $value;
            }

            $data[] = [
                'type' => $type,
                'title' => $name,
                'country' => $countries[array_rand($countries)],
                'ville' => $cities[array_rand($cities)],
                'image' => $images[array_rand($images)]
            ];
        }

        return $this->json([
            'status' => true,
            'query' => $value,
            'data' => $data
        ]);
    }
}
