<?php

namespace App\Service;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack
    ) {
    }

    public function log(
        string $action,
        ?User $actor = null,
        ?string $targetEmail = null,
        ?string $details = null
    ): void {
        $request = $this->requestStack->getCurrentRequest();

        $log = new ActivityLog();
        $log->setAction($action);
        $log->setActorEmail($actor?->getEmail());
        $log->setActorRole($actor?->getRole()->value);
        $log->setTargetEmail($targetEmail);
        $log->setDetails($details);
        $log->setIpAddress($request?->getClientIp());
        $log->setUserAgent($request?->headers->get('User-Agent'));
        $log->setSessionId($request?->hasSession() ? $request->getSession()->getId() : null);
        $log->setRoute($request?->attributes->get('_route'));
        $log->setMethod($request?->getMethod());
        $log->setUrl($request?->getUri());
        $log->setIsSeen(false);

        $this->entityManager->persist($log);
        try {
            $this->entityManager->flush();
        } catch (TableNotFoundException) {
            // Migration not executed yet; do not break the app.
            $this->entityManager->clear();
        }
    }
}

