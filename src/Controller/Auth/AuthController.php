<?php

namespace App\Controller\Auth;

use App\Entity\LoggingAttempt;
use App\Entity\RefreshToken;
use App\Entity\Users;
use App\Entity\UsersPasswordResetToken;
use App\Repository\LoggingAttemptRepository;
use App\Repository\RefreshTokenRepository;
use App\Repository\UsersPasswordResetTokenRepository;
use App\Repository\UsersRepository;
use DateTime;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\Auth\AuthValidatorService;
use App\Service\Auth\LoggingSecurityService;
use App\Service\Mailer\UserMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;

class AuthController extends AbstractController
{
    public function login(
        HttpFoundationRequest $request,
        JWTTokenManagerInterface $jwtManager,
        UsersRepository $usersRepository,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManger,
        LoggingAttemptRepository $loggingAttemptRepository,
        LoggingSecurityService $loggingSecurity,
        RefreshTokenRepository $refreshTokenRepository
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

        if (!$user->isActive()) {
            return $this->json([
                'status'  => false,
                'message' => 'Veuillez vérifier votre compte, un courriel de vérification a été envoyé à votre adresse courriel.',
            ], 403);
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
        $refreshToken->setExpiresAt(new \DateTime('+7 days'));
        $entityManger->persist($refreshToken);

        // Update user + cleanup logs
        $user->setLastLogin(new \DateTime());
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
            ->withExpires(new \DateTime('+1 hour'));

        // Refresh token cookie (HttpOnly)
        $refreshCookie = Cookie::create('app_refresh')
            ->withValue($refreshTokenString)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('none')
            ->withPath('/')
            ->withExpires(new \DateTime('+7 days'));

        // Response with both cookies
        $response = $this->json([
            'status' => true,
            'email'  => $user->getEmail(),
        ]);

        $response->headers->setCookie($accessCookie);
        $response->headers->setCookie($refreshCookie);
        $response->headers->set('Authorization', 'Bearer ' . $accessToken);

        return $response;
    }

    public function logout(
        HttpFoundationRequest $request,
        RefreshTokenRepository $refreshTokenRepository,
        EntityManagerInterface $entityManger
    ): JsonResponse {
        // Get refresh token from cookie
        $refreshToken = $request->cookies->get('app_refresh');
        if ($refreshToken) {
            $token = $refreshTokenRepository->findValidByToken($refreshToken);
            if ($token) {
                $token->setRevoked(true);
                $entityManger->flush();
            }
        }

        $response = new JsonResponse(['status' => true, 'message' => 'Logged out']);

        // Clear both cookies
        $response->headers->setCookie(
            Cookie::create('app_auth')
                ->withValue('')
                ->withHttpOnly(true)
                ->withSecure(true)
                ->withSameSite('none')
                ->withPath('/')
                ->withExpires(new \DateTime('-1 hour'))
        );

        $response->headers->setCookie(
            Cookie::create('app_refresh')
                ->withValue('')
                ->withHttpOnly(true)
                ->withSecure(true)
                ->withSameSite('none')
                ->withPath('/')
                ->withExpires(new \DateTime('-1 hour'))
        );

        return $response;
    }

    public function refresh(
        HttpFoundationRequest $request,
        JWTTokenManagerInterface $jwtManager,
        RefreshTokenRepository $refreshTokenRepository,
        EntityManagerInterface $entityManger
    ): JsonResponse {
        $refreshTokenString = $request->cookies->get('app_refresh');

        if (!$refreshTokenString) {
            return $this->json([
                'status' => false,
                'message' => 'Refresh token not found',
            ], 401);
        }

        $refreshToken = $refreshTokenRepository->findValidByToken($refreshTokenString);

        if (!$refreshToken) {
            return $this->json([
                'status' => false,
                'message' => 'Invalid or expired refresh token',
            ], 401);
        }

        $user = $refreshToken->getUser();

        // Generate new access token
        $newAccessToken = $jwtManager->create($user);

        // Update refresh token expiry to extend session
        $refreshToken->setExpiresAt(new \DateTime('+7 days'));
        $entityManger->flush();

        // New access token cookie
        $accessCookie = Cookie::create('app_auth')
            ->withValue($newAccessToken)
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite('none')
            ->withPath('/')
            ->withExpires(new \DateTime('+1 hour'));

        $response = $this->json([
            'status' => true,
            'message' => 'Token refreshed',
        ]);

        $response->headers->setCookie($accessCookie);
        $response->headers->set('Authorization', 'Bearer ' . $newAccessToken);

        return $response;
    }

    public function register(
        HttpFoundationRequest $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UsersRepository $usersRepository,
        ValidatorInterface $validator,
        AuthValidatorService $addUserValidate,
        UserMailerService $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['firstName'], $data['lastName'], $data['email'], $data['password'], $data['phone'], $data['birthDay'], $data['gender'])) {
            return $this->json([
                'status' => false,
                'message' => 'Certains paramètres sont manquants.'
            ], 400);
        }

