<?php

namespace App\Repository;

use App\Entity\Doctors;
use App\Entity\Specialities;
use App\Entity\BusinessSites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Doctors>
 *
 * @method Doctors|null find($id, $lockMode = null, $lockVersion = null)
 * @method Doctors|null findOneBy(array $criteria, array $orderBy = null)
 * @method Doctors[]    findAll()
 * @method Doctors[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DoctorsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Doctors::class);
    }

    public function add(Doctors $doctor, bool $flush = true): void
    {
        $this->getEntityManager()->persist($doctor);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Doctors $doctor, bool $flush = true): void
    {
        $this->getEntityManager()->remove($doctor);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouver un médecin par UserId
     */
    public function findByUserId(int $userId): ?Doctors
    {
        return $this->createQueryBuilder('d')
            ->join('d.user', 'u')
            ->andWhere('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne tous les médecins actifs
     */
    public function getAllActiveDoctors($page = 1, $limit = 5): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('d')
            ->andWhere('d.isActive = :active')
            ->setParameter('active', true)
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->leftJoin('d.speciality', 's')
            ->addSelect('s')
            ->orderBy('u.id', 'DESC')  // trier par user.id
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les médecins actifs
     */
    public function getAllDoctors($page = 1, $limit = 5): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('d')
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->leftJoin('d.speciality', 's')
            ->addSelect('s')
            ->orderBy('u.id', 'DESC')  // trier par user.id
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les médecins actifs d'une spécialité donnée
     */
    public function findBySpecialty(Specialities $speciality): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.specialty = :specialty')
            ->andWhere('d.isActive = :active')
            ->setParameter('specialty', $speciality)
            ->setParameter('active', true)
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les médecins actifs d'un cabinet (business site)
     */
    public function findByBusinessSite(BusinessSites $businessSite): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.businessSite = :businessSite')
            ->andWhere('d.isActive = :active')
            ->setParameter('businessSite', $businessSite)
            ->setParameter('active', true)
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des médecins actifs par nom (partial match)
     */
    public function searchByName(string $name): array
    {
        return $this->createQueryBuilder('d')
            ->join('d.user', 'u')
            ->andWhere('d.isActive = :active')
            ->andWhere('u.firstName LIKE :name OR u.lastName LIKE :name')
            ->setParameter('active', true)
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les médecins actifs disponibles à partir d'une date donnée
     */
    public function findAvailableDoctors(\DateTimeInterface $fromDate): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.unavailabilitySlots', 'u')
            ->andWhere('d.isActive = :active')
            ->andWhere('u.id IS NULL OR u.endTime < :fromDate OR u.startTime > :fromDate')
            ->setParameter('active', true)
            ->setParameter('fromDate', $fromDate)
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
