{{--
    Modal auto-suffisant de configuration des coefficients — page étudiant.
    Variables attendues (calculées dans etudiant.blade.php) :
    - $coeffContext       : session('coefficient_missing_context') ou null
    - $coeffFiliere       : objet filière de la classe
    - $coeffNiveau        : objet niveau de la classe
    - $coeffAnneeId       : annee_universitaire_id (int)
    - $coeffMatieresLiees : Collection — matières formellement liées à la combinaison
    - $coeffMatieresEvals : Collection — matières avec évaluations dans la classe hors combinaison
    - $coefficients       : Collection keyBy('matiere_id') des coefficients existants
--}}

@if(isset($coeffFiliere) && isset($coeffNiveau))
<div class="modal fade" id="studentCoeffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered scm-dialog">
        <div class="modal-content scm-modal">

            {{-- ═══ HEADER ═══ --}}
            <div class="scm-header">
                <div class="scm-header-bg"></div>
                <div class="scm-header-content">
                    <div class="scm-icon-wrap">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/>
                            <path d="M15.54 8.46a5 5 0 0 1 0 7.07M8.46 8.46a5 5 0 0 0 0 7.07"/>
                        </svg>
                    </div>
                    <div class="scm-header-text">
                        <h5>Coefficients — {{ $coeffFiliere->name }} <span class="scm-slash">/</span> {{ $coeffNiveau->name }}</h5>
                        <p>Configurez les poids de chaque matière pour générer le bulletin</p>
                    </div>
                    <div class="scm-combo-badges">
                        <span class="scm-badge scm-badge-filiere">{{ $coeffFiliere->name }}</span>
                        <span class="scm-badge scm-badge-niveau">{{ $coeffNiveau->name }}</span>
                    </div>
                </div>
                <button type="button" class="scm-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- ═══ BANNIÈRE CONTEXTUELLE ═══ --}}
            @if($coeffContext && isset($coeffContext['reason']))
                <div class="scm-banner scm-banner--{{ $coeffContext['reason'] === 'matiere_hors_combinaison' ? 'warn' : 'error' }}">
                    <div class="scm-banner-icon">
                        @if($coeffContext['reason'] === 'matiere_hors_combinaison')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        @endif
                    </div>
                    <div class="scm-banner-body">
                        @if($coeffContext['reason'] === 'matiere_hors_combinaison')
                            <strong>Matière hors combinaison</strong>
                            <span>{{ $coeffContext['matiere']['name'] ?? 'Matière inconnue' }} n'est pas rattachée à cette combinaison. Ajoutez son coefficient ci-dessous.</span>
                        @else
                            <strong>Coefficient manquant — bulletin bloqué</strong>
                            <span>La matière <em>{{ $coeffContext['matiere']['name'] ?? '—' }}</em> n'a pas de coefficient pour cette combinaison filière / niveau.</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- ═══ FEEDBACK ═══ --}}
            <div id="scmSuccess" class="scm-feedback scm-feedback--success d-none">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Coefficients enregistrés. Rechargement en cours…</span>
                <div class="scm-progress-bar"><div class="scm-progress-fill"></div></div>
            </div>
            <div id="scmError" class="scm-feedback scm-feedback--error d-none">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                <span id="scmErrorText">Une erreur est survenue.</span>
            </div>

            {{-- ═══ BODY ═══ --}}
            <div class="scm-body">
                <form id="scmForm">
                    @csrf
                    <input type="hidden" name="filiere_id" value="{{ $coeffFiliere->id }}">
                    <input type="hidden" name="niveau_etude_id" value="{{ $coeffNiveau->id }}">
                    <input type="hidden" name="annee_universitaire_id" value="{{ $coeffAnneeId }}">

                    {{-- ── GROUPE 1 : Matières officiellement liées ── --}}
                    <div class="scm-group" data-group="linked">
                        <div class="scm-group-label scm-group-label--blue">
                            <div class="scm-group-dot scm-group-dot--blue"></div>
                            <span>Matières de la combinaison officielle</span>
                            <span class="scm-count">{{ $coeffMatieresLiees->count() }}</span>
                        </div>

                        @if($coeffMatieresLiees->isNotEmpty())
                            <div class="scm-matiere-list">
                                @foreach($coeffMatieresLiees as $idx => $matiere)
                                    @php
                                        $val = $coefficients[$matiere->id]->coefficient ?? '';
                                        $isBlock = $coeffContext
                                            && isset($coeffContext['matiere']['name'])
                                            && $coeffContext['matiere']['name'] === $matiere->name;
                                    @endphp
                                    <div class="scm-row {{ $isBlock ? 'scm-row--blocking' : ($val ? '' : 'scm-row--empty') }}"
                                         style="--delay: {{ $idx * 35 }}ms">
                                        <div class="scm-row-index">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</div>
                                        <div class="scm-row-info">
                                            <span class="scm-row-name">{{ $matiere->name }}</span>
                                            @if($matiere->code)
                                                <span class="scm-row-code">{{ $matiere->code }}</span>
                                            @endif
                                            @if($isBlock)
                                                <span class="scm-pill scm-pill--danger">Bloquant</span>
                                            @endif
                                        </div>
                                        <div class="scm-input-wrap">
                                            <input type="number"
                                                   name="coefficients[{{ $matiere->id }}]"
                                                   value="{{ $val }}"
                                                   step="0.1" min="0.1"
                                                   placeholder="—"
                                                   class="scm-input {{ $isBlock ? 'scm-input--blocking' : '' }}"
                                                   autocomplete="off">
                                            <span class="scm-input-unit">coeff.</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="scm-empty">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Aucune matière formellement liée à cette combinaison.
                            </div>
                        @endif
                    </div>

                    {{-- ── GROUPE 2 : Matières avec évaluations hors combinaison ── --}}
                    @if($coeffMatieresEvals->isNotEmpty())
                    <div class="scm-group" data-group="evals">
                        <div class="scm-group-label scm-group-label--amber">
                            <div class="scm-group-dot scm-group-dot--amber"></div>
                            <span>Matières avec évaluations — hors combinaison officielle</span>
                            <span class="scm-count scm-count--amber">{{ $coeffMatieresEvals->count() }}</span>
                        </div>
                        <div class="scm-group-note">
                            Ces matières ont des évaluations pour cet étudiant mais ne font pas partie de la combinaison officielle
                            <strong>{{ $coeffFiliere->name }} / {{ $coeffNiveau->name }}</strong>.
                            Assignez-leur un coefficient pour qu'elles soient intégrées au bulletin.
                        </div>
                        <div class="scm-matiere-list">
                            @foreach($coeffMatieresEvals as $idx => $matiere)
                                @php
                                    $val = $coefficients[$matiere->id]->coefficient ?? '';
                                    $isBlock = $coeffContext
                                        && isset($coeffContext['matiere']['name'])
                                        && $coeffContext['matiere']['name'] === $matiere->name;
                                @endphp
                                <div class="scm-row scm-row--hors-combo {{ $isBlock ? 'scm-row--blocking' : ($val ? '' : 'scm-row--empty') }}"
                                     style="--delay: {{ $idx * 35 }}ms">
                                    <div class="scm-row-index scm-row-index--amber">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</div>
                                    <div class="scm-row-info">
                                        <span class="scm-row-name">{{ $matiere->name }}</span>
                                        @if($matiere->code)
                                            <span class="scm-row-code">{{ $matiere->code }}</span>
                                        @endif
                                        <span class="scm-pill scm-pill--amber">Hors combinaison</span>
                                        @if($isBlock)
                                            <span class="scm-pill scm-pill--danger">Bloquant</span>
                                        @endif
                                    </div>
                                    <div class="scm-input-wrap">
                                        <input type="number"
                                               name="coefficients[{{ $matiere->id }}]"
                                               value="{{ $val }}"
                                               step="0.1" min="0.1"
                                               placeholder="—"
                                               class="scm-input scm-input--amber {{ $isBlock ? 'scm-input--blocking' : '' }}"
                                               autocomplete="off">
                                        <span class="scm-input-unit">coeff.</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Aucune matière du tout --}}
                    @if($coeffMatieresLiees->isEmpty() && $coeffMatieresEvals->isEmpty())
                        <div class="scm-empty scm-empty--global">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" width="32" height="32"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            <strong>Aucune matière trouvée</strong>
                            <span>Vérifiez la configuration des matières et des évaluations de la classe.</span>
                        </div>
                    @endif

                    {{-- ── FOOTER ── --}}
                    @if($coeffMatieresLiees->isNotEmpty() || $coeffMatieresEvals->isNotEmpty())
                    <div class="scm-footer">
                        <div class="scm-footer-info">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            Les champs laissés vides supprimeront les coefficients existants.
                        </div>
                        <div class="scm-footer-actions">
                            <button type="button" class="scm-btn scm-btn--ghost" data-bs-dismiss="modal">
                                Annuler
                            </button>
                            <button type="submit" id="scmSaveBtn" class="scm-btn scm-btn--primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="scm-btn-icon"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                <span class="scm-btn-label">Enregistrer les coefficients</span>
                                <span class="scm-btn-loading d-none">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="scm-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                    Enregistrement…
                                </span>
                            </button>
                        </div>
                    </div>
                    @endif
                </form>
            </div>

        </div>{{-- /.scm-modal --}}
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════
     STYLES
