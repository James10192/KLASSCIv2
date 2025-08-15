@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .filtres-section {
        margin-bottom: var(--space-xl);
        padding: var(--space-lg);
        background: var(--background);
        border-radius: var(--radius-medium);
        border: 1px solid #e5e7eb;
    }
    
    .filtres-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        align-items: end;
    }
    
    .filtre-item {
        display: flex;
        flex-direction: column;
    }
    
    .filtre-label {
        font-size: var(--text-small);
        font-weight: 600;
        color: var(--text-secondary);
        margin-bottom: var(--space-xs);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .form-select-moderne,
    .form-input-moderne {
        padding: var(--space-sm) var(--space-md);
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-small);
        font-size: var(--text-normal);
        background: var(--surface);
        transition: all 0.2s ease;
    }
    
    .form-select-moderne:focus,
    .form-input-moderne:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
    }
    
    .card-header-moderne {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--space-lg);
        border-bottom: 1px solid #e5e7eb;
    }
    
    .card-body-moderne {
        padding: var(--space-lg);
    }
    
    .actions-top {
        display: flex;
        gap: var(--space-sm);
    }
    
    .main-content {
        flex: 1;
        padding: var(--space-lg);
    }
    
    .evenement-card {
        padding: var(--space-lg);
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-md);
        transition: all 0.2s ease;
        background: var(--surface);
    }
    
    .evenement-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-1px);
    }
    
    .evenement-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: var(--space-md);
    }
    
    .evenement-info {
        display: flex;
        gap: var(--space-md);
        align-items: start;
        flex: 1;
    }
    
    .evenement-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: var(--background);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    
    .evenement-details {
        flex: 1;
    }
    
    .evenement-title {
        font-size: var(--title-section);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-xs);
    }
    
    .evenement-description {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin: 0;
        line-height: 1.4;
    }
    
    .evenement-actions {
        display: flex;
        gap: var(--space-xs);
        align-items: center;
    }
    
    .btn-sm {
        padding: var(--space-xs) var(--space-sm);
        font-size: var(--text-small);
    }
    
    .evenement-meta {
        display: flex;
        gap: var(--space-md);
        align-items: center;
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }
    
    .meta-item i {
        color: var(--text-muted);
    }
    
    .meta-value {
        font-weight: 500;
    }
    
    .meta-item.participants {
        margin-left: auto;
    }
    
    .badge-moderne {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: var(--text-small);
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-primary { background: var(--primary); color: white; }
    .badge-success { background: var(--success); color: white; }
    .badge-warning { background: var(--warning); color: white; }
    .badge-danger { background: var(--danger); color: white; }
    .badge-secondary { background: var(--neutral); color: white; }
    .badge-info { background: var(--accent-blue); color: white; }
    
    .evenements-liste {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
    }
    
    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }
    
    .empty-icon {
        font-size: 3rem;
        margin-bottom: var(--space-md);
        color: var(--text-muted);
    }
    
    .empty-title {
        font-size: var(--title-section);
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }
    
    .empty-description {
        font-size: var(--text-normal);
        margin-bottom: var(--space-lg);
    }
    
    .pagination-wrapper {
        display: flex;
        justify-content: center;
        margin-top: var(--space-lg);
    }
    
    .dropdown-menu {
        background: var(--surface);
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-small);
        box-shadow: var(--shadow-elevated);
        padding: var(--space-xs);
    }
    
    .dropdown-item {
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-small);
        color: var(--text-primary);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background: var(--background);
        color: var(--text-primary);
    }
    
    .dropdown-item.text-danger {
        color: var(--danger);
    }
    
    .dropdown-item.text-danger:hover {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
    }
    
    .dropdown-divider {
        height: 1px;
        background: #e5e7eb;
        margin: var(--space-xs) 0;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header et navigation du planning -->
        <x-planning-header 
            title="Événements Académiques" 
            subtitle="Gestion des événements et dates importantes du calendrier académique"
            active-tab="evenements"
            :annee-selectionnee="$anneeSelectionnee"
            :annees="$annees"
        />

        @if($anneeSelectionnee)
            <!-- Raccourcis pour événements manquants -->
            @php
                $hasRentree = \App\Models\ESBTPEvenementAcademique::where('annee_universitaire_id', $anneeSelectionnee->id)
                    ->where('type', 'rentree')
                    ->exists();
                $hasFermeture = \App\Models\ESBTPEvenementAcademique::where('annee_universitaire_id', $anneeSelectionnee->id)
                    ->where('type', 'fermeture')
                    ->exists();
            @endphp
            
            @if(!$hasRentree || !$hasFermeture)
                <div class="card-moderne mb-lg" style="border-left: 4px solid var(--warning);">
                    <div class="card-body-moderne">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-warning mb-2">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Événements manquants pour {{ $anneeSelectionnee->name }}
                                </h6>
                                <p class="text-muted mb-0">
                                    @if(!$hasRentree && !$hasFermeture)
                                        Les événements de rentrée et de fermeture ne sont pas encore définis.
                                    @elseif(!$hasRentree)
                                        L'événement de rentrée n'est pas encore défini.
                                    @else
                                        L'événement de fermeture n'est pas encore défini.
                                    @endif
                                </p>
                            </div>
                            <div class="d-flex gap-2">
                                @if(!$hasRentree)
                                    <a href="{{ route('esbtp.evenements-academiques.create-quick', ['type' => 'rentree', 'annee_id' => $anneeSelectionnee->id]) }}" 
                                       class="btn-acasi success">
                                        <i class="fas fa-graduation-cap me-2"></i>
                                        Créer Rentrée
                                    </a>
                                @endif
                                @if(!$hasFermeture)
                                    <a href="{{ route('esbtp.evenements-academiques.create-quick', ['type' => 'fermeture', 'annee_id' => $anneeSelectionnee->id]) }}" 
                                       class="btn-acasi secondary">
                                        <i class="fas fa-flag-checkered me-2"></i>
                                        Créer Fermeture
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <div class="card-moderne">
            <div class="card-header-moderne">
                <div class="actions-top">
                    <a href="{{ route('esbtp.evenements-academiques.create', ['annee_id' => $anneeSelectionnee?->id]) }}" class="btn-acasi primary">
                        <i class="fas fa-plus me-2"></i>
                        Nouvel Événement
                    </a>
                </div>
            </div>
                
            <div class="card-body-moderne">
                <!-- Filtres -->
                <div class="filtres-section">
                    <form method="GET" action="{{ route('esbtp.evenements-academiques.index') }}">
                        <div class="filtres-grid">
                            <div class="filtre-item">
                                <label class="filtre-label">Année universitaire</label>
                                <select name="annee_id" class="form-select-moderne">
                                    <option value="">Toutes les années</option>
                                    @foreach($annees as $annee)
                                        <option value="{{ $annee->id }}" {{ $anneeSelectionnee && $anneeSelectionnee->id == $annee->id ? 'selected' : '' }}>
                                            {{ $annee->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filtre-item">
                                <label class="filtre-label">Type d'événement</label>
                                <select name="type" class="form-select-moderne">
                                    <option value="">Tous les types</option>
                                    @foreach(\App\Models\ESBTPEvenementAcademique::TYPES as $key => $label)
                                        <option value="{{ $key }}" {{ $type == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filtre-item">
                                <label class="filtre-label">Statut</label>
                                <select name="statut" class="form-select-moderne">
                                    <option value="">Tous les statuts</option>
                                    @foreach(\App\Models\ESBTPEvenementAcademique::STATUTS as $key => $label)
                                        <option value="{{ $key }}" {{ $statut == $key ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filtre-item">
                                <label class="filtre-label">Recherche</label>
                                <input type="text" name="search" class="form-input-moderne" 
                                       placeholder="Rechercher..." 
                                       value="{{ $search }}">
                            </div>
                            <div class="filtre-item">
                                <label class="filtre-label">&nbsp;</label>
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-search"></i> Filtrer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Statistiques -->
                @if($stats['total_evenements'] > 0)
                <div class="kpi-grid">
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">Total Événements</div>
                        <div class="kpi-value color-primary">{{ $stats['total_evenements'] }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-calendar"></i>
                            <span>Cette année</span>
                        </div>
                    </div>
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">Confirmés</div>
                        <div class="kpi-value color-success">{{ $stats['evenements_confirmes'] }}</div>
                        <div class="kpi-trend positive">
                            <i class="fas fa-check"></i>
                            <span>Validés</span>
                        </div>
                    </div>
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">À venir</div>
                        <div class="kpi-value color-accent">{{ $stats['evenements_a_venir'] }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-clock"></i>
                            <span>Prochainement</span>
                        </div>
                    </div>
                    <div class="kpi-card card-moderne">
                        <div class="kpi-title">En cours</div>
                        <div class="kpi-value color-warning">{{ $stats['evenements_en_cours'] }}</div>
                        <div class="kpi-trend">
                            <i class="fas fa-play"></i>
                            <span>Actifs</span>
                        </div>
                    </div>
                </div>
                @endif

                    <!-- Actions en lot -->
                    @if($evenements->count() > 0)
                    <div class="bulk-actions-bar card-moderne mb-lg" style="display: none;" id="bulk-actions-bar">
                        <div class="p-md d-flex justify-content-between align-items-center">
                            <span id="selected-count">0 événement(s) sélectionné(s)</span>
                            <div class="d-flex gap-2">
                                <form id="bulk-form" method="POST" action="{{ route('esbtp.evenements-academiques.bulk-action') }}" style="display: inline;">
                                    @csrf
                                    <input type="hidden" name="action" id="bulk-action">
                                    <input type="hidden" name="status" id="bulk-status">
                                    <div id="selected-events"></div>
                                    
                                    <select class="form-select-moderne" id="bulk-status-select" style="display: none;">
                                        <option value="">Choisir un statut</option>
                                        @foreach(\App\Models\ESBTPEvenementAcademique::STATUTS as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    
                                    <button type="button" class="btn-acasi warning" onclick="changeBulkStatus()">
                                        <i class="fas fa-edit me-2"></i> Changer statut
                                    </button>
                                    <button type="button" class="btn-acasi danger" onclick="deleteBulkEvents()">
                                        <i class="fas fa-trash me-2"></i> Supprimer
                                    </button>
                                </form>
                                <button type="button" class="btn-acasi secondary" onclick="clearSelection()">
                                    <i class="fas fa-times me-2"></i> Annuler
                                </button>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Liste des événements -->
                    <div class="evenements-liste">
                        @forelse($evenements as $evenement)
                        <div class="evenement-card" data-event-id="{{ $evenement->id }}">
                            <div class="evenement-header">
                                <div class="evenement-info">
                                    <div class="d-flex align-items-center me-3">
                                        <input type="checkbox" class="event-checkbox me-2" value="{{ $evenement->id }}" onchange="updateBulkActions()">
                                    </div>
                                    <div class="evenement-icon">
                                        <i class="fas fa-{{ $evenement->icone }} color-{{ $evenement->couleur }}"></i>
                                    </div>
                                    <div class="evenement-details">
                                        <h6 class="evenement-title">{{ $evenement->titre }}</h6>
                                        <p class="evenement-description">{{ Str::limit($evenement->description, 80) }}</p>
                                    </div>
                                </div>
                                <div class="evenement-actions">
                                    <a href="{{ route('esbtp.evenements-academiques.show', $evenement) }}" 
                                       class="btn-acasi secondary btn-sm" title="Voir">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($evenement->isEditable())
                                        <a href="{{ route('esbtp.evenements-academiques.edit', $evenement) }}" 
                                           class="btn-acasi primary btn-sm" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    <div class="dropdown">
                                        <button class="btn-acasi secondary btn-sm dropdown-toggle" 
                                                type="button" data-toggle="dropdown" title="Plus d'actions">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <form method="POST" action="{{ route('esbtp.evenements-academiques.duplicate', $evenement) }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="fas fa-copy me-2"></i> Dupliquer
                                                </button>
                                            </form>
                                            @if($evenement->isDeletable())
                                                <div class="dropdown-divider"></div>
                                                <form method="POST" 
                                                      action="{{ route('esbtp.evenements-academiques.destroy', $evenement) }}"
                                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash me-2"></i> Supprimer
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="evenement-meta">
                                <div class="meta-item">
                                    <i class="fas fa-calendar-day"></i>
                                    <span class="meta-value">{{ $evenement->date_formatee }}</span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <span class="meta-value">{{ $evenement->duree }}</span>
                                </div>
                                <div class="meta-item">
                                    <span class="badge-moderne badge-{{ $evenement->couleur }}">
                                        {{ $evenement->type_libelle }}
                                    </span>
                                </div>
                                <div class="meta-item">
                                    @php
                                        $statusColor = match($evenement->statut) {
                                            'planifie' => 'secondary',
                                            'confirme' => 'success',
                                            'annule' => 'danger',
                                            'reporte' => 'warning',
                                            'termine' => 'info',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge-moderne badge-{{ $statusColor }}">
                                        {{ $evenement->statut_libelle }}
                                    </span>
                                </div>
                                <div class="meta-item participants">
                                    <i class="fas fa-users"></i>
                                    <span class="meta-value">{{ $evenement->participants_formatted }}</span>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <h5 class="empty-title">Aucun événement trouvé</h5>
                            <p class="empty-description">Créez votre premier événement académique</p>
                            <a href="{{ route('esbtp.evenements-academiques.create') }}" class="btn-acasi primary">
                                <i class="fas fa-plus me-2"></i> Créer un événement
                            </a>
                        </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    @if($evenements->hasPages())
                        <div class="pagination-wrapper">
                            {{ $evenements->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Auto-submit form when filters change
    $('select[name="annee_id"], select[name="type"], select[name="statut"]').change(function() {
        $(this).closest('form').submit();
    });
});

// Bulk actions functionality
function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.event-checkbox:checked');
    const count = checkboxes.length;
    const bulkBar = document.getElementById('bulk-actions-bar');
    const countSpan = document.getElementById('selected-count');
    const selectedEventsDiv = document.getElementById('selected-events');
    
    if (count > 0) {
        bulkBar.style.display = 'block';
        countSpan.textContent = count + ' événement(s) sélectionné(s)';
        
        // Clear and add hidden inputs for selected events
        selectedEventsDiv.innerHTML = '';
        checkboxes.forEach(checkbox => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'events[]';
            input.value = checkbox.value;
            selectedEventsDiv.appendChild(input);
        });
    } else {
        bulkBar.style.display = 'none';
    }
}

function clearSelection() {
    document.querySelectorAll('.event-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateBulkActions();
}

function changeBulkStatus() {
    const statusSelect = document.getElementById('bulk-status-select');
    const bulkStatus = document.getElementById('bulk-status');
    const bulkAction = document.getElementById('bulk-action');
    
    statusSelect.style.display = 'inline-block';
    statusSelect.onchange = function() {
        if (this.value) {
            if (confirm('Êtes-vous sûr de vouloir changer le statut des événements sélectionnés ?')) {
                bulkAction.value = 'change_status';
                bulkStatus.value = this.value;
                document.getElementById('bulk-form').submit();
            }
        }
    };
}

function deleteBulkEvents() {
    if (confirm('Êtes-vous sûr de vouloir supprimer les événements sélectionnés ? Cette action est irréversible.')) {
        document.getElementById('bulk-action').value = 'delete';
        document.getElementById('bulk-form').submit();
    }
}
</script>
@endpush