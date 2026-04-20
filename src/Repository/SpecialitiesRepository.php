<?php

namespace App\Repository;

use App\Entity\Specialities;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Specialities>
 *
 * @method Specialities|null find($id, $lockMode = null, $lockVersion = null)
 * @method Specialities|null findOneBy(array $criteria, array $orderBy = null)
 * @method Specialities[]    findAll()
 * @method Specialities[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpecialitiesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specialities::class);
    }

    public function add(Specialities $speciality, bool $flush = true): void
    {
        $this->getEntityManager()->persist($speciality);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Specialities $speciality, bool $flush = true): void
    {
        $this->getEntityManager()->remove($speciality);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère une spécialité par son nom exact
     */
    public function findByName(string $name): ?Specialities
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Recherche des spécialités contenant une chaîne de caractères
     */
    public function searchByName(string $name, int $max = 5): array
    {
        $term = '%' . strtolower($name) . '%';

        return $this->createQueryBuilder('s')
            ->where('LOWER(s.name) LIKE :term')
            ->setParameter('term', $term)
            ->orderBy('s.name', 'ASC')
            ->setMaxResults($max)
            ->getQuery()
            ->getResult();
    }
}