═══════════════════════════════════════════════════════════════ --}}
<style>
/* ── Root tokens ── */
.scm-dialog {
    --scm-blue:       #0453cb;
    --scm-blue-mid:   #3b7de8;
    --scm-blue-light: #dbeafe;
    --scm-blue-glow:  rgba(4, 83, 203, 0.18);
    --scm-amber:      #d97706;
    --scm-amber-light:#fef3c7;
    --scm-amber-glow: rgba(217, 119, 6, 0.15);
    --scm-red:        #dc2626;
    --scm-red-light:  #fee2e2;
    --scm-green:      #059669;
    --scm-green-light:#d1fae5;
    --scm-ink:        #0f172a;
    --scm-ink-2:      #1e293b;
    --scm-muted:      #64748b;
    --scm-border:     #e2e8f0;
    --scm-surface:    #ffffff;
    --scm-bg:         #f8fafc;
    --scm-radius:     14px;
    --scm-radius-sm:  8px;
    --scm-shadow:     0 32px 80px rgba(4, 83, 203, 0.14), 0 8px 24px rgba(0,0,0,0.08);
    --scm-transition: 0.18s cubic-bezier(.4,0,.2,1);
}

/* ── Dialog sizing ── */
.scm-dialog { max-width: 680px; }

/* ── Modal shell ── */
.scm-modal {
    border: none;
    border-radius: var(--scm-radius);
    overflow: hidden;
    box-shadow: var(--scm-shadow);
    background: var(--scm-surface);
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}

