@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .codes-generate-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 4px 16px rgba(0,0,0,0.07);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
    }

    .codes-table-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 4px 16px rgba(0,0,0,0.07);
        overflow: hidden;
    }

    .codes-table-header {
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid rgba(0,0,0,0.06);
        background: rgba(4, 83, 203, 0.03);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 700;
        font-size: 1rem;
        color: var(--text-primary);
    }

    .codes-table {
        width: 100%;
        border-collapse: collapse;
    }

    .codes-table thead th {
        background: var(--surface, #f8fafc);
        color: var(--text-secondary);
        font-weight: 600;
        font-size: var(--text-sm);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: var(--space-sm) var(--space-md);
        border-bottom: 2px solid rgba(0,0,0,0.07);
        white-space: nowrap;
    }

    .codes-table tbody td {
        padding: var(--space-sm) var(--space-md);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .codes-table tbody tr:last-child td { border-bottom: none; }

    .codes-table tbody tr:hover { background: rgba(4,83,203,0.03); }

    .code-value {
        font-family: monospace;
        font-weight: 700;
        color: var(--primary);
        background: rgba(4,83,203,0.08);
        padding: 0.2rem 0.5rem;
        border-radius: var(--radius-small);
        font-size: 1rem;
        letter-spacing: 1px;
    }

    .section-title {
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header KLASSCI -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-key me-3"></i>Codes d'assiduité</h1>
                <p class="header-subtitle">Générez et suivez les codes d'assiduité pour la présence</p>
            </div>
            <div class="header-actions">
                <span class="badge" style="background: rgba(255,255,255,0.2); color: white; padding: var(--space-sm) var(--space-md); border-radius: var(--radius-medium);">
                    <i class="fas fa-calendar me-1"></i>{{ now()->format('d/m/Y') }}
                </span>
            </div>
        </div>

        <!-- Alertes -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Formulaire génération -->
        <div class="codes-generate-card">
            <div class="section-title">
                <i class="fas fa-plus-circle text-primary"></i>
                Générer un nouveau code
            </div>
            <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4 col-sm-6">
                    <label for="date" class="form-label fw-semibold">Date</label>
                    <input type="date" class="form-control" id="date" name="date" required
                           min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-key me-2"></i>Générer un code
                    </button>
                </div>
            </form>
        </div>

        <!-- Table des codes -->
        <div class="codes-table-card">
            <div class="codes-table-header">
                <i class="fas fa-list text-primary"></i>
                Liste des codes générés
            </div>
            <div class="table-responsive">
                <table class="codes-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Expire le</th>
                            <th>Statut</th>
                            <th>Utilisé par</th>
                            <th>Tentatives</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($codes as $code)
                        <tr>
                            <td><span class="code-value">{{ $code->code }}</span></td>
                            <td>{{ $code->date->format('d/m/Y') }}</td>
                            <td style="color: var(--text-secondary);">{{ $code->expires_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($code->is_used)
                                    <span class="badge bg-success">Utilisé</span>
                                @elseif($code->expires_at->isPast())
                                    <span class="badge bg-danger">Expiré</span>
                                @else
                                    <span class="badge" style="background: var(--primary); color: white;">Valide</span>
                                @endif
                            </td>
                            <td>
                                @if($code->usedByTeacher)
                                    <span style="font-weight: 500;">{{ $code->usedByTeacher->nom }} {{ $code->usedByTeacher->prenoms }}</span>
                                @else
                                    <span style="color: var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td>
                                @if($code->attempts >= 3)
                                    <span class="badge bg-danger">{{ $code->attempts }}</span>
                                @else
                                    <span style="font-weight: 600;">{{ $code->attempts }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                                <i class="fas fa-inbox fa-2x mb-2 d-block" style="opacity: 0.4;"></i>
                                Aucun code généré pour l'instant.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-center p-3">
                {{ $codes->links() }}
            </div>
        </div>

    </div>
</div>
@endsection
