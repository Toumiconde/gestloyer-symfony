<?php

namespace App\Controller;

use App\Entity\Bien;
use App\Entity\BienPhoto;
use App\Enum\RoleUtilisateur;
use App\Form\BienType;
use App\Repository\BienPhotoRepository;
use App\Repository\BienRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/biens')]
#[IsGranted('ROLE_USER')]
class BienController extends AbstractController
{
    #[Route('/', name: 'app_bien_index', methods: ['GET'])]
    public function index(BienRepository $bienRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user    = $this->getUser();
        $isAdmin = \in_array('ROLE_ADMIN', $user->getRoles());

        $qb = $bienRepository->createQueryBuilder('b')
            ->leftJoin('b.proprietaire', 'p')
            ->orderBy('b.id', 'DESC');

        if (!$isAdmin && !\in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE && $user->getProprietaire()) {
                $qb->where('b.proprietaire = :prop')->setParameter('prop', $user->getProprietaire());
            } elseif ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
                $qb->leftJoin('b.contrats', 'c')
                    ->where('c.locataire = :user')
                    ->setParameter('user', $user);
            }
        }

        return $this->render('bien/index.html.twig', [
            'biens' => $qb->getQuery()->getResult(),
        ]);
    }

    #[Route('/nouveau', name: 'app_bien_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $bien = new Bien();
        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($bien);
            $em->flush();

            $this->handlePhotoUploads($request, $bien, $em, $slugger);

            $this->addFlash('success', 'Bien immobilier ajouté avec succès.');
            return $this->redirectToRoute('app_bien_show', ['id' => $bien->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bien/new.html.twig', [
            'bien'       => $bien,
            'form'       => $form,
            'categories' => BienPhoto::CATEGORIES,
        ]);
    }

    #[Route('/{id}', name: 'app_bien_show', methods: ['GET'])]
    public function show(Bien $bien): Response
    {
        $this->denyAccessUnlessGranted('VIEW', $bien);

        return $this->render('bien/show.html.twig', [
            'bien'       => $bien,
            'categories' => BienPhoto::CATEGORIES,
        ]);
    }

    #[Route('/{id}/editer', name: 'app_bien_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Bien $bien,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('EDIT', $bien);

        $form = $this->createForm(BienType::class, $bien);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhotoUploads($request, $bien, $em, $slugger);
            $em->flush();

            $this->addFlash('success', 'Bien immobilier mis à jour.');
            return $this->redirectToRoute('app_bien_show', ['id' => $bien->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bien/edit.html.twig', [
            'bien'       => $bien,
            'form'       => $form,
            'categories' => BienPhoto::CATEGORIES,
        ]);
    }

    #[Route('/{id}/photo/{photoId}/supprimer', name: 'app_bien_photo_delete', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function deletePhoto(
        Bien $bien,
        int $photoId,
        BienPhotoRepository $photoRepo,
        EntityManagerInterface $em,
        Request $request
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('delete_photo_' . $photoId, $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Token invalide'], 403);
        }

        $photo = $photoRepo->find($photoId);
        if ($photo && $photo->getBien() === $bien) {
            $this->removePhotoFile($photo);
            $em->remove($photo);
            $em->flush();
            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['error' => 'Photo introuvable'], 404);
    }

    #[Route('/{id}/statut', name: 'app_bien_change_statut', methods: ['POST'])]
    #[IsGranted('ROLE_GESTIONNAIRE')]
    public function changerStatut(Request $request, Bien $bien, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('statut' . $bien->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_bien_index');
        }

        $statut = \App\Enum\StatutBien::tryFrom((string) $request->request->get('statut'));
        if ($statut === null) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_bien_index');
        }

        $bien->setStatut($statut);
        $em->flush();

        $this->addFlash('success', 'Statut mis à jour : ' . $statut->value);
        return $this->redirectToRoute('app_bien_show', ['id' => $bien->getId()]);
    }

    #[Route('/{id}', name: 'app_bien_delete', methods: ['POST'])]
    public function delete(Request $request, Bien $bien, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('DELETE', $bien);

        if ($this->isCsrfTokenValid('delete' . $bien->getId(), $request->request->get('_token'))) {
            foreach ($bien->getPhotos() as $photo) {
                $this->removePhotoFile($photo);
            }
            $em->remove($bien);
            $em->flush();
            $this->addFlash('success', 'Bien supprimé.');
        }

        return $this->redirectToRoute('app_bien_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handlePhotoUploads(
        Request $request,
        Bien $bien,
        EntityManagerInterface $em,
        SluggerInterface $slugger
    ): void {
        $uploadDir  = $this->getParameter('kernel.project_dir') . '/public/uploads/biens/';
        $files      = $request->files->get('photos', []);
        $categories = $request->request->all('photo_categories');

        if (!is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $index => $file) {
            if (!$file || !$file->isValid()) {
                continue;
            }

            $ext      = $file->guessExtension() ?? 'jpg';
            $safe     = $slugger->slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
            $filename = $safe . '-' . uniqid() . '.' . $ext;

            $file->move($uploadDir, $filename);

            $photo = new BienPhoto();
            $photo->setFilename($filename);
            $photo->setCategorie($categories[$index] ?? 'autre');
            $photo->setBien($bien);
            $em->persist($photo);
        }

        $em->flush();
    }

    private function removePhotoFile(BienPhoto $photo): void
    {
        $path = $this->getParameter('kernel.project_dir') . '/public/uploads/biens/' . $photo->getFilename();
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
