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

        if (empty($doctors)) {
            return $this->json([
                'status' => false,
                'message' => 'Aucun resultat trouvé'
            ], 200);
        }

        return $this->json([
            'status' => true,
            'data'   => [
                'page'  => $page,
                'limit' => $limit,
                'doctors'  => $doctors,
            ]
        ], 200, [], ['groups' => ['doctor:read']]);
    }
}
