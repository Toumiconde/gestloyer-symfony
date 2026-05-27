<?php

namespace App\Service;

use App\Entity\Proprietaire;
use App\Entity\Reversement;
use App\Enum\StatutReversement;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReversementService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PaiementRepository $paiementRepository
    ) {
    }

    public function calculerReversementMensuel(Proprietaire $proprietaire, \DateTimeImmutable $mois): Reversement
    {
        $reversement = new Reversement();
        $reversement->setProprietaire($proprietaire);
        $reversement->setMois($mois);

        // Calculate total amount collected from all contracts for this owner this month
        $loyersCollectes = $this->paiementRepository->calculerTotalBrutByProprietaireAndMois($proprietaire, $mois);
        
        $montantBrut = $loyersCollectes ?? '0.00';
        $reversement->setMontantBrut($montantBrut);

        // Calculate 10% commission
        $commission = bcmul($montantBrut, '0.10', 2);
        $reversement->setCommission($commission);

        // Subtract maintenance fees (mocked for now)
        $fraisMaintenance = '0.00';
        $reversement->setFraisMaintenance($fraisMaintenance);

        // Net Amount = Brut - Commission - Frais
        $montantNet = bcsub(bcsub($montantBrut, $commission, 2), $fraisMaintenance, 2);
        $reversement->setMontantNet($montantNet);

        $reversement->setStatut(StatutReversement::CALCULE);

        $this->entityManager->persist($reversement);
        $this->entityManager->flush();

        return $reversement;
    }

    public function cloturerReversement(Reversement $reversement): void
    {
        $reversement->setStatut(StatutReversement::VERSE);
        $this->entityManager->flush();
        
        // Dispatch message to generate Bordereau PDF
        // $this->messageBus->dispatch(new GenerateBordereauPdfMessage($reversement->getId()));
    }
}
