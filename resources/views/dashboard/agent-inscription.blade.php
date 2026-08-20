@extends('layouts.app')

@section('title', 'Dashboard Agent d inscription - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    body { background-color: var(--background); }
    .ai-header { background: var(--primary); color: #fff; border-radius: var(--radius-medium); padding: var(--space-xl) var(--space-lg); margin-bottom: var(--space-lg); }
    .ai-header h1 { color: #fff; margin: 0; font-size: 1.35rem; font-weight: 700; }
    .ai-header p { color: rgba(255,255,255,.82); margin: 6px 0 0; }
    .ai-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-medium); padding: var(--space-lg); height: 100%; }
    .ai-kpi { font-size: 2rem; font-weight: 700; color: var(--primary); }
    .ai-label { color: var(--muted); font-size: .85rem; font-weight: 600; text-transform: uppercase; }
</style>
@endsection

@section('content')
<div class="main-content">
    <div class="ai-header">
        <h1>Agent d inscription</h1>
        <p>File des dossiers a creer, editer et valider apres encaissement. Aucun montant affiche.</p>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="ai-card">
                <div class="ai-label">A valider</div>
                <div class="ai-kpi">{{ $pendingValidation }}</div>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'en_attente']) }}">Ouvrir la file</a>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ai-card">
                <div class="ai-label">En attente de paiement</div>
                <div class="ai-kpi">{{ $waitingPayment }}</div>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'en_attente']) }}">Voir les dossiers</a>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ai-card">
                <div class="ai-label">Validees aujourd hui</div>
                <div class="ai-kpi">{{ $validatedToday }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="ai-card">
                <div class="ai-label">Sous reserve</div>
                <div class="ai-kpi">{{ $sousReserve }}</div>
                <a href="{{ route('esbtp.inscriptions.sous-reserve') }}">Ouvrir la file</a>
            </div>
        </div>
    </div>
    <div class="ai-card">
        <h2 class="h5">Actions rapides</h2>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn-acasi" href="{{ route('esbtp.inscriptions.create') }}">Nouvelle inscription</a>
            <a class="btn-acasi secondary" href="{{ route('esbtp.inscriptions.index') }}">Liste des inscriptions</a>
            <a class="btn-acasi secondary" href="{{ route('esbtp.etudiants.index') }}">Liste des etudiants</a>
            <a class="btn-acasi secondary" href="{{ route('esbtp.reinscription.index') }}">Reinscriptions</a>
        </div>
    </div>
</div>
@endsection
