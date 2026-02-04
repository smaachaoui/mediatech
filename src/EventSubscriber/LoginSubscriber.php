<?php

namespace App\EventSubscriber;

use App\Service\LoginAttemptService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * J'ecoute les evenements de connexion pour enregistrer les tentatives
 * et proteger contre les attaques par force brute.
 */
final class LoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoginAttemptService $loginAttemptService,
        private readonly RequestStack $requestStack
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LoginFailureEvent::class => 'onLoginFailure',
        ];
    }

    /**
     * J'enregistre une connexion reussie.
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $email = $event->getPassport()->getUser()->getUserIdentifier();
        $ipAddress = $request->getClientIp() ?? 'unknown';

        $this->loginAttemptService->recordSuccessfulAttempt($email, $ipAddress);
    }

    /**
     * J'enregistre une connexion echouee.
     */
    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        /*
         * Je recupere l'email depuis la requete car le passport peut ne pas etre disponible
         * en cas d'echec d'authentification.
         */
        $email = $request->request->get('_username', '') ?? '';
        $ipAddress = $request->getClientIp() ?? 'unknown';

        if ($email !== '') {
            $this->loginAttemptService->recordFailedAttempt($email, $ipAddress);
        }
    }
}