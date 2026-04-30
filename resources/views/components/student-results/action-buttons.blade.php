{{-- 7. Actions — card-button grid + stepper --}}
<div class="sr-actions-card sr-animate sr-animate-delay-5">
    <div class="sr-actions-header">
        <i class="fas fa-bolt"></i>
        <h3>Actions rapides</h3>
    </div>
    <div class="sr-actions-body">
        <div class="sr-actions-grid">
            {{-- Navigation --}}
            @if(isset($classe) && $classe)
                <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id]) }}?periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}"
                   class="sr-action-btn">
                    <div class="sr-action-btn-icon sr-action-btn-icon--secondary">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="sr-action-btn-text">Retour à la classe</span>
                </a>
            @else
                <a href="{{ route('esbtp.resultats.index') }}" class="sr-action-btn">
                    <div class="sr-action-btn-icon sr-action-btn-icon--secondary">
                        <i class="fas fa-list"></i>
                    </div>
                    <span class="sr-action-btn-text">Tous les résultats</span>
                </a>
            @endif

            @if(isset($classe) && $classe)
                {{-- Modification (superAdmin / secretaire) --}}
                @if(auth()->user()->hasAnyPermission(['access_admin', 'can_manage_school']))
                    <a href="{{ route('esbtp.bulletins.moyennes-preview', ['etudiant_id' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => ($periode == '1' ? 'semestre1' : ($periode == '2' ? 'semestre2' : $periode)), 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--warning">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <span class="sr-action-btn-text">Modifier moyennes</span>
                    </a>
                @endif

                {{-- Configuration (superAdmin) --}}
                @if(auth()->user()->can('admin.access'))
                    <a href="{{ route('esbtp.bulletins.config-matieres', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--info">
                            <i class="fas fa-book"></i>
                        </div>
                        <span class="sr-action-btn-text">Config. matières</span>
                    </a>
                    <a href="{{ route('esbtp.bulletins.edit-professeurs', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--primary">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <span class="sr-action-btn-text">Éditer professeurs</span>
                    </a>
                    <a href="{{ route('esbtp.bulletins.edit-absences', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                       class="sr-action-btn">
                        <div class="sr-action-btn-icon sr-action-btn-icon--warning">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <span class="sr-action-btn-text">Éditer absences</span>
                    </a>
                @endif

                {{-- Preview & PDF --}}
                <a href="{{ route('esbtp.resultats.etudiant.preview', ['etudiant' => $etudiant->id]) }}?classe_id={{ $classe->id }}&annee_universitaire_id={{ $annee_id }}&periode={{ $periode }}"
                   class="sr-action-btn"
                   data-check-url="{{ route('esbtp.bulletins.check-prerequisites', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                   onclick="return srCheckBeforePDF(event, this);">
                    <div class="sr-action-btn-icon sr-action-btn-icon--success">
                        <i class="fas fa-eye"></i>
                    </div>
                    <span class="sr-action-btn-text">Prévisualiser bulletin</span>
                </a>
                <a href="{{ route('esbtp.bulletins.pdf-params', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                   class="sr-action-btn sr-pdf-link"
                   data-check-url="{{ route('esbtp.bulletins.check-prerequisites', ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $annee_id]) }}"
                   onclick="return srCheckBeforePDF(event, this);">
                    <div class="sr-action-btn-icon sr-action-btn-icon--primary">
                        <i class="fas fa-file-pdf"></i>
                    </div>
                    <span class="sr-action-btn-text">Télécharger PDF</span>
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
            {{-- Stepper guide --}}
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
