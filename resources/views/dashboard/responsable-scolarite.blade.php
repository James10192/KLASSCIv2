@extends('layouts.app')

@section('title', 'Tableau de bord Responsable scolarité - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="main-content">

    <x-role-hero
        icon="fa-user-check"
        title="Responsable scolarité"
        subtitle="Approbations de documents, fenêtres de saisie des notes et inscriptions à valider. Aucun montant n'est affiché."
        :kpis="[
            [
                'icon' => 'fa-stamp',
                'value' => $pendingApprovals->count(),
                'label' => 'Documents à approuver',
                'tone' => $pendingApprovals->count() > 0 ? 'alert' : null,
            ],
            [
                'icon' => 'fa-door-open',
                'value' => $openWindows->count(),
                'label' => 'Fenêtres de notes ouvertes',
            ],
            [
                'icon' => 'fa-clipboard-list',
                'value' => $pendingInscriptions,
                'label' => 'Inscriptions à valider',
                'href' => route('esbtp.inscriptions.index', ['status' => 'en_attente']),
                'tone' => $pendingInscriptions > 0 ? 'alert' : null,
            ],
        ]">
        <x-slot:actions>
            <a class="rdx-btn" href="{{ route('esbtp.bulletins.index') }}">
                <i class="fas fa-file-lines"></i>Bulletins
            </a>
            <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.inscriptions.index') }}">
                <i class="fas fa-folder-open"></i>Inscriptions
            </a>
        </x-slot:actions>
    </x-role-hero>

    <div class="rdx-grid">

        <x-role-panel
            icon="fa-stamp"
            title="File d'approbation"
            subtitle="Certificats, attestations et bulletins en attente de votre validation"
            :count="$pendingApprovals->count()">
            @forelse($pendingApprovals as $approval)
                <div class="rdx-row">
                    <div class="rdx-row-icon"><i class="fas fa-file-signature"></i></div>
                    <div class="rdx-row-main">
                        <div class="rdx-row-title">{{ ucfirst($approval->document_type) }}</div>
                        <div class="rdx-row-meta">
                            Étudiant&nbsp;#{{ $approval->etudiant_id }}
                            @if($approval->created_at)
                                · demandé le {{ $approval->created_at->format('d/m/Y') }}
                            @endif
                        </div>
                    </div>
                    @can('documents.approve')
                        <div class="rdx-row-actions">
                            <form method="POST" action="{{ route('esbtp.documents.approvals.approve', $approval) }}">
                                @csrf
                                <button class="rdx-act rdx-act--primary" type="submit">
                                    <i class="fas fa-check"></i>Approuver
                                </button>
                            </form>
                            <form method="POST" action="{{ route('esbtp.documents.approvals.reject', $approval) }}">
                                @csrf
                                <button class="rdx-act rdx-act--danger" type="submit">
                                    <i class="fas fa-xmark"></i>Refuser
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            @empty
                <x-role-empty
                    icon="fa-circle-check"
                    title="Aucun document en attente"
                    hint="Les demandes de certificat, d'attestation ou de bulletin apparaîtront ici." />
            @endforelse
        </x-role-panel>

        <x-role-panel
            icon="fa-calendar-check"
            title="Fenêtres de saisie des notes"
            subtitle="Le service scolarité ne peut saisir que pendant une fenêtre ouverte"
            :count="$openWindows->count()">

            @can('notes.window.manage')
                <form method="POST" action="{{ route('esbtp.notes-windows.store') }}" class="rs-window-form">
                    @csrf
                    <div class="rs-form-title">
                        <i class="fas fa-plus"></i>Ouvrir une nouvelle fenêtre
                    </div>
                    <div class="rs-form-grid">
                        <div class="rs-field">
                            <label for="classe_id">Classe</label>
                            <x-au-select
                                name="classe_id"
                                icon="fa-chalkboard"
                                placeholder="Choisir une classe"
                                :searchable="true"
                                :options="$classes->pluck('name', 'id')->toArray()" />
                        </div>
                        <div class="rs-field">
                            <label for="starts_at">Du</label>
                            <input class="form-control" type="date" id="starts_at" name="starts_at" required>
                        </div>
                        <div class="rs-field">
                            <label for="ends_at">Au</label>
                            <input class="form-control" type="date" id="ends_at" name="ends_at" required>
                        </div>
                    </div>
                    <button class="rdx-act rdx-act--primary rs-submit" type="submit">
                        <i class="fas fa-door-open"></i>Ouvrir la fenêtre
                    </button>
                </form>
            @endcan

            @forelse($openWindows as $window)
                <div class="rdx-row">
                    <div class="rdx-row-icon"><i class="fas fa-chalkboard"></i></div>
                    <div class="rdx-row-main">
                        <div class="rdx-row-title">{{ $window->classe->name ?? 'Classe #'.$window->classe_id }}</div>
                        <div class="rdx-row-meta">
                            Du {{ $window->starts_at->format('d/m/Y') }}
                            au {{ $window->ends_at->format('d/m/Y') }}
                        </div>
                    </div>
                    @can('notes.window.manage')
                        <div class="rdx-row-actions">
                            <form method="POST" action="{{ route('esbtp.notes-windows.close', $window) }}">
                                @csrf
                                <button class="rdx-act rdx-act--ghost" type="submit">
                                    <i class="fas fa-lock"></i>Fermer
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            @empty
                <x-role-empty
                    icon="fa-door-closed"
                    title="Aucune fenêtre ouverte"
                    hint="Tant qu'aucune fenêtre n'est ouverte, la saisie des notes reste bloquée." />
            @endforelse
        </x-role-panel>

    </div>
</div>
@endsection

@push('styles')
<style>
    /* Namespace rs-* : tableau de bord responsable scolarité */
    .rs-window-form {
        background: rgba(4, 83, 203, .04);
        border: 1px solid rgba(4, 83, 203, .14);
        border-radius: 10px;
        padding: .9rem 1rem 1rem;
        margin-bottom: 1rem;
    }
    .rs-form-title {
        display: flex; align-items: center; gap: .45rem;
        font-size: .82rem; font-weight: 700; color: #0453cb;
        margin-bottom: .7rem;
    }
    .rs-form-grid {
        display: grid; gap: .7rem;
        grid-template-columns: 1.4fr 1fr 1fr;
    }
    .rs-field { display: flex; flex-direction: column; gap: .25rem; }
    .rs-field label {
        font-size: .72rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .3px;
    }
    .rs-field .form-control {
        border: 1px solid #e2e8f0; border-radius: 8px;
        padding: .45rem .65rem; font-size: .84rem;
    }
    .rs-field .form-control:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4, 83, 203, .1);
        outline: none;
    }
    .rs-submit { margin-top: .8rem; }

    @media (max-width: 768px) {
        .rs-form-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush
