<?php

namespace App\Repository;

use App\Entity\Doctors;
use App\Entity\Specialities;
use App\Entity\BusinessSites;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeInterface;

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
    public function searchByName(string $name, int $max = 5, ?bool $onlyActive = true): array
    {
        $name = '%' . strtolower($name) . '%';

        // recupérer les IDs avoid Doctrine trap
        // JOIN + LIMIT ne limite pas les entities… mais les lignes SQL, alors 2 QueryBuilders 
        $qb = $this->createQueryBuilder('d')
            ->select('d.id')
            ->join('d.user', 'u')
            ->where(
                'LOWER(u.firstName) LIKE :name
                 OR LOWER(u.lastName) LIKE :name
                 OR LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE :name
                 OR LOWER(CONCAT(u.lastName, \' \', u.firstName)) LIKE :name'
            )
            ->setParameter('name', $name);

        if ($onlyActive !== null) {
            $qb->andWhere('d.isActive = :active')
                ->setParameter('active', $onlyActive);
        }

        $qb->orderBy('u.lastName', 'ASC')
            ->setMaxResults($max);

        $ids = array_column($qb->getQuery()->getScalarResult(), 'id');

        if (empty($ids)) {
            return [];
        }

        // hydrate complet avoid Doctrine trap
        return $this->createQueryBuilder('d')
            ->where('d.id IN (:ids)')
            ->setParameter('ids', $ids)

            ->join('d.user', 'u')->addSelect('u')
            ->leftJoin('d.speciality', 's')->addSelect('s')
            ->leftJoin('d.doctorBusinessSites', 'dbs')->addSelect('dbs')
            ->leftJoin('dbs.businessSite', 'bs')->addSelect('bs')

            ->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les médecins actifs disponibles à partir d'une date donnée
     */
    public function findAvailableDoctors(DateTimeInterface $fromDate): array
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
     * Recherche paginée de médecins par nom/spécialité/établissement + filtre localisation.
     *
     * searchValue : correspond au nom du médecin, à la spécialité ou au nom du cabinet.
     * location    : correspond à la ville (BusinessSites.ville) ou à la région (Regions.name).
     *
     * Utilise le pattern en deux étapes (IDs d'abord, hydratation ensuite) pour
     * éviter les problèmes de pagination Doctrine avec les fetch-joins sur collections.
     *
     * @return array{doctors: Doctors[], total: int, page: int, limit: int, pages: int}
     */
    public function searchWithFilters(
        ?string $searchValue,
        ?string $location,
        int $page,
        int $limit
    ): array {
        $qb = $this->createQueryBuilder('d')
            ->join('d.user', 'u')
            ->leftJoin('d.speciality', 's')
            ->leftJoin('d.doctorBusinessSites', 'dbs')
            ->leftJoin('dbs.businessSite', 'bs')
            ->leftJoin('bs.region', 'r')
            ->andWhere('d.isActive = true');

        if ($searchValue !== null && $searchValue !== '') {
            $sv = '%' . mb_strtolower($searchValue) . '%';
            $qb->andWhere(
                'LOWER(u.firstName) LIKE :sv
                 OR LOWER(u.lastName) LIKE :sv
                 OR LOWER(CONCAT(u.firstName, \' \', u.lastName)) LIKE :sv
                 OR LOWER(s.name) LIKE :sv
                 OR LOWER(bs.name) LIKE :sv'
            )->setParameter('sv', $sv);
        }

        if ($location !== null && $location !== '') {
            $loc = '%' . mb_strtolower($location) . '%';
            $qb->andWhere(
                'LOWER(bs.ville) LIKE :loc OR LOWER(r.name) LIKE :loc'
            )->setParameter('loc', $loc);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT d.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // GROUP BY d.id + colonnes de tri dans le SELECT pour satisfaire MySQL ONLY_FULL_GROUP_BY
        $ids = array_column(
            (clone $qb)
                ->select('d.id, u.lastName, u.firstName')
                ->groupBy('d.id, u.lastName, u.firstName')
                ->orderBy('u.lastName', 'ASC')
                ->addOrderBy('u.firstName', 'ASC')
                ->setFirstResult(($page - 1) * $limit)
                ->setMaxResults($limit)
                ->getQuery()
                ->getScalarResult(),
            'id'
        );

        $doctors = [];
        if (!empty($ids)) {
            $doctors = $this->createQueryBuilder('d')
                ->where('d.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->join('d.user', 'u')->addSelect('u')
                ->leftJoin('d.speciality', 's')->addSelect('s')
                ->leftJoin('d.doctorBusinessSites', 'dbs')->addSelect('dbs')
                ->leftJoin('dbs.businessSite', 'bs')->addSelect('bs')
                ->leftJoin('bs.region', 'r')->addSelect('r')
                ->orderBy('u.lastName', 'ASC')
                ->addOrderBy('u.firstName', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return [
            'doctors' => $doctors,
            'total'   => $total,
            'page'    => $page,
            'limit'   => $limit,
            'pages'   => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ];
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
