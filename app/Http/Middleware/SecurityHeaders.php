<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Headers HTTP de sécurité appliqués à toutes les réponses (audit 2026-05-21).
     *
     * Pas de CSP ici — le code KLASSCI utilise des `<script>` inline dans les vues
     * Blade, ce qui ferait sauter une CSP stricte. CSP à introduire dans une PR
     * séparée avec migration progressive des inline scripts vers des fichiers JS
     * + nonces.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Clickjacking : interdit le rendu dans un iframe (sauf same-origin).
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN', false);

        // Type sniffing : navigateur ne devine pas le MIME (anti-XSS via faux .jpg).
        $response->headers->set('X-Content-Type-Options', 'nosniff', false);

        // Referrer leak : ne transmet le full URL qu'en navigation HTTPS↔HTTPS.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin', false);

        // HSTS : force HTTPS au navigateur pour 1 an + sous-domaines.
        // Uniquement appliqué quand la requête est elle-même en HTTPS.
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', false);
        }

        // Permissions Policy (legacy Feature-Policy) : désactive caméra/micro/geo
        // par défaut. Les features qui en ont besoin (présence par géoloc ?)
        // doivent override par route.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()', false);

        return $response;
    }
}
