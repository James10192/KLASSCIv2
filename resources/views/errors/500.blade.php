@extends('layouts.app')

@section('title', \App\Helpers\SettingsHelper::get('school_name', 'KLASSCI') . ' — Erreur serveur')

@section('page_title', 'Erreur serveur')

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
        --pe-warn-bg: #fffbeb;
        --pe-warn-border: #fef3c7;
        --pe-warn-icon: #f59e0b;
        --pe-warn-text: #92400e;
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
    .pe-message { font-size: 0.9rem; line-height: 1.6; color: var(--pe-text-secondary); text-align: center; margin-bottom: 1.5rem; }

    .pe-note {
        display: flex; gap: 0.65rem; align-items: flex-start;
        background: var(--pe-warn-bg); border: 1px solid var(--pe-warn-border);
        border-radius: 10px; padding: 0.85rem 1rem; margin-bottom: 1.5rem;
    }
    .pe-note svg { width: 18px; height: 18px; min-width: 18px; fill: var(--pe-warn-icon); margin-top: 1px; }
    .pe-note p { font-size: 0.78rem; line-height: 1.5; color: var(--pe-warn-text); margin: 0; }

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
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="pe-code">500</div>
            <h2>Erreur serveur</h2>
            <p>Quelque chose s'est mal passé de notre côté</p>
        </div>

        <div class="pe-body">
            <p class="pe-message">
                Une erreur inattendue est survenue. Nos équipes ont été automatiquement notifiées et travaillent à résoudre le problème.
            </p>

            <div class="pe-note">
                <svg viewBox="0 0 20 20"><path d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 7v3m0 3h.01"/></svg>
                <p>
                    <strong>Si le problème persiste,</strong> contactez le support technique en indiquant la date et l'heure de l'incident.
                </p>
            </div>

            <div class="pe-actions">
                <a href="{{ url()->previous() }}" class="pe-btn pe-btn-primary">
                    <svg viewBox="0 0 24 24"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 102.13-9.36L1 10"/></svg>
                    Réessayer
                </a>
                <a href="{{ url('/') }}" class="pe-btn pe-btn-outline">
                    <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                    Accueil
                </a>
            </div>
        </div>

        <div class="pe-footer">
            <p>Support technique — <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a></p>
        </div>
    </div>
</div>
@endsection
