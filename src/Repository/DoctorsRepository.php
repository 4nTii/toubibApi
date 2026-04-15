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
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
            ->leftJoin('d.speciality', 's')
            ->addSelect('s')
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
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
            ->orderBy('u.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne tous les médecins
     */
    public function getAllDoctors($page = 1, $limit = 5): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('d')
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->leftJoin('d.speciality', 's')
            ->addSelect('s')
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
            ->orderBy('u.id', 'DESC')
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
            ->andWhere('d.speciality = :speciality')
            ->andWhere('d.isActive = :active')
            ->setParameter('speciality', $speciality)
            ->setParameter('active', true)
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les médecins actifs d'un cabinet
     */
    public function findByBusinessSite(BusinessSites $businessSite): array
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
            ->andWhere('bs = :businessSite')
            ->andWhere('d.isActive = :active')
            ->setParameter('businessSite', $businessSite)
            ->setParameter('active', true)
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->leftJoin('d.speciality', 's')
            ->addSelect('s')
            ->orderBy('u.lastName', 'ASC')
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
            ->addSelect('u')
            ->andWhere('d.isActive = :active')
            ->andWhere('u.firstName LIKE :name OR u.lastName LIKE :name')
            ->setParameter('active', true)
            ->setParameter('name', '%' . $name . '%')
            ->leftJoin('d.speciality', 's')
            ->addSelect('s')
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
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
            ->andWhere('d.isActive = :active')
            ->setParameter('active', true)
            ->leftJoin('d.unavailabilitySlots', 'us')
            ->andWhere('us.id IS NULL OR us.endTime < :fromDate OR us.startTime > :fromDate')
            ->setParameter('fromDate', $fromDate)
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->leftJoin('d.speciality', 's')
            ->addSelect('s')
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le owner d'un cabinet
     */
    public function findOwnerByBusinessSite(int $businessSiteId): ?Doctors
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->addSelect('bs')
            ->andWhere('bs.id = :businessSiteId')
            ->andWhere('dbs.isOwner = true')
            ->setParameter('businessSiteId', $businessSiteId)
            ->leftJoin('d.user', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
