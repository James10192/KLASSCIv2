@extends('layouts.app')

@section('title', 'Ajouter une séance - KLASSCI')

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
                            <label class="form-label">Type de séance <span class="text-danger">*</span></label>
                            <x-au-select
                                name="type_seance"
                                :value="old('type_seance', 'CM')"
                                icon="fa-tag"
                                placeholder="Sélectionner un type"
                                :options="\App\Enums\TypeSeance::selectOptions()" />
                            @error('type_seance')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>

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
                    <div class="main-card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <div class="main-card-title">
                                <i class="fas fa-calendar-check"></i>
                                Disponibilités de l'enseignant
                            </div>
                            <div class="main-card-subtitle">Grille des créneaux disponibles — cliquez sur "Modifier" pour éditer</div>
                        </div>
                        <div class="availability-actions" style="display: flex; gap: 8px;">
                            <button type="button" class="btn-edit-availability" id="btn-edit-avail" onclick="toggleInlineEdit()">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </button>
                            <button type="button" class="btn-save-availability" id="btn-save-avail" style="display: none;" onclick="saveInlineAvailability()">
                                <i class="fas fa-save me-1"></i>Sauvegarder
                            </button>
                            <button type="button" class="btn-cancel-availability" id="btn-cancel-avail" style="display: none;" onclick="cancelInlineEdit()">
                                <i class="fas fa-times me-1"></i>Annuler
                            </button>
                        </div>
                    </div>
                    <div class="main-card-body">
                        <div id="availability-save-status" style="display: none;" class="mb-3"></div>
                        <div class="availability-grid-container">
                            <div class="availability-grid" id="availability-grid">
                                <!-- Grille générée par JavaScript -->
                            </div>
                        </div>
                        <div class="availability-legend mt-3">
                            <div class="d-flex flex-wrap gap-3 justify-content-center">
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; width: 20px; height: 20px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <span class="legend-text">Préféré</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; width: 20px; height: 20px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;">
                                        <i class="fas fa-check"></i>
                                    </div>
                                    <span class="legend-text">Disponible</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; width: 20px; height: 20px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem;">
                                        <i class="fas fa-minus"></i>
                                    </div>
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
    grid-template-columns: 72px repeat(6, 1fr);
    background: #ffffff;
    padding: 0;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    gap: 0;
    overflow: hidden;
    min-width: 550px;
}

