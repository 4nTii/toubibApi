<?php

namespace App\Controller\Doctors;

use App\Entity\LoggingAttempt;
use App\Entity\Users;
use App\Repository\BusinessSitesRepository;
use App\Repository\DoctorsRepository;
use App\Repository\LoggingAttemptRepository;
use App\Repository\UsersRepository;
use App\Service\Auth\LoggingSecurityService;
use App\Service\Helper\AppointmentsHelper;
use App\Service\Helper\FileUploadHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DoctorsController extends AbstractController
{

    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UsersRepository $usersRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManger,
        LoggingAttemptRepository $loggingAttemptRepository,
        LoggingSecurityService $loggingSecurity
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userAgent = $request->headers->get('User-Agent');
        $userIp = $request->getClientIp();
        $origin = $request->headers->get('origin');

        if (!isset($data['username'], $data['password'])) {
            $loggingAttemptRepository->add(new LoggingAttempt(null, $userIp, $userAgent), true);
            return $this->json([
                'status'  => false,
                'message' => 'Nom d\'utilisateur et mot de passe requis',
            ], 400);
        }

        if (!$loggingSecurity->verifyLoggingAbility($userIp, $data['username'])) {
            return $this->json([
                'status'  => false,
                'message' => 'Trop de tentatives de connexion, veuillez réessayer plus tard',
            ], 429);
        }

        $user = $usersRepository->findByEmail($data['username']);
        if (!$user || !$userPasswordHasher->isPasswordValid($user, $data['password'])) {
            $loggingAttemptRepository->add(new LoggingAttempt($user?->getEmail(), $userIp, $userAgent), true);
            return $this->json([
                'status'  => false,
                'message' => 'Identifiants invalides',
            ], 401);
        }

        if ($user->getRole() !== 'ROLE_DOCTOR' && $user->getRole() !== 'ROLE_ADMIN') {
            return $this->json([
                'status'  => false,
                'message' => 'Cet utilisateur ne correspond pas à un médecin',
            ], 403);
        }

        if (!$user->isActive()) {
            return $this->json([
                'status'  => false,
                'message' => 'Veuillez vérifier votre compte, un courriel de vérification a été envoyé à votre adresse courriel.',
            ], 403);
        }

        // Generation du token
        $token = $jwtManager->create($user);

        // Cookie HttpOnly
        $cookie = Cookie::create('app_auth')
            ->withValue($token)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('none')
            ->withPath('/')
            ->withExpires(new \DateTime('+1 hour'));

        // update user + nettoyage logs
        $user->setLastLogin(new \DateTime());
        $entityManger->flush();

        $loggingAttemptRepository->deleteByEmail($user->getEmail());
        $loggingAttemptRepository->deleteByIpAddress($userIp);

        // cookie + header Authorization
        $response = $this->json([
            'status' => true,
            'email'  => $user->getEmail(),
        ]);

        $response->headers->setCookie($cookie);
        $response->headers->set('Authorization', 'Bearer ' . $token);

        return $response;
    }

    public function getAllDoctors(
        Request $request,
        DoctorsRepository $doctorsRepository
    ): JsonResponse {
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

    public function me(DoctorsRepository $doctorsRepository): JsonResponse
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

    public function getDoctorInfo(
        int $id,
        DoctorsRepository $doctorsRepository,
        BusinessSitesRepository $businessSitesRepository,
        AppointmentsHelper $appointmentsHelper
    ): JsonResponse {
        $doctor = $doctorsRepository->find($id);
        if (!$doctor) {
            return $this->json([
                'status'  => false,
                'message' => 'Cet utilisateur ne correspond pas à un médecin'
            ], 404);
        }

        $response = [
            'doctor'         => $doctor,
            'availableSlots' => []
        ];

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

        return $this->json([
            'status' => true,
            'data'   => $response
        ], 200, [], ['groups' => ['doctor:read']]);
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
