<?php

namespace App\Controller;

use App\Service\SteamAuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SteamAuthController extends AbstractController
{
    public function __construct(
        private SteamAuthService $steamAuthService,
        private TokenStorageInterface $tokenStorage
    ) {
    }

    #[Route('/auth/steam', name: 'app_steam_login')]
    public function steamLogin(Request $request): RedirectResponse
    {
        $authUrl = $this->steamAuthService->getAuthUrl($request);
        return new RedirectResponse($authUrl);
    }

    #[Route('/auth/steam/callback', name: 'app_steam_callback')]
    public function steamCallback(Request $request): Response
    {
        try {
            $user = $this->steamAuthService->validateAndGetUser($request);
            
            // Autenticar o usuário manualmente
            $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($token);
            
            $this->addFlash('success', 'Login com Steam realizado com sucesso!');
            
            return $this->redirectToRoute('app_home');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erro no login Steam: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}
