<?php

namespace App\Repository\User;

use App\Entity\User\AbstractUser;
use App\Entity\User\ImportableUserInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository des utilisateurs.
 */
class AbstractUserRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbstractUser::class);
    }

    /**
     * Récupère les utilisateurs de type $userClass importés sur le réseau $network.
     *
     * @param string $userClass Fully-qualified name de la classe utilisateur
     * @param string $network   Réseau
     *
     * @return ImportableUserInterface[]
     */
    public function findImportedUsers($userClass, $network): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u INSTANCE OF :type')
            ->andWhere('u.imported = :imported')
            ->andWhere('u.network = :network')
            ->setParameter('type', $this->getEntityManager()->getClassMetadata($userClass))
            ->setParameter('imported', true)
            ->setParameter('network', $network)
            ->getQuery()
            ->getResult()
        ;
    }
}
