@extends('layouts.app')

@section('title', 'Gestion des matières - ' . $classe->name . ' - ESBTP-yAKRO')

@section('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="main-content">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1><i class="fas fa-graduation-cap me-2"></i>Gestion des Matières</h1>
            <p class="header-subtitle">{{ $classe->name }}</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="btn-acasi secondary me-2">
                <i class="fas fa-eye me-1"></i>Détails de la classe
            </a>
            <a href="{{ route('esbtp.student.classes.index') }}" class="btn-acasi secondary">
                <i class="fas fa-list me-1"></i>Liste des classes
            </a>
        </div>
    </div>

    <!-- Success Alert -->
    @if(session('success'))
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--success);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle color-success me-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        </div>
    @endif

    <!-- Error Alert -->
    @if(session('error'))
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--danger);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle color-danger me-2"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        </div>
    @endif
    <!-- Info Alert -->
    @if($classe->matieres->isEmpty())
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--info);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle color-info me-2"></i>
                    <span>Aucune matière n'est associée à cette classe. Utilisez le formulaire ci-dessous pour attacher des matières.</span>
                </div>
            </div>
        </div>
    @endif

    @if(!$allMatieres->isEmpty())
        <div class="card-moderne mb-lg" style="border-left: 4px solid var(--info);">
            <div class="p-lg">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle color-info me-2"></i>
                    <span>Gérez les matières de la classe <strong>{{ $classe->name }}</strong> en ajustant leurs coefficients. La modification des coefficients affectera le calcul des moyennes dans les bulletins.</span>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <form action="{{ route('esbtp.classes.update-matieres', ['classe' => $classe->id]) }}" method="POST">
            @csrf

            <!-- Matières disponibles -->
            <div class="card-moderne">
                <div class="main-card-header">
                    <h3 class="main-card-title">
                        <i class="fas fa-graduation-cap"></i>Matières disponibles
                    </h3>
                    <p class="main-card-subtitle">Sélectionnez et configurez les matières pour cette classe</p>
                </div>
                <div class="main-card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 5%" class="text-center">Sélection</th>
                                    <th style="width: 10%">Code</th>
                                    <th style="width: 30%">Nom de la matière</th>
                                    <th style="width: 25%">Unité d'enseignement</th>
                                    <th style="width: 15%">Coefficient</th>
                                    <th style="width: 15%">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($allMatieres as $matiere)
                                    @php
                                        $selected = $classe->matieres->contains($matiere->id);
                                        $matiereClasse = $selected ? $classe->matieres->find($matiere->id) : null;
                                        $coefficient = $matiereClasse ? $matiereClasse->pivot->coefficient : ($matiere->coefficient ?? 1);
                                        $isActive = $matiereClasse ? $matiereClasse->pivot->is_active : true;
                                    @endphp
                                    <tr class="{{ $selected ? 'table-success' : '' }}">
                                        <td class="text-center">
                                            <div class="form-check">
                                                <input class="form-check-input matiere-checkbox" type="checkbox"
                                                       name="matiere_ids[]" value="{{ $matiere->id }}"
                                                       id="matiere{{ $matiere->id }}" {{ $selected ? 'checked' : '' }}>
                                                <label class="form-check-label" for="matiere{{ $matiere->id }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">{{ $matiere->code }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ $matiere->name }}</strong>
                                            @if($matiere->nom && $matiere->nom !== $matiere->name)
                                                <br><small class="text-muted">{{ $matiere->nom }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ $matiere->uniteEnseignement ? $matiere->uniteEnseignement->name : 'Non définie' }}
                                            </span>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                   class="form-control form-control-sm coefficient-input"
                                                   name="coefficients[{{ $matiere->id }}]"
                                                   value="{{ $coefficient }}"
                                                   {{ $selected ? '' : 'disabled' }}
                                                   style="max-width: 100px;">
                                        </td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input status-switch" type="checkbox"
                                                       name="active[{{ $matiere->id }}]" value="1"
                                                       id="active{{ $matiere->id }}"
                                                       {{ $isActive ? 'checked' : '' }}
                                                       {{ $selected ? '' : 'disabled' }}>
                                                <label class="form-check-label" for="active{{ $matiere->id }}">
                                                    <span class="badge {{ $isActive ? 'bg-success' : 'bg-danger' }}">
                                                        {{ $isActive ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                            Aucune matière disponible pour cette classe.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Actions et boutons -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card-moderne">
                        <div class="main-card-header">
                            <h3 class="main-card-title">
                                <i class="fas fa-tools"></i>Actions groupées
                            </h3>
                        </div>
                        <div class="main-card-body">
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn-acasi secondary" id="select-all">
                                    <i class="fas fa-check-square me-1"></i>Tout sélectionner
                                </button>
                                <button type="button" class="btn-acasi secondary" id="deselect-all">
                                    <i class="fas fa-square me-1"></i>Tout désélectionner
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn-acasi primary btn-lg">
                        <i class="fas fa-save me-1"></i>Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    @endif
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Activer/désactiver les champs en fonction de la sélection de la matière
        $('.matiere-checkbox').on('change', function() {
            const row = $(this).closest('tr');
            const inputs = row.find('input:not(.matiere-checkbox)');

            if ($(this).is(':checked')) {
                inputs.prop('disabled', false);
                row.addClass('table-success');
            } else {
                inputs.prop('disabled', true);
                row.removeClass('table-success');
            }
        });

        // Mettre à jour l'étiquette du statut lorsque le switch change
        $('.status-switch').on('change', function() {
            const label = $(this).siblings('label').find('.badge');

            if ($(this).is(':checked')) {
                label.removeClass('bg-danger').addClass('bg-success');
                label.text('Active');
            } else {
                label.removeClass('bg-success').addClass('bg-danger');
                label.text('Inactive');
            }
        });

        // Tout sélectionner
        $('#select-all').on('click', function() {
            $('.matiere-checkbox').prop('checked', true).trigger('change');
        });

        // Tout désélectionner
        $('#deselect-all').on('click', function() {
            $('.matiere-checkbox').prop('checked', false).trigger('change');
        });

        // Animation de sélection des matières
        $('.matiere-checkbox').each(function() {
            if ($(this).is(':checked')) {
                $(this).closest('tr').addClass('table-success');
            }
        });

        // Effet de survol pour les lignes de matières
        $('tbody tr').hover(
            function() {
                if (!$(this).hasClass('table-success')) {
                    $(this).addClass('table-light');
                }
            },
            function() {
                $(this).removeClass('table-light');
            }
        );
    });
</script>
@endsection
