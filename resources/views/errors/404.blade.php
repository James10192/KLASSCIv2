@extends('layouts.app')

@section('title', \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI') . ' — Page non trouvée')

@section('page_title', 'Page non trouvée')

@section('content')
<style>
    :root {
        --pe-shadow-primary: rgba(4,83,203,0.06);
        --pe-shadow-primary-hover: rgba(4,83,203,0.25);
        --pe-primary-dark: #0341a0;
        --pe-border: #e2e8f0;
        --pe-bg-light: #f8fafc;
        --pe-text-primary: #1e293b;
        --pe-text-secondary: #64748b;
        --pe-text-muted: #94a3b8;
    }

    .pe-container { max-width: 520px; margin: 3rem auto; padding: 0 1rem; }
    .pe-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px var(--pe-shadow-primary);
        border: 1px solid var(--pe-border);
        overflow: hidden;
    }
    .pe-header {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        padding: 2.5rem 2rem 2rem;
        text-align: center;
        position: relative;
    }
    .pe-icon-wrap {
        width: 64px; height: 64px; margin: 0 auto 1.25rem;
        background: rgba(255,255,255,0.15);
        border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.18);
    }
    .pe-icon-wrap svg { width: 32px; height: 32px; fill: none; stroke: #fff; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .pe-code { font-size: 4rem; font-weight: 800; color: #fff; line-height: 1; margin-bottom: 0.5rem; letter-spacing: -2px; }
    .pe-header h2 { font-size: 1.35rem; font-weight: 700; color: #fff; margin-bottom: 0.35rem; }
    .pe-header p { font-size: 0.85rem; color: rgba(255,255,255,0.8); margin: 0; }

    .pe-body { padding: 2rem; }
    .pe-message { font-size: 0.9rem; line-height: 1.6; color: var(--pe-text-secondary); text-align: center; margin-bottom: 1.75rem; }

    .pe-actions { display: flex; gap: 0.75rem; }
    .pe-btn {
        flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
        padding: 0.7rem 1rem; font-size: 0.85rem; font-weight: 600;
        border-radius: 10px; text-decoration: none; transition: all 0.15s ease;
        border: none; cursor: pointer; font-family: inherit;
    }
    .pe-btn svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .pe-btn-primary { background: #0453cb; color: #fff; }
    .pe-btn-primary:hover { background: var(--pe-primary-dark); color: #fff; box-shadow: 0 4px 14px var(--pe-shadow-primary-hover); text-decoration: none; }
    .pe-btn-outline { background: #fff; color: var(--pe-text-primary); border: 1px solid var(--pe-border); }
    .pe-btn-outline:hover { background: var(--pe-bg-light); border-color: #cbd5e1; text-decoration: none; color: var(--pe-text-primary); }

    .pe-footer { border-top: 1px solid var(--pe-border); padding: 1rem 2rem; text-align: center; }
    .pe-footer p { font-size: 0.7rem; color: var(--pe-text-muted); margin: 0; }
    .pe-footer a { color: #0453cb; text-decoration: none; font-weight: 500; }
    .pe-footer a:hover { text-decoration: underline; }

    @media (max-width: 480px) {
        .pe-body, .pe-footer { padding: 1.25rem; }
        .pe-header { padding: 1.75rem 1.25rem 1.5rem; }
        .pe-actions { flex-direction: column; }
        .pe-code { font-size: 3rem; }
    }
</style>

<div class="pe-container">
    <div class="pe-card">
        <div class="pe-header">
            <div class="pe-icon-wrap">
                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                    <path d="M11 8v3M11 14v.01"/>
                </svg>
            </div>
            <div class="pe-code">404</div>
            <h2>Page non trouvée</h2>
            <p>La page que vous cherchez n'existe pas</p>
        </div>

        <div class="pe-body">
            <p class="pe-message">
                Cette page a été déplacée, supprimée, ou vous n'avez peut-être pas les droits d'accès nécessaires.
                @guest
                    <br><strong>Connectez-vous</strong> pour accéder à votre espace.
                @endguest
            </p>

            <div class="pe-actions">
                <a href="{{ url('/') }}" class="pe-btn pe-btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                    Retour à l'accueil
                </a>
                @auth
                    <a href="{{ url('/dashboard') }}" class="pe-btn pe-btn-outline">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/></svg>
                        Tableau de bord
                    </a>
                @else
                    <a href="{{ url('/login') }}" class="pe-btn pe-btn-outline">
                        <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
                        Se connecter
                    </a>
                @endauth
            </div>
        </div>

        <div class="pe-footer">
            <p>Besoin d'aide ? <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a></p>
        </div>
    </div>
</div>
@endsection
