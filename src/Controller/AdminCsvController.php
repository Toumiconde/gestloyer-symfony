<?php
namespace App\Controller;

use App\Service\CsvExportImportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Handles CSV import and export for all entities via a unified UI.
 */
#[Route('/admin/csv', name: 'admin_csv', methods: ['GET', 'POST'])]
#[IsGranted('ROLE_ADMIN')]
class AdminCsvController extends AbstractController
{
    public function index(Request $request, CsvExportImportService $service, EntityManagerInterface $em): Response
    {
        // List of entities (class => label). Add new entities here if needed.
        $entities = [
            'App\\Entity\\ActivityLog'   => 'ActivityLog',
            'App\\Entity\\AgenceConfig'  => 'AgenceConfig',
            'App\\Entity\\Bien'          => 'Bien',
            'App\\Entity\\Contrat'       => 'Contrat',
            'App\\Entity\\Devis'         => 'Devis',
            'App\\Entity\\Incident'      => 'Incident',
            'App\\Entity\\Paiement'      => 'Paiement',
            'App\\Entity\\Proprietaire'  => 'Proprietaire',
            'App\\Entity\\Quittance'     => 'Quittance',
            'App\\Entity\\Reversement'   => 'Reversement',
            'App\\Entity\\User'          => 'User',
            'App\\Entity\\Versement'     => 'Versement',
        ];

        $action  = $request->query->get('action');   // 'import' | 'export'
        $entity  = $request->query->get('entity');   // fully‑qualified class name
        $message = null;

        if ($request->isMethod('POST') && $action === 'import' && $entity && $request->files->has('csv_file')) {
            $file = $request->files->get('csv_file');
            [$created, $updated] = $service->import($em, $entity, $file);
            $message = "$created records created, $updated records updated.";
        } elseif ($action === 'export' && $entity) {
            $csvContent = $service->export($em, $entity);
            $filename = strtolower((new \ReflectionClass($entity))->getShortName()) . '-export-' . date('Ymd-His') . '.csv';

            return new Response(
                $csvContent,
                200,
                [
                    'Content-Type'        => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                ]
            );
        }

        return $this->render('admin/csv.html.twig', [
            'entities' => $entities,
            'action'   => $action,
            'entity'   => $entity,
            'message'  => $message,
        ]);
    }
}
