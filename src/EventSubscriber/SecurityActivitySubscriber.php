<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private ActivityLogService $activityLogService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $this->activityLogService->log(
            action: 'LOGIN_SUCCESS',
            actor: $user,
            targetEmail: $user->getEmail(),
            details: 'Connexion reussie'
        );
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $tokenUser = $token?->getUser();

        if (!$tokenUser instanceof User) {
            return;
        }

        $this->activityLogService->log(
            action: 'LOGOUT',
            actor: $tokenUser,
            targetEmail: $tokenUser->getEmail(),
            details: 'Deconnexion utilisateur'
        );
    }
}

