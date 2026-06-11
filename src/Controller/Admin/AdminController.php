<?php

namespace App\Controller\Admin;

use App\Entity\Users;
use App\Entity\Doctors;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AdminController extends AbstractController
{
    public function listUsers(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $search = trim($request->query->getString('search', ''));
        $role   = trim($request->query->getString('role', ''));

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

        /** @var Users[] $users */
        $users = $qb->getQuery()->getResult();

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
                'doctorId'            => $doctor?->getId(),
            ];
        }, $users);

        return $this->json(['status' => true, 'data' => $data]);
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
