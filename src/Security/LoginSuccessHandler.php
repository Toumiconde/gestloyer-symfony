<?php

namespace App\Security;

use App\Entity\User;
use App\Enum\RoleUtilisateur;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private RouterInterface $router)
    {
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): ?Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return new RedirectResponse($this->router->generate('app_dashboard'));
        }

        return match ($user->getRole()) {
            RoleUtilisateur::LOCATAIRE => new RedirectResponse($this->router->generate('app_locataire_home')),
            RoleUtilisateur::PROPRIETAIRE => new RedirectResponse($this->router->generate('app_proprietaire_home')),
            default => new RedirectResponse($this->router->generate('app_dashboard')),
        };
    }
}

