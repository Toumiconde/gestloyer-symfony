<?php

namespace App\Controller;

use App\Entity\Contrat;
use App\Entity\Bien;
use App\Entity\User;
use App\Enum\StatutContrat;
use App\Enum\RoleUtilisateur;
use App\Repository\ContratRepository;
use App\Repository\BienRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contrats')]
#[IsGranted('ROLE_USER')]
class ContratController extends AbstractController
{
    #[Route('/', name: 'app_contrat_index', methods: ['GET'])]
    public function index(ContratRepository $contratRepository, Request $request): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $qb = $contratRepository->createQueryBuilder('c')
            ->leftJoin('c.bien', 'b')
            ->leftJoin('c.locataire', 'l')
            ->orderBy('c.dateDebut', 'DESC');

        // Filtres pour les non-admin
        if (!$isAdmin) {
            if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
                $qb->where('c.locataire = :user')->setParameter('user', $user);
            } elseif ($user->getRole() === RoleUtilisateur::PROPRIETAIRE && $user->getProprietaire()) {
                $qb->where('b.proprietaire = :prop')->setParameter('prop', $user->getProprietaire());
            }
        }

        // Filtre par statut
        $statut = $request->query->get('statut');
        if ($statut) {
            $qb->andWhere('c.statut = :statut')->setParameter('statut', StatutContrat::from($statut));
        }

        $contrats = $qb->getQuery()->getResult();

        return $this->render('contrat/index.html.twig', [
            'contrats' => $contrats,
            'statuts'  => StatutContrat::cases(),
            'current_statut' => $statut,
            'is_admin' => $isAdmin,
        ]);
    }

    #[Route('/nouveau', name: 'app_contrat_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        BienRepository $bienRepository,
        UserRepository $userRepository
    ): Response {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('contrat_new', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_contrat_new');
            }

            $bien = $bienRepository->find($request->request->get('bien_id'));
            $locataire = $userRepository->find($request->request->get('locataire_id'));
            $dateDebutStr = $request->request->get('dateDebut');
            $loyerMensuel = $request->request->get('loyerMensuel');
            $caution = $request->request->get('caution');

            if (!$bien || !$locataire || !$dateDebutStr || $loyerMensuel === null || $caution === null) {
                $this->addFlash('error', 'Tous les champs obligatoires doivent être renseignés.');
                return $this->redirectToRoute('app_contrat_new');
            }

            if (!is_numeric($loyerMensuel) || (float) $loyerMensuel < 0) {
                $this->addFlash('error', 'Le loyer mensuel doit être un nombre positif.');
                return $this->redirectToRoute('app_contrat_new');
            }

            if (!is_numeric($caution) || (float) $caution < 0) {
                $this->addFlash('error', 'La caution doit être un nombre positif.');
                return $this->redirectToRoute('app_contrat_new');
            }

            try {
                $dateDebut = new \DateTimeImmutable($dateDebutStr);
            } catch (\Exception) {
                $this->addFlash('error', 'Date de début invalide.');
                return $this->redirectToRoute('app_contrat_new');
            }

            $contrat = new Contrat();
            $contrat->setNumero('CONT-' . strtoupper(uniqid()));
            $contrat->setBien($bien);
            $contrat->setLocataire($locataire);
            $contrat->setDateDebut($dateDebut);

            $dateFin = $request->request->get('dateFin');
            if ($dateFin) {
                try {
                    $contrat->setDateFin(new \DateTimeImmutable($dateFin));
                } catch (\Exception) {
                    $this->addFlash('error', 'Date de fin invalide.');
                    return $this->redirectToRoute('app_contrat_new');
                }
            }

            $contrat->setLoyerMensuel($loyerMensuel);
            $contrat->setCaution($caution);
            $contrat->setStatut(StatutContrat::ACTIF);

            $em->persist($contrat);
            $em->flush();

            $this->addFlash('success', 'Contrat '.$contrat->getNumero().' créé avec succès.');
            return $this->redirectToRoute('app_contrat_index');
        }

        $biens     = $bienRepository->findAll();
        $locataires = $userRepository->findBy(['role' => RoleUtilisateur::LOCATAIRE]);

        return $this->render('contrat/new.html.twig', [
            'biens'     => $biens,
            'locataires' => $locataires,
        ]);
    }

    #[Route('/{id}', name: 'app_contrat_show', methods: ['GET'])]
    public function show(Contrat $contrat): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $contrat);

        return $this->render('contrat/show.html.twig', [
            'contrat' => $contrat,
        ]);
    }

    #[Route('/{id}/resilier', name: 'app_contrat_resilier', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function resilier(Contrat $contrat, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('resilier'.$contrat->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_contrat_show', ['id' => $contrat->getId()]);
        }

        $contrat->setStatut(StatutContrat::RESILIE);
        if (!$contrat->getDateFin()) {
            $contrat->setDateFin(new \DateTimeImmutable());
        }
        $em->flush();

        $this->addFlash('success', 'Contrat résilié avec succès.');
        return $this->redirectToRoute('app_contrat_index');
    }

    #[Route('/{id}/supprimer', name: 'app_contrat_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Contrat $contrat, EntityManagerInterface $em, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete'.$contrat->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_contrat_index');
        }

        $numero = $contrat->getNumero();
        $em->remove($contrat);
        $em->flush();

        $this->addFlash('success', "Contrat $numero supprimé définitivement.");
        return $this->redirectToRoute('app_contrat_index');
    }
}
