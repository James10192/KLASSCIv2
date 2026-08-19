@extends('layouts.app')

@section('title', 'Dashboard Service scolarite - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    body { background-color: var(--background); }
    .ss-header { background: var(--primary); color: #fff; border-radius: var(--radius-medium); padding: var(--space-xl) var(--space-lg); margin-bottom: var(--space-lg); }
    .ss-header h1 { color: #fff; margin: 0; font-size: 1.35rem; font-weight: 700; }
    .ss-header p { color: rgba(255,255,255,.82); margin: 6px 0 0; }
    .ss-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-medium); padding: var(--space-lg); height: 100%; }
</style>
@endsection

@section('content')
<div class="main-content">
    <div class="ss-header">
        <h1>Service scolarite</h1>
        <p>Documents approuves a imprimer et saisie des notes uniquement pendant la fenetre ouverte.</p>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="ss-card">
                <h2 class="h5">File d impression</h2>
                @forelse($approvedDocuments as $document)
                    <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $document->document_type }}</strong>
                            <div class="small text-muted">Etudiant #{{ $document->etudiant_id }}</div>
                        </div>
                        @php
                            $printUrl = match ($document->document_type) {
                                'certificat' => route('esbtp.etudiants.certificat', $document->etudiant_id),
                                'attestation' => route('esbtp.etudiants.attestation-frequentation', $document->etudiant_id),
                                'bulletin' => $document->document_id ? route('esbtp.bulletins.download', $document->document_id) : null,
                                default => null,
                            };
                        @endphp
                        @if($printUrl)
                            <a class="btn btn-sm btn-primary" href="{{ $printUrl }}">Imprimer</a>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucun document approuve.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="ss-card">
                <h2 class="h5">Saisie des notes autorisee</h2>
                @forelse($openWindows as $window)
                    <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $window->classe->name ?? ('Classe #'.$window->classe_id) }}</strong>
                            <div class="small text-muted">Jusqu au {{ $window->ends_at->format('d/m/Y') }}</div>
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('esbtp.notes.index', ['classe_id' => $window->classe_id]) }}">Saisir</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">Aucune fenetre ouverte pour le moment.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
