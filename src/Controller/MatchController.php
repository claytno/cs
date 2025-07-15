<?php

namespace App\Controller;

use App\Entity\GameMatch;
use App\Entity\Lobby;
use App\Entity\MapVeto;
use App\Repository\GameMatchRepository;
use App\Repository\LobbyRepository;
use App\Repository\MapVetoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/match')]
#[IsGranted('ROLE_USER')]
class MatchController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private GameMatchRepository $gameMatchRepository,
        private LobbyRepository $lobbyRepository,
        private MapVetoRepository $mapVetoRepository
    ) {}

    #[Route('/challenge/{id}', name: 'match_challenge', methods: ['POST'])]
    public function challenge(Lobby $targetLobby): JsonResponse
    {
        $user = $this->getUser();
        
        // Verificar se o usuário é líder de uma lobby
        $userLobbyPlayer = $this->entityManager->getRepository(\App\Entity\LobbyPlayer::class)
            ->findOneBy(['user' => $user, 'isLeader' => true]);
        
        if (!$userLobbyPlayer) {
            return new JsonResponse(['error' => 'Você precisa ser líder de uma lobby para desafiar.'], 400);
        }

        $userLobby = $userLobbyPlayer->getLobby();
        
        // Verificar se ambas as lobbies estão cheias
        if (!$userLobby->isFull() || !$targetLobby->isFull()) {
            return new JsonResponse(['error' => 'Ambas as lobbies devem estar cheias.'], 400);
        }

        // Verificar se já existe um match ativo para qualquer uma das lobbies
        $existingMatch = $this->gameMatchRepository->findActiveMatchForLobby($userLobby);
        if ($existingMatch) {
            return new JsonResponse(['error' => 'Sua lobby já está em uma partida.'], 400);
        }

        $existingMatch = $this->gameMatchRepository->findActiveMatchForLobby($targetLobby);
        if ($existingMatch) {
            return new JsonResponse(['error' => 'A lobby alvo já está em uma partida.'], 400);
        }

        // Criar o desafio
        $match = new GameMatch();
        $match->setLobbyA($userLobby);
        $match->setLobbyB($targetLobby);
        $match->setLobbyAAccepted(true); // Quem desafia já aceita automaticamente
        $match->setStage(GameMatch::STAGE_WAITING);

        $this->entityManager->persist($match);
        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Desafio enviado!']);
    }

    #[Route('/accept/{id}', name: 'match_accept', methods: ['POST'])]
    public function accept(GameMatch $match): JsonResponse
    {
        $user = $this->getUser();
        
        // Verificar se o usuário é líder da lobby B (a que foi desafiada)
        $userLobbyPlayer = $this->entityManager->getRepository(\App\Entity\LobbyPlayer::class)
            ->findOneBy(['user' => $user, 'isLeader' => true]);
        
        if (!$userLobbyPlayer || $userLobbyPlayer->getLobby() !== $match->getLobbyB()) {
            return new JsonResponse(['error' => 'Você não pode aceitar este desafio.'], 400);
        }

        if ($match->getStage() !== GameMatch::STAGE_WAITING) {
            return new JsonResponse(['error' => 'Este desafio não pode mais ser aceito.'], 400);
        }

        $match->setLobbyBAccepted(true);
        
        // Se ambas aceitaram, iniciar o veto
        if ($match->bothAccepted()) {
            $match->setStage(GameMatch::STAGE_VETO);
            $match->setStartedAt(new \DateTimeImmutable());
        }

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Desafio aceito! Iniciando veto de mapas.']);
    }

    #[Route('/decline/{id}', name: 'match_decline', methods: ['POST'])]
    public function decline(GameMatch $match): JsonResponse
    {
        $user = $this->getUser();
        
        // Verificar se o usuário é líder da lobby B (a que foi desafiada)
        $userLobbyPlayer = $this->entityManager->getRepository(\App\Entity\LobbyPlayer::class)
            ->findOneBy(['user' => $user, 'isLeader' => true]);
        
        if (!$userLobbyPlayer || $userLobbyPlayer->getLobby() !== $match->getLobbyB()) {
            return new JsonResponse(['error' => 'Você não pode recusar este desafio.'], 400);
        }

        if ($match->getStage() !== GameMatch::STAGE_WAITING) {
            return new JsonResponse(['error' => 'Este desafio não pode mais ser recusado.'], 400);
        }

        $match->setLobbyBAccepted(false);
        $match->setStage(GameMatch::STAGE_FINISHED);
        $match->setFinishedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Desafio recusado.']);
    }

    #[Route('/veto', name: 'match_veto', methods: ['GET'])]
    public function veto(): Response
    {
        $user = $this->getUser();
        
        // Buscar match ativo do usuário
        $match = $this->gameMatchRepository->findActiveMatchForUser($user);
        
        if (!$match || $match->getStage() !== GameMatch::STAGE_VETO) {
            return $this->redirectToRoute('lobby_index');
        }

        $vetos = $this->mapVetoRepository->findByMatchOrderedByRound($match);
        $availableMaps = $this->mapVetoRepository->getAvailableMapsForMatch($match);
        $nextRound = $this->mapVetoRepository->getNextRoundForMatch($match);
        
        // Determinar de quem é a vez
        $isUserTurn = $this->isUserTurnInVeto($match, $user, $nextRound);
        $isUserCaptain = $match->isUserCaptain($user);

        return $this->render('match/veto.html.twig', [
            'match' => $match,
            'vetos' => $vetos,
            'availableMaps' => $availableMaps,
            'nextRound' => $nextRound,
            'isUserTurn' => $isUserTurn,
            'isUserCaptain' => $isUserCaptain,
            'userLobby' => $match->getUserLobby($user)
        ]);
    }

    #[Route('/veto/ban', name: 'match_veto_ban', methods: ['POST'])]
    public function vetoBan(Request $request): JsonResponse
    {
        $user = $this->getUser();
        $mapName = $request->request->get('map');
        
        $match = $this->gameMatchRepository->findActiveMatchForUser($user);
        
        if (!$match || $match->getStage() !== GameMatch::STAGE_VETO) {
            return new JsonResponse(['error' => 'Match não encontrada ou não está na fase de veto.'], 400);
        }

        if (!$match->isUserCaptain($user)) {
            return new JsonResponse(['error' => 'Apenas capitães podem vetar.'], 400);
        }

        $nextRound = $this->mapVetoRepository->getNextRoundForMatch($match);
        
        if (!$this->isUserTurnInVeto($match, $user, $nextRound)) {
            return new JsonResponse(['error' => 'Não é sua vez de vetar.'], 400);
        }

        $availableMaps = $this->mapVetoRepository->getAvailableMapsForMatch($match);
        if (!in_array($mapName, $availableMaps)) {
            return new JsonResponse(['error' => 'Mapa não disponível para veto.'], 400);
        }

        // Criar o veto
        $veto = new MapVeto();
        $veto->setMatch($match);
        $veto->setLobby($match->getUserLobby($user));
        $veto->setMapName($mapName);
        $veto->setType(MapVeto::TYPE_BAN);
        $veto->setRound($nextRound);

        $this->entityManager->persist($veto);

        // Verificar se é o último ban (deve sobrar 1 mapa)
        $availableMapsAfterBan = array_filter($availableMaps, fn($map) => $map !== $mapName);
        
        if (count($availableMapsAfterBan) === 1) {
            // Último mapa é selecionado automaticamente
            $finalMap = array_values($availableMapsAfterBan)[0];
            $match->setSelectedMap($finalMap);
            $match->setStage(GameMatch::STAGE_CONNECT);
            
            // Gerar informações do servidor (aqui você pode integrar com seu sistema de servidores)
            $match->setServerInfo($this->generateServerInfo());
        }

        $this->entityManager->flush();

        $response = ['success' => true, 'message' => 'Mapa banido!'];
        
        if ($match->getStage() === GameMatch::STAGE_CONNECT) {
            $response['redirect'] = $this->generateUrl('match_connect');
            $response['selectedMap'] = $finalMap;
        }

        return new JsonResponse($response);
    }

    #[Route('/connect', name: 'match_connect', methods: ['GET'])]
    public function connect(): Response
    {
        $user = $this->getUser();
        
        $match = $this->gameMatchRepository->findActiveMatchForUser($user);
        
        if (!$match || $match->getStage() !== GameMatch::STAGE_CONNECT) {
            return $this->redirectToRoute('lobby_index');
        }

        return $this->render('match/connect.html.twig', [
            'match' => $match
        ]);
    }

    #[Route('/status', name: 'match_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $user = $this->getUser();
        
        $match = $this->gameMatchRepository->findActiveMatchForUser($user);
        
        if (!$match) {
            return new JsonResponse(['stage' => null]);
        }

        $data = [
            'stage' => $match->getStage(),
            'matchId' => $match->getId()
        ];

        // Adicionar informações específicas do estágio
        switch ($match->getStage()) {
            case GameMatch::STAGE_WAITING:
                $data['lobbyAAccepted'] = $match->isLobbyAAccepted();
                $data['lobbyBAccepted'] = $match->isLobbyBAccepted();
                break;
                
            case GameMatch::STAGE_VETO:
                $nextRound = $this->mapVetoRepository->getNextRoundForMatch($match);
                $data['isUserTurn'] = $this->isUserTurnInVeto($match, $user, $nextRound);
                $data['isUserCaptain'] = $match->isUserCaptain($user);
                $data['availableMaps'] = $this->mapVetoRepository->getAvailableMapsForMatch($match);
                break;
                
            case GameMatch::STAGE_CONNECT:
                $data['selectedMap'] = $match->getSelectedMap();
                $data['serverInfo'] = $match->getServerInfo();
                break;
        }

        return new JsonResponse($data);
    }

    private function isUserTurnInVeto(GameMatch $match, $user, int $round): bool
    {
        $userLobby = $match->getUserLobby($user);
        if (!$userLobby) {
            return false;
        }

        // Sistema alternado: rounds ímpares = lobby A, rounds pares = lobby B
        $expectedLobby = ($round % 2 === 1) ? $match->getLobbyA() : $match->getLobbyB();
        
        return $userLobby === $expectedLobby;
    }

    private function generateServerInfo(): string
    {
        // Aqui você pode integrar com seu sistema de servidores
        // Por enquanto, gerar um IP e porta fictícios
        $ip = '192.168.1.' . rand(100, 200);
        $port = rand(27015, 27050);
        
        return "connect {$ip}:{$port}";
    }
}
