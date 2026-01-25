@extends('layouts.app')

@section('title', 'Saisie groupée des notes - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .notes-bulk-page {
        padding: 1.5rem;
    }

    .bulk-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .bulk-header h1 {
        font-size: 1.6rem;
        font-weight: 700;
        margin: 0;
    }

    .bulk-header .subtitle {
        color: var(--muted);
        margin-top: 0.35rem;
    }

    .bulk-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.25rem;
    }

    .bulk-card {
        background: #fff;
        border-radius: 18px;
        border: 1px solid rgba(148, 163, 184, 0.35);
        padding: 1.25rem;
        box-shadow: 0 10px 26px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .bulk-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 32px rgba(15, 23, 42, 0.16);
    }

    .bulk-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .bulk-type {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(4, 83, 203, 0.12);
        color: #1d4ed8;
    }

    .bulk-date {
        font-size: 0.8rem;
        color: var(--muted);
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.3rem 0.6rem;
        border-radius: 999px;
        background: rgba(148, 163, 184, 0.16);
    }

    .bulk-title {
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 0.5rem;
    }

    .bulk-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .bulk-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.6rem;
        border-radius: 999px;
        background: rgba(4, 83, 203, 0.08);
        color: var(--text);
        font-size: 0.78rem;
        border: 1px solid rgba(4, 83, 203, 0.15);
    }

    .bulk-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
    }

    .bulk-actions .status {
        font-size: 0.75rem;
        padding: 0.3rem 0.6rem;
        border-radius: 999px;
        font-weight: 600;
        color: #047857;
        background: rgba(16, 185, 129, 0.12);
        border: 1px solid rgba(16, 185, 129, 0.25);
    }

    .bulk-actions .btn {
        border-radius: 10px;
        padding: 0.5rem 0.9rem;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .bulk-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi notes-bulk-page">
    <div class="bulk-header">
        <div>
            <h1><i class="fas fa-pen-to-square me-2"></i>Saisie groupée des notes</h1>
            <div class="subtitle">Sélectionnez une évaluation passée pour saisir les notes en groupe.</div>
        </div>
        <a href="{{ route('esbtp.notes.index') }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i>Retour
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>Veuillez corriger les erreurs.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($evaluations->isEmpty())
        <div class="main-card">
            <div class="main-card-body text-center">
                <i class="fas fa-clipboard-check fa-2x text-muted mb-2"></i>
                <h4>Aucune évaluation disponible</h4>
                <p class="text-muted">Les notes ne peuvent être saisies que pour les évaluations passées et publiées.</p>
            </div>
        </div>
    @else
        <div class="bulk-grid">
            @foreach($evaluations as $evaluation)
                <div class="bulk-card">
                    <div class="bulk-card-header">
                        <span class="bulk-type">
                            <i class="fas fa-file-circle-check"></i>
                            {{ ucfirst($evaluation->type) }}
                        </span>
                        <span class="bulk-date">
                            <i class="fas fa-calendar-day"></i>
                            {{ $evaluation->date_evaluation?->format('d/m/Y') }}
                        </span>
                    </div>
                    <div class="bulk-title">{{ $evaluation->titre }}</div>
                    <div class="bulk-meta">
                        <span class="bulk-pill">
                            <i class="fas fa-people-group"></i>
                            {{ $evaluation->classe->name ?? 'Classe' }}
                        </span>
                        <span class="bulk-pill">
                            <i class="fas fa-book-open"></i>
                            {{ $evaluation->matiere->name ?? 'Matière' }}
                        </span>
                        <span class="bulk-pill">
                            <i class="fas fa-scale-balanced"></i>
                            /{{ $evaluation->bareme ?? 20 }}
                        </span>
                    </div>
                    <div class="bulk-actions">
                        <span class="status">Évaluation passée</span>
                        <a href="{{ route('notes.saisie-rapide', $evaluation) }}" class="btn btn-primary">
                            <i class="fas fa-users me-1"></i>Saisie groupée
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
