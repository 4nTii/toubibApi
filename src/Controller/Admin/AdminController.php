<?php

namespace App\Controller\Admin;

use App\Entity\Doctors;
use App\Entity\Specialities;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use DateTime;

class AdminController extends AbstractController
{
    public function listUsers(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $search = trim($request->query->getString('search', ''));
        $role   = trim($request->query->getString('role', ''));
        $page   = max(1, (int) $request->query->get('page', 1));
        $limit  = max(1, min(100, (int) $request->query->get('limit', 10)));

        $qb = $em->createQueryBuilder()
            ->select('u')
            ->from(Users::class, 'u')
            ->orderBy('u.dateInscription', 'DESC');

        if ($search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.firstName)', ':s'),
                    $qb->expr()->like('LOWER(u.lastName)', ':s'),
                    $qb->expr()->like('LOWER(u.email)', ':s'),
                    $qb->expr()->like('u.phone', ':s')
                )
            )->setParameter('s', '%' . strtolower($search) . '%');
        }

        if ($role !== '') {
            $qb->andWhere('u.role = :role')->setParameter('role', $role);
        }

        $total = (clone $qb)->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($total / $limit);
        $page = min($page, max(1, $totalPages));

        /** @var Users[] $users */
        $users = $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $doctorRepo = $em->getRepository(Doctors::class);

        $data = array_map(function (Users $user) use ($doctorRepo) {
            $doctor = $doctorRepo->findOneBy(['user' => $user]);

            return [
                'id'                  => $user->getId(),
                'firstName'           => $user->getFirstName(),
                'lastName'            => $user->getLastName(),
                'email'               => $user->getEmail(),
                'phone'               => $user->getPhone(),
                'role'                => $user->getRole(),
                'isActive'            => $user->isActive(),
                'forcePasswordChange' => $user->isForcePasswordChange(),
                'isEmailVerified'     => $user->isEmailVerified(),
                'dateInscription'     => $user->getDateInscription()?->format('Y-m-d H:i:s'),
                'lastLogin'           => $user->getLastLogin()?->format('Y-m-d H:i:s'),
                'isDoctor'            => $doctor !== null,
                'isDoctorActive'      => $doctor?->isActive(),
                'doctorId'            => $doctor?->getId(),
            ];
        }, $users);

        return $this->json([
            'status'     => true,
            'data'       => $data,
            'pagination' => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => (int) $total,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    public function suspendUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var Users|null $target */
        $target = $em->getRepository(Users::class)->find($id);

        if (!$target) {
            return $this->json(['status' => false, 'message' => 'Utilisateur introuvable'], 404);
        }

        /** @var Users $admin */
        $admin = $this->getUser();
        if ($admin->getId() === $target->getId()) {
            return $this->json(['status' => false, 'message' => 'Vous ne pouvez pas suspendre votre propre compte'], 403);
        }

        $target->setIsActive(!$target->isActive());
        $em->flush();

        $action = $target->isActive() ? 'réactivé' : 'suspendu';

        return $this->json(['status' => true, 'message' => "Compte $action avec succès", 'isActive' => $target->isActive()]);
    }

    public function toggleDoctor(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var Users|null $target */
        $target = $em->getRepository(Users::class)->find($id);

        if (!$target) {
            return $this->json(['status' => false, 'message' => 'Utilisateur introuvable'], 404);
        }

        $doctor = $em->getRepository(Doctors::class)->findOneBy(['user' => $target]);

        if ($doctor === null) {
            // Promote: create a doctor profile
            $speciality = $em->getRepository(Specialities::class)->findOneBy([]);
            if (!$speciality) {
                return $this->json(['status' => false, 'message' => 'Aucune spécialité disponible en base'], 422);
            }

            $doctor = new Doctors();
            $doctor->setUser($target);
            $doctor->setSpeciality($speciality);
            $doctor->setLicenseNumber('ADM-' . $target->getId() . '-' . time());
            $doctor->setActivityStarted(new DateTime());
            $doctor->setIsActive(true);

            if ($target->getRole() === 'ROLE_USER') {
                $target->setRole('ROLE_DOCTOR');
            }

            $em->persist($doctor);
            $em->flush();

            return $this->json([
                'status'         => true,
                'message'        => 'Utilisateur promu médecin',
                'isDoctor'       => true,
                'isDoctorActive' => true,
                'doctorId'       => $doctor->getId(),
            ]);
        }

        // Toggle doctor active status
        $doctor->setIsActive(!$doctor->isActive());
        $em->flush();

        $action = $doctor->isActive() ? 'réactivé' : 'suspendu';

        return $this->json([
            'status'         => true,
            'message'        => "Profil médecin $action",
            'isDoctor'       => true,
            'isDoctorActive' => $doctor->isActive(),
            'doctorId'       => $doctor->getId(),
        ]);
    }

    public function forcePasswordChange(int $id, EntityManagerInterface $em): JsonResponse
    {
        /** @var Users|null $target */
        $target = $em->getRepository(Users::class)->find($id);

        if (!$target) {
            return $this->json(['status' => false, 'message' => 'Utilisateur introuvable'], 404);
        }

        $target->setForcePasswordChange(!$target->isForcePasswordChange());
        $em->flush();

        $state = $target->isForcePasswordChange();

        return $this->json([
            'status'              => true,
            'message'             => $state ? 'Changement de mot de passe requis à la prochaine connexion' : 'Obligation levée',
            'forcePasswordChange' => $state,
        ]);
    }
}
