<?php

namespace App\Controller\BusinessSites;

use App\DTO\CreateBusinessSiteDTO;
use App\Entity\DoctorBusinessSite;
use App\Entity\Users;
use App\Repository\BusinessSitesRepository;
use App\Repository\DoctorsRepository;
use App\Repository\DoctorBusinessSiteRepository;
use App\Repository\RegionsRepository;
use App\Repository\UsersRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BusinessSitesController extends AbstractController
{
    public function createBusinessSite(
        Request $request,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        DoctorsRepository $doctorsRepository,
        RegionsRepository $regionsRepository
    ): JsonResponse {

        try {
            /** @var CreateBusinessSiteDTO $dto */
            $dto = $serializer->deserialize(
                $request->getContent(),
                CreateBusinessSiteDTO::class,
                'json'
            );
        } catch (\Exception $e) {
            return $this->json([
                'status' => false,
                'message' => 'JSON invalide'
            ], 400);
        }

        // Validation
        $errors = $validator->validate($dto);

        if (count($errors) > 0) {
            $formattedErrors = [];
            foreach ($errors as $error) {
                $formattedErrors[] = $error->getPropertyPath() . ' : ' . $error->getMessage();
            }

            return $this->json([
                'status' => false,
                'errors' => $formattedErrors
            ], 400);
        }

        /** @var Users $user */
        $user = $this->getUser();
        $doctor = $doctorsRepository->findOneBy(['user' => $user]);

        if (!$doctor) {
            return $this->json(['status' => false, 'message' => 'Non autorisé'], 403);
        }

        $region = $regionsRepository->find($dto->region);
        if (!$region) {
            return $this->json(['status' => false, 'message' => 'Région invalide'], 404);
        }

        // Création
        $businessSite = new \App\Entity\BusinessSites();
        $businessSite->setName($dto->name);
        $businessSite->setAddress($dto->address);
        $businessSite->setVille($dto->ville);
        $businessSite->setPhone($dto->phone);
        $businessSite->setEmail($dto->email);
        $businessSite->setRegion($region);

        $entityManager->persist($businessSite);

        $dbs = new \App\Entity\DoctorBusinessSite();
        $dbs->setDoctor($doctor);
        $dbs->setBusinessSite($businessSite);
        $dbs->setIsOwner(true);
        $dbs->setIsPrimary(true);

        $dbs->setConsultationDuration($dto->doctorBusinessSite->consultationDuration);
        $dbs->setConsultationFee($dto->doctorBusinessSite->consultationFee);
        $dbs->setWorkingSchedule($dto->doctorBusinessSite->workingSchedule);

        $entityManager->persist($dbs);
        $entityManager->flush();

        return $this->json([
            'status' => true,
            'message' => 'Cabinet créé avec succès'
        ], 201);
    }

    public function updateBusinessSites(
        Request $request,
        EntityManagerInterface $entityManager,
        BusinessSitesRepository $businessSitesRepository,
        DoctorsRepository $doctorsRepository,
        DoctorBusinessSiteRepository $doctorBusinessSiteRepository,
        RegionsRepository $regionsRepository,
        UsersRepository $usersRepository,
        int $businesssitesId
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (empty($data)) {
            return $this->json(['status' => false, 'message' => 'Aucune valeur à modifier.'], 400);
        }

        $businessSite = $businessSitesRepository->find($businesssitesId);
        if (!$businessSite) {
            return $this->json(['status' => false, 'message' => 'Cabinet non trouvé.'], 404);
        }

        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $currentDoctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$currentDoctor) {
            return $this->json(['status' => false, 'message' => 'Docteur non trouvé.'], 404);
        }

        $doctorBusinessSite = $doctorBusinessSiteRepository->findOneBy([
            'doctor'       => $currentDoctor,
            'businessSite' => $businessSite
        ]);

        if (!$doctorBusinessSite) {
            return $this->json(['status' => false, 'message' => 'Vous n\'êtes pas associé à ce cabinet.'], 403);
        }

        $isOwner = $doctorBusinessSite->isOwner();

        // --- Mise à jour des champs de BusinessSites (Owner uniquement) ---
        $businessSiteFields = ['name', 'address', 'ville', 'phone', 'email', 'region'];
        $hasBusinessSiteChanges = !empty(array_intersect(array_keys($data), $businessSiteFields));

        if ($hasBusinessSiteChanges && !$isOwner) {
            return $this->json(['status' => false, 'message' => 'Action réservée au propriétaire du cabinet.'], 403);
        }

        if ($isOwner) {
            if (isset($data['name']))    $businessSite->setName($data['name']);
            if (isset($data['address'])) $businessSite->setAddress($data['address']);
            if (isset($data['ville']))   $businessSite->setVille($data['ville']);
            if (isset($data['phone']))   $businessSite->setPhone($data['phone']);
            if (isset($data['email']))   $businessSite->setEmail($data['email']);

            if (isset($data['region'])) {
                $region = $regionsRepository->find($data['region']);
                if (!$region) {
                    return $this->json(['status' => false, 'message' => 'Région non trouvée.'], 404);
                }
                $businessSite->setRegion($region);
            }
        }

        if (isset($data['doctorBusinessSite'])) {
            $dbsData = $data['doctorBusinessSite'];

            if (isset($dbsData['id'])) {
                $targetDbs = $doctorBusinessSiteRepository->find($dbsData['id']);
                if (!$targetDbs || $targetDbs->getBusinessSite()->getId() !== $businesssitesId) {
                    return $this->json(['status' => false, 'message' => 'Relation docteur-cabinet introuvable.'], 404);
                }

                // Vérifier que le docteur modifie ses propres données ou owner
                $isOwnData = $targetDbs->getDoctor()->getId() === $currentDoctor->getId();
                if (!$isOwnData && !$isOwner) {
                    return $this->json(['status' => false, 'message' => 'Vous ne pouvez modifier que vos propres paramètres.'], 403);
                }
            } else {
                $targetDbs = $doctorBusinessSite;
            }

            if (isset($dbsData['consultationDuration'])) {
                $targetDbs->setConsultationDuration((int) $dbsData['consultationDuration']);
            }
            if (isset($dbsData['consultationFee'])) {
                $targetDbs->setConsultationFee((int) $dbsData['consultationFee']);
            }
            if (isset($dbsData['workingSchedule'])) {
                $targetDbs->setWorkingSchedule($dbsData['workingSchedule']);
            }
        }

        // --- Invitations ownerInvitations (Owner uniquement) ---
        if (isset($data['ownerInvitations']) && is_array($data['ownerInvitations'])) {
            if (!$isOwner) {
                return $this->json(['status' => false, 'message' => 'Seul le propriétaire peut inviter des collaborateurs.'], 403);
            }

            foreach ($data['ownerInvitations'] as $email) {
                // Vérifier que l'email est valide
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $invitedUser = $usersRepository->findByEmail($email);
                if (!$invitedUser) {
                    // TODO: envoyer un mail d'invitation à la plateforme
                    continue;
                }

                $invitedDoctor = $doctorsRepository->findOneBy(['user' => $invitedUser]);
                if (!$invitedDoctor) {
                    // TODO: envoyer un mail d'invitation à la plateforme
                    continue;
                }

                // Vérifier que le docteur n'est pas déja associé à ce cabinet
                $alreadyLinked = $doctorBusinessSiteRepository->findOneBy([
                    'doctor'       => $invitedDoctor,
                    'businessSite' => $businessSite
                ]);

                if ($alreadyLinked) {
                    continue;
                }

                // Ajouter le docteur au cabinet
                $newDbs = new DoctorBusinessSite();
                $newDbs->setDoctor($invitedDoctor);
                $newDbs->setBusinessSite($businessSite);
                $newDbs->setIsOwner(false);
                $newDbs->setIsPrimary(false);
                $entityManager->persist($newDbs);

                // TODO: envoyer un mail de notification au docteur invité
            }
        }

        $entityManager->flush();

        return $this->json(['status' => true, 'message' => 'Cabinet mis à jour avec succès.']);
    }

    public function getBusinessSites(
        BusinessSitesRepository $businessSitesRepository,
        DoctorBusinessSiteRepository $doctorBusinessSiteRepository,
        int $businesssitesId
    ): JsonResponse {
        $businessSite = $businessSitesRepository->find($businesssitesId);
        if (!$businessSite) {
            return $this->json(['status' => false, 'message' => 'Cabinet non trouvé.'], 404);
        }

        $ftpTarget = $this->getParameter('vite_ftp_target');
        $doctorBusinessSites = $doctorBusinessSiteRepository->findByBusinessSite($businessSite);

        $doctors = array_map(function ($dbs) use ($ftpTarget) {
            $doctor = $dbs->getDoctor();
            $user = $doctor->getUser();

            return [
                'doctorBusinessSiteId' => $dbs->getId(),
                'doctorId' => $doctor->getId(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'profilePicture' => $ftpTarget . ltrim($doctor->getProfilePicture(), '/'),
                'biography' => $doctor->getBiography(),
                'speciality' => $doctor->getSpeciality()->getName(),
                'workingSchedule' => $dbs->getWorkingSchedule(),
            ];
        }, $doctorBusinessSites);

        return $this->json([
            'status' => true,
            'data' => [
                'businessSite' => [
                    'id' => $businessSite->getId(),
                    'name' => $businessSite->getName(),
                    'address' => $businessSite->getAddress(),
                    'phone' => $businessSite->getPhone(),
                    'email' => $businessSite->getEmail(),
                    'region' => $businessSite->getRegion() ? [
                        'id' => $businessSite->getRegion()->getId(),
                        'name' => $businessSite->getRegion()->getName(),
                    ] : null,
                ],
                'doctors' => $doctors
            ]
        ]);
    }

    public function deleteDoctorFromBusinessSite(
        EntityManagerInterface $entityManager,
        BusinessSitesRepository $businessSitesRepository,
        DoctorsRepository $doctorsRepository,
        DoctorBusinessSiteRepository $doctorBusinessSiteRepository,
        int $businesssitesId,
        int $doctorId
    ): JsonResponse {

        $businessSite = $businessSitesRepository->find($businesssitesId);
        if (!$businessSite) {
            return $this->json(['status' => false, 'message' => 'Cabinet non trouvé.'], 404);
        }

        /** @var Users $currentUser */
        $currentUser = $this->getUser();
        $currentDoctor = $doctorsRepository->findOneBy(['user' => $currentUser]);
        if (!$currentDoctor) {
            return $this->json(['status' => false, 'message' => 'Docteur non trouvé.'], 404);
        }

        $doctorBusinessSite = $doctorBusinessSiteRepository->findOneBy([
            'doctor'       => $currentDoctor,
            'businessSite' => $businessSite
        ]);

        if (!$doctorBusinessSite) {
            return $this->json(['status' => false, 'message' => 'Vous n\'êtes pas associé à ce cabinet.'], 403);
        }

        $target = $doctorBusinessSiteRepository->find($doctorId);
        if (!$target) {
            return $this->json(['status' => false, 'message' => 'Relation introuvable.'], 404);
        }

        // Vérifier que la relation cible appartient bien à ce cabinet
        if ($target->getBusinessSite()->getId() !== $businesssitesId) {
            return $this->json(['status' => false, 'message' => 'Cette relation n\'appartient pas à ce cabinet.'], 403);
        }

        $isCurrentUser  = $target->getDoctor()->getId() === $currentDoctor->getId();
        $currentIsOwner = $doctorBusinessSite->isOwner();
        $targetIsOwner  = $target->isOwner();

        // Un non-owner ne peut pas supprimer quelqu'un d'autre
        if (!$currentIsOwner && !$isCurrentUser) {
            return $this->json(['status' => false, 'message' => 'Action non autorisée.'], 403);
        }

        // Un non-owner ne peut pas supprimer son propre accès
        if (!$currentIsOwner && $isCurrentUser) {
            return $this->json(['status' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre accès au cabinet.'], 403);
        }

        // Un owner ne peut pas supprimer un autre owner
        if ($currentIsOwner && $targetIsOwner && !$isCurrentUser) {
            return $this->json(['status' => false, 'message' => 'Un propriétaire ne peut pas supprimer un autre propriétaire.'], 403);
        }

        $entityManager->remove($target);
        $entityManager->flush();

        $response = ['status' => true, 'message' => 'Médecin retiré du cabinet avec succès.'];
        return $this->json($response, 200);
    }
}
