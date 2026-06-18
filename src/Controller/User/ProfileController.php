<?php

namespace App\Controller\User;

use App\Entity\Appointments;
use App\Repository\AppointmentsRepository;
use App\Repository\UserCardRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class ProfileController extends AbstractController
{
    public function me(UserCardRepository $userCardRepository): JsonResponse
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $userData = $this->json($user, 200, [], ['groups' => ['user:read']])->getContent();
        $userData = json_decode($userData, true);

        $ftpTarget = $this->getParameter('vite_ftp_target');
        $mainDoctor = $user->getMainDoctor();
        $userData['mainDoctor'] = null;
        if ($mainDoctor) {
            $doctorUser = $mainDoctor->getUser();
            $userData['mainDoctor'] = [
                'id'         => $mainDoctor->getId(),
                'firstName'  => $doctorUser->getFirstName(),
                'lastName'   => $doctorUser->getLastName(),
                'speciality' => $mainDoctor->getSpeciality()?->getName(),
                'photo'      => $ftpTarget . ltrim($mainDoctor->getProfilePicture(), '/'),
            ];
        }

        // Get user card if exists
        $userCard = $userCardRepository->findOneBy(['user' => $user]);
        $userData['userCard'] = null;
        if ($userCard) {
            // Mask card number to show only last 4 digits
            $cardNumber = $userCard->getCardNumber();
            $maskedCardNumber = '•••• •••• •••• ' . substr($cardNumber, -4);

            $userData['userCard'] = [
                'id'         => $userCard->getId(),
                'cardHolder' => $userCard->getCardHolder(),
                'cardNumber' => $maskedCardNumber,
                'expireDate' => $userCard->getExpireDate(),
            ];
        }

        return $this->json(['status' => true, 'data' => $userData], 200);
    }

    public function getAppointments(
        AppointmentsRepository $appointmentsRepository
    ): JsonResponse {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['status' => false, 'message' => 'Utilisateur non authentifié'], 401);
        }

        $appointments = $appointmentsRepository->createQueryBuilder('a')
            ->andWhere('a.patient = :user')
            ->setParameter('user', $user)
            ->orderBy('a.startTime', 'DESC')
            ->getQuery()
            ->getResult();

        $ftpTarget = $this->getParameter('vite_ftp_target');
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            null,
            null,
            'EEEE d MMMM yyyy'
        );

        $data = array_map(function (Appointments $appt) use ($formatter, $ftpTarget) {
            $doctor = $appt->getDoctor();
            $doctorUser = $doctor->getUser();
            $bs = $appt->getBusinessSite();

            return [
                'id'                  => $appt->getId(),
                'doctorFirstName'     => $doctorUser->getFirstName(),
                'doctorLastName'      => $doctorUser->getLastName(),
                'doctorSpeciality'    => $doctor->getSpeciality()?->getName(),
                'doctorAvatar'        => $ftpTarget . ltrim($doctor->getProfilePicture(), '/'),
                'doctorEmail'         => $doctorUser->getEmail(),
                'doctorPhone'         => $doctorUser->getPhone(),
                'businessSiteName'    => $bs->getName(),
                'businessSiteAddress' => $bs->getAddress(),
                'date'                => ucfirst($formatter->format($appt->getStartTime())),
                'dateIso'             => $appt->getStartTime()->format('Y-m-d'),
                'timeStart'           => $appt->getStartTime()->format('H:i'),
                'timeEnd'             => $appt->getEndTime()->format('H:i'),
                'status'              => $appt->getStatus(),
                'notes'               => $appt->getNotes(),
            ];
        }, $appointments);

        return $this->json(['status' => true, 'message' => 'Rendez-vous récupérés', 'data' => $data]);
    }

    public function editProfile(HttpFoundationRequest $request, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'status' => false,
                'message' => 'Corps de requête invalide ou vide'
            ], 400);
        }

        $allowedFields = [
            'firstName'    => 'setFirstName',
            'lastName'     => 'setLastName',
            'birthDay'     => 'setBirthDay',
            'gender'       => 'setGender',
            'address'      => 'setAddress',
            'email'        => 'setEmail',
            'socialNumber' => 'setSocialNumber',
        ];

        $nullableFields = ['address', 'birthDay', 'socialNumber'];

        $unexpectedFields = array_diff(array_keys($data), array_keys($allowedFields));
        if (!empty($unexpectedFields)) {
            return $this->json([
                'status'  => false,
                'message' => 'Champs non autorisés : ' . implode(', ', $unexpectedFields)
            ], 400);
        }

        foreach ($allowedFields as $field => $setter) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if ($value === null || $value === '') {
                if (!in_array($field, $nullableFields)) {
                    return $this->json([
                        'status'  => false,
                        'message' => "Le champ '$field' ne peut pas être vide"
                    ], 400);
                }
                $user->$setter(null);
                continue;
            }

            if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $this->json([
                    'status'  => false,
                    'message' => "Format d'email invalide"
                ], 400);
            }

            if ($field === 'birthDay') {
                try {
                    $value = new \DateTime($value);
                } catch (\Exception $e) {
                    return $this->json([
                        'status'  => false,
                        'message' => 'Format de date invalide, utilisez ISO 8601 (ex: 1990-05-21)'
                    ], 400);
                }
            }

            $user->$setter($value);
        }

        $entityManager->flush();

        return $this->json([
            'status'  => true,
            'message' => 'Profil mis à jour avec succès'
        ], 200);
    }

    public function changePassword(
        HttpFoundationRequest $request,
        EntityManagerInterface $entityManager,
        \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher,
        \App\Service\Auth\AuthValidatorService $authValidator
    ): JsonResponse
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['oldPassword']) || !isset($data['newPassword'])) {
            return $this->json([
                'status' => false,
                'message' => 'Les champs oldPassword et newPassword sont requis'
            ], 400);
        }

        // Verify old password
        if (!$passwordHasher->isPasswordValid($user, $data['oldPassword'])) {
            return $this->json([
                'status' => false,
                'message' => 'L\'ancien mot de passe est incorrect'
            ], 401);
        }

        // Validate new password strength
        $passwordError = $authValidator->validatePassword($data['newPassword']);
        if ($passwordError) {
            return $this->json([
                'status' => false,
                'message' => $passwordError
            ], 400);
        }

        // Hash and set new password
        $hashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
        $user->setPassword($hashedPassword);
        $entityManager->flush();

        return $this->json([
            'status' => true,
            'message' => 'Mot de passe mis à jour avec succès'
        ], 200);
    }

    public function addOrUpdateCard(
        HttpFoundationRequest $request,
        EntityManagerInterface $entityManager,
        \App\Repository\UserCardRepository $userCardRepository
    ): JsonResponse
    {
        /** @var \App\Entity\Users $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'status' => false,
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        if (!isset($data['card_holder']) || !isset($data['card_number']) || !isset($data['expire_date']) || !isset($data['card_cvv'])) {
            return $this->json([
                'status' => false,
                'message' => 'Les champs card_holder, card_number, expire_date et card_cvv sont requis'
            ], 400);
        }

        // Validate card number (16 digits)
        $cardNumber = preg_replace('/\D/', '', $data['card_number']);
        if (strlen($cardNumber) !== 16) {
            return $this->json([
                'status' => false,
                'message' => 'Le numéro de carte doit contenir 16 chiffres'
            ], 400);
        }

        // Validate CVV (3-4 digits)
        $cvv = preg_replace('/\D/', '', $data['card_cvv']);
        if (strlen($cvv) < 3 || strlen($cvv) > 4) {
            return $this->json([
                'status' => false,
                'message' => 'Le CVV doit contenir 3 ou 4 chiffres'
            ], 400);
        }

        // Validate expiry date (MM/YY format and must be future)
        if (!preg_match('/^\d{2}\/\d{2}$/', $data['expire_date'])) {
            return $this->json([
                'status' => false,
                'message' => 'La date d\'expiration doit être au format MM/AA'
            ], 400);
        }

        list($month, $year) = explode('/', $data['expire_date']);
        $month = (int)$month;
        $year = (int)$year;

        if ($month < 1 || $month > 12) {
            return $this->json([
                'status' => false,
                'message' => 'Le mois d\'expiration doit être entre 01 et 12'
            ], 400);
        }

        // Check if expiry date is in the future
        $currentYear = date('y');
        $currentMonth = date('m');
        if ($year < $currentYear || ($year == $currentYear && $month < $currentMonth)) {
            return $this->json([
                'status' => false,
                'message' => 'La date d\'expiration doit être une date future'
            ], 400);
        }

        // Get or create user card
        $userCard = $userCardRepository->findOneBy(['user' => $user]);
        if (!$userCard) {
            $userCard = new \App\Entity\UserCard();
            $userCard->setUser($user);
        }

        // Update card details
        $userCard->setCardHolder($data['card_holder']);
        $userCard->setCardNumber($cardNumber);
        $userCard->setExpireDate($data['expire_date']);
        $userCard->setCardCvv($cvv);

        $entityManager->persist($userCard);
        $entityManager->flush();

        // Mask card number to show only last 4 digits
        $maskedCardNumber = '•••• •••• •••• ' . substr($userCard->getCardNumber(), -4);

        return $this->json([
            'status' => true,
            'message' => 'Carte bancaire ajoutée avec succès',
            'data' => [
                'id' => $userCard->getId(),
                'cardHolder' => $userCard->getCardHolder(),
                'cardNumber' => $maskedCardNumber,
                'expireDate' => $userCard->getExpireDate(),
            ]
        ], 200);
    }
}
