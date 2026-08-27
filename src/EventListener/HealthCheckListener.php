<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Healthcheck en court-circuit : la réponse est produite avant le routage,
 * avant la sécurité et avant tout autre écouteur de kernel.request. Elle ne
 * dépend d'aucun service de l'application, et rien de ce qui sera branché plus
 * tard sur cet événement ne pourra la faire échouer.
 */
#[AsEventListener(event: KernelEvents::REQUEST, priority: 4096)]
final class HealthCheckListener
{
    public const string PATH = '/cc-health';

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || self::PATH !== $event->getRequest()->getPathInfo()) {
            return;
        }

        // setResponse() appelle stopPropagation() : aucun autre écouteur de
        // kernel.request ne s'exécutera pour cette requête.
        $event->setResponse(new JsonResponse(['status' => 'ok']));
    }
}
