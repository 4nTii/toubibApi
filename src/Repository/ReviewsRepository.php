<?php

namespace App\Repository;

use App\Entity\Reviews;
use App\Entity\Doctors;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reviews>
 *
 * @method Reviews|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reviews|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reviews[]    findAll()
 * @method Reviews[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reviews::class);
    }

    public function add(Reviews $review, bool $flush = true): void
    {
        $this->getEntityManager()->persist($review);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Reviews $review, bool $flush = true): void
    {
        $this->getEntityManager()->remove($review);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère tous les avis d'un médecin
     */
    public function findByDoctor(Doctors $doctor): array
    {
        return $this->findBy(['doctor' => $doctor], ['createdAt' => 'DESC']);
    }

    /**
     * Récupère tous les avis d'un patient
     */
    public function findByPatient(Users $user): array
    {
        return $this->findBy(['patient' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Récupère les avis récents pour un médecin
     */
    public function findRecentByDoctor(Doctors $doctor, int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule la note moyenne d'un médecin
     */
    public function getAverageRating(Doctors $doctor): ?float
    {
        return $this->createQueryBuilder('r')
            ->select('AVG(r.rating) as avgRating')
            ->andWhere('r.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
