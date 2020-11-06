<?php

namespace App\Repository\Event;

use App\Entity\Event\FeeGreaterThanEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository des événements "honoraires supérieurs à".
 */
class FeeGreaterThanEventRepository extends ServiceEntityRepository
{
    /**
     * FeeGreaterThanEventRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FeeGreaterThanEvent::class);
    }
}
