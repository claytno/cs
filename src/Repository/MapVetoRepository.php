<?php

namespace App\Repository;

use App\Entity\MapVeto;
use App\Entity\GameMatch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MapVeto>
 */
class MapVetoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MapVeto::class);
    }

    public function findByMatchOrderedByRound(GameMatch $match): array
    {
        return $this->createQueryBuilder('mv')
            ->where('mv.match = :match')
            ->setParameter('match', $match)
            ->orderBy('mv.round', 'ASC')
            ->addOrderBy('mv.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextRoundForMatch(GameMatch $match): int
    {
        $lastRound = $this->createQueryBuilder('mv')
            ->select('MAX(mv.round)')
            ->where('mv.match = :match')
            ->setParameter('match', $match)
            ->getQuery()
            ->getSingleScalarResult();

        return $lastRound ? (int)$lastRound + 1 : 1;
    }

    public function getAvailableMapsForMatch(GameMatch $match): array
    {
        $allMaps = [
            'de_mirage',
            'de_inferno',
            'de_cache',
            'de_overpass',
            'de_train',
            'de_nuke',
            'de_dust2'
        ];

        $bannedMaps = $this->createQueryBuilder('mv')
            ->select('mv.mapName')
            ->where('mv.match = :match')
            ->andWhere('mv.type = :banType')
            ->setParameter('match', $match)
            ->setParameter('banType', MapVeto::TYPE_BAN)
            ->getQuery()
            ->getSingleColumnResult();

        return array_diff($allMaps, $bannedMaps);
    }
}
