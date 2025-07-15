<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LightOpenID;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class SteamAuthService
{
    private const STEAM_OPENID_URL = 'https://steamcommunity.com/openid';
    private const STEAM_API_URL = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private string $steamApiKey
    ) {
    }

    public function getAuthUrl(Request $request): string
    {
        $openid = new LightOpenID($request->getSchemeAndHttpHost());
        $openid->identity = self::STEAM_OPENID_URL;
        $openid->returnUrl = $request->getSchemeAndHttpHost() . '/auth/steam/callback';
        $openid->realm = $request->getSchemeAndHttpHost();

        return (string) $openid->authUrl();
    }

    public function validateAndGetUser(Request $request): ?User
    {
        $openid = new LightOpenID($request->getSchemeAndHttpHost());
        
        if (!$openid->validate()) {
            throw new AuthenticationException('Falha na validação OpenID Steam');
        }

        $steamId = $this->extractSteamId($openid->identity);
        if (!$steamId) {
            throw new AuthenticationException('ID Steam inválido');
        }

        return $this->getOrCreateUser($steamId);
    }

    private function extractSteamId(string $identity): ?string
    {
        $matches = [];
        if (preg_match('/\/id\/(\d+)$/', $identity, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function getOrCreateUser(string $steamId): User
    {
        $user = $this->userRepository->findOneBy(['steamId' => $steamId]);
        
        if (!$user) {
            $steamData = $this->getSteamUserData($steamId);
            $user = $this->createUserFromSteamData($steamId, $steamData);
        } else {
            // Atualizar dados do Steam
            $steamData = $this->getSteamUserData($steamId);
            $this->updateUserSteamData($user, $steamData);
        }

        return $user;
    }

    private function getSteamUserData(string $steamId): array
    {
        $url = self::STEAM_API_URL . "?key={$this->steamApiKey}&steamids={$steamId}";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Symfony Steam Auth'
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new AuthenticationException('Erro ao buscar dados do Steam');
        }

        $data = json_decode($response, true);
        
        if (!isset($data['response']['players'][0])) {
            throw new AuthenticationException('Usuário Steam não encontrado');
        }

        return $data['response']['players'][0];
    }

    private function createUserFromSteamData(string $steamId, array $steamData): User
    {
        $user = new User();
        $user->setSteamId($steamId);
        $user->setSteamName($steamData['personaname'] ?? 'Unknown');
        $user->setSteamAvatar($steamData['avatarfull'] ?? null);
        $user->setSteamProfileUrl($steamData['profileurl'] ?? null);
        
        // Email temporário baseado no Steam ID
        $user->setEmail("steam_{$steamId}@temp.local");
        $user->setIsVerified(true);
        $user->setRoles(['ROLE_USER']);
        
        // Password placeholder (usuários Steam não precisam de senha)
        $user->setPassword('STEAM_AUTH');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function updateUserSteamData(User $user, array $steamData): void
    {
        $user->setSteamName($steamData['personaname'] ?? $user->getSteamName());
        $user->setSteamAvatar($steamData['avatarfull'] ?? $user->getSteamAvatar());
        $user->setSteamProfileUrl($steamData['profileurl'] ?? $user->getSteamProfileUrl());

        $this->entityManager->flush();
    }
}
