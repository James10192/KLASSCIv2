@extends('layouts.app')

@section('title', config('app.name', 'KLASSCI') . ' — Accès restreint')

@section('page_title', 'Accès restreint')

@section('content')
<style>
    :root {
        --ar-shadow-primary: rgba(4,83,203,0.06);
        --ar-shadow-primary-hover: rgba(4,83,203,0.25);
        --ar-primary-dark: #0341a0;
        --ar-warn-bg: #fffbeb;
        --ar-warn-border: #fef3c7;
        --ar-warn-icon: #f59e0b;
        --ar-warn-text: #92400e;
        --ar-border: #e2e8f0;
        --ar-bg-light: #f8fafc;
        --ar-text-primary: #1e293b;
        --ar-text-secondary: #64748b;
        --ar-text-muted: #94a3b8;
    }

    .ar-container { max-width: 560px; margin: 2rem auto; }
    .ar-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 6px 24px var(--ar-shadow-primary);
        border: 1px solid var(--ar-border);
        overflow: hidden;
    }
    .ar-header {
        background: linear-gradient(135deg, var(--primary, #0453cb) 0%, #5e91de 100%);
        padding: 2rem 2rem 1.75rem;
        text-align: center;
    }
    .ar-shield {
        width: 56px; height: 56px; margin: 0 auto 1rem;
        background: rgba(255,255,255,0.15);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        backdrop-filter: blur(8px);
    }
    .ar-shield svg { width: 28px; height: 28px; fill: none; stroke: #fff; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
    .ar-header h2 { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.35rem; }
    .ar-header p { font-size: 0.8125rem; color: rgba(255,255,255,0.8); margin: 0; }
    .ar-body { padding: 1.75rem 2rem 2rem; }
    .ar-message { font-size: 0.875rem; line-height: 1.6; color: var(--ar-text-secondary); margin-bottom: 1.5rem; }
    .ar-steps {
        background: var(--ar-bg-light);
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    .ar-steps-title {
        font-size: 0.75rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.05em;
        color: var(--ar-text-muted); margin-bottom: 0.875rem;
    }
    .ar-step { display: flex; gap: 0.75rem; align-items: flex-start; padding: 0.5rem 0; }
    .ar-step + .ar-step { border-top: 1px solid var(--ar-border); }
    .ar-step-num {
        width: 22px; height: 22px; min-width: 22px;
        background: var(--primary, #0453cb); color: #fff;
        font-size: 0.6875rem; font-weight: 700; border-radius: 6px;
        display: flex; align-items: center; justify-content: center; margin-top: 1px;
    }
    .ar-step-text { font-size: 0.8125rem; line-height: 1.5; color: var(--ar-text-primary); }
    .ar-step-text strong { font-weight: 600; }
    .ar-step-text a { color: var(--primary, #0453cb); text-decoration: none; font-weight: 500; }
    .ar-step-text a:hover { text-decoration: underline; }
    .ar-note {
        display: flex; gap: 0.5rem; align-items: flex-start;
        background: var(--ar-warn-bg); border: 1px solid var(--ar-warn-border);
        border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1.5rem;
    }
    .ar-note svg { width: 16px; height: 16px; min-width: 16px; fill: var(--ar-warn-icon); margin-top: 1px; }
    .ar-note p { font-size: 0.75rem; line-height: 1.5; color: var(--ar-warn-text); margin: 0; }
    .ar-actions { display: flex; gap: 0.75rem; }
    .ar-btn {
        flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem;
        padding: 0.625rem 1rem; font-size: 0.8125rem; font-weight: 600;
        border-radius: 8px; text-decoration: none; transition: all 0.15s ease;
        border: none; cursor: pointer; font-family: inherit;
    }
    .ar-btn svg { width: 15px; height: 15px; fill: none; stroke: currentColor; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .ar-btn-primary { background: var(--primary, #0453cb); color: #fff; }
    .ar-btn-primary:hover { background: var(--ar-primary-dark); color: #fff; box-shadow: 0 2px 8px var(--ar-shadow-primary-hover); text-decoration: none; }
    .ar-btn-outline { background: #fff; color: var(--ar-text-primary); border: 1px solid var(--ar-border); }
    .ar-btn-outline:hover { background: var(--ar-bg-light); border-color: #cbd5e1; text-decoration: none; color: var(--ar-text-primary); }
    .ar-footer { border-top: 1px solid var(--ar-border); padding: 1rem 2rem; text-align: center; }
    .ar-footer p { font-size: 0.6875rem; color: var(--ar-text-muted); margin: 0; }
    .ar-footer a { color: var(--primary, #0453cb); text-decoration: none; font-weight: 500; }
    .ar-footer a:hover { text-decoration: underline; }
    @media (max-width: 480px) {
        .ar-body { padding: 1.25rem; }
        .ar-header { padding: 1.5rem 1.25rem; }
        .ar-actions { flex-direction: column; }
    }
</style>
<?php $supportEmail = config('app.support_email'); ?>

<div class="ar-container">
    <div class="ar-card">
        <div class="ar-header">
            <div class="ar-shield">
                <svg viewBox="0 0 24 24">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M9.5 12l2 2 4-4" stroke-width="2"/>
                </svg>
            </div>
            <h2>Accès restreint</h2>
            <p>Cette fonctionnalité nécessite une autorisation</p>
        </div>

        <div class="ar-body">
            <p class="ar-message">
                L'accès à cette section est réservé aux utilisateurs disposant des droits nécessaires.
                Pour obtenir l'autorisation, veuillez suivre la procédure ci-dessous.
            </p>

            <div class="ar-steps">
                <div class="ar-steps-title">Procédure de demande d'accès</div>

                <div class="ar-step">
                    <div class="ar-step-num">1</div>
                    <div class="ar-step-text">
                        <strong>Contactez votre Directeur des Études</strong> et expliquez la fonctionnalité à laquelle vous souhaitez accéder ainsi que la raison de votre demande.
                    </div>
                </div>

                <div class="ar-step">
                    <div class="ar-step-num">2</div>
                    <div class="ar-step-text">
                        Le Directeur des Études doit <strong>valider votre demande</strong> et la transmettre au service technique avec son accord explicite.
                    </div>
                </div>

                <div class="ar-step">
                    <div class="ar-step-num">3</div>
                    <div class="ar-step-text">
                        Envoyez votre demande validée à <a href="mailto:{{ $supportEmail }}?subject=%5BKLASSCI%5D%20Demande%20d%27acc%C3%A8s%20-%20{{ urlencode(auth()->user()->name ?? 'Utilisateur') }}&body=Bonjour%2C%0A%0AJe%20souhaite%20obtenir%20l%27acc%C3%A8s%20%C3%A0%20la%20fonctionnalit%C3%A9%20suivante%20%3A%20%5Bpr%C3%A9cisez%5D%0A%0ARaison%20%3A%20%5Bpr%C3%A9cisez%5D%0A%0ACette%20demande%20a%20%C3%A9t%C3%A9%20valid%C3%A9e%20par%20le%20Directeur%20des%20%C3%89tudes.%0A%0ACordialement%2C%0A{{ urlencode(auth()->user()->name ?? '') }}">{{ $supportEmail }}</a>
                    </div>
                </div>
            </div>

            <div class="ar-note">
                <svg viewBox="0 0 20 20"><path d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.168 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 7v3m0 3h.01"/></svg>
                <p>
                    <strong>Important :</strong> La validation du Directeur des Études doit figurer en copie du mail.
                    Sans cette validation, la demande ne pourra pas être traitée.
                </p>
            </div>

            <div class="ar-actions">
                <a href="{{ url('/dashboard') }}" class="ar-btn ar-btn-primary">
                    <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
                    Tableau de bord
                </a>
                <a href="mailto:{{ $supportEmail }}?subject=%5BKLASSCI%5D%20Demande%20d%27acc%C3%A8s%20-%20{{ urlencode(auth()->user()->name ?? 'Utilisateur') }}&body=Bonjour%2C%0A%0AJe%20souhaite%20obtenir%20l%27acc%C3%A8s%20%C3%A0%20la%20fonctionnalit%C3%A9%20suivante%20%3A%20%5Bpr%C3%A9cisez%5D%0A%0ARaison%20%3A%20%5Bpr%C3%A9cisez%5D%0A%0ACette%20demande%20a%20%C3%A9t%C3%A9%20valid%C3%A9e%20par%20le%20Directeur%20des%20%C3%89tudes.%0A%0ACordialement%2C%0A{{ urlencode(auth()->user()->name ?? '') }}" class="ar-btn ar-btn-outline">
                    <svg viewBox="0 0 24 24"><path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Demander l'accès
                </a>
            </div>
        </div>

        <div class="ar-footer">
            <p>Service technique — <a href="mailto:{{ $supportEmail }}">African Digit Consulting</a></p>
        </div>
    </div>
</div>
@endsection
