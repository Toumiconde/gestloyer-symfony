<?php

namespace App\Controller;

use App\Entity\User;
use App\Enum\StatutBien;
use App\Enum\StatutIncident;
use App\Enum\StatutPaiement;
use App\Repository\BienRepository;
use App\Repository\ContratRepository;
use App\Repository\IncidentRepository;
use App\Repository\PaiementRepository;
use App\Repository\UserRepository;
use App\Service\ActivityLogService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rapport')]
class ReportController extends AbstractController
{
    private const REPORT_TYPES = [
        'global',
        'biens',
        'contrats',
        'paiements',
        'incidents',
        'utilisateurs',
    ];

    #[Route('/global/csv/export', name: 'app_admin_report_global_export_csv', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function exportGlobalCsv(
        BienRepository $bienRepository,
        ContratRepository $contratRepository,
        PaiementRepository $paiementRepository,
        IncidentRepository $incidentRepository,
        UserRepository $userRepository,
        Request $request,
        ActivityLogService $activityLogService
    ): Response {
        $reportType = (string) $request->query->get('type', 'global');
        if (!in_array($reportType, self::REPORT_TYPES, true)) {
            $reportType = 'global';
        }

        $rows = $this->buildRowsByType(
            $reportType,
            $bienRepository,
            $contratRepository,
            $paiementRepository,
            $incidentRepository,
            $userRepository
        );

        if (count($rows) === 0) {
            $this->addFlash('error', 'Aucune donnée à exporter pour ce rapport.');
            return $this->redirectToRoute('app_dashboard');
        }

        $handle = fopen('php://temp', 'r+');
        $delimiter = ';';
        fputcsv($handle, array_keys($rows[0]), $delimiter);
        foreach ($rows as $row) {
            fputcsv($handle, array_values($row), $delimiter);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        $filename = 'gestloyer-rapport-' . $reportType . '-' . (new \DateTimeImmutable())->format('Ymd-His') . '.csv';

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $actor = $this->getUser();
        $activityLogService->log(
            action: 'CSV_EXPORT',
            actor: $actor instanceof User ? $actor : null,
            details: 'Export CSV type=' . $reportType
        );

        return $response;
    }

    #[Route('/global/csv/import', name: 'app_admin_report_global_import_csv', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function importGlobalCsv(Request $request, ActivityLogService $activityLogService): Response
    {
        if (!$this->isCsrfTokenValid('admin_report_global_import_csv', (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_dashboard');
        }

        $reportType = (string) $request->request->get('reportType', 'global');
        if (!in_array($reportType, self::REPORT_TYPES, true)) {
            $reportType = 'global';
        }

        $uploadedFile = $request->files->get('csvFile');
        if (!$uploadedFile) {
            $this->addFlash('error', 'Veuillez choisir un fichier CSV.');
            return $this->redirectToRoute('app_dashboard');
        }

        if (!$uploadedFile->isValid()) {
            $this->addFlash('error', 'Le fichier CSV est invalide.');
            return $this->redirectToRoute('app_dashboard');
        }

        $path = $uploadedFile->getPathname();
        $content = file_get_contents($path);
        if ($content === false) {
            $this->addFlash('error', 'Impossible de lire le fichier CSV.');
            return $this->redirectToRoute('app_dashboard');
        }

        $lines = preg_split("/\r\n|\n|\r/", trim((string) $content));
        if (!$lines || count($lines) < 2) {
            $this->addFlash('error', 'Le CSV ne contient pas assez de lignes.');
            return $this->redirectToRoute('app_dashboard');
        }

        // CSV attendu généré par exportGlobalCsv (delimiter ;) mais on essaie aussi la virgule.
        $headerLine = $lines[0];
        $expectedHeaders = $this->expectedHeadersForType($reportType);
        if (count($expectedHeaders) === 0) {
            $this->addFlash('error', 'Type de rapport non supporté.');
            return $this->redirectToRoute('app_dashboard');
        }

        $parseHeader = static function (string $line, string $delimiter): array {
            return array_map('trim', str_getcsv($line, $delimiter));
        };

        $delimiter = ';';
        $header = $parseHeader($headerLine, $delimiter);
        if (array_slice($header, 0, count($expectedHeaders)) !== $expectedHeaders) {
            $delimiter = ',';
            $header = $parseHeader($headerLine, $delimiter);
        }

        if (array_slice($header, 0, count($expectedHeaders)) !== $expectedHeaders) {
            $this->addFlash('error', 'Format CSV inattendu pour ce type de rapport.');
            return $this->redirectToRoute('app_dashboard');
        }

        $dataLine = $lines[1];
        $values = $parseHeader($dataLine, $delimiter);

        // Map header => value
        $map = [];
        foreach ($expectedHeaders as $i => $key) {
            $map[$key] = $values[$i] ?? null;
        }

        $this->addFlash(
            'success',
            sprintf(
                'Rapport "%s" importé avec succès (%d colonnes détectées).',
                $reportType,
                count($map)
            )
        );

        $actor = $this->getUser();
        $activityLogService->log(
            action: 'CSV_IMPORT',
            actor: $actor instanceof User ? $actor : null,
            details: 'Import CSV type=' . $reportType . ', fichier=' . $uploadedFile->getClientOriginalName()
        );

        return $this->redirectToRoute('app_dashboard');
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildRowsByType(
        string $reportType,
        BienRepository $bienRepository,
        ContratRepository $contratRepository,
        PaiementRepository $paiementRepository,
        IncidentRepository $incidentRepository,
        UserRepository $userRepository
    ): array {
        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($reportType === 'global') {
            $totalBiens = (int) $bienRepository->count([]);
            $biensOccupes = (int) $bienRepository->createQueryBuilder('b')
                ->select('COUNT(b.id)')
                ->where('b.statut = :statut')
                ->setParameter('statut', StatutBien::OCCUPE)
                ->getQuery()->getSingleScalarResult();
            $tauxOccupation = $totalBiens > 0 ? round(($biensOccupes / $totalBiens) * 100, 2) : 0.0;
            $totalContrats = (int) $contratRepository->count([]);
            $totalPaiements = (int) $paiementRepository->count([]);
            $revenusGlobaux = $paiementRepository->createQueryBuilder('p')
                ->select('SUM(p.montantVerse)')
                ->where('p.statut = :statut')
                ->setParameter('statut', StatutPaiement::VALIDE)
                ->getQuery()->getSingleScalarResult();
            $impayes = $paiementRepository->createQueryBuilder('p')
                ->select('SUM(p.montantDu - p.montantVerse)')
                ->where('p.statut IN (:statuts)')
                ->setParameter('statuts', [StatutPaiement::EN_ATTENTE, StatutPaiement::PARTIEL])
                ->getQuery()->getSingleScalarResult();
            $incidentsOuverts = (int) $incidentRepository->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->where('i.statut NOT IN (:statuts)')
                ->setParameter('statuts', [StatutIncident::RESOLU, StatutIncident::CLOTURE])
                ->getQuery()->getSingleScalarResult();

            return [[
                'date_export' => $now,
                'total_biens' => (string) $totalBiens,
                'biens_occupes' => (string) $biensOccupes,
                'taux_occupation_percent' => (string) $tauxOccupation,
                'total_contrats' => (string) $totalContrats,
                'total_paiements' => (string) $totalPaiements,
                'revenus_globaux_gnf' => (string) ($revenusGlobaux !== null ? (float) $revenusGlobaux : 0.0),
                'impayes_gnf' => (string) ($impayes !== null ? (float) $impayes : 0.0),
                'incidents_ouverts' => (string) $incidentsOuverts,
            ]];
        }

        if ($reportType === 'biens') {
            $rows = [];
            foreach ($bienRepository->findBy([], ['id' => 'DESC']) as $bien) {
                $rows[] = [
                    'date_export' => $now,
                    'id' => (string) $bien->getId(),
                    'nom' => (string) ($bien->getNom() ?? ''),
                    'adresse' => (string) ($bien->getAdresse() ?? ''),
                    'type' => (string) ($bien->getType()?->value ?? ''),
                    'statut' => (string) ($bien->getStatut()?->value ?? ''),
                    'proprietaire' => (string) ($bien->getProprietaire()?->getNom() ?? ''),
                ];
            }
            return $rows;
        }

        if ($reportType === 'contrats') {
            $rows = [];
            foreach ($contratRepository->findBy([], ['id' => 'DESC']) as $contrat) {
                $rows[] = [
                    'date_export' => $now,
                    'id' => (string) $contrat->getId(),
                    'numero' => (string) ($contrat->getNumero() ?? ''),
                    'locataire_email' => (string) ($contrat->getLocataire()?->getEmail() ?? ''),
                    'bien_nom' => (string) ($contrat->getBien()?->getNom() ?? ''),
                    'date_debut' => (string) ($contrat->getDateDebut()?->format('Y-m-d') ?? ''),
                    'date_fin' => (string) ($contrat->getDateFin()?->format('Y-m-d') ?? ''),
                    'loyer_mensuel_gnf' => (string) ($contrat->getLoyerMensuel() ?? '0'),
                    'statut' => (string) ($contrat->getStatut()?->value ?? ''),
                ];
            }
            return $rows;
        }

        if ($reportType === 'paiements') {
            $rows = [];
            foreach ($paiementRepository->findBy([], ['id' => 'DESC']) as $paiement) {
                $rows[] = [
                    'date_export' => $now,
                    'id' => (string) $paiement->getId(),
                    'contrat_numero' => (string) ($paiement->getContrat()?->getNumero() ?? ''),
                    'mois' => (string) ($paiement->getMois()?->format('Y-m') ?? ''),
                    'montant_du_gnf' => (string) ($paiement->getMontantDu() ?? '0'),
                    'montant_verse_gnf' => (string) $paiement->getMontantVerse(),
                    'solde_gnf' => (string) $paiement->getSolde(),
                    'statut' => (string) ($paiement->getStatut()?->value ?? ''),
                ];
            }
            return $rows;
        }

        if ($reportType === 'incidents') {
            $rows = [];
            foreach ($incidentRepository->findBy([], ['id' => 'DESC']) as $incident) {
                $rows[] = [
                    'date_export' => $now,
                    'id' => (string) $incident->getId(),
                    'titre' => (string) ($incident->getTitre() ?? ''),
                    'bien_nom' => (string) ($incident->getBien()?->getNom() ?? ''),
                    'declarant_email' => (string) ($incident->getDeclarant()?->getEmail() ?? ''),
                    'priorite' => (string) ($incident->getPriorite()?->value ?? ''),
                    'statut' => (string) ($incident->getStatut()?->value ?? ''),
                    'date_declaration' => (string) ($incident->getDateDeclaration()?->format('Y-m-d H:i:s') ?? ''),
                ];
            }
            return $rows;
        }

        if ($reportType === 'utilisateurs') {
            $rows = [];
            foreach ($userRepository->findBy([], ['id' => 'DESC']) as $user) {
                $rows[] = [
                    'date_export' => $now,
                    'id' => (string) $user->getId(),
                    'email' => (string) ($user->getEmail() ?? ''),
                    'role' => (string) $user->getRole()->value,
                    'actif' => $user->isIsActive() ? '1' : '0',
                    'created_at' => (string) ($user->getCreatedAt()?->format('Y-m-d H:i:s') ?? ''),
                ];
            }
            return $rows;
        }

        return [];
    }

    /**
     * @return string[]
     */
    private function expectedHeadersForType(string $reportType): array
    {
        return match ($reportType) {
            'global' => [
                'date_export',
                'total_biens',
                'biens_occupes',
                'taux_occupation_percent',
                'total_contrats',
                'total_paiements',
                'revenus_globaux_gnf',
                'impayes_gnf',
                'incidents_ouverts',
            ],
            'biens' => ['date_export', 'id', 'nom', 'adresse', 'type', 'statut', 'proprietaire'],
            'contrats' => ['date_export', 'id', 'numero', 'locataire_email', 'bien_nom', 'date_debut', 'date_fin', 'loyer_mensuel_gnf', 'statut'],
            'paiements' => ['date_export', 'id', 'contrat_numero', 'mois', 'montant_du_gnf', 'montant_verse_gnf', 'solde_gnf', 'statut'],
            'incidents' => ['date_export', 'id', 'titre', 'bien_nom', 'declarant_email', 'priorite', 'statut', 'date_declaration'],
            'utilisateurs' => ['date_export', 'id', 'email', 'role', 'actif', 'created_at'],
            default => [],
        };
    }
}

