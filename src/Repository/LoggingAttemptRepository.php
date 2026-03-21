<?php

namespace App\Repository;

use App\Entity\LoggingAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LoggingAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoggingAttempt::class);
    }

    public function add(LoggingAttempt $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(LoggingAttempt $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Compter les tentatives récentes pour un email donné
     * Par exemple dans les 5 dernières minutes
     * @param $email
     * @param $minutes 5 by default
     * 
     * @return int 
     */
    public function countRecentAttemptsByEmail(string $email, int $minutes = 5): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.email = :email')
            ->andWhere('l.attemptedAt >= :since')
            ->setParameter('email', $email)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compter les tentatives récentes pour une IP donnée
     * Par défaut dans les 30 dernières minutes
     * @param string $email
     * @param int $minutes 5 by default
     * 
     * @return int 
     */
    public function countRecentAttemptsByIpAddress(string $ipAddress, int $minutes = 30): int
    {
        $since = new \DateTimeImmutable("-{$minutes} minutes");

        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.ipAddress = :ip')
            ->andWhere('l.attemptedAt >= :since')
            ->setParameter('ip', $ipAddress)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Supprimer toutes les tentatives pour un email
     * (à appeler après login réussi)
     * @param string $email
     * 
     * @return void 
     */
    public function deleteByEmail(string $email): void
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->andWhere('l.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->execute();
    }

    /**
     * Supprimer toutes les tentatives pour un IP
     * (à appeler après login réussi)
     * @param string $ipAddress
     * 
     * @return void 
     */
    public function deleteByIpAddress(string $ipAddress): void
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->andWhere('l.ipAddress  = :ip_address')
            ->setParameter('ip_address', $ipAddress)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupérer les dernières tentatives d’un email
     * @param string $email
     * @param int $limit
     * 
     * @return void 
     */
    public function findRecentAttemptsByEmail(string $email, int $limit = 10): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.email = :email')
            ->setParameter('email', $email)
            ->orderBy('l.attemptedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
