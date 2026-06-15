<?php

namespace App\Controller\Auth;

use App\Entity\LoggingAttempt;
use App\Repository\LoggingAttemptRepository;
use App\Repository\UsersRepository;
use App\Service\Auth\LoggingSecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class DoctorAuthController extends AbstractController
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
        $data      = json_decode($request->getContent(), true);
        $userAgent = $request->headers->get('User-Agent');
        $userIp    = $request->getClientIp();

        if (!isset($data['username'], $data['password'])) {
            $loggingAttemptRepository->add(new LoggingAttempt(null, $userIp, $userAgent), true);
            return $this->json(['status' => false, 'message' => 'Nom d\'utilisateur et mot de passe requis'], 400);
        }

        if (!$loggingSecurity->verifyLoggingAbility($userIp, $data['username'])) {
            return $this->json(['status' => false, 'message' => 'Trop de tentatives de connexion, veuillez réessayer plus tard'], 429);
        }

        $user = $usersRepository->findByEmail($data['username']);
        if (!$user || !$userPasswordHasher->isPasswordValid($user, $data['password'])) {
            $loggingAttemptRepository->add(new LoggingAttempt($user?->getEmail(), $userIp, $userAgent), true);
            return $this->json(['status' => false, 'message' => 'Identifiants invalides'], 401);
        }

        if ($user->getRole() !== 'ROLE_DOCTOR' && $user->getRole() !== 'ROLE_ADMIN') {
            return $this->json(['status' => false, 'message' => 'Cet utilisateur ne correspond pas à un médecin'], 403);
        }

        if (!$user->isActive()) {
            return $this->json(['status' => false, 'message' => 'Veuillez vérifier votre compte, un courriel de vérification a été envoyé à votre adresse courriel.'], 403);
        }

        $token = $jwtManager->create($user);

        $cookie = Cookie::create('app_auth')
            ->withValue($token)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('none')
            ->withPath('/')
            ->withExpires(new \DateTime('+1 hour'));

        $user->setLastLogin(new \DateTime());
        $entityManger->flush();

        $loggingAttemptRepository->deleteByEmail($user->getEmail());
        $loggingAttemptRepository->deleteByIpAddress($userIp);

        $response = $this->json(['status' => true, 'email' => $user->getEmail()]);
        $response->headers->setCookie($cookie);
        $response->headers->set('Authorization', 'Bearer ' . $token);

        return $response;
    }
}
