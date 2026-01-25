@extends('layouts.app')

@section('title', 'Gestion des annonces - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* Styles spécifiques pour la page des annonces */
.announcements-page {
    background: var(--background);
    min-height: 100vh;
    padding: 0;
    overflow-x: hidden;
}

.announcement-header {
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.95), rgba(94, 145, 222, 0.95));
    color: white;
    padding: var(--space-lg) var(--space-xl);
    border-radius: var(--radius-medium);
    margin-bottom: var(--space-lg);
    box-shadow: 0 18px 36px rgba(4, 83, 203, 0.2);
}

.announcement-header .header-subtitle {
    color: rgba(255, 255, 255, 0.85);
}

.header-actions {
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.btn-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    border-radius: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-size: 0.9rem;
}

.btn-modern.primary {
    background: #0453cb;
    color: white;
    box-shadow: 0 2px 4px rgba(4, 83, 203, 0.3);
}

.btn-modern.primary:hover {
    background: #1b64d4;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(4, 83, 203, 0.4);
    color: white;
}

.btn-modern.secondary {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
}

.btn-modern.secondary:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: linear-gradient(145deg, #ffffff, #f8fafc);
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.25);
    transition: all 0.25s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.primary { background: linear-gradient(135deg, #0453cb, #1b64d4); }
.stat-icon.success { background: linear-gradient(135deg, #10b981, #059669); }
.stat-icon.warning { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-icon.danger { background: linear-gradient(135deg, #ef4444, #dc2626); }

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}

.stat-content p {
    margin: 0.25rem 0 0;
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
}

.content-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 16px 30px rgba(15, 23, 42, 0.08);
    border: 1px solid rgba(148, 163, 184, 0.2);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.08));
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.search-container {
    position: relative;
    max-width: 300px;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 12px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    background: white;
}

.search-input:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.1);
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.9rem;
}

.table-responsive {
    overflow-x: auto;
}

.modern-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.modern-table thead th {
    background: rgba(4, 83, 203, 0.06);
    padding: 1rem;
    font-weight: 600;
    color: #374151;
    text-align: left;
    border-bottom: 2px solid #e5e7eb;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.modern-table tbody tr {
    transition: background-color 0.2s ease;
}

.modern-table tbody tr:hover {
    background: rgba(4, 83, 203, 0.04);
}

/* Styles pour les annonces expirées */
.modern-table tbody tr.expired-row {
    background-color: rgba(239, 68, 68, 0.05);
    opacity: 0.8;
}

.modern-table tbody tr.expired-row:hover {
    background-color: rgba(239, 68, 68, 0.1);
}

.modern-table tbody tr.expired-row td {
    color: #6b7280;
}

.modern-table tbody tr.expired-row .table-title {
    text-decoration: line-through;
    color: #9ca3af;
}

.modern-table tbody td {
    padding: 1rem;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: middle;
}

.table-title {
    font-weight: 600;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-badge, .priority-badge, .type-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.status-badge.success {
    background: #dcfce7;
    color: #166534;
}

.status-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.priority-badge.priority-2 {
    background: #fecaca;
    color: #991b1b;
}

.priority-badge.priority-1 {
    background: #fed7aa;
    color: #9a3412;
}

.priority-badge.priority-0 {
    background: #e5e7eb;
    color: #374151;
}

.type-badge {
    background: #e0e7ff;
    color: #3730a3;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.2s ease;
    border: none;
    cursor: pointer;
    font-size: 0.875rem;
}

.btn-action.primary {
    background: #dbeafe;
    color: #1d4ed8;
}

.btn-action.primary:hover {
    background: #3b82f6;
    color: white;
}

.btn-action.secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-action.secondary:hover {
    background: #6b7280;
    color: white;
}

.btn-action.danger {
    background: #fee2e2;
    color: #dc2626;
}

.btn-action.danger:hover {
    background: #ef4444;
    color: white;
}

.btn-action.disabled {
    background: #f9fafb !important;
    color: #9ca3af !important;
    cursor: not-allowed !important;
    opacity: 0.5;
}

.btn-action.disabled:hover {
    background: #f9fafb !important;
    color: #9ca3af !important;
    transform: none !important;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state i {
    color: #d1d5db !important;
    margin-bottom: 1rem;
}

.empty-state h5 {
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: #9ca3af;
    margin: 0;
}

.alert-modern {
    padding: 1rem 1.25rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-weight: 500;
}

.alert-modern.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert-modern.error {
    background: #fecaca;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Vue grille pour mobile */
.annonces-grid {
    display: none;
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1rem;
}

.annonce-card {
    background: white;
    border-radius: 16px;
    padding: 1.25rem;
    border: 1px solid rgba(148, 163, 184, 0.25);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    transition: all 0.25s ease;
}

.annonce-card:hover {
    box-shadow: 0 18px 32px rgba(15, 23, 42, 0.12);
    transform: translateY(-4px);
}

.annonce-card.expired {
    background-color: rgba(239, 68, 68, 0.05);
    opacity: 0.8;
}

.annonce-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 0.75rem;
}

.annonce-card-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 1.125rem;
    margin: 0;
    flex: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.annonce-card-title.expired {
    text-decoration: line-through;
    color: #9ca3af;
}

.annonce-card-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.annonce-card-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: rgba(4, 83, 203, 0.06);
    border-radius: 12px;
}

.annonce-card-info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.annonce-card-info-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.annonce-card-info-value {
    font-size: 0.875rem;
    color: #1f2937;
    font-weight: 600;
}

.annonce-card-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

/* Responsive */
@media (max-width: 768px) {
    .announcement-header {
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-md);
    }

    .header-actions {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
        margin-top: 1rem;
    }

    .btn-modern {
        width: 100%;
        justify-content: center;
        font-size: 0.875rem;
        padding: 0.625rem 1rem;
    }

    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }

    .search-container {
        max-width: 100%;
    }

    /* Cacher le tableau et afficher la grille sur mobile */
    .table-responsive {
        display: none !important;
    }

    .annonces-grid {
        display: grid !important;
    }
}

@media (min-width: 769px) {
    .annonces-grid {
        display: none !important;
    }

    .table-responsive {
        display: block !important;
    }
}

@media (max-width: 576px) {
    .stat-card {
        flex-direction: column;
        text-align: center;
    }

    .annonce-card-info {
        grid-template-columns: 1fr;
    }

    .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi announcements-page">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
        <div class="dashboard-header announcement-header">
            <div class="header-left">
                <h1><i class="fas fa-bullhorn me-2"></i>Gestion des annonces</h1>
                <p class="header-subtitle">Diffusez des informations aux classes et aux étudiants.</p>
            </div>
            <div class="header-actions">
                <button class="btn-modern secondary" onclick="window.location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Actualiser
                </button>
                @if(auth()->user()->can('create_annonces'))
                <a href="{{ route('esbtp.annonces.create') }}" class="btn-modern primary">
                    <i class="fas fa-plus"></i>
                    Nouvelle annonce
                </a>
                @endif
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['total'] ?? 0 }}</h3>
                    <p>Total des annonces</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['published'] ?? 0 }}</h3>
                    <p>Annonces publiées</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['pending'] ?? 0 }}</h3>
                    <p>En attente</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
                <div class="stat-content">
                    <h3>{{ $stats['urgent'] ?? 0 }}</h3>
                    <p>Urgentes</p>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert-modern success">
                <i class="fas fa-check-circle"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert-modern error">
                <i class="fas fa-exclamation-circle"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        <!-- Contenu principal -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-list"></i>
                    Liste des annonces
                </h2>
                <div class="search-container">
                    <input type="text" id="searchInput" class="search-input" placeholder="Rechercher une annonce...">
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
            <!-- Vue tableau (desktop) -->
            <div class="table-responsive">
                <table class="modern-table" id="annoncesTable">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Statut</th>
                                <th>Priorité</th>
                                <th>Type</th>
                                <th>Date création</th>
                                <th>Expiration</th>
                                <th class="actions-col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($annonces as $annonce)
                                <tr class="{{ $annonce->isExpired() ? 'expired-row' : '' }}">
                                    <td>
                                        <div class="table-title">
                                            @if($annonce->priorite == 2)
                                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                            @endif
                                            {{ $annonce->titre }}
                                            @if($annonce->isExpired())
                                                <i class="fas fa-clock text-danger ms-2" title="Annonce expirée"></i>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($annonce->isExpired())
                                            <span class="status-badge danger">
                                                <i class="fas fa-clock me-1"></i>Expirée
                                            </span>
                                        @else
                                            <span class="status-badge {{ $annonce->is_published ? 'success' : 'warning' }}">
                                                {{ $annonce->is_published ? 'Publiée' : 'Brouillon' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="priority-badge priority-{{ $annonce->priorite }}">
                                            {{ $annonce->priorite == 2 ? 'Urgente' : ($annonce->priorite == 1 ? 'Importante' : 'Normale') }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="type-badge">
                                            {{ $annonce->type == 'general' ? 'Générale' : ucfirst($annonce->type) }}
                                        </span>
                                    </td>
                                    <td>{{ $annonce->created_at->format('d/m/Y H:i') }}</td>
                                    <td>{{ $annonce->date_expiration ? \Carbon\Carbon::parse($annonce->date_expiration)->format('d/m/Y H:i') : '-' }}</td>
                                    <td class="actions">
                                        <div class="action-buttons">
                                            <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-action primary" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @php
                                                $canEdit = true;

                                                // Bloquer l'édition si l'annonce est expirée
                                                if ($annonce->isExpired()) {
                                                    $canEdit = false;
                                                } elseif ($annonce->is_published) {
                                                    // Règle des 15 minutes pour les annonces publiées
                                                    $publishedAt = $annonce->date_publication && $annonce->date_publication > $annonce->created_at
                                                        ? $annonce->date_publication
                                                        : $annonce->created_at;
                                                    $canEdit = $publishedAt->diffInMinutes(now()) <= 15;
                                                }
                                            @endphp

                                            @if($canEdit)
                                                <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="btn-action secondary" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @else
                                                <button class="btn-action secondary disabled" disabled title="{{ $annonce->isExpired() ? 'Modification impossible (annonce expirée)' : 'Modification impossible (plus de 15 minutes)' }}">
                                                    <i class="fas fa-edit text-muted"></i>
                                                </button>
                                            @endif
                                            <button type="button" class="btn-action danger" onclick="deleteAnnonce({{ $annonce->id }})" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="empty-state">
                                            <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Aucune annonce trouvée</h5>
                                            <p class="text-muted">Commencez par créer votre première annonce.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                </table>
            </div>

            <!-- Vue grille (mobile) -->
            <div class="annonces-grid" id="annoncesGrid">
                @forelse($annonces as $annonce)
                    @php
                        $canEdit = true;
                        if ($annonce->isExpired()) {
                            $canEdit = false;
                        } elseif ($annonce->is_published) {
                            $publishedAt = $annonce->date_publication && $annonce->date_publication > $annonce->created_at
                                ? $annonce->date_publication
                                : $annonce->created_at;
                            $canEdit = $publishedAt->diffInMinutes(now()) <= 15;
                        }
                    @endphp

                    <div class="annonce-card {{ $annonce->isExpired() ? 'expired' : '' }}" data-title="{{ strtolower($annonce->titre) }}">
                        <div class="annonce-card-header">
                            <h3 class="annonce-card-title {{ $annonce->isExpired() ? 'expired' : '' }}">
                                @if($annonce->priorite == 2)
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                @endif
                                {{ $annonce->titre }}
                                @if($annonce->isExpired())
                                    <i class="fas fa-clock text-danger" title="Annonce expirée"></i>
                                @endif
                            </h3>
                        </div>

                        <div class="annonce-card-badges">
                            @if($annonce->isExpired())
                                <span class="status-badge danger">
                                    <i class="fas fa-clock"></i> Expirée
                                </span>
                            @else
                                <span class="status-badge {{ $annonce->is_published ? 'success' : 'warning' }}">
                                    {{ $annonce->is_published ? 'Publiée' : 'Brouillon' }}
                                </span>
                            @endif
                            <span class="priority-badge priority-{{ $annonce->priorite }}">
                                {{ $annonce->priorite == 2 ? 'Urgente' : ($annonce->priorite == 1 ? 'Importante' : 'Normale') }}
                            </span>
                            <span class="type-badge">
                                {{ $annonce->type == 'general' ? 'Générale' : ucfirst($annonce->type) }}
                            </span>
                        </div>

                        <div class="annonce-card-info">
                            <div class="annonce-card-info-item">
                                <div class="annonce-card-info-label">
                                    <i class="fas fa-calendar-plus"></i> Créée le
                                </div>
                                <div class="annonce-card-info-value">
                                    {{ $annonce->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                            <div class="annonce-card-info-item">
                                <div class="annonce-card-info-label">
                                    <i class="fas fa-calendar-times"></i> Expire le
                                </div>
                                <div class="annonce-card-info-value">
                                    {{ $annonce->date_expiration ? \Carbon\Carbon::parse($annonce->date_expiration)->format('d/m/Y H:i') : '-' }}
                                </div>
                            </div>
                        </div>

                        <div class="annonce-card-actions">
                            <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-action primary" title="Voir">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($canEdit)
                                <a href="{{ route('esbtp.annonces.edit', $annonce) }}" class="btn-action secondary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                            @else
                                <button class="btn-action secondary disabled" disabled title="{{ $annonce->isExpired() ? 'Modification impossible (annonce expirée)' : 'Modification impossible (plus de 15 minutes)' }}">
                                    <i class="fas fa-edit"></i>
                                </button>
                            @endif
                            <button type="button" class="btn-action danger" onclick="deleteAnnonce({{ $annonce->id }})" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="empty-state" style="grid-column: 1; padding: 3rem 1rem;">
                        <i class="fas fa-bullhorn fa-3x mb-3"></i>
                        <h5>Aucune annonce trouvée</h5>
                        <p>Commencez par créer votre première annonce.</p>
                    </div>
                @endforelse
            </div>
        </div>
        
        @if($annonces->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $annonces->links() }}
            </div>
        @endif
    </div>
    
    </div>
</div>
@endsection

@push('scripts')
<script>
// Fonction de recherche en temps réel (table + grille)
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();

    // Recherche dans le tableau (desktop)
    const table = document.getElementById('annoncesTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const titleCell = row.cells[0];
        if (titleCell && titleCell.textContent.toLowerCase().includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }

    // Recherche dans la grille (mobile)
    const grid = document.getElementById('annoncesGrid');
    const cards = grid.querySelectorAll('.annonce-card');
    cards.forEach(card => {
        const title = card.getAttribute('data-title');
        if (title && title.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
});

// Fonction de suppression d'annonce
function deleteAnnonce(id) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette annonce ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("esbtp.annonces.index") }}/' + id;

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';

        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);

        form.submit();
    }
}
</script>
@endpush
