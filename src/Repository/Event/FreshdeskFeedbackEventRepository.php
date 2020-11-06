<?php

namespace App\Repository\Event;

use App\Entity\Event\FreshdeskFeedbackEvent;
use App\Entity\User\Mandatary;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des évaluations Freshdesk.
 */
class FreshdeskFeedbackEventRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FreshdeskFeedbackEvent::class);
    }

    /**
     * Récupère l'évaluation Freshdesk la plus récente,
     * tous négociateurs confondus ou pour un négociateur en particulier.
     *
     * @param Mandatary|null $mandatary
     *
     * @return FreshdeskFeedbackEvent|null
     */
    public function findLatest(?Mandatary $mandatary = null): ?FreshdeskFeedbackEvent
    {
        $qb = $this->createQueryBuilder('e');

        if (null !== $mandatary) {
            $qb
                ->andWhere('e.mandatary = :mandatary')
                ->setParameter('mandatary', $mandatary)
            ;
        }

        return $qb
            ->orderBy('e.date', 'desc')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
