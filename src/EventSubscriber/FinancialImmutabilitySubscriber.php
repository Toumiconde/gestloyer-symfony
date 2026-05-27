<?php

namespace App\EventSubscriber;

use App\Entity\Paiement;
use App\Entity\Quittance;
use App\Entity\Versement;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class FinancialImmutabilitySubscriber implements EventSubscriberInterface
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::preUpdate,
            Events::preRemove,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Versement && $entity->isEstValide()) {
            // Un versement validé ne peut pas être modifié (sauf éventuellement par un admin via une commande spéciale)
            throw new \LogicException('Un versement validé est immuable et ne peut pas être modifié.');
        }

        if ($entity instanceof Quittance) {
            throw new \LogicException('Une quittance est un document légal immuable et ne peut pas être modifiée.');
        }

        if ($entity instanceof Paiement && $args->hasChangedField('montantDu')) {
            // Le montant dû d'un paiement déjà généré ne devrait pas changer si des versements existent
            if (!$entity->getVersements()->isEmpty()) {
                throw new \LogicException('Impossible de modifier le montant dû d\'un paiement qui a déjà des versements.');
            }
        }
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Versement && $entity->isEstValide()) {
            throw new \LogicException('Un versement validé est immuable et ne peut pas être supprimé.');
        }

        if ($entity instanceof Quittance) {
            throw new \LogicException('Une quittance est un document légal immuable et ne peut pas être supprimée.');
        }
    }
}
