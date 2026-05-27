<?php

namespace App\Service;

use App\Entity\Paiement;
use App\Entity\Versement;
use App\Enum\ModePaiement;
use App\Enum\StatutPaiement;
use App\Message\GenerateQuittancePdfMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PaiementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus
    ) {
    }

    public function declarerVersement(Paiement $paiement, string $montant, ModePaiement $mode): Versement
    {
        $versement = new Versement();
        $versement->setMontant($montant);
        $versement->setMode($mode);
        $paiement->addVersement($versement);

        // Update paiement status
        if ($paiement->isComplet()) {
            $paiement->setStatut(StatutPaiement::COMPLET);
        } else {
            $paiement->setStatut(StatutPaiement::PARTIEL);
        }

        $this->entityManager->persist($versement);
        $this->entityManager->flush();

        return $versement;
    }

    public function validerPaiement(Paiement $paiement): void
    {
        if (!$paiement->isComplet()) {
            throw new \LogicException('Le paiement n\'est pas complet et ne peut pas être validé.');
        }

        $paiement->setStatut(StatutPaiement::VALIDE);

        foreach ($paiement->getVersements() as $versement) {
            $versement->setEstValide(true);
        }

        $this->entityManager->flush();

        // Dispatch async message for Quittance PDF generation
        $this->messageBus->dispatch(new GenerateQuittancePdfMessage($paiement->getId()));
    }
}
