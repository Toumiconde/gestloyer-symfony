<?php

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

class AuditLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->logAction('CREATED', $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->logAction('UPDATED', $args->getObject());
    }

    private function logAction(string $action, object $entity): void
    {
        $className = get_class($entity);
        $id = method_exists($entity, 'getId') ? $entity->getId() : 'N/A';
        
        // Ensure 100% action tracked (O4 Traçabilité totale)
        $this->logger->info(sprintf('[AUDIT] %s - %s (ID: %s)', $action, $className, $id));
    }
}
