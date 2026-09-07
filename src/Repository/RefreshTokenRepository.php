<?php

namespace App\Repository;

use App\Entity\RefreshToken;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

class RefreshTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RefreshToken::class);
    }

    public function findValidByToken(string $token): ?RefreshToken
    {
        $refreshToken = $this->findOneBy(['token' => $token]);

        if (!$refreshToken || $refreshToken->isRevoked() || $refreshToken->isExpired()) {
            return null;
        }

        return $refreshToken;
    }

    public function revokeAllByUser(Users $user): void
    {
        $this->createQueryBuilder('rt')
            ->update()
            ->set('rt.revoked', true)
            ->where('rt.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('rt')
            ->delete()
            ->where('rt.expiresAt < :now')
            ->setParameter('now', new DateTime())
            ->getQuery()
            ->execute();
    }
}
