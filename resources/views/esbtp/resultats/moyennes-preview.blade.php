@extends('layouts.app')

@section('title', 'Modification des moyennes - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modification des moyennes</h1>
                <p class="header-subtitle">Ajustez les moyennes pour {{ $etudiant->nom }} {{ $etudiant->prenoms }}</p>
            </div>
            <div class="header-actions">
                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire') || auth()->user()->hasRole('coordinateur'))
                <a href="{{ route('esbtp.classes.matieres', ['classe' => $classe->id]) }}" class="btn btn-outline-primary me-2" title="Gérer les matières de cette classe">
                    <i class="fas fa-sliders-h me-1"></i>Gérer les matières de la classe
                </a>
                @endif
                @role('superAdmin')
                <a href="{{ route('esbtp.matieres.index') }}" class="btn btn-outline-info me-2" title="Gestion globale des matières">
                    <i class="fas fa-cog me-1"></i>Gestion globale
                </a>
                @endrole
                <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode) }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Annuler
                </a>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Étudiant</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $etudiant->nom }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-user"></i>
                    {{ $etudiant->prenoms }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Classe</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $classe->name }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Période</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">
                    @if($periode == 'semestre1')
                        1er Semestre
                    @elseif($periode == 'semestre2')
                        2e Semestre
                    @else
                        Année complète
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-alt"></i>
                    Modification en cours
                </div>
            </div>
            
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Matières</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 2.5rem; font-weight: bold;">{{ count($resultatsData) }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-book"></i>
                    À modifier
                </div>
            </div>
        </div>

        <!-- Alertes de session -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erreurs de validation :</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Guide d'utilisation -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-info-circle"></i>
                    Instructions
                </div>
            </div>
            <div class="main-card-body">
                <div class="alert alert-warning mb-0">
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="fas fa-exclamation-triangle me-2"></i><strong>Attention :</strong> La modification des moyennes a un impact direct sur les bulletins générés.</p>
                            <p><i class="fas fa-check-circle text-success me-2"></i>Vous pouvez modifier les moyennes calculées automatiquement.</p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fas fa-check-circle text-success me-2"></i>Les coefficients sont gérés par filière, niveau et année.</p>
                            <p class="mb-0">
                                <i class="fas fa-sliders-h text-primary me-2"></i>
                                <a href="{{ route('esbtp.evaluations.index', ['open_coefficients' => 1]) }}" class="text-decoration-none">Configurer les coefficients</a>
                            </p>
                            <p><i class="fas fa-info-circle text-primary me-2"></i>Les moyennes doivent être comprises entre 0 et 20.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulaire de modification -->
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-list"></i>
                    Moyennes par matière
                </div>
                <div class="main-card-subtitle">Modifiez les moyennes pour chaque matière</div>
            </div>

            <div class="main-card-body">
                <form method="POST" action="{{ route('esbtp.bulletins.moyennes-update') }}">
                    @csrf
                    <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
                    <input type="hidden" name="classe_id" value="{{ $classe->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="25%">Matière</th>
                                    <th width="15%" class="text-center">Moyenne calculée</th>
                                    <th width="15%" class="text-center">Moyenne à enregistrer</th>
                                    <th width="10%" class="text-center">Coefficient</th>
                                    <th width="20%">Appréciation</th>
                                    <th width="10%" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 1; @endphp
                                @forelse($resultatsData as $matiereId => $resultat)
                                    @php
                                        $calculatedMoyenne = isset($notesByMatiere[$matiereId]) ? $notesByMatiere[$matiereId]['moyenne'] : null;
                                        $existingMoyenne = $resultat['moyenne'] ?? $calculatedMoyenne;
                                        $source = $resultat['source'] ?? 'manuelle';
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center justify-content-center">
                                                <span class="badge bg-primary rounded-pill">{{ $i++ }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-book text-primary me-3"></i>
                                                <div>
                                                    <div class="fw-medium">{{ $resultat['matiere']->name }}</div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        @if($resultat['matiere']->code)
                                                            <small class="text-muted">{{ $resultat['matiere']->code }}</small>
                                                        @endif
                                                        @if($source == 'calculee')
                                                            <span class="badge bg-success bg-opacity-10 text-success" style="font-size: 0.7rem;">
                                                                <i class="fas fa-calculator me-1"></i>Auto
                                                            </span>
                                                        @else
                                                            <span class="badge bg-warning bg-opacity-10 text-warning" style="font-size: 0.7rem;">
                                                                <i class="fas fa-edit me-1"></i>Manuel
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if($calculatedMoyenne !== null)
                                                <span class="badge rounded-pill {{ $calculatedMoyenne >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                    {{ number_format($calculatedMoyenne, 2) }}/20
                                                </span>
                                            @else
                                                <span class="text-muted fst-italic">
                                                    <i class="fas fa-minus"></i> Aucune évaluation
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <input type="hidden" name="resultats[{{ $matiereId }}][matiere_id]" value="{{ $matiereId }}">
                                            <input type="hidden" name="resultats[{{ $matiereId }}][id]" value="{{ $resultat['id'] }}">
                                            <input type="number" class="form-control text-center" name="resultats[{{ $matiereId }}][moyenne]" value="{{ old('resultats.' . $matiereId . '.moyenne', $existingMoyenne ? number_format($existingMoyenne, 2) : '') }}" min="0" max="20" step="0.01" placeholder="Saisir moyenne" required>
                                        </td>
                                        <td>
                                            <input type="number" class="form-control text-center" value="{{ $resultat['coefficient'] ?? 1 }}" min="0" step="0.5" readonly disabled>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control" name="resultats[{{ $matiereId }}][appreciation]" value="{{ old('resultats.' . $matiereId . '.appreciation', $resultat['appreciation'] ?? '') }}" placeholder="Appréciation optionnelle">
                                        </td>
                                        <td>
                                            @if($source == 'calculee')
                                                <!-- Moyennes calculées : seule modification possible -->
                                                <span class="text-muted small">
                                                    <i class="fas fa-lock me-1"></i>Calculée
                                                </span>
                                            @else
                                                <!-- Moyennes manuelles : bouton supprimer -->
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                        onclick="supprimerMoyenneManuelle('{{ $matiereId }}')"
                                                        title="Supprimer cette moyenne manuelle">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle fa-2x mb-3 d-block text-primary"></i>
                                                <h5 class="alert-heading">Aucune matière trouvée</h5>
                                                <p class="mb-0">Cette classe n'a pas de matières associées ou l'étudiant n'a pas d'évaluations.</p>
                                                <hr>
                                                <p class="mb-0 text-muted">
                                                    <strong>Utilisez le bouton "Ajouter une matière" ci-dessous pour créer manuellement les matières et leurs moyennes.</strong>
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Section d'ajout de matières supplémentaires -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <i class="fas fa-plus-circle text-primary me-2"></i>
                                Ajouter des matières supplémentaires
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="ajouterMatiere()">
                                <i class="fas fa-plus me-1"></i>Ajouter une matière
                            </button>
                        </div>
                        
                        <div id="matieres-supplementaires">
                            <!-- Les matières ajoutées dynamiquement apparaîtront ici -->
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                        <div>
                            <a href="{{ route('esbtp.resultats.etudiant', $etudiant) }}?classe_id={{ $classe->id }}&periode={{ $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode) }}&annee_universitaire_id={{ $anneeUniversitaire->id }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn-acasi primary">
                                <i class="fas fa-save"></i>Enregistrer les modifications
                            </button>
                            <a href="#" class="btn-acasi danger" onclick="window.open('{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $anneeUniversitaire->id]) }}', '_blank')">
                                <i class="fas fa-file-pdf"></i>Générer le bulletin
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Activer les tooltips Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    });

    // Compteur pour les matières supplémentaires
    let matiereCounter = {{ count($resultatsData) + 1000 }}; // Commencer après les IDs existants
    
    // Fonction pour ajouter une matière supplémentaire
    function ajouterMatiere() {
        const container = document.getElementById('matieres-supplementaires');
        const matiereId = 'nouvelle_' + matiereCounter;
        
        const matiereHTML = `
            <div class="row mb-3 border rounded p-3 bg-light" id="matiere-${matiereId}">
                <div class="col-md-4">
                    <label class="form-label">Matière</label>
                    <select class="form-control matiere-select" name="nouvelles_matieres[${matiereId}][matiere_type]" onchange="toggleMatiereInput('${matiereId}')" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="existante">Matière existante</option>
                        <option value="nouvelle">Créer nouvelle matière</option>
                    </select>
                    <input type="hidden" name="nouvelles_matieres[${matiereId}][id]" value="${matiereId}">
                    
                    <!-- Dropdown matières existantes (masqué par défaut) -->
                    <select class="form-control mt-2 d-none" id="existing-select-${matiereId}" name="nouvelles_matieres[${matiereId}][matiere_existante_id]">
                        <option value="">-- Choisir une matière --</option>
                    </select>
                    
                    <!-- Input nouvelle matière (masqué par défaut) -->
                    <input type="text" class="form-control mt-2 d-none" id="new-input-${matiereId}" name="nouvelles_matieres[${matiereId}][nom_nouvelle]" placeholder="Ex: Mathématiques Avancées">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Moyenne</label>
                    <input type="number" class="form-control text-center" name="nouvelles_matieres[${matiereId}][moyenne]" min="0" max="20" step="0.01" placeholder="0.00" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Coefficient</label>
                    <input type="number" class="form-control text-center" name="nouvelles_matieres[${matiereId}][coefficient]" min="0" step="0.5" value="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Appréciation</label>
                    <input type="text" class="form-control" name="nouvelles_matieres[${matiereId}][appreciation]" placeholder="Optionnel">
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-outline-danger btn-sm d-block" onclick="supprimerMatiere('${matiereId}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', matiereHTML);
        matiereCounter++;
        
        // Animer l'ajout
        const newElement = document.getElementById(`matiere-${matiereId}`);
        newElement.style.opacity = '0';
        newElement.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            newElement.style.transition = 'all 0.3s ease';
            newElement.style.opacity = '1';
            newElement.style.transform = 'translateY(0)';
        }, 10);
    }
    
    // Fonction pour supprimer une matière
    function supprimerMatiere(matiereId) {
        const element = document.getElementById(`matiere-${matiereId}`);
        if (element) {
            element.style.transition = 'all 0.3s ease';
            element.style.opacity = '0';
            element.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                element.remove();
            }, 300);
        }
    }
    
    // Fonction pour gérer l'affichage des inputs matière
    function toggleMatiereInput(matiereId) {
        const select = document.querySelector(`[name="nouvelles_matieres[${matiereId}][matiere_type]"]`);
        const existingSelect = document.getElementById(`existing-select-${matiereId}`);
        const newInput = document.getElementById(`new-input-${matiereId}`);
        
        // Masquer tous les inputs d'abord
        existingSelect.classList.add('d-none');
        newInput.classList.add('d-none');
        
        if (select.value === 'existante') {
            existingSelect.classList.remove('d-none');
            existingSelect.required = true;
            newInput.required = false;
            
            // Charger les matières existantes si pas encore fait
            if (existingSelect.children.length <= 1) {
                chargerMatieresExistantes(matiereId);
            }
        } else if (select.value === 'nouvelle') {
            newInput.classList.remove('d-none');
            newInput.required = true;
            existingSelect.required = false;
        }
    }
    
    // Fonction pour charger les matières existantes via AJAX
    function chargerMatieresExistantes(matiereId) {
        const select = document.getElementById(`existing-select-${matiereId}`);
        
        // Afficher un loader
        select.innerHTML = '<option value="">Chargement...</option>';
        
        fetch('/api/esbtp/matieres/list')
            .then(response => response.json())
            .then(data => {
                select.innerHTML = '<option value="">-- Choisir une matière --</option>';
                
                data.forEach(matiere => {
                    const option = document.createElement('option');
                    option.value = matiere.id;
                    option.textContent = `${matiere.name}${matiere.code ? ' (' + matiere.code + ')' : ''}`;
                    select.appendChild(option);
                });
            })
            .catch(error => {
                debugError('Erreur:', error);
                select.innerHTML = '<option value="">Erreur de chargement</option>';
            });
    }
    
    // Fonction pour supprimer une moyenne manuelle
    function supprimerMoyenneManuelle(matiereId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette moyenne manuelle ?\n\nCette action est irréversible.')) {
            
            // Créer un formulaire pour soumettre la suppression
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("esbtp.bulletins.moyennes-delete") }}';
            form.style.display = 'none';
            
            // Token CSRF
            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';
            form.appendChild(csrfToken);
            
            // Method DELETE
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            form.appendChild(methodInput);
            
            // Paramètres
            const params = {
                'etudiant_id': '{{ $etudiant->id }}',
                'classe_id': '{{ $classe->id }}',
                'matiere_id': matiereId,
                'periode': '{{ $periode }}',
                'annee_universitaire_id': '{{ $anneeUniversitaire->id }}'
            };
            
            for (const [key, value] of Object.entries(params)) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = value;
                form.appendChild(input);
            }
            
            // Ajouter au DOM et soumettre
            document.body.appendChild(form);
            form.submit();
        }
    }
</script>
@endpush
@endsection
