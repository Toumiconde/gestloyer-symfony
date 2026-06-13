<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\RoleUtilisateur;
use App\Enum\StatutBien;
use App\Enum\StatutContrat;
use App\Enum\StatutIncident;
use App\Enum\StatutPaiement;
use App\Repository\BienRepository;
use App\Repository\ContratRepository;
use App\Repository\IncidentRepository;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    // ══════════════════════════════════════════════════════════════
    // ROUTEUR PRINCIPAL
    // ══════════════════════════════════════════════════════════════
    #[Route('/', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        BienRepository     $bienRepository,
        PaiementRepository $paiementRepository,
        ContratRepository  $contratRepository,
        IncidentRepository $incidentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        return match ($user->getRole()) {
            RoleUtilisateur::LOCATAIRE    => $this->redirectToRoute('app_dashboard_locataire'),
            RoleUtilisateur::PROPRIETAIRE => $this->redirectToRoute('app_dashboard_proprietaire'),
            RoleUtilisateur::GESTIONNAIRE => $this->redirectToRoute('app_dashboard_gestionnaire'),
            RoleUtilisateur::COMPTABLE    => $this->redirectToRoute('app_dashboard_comptable'),
            default                       => $this->renderAdminDashboard(
                $bienRepository,
                $paiementRepository,
                $contratRepository,
                $incidentRepository,
                $user
            ),
        };
    }

    // ══════════════════════════════════════════════════════════════
    // ADMIN
    // ══════════════════════════════════════════════════════════════
    private function renderAdminDashboard(
        BienRepository     $bienRepository,
        PaiementRepository $paiementRepository,
        ContratRepository  $contratRepository,
        IncidentRepository $incidentRepository,
        User               $user
    ): Response {
        $totalBiens   = $bienRepository->count([]);
        $biensOccupes = (int) $bienRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.statut = :s')
            ->setParameter('s', StatutBien::OCCUPE)
            ->getQuery()->getSingleScalarResult();

        $biensVacants = (int) $bienRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.statut = :s')
            ->setParameter('s', StatutBien::DISPONIBLE)
            ->getQuery()->getSingleScalarResult();

        $biensTravaux   = $totalBiens - $biensOccupes - $biensVacants;
        $tauxOccupation = $totalBiens > 0 ? round(($biensOccupes / $totalBiens) * 100, 1) : 0;

        $loyersEncaisses = (float) ($paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montantVerse)')
            ->where('p.statut = :s')
            ->setParameter('s', StatutPaiement::VALIDE)
            ->getQuery()->getSingleScalarResult() ?? 0);

        $impayes = (float) ($paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montantDu - p.montantVerse)')
            ->where('p.statut IN (:s)')
            ->setParameter('s', [StatutPaiement::EN_ATTENTE, StatutPaiement::PARTIEL])
            ->getQuery()->getSingleScalarResult() ?? 0);

        $incidentsByPriorite = $incidentRepository->createQueryBuilder('i')
            ->select('i.priorite, COUNT(i.id) as total')
            ->where('i.statut NOT IN (:s)')
            ->setParameter('s', [StatutIncident::RESOLU, StatutIncident::CLOTURE])
            ->groupBy('i.priorite')
            ->getQuery()->getResult();

        $impayesParLocataire = $paiementRepository->createQueryBuilder('p')
            ->select('u.nom, u.prenom, u.email, SUM(p.montantDu - p.montantVerse) as total_impaye, COUNT(p.id) as nb_retards')
            ->join('p.contrat', 'c')
            ->join('c.locataire', 'u')
            ->where('p.statut IN (:s)')
            ->setParameter('s', [StatutPaiement::EN_ATTENTE, StatutPaiement::PARTIEL])
            ->groupBy('u.id')
            ->orderBy('total_impaye', 'DESC')
            ->setMaxResults(8)
            ->getQuery()->getResult();

        $revenusParProprietaire = $paiementRepository->createQueryBuilder('p')
            ->select('pr.nom, pr.prenom, SUM(p.montantVerse) as total_revenu, COUNT(DISTINCT b.id) as nb_biens')
            ->join('p.contrat', 'c')
            ->join('c.bien', 'b')
            ->join('b.proprietaire', 'pr')
            ->where('p.statut = :s')
            ->setParameter('s', StatutPaiement::VALIDE)
            ->groupBy('pr.id')
            ->orderBy('total_revenu', 'DESC')
            ->setMaxResults(8)
            ->getQuery()->getResult();

        [$revenusLabels, $revenusValues] = $this->buildMonthlyRevenue($paiementRepository);

        return $this->render('dashboard/index.html.twig', [
            'user'                     => $user,
            'stats'                    => [
                'biens_total'       => $totalBiens,
                'biens_occupes'     => $biensOccupes,
                'biens_vacants'     => $biensVacants,
                'biens_travaux'     => $biensTravaux,
                'taux_occupation'   => $tauxOccupation,
                'loyers_encaisses'  => $loyersEncaisses,
                'impayes'           => $impayes,
                'incidents_ouverts' => array_sum(array_column($incidentsByPriorite, 'total')),
            ],
            'derniers_contrats'        => $contratRepository->findBy([], ['dateDebut' => 'DESC'], 5),
            'revenus_labels'           => $revenusLabels,
            'revenus_values'           => $revenusValues,
            'incidents_by_priorite'    => $incidentsByPriorite,
            'impayes_par_locataire'    => $impayesParLocataire,
            'revenus_par_proprietaire' => $revenusParProprietaire,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // LOCATAIRE
    // ══════════════════════════════════════════════════════════════
    #[Route('/locataire', name: 'app_dashboard_locataire', methods: ['GET'])]
    public function locataireDashboard(
        BienRepository     $bienRepository,
        PaiementRepository $paiementRepository,
        ContratRepository  $contratRepository,
        IncidentRepository $incidentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getRole() !== RoleUtilisateur::LOCATAIRE) {
            return $this->redirectToRoute('app_dashboard');
        }

        $contrat   = $contratRepository->findOneBy(
            ['locataire' => $user, 'statut' => StatutContrat::ACTIF],
            ['id' => 'DESC']
        );
        $paiements = $contrat
            ? $paiementRepository->findBy(['contrat' => $contrat], ['mois' => 'DESC'], 6)
            : [];

        $totalDu    = array_sum(array_map(fn($p) => (float) $p->getMontantDu(), $paiements));
        $totalVerse = array_sum(array_map(fn($p) => (float) $p->getMontantVerse(), $paiements));
        $incidents  = $incidentRepository->findBy(['declarant' => $user], ['dateDeclaration' => 'DESC'], 5);
        // Available biens for locataire (future rentals)
        $biensDisponibles = $bienRepository->findBy(['statut' => StatutBien::DISPONIBLE]);

        return $this->render('dashboard/locataire.html.twig', [
            'user'            => $user,
            'contrat'         => $contrat,
            'paiements'       => $paiements,
            'incidents'       => $incidents,
            'biens_disponibles'=> $biensDisponibles,
            'stats'           => [
                'total_du'      => $totalDu,
                'total_verse'   => $totalVerse,
                'solde'         => $totalDu - $totalVerse,
                'nb_paiements'  => count($paiements),
                'nb_incidents'  => count($incidents),
                'contrat_actif' => $contrat !== null,
            ],
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // GESTIONNAIRE
    // ══════════════════════════════════════════════════════════════
    #[Route('/gestionnaire', name: 'app_dashboard_gestionnaire', methods: ['GET'])]
    public function gestionnaireDashboard(
        BienRepository     $bienRepository,
        PaiementRepository $paiementRepository,
        ContratRepository  $contratRepository,
        IncidentRepository $incidentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getRole() !== RoleUtilisateur::GESTIONNAIRE) {
            return $this->redirectToRoute('app_dashboard');
        }

        $totalBiens   = $bienRepository->count([]);
        $totalVacants = $bienRepository->count(['statut' => StatutBien::DISPONIBLE]);
        $totalOccupes = $bienRepository->count(['statut' => StatutBien::OCCUPE]);
        $totalTravaux = (int) $bienRepository->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.statut = :s')
            ->setParameter('s', StatutBien::TRAVAUX)
            ->getQuery()->getSingleScalarResult();

        $totalIncidents = (int) $incidentRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.statut NOT IN (:s)')
            ->setParameter('s', [StatutIncident::RESOLU, StatutIncident::CLOTURE])
            ->getQuery()->getSingleScalarResult();

        $totalRetards = (int) $paiementRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.statut IN (:s)')
            ->setParameter('s', [StatutPaiement::EN_ATTENTE, StatutPaiement::PARTIEL])
            ->getQuery()->getSingleScalarResult();

        $totalContratsActifs = $contratRepository->count(['statut' => StatutContrat::ACTIF]);

        [$revenusLabels, $revenusValues] = $this->buildMonthlyRevenue($paiementRepository);

        return $this->render('dashboard/gestionnaire.html.twig', [
            'user'              => $user,
            'stats'             => [
                'total_biens'           => $totalBiens,
                'total_vacants'         => $totalVacants,
                'total_occupes'         => $totalOccupes,
                'total_travaux'         => $totalTravaux,
                'total_incidents'       => $totalIncidents,
                'total_retards'         => $totalRetards,
                'total_contrats_actifs' => $totalContratsActifs,
            ],
            'biens_vacants'     => $bienRepository->findBy(
                ['statut' => StatutBien::DISPONIBLE], ['id' => 'DESC'], 5
            ),
            'incidents_ouverts' => $incidentRepository->createQueryBuilder('i')
                ->where('i.statut NOT IN (:s)')
                ->setParameter('s', [StatutIncident::RESOLU, StatutIncident::CLOTURE])
                ->orderBy('i.dateDeclaration', 'DESC')
                ->setMaxResults(5)->getQuery()->getResult(),
            'paiements_retard'  => $paiementRepository->createQueryBuilder('p')
                ->where('p.statut IN (:s)')
                ->setParameter('s', [StatutPaiement::EN_ATTENTE, StatutPaiement::PARTIEL])
                ->orderBy('p.mois', 'ASC')
                ->setMaxResults(5)->getQuery()->getResult(),
            'derniers_contrats' => $contratRepository->findBy(
                ['statut' => StatutContrat::ACTIF], ['dateDebut' => 'DESC'], 5
            ),
            'revenus_labels'    => $revenusLabels,
            'revenus_values'    => $revenusValues,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // PROPRIÉTAIRE
    // ══════════════════════════════════════════════════════════════
    #[Route('/proprietaire', name: 'app_dashboard_proprietaire', methods: ['GET'])]
    public function proprietaireDashboard(
        Request            $request,
        BienRepository     $bienRepository,
        PaiementRepository $paiementRepository,
        ContratRepository  $contratRepository,
        IncidentRepository $incidentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getRole() !== RoleUtilisateur::PROPRIETAIRE) {
            return $this->redirectToRoute('app_dashboard');
        }

        $proprietaire     = $user->getProprietaire();
        $year             = $request->query->getInt('year', (int) date('Y'));
        $month            = $request->query->getInt('month', 0);
        $totalBiens       = 0;
        $biensOccupes     = 0;
        $tauxOccupation   = 0;
        $loyersEncaisses  = 0.0;
        $impayes          = 0.0;
        $incidentsOuverts = 0;
        $derniersContrats = [];

        if ($proprietaire) {
            $totalBiens = $bienRepository->count(['proprietaire' => $proprietaire]);

            $biensOccupes = (int) $bienRepository->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->where('b.proprietaire = :prop')
                ->andWhere('b.statut = :s')
                ->setParameter('prop', $proprietaire)
                ->setParameter('s', StatutBien::OCCUPE)
                ->getQuery()->getSingleScalarResult();

            $tauxOccupation = $totalBiens > 0 ? round(($biensOccupes / $totalBiens) * 100, 1) : 0;

            $loyersEncaisses = (float) ($paiementRepository->createQueryBuilder('p')
                ->select('SUM(p.montantVerse)')
                ->join('p.contrat', 'c')
                ->join('c.bien', 'b')
                ->where('b.proprietaire = :prop')
                ->andWhere('p.statut = :s')
                ->setParameter('prop', $proprietaire)
                ->setParameter('s', StatutPaiement::VALIDE)
                ->getQuery()->getSingleScalarResult() ?? 0);

            $impayes = (float) ($paiementRepository->createQueryBuilder('p')
                ->select('SUM(p.montantDu - p.montantVerse)')
                ->join('p.contrat', 'c')
                ->join('c.bien', 'b')
                ->where('b.proprietaire = :prop')
                ->andWhere('p.statut IN (:s)')
                ->setParameter('prop', $proprietaire)
                ->setParameter('s', [StatutPaiement::EN_ATTENTE, StatutPaiement::PARTIEL])
                ->getQuery()->getSingleScalarResult() ?? 0);

            $incidentsOuverts = (int) $incidentRepository->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->join('i.bien', 'b')
                ->where('b.proprietaire = :prop')
                ->andWhere('i.statut NOT IN (:s)')
                ->setParameter('prop', $proprietaire)
                ->setParameter('s', [StatutIncident::RESOLU, StatutIncident::CLOTURE])
                ->getQuery()->getSingleScalarResult();

            $derniersContrats = $contratRepository->createQueryBuilder('c')
                ->join('c.bien', 'b')
                ->where('b.proprietaire = :prop')
                ->setParameter('prop', $proprietaire)
                ->orderBy('c.dateDebut', 'DESC')
                ->setMaxResults(5)
                ->getQuery()->getResult();
        }

        [$revenusLabels, $revenusValues] = $this->buildMonthlyRevenue(
            $paiementRepository, $proprietaire, $year, $month
        );

        return $this->render('dashboard/proprietaire.html.twig', [
            'user'              => $user,
            'proprietaire'      => $proprietaire,
            'stats'             => [
                'biens_total'       => $totalBiens,
                'biens_occupes'     => $biensOccupes,
                'taux_occupation'   => $tauxOccupation,
                'loyers_encaisses'  => $loyersEncaisses,
                'impayes'           => $impayes,
                'incidents_ouverts' => $incidentsOuverts,
            ],
            'derniers_contrats' => $derniersContrats,
            'revenus_labels'    => $revenusLabels,
            'revenus_values'    => $revenusValues,
            'year'              => $year,
            'month'             => $month,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // COMPTABLE
    // ══════════════════════════════════════════════════════════════
    #[Route('/comptable', name: 'app_dashboard_comptable', methods: ['GET'])]
    public function comptableDashboard(
        PaiementRepository $paiementRepository,
        IncidentRepository $incidentRepository
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->getRole() !== RoleUtilisateur::COMPTABLE) {
            return $this->redirectToRoute('app_dashboard');
        }

        $totalEnAttente = (int) $paiementRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.statut = :s')
            ->setParameter('s', StatutPaiement::EN_ATTENTE)
            ->getQuery()->getSingleScalarResult();

        $montantEnAttente = (float) ($paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montantDu - p.montantVerse)')
            ->where('p.statut = :s')
            ->setParameter('s', StatutPaiement::EN_ATTENTE)
            ->getQuery()->getSingleScalarResult() ?? 0);

        $totalValides = (int) $paiementRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.statut = :s')
            ->setParameter('s', StatutPaiement::VALIDE)
            ->getQuery()->getSingleScalarResult();

        $montantValides = (float) ($paiementRepository->createQueryBuilder('p')
            ->select('SUM(p.montantVerse)')
            ->where('p.statut = :s')
            ->setParameter('s', StatutPaiement::VALIDE)
            ->getQuery()->getSingleScalarResult() ?? 0);

        $incidentsEnCours = (int) $incidentRepository->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->where('i.statut NOT IN (:s)')
            ->setParameter('s', [StatutIncident::RESOLU, StatutIncident::CLOTURE])
            ->getQuery()->getSingleScalarResult();

        $paiementsEnAttente = $paiementRepository->findBy(
            ['statut' => StatutPaiement::EN_ATTENTE],
            ['mois' => 'ASC'],
            10
        );

        [$revenusLabels, $revenusValues] = $this->buildMonthlyRevenue($paiementRepository);

        return $this->render('dashboard/comptable.html.twig', [
            'user'                 => $user,
            'stats'                => [
                'paiements_en_attente' => $totalEnAttente,
                'montant_en_attente'   => $montantEnAttente,
                'paiements_valides'    => $totalValides,
                'montant_valides'      => $montantValides,
                'incidents_en_cours'   => $incidentsEnCours,
            ],
            'paiements_en_attente' => $paiementsEnAttente,
            'revenus_labels'       => $revenusLabels,
            'revenus_values'       => $revenusValues,
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // HELPER — Graphique revenus mensuels
    // ══════════════════════════════════════════════════════════════
    private function buildMonthlyRevenue(
        PaiementRepository $paiementRepository,
        mixed              $proprietaire = null,
        int                $year = 0,
        int                $month = 0
    ): array {
        if ($year === 0) {
            $year = (int) date('Y');
        }

        $qb = $paiementRepository->createQueryBuilder('p')
            ->select('p.mois', 'p.montantVerse')
            ->where('p.statut = :s')
            ->setParameter('s', StatutPaiement::VALIDE)
            ->andWhere('p.mois BETWEEN :start AND :end')
            ->setParameter('start', new \DateTimeImmutable("$year-01-01"))
            ->setParameter('end', new \DateTimeImmutable("$year-12-31 23:59:59"));

        if ($proprietaire !== null) {
            $qb->join('p.contrat', 'c')
               ->join('c.bien', 'b')
               ->andWhere('b.proprietaire = :prop')
               ->setParameter('prop', $proprietaire);
        }

        $monthlySums = array_fill(1, 12, 0.0);
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $idx = (int) $row['mois']->format('n');
            if ($month > 0 && $idx !== $month) {
                continue;
            }
            $monthlySums[$idx] += (float) $row['montantVerse'];
        }

        $labels = [];
        $values = [];
        for ($m = 1; $m <= 12; $m++) {
            $labels[] = \DateTime::createFromFormat('!m', (string) $m)->format('M');
            $values[] = $monthlySums[$m];
        }

        return [$labels, $values];
    }
}