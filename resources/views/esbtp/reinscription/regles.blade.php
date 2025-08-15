@extends('layouts.app')

@section('title', 'Règles Académiques')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Règles Académiques</h1>
                <p class="header-subtitle">Configuration des seuils de passage, rattrapage et redoublement</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.reinscription.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
                <button class="btn-acasi primary" data-toggle="modal" data-target="#modalNouvelleRegle">
                    <i class="fas fa-plus"></i>Nouvelle Règle
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="card-moderne mb-md" style="border-left: 4px solid var(--danger); background-color: rgba(239, 68, 68, 0.05);">
                <div class="p-lg">
                    <ul style="margin: 0; padding-left: 20px; color: var(--danger);">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="card-moderne mb-md" style="border-left: 4px solid var(--success); background-color: rgba(16, 185, 129, 0.05);">
                <div class="p-lg">
                    <p style="margin: 0; color: var(--success); font-weight: 500;">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Liste des règles -->
        <div class="card-moderne">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-cogs"></i>
                    Règles Définies
                </div>
            </div>
            <div class="p-lg">
                @if($regles->count() > 0)
                    <div class="table-moderne">
                        <table>
                            <thead>
                                <tr>
                                    <th>Niveau</th>
                                    <th>Filière</th>
                                    <th class="text-center">Passage</th>
                                    <th class="text-center">Rattrapage</th>
                                    <th class="text-center">Max Matières</th>
                                    <th class="text-center">Redoublement</th>
                                    <th class="text-center">Statut</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($regles as $regle)
                                <tr>
                                    <td>
                                        <span class="table-badge primary">{{ $regle->niveau ?: 'Défaut' }}</span>
                                    </td>
                                    <td>
                                        <span class="table-badge primary">{{ $regle->filiere ?: 'Toutes' }}</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="table-badge success">{{ $regle->moyenne_passage }}/20</span>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="table-badge warning">{{ $regle->moyenne_rattrapage }}/20</span>
                                    </td>
                                    <td style="text-align: center; font-weight: 500;">{{ $regle->max_matieres_rattrapage }}</td>
                                    <td style="text-align: center;">
                                        <span class="table-badge {{ $regle->autoriser_redoublement ? 'success' : 'danger' }}">
                                            {{ $regle->autoriser_redoublement ? 'Oui' : 'Non' }}
                                        </span>
                                        @if($regle->autoriser_redoublement)
                                        <div style="font-size: var(--text-small); color: var(--text-muted); margin-top: var(--space-xs);">
                                            Max: {{ $regle->max_redoublements }}
                                        </div>
                                        @endif
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="table-badge {{ $regle->actif ? 'success' : 'neutral' }}">
                                            {{ $regle->actif ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <div class="table-actions">
                                            <button onclick="editerRegle({{ $regle->id }}, {{ json_encode($regle) }})"
                                                    title="Modifier" class="btn-table-action primary">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form action="{{ route('esbtp.reinscription.regles.destroy', $regle->id) }}" 
                                                  method="POST" style="display: inline;"
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette règle ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Supprimer" class="btn-table-action danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div style="text-align: center; padding: var(--space-xl) 0;">
                        <div style="margin-bottom: var(--space-lg);">
                            <i class="fas fa-cogs" style="font-size: 4rem; color: var(--text-muted);"></i>
                        </div>
                        <h3 style="color: var(--text-muted); margin-bottom: var(--space-md);">Aucune règle définie</h3>
                        <p style="color: var(--text-muted); margin-bottom: var(--space-lg);">
                            Commencez par définir les règles académiques pour vos niveaux et filières.
                        </p>
                        <button class="btn-acasi primary" data-toggle="modal" data-target="#modalNouvelleRegle">
                            <i class="fas fa-plus"></i>Créer une Règle
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Règle -->
<div class="modal fade" id="modalNouvelleRegle" tabindex="-1" role="dialog" aria-labelledby="modalNouvelleRegleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-moderne">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNouvelleRegleLabel">
                    <i class="fas fa-plus"></i> Nouvelle Règle Académique
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('esbtp.reinscription.regles.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div class="form-group-moderne">
                            <label for="niveau" class="form-label-moderne">Niveau *</label>
                            <select name="niveau" id="niveau" class="form-select-moderne" required>
                                <option value="">Sélectionnez un niveau</option>
                                @foreach($niveaux as $niveau)
                                    <option value="{{ $niveau->name }}">{{ $niveau->name }} ({{ $niveau->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group-moderne">
                            <label for="filiere" class="form-label-moderne">Filière *</label>
                            <select name="filiere" id="filiere" class="form-select-moderne" required>
                                <option value="">Sélectionnez une filière</option>
                                @foreach($filieres as $filiere)
                                    <option value="{{ $filiere->name }}">{{ $filiere->name }} ({{ $filiere->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group-moderne">
                            <label for="moyenne_passage" class="form-label-moderne">Moyenne de Passage *</label>
                            <input type="number" name="moyenne_passage" id="moyenne_passage" 
                                   class="form-input-moderne" step="0.01" min="0" max="20" placeholder="12.00" required>
                        </div>
                        <div class="form-group-moderne">
                            <label for="moyenne_rattrapage" class="form-label-moderne">Moyenne de Rattrapage *</label>
                            <input type="number" name="moyenne_rattrapage" id="moyenne_rattrapage" 
                                   class="form-input-moderne" step="0.01" min="0" max="20" placeholder="8.00" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group-moderne">
                            <label for="max_matieres_rattrapage" class="form-label-moderne">Max Matières Rattrapage *</label>
                            <input type="number" name="max_matieres_rattrapage" id="max_matieres_rattrapage" 
                                   class="form-input-moderne" min="1" placeholder="3" required>
                        </div>
                        <div class="form-group-moderne">
                            <label for="max_redoublements" class="form-label-moderne">Max Redoublements *</label>
                            <input type="number" name="max_redoublements" id="max_redoublements" 
                                   class="form-input-moderne" min="1" placeholder="2" required>
                        </div>
                    </div>

                    <div class="form-group-moderne">
                        <div class="form-check-moderne">
                            <input type="checkbox" name="autoriser_redoublement" id="autoriser_redoublement" 
                                   class="form-check-input-moderne" value="1" checked>
                            <label for="autoriser_redoublement" class="form-check-label-moderne">
                                Autoriser le redoublement
                            </label>
                        </div>
                    </div>

                    <div class="form-group-moderne">
                        <label for="conditions_speciales" class="form-label-moderne">Conditions Spéciales</label>
                        <textarea name="conditions_speciales" id="conditions_speciales" 
                                  class="form-textarea-moderne" rows="3" 
                                  placeholder="Conditions ou notes particulières..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-acasi secondary" data-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Édition Règle -->
<div class="modal fade" id="modalEditerRegle" tabindex="-1" role="dialog" aria-labelledby="modalEditerRegleLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content modal-moderne">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditerRegleLabel">
                    <i class="fas fa-edit"></i> Modifier la Règle Académique
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditerRegle" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div class="form-group-moderne">
                            <label class="form-label-moderne">Niveau</label>
                            <input type="text" id="edit_niveau" class="form-input-moderne" readonly 
                                   style="background-color: var(--background); cursor: not-allowed;">
                        </div>
                        <div class="form-group-moderne">
                            <label class="form-label-moderne">Filière</label>
                            <input type="text" id="edit_filiere" class="form-input-moderne" readonly
                                   style="background-color: var(--background); cursor: not-allowed;">
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group-moderne">
                            <label for="edit_moyenne_passage" class="form-label-moderne">Moyenne de Passage *</label>
                            <input type="number" name="moyenne_passage" id="edit_moyenne_passage" 
                                   class="form-input-moderne" step="0.01" min="0" max="20" required>
                        </div>
                        <div class="form-group-moderne">
                            <label for="edit_moyenne_rattrapage" class="form-label-moderne">Moyenne de Rattrapage *</label>
                            <input type="number" name="moyenne_rattrapage" id="edit_moyenne_rattrapage" 
                                   class="form-input-moderne" step="0.01" min="0" max="20" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group-moderne">
                            <label for="edit_max_matieres_rattrapage" class="form-label-moderne">Max Matières Rattrapage *</label>
                            <input type="number" name="max_matieres_rattrapage" id="edit_max_matieres_rattrapage" 
                                   class="form-input-moderne" min="1" required>
                        </div>
                        <div class="form-group-moderne">
                            <label for="edit_max_redoublements" class="form-label-moderne">Max Redoublements *</label>
                            <input type="number" name="max_redoublements" id="edit_max_redoublements" 
                                   class="form-input-moderne" min="1" required>
                        </div>
                    </div>

                    <div class="form-grid-2">
                        <div class="form-group-moderne">
                            <div class="form-check-moderne">
                                <input type="checkbox" name="autoriser_redoublement" id="edit_autoriser_redoublement" 
                                       class="form-check-input-moderne" value="1">
                                <label for="edit_autoriser_redoublement" class="form-check-label-moderne">
                                    Autoriser le redoublement
                                </label>
                            </div>
                        </div>
                        <div class="form-group-moderne">
                            <div class="form-check-moderne">
                                <input type="checkbox" name="actif" id="edit_actif" 
                                       class="form-check-input-moderne" value="1">
                                <label for="edit_actif" class="form-check-label-moderne">
                                    Règle active
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group-moderne">
                        <label for="edit_conditions_speciales" class="form-label-moderne">Conditions Spéciales</label>
                        <textarea name="conditions_speciales" id="edit_conditions_speciales" 
                                  class="form-textarea-moderne" rows="3" placeholder="Conditions ou notes particulières..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-acasi secondary" data-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Gestion des modals - s'assurer que les boutons de fermeture fonctionnent
    $('.modal').on('show.bs.modal', function() {
        var modal = $(this);
        // S'assurer que les boutons close fonctionnent
        modal.find('.close, [data-dismiss="modal"]').off('click.modal').on('click.modal', function() {
            modal.modal('hide');
        });
    });
    
    // Gestion spécifique pour le bouton close du header
    $('.modal .close').on('click', function(e) {
        e.preventDefault();
        $(this).closest('.modal').modal('hide');
    });
});

function editerRegle(id, regle) {
    document.getElementById('formEditerRegle').action = `{{ url('esbtp/reinscription/regles') }}/${id}`;
    document.getElementById('edit_niveau').value = regle.niveau || 'Défaut';
    document.getElementById('edit_filiere').value = regle.filiere || 'Toutes';
    document.getElementById('edit_moyenne_passage').value = regle.moyenne_passage;
    document.getElementById('edit_moyenne_rattrapage').value = regle.moyenne_rattrapage;
    document.getElementById('edit_max_matieres_rattrapage').value = regle.max_matieres_rattrapage;
    document.getElementById('edit_max_redoublements').value = regle.max_redoublements;
    document.getElementById('edit_autoriser_redoublement').checked = regle.autoriser_redoublement;
    document.getElementById('edit_actif').checked = regle.actif;
    document.getElementById('edit_conditions_speciales').value = regle.conditions_speciales || '';
    
    $('#modalEditerRegle').modal('show');
}
</script>
@endsection