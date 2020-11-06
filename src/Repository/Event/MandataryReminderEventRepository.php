<?php

namespace App\Repository\Event;

use App\Entity\Event\MandataryReminderEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des relances.
 */
class MandataryReminderEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MandataryReminderEvent::class);
    }

    /**
     * Récupère les relances à envoyer.
     *
     * @param int $way
     *
     * @return MandataryReminderEvent[]
     */
    public function findToSend(int $way): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sent = :sent')
            ->andWhere('e.date <= :now')
            ->andWhere('e.way = :way')
            ->setParameter('sent', false)
            ->setParameter('now', new \DateTime())
            ->setParameter('way', $way)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Récupère le dernier rappel envoyé (le plus récent),
     * pour un négociateur donné.
     *
     * @param Mandatary $mandatary
     *
     * @return MandataryReminderEvent|null
     */
    public function findLatest(Mandatary $mandatary): ?MandataryReminderEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sent = :sent')
            ->andWhere('e.mandatary = :mandatary')
            ->andWhere('e.date <= :now')
            ->setParameter('sent', true)
            ->setParameter('mandatary', $mandatary)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
