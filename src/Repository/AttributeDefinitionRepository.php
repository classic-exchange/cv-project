<?php

namespace App\Repository;

use App\Entity\AttributeDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AttributeDefinition>
 */
class AttributeDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AttributeDefinition::class);
    }

    /**
     * @return AttributeDefinition[]
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        return $this->createQueryBuilder('ad')
            ->andWhere('ad.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult()
        ;
    }
}
