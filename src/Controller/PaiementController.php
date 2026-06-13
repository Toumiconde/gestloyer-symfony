<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Enum\StatutPaiement;
use App\Enum\RoleUtilisateur;
use App\Repository\PaiementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/paiements')]
#[IsGranted('ROLE_USER')]
class PaiementController extends AbstractController
{
    #[Route('/', name: 'app_paiement_index', methods: ['GET'])]
    public function index(PaiementRepository $paiementRepository, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $isAdmin = \in_array('ROLE_ADMIN', $user->getRoles());

        $qb = $paiementRepository->createQueryBuilder('p')
            ->leftJoin('p.contrat', 'c')
            ->leftJoin('c.bien', 'b')
            ->leftJoin('c.locataire', 'l')
            ->orderBy('p.mois', 'DESC');

        if (!$isAdmin) {
            if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
                $qb->where('c.locataire = :user')->setParameter('user', $user);
            } elseif ($user->getRole() === RoleUtilisateur::PROPRIETAIRE && $user->getProprietaire()) {
                $qb->where('b.proprietaire = :prop')->setParameter('prop', $user->getProprietaire());
            }
        }

        $statut = $request->query->get('statut');
        if ($statut) {
            $statutEnum = StatutPaiement::tryFrom($statut);
            if ($statutEnum !== null) {
                $qb->andWhere('p.statut = :statut')->setParameter('statut', $statutEnum);
            }
        }

        $paiements = $qb->getQuery()->getResult();

        // Calcul totaux
        $totalDu    = array_sum(array_map(fn($p) => (float) $p->getMontantDu(), $paiements));
        $totalVerse = array_sum(array_map(fn($p) => (float) $p->getMontantVerse(), $paiements));

        return $this->render('paiement/index.html.twig', [
            'paiements'      => $paiements,
            'statuts'        => StatutPaiement::cases(),
            'current_statut' => $statut,
            'total_du'       => $totalDu,
            'total_verse'    => $totalVerse,
            'solde_total'    => $totalDu - $totalVerse,
            'is_admin'       => $isAdmin,
        ]);
    }

    #[Route('/{id}', name: 'app_paiement_show', methods: ['GET'])]
    public function show(Paiement $paiement): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $paiement);

        return $this->render('paiement/show.html.twig', [
            'paiement' => $paiement,
        ]);
    }

    #[Route('/{id}/valider', name: 'app_paiement_valider', methods: ['POST'])]
    #[IsGranted('ROLE_COMPTABLE')]
    public function valider(Paiement $paiement, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('valider'.$paiement->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_paiement_show', ['id' => $paiement->getId()]);
        }

        $paiement->setStatut(StatutPaiement::VALIDE);

        // Générer une quittance s'il n'y en a pas encore
        if (!$paiement->getQuittance()) {
            $quittance = new \App\Entity\Quittance();
            // Génération d'un numéro unique (ex: Q-202310-001)
            $mois = $paiement->getMois();
            $numero = 'Q-' . ($mois !== null ? $mois->format('Ym') : date('Ym')) . '-' . str_pad((string)$paiement->getId(), 4, '0', STR_PAD_LEFT);
            $quittance->setNumero($numero);
            $quittance->setPaiement($paiement);
            $em->persist($quittance);
        }

        $em->flush();

        $this->addFlash('success', 'Paiement validé avec succès. La quittance a été générée.');
        return $this->redirectToRoute('app_paiement_index');
    }

    #[Route('/{id}/annuler', name: 'app_paiement_annuler', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function annuler(Paiement $paiement, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('annuler'.$paiement->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_paiement_index');
        }

        $paiement->setStatut(StatutPaiement::ANNULE);
        $em->flush();

        $this->addFlash('success', 'Paiement annulé et remis en attente.');
        return $this->redirectToRoute('app_paiement_index');
    }

    #[Route('/{id}/supprimer', name: 'app_paiement_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Paiement $paiement, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$paiement->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_paiement_index');
        }

        $em->remove($paiement);
        $em->flush();

        $this->addFlash('success', 'Paiement supprimé définitivement.');
        return $this->redirectToRoute('app_paiement_index');
    }
}
