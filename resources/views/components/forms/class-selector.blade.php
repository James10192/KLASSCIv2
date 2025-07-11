<!-- resources/views/components/forms/class-selector.blade.php -->

<div class="form-group">
    <label for="classe_display">Classe <span class="text-danger">*</span></label>
    <div style="display: flex; gap: 10px;">
        <input type="hidden" id="classe_id" name="classe_id" value="{{ old('classe_id') }}">
        <input type="text" id="classe_display" class="form-control @error('classe_id') is-invalid @enderror" value="{{ old('classe_display') }}" readonly>
        <button class="btn btn-primary" type="button" id="selectClasseBtn" style="min-width: 120px;">
            <i class="fas fa-search"></i> Sélectionner
        </button>
    </div>
    <div id="available-places-info" class="mt-2"></div>
    @error('classe_id')
    <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<!-- Modal -->
<div class="modal fade" id="classeSelectorModal" tabindex="-1" aria-labelledby="classeSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="classeSelectorModalLabel">Sélectionner une Classe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Filtres pour la recherche de classe -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="classe_search_filter">Filtrer par :</label>
                        <select class="form-control" id="classe_search_filter">
                            <option value="all">Toutes les classes</option>
                            <option value="nom">Nom de la classe</option>
                            <option value="filiere">Filière</option>
                            <option value="niveau">Niveau</option>
                            <option value="annee">Année universitaire</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="classe_search_query">Rechercher :</label>
                        <input type="text" class="form-control" id="classe_search_query" placeholder="Nom de la classe, filière, niveau, etc.">
                    </div>
                    <div class="col-md-4">
                        <label for="classe_search_year">Année universitaire :</label>
                        <select class="form-control" id="classe_search_year">
                            <option value="">Toutes les années</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}">{{ $year->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- Tableau pour afficher les classes -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Classe</th>
                                <th>Filière</th>
                                <th>Niveau</th>
                                <th>Année</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="classes-table-body">
                            <!-- Les classes seront chargées ici par AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function selectClasse(id, displayText) {
        document.getElementById('classe_id').value = id;
        document.getElementById('classe_display').value = displayText;
        
        // Simuler un changement pour déclencher l'appel API
        const event = new Event('change');
        document.getElementById('classe_id').dispatchEvent(event);

        // Fermer le modal
        var myModalEl = document.getElementById('classeSelectorModal');
        var modal = bootstrap.Modal.getInstance(myModalEl);
        modal.hide();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const classeIdInput = document.getElementById('classe_id');
        const availablePlacesDiv = document.getElementById('available-places-info');

        if(classeIdInput) {
            classeIdInput.addEventListener('change', function() {
                const classeId = this.value;
                if (classeId && availablePlacesDiv) {
                    availablePlacesDiv.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Vérification...';
                    fetch(`/classes/${classeId}/available-places`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.available_places !== undefined) {
                                let message = `Places disponibles: <strong>${data.available_places}</strong>`;
                                let alertClass = 'alert-success';
                                if (data.available_places <= 5) alertClass = 'alert-warning';
                                if (data.available_places === 0) {
                                    alertClass = 'alert-danger';
                                    message = '<strong>Aucune place disponible !</strong>';
                                }
                                availablePlacesDiv.innerHTML = `<div class="alert ${alertClass} p-2">${message}</div>`;
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            availablePlacesDiv.innerHTML = '<div class="alert alert-danger p-2">Erreur de vérification.</div>';
                        });
                } else if (availablePlacesDiv) {
                    availablePlacesDiv.innerHTML = '';
                }
            });
        }
    });

    // Add AJAX loading logic for classes when modal opens
    document.getElementById('classeSelectorModal').addEventListener('show.bs.modal', function () {
        const tableBody = document.getElementById('classes-table-body');
        tableBody.innerHTML = '<tr><td colspan="5">Chargement...</td></tr>';
        
        // TODO: Implement actual AJAX call to fetch classes
        fetch('/api/classes')
            .then(response => response.json())
            .then(classes => {
                tableBody.innerHTML = '';
                classes.forEach(classe => {
                    const displayText = `${classe.name} - ${classe.filiere.name} - ${classe.niveau.name} - ${classe.annee_academique.name}`;
                    tableBody.innerHTML += `<tr>
                        <td>${classe.name}</td>
                        <td>${classe.filiere.name}</td>
                        <td>${classe.niveau.name}</td>
                        <td>${classe.annee_academique.name}</td>
                        <td><button class="btn btn-sm btn-primary" onclick="selectClasse(${classe.id}, '${displayText}')">Sélectionner</button></td>
                    </tr>`;
                });
            })
            .catch(error => {
                tableBody.innerHTML = '<tr><td colspan="5">Erreur de chargement des classes.</td></tr>';
                console.error('Error loading classes:', error);
            });
    });

    document.getElementById('selectClasseBtn').addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        console.log('Button clicked, attempting to open modal');
        const modalEl = document.getElementById('classeSelectorModal');
        if (!modalEl) {
            console.error('Modal element not found');
        } else {
            console.log('Modal element found, initializing');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            console.log('Modal show called');
        }

        // Add to modal shown event for confirmation
        modalEl.addEventListener('shown.bs.modal', () => {
            console.log('Modal successfully shown');
        });

        // Add global click log to detect arbitrary clicks
        document.addEventListener('click', (e) => {
            console.log('Global click detected at:', e.target);
        }, true); // Capture phase

        // Add modal event logs
        modalEl.addEventListener('show.bs.modal', (e) => {
            console.log('Modal about to show, triggered by:', e.relatedTarget);
        });

        modalEl.addEventListener('shown.bs.modal', () => {
            console.log('Modal fully shown');
        });

        modalEl.addEventListener('hide.bs.modal', (e) => {
            console.log('Modal about to hide, triggered by:', e.relatedTarget);
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            console.log('Modal fully hidden');
        });
    });
</script> 