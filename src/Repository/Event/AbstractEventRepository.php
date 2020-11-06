<?php

namespace App\Repository\Event;

use App\Entity\Event\AbstractEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des événements.
 */
class AbstractEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractEvent::class);
    }

    /**
     * Récupère le dernier événement (le plus récent) pouvant être considéré comme une prise de contact
     * entre le négociateur et les coachs.
     *
     * @param Mandatary $mandatary
     *
     * @return AppointmentEvent|CallEvent|null
     */
    public function findLatestContact(Mandatary $mandatary): ?AbstractEvent
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e INSTANCE OF :appointment_type OR e INSTANCE OF :call_type')
            ->andWhere('e.mandatary = :mandatary')
            ->andWhere('e.date <= :now')
            ->setParameter('appointment_type', $this->getEntityManager()->getClassMetadata('App\Entity\Event\AppointmentEvent'))
            ->setParameter('call_type', $this->getEntityManager()->getClassMetadata('App\Entity\Event\CallEvent'))
            ->setParameter('mandatary', $mandatary)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * Compte le nombre d'événements pouvant être considérés comme des prise de contacts
     * entre le négociateur et les coachs.
     *
     * @param Mandatary $mandatary
     * @param int|null  $latestDays Ne compter que parmi les $latestDays derniers jours
     *
     * @return int
     */
    public function countContacts(Mandatary $mandatary, ?int $latestDays = null): int
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e)')
            ->andWhere('e INSTANCE OF :appointment_type OR e INSTANCE OF :call_type')
            ->andWhere('e.mandatary = :mandatary')
            ->setParameter('appointment_type', $this->getEntityManager()->getClassMetadata('App\Entity\Event\AppointmentEvent'))
            ->setParameter('call_type', $this->getEntityManager()->getClassMetadata('App\Entity\Event\CallEvent'))
            ->setParameter('mandatary', $mandatary)
        ;

        // Uniquement les événements survenus dans les $latestDays derniers jours
        if (null !== $latestDays) {
            $date = (new \DateTime())->modify(sprintf('-%d days midnight', $latestDays));
            $qb
                ->andWhere('e.date >= :date')
                ->setParameter('date', $date)
            ;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
