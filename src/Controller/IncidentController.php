<?php

namespace App\Controller;

use App\Entity\Incident;
use App\Enum\StatutIncident;
use App\Enum\PrioriteIncident;
use App\Enum\RoleUtilisateur;
use App\Repository\IncidentRepository;
use App\Repository\BienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/incidents')]
#[IsGranted('ROLE_USER')]
class IncidentController extends AbstractController
{
    #[Route('/', name: 'app_incident_index', methods: ['GET'])]
    public function index(IncidentRepository $incidentRepository, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $isAdmin = \in_array('ROLE_ADMIN', $user->getRoles());

        $qb = $incidentRepository->createQueryBuilder('i')
            ->leftJoin('i.bien', 'b')
            ->leftJoin('i.declarant', 'd')
            ->orderBy('i.dateDeclaration', 'DESC');

        // Locataires et propriétaires ne voient que leurs incidents
        if (!$isAdmin && !\in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
                $qb->where('i.declarant = :user')->setParameter('user', $user);
            } elseif ($user->getRole() === RoleUtilisateur::PROPRIETAIRE && $user->getProprietaire()) {
                $qb->where('b.proprietaire = :prop')->setParameter('prop', $user->getProprietaire());
            }
        }

        // Filtres optionnels
        $statut   = $request->query->get('statut');
        $priorite = $request->query->get('priorite');

        if ($statut) {
            $statutEnum = StatutIncident::tryFrom($statut);
            if ($statutEnum !== null) {
                $qb->andWhere('i.statut = :statut')->setParameter('statut', $statutEnum);
            }
        }
        if ($priorite) {
            $prioriteEnum = PrioriteIncident::tryFrom($priorite);
            if ($prioriteEnum !== null) {
                $qb->andWhere('i.priorite = :priorite')->setParameter('priorite', $prioriteEnum);
            }
        }

        $incidents = $qb->getQuery()->getResult();

        return $this->render('incident/index.html.twig', [
            'incidents'       => $incidents,
            'statuts'         => StatutIncident::cases(),
            'priorites'       => PrioriteIncident::cases(),
            'current_statut'  => $statut,
            'current_priorite' => $priorite,
            'is_admin'        => $isAdmin,
        ]);
    }

    #[Route('/nouveau', name: 'app_incident_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        BienRepository $bienRepository
    ): Response {
        // Prevent proprietors from creating incidents
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            $this->addFlash('error', 'Les propriétaires ne peuvent pas déclarer d\'incident.');
            return $this->redirectToRoute('app_dashboard');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('incident_new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_incident_new');
            }

            $titre = trim((string) $request->request->get('titre'));
            $description = trim((string) $request->request->get('description'));
            $prioriteStr = $request->request->get('priorite');
            $bien = $bienRepository->find($request->request->get('bien_id'));

            if ($titre === '' || $description === '' || !$prioriteStr || !$bien) {
                $this->addFlash('error', 'Tous les champs obligatoires doivent être renseignés.');
                return $this->redirectToRoute('app_incident_new');
            }

            try {
                $priorite = PrioriteIncident::from($prioriteStr);
            } catch (\ValueError) {
                $this->addFlash('error', 'Priorité invalide.');
                return $this->redirectToRoute('app_incident_new');
            }

            $incident = new Incident();
            $incident->setTitre($titre);
            $incident->setDescription($description);
            $incident->setPriorite($priorite);
            $incident->setBien($bien);
            $incident->setDeclarant($user);
            $incident->setStatut(StatutIncident::DECLARE);

            $em->persist($incident);
            $em->flush();

            $this->addFlash('success', 'Incident déclaré avec succès. Numéro : #'.$incident->getId());
            return $this->redirectToRoute('app_incident_index');
        }

        if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
            $biens = $bienRepository->createQueryBuilder('b')
                ->join('b.contrats', 'c')
                ->where('c.locataire = :user')
                ->andWhere('c.statut = :statut')
                ->setParameter('user', $user)
                ->setParameter('statut', \App\Enum\StatutContrat::ACTIF)
                ->getQuery()
                ->getResult();
        } else {
            $biens = $bienRepository->findAll();
        }

        return $this->render('incident/new.html.twig', [
            'biens' => $biens,
            'priorites' => PrioriteIncident::cases(),
        ]);
    }
    

    #[Route('/{id}', name: 'app_incident_show', methods: ['GET'])]
    public function show(Incident $incident): Response
    {
        return $this->render('incident/show.html.twig', [
            'incident' => $incident,
            'statuts'  => StatutIncident::cases(),
        ]);
    }

    #[Route('/{id}/ajouter-devis', name: 'app_incident_add_devis', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function ajouterDevis(
        Incident $incident,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('devis'.$incident->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        $montant     = $request->request->get('montant');
        $prestataire = trim((string) $request->request->get('prestataire'));
        $description = trim((string) $request->request->get('description'));

        if (!$montant || !$prestataire) {
            $this->addFlash('error', 'Le montant et le prestataire sont obligatoires.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        $devis = new \App\Entity\Devis();
        $devis->setMontant((string) $montant);
        $devis->setPrestataire($prestataire);
        $devis->setDescription($description ?: null);
        $devis->setStatut('SOUMIS');
        $devis->setIncident($incident);

        $incident->setStatut(StatutIncident::DEVIS_SOUMIS);

        $em->persist($devis);
        $em->flush();

        $this->addFlash('success', 'Devis de '.$montant.' GNF soumis par '.$prestataire.' et incident mis en statut DEVIS_SOUMIS.');
        return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
    }

    #[Route('/{id}/devis/{devisId}/approuver', name: 'app_incident_approve_devis', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function approuverDevis(
        Incident $incident,
        int $devisId,
        Request $request,
        EntityManagerInterface $em,
        \App\Repository\DevisRepository $devisRepository
    ): Response {
        if (!$this->isCsrfTokenValid('approve_devis'.$devisId, $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        $devis = $devisRepository->find($devisId);
        if (!$devis || $devis->getIncident() !== $incident) {
            $this->addFlash('error', 'Devis introuvable.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        // Rejeter tous les autres devis de cet incident
        foreach ($incident->getDevis() as $d) {
            $d->setStatut($d === $devis ? 'APPROUVE' : 'REJETE');
        }

        $incident->setStatut(StatutIncident::APPROUVE);
        $em->flush();

        $this->addFlash('success', 'Devis approuvé. Intervention planifiée.');
        return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
    }

    #[Route('/{id}/cloturer', name: 'app_incident_cloturer', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function cloturerIncident(
        Incident $incident,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('cloturer'.$incident->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        $incident->setStatut(StatutIncident::CLOTURE);
        $em->flush();

        $this->addFlash('success', 'Incident #'.$incident->getId().' clôturé avec succès.');
        return $this->redirectToRoute('app_incident_index');
    }

    #[Route('/{id}/changer-statut', name: 'app_incident_change_statut', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function changerStatut(Incident $incident, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('statut'.$incident->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        $nouveauStatut = StatutIncident::tryFrom($request->request->get('statut'));
        if ($nouveauStatut === null) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }
        $incident->setStatut($nouveauStatut);
        $em->flush();

        $this->addFlash('success', 'Statut de l\'incident mis à jour.');
        return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_incident_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Incident $incident, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$incident->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_index');
        }

        $titre = $incident->getTitre();
        $em->remove($incident);
        $em->flush();

        $this->addFlash('success', "Incident \"$titre\" supprimé.");
        return $this->redirectToRoute('app_incident_index');
    }
}
