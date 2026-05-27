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
use Symfony\Component\Routing\Annotation\Route;
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
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $qb = $incidentRepository->createQueryBuilder('i')
            ->leftJoin('i.bien', 'b')
            ->leftJoin('i.declarant', 'd')
            ->orderBy('i.dateDeclaration', 'DESC');

        // Locataires et propriétaires ne voient que leurs incidents
        if (!$isAdmin && !in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
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
            $qb->andWhere('i.statut = :statut')->setParameter('statut', StatutIncident::from($statut));
        }
        if ($priorite) {
            $qb->andWhere('i.priorite = :priorite')->setParameter('priorite', PrioriteIncident::from($priorite));
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
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('incident_new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_incident_new');
            }

            /** @var \App\Entity\User $user */
            $user = $this->getUser();

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

        $biens = $bienRepository->findAll();

        return $this->render('incident/new.html.twig', [
            'biens'    => $biens,
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

    #[Route('/{id}/changer-statut', name: 'app_incident_change_statut', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function changerStatut(Incident $incident, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('statut'.$incident->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_incident_show', ['id' => $incident->getId()]);
        }

        $nouveauStatut = StatutIncident::from($request->request->get('statut'));
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