/* ── Header ── */
.scm-header {
    position: relative;
    overflow: hidden;
    padding: 1.4rem 1.5rem;
    background: var(--scm-surface);
    border-bottom: 1px solid var(--scm-border);
}

.scm-header-bg {
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 60% 80% at 0% 0%, rgba(4,83,203,0.08) 0%, transparent 70%),
        radial-gradient(ellipse 40% 60% at 100% 100%, rgba(59,125,232,0.06) 0%, transparent 60%);
    pointer-events: none;
}

.scm-header-content {
    position: relative;
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding-right: 2.5rem;
}

.scm-icon-wrap {
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    background: linear-gradient(140deg, var(--scm-blue) 0%, var(--scm-blue-mid) 100%);
    border-radius: 12px;
    display: grid;
    place-items: center;
    color: white;
    box-shadow: 0 4px 14px var(--scm-blue-glow);
}

.scm-icon-wrap svg { width: 22px; height: 22px; }

.scm-header-text {
    flex: 1;
    min-width: 0;
}

.scm-header-text h5 {
    font-size: 1rem;
    font-weight: 700;
    color: var(--scm-ink);
    margin: 0 0 0.2rem;
    line-height: 1.3;
    letter-spacing: -0.01em;
}

.scm-slash { color: var(--scm-muted); margin: 0 0.25rem; font-weight: 300; }

.scm-header-text p {
    font-size: 0.78rem;
    color: var(--scm-muted);
    margin: 0;
    line-height: 1.4;
}

.scm-combo-badges {
    display: flex;
    gap: 0.4rem;
    flex-shrink: 0;
}

.scm-badge {
    font-size: 0.7rem;
    font-weight: 600;
    padding: 0.25rem 0.65rem;
    border-radius: 20px;
    letter-spacing: 0.02em;
    text-transform: uppercase;
}

.scm-badge-filiere {
    background: var(--scm-blue-light);
    color: var(--scm-blue);
    border: 1px solid rgba(4,83,203,0.2);
}

.scm-badge-niveau {
    background: var(--scm-bg);
    color: var(--scm-muted);
    border: 1px solid var(--scm-border);
}

.scm-close {
    position: absolute;
    top: 1.1rem;
    right: 1.1rem;
    width: 32px;
    height: 32px;
    display: grid;
    place-items: center;
    background: var(--scm-bg);
    border: 1px solid var(--scm-border);
    border-radius: 8px;
    cursor: pointer;
    transition: var(--scm-transition);
    color: var(--scm-muted);
    padding: 0;
}

