<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly KernelInterface $kernel
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

        if ($this->kernel->getEnvironment() === 'prod' && $request->isSecure()) {
            $this->applyHsts($response);
        }
    }

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

        if ($this->kernel->getEnvironment() === 'prod') {
            $policy .= '; upgrade-insecure-requests';
        }

        $response->headers->set('Content-Security-Policy', $policy);
    }

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
