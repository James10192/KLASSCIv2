@extends('layouts.app')

@section('title', 'Dashboard Responsable scolarite - KLASSCI')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Responsable scolarite</h1>
            <p class="text-muted mb-0">Approbations, fenetres de notes et inscriptions a valider.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <div class="text-muted small">Documents en attente</div>
                <div class="fs-3 fw-bold">{{ $pendingApprovals->count() }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <div class="text-muted small">Fenetres de notes ouvertes</div>
                <div class="fs-3 fw-bold">{{ $openWindows->count() }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="border rounded p-3 h-100">
                <div class="text-muted small">Inscriptions a valider</div>
                <div class="fs-3 fw-bold">{{ $pendingInscriptions }}</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <h2 class="h5">File d approbation</h2>
            @forelse($pendingApprovals as $approval)
                <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $approval->document_type }}</strong>
                        <div class="small text-muted">Etudiant #{{ $approval->etudiant_id }}</div>
                    </div>
                    @can('documents.approve')
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('esbtp.documents.approvals.approve', $approval) }}">
                            @csrf
                            <button class="btn btn-sm btn-primary" type="submit">Approuver</button>
                        </form>
                        <form method="POST" action="{{ route('esbtp.documents.approvals.reject', $approval) }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" type="submit">Refuser</button>
                        </form>
                    </div>
                    @endcan
                </div>
            @empty
                <p class="text-muted">Aucun document en attente.</p>
            @endforelse
        </div>
        <div class="col-lg-6">
            <h2 class="h5">Ouvrir une fenetre de notes</h2>
            @can('notes.window.manage')
            <form method="POST" action="{{ route('esbtp.notes-windows.store') }}" class="border rounded p-3 mb-3">
                @csrf
                <div class="mb-2">
                    <label class="form-label" for="classe_id">Classe</label>
                    <select class="form-select" id="classe_id" name="classe_id" required>
                        @foreach($classes as $classe)
                            <option value="{{ $classe->id }}">{{ $classe->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label" for="starts_at">Du</label>
                        <input class="form-control" type="date" id="starts_at" name="starts_at" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label" for="ends_at">Au</label>
                        <input class="form-control" type="date" id="ends_at" name="ends_at" required>
                    </div>
                </div>
                <button class="btn btn-primary" type="submit">Ouvrir</button>
            </form>
            @endcan

            @forelse($openWindows as $window)
                <div class="border rounded p-3 mb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <strong>{{ $window->classe->name ?? ('Classe #'.$window->classe_id) }}</strong>
                        <div class="small text-muted">{{ $window->starts_at->format('d/m/Y') }} - {{ $window->ends_at->format('d/m/Y') }}</div>
                    </div>
                    @can('notes.window.manage')
                    <form method="POST" action="{{ route('esbtp.notes-windows.close', $window) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary" type="submit">Fermer</button>
                    </form>
                    @endcan
                </div>
            @empty
                <p class="text-muted">Aucune fenetre ouverte.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection