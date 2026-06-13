<?php

namespace App\Controller;

use App\Repository\BienRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/locataire')]
#[IsGranted('ROLE_LOCATAIRE')]
class TenantController extends AbstractController
{
    #[Route('/', name: 'tenant_dashboard')]
    public function dashboard(): Response
    {
        return $this->render('tenant/dashboard.html.twig');
    }

    #[Route('/search', name: 'tenant_search')]
    public function search(Request $request, BienRepository $bienRepository): Response
    {
        $criteria = [
            'city' => $request->query->get('city'),
            'minPrice' => $request->query->get('minPrice'),
            'maxPrice' => $request->query->get('maxPrice'),
            'rooms' => $request->query->get('rooms'),
        ];
        // Filter out empty values
        $criteria = array_filter($criteria, fn($v) => $v !== null && $v !== '');
        $results = [];
        if (!empty($criteria)) {
            $results = $bienRepository->search($criteria);
        }
        return $this->render('tenant/search.html.twig', [
            'criteria' => $criteria,
            'results' => $results,
        ]);
    }
}
