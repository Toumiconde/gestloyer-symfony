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

        // Capture du payload (données de formulaire) pour les requêtes POST/PUT/PATCH
        if ($request && in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'])) {
            $payload = $request->request->all();
            
            // Suppression des données sensibles
            $sensitiveKeys = ['password', '_password', 'token', '_token', 'csrf'];
            $cleanPayload = $this->sanitizePayload($payload, $sensitiveKeys);
            
            if (!empty($cleanPayload)) {
                $log->setPayload($cleanPayload);
            }
        }

        $this->entityManager->persist($log);
        try {
            $this->entityManager->flush();
        } catch (TableNotFoundException) {
            // Migration not executed yet; do not break the app.
            $this->entityManager->clear();
        }
    }
    private function sanitizePayload(array $payload, array $sensitiveKeys): array
    {
        $sanitized = [];
        foreach ($payload as $key => $value) {
            $isSensitive = false;
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos((string)$key, $sensitive) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '***';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizePayload($value, $sensitiveKeys);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }
}

