@extends('layouts.app')

@section('title', 'Créer un emploi du temps - ESBTP-yAKRO')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Créer un nouvel emploi du temps</h5>
                    <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.emploi-temps.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="titre" class="form-label">Titre de l'emploi du temps *</label>
                                    <input type="text" class="form-control @error('titre') is-invalid @enderror" id="titre" name="titre" value="{{ old('titre') }}" required>
                                    @error('titre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ex: Emploi du temps BTS 1ère année Génie Civil - Semestre 1</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="classe_id" class="form-label">Classe *</label>
                                    <select class="form-select @error('classe_id') is-invalid @enderror" id="classe_id" name="classe_id" required>
                                        <option value="">Sélectionner une classe</option>
                                        @foreach($classes as $classe)
                                            <option value="{{ $classe->id }}" {{ old('classe_id') == $classe->id ? 'selected' : '' }}>
                                                {{ $classe->name }} ({{ $classe->filiere->name }} - {{ $classe->niveau->name }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('classe_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="annee_universitaire_id" class="form-label">Année universitaire *</label>
                                    <select class="form-select @error('annee_universitaire_id') is-invalid @enderror" id="annee_universitaire_id" name="annee_universitaire_id" required>
                                        <option value="">Sélectionner une année universitaire</option>
                                        @foreach($annees as $annee)
                                            <option value="{{ $annee->id }}" {{ old('annee_universitaire_id') == $annee->id ? 'selected' : '' }}>
                                                {{ $annee->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('annee_universitaire_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="semestre" class="form-label">Période *</label>
                                    <select class="form-select @error('semestre') is-invalid @enderror" id="semestre" name="semestre" required>
                                        <option value="">Sélectionner une période</option>
                                        <option value="Semestre 1">Semestre 1</option>
                                        <option value="Semestre 2">Semestre 2</option>
                                        <option value="Année complète">Année complète</option>
                                    </select>
                                    @error('semestre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Section d'information sur la planification académique -->
                        @if(request('classe_id'))
                            <div class="alert alert-info">
                                <strong>Debug:</strong> Classe ID reçu = {{ request('classe_id') }}
                                @if(isset($classeSelectionnee))
                                    | Classe trouvée = {{ $classeSelectionnee->name ?? 'NULL' }}
                                @else
                                    | Classe non trouvée en base
                                @endif
                                @if(isset($planificationData))
                                    | Données planification = OUI
                                @else
                                    | Données planification = NON
                                @endif
                            </div>
                        @endif
                        
                        @if(isset($planificationData) && $classeSelectionnee)
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            Planification académique - {{ $classeSelectionnee->name }}
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        @if($planificationData['planifications_configurees'])
                                            <div class="alert alert-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <strong>Planification configurée</strong> - {{ $planificationData['matieres_planifiees']->count() }} matière(s) planifiée(s)
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold text-primary">Matières disponibles</h6>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Matière</th>
                                                                    <th>Enseignant</th>
                                                                    <th>Heures restantes</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($planificationData['matieres_planifiees'] as $matiere)
                                                                <tr class="{{ $matiere['heures_restantes'] <= 0 ? 'table-warning' : '' }}">
                                                                    <td>
                                                                        <strong>{{ $matiere['matiere']->name }}</strong>
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            Total: {{ $matiere['volume_horaire_total'] }}h
                                                                        </small>
                                                                    </td>
                                                                    <td>
                                                                        @if($matiere['enseignant_principal'])
                                                                            <small>{{ $matiere['enseignant_principal']->name }}</small>
                                                                        @else
                                                                            <small class="text-muted">Non assigné</small>
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        <span class="badge {{ $matiere['heures_restantes'] > 0 ? 'bg-success' : 'bg-warning' }}">
                                                                            {{ $matiere['heures_restantes'] }}h
                                                                        </span>
                                                                        <br>
                                                                        <small class="text-muted">
                                                                            {{ $matiere['pourcentage_utilise'] }}% utilisé
                                                                        </small>
                                                                    </td>
                                                                </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6">
                                                    <h6 class="fw-bold text-primary">Résumé des heures</h6>
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="card text-center">
                                                                <div class="card-body p-2">
                                                                    <h4 class="text-primary mb-1">{{ $planificationData['heures_totales'] }}h</h4>
                                                                    <small class="text-muted">Total planifié</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="card text-center">
                                                                <div class="card-body p-2">
                                                                    <h4 class="text-success mb-1">{{ $planificationData['heures_restantes'] }}h</h4>
                                                                    <small class="text-muted">Restantes</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    @if($planificationData['heures_restantes'] <= 0)
                                                        <div class="alert alert-warning mt-2">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            <small>Toutes les heures ont été programmées pour cette classe.</small>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>Planification non configurée</strong>
                                            </div>
                                            <p class="mb-2">{{ $planificationData['message_configuration'] }}</p>
                                            @if($planificationData['lien_configuration'])
                                                <a href="{{ $planificationData['lien_configuration'] }}" class="btn btn-warning btn-sm" target="_blank">
                                                    <i class="fas fa-cog me-1"></i>Configurer la planification
                                                </a>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_debut" class="form-label">Date de début *</label>
                                    <div class="input-group">
                                        <input type="date" class="form-control @error('date_debut') is-invalid @enderror" id="date_debut" name="date_debut" value="{{ old('date_debut', $semaineCourante['date_debut'] ?? '') }}" required>
                                        <button type="button" class="btn btn-outline-secondary" id="btn-semaine-courante">
                                            <i class="fas fa-calendar-week"></i> Semaine courante
                                        </button>
                                    </div>
                                    @error('date_debut')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="date_fin" class="form-label">Date de fin *</label>
                                    <input type="date" class="form-control @error('date_fin') is-invalid @enderror" id="date_fin" name="date_fin" value="{{ old('date_fin', $semaineCourante['date_fin'] ?? '') }}" required>
                                    @error('date_fin')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle"></i> La période doit être de 5 jours maximum (du lundi au vendredi).
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">
                                Emploi du temps actif
                            </label>
                            <div class="form-text">
                                <span class="text-info"><i class="fas fa-info-circle me-1"></i>Info :</span>
                                Un seul emploi du temps peut être actif par classe à la fois. Si vous activez cet emploi du temps, les autres emplois du temps pour la même classe seront automatiquement désactivés et celui-ci sera défini comme l'emploi du temps courant.
                            </div>
                        </div>

                        <div class="alert alert-warning">
                            <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Remarque importante</h6>
                            <p class="mb-0">Après avoir créé l'emploi du temps, vous pourrez y ajouter des séances de cours. Assurez-vous que la classe sélectionnée a des matières et des enseignants assignés avant de créer l'emploi du temps.</p>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-secondary me-2">Annuler</button>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Amélioration des listes déroulantes avec Select2
        $('#classe_id, #annee_universitaire_id, #semestre').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Sélectionnez un élément'
        });

        // Attendre que Select2 soit chargé puis attacher l'événement
        $(document).ready(function() {
            // Vérifier si jQuery et Select2 sont disponibles
            if (typeof $ === 'undefined') {
                console.error('jQuery non disponible');
                return;
            }
            
            // Attendre un peu que Select2 soit initialisé
            setTimeout(function() {
                // Recharger la page avec les données de planification lors du changement de classe
                $('#classe_id').on('change', function() {
            const classeId = $(this).val();
            if (classeId) {
                // Construire l'URL avec le paramètre classe_id
                const currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('classe_id', classeId);
                
                // Conserver les autres champs déjà remplis
                const titre = document.getElementById('titre').value;
                const dateDebut = document.getElementById('date_debut').value;
                const dateFin = document.getElementById('date_fin').value;
                const anneeId = document.getElementById('annee_universitaire_id').value;
                const semestre = document.getElementById('semestre').value;
                const isActive = document.getElementById('is_active').checked;
                
                if (titre) currentUrl.searchParams.set('titre', titre);
                if (dateDebut) currentUrl.searchParams.set('date_debut', dateDebut);
                if (dateFin) currentUrl.searchParams.set('date_fin', dateFin);
                if (anneeId) currentUrl.searchParams.set('annee_universitaire_id', anneeId);
                if (semestre) currentUrl.searchParams.set('semestre', semestre);
                if (isActive) currentUrl.searchParams.set('is_active', '1');
                
                    // Rediriger vers l'URL mise à jour
                    window.location.href = currentUrl.toString();
                }
            });
            }, 500); // Attendre 500ms
            
            // Alternative : événement direct sur le select (au cas où Select2 pose problème)
            document.getElementById('classe_id').addEventListener('change', function() {
                const classeId = this.value;
                if (classeId) {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('classe_id', classeId);
                    console.log('Redirection vers:', currentUrl.toString());
                    window.location.href = currentUrl.toString();
                }
            });
        });

        // Restaurer les valeurs depuis les paramètres URL au chargement
        $(document).ready(function() {
            const urlParams = new URLSearchParams(window.location.search);
            
            // Restaurer les valeurs des champs depuis l'URL
            if (urlParams.get('titre')) {
                document.getElementById('titre').value = urlParams.get('titre');
            }
            if (urlParams.get('date_debut')) {
                document.getElementById('date_debut').value = urlParams.get('date_debut');
            }
            if (urlParams.get('date_fin')) {
                document.getElementById('date_fin').value = urlParams.get('date_fin');
            }
            if (urlParams.get('annee_universitaire_id')) {
                $('#annee_universitaire_id').val(urlParams.get('annee_universitaire_id')).trigger('change');
            }
            if (urlParams.get('semestre')) {
                $('#semestre').val(urlParams.get('semestre')).trigger('change');
            }
            if (urlParams.get('is_active') === '1') {
                document.getElementById('is_active').checked = true;
            }
        });

        // Bouton pour définir la semaine courante
        document.getElementById('btn-semaine-courante').addEventListener('click', function() {
            document.getElementById('date_debut').value = '{{ $semaineCourante['date_debut'] }}';
            document.getElementById('date_fin').value = '{{ $semaineCourante['date_fin'] }}';
        });

        // Validation côté client pour la période de 5 jours maximum
        document.getElementById('date_fin').addEventListener('change', function() {
            const dateDebut = new Date(document.getElementById('date_debut').value);
            const dateFin = new Date(this.value);

            // Calculer la différence en jours
            const diffTime = Math.abs(dateFin - dateDebut);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays > 4) {
                alert('La période de l\'emploi du temps ne doit pas dépasser 5 jours (du lundi au vendredi).');
                this.value = '';
            }
        });
    });
</script>
@endsection
