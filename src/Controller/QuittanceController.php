<?php

namespace App\Controller;

use App\Entity\Quittance;
use App\Repository\AgenceConfigRepository;
use App\Service\PdfGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/quittance')]
class QuittanceController extends AbstractController
{
    #[Route('/{id}/telecharger', name: 'app_quittance_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')] // À affiner si on veut que seul le locataire ou l'admin puisse télécharger
    public function download(
        Quittance $quittance,
        PdfGeneratorService $pdfGenerator,
        AgenceConfigRepository $agenceConfigRepository
    ): Response {
        $paiement = $quittance->getPaiement();
        $contrat = $paiement->getContrat();
        $bien = $contrat->getBien();
        $locataire = $contrat->getLocataire();
        
        $agence = $agenceConfigRepository->findOneBy([]);

        $html = $this->renderView('pdf/quittance.html.twig', [
            'quittance' => $quittance,
            'paiement'  => $paiement,
            'bien'      => $bien,
            'locataire' => $locataire,
            'agence'    => $agence,
        ]);

        $pdfBinary = $pdfGenerator->generateBinaryPdf($html);

        return new Response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Quittance_'.$quittance->getNumero().'.pdf"'
        ]);
    }
}
