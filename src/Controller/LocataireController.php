<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LocataireController extends AbstractController
{
    #[Route('/locataire', name: 'app_locataire_home', methods: ['GET'])]
    #[IsGranted('ROLE_LOCATAIRE')]
    public function index(): Response
    {
        return $this->render('locataire/index.html.twig');
    }
}

