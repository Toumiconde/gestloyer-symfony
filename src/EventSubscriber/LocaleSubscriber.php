<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LocaleSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $defaultLocale = 'fr') {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        // Lire la locale depuis la session (définie par LocaleController)
        $locale = $request->getSession()->get('_locale', $this->defaultLocale);
        $request->setLocale($locale);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité 20 pour s'exécuter APRÈS que la session est initialisée (16)
            KernelEvents::REQUEST => [['onKernelRequest', 20]],
        ];
    }
}
