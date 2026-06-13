<?php

namespace App\Controller;

use App\Entity\Devis;
use App\Enum\RoleUtilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/devis')]
#[IsGranted('ROLE_USER')]
class DevisController extends AbstractController
{
    #[Route('/{id}/approuver', name: 'app_devis_approuver', methods: ['POST'])]
    public function approuver(Devis $devis, EntityManagerInterface $em, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('approuver' . $devis->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_index');
        }

        // Seul le propriétaire du bien ou un admin peut approuver
        $bien = $devis->getIncident()?->getBien();
        $isOwner = $user->getRole() === RoleUtilisateur::PROPRIETAIRE
            && $user->getProprietaire()
            && $bien?->getProprietaire() === $user->getProprietaire();

        if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('app_incident_index');
        }

        $devis->setStatut('APPROUVE');
        $em->flush();

        $this->addFlash('success', 'Devis approuvé avec succès.');
        return $this->redirectToRoute('app_incident_index');
    }

    #[Route('/{id}/rejeter', name: 'app_devis_rejeter', methods: ['POST'])]
    public function rejeter(Devis $devis, EntityManagerInterface $em, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('rejeter' . $devis->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_index');
        }

        $bien = $devis->getIncident()?->getBien();
        $isOwner = $user->getRole() === RoleUtilisateur::PROPRIETAIRE
            && $user->getProprietaire()
            && $bien?->getProprietaire() === $user->getProprietaire();

        if (!$isOwner && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Accès refusé.');
            return $this->redirectToRoute('app_incident_index');
        }

        $devis->setStatut('REJETE');
        $em->flush();

        $this->addFlash('success', 'Devis rejeté.');
        return $this->redirectToRoute('app_incident_index');
    }
}