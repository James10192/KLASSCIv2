{{-- Composant pour les boutons d'action --}}
<div class="main-card">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-tools"></i>
            Actions disponibles
        </div>
        <div class="main-card-subtitle">Générer des documents et gérer les résultats</div>
    </div>
    <div class="main-card-body">
        <div class="actions-grid">
            <div class="action-section">
                <h6 class="section-title">
                    <i class="fas fa-arrow-left"></i>
                    Navigation
                </h6>
                @if(isset($classe) && $classe)
                    <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id]) }}?periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}" 
                       class="btn-acasi secondary">
                        <i class="fas fa-users"></i>Retour à la classe
                    </a>
                @else
                    <a href="{{ route('esbtp.resultats.index') }}" class="btn-acasi secondary">
                        <i class="fas fa-list"></i>Tous les résultats
                    </a>
                @endif
            </div>

            @if(isset($classe) && $classe)
                @if(auth()->user()->hasRole('superAdmin') || auth()->user()->hasRole('secretaire'))
                    <div class="action-section">
                        <h6 class="section-title">
                            <i class="fas fa-edit"></i>
                            Modification
                        </h6>
                        <div class="button-group">
                            <a href="{{ route('esbtp.bulletins.moyennes-preview', ['etudiant_id' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => ($periode == '1' ? 'semestre1' : ($periode == '2' ? 'semestre2' : $periode)), 'annee_universitaire_id' => $annee_id]) }}" 
                               class="btn-acasi warning">
                                <i class="fas fa-calculator"></i>Modifier les moyennes
                            </a>
                        </div>
                    </div>
                @endif

                @if(auth()->user()->hasRole('superAdmin'))
                    <div class="action-section">
                        <h6 class="section-title">
                            <i class="fas fa-cog"></i>
                            Configuration
                        </h6>
                        <div class="button-group">
                            <a href="{{ route('esbtp.bulletins.config-matieres', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                               class="btn-acasi info">
                                <i class="fas fa-book"></i>Configurer matières
                            </a>
                            <a href="{{ route('esbtp.bulletins.edit-professeurs', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                               class="btn-acasi primary">
                                <i class="fas fa-chalkboard-teacher"></i>Éditer professeurs
                            </a>
                            <a href="{{ route('esbtp.bulletins.edit-absences', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                               class="btn-acasi warning">
                                <i class="fas fa-user-clock"></i>Éditer absences
                            </a>
                        </div>
                    </div>
                @endif

                <div class="action-section">
                    <h6 class="section-title">
                        <i class="fas fa-file-pdf"></i>
                        Génération
                    </h6>
                    <a href="#" class="btn-acasi danger featured"
                       onclick="window.open('{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}', '_blank')">
                        <i class="fas fa-file-pdf"></i>Générer le bulletin PDF
                    </a>
                </div>
            @else
                <div class="action-section">
                    <div class="info-notice">
                        <div class="notice-content">
                            <i class="fas fa-info-circle"></i>
                            <div>
                                <h6>Configuration requise</h6>
                                <p>Pour accéder aux options avancées, veuillez sélectionner une classe et une période dans les filtres.</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if(isset($classe) && $classe)
            <div class="instructions-card">
                <div class="instructions-header">
                    <i class="fas fa-lightbulb"></i>
                    <h6>Guide de génération du bulletin</h6>
                </div>
                <div class="instructions-content">
                    <div class="step-list">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <strong>Configurer les matières</strong>
                                <span>Classez par type d'enseignement</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <strong>Vérifier les moyennes</strong>
                                <span>Ajustez si nécessaire</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <strong>Éditer les professeurs</strong>
                                <span>Ajoutez les noms par matière</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <strong>Éditer les absences (optionnel)</strong>
                                <span>Ajustez si nécessaire</span>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">5</div>
                            <div class="step-content">
                                <strong>Générer le PDF</strong>
                                <span>Créez le bulletin final</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<style>
.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.action-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid var(--border-color);
}

.button-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-acasi.featured {
    position: relative;
    overflow: hidden;
}

.btn-acasi.featured::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.6s;
}

.btn-acasi.featured:hover::before {
    left: 100%;
}

.info-notice {
    padding: 1.5rem;
    background: var(--info-bg);
    border: 1px solid var(--info);
    border-radius: var(--border-radius);
    color: var(--info);
}

.notice-content {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.notice-content i {
    font-size: 1.5rem;
    margin-top: 0.25rem;
}

.notice-content h6 {
    margin-bottom: 0.5rem;
    color: var(--info);
}

.notice-content p {
    margin: 0;
    opacity: 0.9;
}

.instructions-card {
    background: var(--background-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.instructions-header {
    padding: 1rem 1.5rem;
    background: var(--primary-bg);
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.instructions-header h6 {
    margin: 0;
    font-weight: 600;
}

.instructions-content {
    padding: 1.5rem;
}

.step-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.step {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: var(--border-radius);
    border: 1px solid var(--border-color);
}

.step-number {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--primary);
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.step-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.step-content strong {
    color: var(--text-primary);
    font-size: 0.875rem;
}

.step-content span {
    color: var(--text-secondary);
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .actions-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .step-list {
        grid-template-columns: 1fr;
    }
}
</style>