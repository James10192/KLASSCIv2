@extends('layouts.app')

@section('title', 'Gestion des annonces - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* Styles spécifiques pour la page des annonces */
.announcements-page {
    background: #f8fafc;
    min-height: 100vh;
    padding: 0;
}

.page-header {
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 100%);
    color: white !important;
    padding: 2rem 0;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.page-header * {
    color: white !important;
}

.page-header h1,
.page-header p,
.page-header i {
    color: white !important;
}

.page-header .container-fluid {
    max-width: 1200px;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white !important;
}

.page-subtitle {
    font-size: 1rem;
    opacity: 0.9;
    margin: 0.5rem 0 0;
    color: white !important;
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
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
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
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    border: 1px solid rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.card-header {
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    background: #fafbfc;
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
    border: 1px solid #d1d5db;
    border-radius: 8px;
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
    background: #f9fafb;
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
    background: #f9fafb;
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
    border-radius: 8px;
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

/* Responsive */
@media (max-width: 768px) {
    .page-header {
        padding: 1.5rem 0;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .card-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .search-container {
        max-width: 100%;
    }
    
    .modern-table {
        font-size: 0.875rem;
    }
    
    .modern-table thead th,
    .modern-table tbody td {
        padding: 0.75rem 0.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
    }
}
</style>
@endsection

@section('content')
<div class="announcements-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">
                        <i class="fas fa-bullhorn"></i>
                        Gestion des annonces
                    </h1>
                    <p class="page-subtitle">Communication et informations pour la communauté ESBTP</p>
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
        </div>
    </div>

    <div class="container-fluid" style="max-width: 1200px;">

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
                                <tr>
                                    <td>
                                        <div class="table-title">
                                            @if($annonce->priorite == 2)
                                                <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                            @endif
                                            {{ $annonce->titre }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge {{ $annonce->is_published ? 'success' : 'warning' }}">
                                            {{ $annonce->is_published ? 'Publiée' : 'Brouillon' }}
                                        </span>
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
                                                if ($annonce->is_published) {
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
                                                <button class="btn-action secondary disabled" disabled title="Modification impossible (plus de 15 minutes)">
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
// Fonction de recherche en temps réel
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
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
