<?php

namespace App\Controller\Doctors;

use App\Entity\Users;
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

        if (empty($doctors)) {
            $response = [
                'status'  => false,
                'message' => 'Aucun resultat trouvé'
            ];
            return $this->json($response, 200);
        }

        $response = [
            'status' => true,
            'data'   => [
                'page'    => $page,
                'limit'   => $limit,
                'doctors' => $doctors,
            ]
        ];

        return $this->json($response, 200, [], ['groups' => ['doctor:read']]);
    }

    public function getConnectedDoctor(DoctorsRepository $doctorsRepository): JsonResponse
    {
        /** @var Users $user */
        $user = $this->getUser();

        if (!$user) {
            $response = [
                'status'  => false,
                'message' => 'Utilisateur non authentifié'
            ];
            return $this->json($response, 401);
        }

        $doctor = $doctorsRepository->findOneBy(['user' => $user]);

        if (!$doctor) {
            $response = [
                'status'  => false,
                'message' => 'Cet utilisateur ne correspond pas à un médecin'
            ];
            return $this->json($response, 200);
        }

        $response = [
            'status' => true,
            'data'   => [
                'doctor' => $doctor,
            ]
        ];

        return $this->json($response, 200, [], ['groups' => ['doctor:read']]);
    }
}
