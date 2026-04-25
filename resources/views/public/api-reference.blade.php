@extends('layouts.landing')

@section('title', 'API Reference')
@section('description', "Référence des endpoints API LMS exposés par KLASSCI : authentification Sanctum, structure académique, planning, évaluations, visioconférences, écriture des notes et présences.")

@push('styles')
<style>
    .api-banner {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 1rem 1.25rem;
        margin-bottom: 2rem;
        background: rgba(217,119,6,0.08);
        border-left: 3px solid #d97706;
        border-radius: 0 var(--radius) var(--radius) 0;
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.9rem;
        line-height: 1.6;
        color: var(--text-secondary);
    }
    html.dark .api-banner {
        background: rgba(251,191,36,0.1);
        border-left-color: #fbbf24;
    }
    .api-banner i {
        color: #d97706;
        font-size: 1rem;
        margin-top: 0.15rem;
    }
    html.dark .api-banner i { color: #fbbf24; }
    .api-banner strong { color: var(--text); }

    .api-section {
        margin-bottom: 4rem;
        scroll-margin-top: 80px;
    }
    .api-section-header h2 {
        font-family: 'IBM Plex Serif', Georgia, serif;
        font-size: 1.5rem;
        font-style: normal;
        font-weight: 400;
        color: var(--accent);
        letter-spacing: -0.015em;
        margin-bottom: 0.5rem;
    }
    .api-section-header p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.95rem;
        color: var(--text-secondary);
        line-height: 1.65;
        margin-bottom: 0;
    }
    .api-section-header {
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border);
    }

    .api-auth-badge {
        display: inline-block;
        margin-top: 0.75rem;
        padding: 0.2rem 0.55rem;
        border-radius: 3px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 500;
    }
    .api-auth-badge--public {
        background: rgba(16,185,129,0.12);
        color: #047857;
    }
    html.dark .api-auth-badge--public { color: #34d399; background: rgba(52,211,153,0.15); }
    .api-auth-badge--bearer {
        background: var(--accent-light);
        color: var(--accent);
    }
    .api-auth-badge--mixed {
        background: rgba(168,85,247,0.12);
        color: #7c3aed;
    }
    html.dark .api-auth-badge--mixed { color: #c4b5fd; background: rgba(196,181,253,0.15); }

    .api-endpoint {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        margin-bottom: 1.25rem;
        overflow: hidden;
        transition: border-color var(--duration-normal);
    }
    .api-endpoint:hover { border-color: var(--border-strong); }

    .api-endpoint-head {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.85rem 1.25rem;
        background: var(--bg-alt);
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }

    .api-method {
        display: inline-block;
        padding: 0.25rem 0.6rem;
        border-radius: 3px;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #fff;
        flex-shrink: 0;
    }
    .api-method--get    { background: #2563eb; }
    .api-method--post   { background: #059669; }
    .api-method--put    { background: #d97706; }
    .api-method--delete { background: #dc2626; }

    .api-path {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.92rem;
        color: var(--text);
        flex: 1;
        word-break: break-all;
    }
    .api-path .seg-var {
        color: var(--accent);
        font-weight: 500;
    }

    .api-summary {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.82rem;
        color: var(--text-muted);
        flex-basis: 100%;
        margin-top: -0.15rem;
    }

    .api-endpoint-body {
        padding: 1.1rem 1.25rem;
    }

    .api-endpoint-body p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.92rem;
        color: var(--text-secondary);
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .api-params {
        margin-top: 0.75rem;
        margin-bottom: 1rem;
    }
    .api-params h5 {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        margin-bottom: 0.55rem;
        font-weight: 600;
    }
    .api-params table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }
    .api-params th, .api-params td {
        text-align: left;
        padding: 0.5rem 0.6rem;
        border-bottom: 1px solid var(--border);
    }
    .api-params th {
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        background: var(--bg-alt);
    }
    .api-params td .name {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        color: var(--text);
        font-weight: 500;
    }
    .api-params td .type {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.78rem;
        color: var(--text-muted);
    }
    .api-params td .req {
        display: inline-block;
        font-size: 0.62rem;
        padding: 1px 5px;
        border-radius: 3px;
        background: var(--accent-light);
        color: var(--accent);
        margin-left: 0.4rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
    }

    .api-response {
        margin-top: 0.75rem;
    }
    .api-response h5 {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        margin-bottom: 0.5rem;
        font-weight: 600;
    }
    .api-response pre {
        background: var(--bg-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 0.85rem 1rem;
        overflow-x: auto;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        line-height: 1.5;
        color: var(--text);
        margin: 0;
    }

    .api-notes {
        margin-top: 0.85rem;
        padding-top: 0.85rem;
        border-top: 1px dashed var(--border);
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.82rem;
        color: var(--text-muted);
        line-height: 1.55;
    }
    .api-notes i {
        color: var(--accent);
        margin-right: 0.4rem;
    }

    .api-quickstart {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.5rem 1.75rem;
        margin-bottom: 3rem;
    }
    .api-quickstart h3 {
        font-family: 'IBM Plex Serif', serif;
        font-style: normal;
        font-weight: 400;
        font-size: 1.15rem;
        color: var(--text);
        margin-bottom: 1rem;
        letter-spacing: -0.01em;
    }
    .api-quickstart pre {
        background: var(--bg-alt);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 0.85rem 1rem;
        overflow-x: auto;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        line-height: 1.55;
        margin: 0.85rem 0;
    }
    .api-quickstart p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.92rem;
        color: var(--text-secondary);
        line-height: 1.65;
    }
</style>
@endpush

@section('content')

<section class="page-hero">
    <div class="container">
        <div class="page-hero-eyebrow">API Reference</div>
        <h1>API LMS — Intégration externe</h1>
        <p>
            Endpoints REST permettant à un LMS externe de se connecter à KLASSCI : authentification Sanctum, lecture de la structure académique, gestion des séances vidéo, écriture des notes et présences. Toutes les requêtes sont en HTTPS.
        </p>
        <div class="page-hero-meta">
            <span><i class="fas fa-code-branch" style="margin-right:.4rem"></i>v1.0.0</span>
            <span class="dot"></span>
            <span>Base URL : <code style="font-family:'IBM Plex Mono',monospace;font-size:.78rem;color:var(--text)">https://{tenant}.klassci.com/api/lms</code></span>
        </div>
    </div>
</section>

<section class="page-with-sidebar">
    <div class="container">
        <div class="page-with-sidebar-inner">

            <aside class="page-sidebar" aria-label="Sommaire de l'API">
                <h4>Référence API</h4>
                <ul>
                    <li><a href="#introduction">Introduction</a></li>
                    <li><a href="#quickstart">Démarrage rapide</a></li>
                </ul>

                <h4 style="margin-top:1.5rem">Endpoints</h4>
                <ul>
                    @foreach($sections as $sec)
                        <li><a href="#{{ $sec['anchor'] }}">{{ $sec['title'] }}</a></li>
                    @endforeach
                </ul>

                <h4 style="margin-top:1.5rem">Liens utiles</h4>
                <ul>
                    <li><a href="{{ route('docs.index') }}">Documentation</a></li>
                    <li><a href="{{ route('changelog') }}">Changelog</a></li>
                    <li><a href="mailto:contact@klassci.com">Demander un accès partenaire</a></li>
                </ul>
            </aside>

            <article class="page-content">

                <div class="api-banner" role="note">
                    <i class="fas fa-flask" aria-hidden="true"></i>
                    <div>
                        <strong>Stabilité : Beta.</strong>
                        L'API LMS est conçue pour les intégrations établies en partenariat direct avec KLASSCI. Des changements non rétro-compatibles sont possibles avant la version 1.0 stable. Pour une intégration en production, contactez-nous afin d'obtenir un token dédié et de connaître le calendrier de stabilisation.
                    </div>
                </div>

                <div id="introduction" class="api-section">
                    <header class="api-section-header">
                        <h2>Introduction</h2>
                        <p>L'API LMS expose les données et opérations nécessaires à un système d'enseignement à distance qui souhaite interopérer avec KLASSCI : authentification unifiée, lecture de l'emploi du temps et des classes, écriture des notes obtenues lors d'évaluations en ligne, synchronisation des présences vidéo. Les endpoints administratifs internes (CLI, gestion de tenants, opérations de DBA) ne sont pas exposés sur cette page.</p>
                    </header>

                    <p style="font-family:'IBM Plex Sans',sans-serif;font-size:0.95rem;color:var(--text-secondary);line-height:1.7">
                        <strong>Authentification.</strong> KLASSCI utilise Laravel Sanctum. Vous récupérez un Bearer token via <code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">POST /api/lms/auth/login</code>, puis vous l'envoyez dans le header <code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">Authorization: Bearer {token}</code> sur toutes les requêtes protégées.
                    </p>

                    <p style="font-family:'IBM Plex Sans',sans-serif;font-size:0.95rem;color:var(--text-secondary);line-height:1.7">
                        <strong>Format.</strong> Toutes les requêtes et réponses sont en JSON (<code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">Content-Type: application/json</code>). Les dates sont en ISO 8601 (<code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">2026-04-25T10:30:00Z</code>). Les montants financiers ne sont pas exposés sur l'API LMS pour des raisons de sécurité.
                    </p>

                    <p style="font-family:'IBM Plex Sans',sans-serif;font-size:0.95rem;color:var(--text-secondary);line-height:1.7">
                        <strong>Codes HTTP.</strong> 200 succès, 201 ressource créée, 204 succès sans contenu, 400 requête invalide, 401 non authentifié, 403 non autorisé, 404 ressource introuvable, 422 validation échouée (corps JSON détaillé), 429 rate limit dépassé, 500 erreur serveur.
                    </p>

                    <p style="font-family:'IBM Plex Sans',sans-serif;font-size:0.95rem;color:var(--text-secondary);line-height:1.7">
                        <strong>Rate-limiting.</strong> Les endpoints publics de découverte sont sous le seuil <code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">lms-discovery</code> (10 requêtes par minute par IP). Les endpoints protégés Sanctum sont sous le seuil <code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">api</code> standard (60 par minute par utilisateur).
                    </p>

                    <p style="font-family:'IBM Plex Sans',sans-serif;font-size:0.95rem;color:var(--text-secondary);line-height:1.7">
                        <strong>Multi-tenant.</strong> Chaque établissement KLASSCI dispose d'un sous-domaine dédié (<code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">esbtp-abidjan.klassci.com</code>, <code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">esbtp-yakro.klassci.com</code>, etc.). Le contexte du tenant est entièrement déterminé par l'URL : pas besoin de header tenant, pas de risque de fuite cross-tenant.
                    </p>
                </div>

                <div id="quickstart" class="api-section">
                    <header class="api-section-header">
                        <h2>Démarrage rapide</h2>
                        <p>Voici un workflow minimal pour récupérer l'emploi du temps d'un enseignant. Toutes les commandes utilisent <code style="font-family:'IBM Plex Mono',monospace;font-size:.85rem;color:var(--text);background:var(--bg-alt);padding:1px 5px;border-radius:3px">curl</code> à titre d'exemple.</p>
                    </header>

                    <div class="api-quickstart">
                        <h3>1. Authentification</h3>
                        <pre>curl -X POST https://esbtp-abidjan.klassci.com/api/lms/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "identifier": "marie.kouassi",
    "password": "•••••••••",
    "device_name": "Mon LMS"
  }'</pre>

                        <p>Réponse :</p>
                        <pre>{
  "token": "1|abcdef1234567890...",
  "user": {
    "id": 42,
    "username": "marie.kouassi",
    "name": "Marie Kouassi",
    "role": "enseignant"
  }
}</pre>

                        <h3 style="margin-top:1.5rem">2. Récupérer l'emploi du temps</h3>
                        <pre>curl https://esbtp-abidjan.klassci.com/api/lms/emploi-temps \
  -H "Authorization: Bearer 1|abcdef1234567890..." \
  -H "Accept: application/json"</pre>

                        <h3 style="margin-top:1.5rem">3. Soumettre les notes d'une évaluation</h3>
                        <pre>curl -X POST https://esbtp-abidjan.klassci.com/api/lms/evaluations/123/notes \
  -H "Authorization: Bearer 1|abcdef1234567890..." \
  -H "Content-Type: application/json" \
  -d '{
    "notes": [
      { "etudiant_id": 1001, "note": 14.5, "commentaire": "Très bon" },
      { "etudiant_id": 1002, "note": 11, "commentaire": "" }
    ]
  }'</pre>

                        <h3 style="margin-top:1.5rem">4. Révoquer le token (déconnexion)</h3>
                        <pre>curl -X POST https://esbtp-abidjan.klassci.com/api/lms/auth/logout \
  -H "Authorization: Bearer 1|abcdef1234567890..."</pre>
                    </div>
                </div>

                @foreach($sections as $sec)
                    <div id="{{ $sec['anchor'] }}" class="api-section">
                        <header class="api-section-header">
                            <h2>{{ $sec['title'] }}</h2>
                            <p>{{ $sec['description'] }}</p>
                            @if($sec['auth'] === 'public')
                                <span class="api-auth-badge api-auth-badge--public">Public — sans token</span>
                            @elseif($sec['auth'] === 'bearer')
                                <span class="api-auth-badge api-auth-badge--bearer">Bearer Token Sanctum requis</span>
                            @else
                                <span class="api-auth-badge api-auth-badge--mixed">Public + Bearer après login</span>
                            @endif
                        </header>

                        @foreach($sec['endpoints'] as $ep)
                            <div class="api-endpoint">
                                <div class="api-endpoint-head">
                                    <span class="api-method api-method--{{ strtolower($ep['method']) }}">{{ $ep['method'] }}</span>
                                    <span class="api-path">{!! preg_replace('/\{([^}]+)\}/', '<span class="seg-var">{$1}</span>', e($ep['path'])) !!}</span>
                                    <span class="api-summary">{{ $ep['summary'] }}</span>
                                </div>
                                <div class="api-endpoint-body">
                                    <p>{{ $ep['description'] }}</p>

                                    @if(!empty($ep['params']))
                                        <div class="api-params">
                                            <h5>Paramètres</h5>
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Nom</th>
                                                        <th>Position</th>
                                                        <th>Type</th>
                                                        <th>Description</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($ep['params'] as $param)
                                                        <tr>
                                                            <td>
                                                                <span class="name">{{ $param['name'] }}</span>
                                                                @if($param['required'])<span class="req">Requis</span>@endif
                                                            </td>
                                                            <td><span class="type">{{ $param['in'] }}</span></td>
                                                            <td><span class="type">{{ $param['type'] }}</span></td>
                                                            <td>{{ $param['description'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                    @if(!empty($ep['response']))
                                        <div class="api-response">
                                            <h5>Exemple de réponse</h5>
                                            <pre>{{ $ep['response'] }}</pre>
                                        </div>
                                    @endif

                                    @if(!empty($ep['notes']))
                                        <div class="api-notes">
                                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                                            {{ $ep['notes'] }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

            </article>
        </div>
    </div>
</section>

@endsection
