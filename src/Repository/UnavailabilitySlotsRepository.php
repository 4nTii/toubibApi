<?php

namespace App\Repository;

use App\Entity\UnavailabilitySlots;
use App\Entity\Doctors;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UnavailabilitySlots>
 *
 * @method UnavailabilitySlots|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnavailabilitySlots|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnavailabilitySlots[]    findAll()
 * @method UnavailabilitySlots[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnavailabilitySlotsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnavailabilitySlots::class);
    }

    public function add(UnavailabilitySlots $slot, bool $flush = true): void
    {
        $this->getEntityManager()->persist($slot);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UnavailabilitySlots $slot, bool $flush = true): void
    {
        $this->getEntityManager()->remove($slot);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère tous les créneaux d'indisponibilité pour un médecin
     */
    public function findByDoctor(Doctors $doctor): array
    {
        return $this->findBy(['doctor' => $doctor], ['startTime' => 'ASC']);
    }

    /**
     * Récupère les créneaux d'indisponibilité d'un médecin à partir d'une date donnée
     */
    public function findFutureByDoctor(Doctors $doctor, \DateTimeInterface $fromDate): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.doctor = :doctor')
            ->andWhere('u.endTime >= :fromDate')
            ->setParameter('doctor', $doctor)
            ->setParameter('fromDate', $fromDate)
            ->orderBy('u.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un médecin est indisponible à un créneau donné
     */
    public function isDoctorUnavailable(Doctors $doctor, \DateTimeInterface $startTime, \DateTimeInterface $endTime): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.doctor = :doctor')
            ->andWhere('u.startTime < :endTime')
            ->andWhere('u.endTime > :startTime')
            ->setParameter('doctor', $doctor)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        $count = $qb->getQuery()->getSingleScalarResult();

        return $count > 0;
    }
}
