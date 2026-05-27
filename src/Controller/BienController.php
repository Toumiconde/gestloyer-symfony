<?php

namespace App\Controller;

use App\Entity\Bien;
use App\Enum\RoleUtilisateur;
use App\Form\BienType;
use App\Repository\BienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/biens')]
#[IsGranted('ROLE_USER')]
class BienController extends AbstractController
{
    #[Route('/', name: 'app_bien_index', methods: ['GET'])]
    public function index(BienRepository $bienRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());

        $qb = $bienRepository->createQueryBuilder('b')
            ->leftJoin('b.proprietaire', 'p')
            ->orderBy('b.id', 'DESC');

        if (!$isAdmin && !in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE && $user->getProprietaire()) {
                $qb->where('b.proprietaire = :prop')->setParameter('prop', $user->getProprietaire());
            } elseif ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
                $qb->leftJoin('b.contrats', 'c')
                    ->where('c.locataire = :user')
                    ->setParameter('user', $user);
            }
        }

        $biens = $qb->getQuery()->getResult();

        return $this->render('bien/index.html.twig', [
            'biens' => $biens,
        ]);
    }

    #[Route('/nouveau', name: 'app_bien_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bien = new Bien();
        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($bien);
            $entityManager->flush();

            $this->addFlash('success', 'Bien immobilier ajouté avec succès.');
            return $this->redirectToRoute('app_bien_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bien/new.html.twig', [
            'bien' => $bien,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bien_show', methods: ['GET'])]
    public function show(Bien $bien): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $bien);

        return $this->render('bien/show.html.twig', [
            'bien' => $bien,
        ]);
    }

    #[Route('/{id}/editer', name: 'app_bien_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Bien $bien, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('EDIT', $bien);

        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Bien immobilier mis à jour.');
            return $this->redirectToRoute('app_bien_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bien/edit.html.twig', [
            'bien' => $bien,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bien_delete', methods: ['POST'])]
    public function delete(Request $request, Bien $bien, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $bien);

        if ($this->isCsrfTokenValid('delete'.$bien->getId(), $request->request->get('_token'))) {
            $entityManager->remove($bien);
            $entityManager->flush();
            $this->addFlash('success', 'Bien supprimé.');
        }

        return $this->redirectToRoute('app_bien_index', [], Response::HTTP_SEE_OTHER);
    }
}
