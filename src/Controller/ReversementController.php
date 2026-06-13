<?php

namespace App\Controller;

use App\Entity\Reversement;
use App\Enum\RoleUtilisateur;
use App\Repository\ReversementRepository;
use App\Service\ReversementPdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reversements')]
#[IsGranted('ROLE_USER')]
class ReversementController extends AbstractController
{
    #[Route('/', name: 'app_reversement_index', methods: ['GET'])]
    public function index(ReversementRepository $reversementRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $qb = $reversementRepository->createQueryBuilder('r')
            ->orderBy('r.mois', 'DESC');

        // Propriétaire ne voit que ses reversements
        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE && $user->getProprietaire()) {
            $qb->where('r.proprietaire = :prop')
               ->setParameter('prop', $user->getProprietaire());
        } elseif (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COMPTABLE')) {
            // Autres rôles non autorisés
            return $this->redirectToRoute('app_dashboard');
        }

        $reversements = $qb->getQuery()->getResult();

        return $this->render('reversement/index.html.twig', [
            'reversements' => $reversements,
        ]);
    }

    #[Route('/{id}/pdf', name: 'app_reversement_pdf', methods: ['GET'])]
    public function pdf(
        Reversement $reversement,
        ReversementPdfGenerator $pdfGenerator,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Sécurité : propriétaire ne peut voir que ses propres reversements
        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            if (!$user->getProprietaire() || $reversement->getProprietaire() !== $user->getProprietaire()) {
                throw $this->createAccessDeniedException('Accès refusé.');
            }
        } elseif (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_COMPTABLE')) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        // Si le PDF n'existe pas encore (ou le fichier a été supprimé), on le régénère
        $pdfPath = $reversement->getPdfPath();
        $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $pdfPath;

        if (!$pdfPath || !file_exists($fullPath)) {
            $pdfPath  = $pdfGenerator->generate($reversement);
            $fullPath = $this->getParameter('kernel.project_dir') . '/public' . $pdfPath;
            $reversement->setPdfPath($pdfPath);
            $em->flush();
        }

        // Renvoie le fichier en téléchargement
        return $this->file(
            $fullPath,
            'bordereau_reversement_' . $reversement->getId() . '.pdf'
        );
    }
}