<?php

namespace App\EventSubscriber;

use App\Entity\Contrat;
use App\Enum\StatutBien;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

class ContratLifecycleSubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Contrat) {
            return;
        }

        // Si le contrat est créé actif, le bien devient occupé
        if ($entity->getStatut() === \App\Enum\StatutContrat::ACTIF) {
            $bien = $entity->getBien();
            if ($bien->getStatut() !== StatutBien::OCCUPE) {
                $bien->setStatut(StatutBien::OCCUPE);
                $args->getObjectManager()->flush();
            }
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Contrat) {
            return;
        }

        // Si le contrat est résilié ou expiré, on libère le bien
        if (in_array($entity->getStatut(), [\App\Enum\StatutContrat::RESILIE, \App\Enum\StatutContrat::EXPIRE])) {
            $bien = $entity->getBien();
            if ($bien->getStatut() === StatutBien::OCCUPE) {
                $bien->setStatut(StatutBien::DISPONIBLE);
                $args->getObjectManager()->flush();
            }
        }
    }
}