.availability-time-header {
    grid-column: 1;
    font-weight: 700;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
    text-align: center;
    padding: 10px 4px;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    border-right: 2px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.availability-day-header {
    text-align: center;
    font-weight: 700;
    font-size: 0.8rem;
    color: #1e40af;
    padding: 10px 4px;
    background: linear-gradient(180deg, #eff6ff 0%, #f8fafc 100%);
    border-bottom: 2px solid #e2e8f0;
    border-right: 1px solid #f1f5f9;
    letter-spacing: 0.3px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.availability-time-slot {
    text-align: center;
    padding: 0 4px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    border-right: 2px solid #e2e8f0;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8fafc;
    min-height: 38px;
}

.availability-slot {
    padding: 4px 2px;
    text-align: center;
    font-size: 0.72rem;
    font-weight: 600;
    transition: all 0.15s ease;
    min-height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f1f5f9;
    position: relative;
    gap: 3px;
    user-select: none;
}

.availability-slot.available {
    background: #dcfce7;
    color: #15803d;
    border-bottom-color: #bbf7d0;
}

.availability-slot.available i { color: #16a34a; font-size: 0.7rem; }

.availability-slot.preferred {
    background: #dbeafe;
    color: #1d4ed8;
    border-bottom-color: #bfdbfe;
}

.availability-slot.preferred i { color: #2563eb; font-size: 0.7rem; }

.availability-slot.unavailable {
    background: #fee2e2;
    color: #991b1b;
    border-bottom-color: #fecaca;
}

.availability-slot.unavailable i { color: #dc2626; font-size: 0.65rem; opacity: 0.6; }

.availability-slot:hover {
    filter: brightness(0.95);
    box-shadow: inset 0 0 0 2px rgba(0,0,0,0.08);
}

.availability-slot.modified {
    box-shadow: inset 0 0 0 2px #f59e0b;
}

.availability-slot.modified::after {
    content: '';
    position: absolute;
    top: 3px;
    right: 3px;
    width: 6px;
    height: 6px;
    background: #f59e0b;
    border-radius: 50%;
}

.availability-slot .slot-label { display: inline; }

/* Légende */
.availability-legend {
    padding: 12px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.legend-text {
    font-size: 0.8rem;
    color: #475569;
    font-weight: 500;
}

/* Boutons édition */
.btn-edit-availability,
.btn-save-availability,
.btn-cancel-availability {
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-edit-availability {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color: white;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3);
}

.btn-edit-availability:hover {
    box-shadow: 0 4px 8px rgba(37, 99, 235, 0.4);
    transform: translateY(-1px);
}

.btn-save-availability {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    color: white;
    box-shadow: 0 2px 4px rgba(22, 163, 74, 0.3);
}

.btn-save-availability:hover {
    box-shadow: 0 4px 8px rgba(22, 163, 74, 0.4);
    transform: translateY(-1px);
}

.btn-cancel-availability {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.btn-cancel-availability:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fecaca;
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
        grid-template-columns: 52px repeat(6, 1fr);
        min-width: 450px;
    }

    .availability-slot {
        min-height: 32px;
        font-size: 0.6rem;
    }

    .availability-slot .slot-label {
        display: none;
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
        const dayNames = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        const dayNamesFull = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        const hours = Array.from({length: 11}, (_, i) => i + 8); // 8h à 18h

        // État édition inline
        let inlineEditMode = false;
        let inlineOriginalData = {};
        let inlineModifiedSlots = new Set();

        // Helpers debug (no-op en prod)
        const debugLog = () => {};
        const debugError = console.error.bind(console);

        // Icônes FA pour chaque statut
        const statusIcons = {
            unavailable: '<i class="fas fa-minus"></i>',
            available: '<i class="fas fa-check"></i><span class="slot-label">Dispo</span>',
            preferred: '<i class="fas fa-star"></i><span class="slot-label">Préf.</span>'
        };

        // Fonction pour afficher la grille de disponibilités
        function displayAvailabilityGrid(availabilityData) {
            debugLog('Affichage disponibilités:', availabilityData);

            if (!availabilityData) {
                debugLog('Pas de données disponibilités');
                availabilityCard.style.display = 'none';
                return;
            }

            // Reset edit state
            inlineEditMode = false;
            inlineOriginalData = {};
            inlineModifiedSlots.clear();
            document.getElementById('btn-edit-avail').style.display = 'flex';
            document.getElementById('btn-save-avail').style.display = 'none';
            document.getElementById('btn-cancel-avail').style.display = 'none';
            document.getElementById('availability-save-status').style.display = 'none';

            // Construire la grille HTML
            let gridHTML = '';

            // Header avec les jours
            gridHTML += '<div class="availability-time-header">Horaires</div>';
            dayNames.forEach(dayName => {
                gridHTML += `<div class="availability-day-header">${dayName}</div>`;
            });

            // Lignes pour chaque heure
            hours.forEach((hour, hourIndex) => {
                const timeLabel = `${hour.toString().padStart(2, '0')}:00`;
                gridHTML += `<div class="availability-time-slot">${timeLabel}</div>`;

                days.forEach((day, dayIndex) => {
                    const status = availabilityData[day] && availabilityData[day][hourIndex]
                        ? availabilityData[day][hourIndex]
                        : 'unavailable';

                    gridHTML += `<div class="availability-slot ${status}"
                        id="inline-slot-${hourIndex}-${dayIndex}"
                        data-day="${dayIndex}"
                        data-hour="${hour}"
                        data-time-index="${hourIndex}"
                        data-original-status="${status}"
                        title="${dayNamesFull[dayIndex]} ${hour.toString().padStart(2, '0')}:00 - ${status === 'preferred' ? 'Préféré' : status === 'available' ? 'Disponible' : 'Indisponible'}">
                        ${statusIcons[status]}
                    </div>`;
                });
            });

            availabilityGrid.innerHTML = gridHTML;
            availabilityCard.style.display = 'block';
            debugLog('Grille affichée');
        }

        // Toggle inline edit mode
        window.toggleInlineEdit = function() {
            inlineEditMode = !inlineEditMode;
            const slots = availabilityGrid.querySelectorAll('.availability-slot');
            const editBtn = document.getElementById('btn-edit-avail');
            const saveBtn = document.getElementById('btn-save-avail');
            const cancelBtn = document.getElementById('btn-cancel-avail');

            if (inlineEditMode) {
                slots.forEach(slot => {
                    slot.style.cursor = 'pointer';
                    slot.onclick = () => toggleInlineSlot(slot);
                    inlineOriginalData[slot.id] = slot.dataset.originalStatus;
                });
                editBtn.style.display = 'none';
                saveBtn.style.display = 'flex';
                cancelBtn.style.display = 'flex';
                availabilityGrid.style.boxShadow = 'inset 0 0 0 2px #f59e0b';
            } else {
                slots.forEach(slot => {
                    slot.style.cursor = 'default';
                    slot.onclick = null;
                });
                editBtn.style.display = 'flex';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                availabilityGrid.style.boxShadow = 'none';
            }
        };

        // Toggle a single slot status
        function toggleInlineSlot(slot) {
            if (!inlineEditMode) return;

            const statuses = ['unavailable', 'available', 'preferred'];
            let currentStatus = statuses.find(s => slot.classList.contains(s)) || 'unavailable';
            const nextIndex = (statuses.indexOf(currentStatus) + 1) % statuses.length;
            const nextStatus = statuses[nextIndex];

            statuses.forEach(s => slot.classList.remove(s));
            slot.classList.add(nextStatus);
            slot.innerHTML = statusIcons[nextStatus];

            if (nextStatus !== inlineOriginalData[slot.id]) {
                slot.classList.add('modified');
                inlineModifiedSlots.add(slot.id);
            } else {
                slot.classList.remove('modified');
                inlineModifiedSlots.delete(slot.id);
            }

            // Update tooltip
            const dayIndex = parseInt(slot.dataset.day);
            const hour = slot.dataset.hour;
            const statusNames = { unavailable: 'Indisponible', available: 'Disponible', preferred: 'Préféré' };
            slot.title = `${dayNamesFull[dayIndex]} ${hour}:00 - ${statusNames[nextStatus]}`;
        }

        // Cancel inline edit
        window.cancelInlineEdit = function() {
            inlineModifiedSlots.forEach(slotId => {
                const slot = document.getElementById(slotId);
                if (!slot) return;
                const originalStatus = inlineOriginalData[slotId];
                const statuses = ['unavailable', 'available', 'preferred'];
                statuses.forEach(s => slot.classList.remove(s));
                slot.classList.add(originalStatus);
                slot.innerHTML = statusIcons[originalStatus];
                slot.classList.remove('modified');
            });
            inlineModifiedSlots.clear();
            toggleInlineEdit();
        };

        // Save inline availability via AJAX
        window.saveInlineAvailability = function() {
            const enseignantId = enseignantSelect.value;
            if (!enseignantId) return;

            if (inlineModifiedSlots.size === 0) {
                showSaveStatus('Aucune modification à sauvegarder.', 'warning');
                return;
            }

            const changedSlots = [];
            inlineModifiedSlots.forEach(slotId => {
                const slot = document.getElementById(slotId);
                if (!slot) return;
                const statuses = ['unavailable', 'available', 'preferred'];
                const currentStatus = statuses.find(s => slot.classList.contains(s));
                const timeIndex = parseInt(slot.dataset.timeIndex);
                const startHour = 8 + timeIndex;
                const endHour = startHour + 1;

                changedSlots.push({
                    day: parseInt(slot.dataset.day),
                    startTime: String(startHour).padStart(2, '0') + ':00',
                    endTime: String(endHour).padStart(2, '0') + ':00',
                    status: currentStatus
                });
            });

            showSaveStatus('Sauvegarde en cours...', 'info');

            fetch(`/esbtp/enseignants/${enseignantId}/update-availability`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ changes: changedSlots })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSaveStatus('Disponibilités mises à jour avec succès !', 'success');

                    // Update original data + data-attributes
                    inlineModifiedSlots.forEach(slotId => {
                        const slot = document.getElementById(slotId);
                        if (!slot) return;
                        const statuses = ['unavailable', 'available', 'preferred'];
                        const currentStatus = statuses.find(s => slot.classList.contains(s));
                        inlineOriginalData[slotId] = currentStatus;
                        slot.dataset.originalStatus = currentStatus;
                        slot.classList.remove('modified');
                    });
                    inlineModifiedSlots.clear();

                    // Update the select option data-availability so it stays in sync
                    updateSelectAvailability(enseignantId);

                    toggleInlineEdit();
                } else {
                    showSaveStatus('Erreur: ' + (data.message || 'Erreur inconnue'), 'danger');
                }
            })
            .catch(error => {
                showSaveStatus('Erreur de connexion: ' + error.message, 'danger');
            });
        };

        // Rebuild availability JSON from the grid and update the select option
        function updateSelectAvailability(enseignantId) {
            const newData = {};
            days.forEach(day => { newData[day] = []; });

            hours.forEach((hour, hourIndex) => {
                days.forEach((day, dayIndex) => {
                    const slot = document.getElementById(`inline-slot-${hourIndex}-${dayIndex}`);
                    if (slot) {
                        const statuses = ['unavailable', 'available', 'preferred'];
                        const st = statuses.find(s => slot.classList.contains(s)) || 'unavailable';
                        newData[day][hourIndex] = st;
                    }
                });
            });

            // Update the option's data-availability
            const option = enseignantSelect.querySelector(`option[value="${enseignantId}"]`);
            if (option) {
                option.dataset.availability = JSON.stringify(newData);
            }
        }

        // Show save status message
        function showSaveStatus(message, type) {
            const el = document.getElementById('availability-save-status');
            const colors = {
                info: { bg: '#eff6ff', border: '#bfdbfe', color: '#1d4ed8' },
                success: { bg: '#f0fdf4', border: '#bbf7d0', color: '#15803d' },
                warning: { bg: '#fefce8', border: '#fde68a', color: '#92400e' },
                danger: { bg: '#fef2f2', border: '#fecaca', color: '#991b1b' }
            };
            const c = colors[type] || colors.info;
            el.style.display = 'block';
            el.style.padding = '10px 16px';
            el.style.borderRadius = '8px';
            el.style.fontSize = '0.85rem';
            el.style.fontWeight = '500';
            el.style.background = c.bg;
            el.style.border = `1px solid ${c.border}`;
            el.style.color = c.color;
            el.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : type === 'danger' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'spinner fa-spin'} me-2"></i>${message}`;

            if (type === 'success') {
                setTimeout(() => { el.style.display = 'none'; }, 4000);
            }
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