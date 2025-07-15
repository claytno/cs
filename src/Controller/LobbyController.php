<?php

namespace App\Controller;

use App\Entity\Lobby;
use App\Entity\LobbyPlayer;
use App\Entity\GameMatch;
use App\Repository\LobbyRepository;
use App\Repository\LobbyPlayerRepository;
use App\Repository\GameMatchRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/lobby')]
#[IsGranted('ROLE_USER')]
class LobbyController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LobbyRepository $lobbyRepository,
        private LobbyPlayerRepository $lobbyPlayerRepository,
        private GameMatchRepository $gameMatchRepository
    ) {}

    #[Route('/', name: 'lobby_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        
        // Verificar se o usuário tem Steam conectado
        if (!$user->getSteamId()) {
            $this->addFlash('warning', 'Você precisa conectar sua conta Steam para usar o sistema de lobbies.');
            return $this->redirectToRoute('steam_auth');
        }
        
        // Verificar se o usuário tem uma partida ativa e redirecionar automaticamente
        $activeMatch = $this->gameMatchRepository->findActiveMatchForUser($user);
        if ($activeMatch) {
            switch ($activeMatch->getStage()) {
                case GameMatch::STAGE_VETO:
                    return $this->redirectToRoute('match_veto');
                case GameMatch::STAGE_CONNECT:
                    return $this->redirectToRoute('match_connect');
                // Para STAGE_WAITING, continuar mostrando a lobby para aceitar/recusar
            }
        }
        
        // Buscar a lobby do usuário atual
        $userLobbyPlayer = $this->lobbyPlayerRepository->findOneBy(['user' => $user]);
        $userLobby = $userLobbyPlayer ? $userLobbyPlayer->getLobby() : null;
        
        // Buscar desafio pendente se o usuário é líder
        $pendingChallenge = null;
        if ($userLobby && $userLobbyPlayer->isLeader()) {
            $pendingChallenge = $this->gameMatchRepository->findPendingChallengeForLobby($userLobby);
        }
        
        // Buscar lobbies baseado no estado do usuário
        if ($userLobby && $userLobby->isFull()) {
            // Se usuário está em lobby cheia, mostrar lobbies para desafiar (outras lobbies cheias)
            $availableLobbies = $this->lobbyRepository->createQueryBuilder('l')
                ->where('l.id != :userLobbyId')
                ->andWhere('SIZE(l.players) = 5')
                ->andWhere('l.matched = false')
                ->setParameter('userLobbyId', $userLobby->getId())
                ->getQuery()
                ->getResult();
        } else {
            // Se usuário não está em lobby ou lobby não está cheia, mostrar todas as lobbies
            $availableLobbies = $this->lobbyRepository->createQueryBuilder('l')
                ->andWhere('l.matched = false')
                ->orderBy('l.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
                
            // Remover a lobby do próprio usuário se existir
            if ($userLobby) {
                $availableLobbies = array_filter($availableLobbies, function($lobby) use ($userLobby) {
                    return $lobby->getId() !== $userLobby->getId();
                });
            }
        }

        return $this->render('lobby/index.html.twig', [
            'user' => $user,
            'userLobby' => $userLobby,
            'userLobbyPlayer' => $userLobbyPlayer,
            'availableLobbies' => $availableLobbies,
            'isInFullLobby' => $userLobby && $userLobby->isFull(),
            'pendingChallenge' => $pendingChallenge,
            'activeMatch' => $activeMatch,
        ]);
    }

    #[Route('/create', name: 'lobby_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        
        // Verificar se o usuário já está em uma lobby
        $existingPlayer = $this->lobbyPlayerRepository->findOneBy(['user' => $user]);
        if ($existingPlayer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Você já está em uma lobby!'
            ], 400);
        }

        $data = json_decode($request->getContent(), true);
        $lobbyName = $data['name'] ?? '';

        if (empty($lobbyName)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Nome da lobby é obrigatório!'
            ], 400);
        }

        // Criar lobby
        $lobby = new Lobby();
        $lobby->setName($lobbyName);
        $lobby->setRelation($user);
        $lobby->setMatched(false);

        // Criar jogador líder
        $lobbyPlayer = new LobbyPlayer();
        $lobbyPlayer->setLobby($lobby);
        $lobbyPlayer->setUser($user);
        $lobbyPlayer->setIsLeader(true);

        $this->entityManager->persist($lobby);
        $this->entityManager->persist($lobbyPlayer);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'lobby' => [
                'id' => $lobby->getId(),
                'name' => $lobby->getName(),
                'playerCount' => $lobby->getPlayerCount(),
                'isFull' => $lobby->isFull()
            ]
        ]);
    }

    #[Route('/join/{id}', name: 'lobby_join', methods: ['POST'])]
    public function join(Lobby $lobby): JsonResponse
    {
        $user = $this->getUser();
        
        // Verificar se o usuário já está em uma lobby
        $existingPlayer = $this->lobbyPlayerRepository->findOneBy(['user' => $user]);
        if ($existingPlayer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Você já está em uma lobby!'
            ], 400);
        }

        // Verificar se a lobby não está cheia
        if ($lobby->isFull()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Esta lobby está cheia!'
            ], 400);
        }

        // Adicionar jogador à lobby
        $lobbyPlayer = new LobbyPlayer();
        $lobbyPlayer->setLobby($lobby);
        $lobbyPlayer->setUser($user);
        $lobbyPlayer->setIsLeader(false);

        $this->entityManager->persist($lobbyPlayer);
        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Você entrou na lobby!',
            'lobby' => [
                'id' => $lobby->getId(),
                'name' => $lobby->getName(),
                'playerCount' => $lobby->getPlayerCount(),
                'isFull' => $lobby->isFull()
            ]
        ]);
    }

    #[Route('/leave', name: 'lobby_leave', methods: ['POST'])]
    public function leave(): JsonResponse
    {
        $user = $this->getUser();
        $lobbyPlayer = $this->lobbyPlayerRepository->findOneBy(['user' => $user]);

        if (!$lobbyPlayer) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Você não está em nenhuma lobby!'
            ], 400);
        }

        $lobby = $lobbyPlayer->getLobby();
        $isLeader = $lobbyPlayer->isLeader();

        if ($isLeader) {
            // Se é o líder, remover toda a lobby
            $this->entityManager->remove($lobby);
        } else {
            // Se não é líder, apenas sair
            $this->entityManager->remove($lobbyPlayer);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $isLeader ? 'Lobby removida!' : 'Você saiu da lobby!'
        ]);
    }

    #[Route('/challenge/{id}', name: 'lobby_challenge', methods: ['POST'])]
    public function challenge(Lobby $targetLobby): JsonResponse
    {
        $user = $this->getUser();
        $userLobbyPlayer = $this->lobbyPlayerRepository->findOneBy(['user' => $user]);

        if (!$userLobbyPlayer || !$userLobbyPlayer->isLeader()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Apenas o líder pode desafiar outras lobbies!'
            ], 400);
        }

        $userLobby = $userLobbyPlayer->getLobby();

        if (!$userLobby->isFull() || !$targetLobby->isFull()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ambas as lobbies precisam estar cheias para desafiar!'
            ], 400);
        }

        // Marcar ambas as lobbies como matched
        $userLobby->setMatched(true);
        $targetLobby->setMatched(true);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Desafio enviado! Partida iniciada!'
        ]);
    }

    #[Route('/refresh', name: 'lobby_refresh', methods: ['GET'])]
    public function refresh(): JsonResponse
    {
        $user = $this->getUser();
        $userLobbyPlayer = $this->lobbyPlayerRepository->findOneBy(['user' => $user]);
        $userLobby = $userLobbyPlayer ? $userLobbyPlayer->getLobby() : null;

        // Preparar dados da lobby do usuário
        $userLobbyData = null;
        if ($userLobby) {
            $players = [];
            foreach ($userLobby->getPlayers() as $player) {
                $players[] = [
                    'steamName' => $player->getUser()->getSteamName(),
                    'steamAvatar' => $player->getUser()->getSteamAvatar(),
                    'displayName' => $player->getUser()->getDisplayName(),
                    'isLeader' => $player->isLeader()
                ];
            }

            $userLobbyData = [
                'id' => $userLobby->getId(),
                'name' => $userLobby->getName(),
                'playerCount' => $userLobby->getPlayerCount(),
                'isFull' => $userLobby->isFull(),
                'players' => $players,
                'isLeader' => $userLobbyPlayer && $userLobbyPlayer->isLeader()
            ];
        }

        // Preparar dados das lobbies disponíveis
        if ($userLobby && $userLobby->isFull()) {
            // Mostrar lobbies para desafiar
            $availableLobbies = $this->lobbyRepository->createQueryBuilder('l')
                ->where('l.id != :userLobbyId')
                ->andWhere('SIZE(l.players) = 5')
                ->andWhere('l.matched = false')
                ->setParameter('userLobbyId', $userLobby->getId())
                ->getQuery()
                ->getResult();
        } else {
            // Mostrar lobbies abertas
            $availableLobbies = $this->lobbyRepository->createQueryBuilder('l')
                ->where('SIZE(l.players) < 5')
                ->andWhere('l.matched = false')
                ->orderBy('l.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
                
            if ($userLobby) {
                $availableLobbies = array_filter($availableLobbies, function($lobby) use ($userLobby) {
                    return $lobby->getId() !== $userLobby->getId();
                });
            }
        }

        $lobbiesData = [];
        foreach ($availableLobbies as $lobby) {
            $leader = $lobby->getLeader();
            $lobbiesData[] = [
                'id' => $lobby->getId(),
                'name' => $lobby->getName(),
                'playerCount' => $lobby->getPlayerCount(),
                'isFull' => $lobby->isFull(),
                'leader' => [
                    'steamName' => $leader?->getSteamName(),
                    'steamAvatar' => $leader?->getSteamAvatar(),
                    'displayName' => $leader?->getDisplayName()
                ]
            ];
        }

        return new JsonResponse([
            'userLobby' => $userLobbyData,
            'availableLobbies' => $lobbiesData,
            'isInFullLobby' => $userLobby && $userLobby->isFull(),
            'isLeader' => $userLobbyPlayer && $userLobbyPlayer->isLeader()
        ]);
    }
}
