@extends('layouts.app')

@section('title', 'Rapport de cours')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .report-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: var(--space-lg);
    }

    .course-info-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        text-align: center;
        box-shadow: var(--shadow-card);
    }

    .course-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin-bottom: var(--space-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-md);
    }

    .course-details {
        display: flex;
        justify-content: center;
        gap: var(--space-xl);
        flex-wrap: wrap;
        margin-top: var(--space-md);
        opacity: 0.9;
    }

    .course-detail-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .report-form-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .form-header {
        background: linear-gradient(135deg, var(--accent-blue), #0891b2);
        color: white;
        padding: var(--space-lg);
        text-align: center;
    }

    .form-title {
        font-size: var(--title-section);
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-md);
    }

    .form-content {
        padding: var(--space-xl);
    }

    .form-group {
        margin-bottom: var(--space-lg);
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
        font-size: var(--text-normal);
    }

    .form-label.required::after {
        content: " *";
        color: var(--danger);
    }

    .form-input, .form-textarea, .form-select {
        width: 100%;
        padding: var(--space-md);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        background: var(--surface);
        color: var(--text-primary);
    }

    .form-input:focus, .form-textarea:focus, .form-select:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .form-textarea.content-summary {
        min-height: 150px;
    }

    .char-counter {
        text-align: right;
        margin-top: var(--space-xs);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .char-counter.error {
        color: var(--danger);
        font-weight: 600;
    }

    .char-counter.valid {
        color: var(--success);
        font-weight: 600;
    }

    .form-help {
        font-size: var(--text-small);
        color: var(--text-secondary);
        margin-top: var(--space-xs);
        line-height: 1.4;
    }

    .behavior-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-top: var(--space-sm);
    }

    .behavior-option {
        position: relative;
    }

    .behavior-radio {
        display: none;
    }

    .behavior-label {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-md);
        border: 2px solid var(--border);
        border-radius: var(--radius-medium);
        cursor: pointer;
        transition: all 0.3s ease;
        background: var(--surface);
    }

    .behavior-label:hover {
        border-color: var(--primary);
        background: rgba(59, 130, 246, 0.05);
    }

    .behavior-radio:checked + .behavior-label {
        border-color: var(--primary);
        background: rgba(59, 130, 246, 0.1);
        color: var(--primary);
        font-weight: 600;
    }

    .behavior-icon {
        font-size: 20px;
    }

    .behavior-text {
        flex: 1;
    }

    .form-actions {
        padding: var(--space-lg);
        background: #f8fafc;
        border-top: 1px solid var(--border);
        display: flex;
        justify-content: center;
        gap: var(--space-md);
        flex-wrap: wrap;
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-md) var(--space-xl);
        border: none;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        text-decoration: none;
        cursor: pointer;
        min-width: 150px;
        justify-content: center;
    }

    .btn-modern.primary {
        background: linear-gradient(135deg, var(--success), #059669);
        color: white;
        box-shadow: var(--shadow-card);
    }

    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .btn-modern.secondary {
        background: linear-gradient(135deg, var(--neutral), #6b7280);
        color: white;
    }

    .btn-modern.secondary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .btn-modern.draft {
        background: linear-gradient(135deg, var(--warning), #d97706);
        color: white;
    }

    .btn-modern.draft:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .alert {
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-lg);
        border: 1px solid;
    }

    .alert-info {
        background: rgba(59, 130, 246, 0.1);
        border-color: rgba(59, 130, 246, 0.2);
        color: var(--primary);
    }

    .existing-report-notice {
        background: rgba(251, 191, 36, 0.1);
        border: 1px solid rgba(251, 191, 36, 0.2);
        color: #d97706;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        text-align: center;
    }

    @media (max-width: 768px) {
        .course-details {
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .behavior-options {
            grid-template-columns: 1fr;
        }
        
        .form-actions {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
<div class="report-container">
    <!-- Informations du cours -->
    <div class="course-info-header">
        <h1 class="course-title">
            <i class="fas fa-file-alt"></i>
            Rapport de cours
        </h1>
        <div class="course-details">
            <div class="course-detail-item">
                <i class="fas fa-book"></i>
                <span>{{ $seance->matiere->name ?? 'Matière non définie' }}</span>
            </div>
            <div class="course-detail-item">
                <i class="fas fa-users"></i>
                <span>{{ $seance->classe->name ?? 'Classe non définie' }}</span>
            </div>
            <div class="course-detail-item">
                <i class="fas fa-clock"></i>
                <span>
                    {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }} - 
                    {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
                </span>
            </div>
            <div class="course-detail-item">
                <i class="fas fa-calendar"></i>
                <span>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($existingReport && $existingReport->status === 'draft')
        <div class="existing-report-notice">
            <i class="fas fa-info-circle me-2"></i>
            <strong>Brouillon existant trouvé</strong> - Vous pouvez continuer à modifier votre rapport en cours ou le soumettre.
        </div>
    @endif

    <div class="alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Important :</strong> Le résumé du contenu doit contenir au minimum 30 caractères. Vous pouvez sauvegarder un brouillon ou soumettre directement le rapport final.
    </div>

    <!-- Formulaire de rapport -->
    <form id="reportForm" method="POST" action="{{ route('teacher.session-report.store', $seance->id) }}">
        @csrf
        
        <div class="report-form-card">
            <div class="form-header">
                <h2 class="form-title">
                    <i class="fas fa-edit"></i>
                    Créer le rapport de séance
                </h2>
            </div>
            
            <div class="form-content">
                <!-- Résumé du contenu (obligatoire, min 30 caractères) -->
                <div class="form-group">
                    <label for="content_summary" class="form-label required">Résumé du contenu enseigné</label>
                    <textarea 
                        name="content_summary" 
                        id="content_summary" 
                        class="form-textarea content-summary" 
                        placeholder="Décrivez le contenu principal de la séance, les notions abordées, les activités réalisées..."
                        required>{{ old('content_summary', $existingReport->content_summary ?? '') }}</textarea>
                    <div class="char-counter" id="contentCounter">0 / 30 caractères minimum</div>
                    <div class="form-help">Minimum 30 caractères requis. Soyez précis et détaillé sur ce qui a été enseigné.</div>
                    @error('content_summary')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Méthodes pédagogiques utilisées -->
                <div class="form-group">
                    <label for="teaching_methods" class="form-label">Méthodes pédagogiques utilisées</label>
                    <textarea 
                        name="teaching_methods" 
                        id="teaching_methods" 
                        class="form-textarea" 
                        placeholder="Cours magistral, travaux pratiques, exercices, discussions, présentations...">{{ old('teaching_methods', $existingReport->teaching_methods ?? '') }}</textarea>
                    <div class="form-help">Décrivez les méthodes et techniques pédagogiques employées durant la séance.</div>
                </div>

                <!-- Comportement des étudiants -->
                <div class="form-group">
                    <label class="form-label required">Comportement général des étudiants</label>
                    <div class="behavior-options">
                        <div class="behavior-option">
                            <input type="radio" name="student_behavior" value="excellent" id="behavior_excellent" 
                                   class="behavior-radio" {{ old('student_behavior', $existingReport->student_behavior ?? '') === 'excellent' ? 'checked' : '' }}>
                            <label for="behavior_excellent" class="behavior-label">
                                <span class="behavior-icon" style="color: var(--success);">😊</span>
                                <span class="behavior-text">Excellent</span>
                            </label>
                        </div>
                        <div class="behavior-option">
                            <input type="radio" name="student_behavior" value="good" id="behavior_good" 
                                   class="behavior-radio" {{ old('student_behavior', $existingReport->student_behavior ?? 'good') === 'good' ? 'checked' : '' }}>
                            <label for="behavior_good" class="behavior-label">
                                <span class="behavior-icon" style="color: var(--primary);">🙂</span>
                                <span class="behavior-text">Bon</span>
                            </label>
                        </div>
                        <div class="behavior-option">
                            <input type="radio" name="student_behavior" value="satisfactory" id="behavior_satisfactory" 
                                   class="behavior-radio" {{ old('student_behavior', $existingReport->student_behavior ?? '') === 'satisfactory' ? 'checked' : '' }}>
                            <label for="behavior_satisfactory" class="behavior-label">
                                <span class="behavior-icon" style="color: var(--warning);">😐</span>
                                <span class="behavior-text">Satisfaisant</span>
                            </label>
                        </div>
                        <div class="behavior-option">
                            <input type="radio" name="student_behavior" value="difficult" id="behavior_difficult" 
                                   class="behavior-radio" {{ old('student_behavior', $existingReport->student_behavior ?? '') === 'difficult' ? 'checked' : '' }}>
                            <label for="behavior_difficult" class="behavior-label">
                                <span class="behavior-icon" style="color: var(--danger);">😤</span>
                                <span class="behavior-text">Difficile</span>
                            </label>
                        </div>
                    </div>
                    @error('student_behavior')
                        <div class="text-danger mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Difficultés rencontrées -->
                <div class="form-group">
                    <label for="difficulties_encountered" class="form-label">Difficultés rencontrées</label>
                    <textarea 
                        name="difficulties_encountered" 
                        id="difficulties_encountered" 
                        class="form-textarea" 
                        placeholder="Problèmes techniques, difficultés de compréhension des étudiants, manque de temps...">{{ old('difficulties_encountered', $existingReport->difficulties_encountered ?? '') }}</textarea>
                    <div class="form-help">Décrivez les éventuelles difficultés rencontrées pendant la séance.</div>
                </div>

                <!-- Préparation séance suivante -->
                <div class="form-group">
                    <label for="next_session_preparation" class="form-label">Préparation pour la séance suivante</label>
                    <textarea 
                        name="next_session_preparation" 
                        id="next_session_preparation" 
                        class="form-textarea" 
                        placeholder="Points à revoir, matériel à prévoir, préparatifs spéciaux...">{{ old('next_session_preparation', $existingReport->next_session_preparation ?? '') }}</textarea>
                    <div class="form-help">Notez ce qui doit être préparé ou prévu pour la prochaine séance.</div>
                </div>

                <!-- Devoirs assignés -->
                <div class="form-group">
                    <label for="homework_assigned" class="form-label">Devoirs ou travaux assignés</label>
                    <textarea 
                        name="homework_assigned" 
                        id="homework_assigned" 
                        class="form-textarea" 
                        placeholder="Exercices à faire, lectures, projets, révisions...">{{ old('homework_assigned', $existingReport->homework_assigned ?? '') }}</textarea>
                    <div class="form-help">Décrivez les devoirs ou travaux donnés aux étudiants.</div>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" name="action" value="save_draft" class="btn-modern draft">
                    <i class="fas fa-save"></i>
                    <span>Sauvegarder brouillon</span>
                </button>
                <button type="submit" name="action" value="submit" class="btn-modern primary" id="submitBtn" disabled>
                    <i class="fas fa-paper-plane"></i>
                    <span>Soumettre le rapport</span>
                </button>
                <a href="{{ route('teacher.select-call-type', $seance->id) }}" class="btn-modern secondary">
                    <i class="fas fa-arrow-left"></i>
                    <span>Retour</span>
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const contentSummary = document.getElementById('content_summary');
    const contentCounter = document.getElementById('contentCounter');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('reportForm');

    // Fonction pour mettre à jour le compteur de caractères
    function updateCharCounter() {
        const length = contentSummary.value.trim().length;
        const isValid = length >= 30;
        
        contentCounter.textContent = `${length} / 30 caractères minimum`;
        contentCounter.className = `char-counter ${isValid ? 'valid' : 'error'}`;
        
        // Activer/désactiver le bouton de soumission
        submitBtn.disabled = !isValid;
        
        if (isValid) {
            submitBtn.style.opacity = '1';
            submitBtn.style.cursor = 'pointer';
        } else {
            submitBtn.style.opacity = '0.6';
            submitBtn.style.cursor = 'not-allowed';
        }
    }

    // Écouter les changements dans le textarea
    contentSummary.addEventListener('input', updateCharCounter);
    contentSummary.addEventListener('keyup', updateCharCounter);
    contentSummary.addEventListener('blur', updateCharCounter);

    // Vérification initiale
    updateCharCounter();

    // Gestion de la soumission du formulaire
    form.addEventListener('submit', function(e) {
        const action = e.submitter.value;
        
        if (action === 'submit') {
            const length = contentSummary.value.trim().length;
            if (length < 30) {
                e.preventDefault();
                alert('Le résumé du contenu doit contenir au minimum 30 caractères.');
                contentSummary.focus();
                return;
            }
            
            // Confirmation pour la soumission finale
            if (!confirm('Voulez-vous vraiment soumettre ce rapport ? Une fois soumis, il ne pourra plus être modifié.')) {
                e.preventDefault();
                return;
            }
        }
        
        // Désactiver le bouton pour éviter les doubles soumissions
        const buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Traitement...</span>';
        });
    });

    // Auto-hide alerts après 5 secondes
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        });
    }, 5000);
});
</script>
@endsection