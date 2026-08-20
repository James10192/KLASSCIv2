@extends('layouts.app')

@section('title', "Tableau de bord Agent d'inscription - KLASSCI")

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="main-content">

    <x-role-hero
        icon="fa-user-plus"
        title="Agent d'inscription"
        subtitle="File des dossiers à créer, éditer et valider après encaissement. Aucun montant n'est affiché."
        :kpis="[
            [
                'icon' => 'fa-clipboard-check',
                'value' => $pendingValidation,
                'label' => 'Dossiers à valider',
                'href' => route('esbtp.inscriptions.index', ['status' => 'en_attente']),
                'tone' => $pendingValidation > 0 ? 'alert' : null,
            ],
            [
                'icon' => 'fa-hourglass-half',
                'value' => $waitingPayment,
                'label' => 'En attente de paiement',
                'href' => route('esbtp.inscriptions.index', ['status' => 'en_attente']),
            ],
            [
                'icon' => 'fa-circle-check',
                'value' => $validatedToday,
                'label' => 'Validés ce jour',
            ],
            [
                'icon' => 'fa-file-shield',
                'value' => $sousReserve,
                'label' => 'Dossiers sous réserve',
                'href' => route('esbtp.inscriptions.sous-reserve'),
                'tone' => $sousReserve > 0 ? 'alert' : null,
            ],
        ]">
        <x-slot:actions>
            @can('inscriptions.create')
                <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.inscriptions.create') }}">
                    <i class="fas fa-plus"></i>Nouvelle inscription
                </a>
            @endcan
        </x-slot:actions>
    </x-role-hero>

    <div class="rdx-grid">

        <x-role-panel
            icon="fa-bolt"
            title="Actions courantes"
            subtitle="Les gestes du quotidien, à portée de clic">
            <div class="ai-actions">
                @can('inscriptions.create')
                    <a class="ai-action" href="{{ route('esbtp.inscriptions.create') }}">
                        <span class="ai-action-icon"><i class="fas fa-user-plus"></i></span>
                        <span class="ai-action-body">
                            <span class="ai-action-title">Nouvelle inscription</span>
                            <span class="ai-action-hint">Créer un dossier étudiant</span>
                        </span>
                        <i class="fas fa-chevron-right ai-action-go"></i>
                    </a>
                @endcan
                <a class="ai-action" href="{{ route('esbtp.inscriptions.index') }}">
                    <span class="ai-action-icon"><i class="fas fa-folder-open"></i></span>
                    <span class="ai-action-body">
                        <span class="ai-action-title">Liste des inscriptions</span>
                        <span class="ai-action-hint">Rechercher et éditer un dossier</span>
                    </span>
                    <i class="fas fa-chevron-right ai-action-go"></i>
                </a>
                <a class="ai-action" href="{{ route('esbtp.etudiants.index') }}">
                    <span class="ai-action-icon"><i class="fas fa-user-graduate"></i></span>
                    <span class="ai-action-body">
                        <span class="ai-action-title">Liste des étudiants</span>
                        <span class="ai-action-hint">Consulter les fiches</span>
                    </span>
                    <i class="fas fa-chevron-right ai-action-go"></i>
                </a>
                <a class="ai-action" href="{{ route('esbtp.reinscription.index') }}">
                    <span class="ai-action-icon"><i class="fas fa-rotate"></i></span>
                    <span class="ai-action-body">
                        <span class="ai-action-title">Réinscriptions</span>
                        <span class="ai-action-hint">Renouveler une année</span>
                    </span>
                    <i class="fas fa-chevron-right ai-action-go"></i>
                </a>
            </div>
        </x-role-panel>

        <x-role-panel
            icon="fa-circle-info"
            title="Votre périmètre"
            subtitle="Ce que ce compte peut faire, et ce qu'il ne voit pas">
            <ul class="ai-scope">
                <li class="ai-scope-item ai-scope-item--yes">
                    <i class="fas fa-check"></i>
                    <span>Créer, éditer et valider les dossiers d'inscription</span>
                </li>
                <li class="ai-scope-item ai-scope-item--yes">
                    <i class="fas fa-check"></i>
                    <span>Consulter les étudiants, les classes et les filières</span>
                </li>
                <li class="ai-scope-item ai-scope-item--no">
                    <i class="fas fa-xmark"></i>
                    <span>Aucun montant, solde ni reçu financier n'est affiché</span>
                </li>
                <li class="ai-scope-item ai-scope-item--no">
                    <i class="fas fa-xmark"></i>
                    <span>Pas d'accès aux notes, au personnel ni aux paramètres</span>
                </li>
            </ul>
            <p class="ai-scope-note">
                La validation d'un dossier intervient <strong>après l'encaissement</strong> par la caisse.
            </p>
        </x-role-panel>

    </div>
</div>
@endsection

@push('styles')
<style>
    /* Namespace ai-* : tableau de bord agent d'inscription */
    .ai-actions { display: flex; flex-direction: column; gap: .55rem; }
    .ai-action {
        display: flex; align-items: center; gap: .85rem;
        padding: .8rem .9rem;
        border: 1px solid #e2e8f0; border-radius: 10px;
        text-decoration: none; background: #fff;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .ai-action:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4, 83, 203, .06); }
    .ai-action-icon {
        width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .85rem;
    }
    .ai-action-body { display: flex; flex-direction: column; min-width: 0; }
    .ai-action-title { font-size: .88rem; font-weight: 600; color: #1e293b; }
    .ai-action-hint { font-size: .75rem; color: #64748b; margin-top: .1rem; }
    .ai-action-go { margin-left: auto; color: #94a3b8; font-size: .72rem; }

    .ai-scope { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: .6rem; }
    .ai-scope-item { display: flex; align-items: flex-start; gap: .6rem; font-size: .84rem; color: #1e293b; }
    .ai-scope-item i {
        width: 20px; height: 20px; border-radius: 6px; flex-shrink: 0; margin-top: .1rem;
        display: flex; align-items: center; justify-content: center; font-size: .65rem;
    }
    .ai-scope-item--yes i { background: rgba(16, 185, 129, .12); color: #10b981; }
    .ai-scope-item--no i { background: rgba(100, 116, 139, .12); color: #64748b; }
    .ai-scope-item--no span { color: #64748b; }
    .ai-scope-note {
        margin: 1rem 0 0; padding: .7rem .85rem;
        background: rgba(4, 83, 203, .05); border-left: 3px solid #0453cb;
        border-radius: 0 8px 8px 0;
        font-size: .8rem; color: #1e293b;
    }
</style>
@endpush
