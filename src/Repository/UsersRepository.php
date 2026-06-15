<?php

namespace App\Repository;

use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Users>
 *
 * @method Users|null find($id, $lockMode = null, $lockVersion = null)
 * @method Users|null findOneBy(array $criteria, array $orderBy = null)
 * @method Users[]    findAll()
 * @method Users[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsersRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Users::class);
    }

    public function add(Users $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Users $user, bool $flush = true): void
    {
        $this->getEntityManager()->remove($user);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère un utilisateur par email
     */
    public function findByEmail(string $email): ?Users
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Récupère un utilisateur par phone
     */
    public function findByPhone(string $phone): ?Users
    {
        return $this->findOneBy(['phone' => $phone]);
    }

    /**
     * Recherche des utilisateurs par nom ou prénom
     */
    public function searchByName(string $term): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.firstName LIKE :term OR u.lastName LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les utilisateurs par rôle
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role))
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByMainDoctorPaginated(\App\Entity\Doctors $doctor, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $data = $this->createQueryBuilder('u')
            ->andWhere('u.mainDoctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->orderBy('u.lastName', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.mainDoctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'data'       => $data,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ];
    }

    /**
     * Récupère les utilisateurs récemment connectés
     */
    public function findRecentLogins(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.lastLogin IS NOT NULL')
            ->orderBy('u.lastLogin', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
