<?php

namespace App\Controller\Doctors;

use App\Repository\DoctorsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DoctorsController extends AbstractController
{
    public function getAllDoctors(Request $request, DoctorsRepository $doctorsRepository): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 5);

        $doctors = $doctorsRepository->getAllDoctors($page, $limit);

        //dd($doctors[0]->getUser());

        $data = array_map(fn($doctor) => [
            'doctorId' => $doctor->getUser()?->getId(),
            'firstName' => $doctor->getUser()?->getFirstName(),
            'lastName' => $doctor->getUser()?->getLastName(),
            'email' => $doctor->getUser()?->getEmail(),
            'speciality' => $doctor->getSpeciality()?->getName(),
        ], $doctors);

        if (empty($data)) {
            return $this->json([
                'status' => false,
                'message' => 'Aucun resultat trouvé'
            ], 200);
        }

        return $this->json([
            'status' => true,
            'data' => [
                'page' => $page,
                'limit' => $limit,
                'data' => $data
            ]
        ], 200);
    }
}
