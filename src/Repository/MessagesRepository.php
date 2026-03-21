<?php

namespace App\Repository;

use App\Entity\Messages;
use App\Entity\Users;
use App\Entity\Appointments;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Messages>
 *
 * @method Messages|null find($id, $lockMode = null, $lockVersion = null)
 * @method Messages|null findOneBy(array $criteria, array $orderBy = null)
 * @method Messages[]    findAll()
 * @method Messages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessagesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Messages::class);
    }

    public function add(Messages $message, bool $flush = true): void
    {
        $this->getEntityManager()->persist($message);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Messages $message, bool $flush = true): void
    {
        $this->getEntityManager()->remove($message);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Récupère tous les messages envoyés ou reçus par un utilisateur
     */
    public function findByUser(Users $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.sender = :user OR m.receiver = :user')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les messages entre deux utilisateurs
     */
    public function findConversation(Users $user1, Users $user2): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('(m.sender = :user1 AND m.receiver = :user2) OR (m.sender = :user2 AND m.receiver = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les messages liés à un rendez-vous spécifique
     */
    public function findByAppointment(Appointments $appointment): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.appointment = :appointment')
            ->setParameter('appointment', $appointment)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère tous les messages non lus pour un utilisateur
     */
    public function findUnreadMessages(Users $user): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.receiver = :user')
            ->andWhere('m.isRead = false')
            ->setParameter('user', $user)
            ->orderBy('m.sentAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
