@extends('layouts.app')

@section('title', 'Ajouter une séance - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-plus-circle me-2"></i>Nouvelle Séance</h1>
                <p class="header-subtitle">Ajouter une séance pour {{ $emploi_temp->classe->name ?? 'Classe non définie' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.emploi-temps.show', $emploi_temp) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à l'emploi du temps
                </a>
            </div>
        </div>

        <form action="{{ route('esbtp.emploi-temps.store-session', $emploi_temp) }}" method="POST">
            @csrf
            
            <div class="form-sections">
                <!-- Section 1: Matière et Volume -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-book"></i>
                            Matière et Volume
                        </div>
                        <div class="main-card-subtitle">Sélection de la matière et informations de volume</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-group">
                            <label for="matiere_id" class="form-label">Matière <span class="text-danger">*</span></label>
                            <select name="matiere_id" id="matiere_id" class="form-select @error('matiere_id') error @enderror" required>
                                <option value="">Sélectionner une matière</option>
                                @foreach($matieres as $matiere)
                                    @php
                                        $volumeInfo = $matiere->volume_info ?? [];
                                        $isComplete = $volumeInfo['est_complete'] ?? false;
                                        $isNotConfigured = $volumeInfo['non_configuree'] ?? false;
                                    @endphp
                                    <option value="{{ $matiere->id }}" 
                                            data-volume-info='@json($volumeInfo)'
                                            {{ old('matiere_id') == $matiere->id ? 'selected' : '' }}
                                            @if($isComplete) class="text-danger" @elseif($isNotConfigured) class="text-warning" @endif>
                                        {{ $matiere->name }}
                                        @if($isNotConfigured)
                                            (Non configurée)
                                        @elseif(isset($volumeInfo['heures_restantes']))
                                            ({{ $volumeInfo['heures_restantes'] }}h/{{ $volumeInfo['volume_total'] }}h restantes)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('matiere_id')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                            
                            <!-- Informations sur le volume de la matière sélectionnée -->
                            <div id="matiere-volume-info" class="mt-3" style="display: none;">
                                <div class="info-box" id="volume-alert">
                                    <!-- Contenu généré par JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Enseignant et Disponibilités -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-user-tie"></i>
                            Enseignant
                        </div>
                        <div class="main-card-subtitle">Sélection et visualisation des disponibilités</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-group">
                            <label for="enseignant_id" class="form-label">Enseignant</label>
                            <select name="enseignant_id" id="enseignant_id" class="form-select @error('enseignant_id') error @enderror">
                                <option value="">Sélectionner un enseignant</option>
                                @foreach($enseignantsAvecDisponibilites as $enseignant)
                                    <option value="{{ $enseignant->id }}" 
                                            data-availability='@json($enseignant->availability_data)'
                                            {{ old('enseignant_id') == $enseignant->id ? 'selected' : '' }}>
                                        {{ $enseignant->user->name ?? $enseignant->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('enseignant_id')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 3: Informations temporelles -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-calendar-alt"></i>
                            Informations temporelles
                        </div>
                        <div class="main-card-subtitle">Planning et horaires</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="jour" class="form-label">Jour <span class="text-danger">*</span></label>
                                <select name="jour" id="jour" class="form-select @error('jour') error @enderror" required>
                                    <option value="">Sélectionner un jour</option>
                                    <option value="Lundi" {{ old('jour') == 'Lundi' ? 'selected' : '' }}>Lundi</option>
                                    <option value="Mardi" {{ old('jour') == 'Mardi' ? 'selected' : '' }}>Mardi</option>
                                    <option value="Mercredi" {{ old('jour') == 'Mercredi' ? 'selected' : '' }}>Mercredi</option>
                                    <option value="Jeudi" {{ old('jour') == 'Jeudi' ? 'selected' : '' }}>Jeudi</option>
                                    <option value="Vendredi" {{ old('jour') == 'Vendredi' ? 'selected' : '' }}>Vendredi</option>
                                    <option value="Samedi" {{ old('jour') == 'Samedi' ? 'selected' : '' }}>Samedi</option>
                                </select>
                                @error('jour')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label for="heure_debut" class="form-label">Heure de début <span class="text-danger">*</span></label>
                                <input type="time" name="heure_debut" id="heure_debut" class="form-input @error('heure_debut') error @enderror" value="{{ old('heure_debut') }}" required>
                                @error('heure_debut')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label for="heure_fin" class="form-label">Heure de fin <span class="text-danger">*</span></label>
                                <input type="time" name="heure_fin" id="heure_fin" class="form-input @error('heure_fin') error @enderror" value="{{ old('heure_fin') }}" required>
                                @error('heure_fin')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="form-group">
                                <label for="salle" class="form-label">Salle</label>
                                <input type="text" name="salle" id="salle" class="form-input @error('salle') error @enderror" value="{{ old('salle') }}">
                                @error('salle')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="form-group mt-4">
                            <label for="description" class="form-label">Détails</label>
                            <textarea name="description" id="description" class="form-textarea @error('description') error @enderror" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Section 4: Grille de disponibilités de l'enseignant sélectionné -->
                <div class="main-card" id="availability-card" style="display: none;">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-calendar-check"></i>
                            Disponibilités de l'enseignant
                        </div>
                        <div class="main-card-subtitle">Grille des créneaux disponibles</div>
                    </div>
                    <div class="main-card-body">
                        <div class="availability-grid-container">
                            <div class="availability-grid" id="availability-grid">
                                <!-- Grille générée par JavaScript -->
                            </div>
                        </div>
                        <div class="availability-legend mt-3">
                            <div class="d-flex flex-wrap gap-3">
                                <div class="legend-item">
                                    <span class="legend-color available"></span>
                                    <span class="legend-text">Disponible</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color preferred"></span>
                                    <span class="legend-text">Préféré</span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color unavailable"></span>
                                    <span class="legend-text">Indisponible</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 5: Actions -->
                <div class="form-actions">
                    <a href="{{ route('esbtp.emploi-temps.show', $emploi_temp) }}" class="btn-acasi secondary">
                        <i class="fas fa-times"></i>Annuler
                    </a>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>Ajouter la séance
                    </button>
                </div>
            </div>

            <input type="hidden" name="classe_id" value="{{ $emploi_temp->classe_id }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $emploi_temp->annee_universitaire_id }}">
        </form>
    </div>
</div>
@endsection

@section('styles')
<style>
/* Formulaire moderne avec dashboard-moderne.css */
.form-sections {
    display: grid;
    gap: var(--space-xl);
    max-width: none;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: var(--text);
    margin-bottom: var(--space-sm);
    font-size: var(--text-small);
    line-height: 1.2;
}

.form-input, .form-select, .form-textarea {
    padding: var(--space-md);
    border: 1px solid var(--border);
    border-radius: var(--radius-small);
    background: var(--card-background);
    color: var(--text);
    font-size: var(--text-base);
    transition: all 0.2s ease;
    line-height: 1.5;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    background: white;
}

.form-input.error, .form-select.error, .form-textarea.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(var(--danger-rgb), 0.1);
}

.form-error {
    color: var(--danger);
    font-size: var(--text-small);
    margin-top: var(--space-xs);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.form-error::before {
    content: "⚠";
    font-weight: bold;
}

.info-box {
    display: flex;
    gap: var(--space-md);
    padding: var(--space-lg);
    background: rgba(var(--info-rgb), 0.08);
    border: 1px solid rgba(var(--info-rgb), 0.2);
    border-radius: var(--radius-medium);
    color: var(--text);
    margin-top: var(--space-lg);
}

.info-box i {
    flex-shrink: 0;
    margin-top: var(--space-xs);
    color: var(--info);
    font-size: 1.1rem;
}

.form-actions {
    display: flex;
    gap: var(--space-md);
    justify-content: flex-end;
    padding: var(--space-xl) 0;
    border-top: 1px solid var(--border);
    margin-top: var(--space-lg);
}

/* Amélioration des cards principales */
.main-card {
    background: var(--card-background);
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    border: 1px solid rgba(var(--border-rgb), 0.1);
    transition: all 0.2s ease;
}

.main-card:hover {
    box-shadow: var(--shadow-hover);
}

.main-card-header {
    padding: var(--space-lg);
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.03), rgba(30, 64, 175, 0.01));
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: var(--radius-medium) var(--radius-medium) 0 0;
}

.main-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-xs);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.main-card-subtitle {
    font-size: var(--text-small);
    color: var(--muted);
    margin: 0;
}

.main-card-body {
    padding: var(--space-xl);
}

/* Styles pour la grille de disponibilités */
.availability-grid-container {
    overflow-x: auto;
}

.availability-grid {
    display: grid;
    grid-template-columns: 80px repeat(6, 1fr);
    gap: 1px;
    min-width: 600px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #dee2e6;
}

.availability-time-header,
.availability-day-header,
.availability-time-slot,
.availability-slot {
    padding: 8px;
    text-align: center;
    background: #fff;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 40px;
}

.availability-time-header {
    background: #f8f9fa;
    font-weight: 600;
    color: #495057;
}

.availability-day-header {
    background: #e9ecef;
    font-weight: 600;
    color: #495057;
}

.availability-time-slot {
    background: #f8f9fa;
    font-weight: 500;
    color: #6c757d;
    font-size: 0.8rem;
}

.availability-slot {
    cursor: default;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.availability-slot.available {
    background: #d1f2d1;
    color: #155724;
}

.availability-slot.preferred {
    background: #b8e6b8;
    color: #155724;
    font-weight: 600;
}

.availability-slot.unavailable {
    background: #f8d7da;
    color: #721c24;
}

/* Légende */
.availability-legend {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.legend-color.available {
    background: #d1f2d1;
}

.legend-color.preferred {
    background: #b8e6b8;
}

.legend-color.unavailable {
    background: #f8d7da;
}

.legend-text {
    font-size: 0.9rem;
    color: #495057;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .main-card-body {
        padding: var(--space-lg);
    }
    
    .availability-grid {
        grid-template-columns: 60px repeat(6, 1fr);
        min-width: 500px;
    }
    
    .availability-time-header,
    .availability-day-header,
    .availability-time-slot,
    .availability-slot {
        padding: 6px;
        font-size: 0.75rem;
        min-height: 35px;
    }
}

/* Couleurs personnalisées */
:root {
    --primary: #1e3a8a;
    --primary-rgb: 30, 58, 138;
    --danger: #dc3545;
    --danger-rgb: 220, 53, 69;
    --info: #0dcaf0;
    --info-rgb: 13, 202, 240;
}
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const enseignantSelect = document.getElementById('enseignant_id');
        const availabilityCard = document.getElementById('availability-card');
        const availabilityGrid = document.getElementById('availability-grid');
        const matiereSelect = document.getElementById('matiere_id');
        const matiereVolumeInfo = document.getElementById('matiere-volume-info');
        const volumeAlert = document.getElementById('volume-alert');
        
        // Configuration de la grille
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        const hours = Array.from({length: 11}, (_, i) => i + 8); // 8h à 18h
        
        // Fonction pour afficher la grille de disponibilités
        function displayAvailabilityGrid(availabilityData) {
            debugLog('Affichage disponibilités:', availabilityData);
            
            if (!availabilityData) {
                debugLog('Pas de données disponibilités');
                availabilityCard.style.display = 'none';
                return;
            }
            
            // Construire la grille HTML
            let gridHTML = '';
            
            // Header avec les jours
            gridHTML += '<div class="availability-time-header">Heure</div>';
            dayNames.forEach(dayName => {
                gridHTML += `<div class="availability-day-header">${dayName}</div>`;
            });
            
            // Lignes pour chaque heure
            hours.forEach((hour, hourIndex) => {
                const timeLabel = `${hour.toString().padStart(2, '0')}:00`;
                gridHTML += `<div class="availability-time-slot">${timeLabel}</div>`;
                
                days.forEach(day => {
                    const status = availabilityData[day] && availabilityData[day][hourIndex] 
                        ? availabilityData[day][hourIndex] 
                        : 'unavailable';
                    
                    gridHTML += `<div class="availability-slot ${status}" data-day="${day}" data-hour="${hour}"></div>`;
                });
            });
            
            availabilityGrid.innerHTML = gridHTML;
            availabilityCard.style.display = 'block';
            debugLog('Grille affichée');
        }
        
        // Événement de changement de sélection d'enseignant
        enseignantSelect.addEventListener('change', function() {
            debugLog('Changement enseignant détecté');
            const selectedOption = this.options[this.selectedIndex];
            debugLog('Option sélectionnée:', selectedOption);
            
            if (selectedOption.value && selectedOption.dataset.availability) {
                debugLog('Données brutes:', selectedOption.dataset.availability);
                try {
                    const availabilityData = JSON.parse(selectedOption.dataset.availability);
                    debugLog('Données parsées:', availabilityData);
                    displayAvailabilityGrid(availabilityData);
                } catch (error) {
                    debugError('Erreur lors du parsing des données de disponibilité:', error);
                    availabilityCard.style.display = 'none';
                }
            } else {
                debugLog('Pas de valeur ou données disponibilités');
                availabilityCard.style.display = 'none';
            }
        });
        
        // Fonction pour afficher les informations de volume de la matière
        function displayMatiereVolumeInfo(volumeInfo) {
            if (!volumeInfo) {
                matiereVolumeInfo.style.display = 'none';
                return;
            }
            
            let alertClass = 'info-box';
            let iconClass = 'fa-info-circle';
            let message = '';
            
            if (volumeInfo.non_configuree) {
                iconClass = 'fa-exclamation-triangle';
                message = `<i class="fas ${iconClass} me-2"></i><strong>Matière non configurée</strong><br>
                          Cette matière n'a pas encore de planification académique configurée. 
                          Veuillez d'abord configurer les volumes horaires dans la planification générale.`;
            } else if (volumeInfo.est_complete) {
                iconClass = 'fa-exclamation-circle';
                message = `<i class="fas ${iconClass} me-2"></i><strong>Volume horaire complètement utilisé</strong><br>
                          Cette matière a déjà atteint son volume horaire total de ${volumeInfo.volume_total}h. 
                          Impossible d'ajouter de nouvelles séances.`;
            } else {
                const pourcentage = volumeInfo.pourcentage_utilise;
                if (pourcentage >= 80) {
                    iconClass = 'fa-clock';
                } else {
                    iconClass = 'fa-check-circle';
                }
                
                message = `<i class="fas ${iconClass} me-2"></i><strong>Volume horaire disponible</strong><br>
                          <div class="row mt-2">
                              <div class="col-6">
                                  <small class="text-muted">Effectuées:</small> <strong>${volumeInfo.heures_effectuees}h</strong>
                              </div>
                              <div class="col-6">
                                  <small class="text-muted">Restantes:</small> <strong>${volumeInfo.heures_restantes}h</strong>
                              </div>
                          </div>
                          <div class="progress mt-2">
                              <div class="progress-bar" style="width: ${pourcentage}%"></div>
                          </div>
                          <small class="text-muted">${pourcentage}% du volume total (${volumeInfo.volume_total}h) utilisé</small>`;
                          
                if (volumeInfo.enseignant_principal) {
                    message += `<br><small class="text-muted">Enseignant principal: <strong>${volumeInfo.enseignant_principal.user ? volumeInfo.enseignant_principal.user.name : volumeInfo.enseignant_principal.name}</strong></small>`;
                }
            }
            
            volumeAlert.className = alertClass;
            volumeAlert.innerHTML = message;
            matiereVolumeInfo.style.display = 'block';
        }
        
        // Événement de changement de sélection de matière
        matiereSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value && selectedOption.dataset.volumeInfo) {
                try {
                    const volumeInfo = JSON.parse(selectedOption.dataset.volumeInfo);
                    displayMatiereVolumeInfo(volumeInfo);
                } catch (error) {
                    debugError('Erreur lors du parsing des données de volume:', error);
                    matiereVolumeInfo.style.display = 'none';
                }
            } else {
                matiereVolumeInfo.style.display = 'none';
            }
        });
        
        // Si un enseignant est pré-sélectionné (old input), afficher ses disponibilités
        if (enseignantSelect.value) {
            enseignantSelect.dispatchEvent(new Event('change'));
        }
        
        // Si une matière est pré-sélectionnée (old input), afficher ses informations
        if (matiereSelect.value) {
            matiereSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endsection