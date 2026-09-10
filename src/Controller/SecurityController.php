<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class SecurityController extends AbstractController
{
    use TargetPathTrait;

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/login/google', name: 'app_login_google')]
    public function googleLogin(Request $request): Response
    {
        $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('app_home'));
        return $this->redirectToRoute('hwi_oauth_service_redirect', [
            'service' => 'google'
        ]);
    }

    #[Route('/login/github', name: 'app_login_github')]
    public function githubLogin(Request $request): Response
    {
        $this->saveTargetPath($request->getSession(), 'main', $this->generateUrl('app_home'));
        return $this->redirectToRoute('hwi_oauth_service_redirect', [
            'service' => 'github'
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
