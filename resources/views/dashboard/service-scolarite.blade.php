@extends('layouts.app')

@section('title', 'Dashboard Service scolarite - KLASSCI')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h1 class="h3 mb-1">Service scolarite</h1>
        <p class="text-muted mb-0">Documents approuves a imprimer et fenetres de notes ouvertes.</p>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
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
                <p class="text-muted">Aucun document approuve.</p>
            @endforelse
        </div>
        <div class="col-lg-6">
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
                <p class="text-muted">Aucune fenetre ouverte pour le moment.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
