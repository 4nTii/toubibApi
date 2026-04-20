<?php

namespace App\Controller\Doctors;

use App\Entity\Users;
use App\Repository\DoctorsRepository;
use App\Service\Helper\FileUploadHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

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

    public function updateConnectedDoctor(
        Request $request,
        DoctorsRepository $doctorsRepository,
        FileUploadHelper $fileUploadHelper,
        EntityManagerInterface $entityManager
    ): JsonResponse {

        // JSON depuis FormData ou JSON direct
        $data = $request->request->get('data');
        $photo = $request->files->get('profilePicture');

        if ($data !== null) {
            $data = json_decode($data, true);
        } else {
            $data = json_decode($request->getContent(), true);
        }

        if (empty($data) && !$photo) {
            return $this->json([
                'status'  => false,
                'message' => 'Aucune valeur à modifier.'
            ], 400);
        }

        /* 
        // TODO: ne fonctionne pas: ajouté une condition sur le format de donnée
        if ($data) {
            return $this->json([
                'status'  => false,
                'message' => 'Format de données incorrect.'
            ], 400);
        } */

        /** @var Users $user */
        $user = $this->getUser();

        $doctor = $doctorsRepository->findOneBy(['user' => $user]);

        if (!$doctor) {
            return $this->json([
                'status'  => false,
                'message' => 'Médecin introuvable.'
            ], 404);
        }

        // TODO: Ajouter les FTP pour les autres env (que PROD pour l'instant)
        if ($photo) {
            $path = $fileUploadHelper->upload($photo, 'avatars/doctors');
            // delete l'ancienne photo du FTP 
            if ($doctor->getProfilePicture()) {
                $fileUploadHelper->delete($doctor->getProfilePicture());
            }

            $doctor->setProfilePicture($path);
        }

        // Update fields
        $doctor->setBiography($data['biography'] ?? $doctor->getBiography());
        $doctor->setLicenseNumber($data['licenseNumber'] ?? $doctor->getLicenseNumber());
        $doctor->setTeleconsultationEnabled($data['teleconsultationEnabled'] ?? $doctor->isTeleconsultationEnabled());

        // PERSIST + FLUSH DIRECTEMENT
        $entityManager->persist($doctor);
        $entityManager->flush();

        return $this->json([
            'status'  => true,
            'message' => 'Profil médecin mis à jour avec succès',
            'data' => $doctor
        ], 200, [], ['groups' => ['doctor:read']]);
    }
}