.scm-close svg { width: 15px; height: 15px; }
.scm-close:hover {
    background: var(--scm-red-light);
    border-color: rgba(220,38,38,0.3);
    color: var(--scm-red);
}

/* ── Banners ── */
.scm-banner {
    display: flex;
    align-items: flex-start;
    gap: 0.85rem;
    padding: 0.85rem 1.5rem;
    border-bottom: 1px solid transparent;
    font-size: 0.83rem;
    line-height: 1.5;
}

.scm-banner--error {
    background: rgba(220,38,38,0.06);
    border-color: rgba(220,38,38,0.15);
    color: #991b1b;
}

.scm-banner--warn {
    background: rgba(217,119,6,0.07);
    border-color: rgba(217,119,6,0.2);
    color: #92400e;
}

.scm-banner-icon {
    flex-shrink: 0;
    margin-top: 0.1rem;
}
.scm-banner-icon svg { width: 16px; height: 16px; }

.scm-banner-body { display: flex; flex-direction: column; gap: 0.15rem; }
.scm-banner-body strong { font-weight: 700; }
.scm-banner-body em { font-style: normal; font-weight: 600; }

/* ── Feedback bars ── */
.scm-feedback {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.8rem 1.5rem;
    font-size: 0.83rem;
    font-weight: 600;
    border-bottom: 1px solid transparent;
    position: relative;
}

.scm-feedback svg { width: 16px; height: 16px; flex-shrink: 0; }

.scm-feedback--success {
    background: var(--scm-green-light);
    border-color: rgba(5,150,105,0.2);
    color: #064e3b;
}

.scm-feedback--error {
    background: var(--scm-red-light);
    border-color: rgba(220,38,38,0.2);
    color: #7f1d1d;
}

.scm-progress-bar {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 2px;
    background: rgba(5,150,105,0.2);
    overflow: hidden;
}

.scm-progress-fill {
    height: 100%;
    background: var(--scm-green);
    width: 0%;
    animation: scm-progress 1.5s ease forwards;
}

@keyframes scm-progress {
    to { width: 100%; }
}

/* ── Body ── */
.scm-body {
    max-height: 62vh;
    overflow-y: auto;
    padding: 1.25rem 1.5rem;
    scroll-behavior: smooth;
}

.scm-body::-webkit-scrollbar { width: 4px; }
.scm-body::-webkit-scrollbar-track { background: transparent; }
.scm-body::-webkit-scrollbar-thumb { background: var(--scm-border); border-radius: 2px; }

/* ── Groups ── */
.scm-group {
    margin-bottom: 1.5rem;
    border: 1px solid var(--scm-border);
    border-radius: var(--scm-radius);
    overflow: hidden;
}

.scm-group-label {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.7rem 1rem;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.scm-group-label--blue {
    background: linear-gradient(to right, rgba(4,83,203,0.07), rgba(4,83,203,0.03));
    color: var(--scm-blue);
    border-bottom: 1px solid rgba(4,83,203,0.12);
}

.scm-group-label--amber {
    background: linear-gradient(to right, rgba(217,119,6,0.07), rgba(217,119,6,0.03));
    color: var(--scm-amber);
    border-bottom: 1px solid rgba(217,119,6,0.15);
}

.scm-group-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.scm-group-dot--blue  { background: var(--scm-blue); box-shadow: 0 0 6px var(--scm-blue-glow); }
.scm-group-dot--amber { background: var(--scm-amber); box-shadow: 0 0 6px var(--scm-amber-glow); }

.scm-count {
    margin-left: auto;
    font-size: 0.68rem;
    padding: 0.15rem 0.55rem;
    border-radius: 20px;
    background: rgba(4,83,203,0.1);
    color: var(--scm-blue);
    letter-spacing: 0;
    text-transform: none;
    font-weight: 600;
}

.scm-count--amber {
    background: rgba(217,119,6,0.1);
    color: var(--scm-amber);
}

.scm-group-note {
    padding: 0.6rem 1rem;
    font-size: 0.78rem;
    color: var(--scm-muted);
    background: rgba(217,119,6,0.03);
    border-bottom: 1px solid rgba(217,119,6,0.08);
    font-style: italic;
    line-height: 1.5;
}

/* ── Matière rows ── */
.scm-matiere-list { padding: 0.25rem 0; }

.scm-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.65rem 1rem;
    border-bottom: 1px solid rgba(226,232,240,0.6);
    transition: background var(--scm-transition);
    animation: scm-row-in 0.25s ease both;
    animation-delay: var(--delay, 0ms);
}

