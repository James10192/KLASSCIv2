@extends('layouts.app')

@section('title', 'Modifier Enseignant - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .form-wizard {
        background: var(--surface);
        border-radius: var(--radius-large);
        overflow: hidden;
        box-shadow: var(--shadow-card);
    }
    
    .wizard-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-xl);
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .wizard-header::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100%;
        background: rgba(255,255,255,0.1);
        transform: skewX(-15deg);
        transform-origin: top;
    }
    
    .wizard-steps {
        display: flex;
        justify-content: space-between;
        background: rgba(255,255,255,0.1);
        padding: var(--space-md);
        margin: var(--space-md) 0 0;
        border-radius: var(--radius-medium);
    }
    
    .wizard-step {
        flex: 1;
        text-align: center;
        padding: var(--space-sm);
        border-radius: var(--radius-small);
        transition: all 0.3s ease;
        cursor: pointer;
        position: relative;
    }
    
    .wizard-step.active {
        background: rgba(255,255,255,0.2);
        transform: scale(1.05);
    }
    
    .wizard-step.completed {
        background: rgba(76, 175, 80, 0.3);
    }
    
    .wizard-step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(255,255,255,0.3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-xs);
        font-weight: bold;
        font-size: 0.9rem;
    }
    
    .wizard-step.active .wizard-step-number {
        background: white;
        color: var(--primary);
    }
    
    .wizard-step.completed .wizard-step-number {
        background: var(--success);
        color: white;
    }
    
    .wizard-step-title {
        font-size: 0.8rem;
        margin-bottom: var(--space-xs);
        font-weight: 600;
    }
    
    .wizard-step-desc {
        font-size: 0.7rem;
        opacity: 0.9;
    }
    
    .wizard-content {
        padding: var(--space-xl);
    }
    
    .form-section {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    
    .form-section.active {
        display: block;
    }
    
    .form-section-title {
        color: var(--primary);
        font-size: 1.4rem;
        font-weight: 600;
        margin-bottom: var(--space-md);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .form-section-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(var(--primary-rgb), 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: 1.2rem;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: var(--space-lg);
        margin-bottom: var(--space-lg);
    }
    
    .form-group-moderne {
        margin-bottom: var(--space-md);
    }
    
    .form-label-moderne {
        display: block;
        margin-bottom: var(--space-xs);
        font-weight: 500;
        color: var(--text-primary);
        font-size: 0.9rem;
    }
    
    .form-input-moderne,
    .form-select-moderne,
    .form-textarea-moderne {
        width: 100%;
        padding: var(--space-sm);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background: var(--surface);
        color: var(--text-primary);
    }
    
    .form-input-moderne:focus,
    .form-select-moderne:focus,
    .form-textarea-moderne:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    }
    
    .form-textarea-moderne {
        min-height: 80px;
        resize: vertical;
    }
    
    .form-help-text {
        font-size: 0.8rem;
        color: var(--text-secondary);
        margin-top: var(--space-xs);
    }
    
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-md);
        margin-top: var(--space-sm);
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        padding: var(--space-sm);
        background: var(--background);
        border-radius: var(--radius-small);
        cursor: pointer;
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .checkbox-item:hover {
        background: rgba(var(--primary-rgb), 0.05);
        border-color: var(--primary);
    }
    
    .checkbox-item input[type="checkbox"] {
        width: 18px;
        height: 18px;
        accent-color: var(--primary);
    }
    
    .availability-grid {
        display: grid;
        grid-template-columns: 120px repeat(7, 1fr);
        gap: var(--space-sm);
        margin-top: var(--space-md);
    }
    
    .availability-header {
        font-weight: 600;
        text-align: center;
        padding: var(--space-sm);
        background: var(--primary);
        color: white;
        border-radius: var(--radius-small);
        font-size: 0.8rem;
    }
    
    .availability-time {
        font-weight: 500;
        text-align: center;
        padding: var(--space-sm);
        background: var(--surface);
        border-radius: var(--radius-small);
        font-size: 0.8rem;
    }
    
    .availability-slot {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: var(--space-xs);
        border: 1px solid var(--border);
        border-radius: var(--radius-small);
        cursor: pointer;
        transition: all 0.3s ease;
        min-height: 35px;
    }
    
    .availability-slot:hover {
        background: rgba(var(--primary-rgb), 0.1);
    }
    
    .availability-slot.available {
        background: var(--success);
        color: white;
    }
    
    .availability-slot.preferred {
        background: var(--primary);
        color: white;
    }
    
    .availability-slot.unavailable {
        background: var(--danger);
        color: white;
    }
    
    .availability-legend {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        margin-top: var(--space-md);
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.8rem;
    }
    
    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: var(--radius-small);
    }
    
    .progress-bar {
        height: 4px;
        background: var(--border);
        border-radius: var(--radius-full);
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }
    
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: var(--radius-full);
        transition: width 0.3s ease;
    }
    
    .teacher-info-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        border: 1px solid var(--border);
    }
    
    .teacher-info-header {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        margin-bottom: var(--space-md);
    }
    
    .teacher-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: var(--primary);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
    }
    
    .teacher-details h3 {
        margin: 0 0 var(--space-xs) 0;
        color: var(--text-primary);
        font-size: 1.3rem;
    }
    
    .teacher-details p {
        margin: 0;
        color: var(--text-secondary);
        font-size: 0.9rem;
    }
    
    .teacher-meta {
        display: flex;
        gap: var(--space-lg);
        margin-top: var(--space-md);
    }
    
    .meta-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.9rem;
        color: var(--text-secondary);
    }
    
    .meta-icon {
        color: var(--primary);
    }
    
    .wizard-actions {
        display: flex;
        justify-content: space-between;
        padding: var(--space-lg);
        background: var(--background);
        border-top: 1px solid var(--border);
        margin-top: var(--space-xl);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .wizard-steps {
            flex-wrap: wrap;
        }
        
        .wizard-step {
            min-width: 120px;
        }
        
        .availability-grid {
            grid-template-columns: 80px repeat(7, 1fr);
            font-size: 0.7rem;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="card-moderne">
            <div class="card-header-moderne">
                <h1 class="section-title">
                    <i class="fas fa-user-edit me-2"></i>
                    Modifier Enseignant
                </h1>
                <p class="section-subtitle">Mise à jour du profil de l'enseignant</p>
            </div>
        </div>

        <!-- Informations actuelles de l'enseignant -->
        <div class="teacher-info-card">
            <div class="teacher-info-header">
                <div class="teacher-avatar">
                    {{ $teacher->user ? substr($teacher->user->name, 0, 2) : 'NN' }}
                </div>
                <div class="teacher-details">
                    <h3>{{ $teacher->user->name ?? 'Nom non disponible' }}</h3>
                    <p>{{ $teacher->user->email ?? 'Email non disponible' }}</p>
                    <p>{{ $teacher->specialization }}</p>
                </div>
            </div>
            <div class="teacher-meta">
                <div class="meta-item">
                    <i class="fas fa-id-card meta-icon"></i>
                    <span>{{ $teacher->matricule }}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-calendar meta-icon"></i>
                    <span>Créé le {{ $teacher->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-building meta-icon"></i>
                    <span>{{ $teacher->department->name ?? 'Aucun département' }}</span>
                </div>
                <div class="meta-item">
                    <i class="fas fa-circle meta-icon {{ $teacher->status === 'active' ? 'text-success' : 'text-danger' }}"></i>
                    <span>{{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}</span>
                </div>
            </div>
        </div>

        <div class="form-wizard">
            <div class="wizard-header">
                <h2>Modification du Profil Enseignant</h2>
                <p>Mettez à jour les informations selon vos besoins</p>
                
                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <div class="wizard-step-number">1</div>
                        <div class="wizard-step-title">Informations Personnelles</div>
                        <div class="wizard-step-desc">Identité & Contact</div>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="wizard-step-number">2</div>
                        <div class="wizard-step-title">Qualifications</div>
                        <div class="wizard-step-desc">Diplômes & Expérience</div>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="wizard-step-number">3</div>
                        <div class="wizard-step-title">Informations Professionnelles</div>
                        <div class="wizard-step-desc">Contrat & Affectation</div>
                    </div>
                    <div class="wizard-step" data-step="4">
                        <div class="wizard-step-number">4</div>
                        <div class="wizard-step-title">Disponibilités</div>
                        <div class="wizard-step-desc">Horaires & Préférences</div>
                    </div>
                    <div class="wizard-step" data-step="5">
                        <div class="wizard-step-number">5</div>
                        <div class="wizard-step-title">Finalisation</div>
                        <div class="wizard-step-desc">Documents & Validation</div>
                    </div>
                </div>
            </div>

            <div class="progress-bar">
                <div class="progress-fill" style="width: 20%"></div>
            </div>

            <form action="{{ route('esbtp.enseignants.update', $teacher) }}" method="POST" enctype="multipart/form-data" id="teacherForm">
                @csrf
                @method('PUT')
                <div class="wizard-content">
                    
                    <!-- Étape 1: Informations Personnelles -->
                    <div class="form-section active" id="step-1">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            Informations Personnelles
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="name" class="form-label-moderne">
                                    Nom complet <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="name" id="name" 
                                       class="form-input-moderne @error('name') is-invalid @enderror"
                                       value="{{ old('name', $teacher->user->name ?? '') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="email" class="form-label-moderne">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <input type="email" name="email" id="email" 
                                       class="form-input-moderne @error('email') is-invalid @enderror"
                                       value="{{ old('email', $teacher->user->email ?? '') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-help-text">
                                    Adresse email pour se connecter au système
                                </div>
                            </div>

                            <div class="form-group-moderne">
                                <label for="phone" class="form-label-moderne">
                                    Téléphone
                                </label>
                                <input type="tel" name="phone" id="phone" 
                                       class="form-input-moderne @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $teacher->user->phone ?? '') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="password" class="form-label-moderne">
                                    Nouveau mot de passe
                                </label>
                                <input type="password" name="password" id="password" 
                                       class="form-input-moderne @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-help-text">
                                    Laissez vide pour conserver l'ancien mot de passe
                                </div>
                            </div>

                            <div class="form-group-moderne">
                                <label for="password_confirmation" class="form-label-moderne">
                                    Confirmer le nouveau mot de passe
                                </label>
                                <input type="password" name="password_confirmation" id="password_confirmation" 
                                       class="form-input-moderne">
                            </div>
                        </div>
                    </div>

                    <!-- Étape 2: Qualifications -->
                    <div class="form-section" id="step-2">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            Qualifications & Expérience
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="specialization" class="form-label-moderne">
                                    Spécialisation <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="specialization" id="specialization" 
                                       class="form-input-moderne @error('specialization') is-invalid @enderror"
                                       value="{{ old('specialization', $teacher->specialization) }}" required
                                       placeholder="ex: Développement Web, Réseaux Informatiques, Base de Données">
                                @error('specialization')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-help-text">
                                    Domaine d'expertise principal
                                </div>
                            </div>

                            <div class="form-group-moderne">
                                <label for="bio" class="form-label-moderne">
                                    Biographie
                                </label>
                                <textarea name="bio" id="bio" 
                                          class="form-textarea-moderne @error('bio') is-invalid @enderror"
                                          placeholder="Décrivez votre parcours, vos centres d'intérêt...">{{ old('bio', $teacher->bio) }}</textarea>
                                @error('bio')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="website" class="form-label-moderne">
                                    Site web / Portfolio
                                </label>
                                <input type="url" name="website" id="website" 
                                       class="form-input-moderne @error('website') is-invalid @enderror"
                                       value="{{ old('website', $teacher->website) }}"
                                       placeholder="https://monsite.com">
                                @error('website')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Étape 3: Informations Professionnelles -->
                    <div class="form-section" id="step-3">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            Informations Professionnelles
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group-moderne">
                                <label for="department_id" class="form-label-moderne">
                                    Département <span class="text-danger">*</span>
                                </label>
                                <select name="department_id" id="department_id" 
                                        class="form-select-moderne @error('department_id') is-invalid @enderror" required>
                                    <option value="">Sélectionnez un département</option>
                                    @foreach($departments as $department)
                                        <option value="{{ $department->id }}" 
                                                {{ old('department_id', $teacher->department_id) == $department->id ? 'selected' : '' }}>
                                            {{ $department->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('department_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="laboratory_id" class="form-label-moderne">
                                    Laboratoire
                                </label>
                                <select name="laboratory_id" id="laboratory_id" 
                                        class="form-select-moderne @error('laboratory_id') is-invalid @enderror">
                                    <option value="">Aucun laboratoire</option>
                                    @foreach($laboratories as $laboratory)
                                        <option value="{{ $laboratory->id }}" 
                                                {{ old('laboratory_id', $teacher->laboratory_id) == $laboratory->id ? 'selected' : '' }}>
                                            {{ $laboratory->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('laboratory_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="status" class="form-label-moderne">
                                    Statut <span class="text-danger">*</span>
                                </label>
                                <select name="status" id="status" 
                                        class="form-select-moderne @error('status') is-invalid @enderror" required>
                                    <option value="active" {{ old('status', $teacher->status) == 'active' ? 'selected' : '' }}>
                                        Actif
                                    </option>
                                    <option value="inactive" {{ old('status', $teacher->status) == 'inactive' ? 'selected' : '' }}>
                                        Inactif
                                    </option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group-moderne">
                                <label for="teaching_hours_due" class="form-label-moderne">
                                    Heures d'enseignement dues
                                </label>
                                <input type="number" name="teaching_hours_due" id="teaching_hours_due" 
                                       class="form-input-moderne @error('teaching_hours_due') is-invalid @enderror"
                                       value="{{ old('teaching_hours_due', $teacher->teaching_hours_due) }}"
                                       min="0" max="80">
                                @error('teaching_hours_due')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Étape 4: Disponibilités -->
                    <div class="form-section" id="step-4">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            Disponibilités & Préférences
                        </div>
                        
                        <div class="form-group-moderne">
                            <label class="form-label-moderne">Grille de disponibilité</label>
                            <div class="form-help-text">
                                Cliquez sur les créneaux pour modifier la disponibilité
                            </div>
                            
                            <div class="availability-grid">
                                <div class="availability-header">Heure</div>
                                <div class="availability-header">Lun</div>
                                <div class="availability-header">Mar</div>
                                <div class="availability-header">Mer</div>
                                <div class="availability-header">Jeu</div>
                                <div class="availability-header">Ven</div>
                                <div class="availability-header">Sam</div>
                                <div class="availability-header">Dim</div>
                                
                                @for($hour = 8; $hour <= 18; $hour++)
                                    <div class="availability-time">{{ sprintf('%02d:00', $hour) }}</div>
                                    @for($day = 0; $day < 7; $day++)
                                        <div class="availability-slot" 
                                             data-day="{{ $day }}" 
                                             data-hour="{{ $hour }}"
                                             onclick="toggleAvailability(this)">
                                        </div>
                                    @endfor
                                @endfor
                            </div>
                            
                            <div class="availability-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background: var(--success);"></div>
                                    <span>Disponible</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: var(--primary);"></div>
                                    <span>Préféré</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background: var(--danger);"></div>
                                    <span>Indisponible</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Étape 5: Finalisation -->
                    <div class="form-section" id="step-5">
                        <div class="form-section-title">
                            <div class="form-section-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            Résumé des Modifications
                        </div>
                        
                        <div class="form-group-moderne">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Vérifiez les informations saisies avant de valider les modifications.
                            </div>
                        </div>
                    </div>

                </div>

                <div class="wizard-actions">
                    <div class="actions-left">
                        <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-acasi secondary">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                        <button type="button" class="btn-acasi secondary" id="prevBtn" onclick="changeStep(-1)" style="display: none;">
                            <i class="fas fa-arrow-left me-1"></i>Précédent
                        </button>
                    </div>
                    
                    <div class="actions-right">
                        <button type="button" class="btn-acasi primary" id="nextBtn" onclick="changeStep(1)">
                            Suivant<i class="fas fa-arrow-right ms-1"></i>
                        </button>
                        
                        <button type="submit" class="btn-acasi success" id="submitBtn" style="display: none;">
                            <i class="fas fa-save me-1"></i>Mettre à jour
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let currentStep = 1;
const totalSteps = 5;

function changeStep(direction) {
    const newStep = currentStep + direction;
    
    if (newStep < 1 || newStep > totalSteps) {
        return;
    }
    
    // Validation avant de passer à l'étape suivante
    if (direction > 0 && !validateStep(currentStep)) {
        return;
    }
    
    // Masquer l'étape actuelle
    document.getElementById(`step-${currentStep}`).classList.remove('active');
    document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
    
    // Marquer l'étape comme terminée si on avance
    if (direction > 0) {
        document.querySelector(`[data-step="${currentStep}"]`).classList.add('completed');
    }
    
    // Afficher la nouvelle étape
    currentStep = newStep;
    document.getElementById(`step-${currentStep}`).classList.add('active');
    document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
    
    // Mettre à jour les boutons
    updateButtons();
    
    // Mettre à jour la barre de progression
    updateProgress();
}

function updateButtons() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const submitBtn = document.getElementById('submitBtn');
    
    prevBtn.style.display = currentStep > 1 ? 'block' : 'none';
    nextBtn.style.display = currentStep < totalSteps ? 'block' : 'none';
    submitBtn.style.display = currentStep === totalSteps ? 'block' : 'none';
}

function updateProgress() {
    const progress = (currentStep / totalSteps) * 100;
    document.querySelector('.progress-fill').style.width = progress + '%';
}

function validateStep(step) {
    const stepElement = document.getElementById(`step-${step}`);
    const requiredFields = stepElement.querySelectorAll('[required]');
    
    for (let field of requiredFields) {
        if (!field.value.trim()) {
            field.focus();
            field.classList.add('is-invalid');
            return false;
        } else {
            field.classList.remove('is-invalid');
        }
    }
    
    return true;
}

// Gestion des disponibilités
function toggleAvailability(element) {
    const classes = ['available', 'preferred', 'unavailable'];
    let currentClass = '';
    
    for (let cls of classes) {
        if (element.classList.contains(cls)) {
            currentClass = cls;
            break;
        }
    }
    
    // Supprimer toutes les classes
    element.classList.remove(...classes);
    
    // Ajouter la classe suivante
    const currentIndex = classes.indexOf(currentClass);
    const nextIndex = (currentIndex + 1) % classes.length;
    element.classList.add(classes[nextIndex]);
}

// Validation en temps réel
document.querySelectorAll('input[required], select[required]').forEach(field => {
    field.addEventListener('blur', function() {
        if (!this.value.trim()) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
});

// Navigation par les étapes
document.querySelectorAll('.wizard-step').forEach((step, index) => {
    step.addEventListener('click', function() {
        const targetStep = index + 1;
        if (targetStep <= currentStep + 1) {
            changeStep(targetStep - currentStep);
        }
    });
});

// Gestion des mots de passe
document.getElementById('password').addEventListener('input', function() {
    const confirmField = document.getElementById('password_confirmation');
    if (this.value) {
        confirmField.required = true;
        confirmField.parentElement.querySelector('.form-label-moderne').innerHTML = 
            'Confirmer le nouveau mot de passe <span class="text-danger">*</span>';
    } else {
        confirmField.required = false;
        confirmField.parentElement.querySelector('.form-label-moderne').innerHTML = 
            'Confirmer le nouveau mot de passe';
    }
});

// Initialisation
updateButtons();
updateProgress();
</script>
@endpush 