<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private ActivityLogService $activityLogService
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 0],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = strtoupper((string) $request->getMethod());

        // Keep the audit useful (avoid logging every page view)
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return;
        }

        $token = $this->tokenStorage->getToken();
        $user = $token?->getUser();
        if (!$user instanceof User) {
            return;
        }

        $route = (string) $request->attributes->get('_route');
        if ($route === 'app_login' || $route === 'app_logout') {
            return;
        }

        $this->activityLogService->log(
            action: 'REQUEST',
            actor: $user,
            details: sprintf('%s %s (%s)', $method, $request->getPathInfo(), $route)
        );
    }
}

