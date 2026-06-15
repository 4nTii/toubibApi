<?php

namespace App\Repository;

use App\Entity\Doctors;
use App\Entity\PatientsHistory;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PatientsHistory>
 *
 * @method PatientsHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method PatientsHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method PatientsHistory[]    findAll()
 * @method PatientsHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PatientsHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientsHistory::class);
    }

    public function add(PatientsHistory $entry, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entry);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PatientsHistory $entry, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entry);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByUser(Users $user): array
    {
        return $this->findBy(['user' => $user], ['date' => 'DESC']);
    }

    public function findByUserPaginated(Users $user, int $page, int $limit): array
    {
        $offset = ($page - 1) * $limit;

        $data = $this->createQueryBuilder('h')
            ->andWhere('h.user = :user')
            ->setParameter('user', $user)
            ->orderBy('h.date', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        $total = (int) $this->createQueryBuilder('h')
            ->select('COUNT(h.id)')
            ->andWhere('h.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'data'       => $data,
            'total'      => $total,
            'totalPages' => (int) ceil($total / $limit),
        ];
    }

    public function findByDoctor(Doctors $doctor): array
    {
        return $this->findBy(['doctor' => $doctor], ['date' => 'DESC']);
    }

    public function findByUserAndDoctor(Users $user, Doctors $doctor): array
    {
        return $this->findBy(['user' => $user, 'doctor' => $doctor], ['date' => 'DESC']);
    }
}
