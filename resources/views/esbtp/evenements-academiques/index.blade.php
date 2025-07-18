@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        Événements Académiques
                    </h3>
                    <a href="{{ route('esbtp.evenements-academiques.create', ['annee_id' => $anneeSelectionnee?->id]) }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Nouvel Événement
                    </a>
                </div>
                
                <div class="card-body">
                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <form method="GET" action="{{ route('esbtp.evenements-academiques.index') }}">
                                <div class="row">
                                    <div class="col-md-3">
                                        <select name="annee_id" class="form-control">
                                            <option value="">Toutes les années</option>
                                            @foreach($annees as $annee)
                                                <option value="{{ $annee->id }}" {{ $anneeSelectionnee && $anneeSelectionnee->id == $annee->id ? 'selected' : '' }}>
                                                    {{ $annee->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="type" class="form-control">
                                            <option value="">Tous les types</option>
                                            @foreach(\App\Models\ESBTPEvenementAcademique::TYPES as $key => $label)
                                                <option value="{{ $key }}" {{ $type == $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="statut" class="form-control">
                                            <option value="">Tous les statuts</option>
                                            @foreach(\App\Models\ESBTPEvenementAcademique::STATUTS as $key => $label)
                                                <option value="{{ $key }}" {{ $statut == $key ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="search" class="form-control" 
                                               placeholder="Rechercher..." 
                                               value="{{ $search }}">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-secondary">
                                            <i class="fas fa-search"></i> Filtrer
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    @if($stats['total_evenements'] > 0)
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-calendar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total</span>
                                    <span class="info-box-number">{{ $stats['total_evenements'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Confirmés</span>
                                    <span class="info-box-number">{{ $stats['evenements_confirmes'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">À venir</span>
                                    <span class="info-box-number">{{ $stats['evenements_a_venir'] }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-play"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">En cours</span>
                                    <span class="info-box-number">{{ $stats['evenements_en_cours'] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Liste des événements -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Événement</th>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Participants</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($evenements as $evenement)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-{{ $evenement->icone }} text-{{ $evenement->couleur }} mr-2"></i>
                                            <div>
                                                <strong>{{ $evenement->titre }}</strong>
                                                <br>
                                                <small class="text-muted">{{ Str::limit($evenement->description, 50) }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light">{{ $evenement->date_formatee }}</span>
                                        <br>
                                        <small class="text-muted">{{ $evenement->duree }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $evenement->couleur }}">
                                            {{ $evenement->type_libelle }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusColor = match($evenement->statut) {
                                                'planifie' => 'secondary',
                                                'confirme' => 'success',
                                                'annule' => 'danger',
                                                'reporte' => 'warning',
                                                'termine' => 'info',
                                                default => 'light'
                                            };
                                        @endphp
                                        <span class="badge badge-{{ $statusColor }}">
                                            {{ $evenement->statut_libelle }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $evenement->participants_formatted }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('esbtp.evenements-academiques.show', $evenement) }}" 
                                               class="btn btn-sm btn-info" title="Voir">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            @if($evenement->isEditable())
                                                <a href="{{ route('esbtp.evenements-academiques.edit', $evenement) }}" 
                                                   class="btn btn-sm btn-warning" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            @endif
                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" 
                                                    data-toggle="dropdown" title="Plus d'actions">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu">
                                                <form method="POST" action="{{ route('esbtp.evenements-academiques.duplicate', $evenement) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-copy mr-2"></i> Dupliquer
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
                                                            <i class="fas fa-trash mr-2"></i> Supprimer
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">Aucun événement trouvé</h5>
                                        <p class="text-muted">Créez votre premier événement académique</p>
                                        <a href="{{ route('esbtp.evenements-academiques.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus mr-2"></i> Créer un événement
                                        </a>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($evenements->hasPages())
                        <div class="d-flex justify-content-center">
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
</script>
@endpush