<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * J’ajoute des en-têtes HTTP de sécurité sur chaque réponse.
 *
 * Je centralise ces en-têtes dans un EventSubscriber pour éviter de les dupliquer
 * dans les contrôleurs et pour garantir une configuration homogène.
 */
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    /**
     * J’injecte le Kernel afin de connaître l’environnement (dev/prod).
     */
    public function __construct(
        private readonly KernelInterface $kernel
    ) {}

    /**
     * Je déclare les événements Symfony auxquels je suis abonné.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * J’applique mes en-têtes de sécurité sur la réponse HTTP.
     *
     * Je ne traite que la requête principale pour éviter d’impacter les sous-requêtes.
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $this->applyBaseHeaders($response);
        $this->applyCsp($response);

        if ($this->kernel->getEnvironment() === 'prod' && $request->isSecure()) {
            $this->applyHsts($response);
        }
    }

    /**
     * J’applique un socle d’en-têtes de sécurité génériques.
     *
     * J’améliore la protection contre le clickjacking, le sniffing de type MIME,
     * certaines fuites de referrer et je désactive des permissions navigateur non utilisées.
     */
    private function applyBaseHeaders(Response $response): void
    {
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), accelerometer=(), gyroscope=(), magnetometer=()'
        );
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
    }

    /**
     * J’applique une Content Security Policy adaptée aux ressources de MediaTech.
     *
     * Je whiteliste uniquement les sources nécessaires (CDN, Google Fonts, images TMDB,
     * APIs externes). Si une CSP est déjà définie, je ne l’écrase pas.
     */
    private function applyCsp(Response $response): void
    {
        if ($response->headers->has('Content-Security-Policy')) {
            return;
        }

        $cdnJsDelivr = 'https://cdn.jsdelivr.net';
        $googleFontsCss = 'https://fonts.googleapis.com';
        $googleFontsFiles = 'https://fonts.gstatic.com';
        $tmdbApi = 'https://api.themoviedb.org';
        $tmdbImages = 'https://image.tmdb.org';
        $googleApis = 'https://www.googleapis.com';
        $openLibraryCovers = 'https://covers.openlibrary.org';
        $googleBooks = 'https://books.google.com';
        $googleBookUserContent = 'https://books.googleusercontent.com';

        $policy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: {$tmdbImages} {$openLibraryCovers} {$googleBooks} {$googleBookUserContent}",
            "font-src 'self' data: {$googleFontsFiles} {$cdnJsDelivr}",
            "style-src 'self' 'unsafe-inline' {$cdnJsDelivr} {$googleFontsCss}",
            "script-src 'self' 'unsafe-inline' {$cdnJsDelivr}",
            "connect-src 'self' {$tmdbApi} {$googleApis}",
        ]);

        if ($this->kernel->getEnvironment() === 'prod') {
            $policy .= '; upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', $policy);
    }

    /**
     * J’applique HSTS pour forcer l’usage de HTTPS côté navigateur.
     *
     * Je ne l’active qu’en production et uniquement si la requête est déjà en HTTPS,
     * afin d’éviter des effets de bord en développement.
     */
    private function applyHsts(Response $response): void
    {
        if ($response->headers->has('Strict-Transport-Security')) {
            return;
        }

        $response->headers->set(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );
    }
}
