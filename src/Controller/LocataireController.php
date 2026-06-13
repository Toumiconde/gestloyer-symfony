<?php

namespace App\Controller;

use App\Repository\BienRepository;
use App\Enum\StatutBien;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/locataire')]
#[IsGranted('ROLE_LOCATAIRE')]
class LocataireController extends AbstractController
{
    #[Route('/recherche', name: 'app_locataire_search', methods: ['GET'])]
    public function search(Request $request, BienRepository $bienRepository): Response
    {
        $query = $request->query->get('q');
        
        $qb = $bienRepository->createQueryBuilder('b')
            ->where('b.statut = :statut')
            ->setParameter('statut', StatutBien::DISPONIBLE);

        if ($query) {
            $qb->andWhere('b.nom LIKE :query OR b.adresse LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        $biens = $qb->orderBy('b.id', 'DESC')->getQuery()->getResult();

        return $this->render('locataire/search.html.twig', [
            'biens' => $biens,
            'query' => $query,
        ]);
    }
}
