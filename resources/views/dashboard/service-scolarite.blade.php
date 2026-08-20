@extends('layouts.app')

@section('title', 'Tableau de bord Service scolarité - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="main-content">

    <x-role-hero
        icon="fa-print"
        title="Service scolarité"
        subtitle="Documents approuvés à imprimer et saisie des notes limitée aux fenêtres ouvertes."
        :kpis="[
            [
                'icon' => 'fa-file-circle-check',
                'value' => $approvedDocuments->count(),
                'label' => 'Documents à imprimer',
                'tone' => $approvedDocuments->count() > 0 ? 'alert' : null,
            ],
            [
                'icon' => 'fa-door-open',
                'value' => $openWindows->count(),
                'label' => 'Classes ouvertes à la saisie',
            ],
        ]">
        <x-slot:actions>
            <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.etudiants.index') }}">
                <i class="fas fa-user-graduate"></i>Liste des étudiants
            </a>
        </x-slot:actions>
    </x-role-hero>

    <div class="rdx-grid">

        <x-role-panel
            icon="fa-print"
            title="File d'impression"
            subtitle="Uniquement les documents déjà approuvés par le responsable"
            :count="$approvedDocuments->count()">
            @forelse($approvedDocuments as $document)
                @php
                    $printUrl = match ($document->document_type) {
                        'certificat' => route('esbtp.etudiants.certificat', $document->etudiant_id),
                        'attestation' => route('esbtp.etudiants.attestation-frequentation', $document->etudiant_id),
                        'bulletin' => $document->document_id ? route('esbtp.bulletins.download', $document->document_id) : null,
                        default => null,
                    };
                @endphp
                <div class="rdx-row">
                    <div class="rdx-row-icon"><i class="fas fa-file-signature"></i></div>
                    <div class="rdx-row-main">
                        <div class="rdx-row-title">{{ ucfirst($document->document_type) }}</div>
                        <div class="rdx-row-meta">
                            Étudiant&nbsp;#{{ $document->etudiant_id }}
                            @if($document->updated_at)
                                · approuvé le {{ $document->updated_at->format('d/m/Y') }}
                            @endif
                        </div>
                    </div>
                    <div class="rdx-row-actions">
                        @if($printUrl)
                            <a class="rdx-act rdx-act--primary" href="{{ $printUrl }}">
                                <i class="fas fa-print"></i>Imprimer
                            </a>
                        @else
                            <span class="ss-unavailable" title="Document introuvable">
                                <i class="fas fa-triangle-exclamation"></i>Indisponible
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <x-role-empty
                    icon="fa-inbox"
                    title="Aucun document approuvé"
                    hint="Les documents validés par le responsable scolarité arriveront dans cette file." />
            @endforelse
        </x-role-panel>

        <x-role-panel
            icon="fa-pen-to-square"
            title="Saisie des notes autorisée"
            subtitle="Une classe disparaît de cette liste dès que sa fenêtre se referme"
            :count="$openWindows->count()">
            @forelse($openWindows as $window)
                @php $joursRestants = (int) now()->startOfDay()->diffInDays($window->ends_at, false); @endphp
                <div class="rdx-row">
                    <div class="rdx-row-icon"><i class="fas fa-chalkboard"></i></div>
                    <div class="rdx-row-main">
                        <div class="rdx-row-title">{{ $window->classe->name ?? 'Classe #'.$window->classe_id }}</div>
                        <div class="rdx-row-meta">
                            Jusqu'au {{ $window->ends_at->format('d/m/Y') }}
                            @if($joursRestants >= 0)
                                · <span class="ss-remaining {{ $joursRestants <= 2 ? 'ss-remaining--soon' : '' }}">
                                    {{ $joursRestants === 0 ? 'dernier jour' : $joursRestants.' jour'.($joursRestants > 1 ? 's' : '').' restant'.($joursRestants > 1 ? 's' : '') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="rdx-row-actions">
                        <a class="rdx-act rdx-act--primary" href="{{ route('esbtp.notes.index', ['classe_id' => $window->classe_id]) }}">
                            <i class="fas fa-pen"></i>Saisir
                        </a>
                    </div>
                </div>
            @empty
                <x-role-empty
                    icon="fa-lock"
                    title="Saisie fermée"
                    hint="Demandez au responsable scolarité d'ouvrir une fenêtre pour la classe concernée." />
            @endforelse
        </x-role-panel>

    </div>
</div>
@endsection

@push('styles')
<style>
    /* Namespace ss-* : tableau de bord service scolarité */
    .ss-unavailable {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .35rem .7rem; border-radius: 8px;
        font-size: .76rem; font-weight: 600;
        background: rgba(245, 158, 11, .1); color: #b45309;
        border: 1px solid rgba(245, 158, 11, .25);
    }
    .ss-remaining { font-weight: 600; color: #0453cb; }
    .ss-remaining--soon { color: #b45309; }
</style>
@endpush
