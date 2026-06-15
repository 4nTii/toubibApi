<?php

namespace App\Controller\User;

use App\Entity\Appointments;
use App\Repository\AppointmentsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request as HttpFoundationRequest;

class ProfileController extends AbstractController
{
    public function me(): JsonResponse
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

        $response = [
            'status' => true,
            'data'   => $userData
        ];

        return $this->json($response, 200);
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

        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            null,
            null,
            'EEEE d MMMM yyyy'
        );

        $data = array_map(function (Appointments $appt) use ($formatter) {
            $doctor = $appt->getDoctor();
            $doctorUser = $doctor->getUser();
            $bs = $appt->getBusinessSite();

            return [
                'id'                  => $appt->getId(),
                'doctorFirstName'     => $doctorUser->getFirstName(),
                'doctorLastName'      => $doctorUser->getLastName(),
                'doctorSpeciality'    => $doctor->getSpeciality()?->getName(),
                'doctorAvatar'        => $doctor->getProfilePicture(),
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
            'firstName' => 'setFirstName',
            'lastName'  => 'setLastName',
            'birthDay'  => 'setBirthDay',
            'gender'    => 'setGender',
            'address'   => 'setAddress',
            'email'     => 'setEmail'
        ];

        $unexpectedFields = array_diff(array_keys($data), array_keys($allowedFields));
        if (!empty($unexpectedFields)) {
            return $this->json([
                'status'  => false,
                'message' => 'Champs non autorisés : ' . implode(', ', $unexpectedFields)
            ], 400);
        }

        foreach ($allowedFields as $field => $setter) {
            if (array_key_exists($field, $data)) {
                $value = $data[$field];

                if ($field === 'birthDay' && is_string($value)) {
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
        }

        $entityManager->flush();

        return $this->json([
            'status'  => true,
            'message' => 'Profil mis à jour avec succès'
        ], 200);
    }
}
