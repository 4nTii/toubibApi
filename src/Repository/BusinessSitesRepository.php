<?php

namespace App\Repository;

use App\Entity\BusinessSites;
use App\Entity\Doctors;
use App\Entity\Regions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BusinessSites>
 *
 * @method BusinessSites|null find($id, $lockMode = null, $lockVersion = null)
 * @method BusinessSites|null findOneBy(array $criteria, array $orderBy = null)
 * @method BusinessSites[]    findAll()
 * @method BusinessSites[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BusinessSitesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessSites::class);
    }

    public function add(BusinessSites $businessSite, bool $flush = true): void
    {
        $this->getEntityManager()->persist($businessSite);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BusinessSites $businessSite, bool $flush = true): void
    {
        $this->getEntityManager()->remove($businessSite);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère tous les business sites d'une région donnée
     */
    public function findByRegion(Regions $region): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.region = :region')
            ->setParameter('region', $region)
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un site par son nom exact
     */
    public function searchByName(string $name, int $max = 5): array
    {
        $name = '%' . strtolower($name) . '%';

        return $this->createQueryBuilder('bs')
            ->where('LOWER(bs.name) LIKE :name')
            ->orWhere('LOWER(bs.ville) LIKE :name')
            ->orWhere('LOWER(bs.address) LIKE :name')
            ->setParameter('name', $name)
            ->orderBy('bs.name', 'ASC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les sites avec le plus de médecins (optionnel)
     */
    public function findSitesWithDoctorsCount(int $limit = 10): array
    {
        return $this->createQueryBuilder('b')
            ->select('b, COUNT(d.id) AS doctorsCount')
            ->leftJoin('b.doctors', 'd')
            ->groupBy('b.id')
            ->orderBy('doctorsCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère le businessSite principal
     */
    public function getPrimaryBusinessSite(Doctors $doctor): ?BusinessSites
    {
        return $this->getEntityManager()
            ->getRepository(BusinessSites::class)
            ->createQueryBuilder('bs')
            ->innerJoin('bs.doctorBusinessSites', 'dbs')
            ->andWhere('dbs.doctor = :doctor')
            ->andWhere('dbs.isPrimary = true')
            ->setParameter('doctor', $doctor)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
