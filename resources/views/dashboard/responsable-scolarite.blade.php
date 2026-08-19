@extends('layouts.app')

@section('title', 'Dashboard Responsable scolarite - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    body { background-color: var(--background); }
    .rs-header { background: var(--primary); color: #fff; border-radius: var(--radius-medium); padding: var(--space-xl) var(--space-lg); margin-bottom: var(--space-lg); }
    .rs-header h1 { color: #fff; margin: 0; font-size: 1.35rem; font-weight: 700; }
    .rs-header p { color: rgba(255,255,255,.82); margin: 6px 0 0; }
    .rs-kpi { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-medium); padding: var(--space-lg); min-height: 120px; }
    .rs-kpi-label { font-size: .78rem; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; }
    .rs-kpi-value { font-size: 1.8rem; font-weight: 700; margin-top: 8px; }
    .rs-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-medium); padding: var(--space-lg); height: 100%; }
</style>
@endsection

@section('content')
<div class="main-content">
    <div class="rs-header">
        <h1>Responsable scolarite</h1>
        <p>Approbations, fenetres de notes et inscriptions a valider. Aucun montant.</p>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="rs-kpi"><div class="rs-kpi-label">Documents en attente</div><div class="rs-kpi-value">{{ $pendingApprovals->count() }}</div></div></div>
        <div class="col-md-4"><div class="rs-kpi"><div class="rs-kpi-label">Fenetres de notes ouvertes</div><div class="rs-kpi-value">{{ $openWindows->count() }}</div></div></div>
        <div class="col-md-4"><div class="rs-kpi"><div class="rs-kpi-label">Inscriptions a valider</div><div class="rs-kpi-value">{{ $pendingInscriptions }}</div></div></div>
    </div>
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="rs-card">
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
                    <p class="text-muted mb-0">Aucun document en attente.</p>
                @endforelse
            </div>
        </div>
        <div class="col-lg-6">
            <div class="rs-card">
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
                    <p class="text-muted mb-0">Aucune fenetre ouverte.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
