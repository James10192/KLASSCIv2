@extends('layouts.app')

@section('title', 'Gestion des matières - ' . $classe->name . ' - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Gestion des matières pour la classe: {{ $classe->name }}</h5>
                    <div>
                        <a href="{{ route('esbtp.classes.show', ['classe' => $classe->id]) }}" class="btn btn-info me-2">
                            <i class="fas fa-eye me-1"></i>Voir les détails
                        </a>
                        <a href="{{ route('esbtp.student.classes.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if($classe->matieres->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Aucune matière n'est associée à cette classe. Utilisez le formulaire ci-dessous pour attacher des matières.
                        </div>
                    @endif

                    @if(!$allMatieres->isEmpty())
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Gérez les matières de la classe <strong>{{ $classe->name }}</strong> en ajustant leurs coefficients et le nombre d'heures. La modification des coefficients affectera le calcul des moyennes dans les bulletins.
                        </div>

                        <form action="{{ route('esbtp.classes.update-matieres', ['classe' => $classe->id]) }}" method="POST">
                            @csrf

                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%">Sélection</th>
                                            <th style="width: 10%">Code</th>
                                            <th style="width: 25%">Nom</th>
                                            <th style="width: 20%">Unité d'enseignement</th>
                                            <th style="width: 15%">Coefficient</th>
                                            <th style="width: 15%">Heures totales</th>
                                            <th style="width: 10%">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($allMatieres as $matiere)
                                            @php
                                                $selected = $classe->matieres->contains($matiere->id);
                                                $matiereClasse = $selected ? $classe->matieres->find($matiere->id) : null;
                                                $coefficient = $matiereClasse ? $matiereClasse->pivot->coefficient : $matiere->coefficient_default;
                                                $totalHeures = $matiereClasse ? $matiereClasse->pivot->total_heures : $matiere->total_heures_default;
                                                $isActive = $matiereClasse ? $matiereClasse->pivot->is_active : true;
                                            @endphp
                                            <tr>
                                                <td class="text-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input matiere-checkbox" type="checkbox" name="matiere_ids[]" value="{{ $matiere->id }}" id="matiere{{ $matiere->id }}" {{ $selected ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="matiere{{ $matiere->id }}"></label>
                                                    </div>
                                                </td>
                                                <td>{{ $matiere->code }}</td>
                                                <td>{{ $matiere->name }}</td>
                                                <td>{{ $matiere->uniteEnseignement ? $matiere->uniteEnseignement->name : 'Non définie' }}</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0" class="form-control form-control-sm coefficient-input" name="coefficients[{{ $matiere->id }}]" value="{{ $coefficient }}" {{ $selected ? '' : 'disabled' }}>
                                                </td>
                                                <td>
                                                    <input type="number" min="1" class="form-control form-control-sm heures-input" name="heures[{{ $matiere->id }}]" value="{{ $totalHeures }}" {{ $selected ? '' : 'disabled' }}>
                                                </td>
                                                <td>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input status-switch" type="checkbox" name="active[{{ $matiere->id }}]" value="1" id="active{{ $matiere->id }}" {{ $isActive ? 'checked' : '' }} {{ $selected ? '' : 'disabled' }}>
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
                                                <td colspan="7" class="text-center">Aucune matière disponible pour cette classe.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Actions groupées</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex gap-2">
                                                <button type="button" class="btn btn-outline-primary" id="select-all">
                                                    <i class="fas fa-check-square me-1"></i>Tout sélectionner
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" id="deselect-all">
                                                    <i class="fas fa-square me-1"></i>Tout désélectionner
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-1"></i>Enregistrer les modifications
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Activer/désactiver les champs en fonction de la sélection de la matière
        $('.matiere-checkbox').on('change', function() {
            const row = $(this).closest('tr');
            const inputs = row.find('input:not(.matiere-checkbox)');

            if ($(this).is(':checked')) {
                inputs.prop('disabled', false);
            } else {
                inputs.prop('disabled', true);
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
    });
</script>
@endsection
