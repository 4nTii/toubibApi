<?php

namespace App\Repository;

use App\Entity\UsersPasswordResetToken;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UsersPasswordResetTokenRepository extends ServiceEntityRepository
{
    private const TOKEN_LIFETIME_MINUTES = 15;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsersPasswordResetToken::class);
    }

    public function createToken(UsersPasswordResetToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeToken(UsersPasswordResetToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByToken(string $token): ?UsersPasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.token = :token')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestForUser(Users $user): ?UsersPasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Supprime tous les tokens d'un utilisateur.
     * À appeler avant d'en créer un nouveau.
     */
    public function deleteAllForUser(Users $user, bool $flush = false): void
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->andWhere('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime tous les tokens créés avant $date (nettoyage cron).
     */
    public function deleteExpiredTokens(\DateTimeImmutable $date): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * Vérifie si un token est encore valide (< TOKEN_LIFETIME_MINUTES minutes).
     */
    public function isTokenValid(UsersPasswordResetToken $token): bool
    {
        $expiry = $token->getCreatedAt()->modify('+' . self::TOKEN_LIFETIME_MINUTES . ' minutes');
        return new \DateTimeImmutable() < $expiry;
    }

    /**
     * Retourne le token valide d'un utilisateur s'il existe.
     */
    public function findValidTokenForUser(Users $user): ?UsersPasswordResetToken
    {
        $token = $this->findLatestForUser($user);
        if ($token && $this->isTokenValid($token)) {
            return $token;
        }
        return null;
    }
}
