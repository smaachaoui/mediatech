<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\CollectionMovie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CollectionMovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionMovie::class);
    }

    public function findLinksByCollection(Collection $collection): array
    {
        return $this->createQueryBuilder('cm')
            ->addSelect('m')
            ->innerJoin('cm.movie', 'm')
            ->andWhere('cm.collection = :collection')
            ->setParameter('collection', $collection)
            ->orderBy('cm.addedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