        $user = new Users();
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setGender($data['gender']);
        $user->setEmail($data['email']);
        $user->setPassword($data['password']);
        $user->setPhone($data['phone']);
        $user->setBiography('New user');
        $user->setBirthDay(new \DateTime($data['birthDay']));
        $user->setLastLogin(null);
        $user->setRole('ROLE_USER');

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return $this->json([
                'status'  => false,
                'message' => $errors[0]->getMessage(),
            ], 400);
        }

        $errorsBeforeFlush = $addUserValidate->validateAll($user);
        if (!empty($errorsBeforeFlush)) {
            return $this->json([
                'status' => false,
                'message' => array_values($errorsBeforeFlush)[0],
            ], 400);
        }

        $user->setPassword($userPasswordHasher->hashPassword($user, $user->getPassword()));
        $usersRepository->add($user, true);

        if ($user->getId() === null) {
            return $this->json([
                'status' => false,
                'message' => 'Impossible d\'ajouter l\'utilisateur à la base de données'
            ], 500);
        }

        $sendEmail = $mailer->sendVerificationEmail($user);

        return $this->json([
            'status' => true,
            'message' => 'Utilisateur créé' . ($sendEmail ? ', un mail de vérification a été envoyé' : ', impossible d\'envoyer le mail de vérification'),
            'data' => [
                'userId' => $user->getId(),
                'userEmail' => $user->getEmail()
            ]
        ], 201);
    }

    public function verifyAccount(
        HttpFoundationRequest $request,
        UsersRepository $usersRepository,
        EntityManagerInterface $entityManger,
    ): JsonResponse {

        $data = json_decode($request->getContent(), true);

        $userId = $data['user'] ?? null;
        $userToken = $data['token'] ?? null;

        if (!$userId || !is_numeric($userId) || !$userToken) {
            return $this->json([
                'status' => false,
                'message' => 'Paramètres manquants ou invalides'
            ], 400);
        }

        $user = $usersRepository->find($userId);
        $userRealToken = $user?->getUserToken() ?? null;

        if (!$user || !$userRealToken) {
            return $this->json([
                'status' => false,
                'message' => 'Aucun résultat n\'a été trouvé'
            ], 404);
        }

        if ($user->isActive()) {
            return $this->json([
                'status' => false,
                'message' => 'Ce lien est expiré ou a déjà été utilisé'
            ], 409);
        }

        if ($userToken !== $userRealToken) {
            return $this->json([
                'status' => false,
                'message' => 'Token invalide'
            ], 401);
        }

        $user->setIsActive(true);
        $user->setIsEmailVerified(true);
        $entityManger->flush();
        return $this->json([
            'status' => true,
            'message' => 'Compte activé'
        ], 200);
    }

    public function forgotPassword(
        HttpFoundationRequest $request,
        UsersRepository $usersRepository,
        UsersPasswordResetTokenRepository $usersPwdTokenRepository,
        UserMailerService $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userEmail = $data['email'] ?? null;

        if (!$userEmail) {
            return $this->json([
                'status' => false,
                'message' => 'Paramètres manquants ou invalides'
            ], 400);
        }

        $user = $usersRepository->findOneBy(['email' => $userEmail]);
        if (!$user) {
            // Volontairement vague pour ne pas exposer l'existance d'un compte
            return $this->json([
                'status' => true,
                'message' => 'Un lien de réinitialisation a été envoyé à votre adresse'
            ], 200);
        }

        $existingToken = $usersPwdTokenRepository->findValidTokenForUser($user);
        if ($existingToken) {
            return $this->json([
                'status' => true,
                'message' => 'Un lien de réinitialisation a été envoyé à votre adresse'
            ], 200);
        }

        $usersPwdTokenRepository->deleteAllForUser($user);

        $resetToken = new UsersPasswordResetToken($user);
        $usersPwdTokenRepository->createToken($resetToken, flush: true);

        $sendEmail = $mailer->sendPasswordResetEmail($user, $resetToken->getToken());
        $message = 'Un lien de réinitialisation a été envoyé à votre adresse';
        if (!$sendEmail) {
            $message = 'Impossible d\'envoyer le mail de réinitialisation';
        }

        return $this->json([
            'status' => $sendEmail,
            'message' => $message
        ], $sendEmail ? 200 : 500);
    }

    public function checkResetToken(
        HttpFoundationRequest $request,
        UsersPasswordResetTokenRepository $usersPwdTokenRepository,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $tokenValue = $data['token'] ?? null;

        if (!$tokenValue) {
            return $this->json([
                'status' => false,
                'message' => 'Paramètres manquants ou invalides'
            ], 400);
        }

        $resetToken = $usersPwdTokenRepository->findByToken($tokenValue);
        if (!$resetToken) {
            return $this->json([
                'status' => false,
                'message' => 'Token invalide'
            ], 404);
        }

        if (!$usersPwdTokenRepository->isTokenValid($resetToken)) {
            $usersPwdTokenRepository->removeToken($resetToken, flush: true);
            return $this->json([
                'status' => false,
                'message' => 'Token expiré'
            ], 410);
        }

        return $this->json([
            'status' => true,
            'message' => 'Token valide'
        ], 200);
    }

    public function resetPassword(
        HttpFoundationRequest $request,
        UsersRepository $usersRepository,
        UsersPasswordResetTokenRepository $usersPwdTokenRepository,
        UserPasswordHasherInterface $userPasswordHasher,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $tokenValue = $data['token'] ?? null;
        $newPassword = $data['password'] ?? null;

        if (!$tokenValue || !$newPassword) {
            return $this->json([
                'status' => false,
                'message' => 'Paramètres manquants ou invalides'
            ], 400);
        }

        $resetToken = $usersPwdTokenRepository->findByToken($tokenValue);
        if (!$resetToken) {
            return $this->json([
                'status' => false,
                'message' => 'Token invalide'
            ], 404);
        }

        if (!$usersPwdTokenRepository->isTokenValid($resetToken)) {
            $usersPwdTokenRepository->removeToken($resetToken, flush: true);
            return $this->json([
                'status' => false,
                'message' => 'Token expiré'
            ], 410);
        }

        $user = $resetToken->getUser();
        $user->setPassword($userPasswordHasher->hashPassword($user, $newPassword));

        $usersPwdTokenRepository->removeToken($resetToken, flush: true);
        $usersRepository->add($user, true);

        return $this->json([
            'status' => true,
            'message' => 'Mot de passe réinitialisé avec succès'
        ], 200);
    }
}