@keyframes scm-row-in {
    from { opacity: 0; transform: translateY(4px); }
    to   { opacity: 1; transform: translateY(0); }
}

.scm-row:last-child { border-bottom: none; }
.scm-row:hover { background: rgba(4,83,203,0.03); }

.scm-row--empty { background: rgba(248,250,252,0.8); }
.scm-row--empty:hover { background: rgba(4,83,203,0.025); }

.scm-row--blocking { background: rgba(220,38,38,0.04) !important; }
.scm-row--blocking:hover { background: rgba(220,38,38,0.07) !important; }

.scm-row--hors-combo { background: rgba(254,243,199,0.3); }
.scm-row--hors-combo:hover { background: rgba(254,243,199,0.55); }

/* Row index badge */
.scm-row-index {
    width: 26px;
    height: 26px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    font-size: 0.65rem;
    font-weight: 700;
    border-radius: 6px;
    background: rgba(4,83,203,0.08);
    color: var(--scm-blue);
    letter-spacing: -0.01em;
}

.scm-row-index--amber {
    background: rgba(217,119,6,0.1);
    color: var(--scm-amber);
}

/* Row info */
.scm-row-info {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.35rem;
}

.scm-row-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--scm-ink-2);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}

.scm-row-code {
    font-size: 0.7rem;
    font-family: 'SF Mono', 'Fira Code', monospace;
    color: var(--scm-muted);
    background: var(--scm-bg);
    border: 1px solid var(--scm-border);
    padding: 0.1rem 0.45rem;
    border-radius: 4px;
    letter-spacing: 0.02em;
}

/* Pills */
.scm-pill {
    font-size: 0.65rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 20px;
    letter-spacing: 0.03em;
    text-transform: uppercase;
}

.scm-pill--amber {
    background: var(--scm-amber-light);
    color: var(--scm-amber);
    border: 1px solid rgba(217,119,6,0.25);
}

.scm-pill--danger {
    background: var(--scm-red-light);
    color: var(--scm-red);
    border: 1px solid rgba(220,38,38,0.25);
}

/* Input */
.scm-input-wrap {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-shrink: 0;
}

.scm-input {
    width: 82px;
    height: 36px;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 700;
    color: var(--scm-ink);
    background: var(--scm-surface);
    border: 1.5px solid var(--scm-border);
    border-radius: var(--scm-radius-sm);
    padding: 0 0.5rem;
    transition: var(--scm-transition);
    outline: none;
    -moz-appearance: textfield;
}

.scm-input::-webkit-inner-spin-button,
.scm-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }

.scm-input:focus {
    border-color: var(--scm-blue);
    box-shadow: 0 0 0 3px var(--scm-blue-glow);
}

.scm-input--amber:focus {
    border-color: var(--scm-amber);
    box-shadow: 0 0 0 3px var(--scm-amber-glow);
}

.scm-input--blocking {
    border-color: var(--scm-red) !important;
    box-shadow: 0 0 0 3px rgba(220,38,38,0.15) !important;
}

.scm-input-unit {
    font-size: 0.68rem;
    color: var(--scm-muted);
    font-style: italic;
    white-space: nowrap;
}

/* ── Empty states ── */
.scm-empty {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 1.1rem 1rem;
    font-size: 0.82rem;
    color: var(--scm-muted);
    font-style: italic;
}

.scm-empty svg { width: 16px; height: 16px; flex-shrink: 0; opacity: 0.6; }

.scm-empty--global {
    flex-direction: column;
    text-align: center;
    padding: 2.5rem 1.5rem;
    background: var(--scm-bg);
    border: 1.5px dashed var(--scm-border);
    border-radius: var(--scm-radius);
    color: #92400e;
    font-style: normal;
    gap: 0.5rem;
    margin-bottom: 0;
}

.scm-empty--global strong { font-size: 0.9rem; }
.scm-empty--global span { font-size: 0.8rem; opacity: 0.85; }

/* ── Footer ── */
.scm-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 0 0;
    border-top: 1px solid var(--scm-border);
    margin-top: 0.5rem;
    flex-wrap: wrap;
}

