{{-- 7. Actions - card-button grid + stepper --}}
@php
    $includeAllStatusesQuery = !empty($include_all_statuses) ? '&include_all_statuses=1' : '';
    $workflowPeriode = $bulletinWorkflowPeriode ?? $periode;
    $workflowPeriodeLabel = $bulletinWorkflowPeriodeLabel ?? ($periode === 'semestre2' ? 'Semestre 2' : 'Semestre 1');
    $bulletinActionLabel = $detailUiState['bulletin_action_label'] ?? (($periode ?? null) === 'annuel' ? 'Annuel' : $workflowPeriodeLabel);
    $annualActionSuffix = ($detailUiState['state'] ?? null) === 'annual_incomplete' || ($periode ?? null) === 'annuel'
        ? ' · ' . $bulletinActionLabel
        : '';
@endphp
<div class="sr-actions-card sr-animate sr-animate-delay-5">
    <div class="sr-actions-header">
        <i class="fas fa-bolt"></i>
        <h3>Actions rapides</h3>
    </div>
    <div class="sr-actions-body">
        <div class="sr-actions-grid">
            @if(isset($classe) && $classe)
                <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id]) }}?periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}{{ $includeAllStatusesQuery }}"
                   class="sr-action-btn">
                    <div class="sr-action-btn-icon sr-action-btn-icon--secondary">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="sr-action-btn-text">Retour à la classe</span>
                </a>
            @else
                <a href="{{ route('esbtp.resultats.index', !empty($include_all_statuses) ? ['include_all_statuses' => 1] : []) }}" class="sr-action-btn">
                    <div class="sr-action-btn-icon sr-action-btn-icon--secondary">
                        <i class="fas fa-list"></i>
                    </div>
                    <span class="sr-action-btn-text">Tous les résultats</span>
                </a>
            @endif

            @if(isset($classe) && $classe)
                @if(auth()->user()->hasAnyPermission(['admin.access', 'identity.school_manager']))
                    <a href="{{ route('esbtp.bulletins.moyennes-preview', ['etudiant_id' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $workflowPeriode, 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--warning">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <span class="sr-action-btn-text">Modifier moyennes{{ $annualActionSuffix }}</span>
                    </a>
                @endif

                @if(auth()->user()->can('admin.access'))
                    <a href="{{ route('esbtp.bulletins.config-matieres', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $workflowPeriode, 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--info">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="sr-action-btn-text">Config. matières{{ $annualActionSuffix }}</span>
                    </a>
                    {{-- Ouvre le modal coefficients in-page au lieu de rediriger vers evaluations.index --}}
                    @if(isset($coeffFiliere) && isset($coeffNiveau))
                    <button type="button"
                            class="sr-action-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#studentCoeffModal"
                            title="Configure les coefficients matière (pondération bulletin)">
                        <div class="sr-action-btn-icon sr-action-btn-icon--info">
                            <i class="fas fa-sliders-h"></i>
                        </div>
                        <span class="sr-action-btn-text">Paramètres coefficients</span>
                    </button>
                    @endif
                    <a href="{{ route('esbtp.bulletins.edit-professeurs', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $workflowPeriode, 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--primary">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <span class="sr-action-btn-text">Éditer professeurs{{ $annualActionSuffix }}</span>
                    </a>
                    <a href="{{ route('esbtp.bulletins.edit-absences', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $workflowPeriode, 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--warning">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <span class="sr-action-btn-text">Éditer absences{{ $annualActionSuffix }}</span>
                    </a>
                @endif

                <a href="{{ route('esbtp.resultats.etudiant.preview', ['etudiant' => $etudiant->id]) }}?classe_id={{ $classe->id }}&annee_universitaire_id={{ $annee_id }}&periode={{ $workflowPeriode }}"
                   class="sr-action-btn"
                   data-check-url="{{ route('esbtp.bulletins.check-consistency', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $workflowPeriode, 'annee_universitaire_id' => $annee_id]) }}"
                   data-consistency-action="web_preview"
                   onclick="return srCheckBeforePDF(event, this);">
                    <div class="sr-action-btn-icon sr-action-btn-icon--success">
                        <i class="fas fa-eye"></i>
                    </div>
                    <span class="sr-action-btn-text">Prévisualiser bulletin{{ $annualActionSuffix }}</span>
                </a>
                @php $_abPdfParams = ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $workflowPeriode, 'annee_universitaire_id' => $annee_id]; @endphp
                <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_abPdfParams) }}"
                   class="sr-action-btn sr-pdf-link"
                   target="_blank"
                   data-check-url="{{ route('esbtp.bulletins.check-consistency', $_abPdfParams) }}"
                   data-consistency-action="preview_pdf"
                   onclick="return srCheckBeforePDF(event, this);">
                    <div class="sr-action-btn-icon sr-action-btn-icon--success">
                        <i class="fas fa-eye"></i>
                    </div>
                    <span class="sr-action-btn-text">Aperçu PDF{{ $annualActionSuffix }}</span>
                </a>
                <a href="{{ route('esbtp.bulletins.pdf-params', $_abPdfParams) }}"
                   class="sr-action-btn sr-pdf-link"
                   data-check-url="{{ route('esbtp.bulletins.check-consistency', $_abPdfParams) }}"
                   data-consistency-action="download_pdf"
                   onclick="return srCheckBeforePDF(event, this);">
                    <div class="sr-action-btn-icon sr-action-btn-icon--primary">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <span class="sr-action-btn-text">Télécharger PDF{{ $annualActionSuffix }}</span>
                </a>
            @else
                <div class="sr-callout" style="grid-column: 1 / -1;">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <h6>Configuration requise</h6>
                        <p>Sélectionnez une classe et une période dans les filtres pour accéder aux options avancées.</p>
                    </div>
                </div>
            @endif
        </div>

        @if(isset($classe) && $classe)
            <div class="sr-callout">
                <i class="fas fa-lightbulb"></i>
                <div>
                    <h6>Guide de génération du bulletin</h6>
                    <p>Suivez ces étapes pour préparer et générer le bulletin de l'étudiant.</p>
                </div>
            </div>
            <div class="sr-stepper">
                <div class="sr-step">
                    <div class="sr-step-circle">1</div>
                    <div>
                        <div class="sr-step-title">Matières</div>
                        <div class="sr-step-desc">Classez par type</div>
                    </div>
                </div>
                <div class="sr-step">
                    <div class="sr-step-circle">2</div>
                    <div>
                        <div class="sr-step-title">Moyennes</div>
                        <div class="sr-step-desc">Vérifiez / ajustez</div>
                    </div>
                </div>
                <div class="sr-step">
                    <div class="sr-step-circle">3</div>
                    <div>
                        <div class="sr-step-title">Professeurs</div>
                        <div class="sr-step-desc">Ajoutez les noms</div>
                    </div>
                </div>
                <div class="sr-step">
                    <div class="sr-step-circle">4</div>
                    <div>
                        <div class="sr-step-title">Absences</div>
                        <div class="sr-step-desc">Optionnel</div>
                    </div>
                </div>
                <div class="sr-step">
                    <div class="sr-step-circle">5</div>
                    <div>
                        <div class="sr-step-title">PDF</div>
                        <div class="sr-step-desc">Générez le bulletin</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
