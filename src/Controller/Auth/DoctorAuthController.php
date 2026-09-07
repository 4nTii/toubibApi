<?php

namespace App\Controller\Auth;

use App\Entity\LoggingAttempt;
use App\Entity\RefreshToken;
use App\Repository\LoggingAttemptRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UsersRepository;
use App\Service\Auth\LoggingSecurityService;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use DateTime;

class DoctorAuthController extends AbstractController
{
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UsersRepository $usersRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManger,
        LoggingAttemptRepository $loggingAttemptRepository,
        LoggingSecurityService $loggingSecurity,
        RefreshTokenRepository $refreshTokenRepository
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

        // Revoke all existing refresh tokens
        $refreshTokenRepository->revokeAllByUser($user);

        // Generate access token (1 hour)
        $accessToken = $jwtManager->create($user);

        // Generate refresh token (7 days)
        $refreshTokenString = bin2hex(random_bytes(32));
        $refreshToken = new RefreshToken();
        $refreshToken->setUser($user);
        $refreshToken->setToken($refreshTokenString);
        $refreshToken->setExpiresAt(new DateTime('+7 days'));
        $entityManger->persist($refreshToken);

        $user->setLastLogin(new DateTime());
        $entityManger->flush();

        $loggingAttemptRepository->deleteByEmail($user->getEmail());
        $loggingAttemptRepository->deleteByIpAddress($userIp);

        // Access token cookie (HttpOnly)
        $accessCookie = Cookie::create('app_auth')
            ->withValue($accessToken)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('none')
            ->withPath('/')
            ->withExpires(new DateTime('+1 hour'));

        // Refresh token cookie (HttpOnly)
        $refreshCookie = Cookie::create('app_refresh')
            ->withValue($refreshTokenString)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('none')
            ->withPath('/')
            ->withExpires(new DateTime('+7 days'));

        $response = $this->json(['status' => true, 'email' => $user->getEmail()]);
        $response->headers->setCookie($accessCookie);
        $response->headers->setCookie($refreshCookie);
        $response->headers->set('Authorization', 'Bearer ' . $accessToken);

        return $response;
    }
}
