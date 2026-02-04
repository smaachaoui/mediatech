<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $appEnv
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $request = $event->getRequest();

        $this->applyBaseHeaders($response);
        $this->applyCsp($response);
        $this->applyHsts($response, $request->isSecure());
    }

    private function applyBaseHeaders(Response $response): void
    {
        $headers = $response->headers;

        if (!$headers->has('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        if (!$headers->has('Referrer-Policy')) {
            $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        if (!$headers->has('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'DENY');
        }

        if (!$headers->has('Permissions-Policy')) {
            $headers->set(
                'Permissions-Policy',
                'camera=(), microphone=(), geolocation=(), payment=(), usb=(), accelerometer=(), gyroscope=(), magnetometer=()'
            );
        }

        if (!$headers->has('Cross-Origin-Opener-Policy')) {
            $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        }

        if (!$headers->has('Cross-Origin-Resource-Policy')) {
            $headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        }

        if (!$headers->has('Cross-Origin-Embedder-Policy')) {
            $headers->set('Cross-Origin-Embedder-Policy', 'unsafe-none');
        }
    }

    private function applyCsp(Response $response): void
    {
        if ($response->headers->has('Content-Security-Policy')) {
            return;
        }

        $policy = implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "img-src 'self' data: https:",
            "font-src 'self' data: https:",
            "style-src 'self' 'unsafe-inline' https:",
            "script-src 'self' 'unsafe-inline' https:",
            "connect-src 'self' https:",
        ]);

        if ($this->appEnv === 'prod') {
            $policy .= '; upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', $policy);
    }

    private function applyHsts(Response $response, bool $isSecure): void
    {
        if ($this->appEnv !== 'prod') {
            return;
        }

        if (!$isSecure) {
            return;
        }

        if ($response->headers->has('Strict-Transport-Security')) {
            return;
        }

        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
