<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    #[Route('/change-locale/{locale}', name: 'app_change_locale', requirements: ['locale' => 'fr|en'])]
    public function changeLocale(string $locale, Request $request): Response
    {
        // Sauvegarder la langue dans la session
        $request->getSession()->set('_locale', $locale);

        // Rediriger vers la page précédente ou vers le dashboard
        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('app_dashboard'));
    }
}
