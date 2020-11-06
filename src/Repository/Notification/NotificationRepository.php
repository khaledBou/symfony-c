<?php

namespace App\Repository\Notification;

use App\Entity\Notification\Notification;
use App\Entity\User\ImportableUserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des notifications.
 */
class NotificationRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Compte les notifications de l'utilisateur.
     *
     * @param ImportableUserInterface $user
     * @param bool|null               $read
     *
     * @return int
     */
    public function countNotifications(ImportableUserInterface $user, ?bool $read = null): int
    {
        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n)')
            ->andWhere('n.user = :user')
            ->setParameter('user', $user)
        ;

        if (null !== $read) {
            $qb
                ->andWhere('n.read = :read')
                ->setParameter('read', $read)
            ;
        }

        return $qb
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * Récupère une notification de moins de 30 jours et qui correspond aux critères indiqués.
     *
     * @param ImportableUserInterface      $user
     * @param string                       $message
     * @param ImportableUserInterface|null $initiator
     *
     * @return Notification|null
     */
    public function findRecent(ImportableUserInterface $user, string $message, ?ImportableUserInterface $initiator = null): ?Notification
    {
        $qb = $this->createQueryBuilder('n')
            ->andWhere('n.user = :user')
            ->andWhere('n.message = :message')
            ->andWhere('n.date >= :date')
            ->setParameter('user', $user)
            ->setParameter('message', $message)
            ->setParameter('date', new \DateTime('-30 days'))
            ->setMaxResults(1)
            ->orderBy('n.date', 'desc')
        ;

        if (null !== $initiator) {
            $qb
                ->andWhere('n.initiator = :initiator')
                ->setParameter('initiator', $initiator)
            ;
        }

        return $qb
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
