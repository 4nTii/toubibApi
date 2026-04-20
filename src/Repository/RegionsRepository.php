<?php

namespace App\Repository;

use App\Entity\Regions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Regions>
 *
 * @method Regions|null find($id, $lockMode = null, $lockVersion = null)
 * @method Regions|null findOneBy(array $criteria, array $orderBy = null)
 * @method Regions[]    findAll()
 * @method Regions[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Regions::class);
    }

    public function add(Regions $region, bool $flush = true): void
    {
        $this->getEntityManager()->persist($region);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Regions $region, bool $flush = true): void
    {
        $this->getEntityManager()->remove($region);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère toutes les régions d'un pays donné
     */
    public function findByCountry(string $country): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.country = :country')
            ->setParameter('country', $country)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère une région par son nom exact
     */
    public function findByName(string $name): ?Regions
    {
        return $this->findOneBy(['name' => $name]);
    }

    public function searchByName(string $term, int $max = 5): array
    {
        $term = '%' . strtolower($term) . '%';

        return $this->createQueryBuilder('r')
            ->where('LOWER(r.name) LIKE :term')
            ->setParameter('term', $term)
            ->orderBy('r.name', 'ASC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
    }
}
