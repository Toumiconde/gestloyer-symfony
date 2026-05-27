<?php

namespace App\MessageHandler;

use App\Message\GenerateQuittancePdfMessage;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GenerateQuittancePdfHandler
{
    public function __construct(
        private PaiementRepository $paiementRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(GenerateQuittancePdfMessage $message)
    {
        $paiementId = $message->getPaiementId();
        $paiement = $this->paiementRepository->find($paiementId);

        if (!$paiement) {
            return;
        }

        // 1. Generate PDF logic using KnpSnappyBundle (mocked here)
        // $pdfContent = $this->pdfGenerator->generateFromHtml($html);
        $pdfPath = '/uploads/quittances/quittance_' . $paiement->getId() . '.pdf';

        // 2. Create Quittance entity if not exists
        if (!$paiement->getQuittance()) {
            $quittance = new \App\Entity\Quittance();
            $quittance->setNumero('QUI-' . date('Y') . '-' . str_pad($paiement->getId(), 4, '0', STR_PAD_LEFT));
            $quittance->setPdfPath($pdfPath);
            $quittance->setPaiement($paiement);

            $this->entityManager->persist($quittance);
            $this->entityManager->flush();
        }
    }
}
