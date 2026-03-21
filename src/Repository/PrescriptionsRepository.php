<?php

namespace App\Repository;

use App\Entity\Prescriptions;
use App\Entity\Doctors;
use App\Entity\Patients;
use App\Entity\Appointments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prescriptions>
 *
 * @method Prescriptions|null find($id, $lockMode = null, $lockVersion = null)
 * @method Prescriptions|null findOneBy(array $criteria, array $orderBy = null)
 * @method Prescriptions[]    findAll()
 * @method Prescriptions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrescriptionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prescriptions::class);
    }

    public function add(Prescriptions $prescription, bool $flush = true): void
    {
        $this->getEntityManager()->persist($prescription);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Prescriptions $prescription, bool $flush = true): void
    {
        $this->getEntityManager()->remove($prescription);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère toutes les prescriptions d'un patient
     */
    public function findByPatient(Patients $patient): array
    {
        return $this->findBy(['patient' => $patient], ['createdAt' => 'DESC']);
    }

    /**
     * Récupère toutes les prescriptions d'un médecin
     */
    public function findByDoctor(Doctors $doctor): array
    {
        return $this->findBy(['doctor' => $doctor], ['createdAt' => 'DESC']);
    }

    /**
     * Récupère toutes les prescriptions liées à un rendez-vous spécifique
     */
    public function findByAppointment(Appointments $appointment): array
    {
        return $this->findBy(['appointment' => $appointment], ['createdAt' => 'DESC']);
    }

    /**
     * Récupère la dernière prescription d'un patient
     */
    public function findLastByPatient(Patients $patient): ?Prescriptions
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.patient = :patient')
            ->setParameter('patient', $patient)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
