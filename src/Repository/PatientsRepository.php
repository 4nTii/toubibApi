<?php

namespace App\Repository;

use App\Entity\Patients;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Patients>
 *
 * @method Patients|null find($id, $lockMode = null, $lockVersion = null)
 * @method Patients|null findOneBy(array $criteria, array $orderBy = null)
 * @method Patients[]    findAll()
 * @method Patients[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatientsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Patients::class);
    }

    public function add(Patients $patient, bool $flush = true): void
    {
        $this->getEntityManager()->persist($patient);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Patients $patient, bool $flush = true): void
    {
        $this->getEntityManager()->remove($patient);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère un patient via son utilisateur associé
     */
    public function findByUser(Users $user): ?Patients
    {
        return $this->findOneBy(['user' => $user]);
    }

    /**
     * Recherche des patients par nom ou prénom
     */
    public function searchByName(string $name): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.user', 'u')
            ->andWhere('u.firstName LIKE :name OR u.lastName LIKE :name')
            ->setParameter('name', '%' . $name . '%')
            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les patients ayant un rendez-vous futur
     * (méthode à adapter selon la relation Appointments si nécessaire)
     */
    public function findWithUpcomingAppointments(): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.appointments', 'a')
            ->andWhere('a.startTime > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('a.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
