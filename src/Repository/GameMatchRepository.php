<?php

namespace App\Repository;

use App\Entity\GameMatch;
use App\Entity\Lobby;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameMatch>
 */
class GameMatchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameMatch::class);
    }

    public function findActiveMatchForUser(User $user): ?GameMatch
    {
        return $this->createQueryBuilder('m')
            ->join('m.lobbyA', 'la')
            ->join('la.players', 'lpa')
            ->join('m.lobbyB', 'lb')
            ->join('lb.players', 'lpb')
            ->where('(lpa.user = :user OR lpb.user = :user)')
            ->andWhere('m.stage IN (:activeStages)')
            ->setParameter('user', $user)
            ->setParameter('activeStages', [
                GameMatch::STAGE_WAITING,
                GameMatch::STAGE_VETO,
                GameMatch::STAGE_CONNECT,
                GameMatch::STAGE_LIVE
            ])
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveMatchForLobby(Lobby $lobby): ?GameMatch
    {
        return $this->createQueryBuilder('m')
            ->where('(m.lobbyA = :lobby OR m.lobbyB = :lobby)')
            ->andWhere('m.stage IN (:activeStages)')
            ->setParameter('lobby', $lobby)
            ->setParameter('activeStages', [
                GameMatch::STAGE_WAITING,
                GameMatch::STAGE_VETO,
                GameMatch::STAGE_CONNECT,
                GameMatch::STAGE_LIVE
            ])
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingChallengeForLobby(Lobby $lobby): ?GameMatch
    {
        return $this->createQueryBuilder('m')
            ->where('m.lobbyB = :lobby')
            ->andWhere('m.stage = :stage')
            ->andWhere('m.lobbyBAccepted IS NULL')
            ->setParameter('lobby', $lobby)
            ->setParameter('stage', GameMatch::STAGE_WAITING)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
