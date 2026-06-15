<?php

namespace App\Repository;

use App\Entity\Appointments;
use App\Entity\Doctors;
use App\Entity\Patients;
use App\Entity\BusinessSites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointments>
 *
 * @method Appointments|null find($id, $lockMode = null, $lockVersion = null)
 * @method Appointments|null findOneBy(array $criteria, array $orderBy = null)
 * @method Appointments[]    findAll()
 * @method Appointments[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AppointmentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointments::class);
    }

    public function add(Appointments $appointment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($appointment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Appointments $appointment, bool $flush = true): void
    {
        $this->getEntityManager()->remove($appointment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère tous les rendez-vous d'un patient
     */
    public function findByPatient(Patients $patient): array
    {
        return $this->findBy(['patient' => $patient], ['startTime' => 'ASC']);
    }

    /**
     * Récupère tous les rendez-vous d'un médecin
     */
    public function findByDoctor(Doctors $doctor): array
    {
        return $this->findBy(['doctor' => $doctor], ['startTime' => 'ASC']);
    }

    /**
     * Récupère tous les rendez-vous d'un cabinet (business site)
     */
    public function findByBusinessSite(BusinessSites $businessSite): array
    {
        return $this->findBy(['businessSite' => $businessSite], ['startTime' => 'ASC']);
    }

    /**
     * Récupère les rendez-vous futurs pour un patient ou un médecin
     */
    public function findFutureAppointmentsByPatient(Patients $patient): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.startTime > :now')
            ->setParameter('patient', $patient)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFutureAppointmentsByDoctor(Doctors $doctor): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.startTime > :now')
            ->setParameter('doctor', $doctor)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les rendez-vous passés pour un patient ou un médecin
     */
    public function findPastAppointmentsByPatient(Patients $patient): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.patient = :patient')
            ->andWhere('a.endTime < :now')
            ->setParameter('patient', $patient)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPastAppointmentsByDoctor(Doctors $doctor): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.endTime < :now')
            ->setParameter('doctor', $doctor)
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les RDV d'un médecin pour un cabinet donné (tous statuts, y compris annulé).
     */
    public function findAllByDoctorAndSite(Doctors $doctor, BusinessSites $businessSite): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.businessSite = :site')
            ->setParameter('doctor', $doctor)
            ->setParameter('site', $businessSite)
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Tous les RDV d'un médecin pour un cabinet donné (sans les annulés).
     */
    public function findByDoctorAndSite(Doctors $doctor, BusinessSites $businessSite): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.businessSite = :site')
            ->andWhere('a.status != :canceled')
            ->setParameter('doctor', $doctor)
            ->setParameter('site', $businessSite)
            ->setParameter('canceled', 'canceled')
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les rendez-vous d'un médecin pour un jour donné et un cabinet
     */
    public function getScheduleByDate(
        Doctors $doctor,
        BusinessSites $businessSite,
        \DateTimeInterface $date,
        bool $excludeLunch = true
    ): array {

        $date = \DateTimeImmutable::createFromInterface($date);

        $dayStart = $date->setTime(0, 0, 0);
        $dayEnd   = $date->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.doctor = :doctor')
            ->andWhere('a.businessSite = :site')
            ->andWhere('a.startTime BETWEEN :start AND :end')
            ->setParameter('doctor', $doctor)
            ->setParameter('site', $businessSite)
            ->setParameter('start', $dayStart)
            ->setParameter('end', $dayEnd);

        // 🔥 Optionnel : exclure la pause déjeuner
        if ($excludeLunch) {
            $lunchStart = $date->setTime(12, 0, 0);
            $lunchEnd   = $date->setTime(14, 0, 0);

            $qb->andWhere(
                '(a.endTime <= :lunchStart OR a.startTime >= :lunchEnd)'
            )
                ->setParameter('lunchStart', $lunchStart)
                ->setParameter('lunchEnd', $lunchEnd);
        }

        return $qb
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
