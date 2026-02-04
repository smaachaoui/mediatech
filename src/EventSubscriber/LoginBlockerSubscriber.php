<?php

namespace App\EventSubscriber;

use App\Service\LoginAttemptService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

/**
 * Je verifie avant chaque tentative de connexion si l'utilisateur
 * ou l'IP n'est pas bloque suite a trop de tentatives echouees.
 */
final class LoginBlockerSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoginAttemptService $loginAttemptService,
        private readonly RequestStack $requestStack
    ) {}

    public static function getSubscribedEvents(): array
    {
        /*
         * Je me positionne avec une priorite elevee pour etre execute
         * avant les autres verificateurs.
         */
        return [
            CheckPassportEvent::class => ['onCheckPassport', 512],
        ];
    }

    /**
     * Je verifie si la connexion est autorisee avant de continuer l'authentification.
     */
    public function onCheckPassport(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $email = $request->request->get('_username', '') ?? '';
        $ipAddress = $request->getClientIp() ?? 'unknown';

        if ($email === '') {
            return;
        }

        /*
         * Je verifie si l'IP est bloquee.
         */
        if ($this->loginAttemptService->isIpBlocked($ipAddress)) {
            throw new CustomUserMessageAuthenticationException(
                sprintf(
                    'Trop de tentatives depuis cette adresse IP. Réessayez dans %d minutes.',
                    $this->loginAttemptService->getTimeWindowMinutes()
                )
            );
        }

        /*
         * Je verifie si l'email est bloque.
         */
        if ($this->loginAttemptService->isEmailBlocked($email)) {
            throw new CustomUserMessageAuthenticationException(
                sprintf(
                    'Trop de tentatives pour ce compte. Réessayez dans %d minutes.',
                    $this->loginAttemptService->getTimeWindowMinutes()
                )
            );
        }
    }
}