.scm-footer-info {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.73rem;
    color: var(--scm-muted);
    font-style: italic;
}

.scm-footer-info svg { flex-shrink: 0; opacity: 0.7; }

.scm-footer-actions {
    display: flex;
    gap: 0.6rem;
    align-items: center;
    margin-left: auto;
}

/* ── Buttons ── */
.scm-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.55rem 1.2rem;
    border-radius: var(--scm-radius-sm);
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: var(--scm-transition);
    line-height: 1;
    white-space: nowrap;
}

.scm-btn--ghost {
    background: var(--scm-bg);
    color: var(--scm-muted);
    border: 1.5px solid var(--scm-border);
}

.scm-btn--ghost:hover {
    background: var(--scm-surface);
    border-color: #cbd5e1;
    color: var(--scm-ink-2);
}

.scm-btn--primary {
    background: linear-gradient(135deg, var(--scm-blue) 0%, var(--scm-blue-mid) 100%);
    color: white;
    box-shadow: 0 3px 10px var(--scm-blue-glow);
}

.scm-btn--primary:hover:not(:disabled) {
    box-shadow: 0 5px 18px rgba(4,83,203,0.3);
    transform: translateY(-1px);
}

.scm-btn--primary:active:not(:disabled) {
    transform: translateY(0);
    box-shadow: 0 2px 8px var(--scm-blue-glow);
}

.scm-btn--primary:disabled {
    opacity: 0.65;
    cursor: not-allowed;
    transform: none;
}

.scm-btn-icon { width: 15px; height: 15px; }

@keyframes scm-spin {
    to { transform: rotate(360deg); }
}

.scm-spin { animation: scm-spin 0.8s linear infinite; }

/* ── Responsive ── */
@media (max-width: 576px) {
    .scm-body { max-height: 68vh; padding: 1rem; }
    .scm-header { padding: 1rem; }
    .scm-combo-badges { display: none; }
    .scm-row { flex-wrap: wrap; }
    .scm-input-wrap { width: 100%; }
    .scm-input { width: 100%; }
    .scm-footer { flex-direction: column; align-items: stretch; }
    .scm-footer-actions { flex-direction: column; width: 100%; margin-left: 0; }
    .scm-btn { justify-content: center; width: 100%; }
    .scm-footer-info { justify-content: center; }
}
</style>

{{-- ═══════════════════════════════════════════════════════════════
     SCRIPT
═══════════════════════════════════════════════════════════════ --}}
<script>
(function () {
    'use strict';

    const form    = document.getElementById('scmForm');
    const saveBtn = document.getElementById('scmSaveBtn');
    const success = document.getElementById('scmSuccess');
    const errBox  = document.getElementById('scmError');
    const errText = document.getElementById('scmErrorText');
    const UPDATE_URL = "{{ route('esbtp.evaluations.coefficients.update') }}";

    if (!form) return;

    /* Focus the first empty blocking input if present */
    setTimeout(function () {
        const blocking = form.querySelector('.scm-input--blocking');
        if (blocking) blocking.focus();
    }, 400);

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        /* Reset feedback */
        success.classList.add('d-none');
        errBox.classList.add('d-none');

        /* Loading state */
        saveBtn.disabled = true;
        saveBtn.querySelector('.scm-btn-label').classList.add('d-none');
        saveBtn.querySelector('.scm-btn-loading').classList.remove('d-none');
        saveBtn.querySelector('.scm-btn-icon')?.classList?.add('d-none');

        const body = new FormData(form);

        fetch(UPDATE_URL, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
            },
            body: body,
        })
        .then(function (res) {
            return res.json().then(function (data) { return { ok: res.ok, data }; });
        })
        .then(function ({ ok, data }) {
            if (ok && data.success) {
                success.classList.remove('d-none');
                /* Reload after progress animation (1.5s) */
                setTimeout(function () { window.location.reload(); }, 1600);
            } else {
                throw new Error(data.message || 'Erreur lors de l\'enregistrement.');
            }
        })
        .catch(function (err) {
            errText.textContent = err.message || 'Une erreur réseau est survenue.';
            errBox.classList.remove('d-none');
            /* Restore button */
            saveBtn.disabled = false;
            saveBtn.querySelector('.scm-btn-label').classList.remove('d-none');
            saveBtn.querySelector('.scm-btn-loading').classList.add('d-none');
            saveBtn.querySelector('.scm-btn-icon')?.classList?.remove('d-none');
        });
    });
})();
</script>
@endif
