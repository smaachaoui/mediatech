<?php

namespace App\Repository;

use App\Entity\Collection;
use App\Entity\Rating;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RatingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rating::class);
    }

    /**
     * Je retourne la moyenne et le nombre de notes pour une collection.
     *
     * @return array{avg: ?float, count: int}
     */
    public function getStatsForCollection(Collection $collection): array
    {
        $result = $this->createQueryBuilder('r')
            ->select('AVG(r.value) AS avgRating, COUNT(r.id) AS totalRatings')
            ->andWhere('r.collection = :collection')
            ->setParameter('collection', $collection)
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'avg' => $result['avgRating'] !== null ? (float) $result['avgRating'] : null,
            'count' => (int) ($result['totalRatings'] ?? 0),
        ];
    }
}
