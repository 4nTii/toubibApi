<?php

namespace App\Controller\Doctors;

use App\Entity\Users;
use App\Repository\BusinessSitesRepository;
use App\Repository\DoctorsRepository;
use App\Service\Helper\AppointmentsHelper;
use App\Service\Helper\FileUploadHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DoctorsController extends AbstractController
{
    public function getAllDoctors(
        Request $request,
        DoctorsRepository $doctorsRepository
    ): JsonResponse {
        $page      = $request->query->getInt('page', 1);
        $limit     = $request->query->getInt('limit', 5);
        $ftpTarget = $this->getParameter('vite_ftp_target');
        $doctors   = $doctorsRepository->getAllDoctors($page, $limit);

        if (empty($doctors)) {
            return $this->json(['status' => false, 'message' => 'Aucun resultat trouvé'], 200);
        }

        foreach ($doctors as $doctor) {
            if ($doctor->getProfilePicture()) {
                $doctor->setProfilePicture($ftpTarget . ltrim($doctor->getProfilePicture(), '/'));
            }
        }

        return $this->json([
            'status' => true,
            'data'   => ['page' => $page, 'limit' => $limit, 'doctors' => $doctors],
        ], 200, [], ['groups' => ['doctor:read']]);
    }

    public function me(DoctorsRepository $doctorsRepository): JsonResponse
    {
        /** @var Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['status' => false, 'message' => 'Utilisateur non authentifié'], 401);
        }

        $doctor = $doctorsRepository->findOneBy(['user' => $user]);

        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Cet utilisateur ne correspond pas à un médecin'], 200);
        }

        $ftpTarget = $this->getParameter('vite_ftp_target');
        if ($doctor->getProfilePicture()) {
            $doctor->setProfilePicture($ftpTarget . ltrim($doctor->getProfilePicture(), '/'));
        }

        return $this->json(['status' => true, 'data' => ['doctor' => $doctor]], 200, [], ['groups' => ['doctor:read']]);
    }

    public function getDoctorInfo(
        int $id,
        DoctorsRepository $doctorsRepository,
        BusinessSitesRepository $businessSitesRepository,
        AppointmentsHelper $appointmentsHelper
    ): JsonResponse {
        $doctor = $doctorsRepository->find($id);
        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Cet utilisateur ne correspond pas à un médecin'], 404);
        }

        $ftpTarget = $this->getParameter('vite_ftp_target');
        if ($doctor->getProfilePicture()) {
            $doctor->setProfilePicture($ftpTarget . ltrim($doctor->getProfilePicture(), '/'));
        }

        $response = ['doctor' => $doctor, 'availableSlots' => []];

        if ($doctor->isActive()) {
            $primaryBusinessSite = $businessSitesRepository->getPrimaryBusinessSite($doctor);

            if ($primaryBusinessSite) {
                $dateStart = new \DateTimeImmutable('now');
                $dateEnd   = $dateStart->modify('+6 days');

                $response['availableSlots'] = $appointmentsHelper->getSlotsByDates(
                    $doctor,
                    $primaryBusinessSite,
                    [$dateStart, $dateEnd],
                    true
                );
            }
        }

        return $this->json(['status' => true, 'data' => $response], 200, [], ['groups' => ['doctor:read']]);
    }

    public function updateConnectedDoctor(
        Request $request,
        DoctorsRepository $doctorsRepository,
        FileUploadHelper $fileUploadHelper,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data  = $request->request->get('data');
        $photo = $request->files->get('profilePicture');

        if ($data !== null) {
            $data = json_decode($data, true);
        } else {
            $data = json_decode($request->getContent(), true);
        }

        if (empty($data) && !$photo) {
            return $this->json(['status' => false, 'message' => 'Aucune valeur à modifier.'], 400);
        }

        /** @var Users $user */
        $user   = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $user]);

        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Médecin introuvable.'], 404);
        }

        if ($photo) {
            $path = $fileUploadHelper->upload($photo, 'avatars/doctors');
            if ($doctor->getProfilePicture()) {
                $fileUploadHelper->delete($doctor->getProfilePicture());
            }
            $doctor->setProfilePicture($path);
        }

        $doctor->setBiography($data['biography'] ?? $doctor->getBiography());
        $doctor->setLicenseNumber($data['licenseNumber'] ?? $doctor->getLicenseNumber());
        $doctor->setTeleconsultationEnabled($data['teleconsultationEnabled'] ?? $doctor->isTeleconsultationEnabled());

        $entityManager->persist($doctor);
        $entityManager->flush();

        return $this->json([
            'status'  => true,
            'message' => 'Profil médecin mis à jour avec succès',
            'data'    => $doctor,
        ], 200, [], ['groups' => ['doctor:read']]);
    }
}
