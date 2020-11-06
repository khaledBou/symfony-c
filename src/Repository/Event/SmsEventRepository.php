<?php

namespace App\Repository\Event;

use App\Entity\Event\SmsEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des SMS.
 */
class SmsEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SmsEvent::class);
    }

    /**
     * Récupère les SMS à envoyer.
     *
     * @return SmsEvent[]
     */
    public function findToSend(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sent = :sent')
            ->andWhere('e.date <= :now')
            ->setParameter('sent', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult()
        ;
    }
}
