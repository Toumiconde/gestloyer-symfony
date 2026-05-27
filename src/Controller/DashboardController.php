<?php

namespace App\Controller;

use App\Repository\BienRepository;
use App\Repository\PaiementRepository;
use App\Repository\ContratRepository;
use App\Enum\StatutBien;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        BienRepository $bienRepository, 
        PaiementRepository $paiementRepository,
        ContratRepository $contratRepository
    ): Response {
        $user = $this->getUser();
        
        // Calcul des statistiques réelles depuis la base de données
        $totalBiens = $bienRepository->count([]);
        
        // Utilisation d'une requête QueryBuilder pour compter les biens occupés
        $biensOccupes = $bienRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.statut = :statut')
            ->setParameter('statut', StatutBien::OCCUPE)
            ->getQuery()
            ->getSingleScalarResult();

        $tauxOccupation = $totalBiens > 0 ? round(($biensOccupes / $totalBiens) * 100, 1) : 0;

        // Calcul des loyers encaissés (Total des paiements validés)
        $loyersEncaisses = $paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montantVerse)')
            ->where('p.statut = :statut')
            ->setParameter('statut', \App\Enum\StatutPaiement::VALIDE)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Calcul des impayés (Total des paiements en attente ou partiels)
        $impayes = $paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montantDu - p.montantVerse)')
            ->where('p.statut IN (:statuts)')
            ->setParameter('statuts', [\App\Enum\StatutPaiement::EN_ATTENTE, \App\Enum\StatutPaiement::PARTIEL])
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        // Derniers contrats actifs
        $derniersContrats = $contratRepository->findBy([], ['dateDebut' => 'DESC'], 5);

        $stats = [
            'biens_total' => $totalBiens,
            'biens_occupes' => $biensOccupes,
            'taux_occupation' => $tauxOccupation,
            'loyers_encaisses' => $loyersEncaisses,
            'impayes' => $impayes,
            'incidents_ouverts' => 0 // À lier avec IncidentRepository plus tard
        ];

        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
            'stats' => $stats,
            'derniers_contrats' => $derniersContrats
        ]);
    }
}
