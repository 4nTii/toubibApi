<?php

namespace App\Repository;

use App\Entity\DoctorBusinessSite;
use App\Entity\Doctors;
use App\Entity\BusinessSites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DoctorBusinessSiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DoctorBusinessSite::class);
    }

    /**
     * Récupérer tous les liens d'un médecin
     */
    public function findByDoctor(Doctors $doctor): array
    {
        return $this->createQueryBuilder('dbs')
            ->andWhere('dbs.doctor = :doctor')
            ->setParameter('doctor', $doctor)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer tous les médecins d'un cabinet
     */
    public function findByBusinessSite(BusinessSites $businessSite): array
    {
        return $this->createQueryBuilder('dbs')
            ->andWhere('dbs.businessSite = :businessSite')
            ->setParameter('businessSite', $businessSite)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouver la relation médecin + cabinet
     */
    public function findOneByDoctorAndBusinessSite(Doctors $doctor, BusinessSites $businessSite): ?DoctorBusinessSite
    {
        return $this->createQueryBuilder('dbs')
            ->andWhere('dbs.doctor = :doctor')
            ->andWhere('dbs.businessSite = :businessSite')
            ->setParameter('doctor', $doctor)
            ->setParameter('businessSite', $businessSite)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupérer uniquement les owners d'un cabinet
     */
    public function findOwnersByBusinessSite(BusinessSites $businessSite): array
    {
        return $this->createQueryBuilder('dbs')
            ->andWhere('dbs.businessSite = :businessSite')
            ->andWhere('dbs.isOwner = :isOwner')
            ->setParameter('businessSite', $businessSite)
            ->setParameter('isOwner', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les collaborateurs (non owners)
     */
    public function findCollaboratorsByBusinessSite(BusinessSites $businessSite): array
    {
        return $this->createQueryBuilder('dbs')
            ->andWhere('dbs.businessSite = :businessSite')
            ->andWhere('dbs.isOwner = :isOwner')
            ->setParameter('businessSite', $businessSite)
            ->setParameter('isOwner', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un doctor est owner dans un cabinet
     */
    public function isOwner(Doctors $doctor, BusinessSites $businessSite): bool
    {
        return (bool) $this->createQueryBuilder('dbs')
            ->select('COUNT(dbs.id)')
            ->andWhere('dbs.doctor = :doctor')
            ->andWhere('dbs.businessSite = :businessSite')
            ->andWhere('dbs.isOwner = true')
            ->setParameter('doctor', $doctor)
            ->setParameter('businessSite', $businessSite)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Récupérer le doctorBusinessSite principal
     */
    public function findPrimaryByDoctor(Doctors $doctor): ?DoctorBusinessSite
    {
        return $this->createQueryBuilder('dbs')
            ->andWhere('dbs.doctor = :doctor')
            ->andWhere('dbs.isPrimary = true')
            ->setParameter('doctor', $doctor)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
