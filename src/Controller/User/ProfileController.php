<?php

namespace App\Controller\User;

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
