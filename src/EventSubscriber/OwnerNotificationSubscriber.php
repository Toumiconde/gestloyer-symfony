<?php

namespace App\EventSubscriber;

use App\Entity\Paiement;
use App\Entity\Incident;
use App\Entity\Devis;
use App\Entity\Notification;
use App\Enum\StatutPaiement;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

class OwnerNotificationSubscriber implements EventSubscriberInterface
{
    private bool $processing = false;

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postUpdate,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        if ($this->processing) {
            return;
        }

        $entity = $args->getObject();
        $em = $args->getObjectManager();

        if ($entity instanceof Incident) {
            $bien = $entity->getBien();
            $owner = $bien?->getProprietaire();
            $ownerUser = $owner?->getUser();

            if ($ownerUser) {
                $notification = new Notification();
                $notification->setUser($ownerUser);
                $notification->setTitle('Nouvel incident déclaré');
                $notification->setMessage(sprintf(
                    'Un nouvel incident "%s" a été déclaré sur votre bien "%s".',
                    $entity->getTitre(),
                    $bien->getNom()
                ));
                $notification->setType('incident');

                $this->processing = true;
                try {
                    $em->persist($notification);
                    $em->flush();
                } finally {
                    $this->processing = false;
                }
            }
        } elseif ($entity instanceof Devis) {
            $incident = $entity->getIncident();
            $bien = $incident?->getBien();
            $owner = $bien?->getProprietaire();
            $ownerUser = $owner?->getUser();

            if ($ownerUser && $entity->getStatut() === 'SOUMIS') {
                $notification = new Notification();
                $notification->setUser($ownerUser);
                $notification->setTitle('Nouveau devis soumis');
                $notification->setMessage(sprintf(
                    'Un devis de %s GNF par %s a été soumis pour l\'incident "%s" sur votre bien "%s". En attente de votre approbation.',
                    number_format((float)$entity->getMontant(), 0, ',', ' '),
                    $entity->getPrestataire(),
                    $incident->getTitre(),
                    $bien->getNom()
                ));
                $notification->setType('devis');

                $this->processing = true;
                try {
                    $em->persist($notification);
                    $em->flush();
                } finally {
                    $this->processing = false;
                }
            }
        } elseif ($entity instanceof Paiement) {
            if ($entity->getStatut() === StatutPaiement::VALIDE) {
                $contrat = $entity->getContrat();
                $bien = $contrat?->getBien();
                $owner = $bien?->getProprietaire();
                $ownerUser = $owner?->getUser();

                if ($ownerUser) {
                    $notification = new Notification();
                    $notification->setUser($ownerUser);
                    $notification->setTitle('Paiement reçu');
                    $notification->setMessage(sprintf(
                        'Le paiement de loyer pour votre bien "%s" a été validé pour un montant de %s GNF.',
                        $bien->getNom(),
                        number_format((float)$entity->getMontantVerse(), 0, ',', ' ')
                    ));
                    $notification->setType('paiement');

                    $this->processing = true;
                    try {
                        $em->persist($notification);
                        $em->flush();
                    } finally {
                        $this->processing = false;
                    }
                }
            }
        }
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        if ($this->processing) {
            return;
        }

        $entity = $args->getObject();
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        if ($entity instanceof Paiement) {
            $changeset = $uow->getEntityChangeSet($entity);
            if (isset($changeset['statut'])) {
                $old = $changeset['statut'][0];
                $new = $changeset['statut'][1];

                if ($new === StatutPaiement::VALIDE && $old !== StatutPaiement::VALIDE) {
                    $contrat = $entity->getContrat();
                    $bien = $contrat?->getBien();
                    $owner = $bien?->getProprietaire();
                    $ownerUser = $owner?->getUser();

                    if ($ownerUser) {
                        $notification = new Notification();
                        $notification->setUser($ownerUser);
                        $notification->setTitle('Paiement reçu');
                        $notification->setMessage(sprintf(
                            'Le paiement de loyer pour votre bien "%s" a été validé pour un montant de %s GNF.',
                            $bien->getNom(),
                            number_format((float)$entity->getMontantVerse(), 0, ',', ' ')
                        ));
                        $notification->setType('paiement');

                        $this->processing = true;
                        try {
                            $em->persist($notification);
                            $em->flush();
                        } finally {
                            $this->processing = false;
                        }
                    }
                }
            }
        } elseif ($entity instanceof Devis) {
            $changeset = $uow->getEntityChangeSet($entity);
            if (isset($changeset['statut'])) {
                $old = $changeset['statut'][0];
                $new = $changeset['statut'][1];

                if ($new === 'SOUMIS' && $old !== 'SOUMIS') {
                    $incident = $entity->getIncident();
                    $bien = $incident?->getBien();
                    $owner = $bien?->getProprietaire();
                    $ownerUser = $owner?->getUser();

                    if ($ownerUser) {
                        $notification = new Notification();
                        $notification->setUser($ownerUser);
                        $notification->setTitle('Nouveau devis soumis');
                        $notification->setMessage(sprintf(
                            'Un devis de %s GNF par %s a été soumis pour l\'incident "%s" sur votre bien "%s". En attente de votre approbation.',
                            number_format((float)$entity->getMontant(), 0, ',', ' '),
                            $entity->getPrestataire(),
                            $incident->getTitre(),
                            $bien->getNom()
                        ));
                        $notification->setType('devis');

                        $this->processing = true;
                        try {
                            $em->persist($notification);
                            $em->flush();
                        } finally {
                            $this->processing = false;
                        }
                    }
                }
            }
        }
    }
}
