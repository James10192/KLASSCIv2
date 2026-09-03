{{--
    Releve annuel de notes — point d'entree utilisateur.

    Place volontairement HORS du hero : `.sr-hero` porte `animation: sr-fade-up ... both`,
    dont l'etat final `transform: translateY(0)` persiste. Un transform non nul cree un
    bloc conteneur : tout panneau flottant place a l'interieur s'ancrerait sur le hero et
    non sur la fenetre. La carte reste donc dans le flux, sans superposition ni menu
    deroulant — aucun risque de rognage ni de contexte d'empilement.

    Variables attendues : $bulletin (etudiant_id, annee_universitaire_id).
--}}
@can('lmd.releve.view')
@php
    $raEtudiantId = (int) ($bulletin->etudiant_id ?? 0);
    $raAnneeId = (int) ($bulletin->annee_universitaire_id ?? 0);
@endphp
@if($raEtudiantId > 0 && $raAnneeId > 0)
@php
    $raConfig = [
        'statusUrl' => route('esbtp.lmd.releves.status', [$raEtudiantId, $raAnneeId]),
        'issueUrl' => route('esbtp.lmd.releves.issue', [$raEtudiantId, $raAnneeId]),
        'previewUrl' => route('esbtp.lmd.releves.preview', [$raEtudiantId, $raAnneeId]),
        'downloadUrl' => route('esbtp.lmd.releves.download', [$raEtudiantId, $raAnneeId]),
        'motifMin' => \App\Domain\OfficialDocuments\Http\LmdTranscriptController::MOTIF_LONGUEUR_MIN,
    ];
@endphp

@include('partials._klassci_toast')

@push('styles')
<style>
    .ra-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
        margin-top: 1.5rem;
        overflow: hidden;
    }
    .ra-head {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid #eef2f7;
    }
    .ra-head-icon {
        width: 40px; height: 40px;
        border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem; flex-shrink: 0;
    }
    .ra-head-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; line-height: 1.25; }
    .ra-head-sub { font-size: .78rem; color: #64748b; margin: .1rem 0 0; }
    .ra-body { padding: 1.25rem 1.5rem; }

    .ra-state { display: flex; align-items: flex-start; gap: .65rem; font-size: .86rem; line-height: 1.45; }
    .ra-state i { margin-top: .18rem; flex-shrink: 0; }
    .ra-state--muted { color: #64748b; }
    .ra-state--warn { color: #92400e; }
    .ra-state--error { color: #b91c1c; }

    .ra-reasons { margin: .55rem 0 0; padding-left: 1.1rem; color: #92400e; font-size: .84rem; line-height: 1.55; }
    .ra-reasons li { margin-bottom: .2rem; }

    .ra-meta { display: flex; flex-wrap: wrap; gap: .5rem 1.75rem; margin-bottom: 1rem; }
    .ra-meta-label { font-size: .64rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .06em; }
    .ra-meta-value { font-size: .9rem; font-weight: 600; color: #1e293b; margin-top: .1rem; }
    .ra-ref { font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: .85rem; }

    .ra-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .22rem .6rem; border-radius: 6px;
        font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        background: rgba(16,185,129,.10); color: #047857; border: 1px solid rgba(16,185,129,.25);
    }

    .ra-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
    .ra-btn {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .55rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600;
        border: 1px solid #d7e0ec; background: #fff; color: #0453cb;
        cursor: pointer; text-decoration: none;
        transition: background .2s ease, border-color .2s ease, color .2s ease;
    }
    .ra-btn:hover { background: #f4f8ff; border-color: #0453cb; color: #033a8e; }
    .ra-btn--solid { background: #0453cb; border-color: #0453cb; color: #fff; }
    .ra-btn--solid:hover { background: #033a8e; border-color: #033a8e; color: #fff; }
    .ra-btn[disabled] { opacity: .55; cursor: not-allowed; }
    .ra-btn[disabled]:hover { background: #fff; border-color: #d7e0ec; color: #0453cb; }
    .ra-btn--solid[disabled]:hover { background: #0453cb; border-color: #0453cb; color: #fff; }

    .ra-replace { margin-top: 1rem; padding-top: 1rem; border-top: 1px dashed #e2e8f0; }
    .ra-label { display: block; font-size: .78rem; font-weight: 600; color: #1e293b; margin-bottom: .35rem; }
    .ra-textarea {
        width: 100%; min-height: 78px; resize: vertical;
        padding: .6rem .75rem; border: 1px solid #d7e0ec; border-radius: 10px;
        font-size: .85rem; color: #1e293b; font-family: inherit; line-height: 1.5;
    }
    .ra-textarea:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10); }
    .ra-hint { font-size: .74rem; color: #64748b; margin-top: .3rem; }
</style>
@endpush

<div class="ra-card sr-animate sr-animate-delay-4"
     data-ra-config='@json($raConfig)'
     x-data="releveAnnuel()"
     x-init="init()">

    <div class="ra-head">
        <div class="ra-head-icon"><i class="fas fa-file-contract"></i></div>
        <div>
            <h2 class="ra-head-title">Relevé annuel de notes</h2>
            <p class="ra-head-sub">Pièce officielle reprenant les résultats de toute l'année universitaire.</p>
        </div>
    </div>

    <div class="ra-body">

        {{-- Vérification en cours --}}
        <div class="ra-state ra-state--muted" x-show="chargement">
            <i class="fas fa-circle-notch fa-spin"></i>
            <span>Vérification de l'état du relevé…</span>
        </div>

        {{-- L'état n'a pas pu être lu --}}
        <div class="ra-state ra-state--error" x-show="!chargement && erreur" x-cloak>
            <i class="fas fa-circle-exclamation"></i>
            <div>
                <span x-text="erreur"></span>
                <div style="margin-top:.6rem;">
                    <button type="button" class="ra-btn" @click="charger()">
                        <i class="fas fa-rotate-right"></i>Réessayer
                    </button>
                </div>
            </div>
        </div>

        {{-- Relevé déjà émis --}}
        <div x-show="!chargement && !erreur && releve" x-cloak>
            <div class="ra-meta">
                <div>
                    <div class="ra-meta-label">Référence</div>
                    <div class="ra-meta-value ra-ref" x-text="releve?.reference || '—'"></div>
                </div>
                <div>
                    <div class="ra-meta-label">Version</div>
                    <div class="ra-meta-value" x-text="releve?.version ?? '—'"></div>
                </div>
                <div>
                    <div class="ra-meta-label">Émis le</div>
                    <div class="ra-meta-value" x-text="dateEmission()"></div>
                </div>
                <div>
                    <div class="ra-meta-label">État</div>
                    <div class="ra-meta-value">
                        <span class="ra-badge"><i class="fas fa-circle-check"></i>Disponible</span>
                    </div>
                </div>
            </div>

            <div class="ra-actions">
                <a class="ra-btn" :href="cfg.previewUrl" target="_blank" rel="noopener">
                    <i class="fas fa-eye"></i>Aperçu
                </a>
                <a class="ra-btn ra-btn--solid" :href="cfg.downloadUrl">
                    <i class="fas fa-download"></i>Télécharger
                </a>
                @can('lmd.releve.issue')
                <button type="button" class="ra-btn" x-show="!remplacement" @click="remplacement = true">
                    <i class="fas fa-arrows-rotate"></i>Remplacer
                </button>
                @endcan
            </div>

            @can('lmd.releve.issue')
            <div class="ra-replace" x-show="remplacement" x-cloak>
                <label class="ra-label" :for="'ra-motif-' + uid">Motif du remplacement</label>
                <textarea class="ra-textarea"
                          :id="'ra-motif-' + uid"
                          x-model="motif"
                          :disabled="envoi"
                          placeholder="Expliquez ce qui est rectifié par rapport au relevé précédent."></textarea>
                <div class="ra-hint">
                    Obligatoire, <span x-text="cfg.motifMin"></span> caractères au minimum. Le relevé actuel sera remplacé et conservé dans l'historique.
                </div>
                <div class="ra-actions" style="margin-top:.75rem;">
                    <button type="button" class="ra-btn ra-btn--solid"
                            :disabled="!motifValide || envoi"
                            @click="emettre()">
                        <i class="fas fa-circle-notch fa-spin" x-show="envoi" x-cloak></i>
                        <i class="fas fa-check" x-show="!envoi"></i>
                        <span x-text="envoi ? 'Émission en cours…' : 'Confirmer le remplacement'"></span>
                    </button>
                    <button type="button" class="ra-btn" :disabled="envoi" @click="remplacement = false; motif = '';">
                        Annuler
                    </button>
                </div>
            </div>
            @endcan
        </div>

        {{-- Aucun relevé, et les conditions ne sont pas réunies --}}
        <div x-show="!chargement && !erreur && !releve && !readiness.ok" x-cloak>
            <div class="ra-state ra-state--warn">
                <i class="fas fa-triangle-exclamation"></i>
                <div>
                    <strong>Le relevé ne peut pas encore être émis.</strong>
                    <ul class="ra-reasons">
                        <template x-for="(raison, i) in (readiness.reasons || [])" :key="i">
                            <li x-text="raison"></li>
                        </template>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Aucun relevé, conditions réunies --}}
        <div x-show="!chargement && !erreur && !releve && readiness.ok" x-cloak>
            @can('lmd.releve.issue')
                <div class="ra-state ra-state--muted" style="margin-bottom:1rem;">
                    <i class="fas fa-circle-info"></i>
                    <span>Les résultats de l'année sont arrêtés. Le relevé peut être émis.</span>
                </div>
                <div class="ra-actions">
                    <button type="button" class="ra-btn ra-btn--solid" :disabled="envoi" @click="emettre()">
                        <i class="fas fa-circle-notch fa-spin" x-show="envoi" x-cloak></i>
                        <i class="fas fa-stamp" x-show="!envoi"></i>
                        <span x-text="envoi ? 'Émission en cours…' : 'Émettre le relevé'"></span>
                    </button>
                </div>
            @else
                <div class="ra-state ra-state--muted">
                    <i class="fas fa-circle-info"></i>
                    <span>Aucun relevé n'a encore été émis pour cette année. Vous n'avez pas le droit de l'émettre.</span>
                </div>
            @endcan
        </div>

    </div>
</div>

@push('scripts')
<script>
    if (typeof window.releveAnnuel !== 'function') {
        window.releveAnnuel = function () {
            return {
                cfg: {},
                uid: Math.random().toString(36).slice(2, 9),
                chargement: true,
                envoi: false,
                erreur: '',
                readiness: { ok: false, reasons: [], semesters: 0 },
                releve: null,
                remplacement: false,
                motif: '',

                init() {
                    try {
                        this.cfg = JSON.parse(this.$root.dataset.raConfig || '{}');
                    } catch (e) {
                        this.cfg = {};
                    }
                    this.charger();
                },

                get motifValide() {
                    return this.motif.trim().length >= (this.cfg.motifMin || 10);
                },

                entetes() {
                    return {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    };
                },

                dateEmission() {
                    if (!this.releve || !this.releve.issued_at) {
                        return '—';
                    }
                    const d = new Date(this.releve.issued_at);
                    if (isNaN(d.getTime())) {
                        return '—';
                    }
                    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
                },

                async charger() {
                    if (!this.cfg.statusUrl) {
                        this.chargement = false;
                        this.erreur = "L'état du relevé n'a pas pu être vérifié.";
                        return;
                    }
                    this.chargement = true;
                    this.erreur = '';
                    try {
                        const rep = await fetch(this.cfg.statusUrl, {
                            headers: this.entetes(),
                            credentials: 'same-origin',
                        });
                        if (!rep.ok) {
                            throw new Error("L'état du relevé n'a pas pu être vérifié.");
                        }
                        const data = await rep.json();
                        // Un repli sans motif afficherait « Le relevé ne peut pas
                        // encore être émis. » suivi d'une liste vide : l'utilisateur
                        // lit un refus sans raison. On bascule sur l'erreur, qui offre
                        // « Réessayer ».
                        if (!data.readiness) {
                            throw new Error("L'état du relevé n'a pas pu être vérifié.");
                        }
                        this.readiness = data.readiness;
                        this.releve = data.releve || null;
                    } catch (e) {
                        this.erreur = e.message || "L'état du relevé n'a pas pu être vérifié.";
                    } finally {
                        this.chargement = false;
                    }
                },

                async emettre() {
                    if (this.envoi) {
                        return;
                    }
                    if (this.releve && !this.motifValide) {
                        return;
                    }
                    this.envoi = true;
                    try {
                        const jeton = document.querySelector('meta[name="csrf-token"]');
                        const corps = new FormData();
                        if (this.releve) {
                            corps.append('motif', this.motif.trim());
                        }
                        const entetes = this.entetes();
                        entetes['X-CSRF-TOKEN'] = jeton ? jeton.getAttribute('content') : '';

                        const rep = await fetch(this.cfg.issueUrl, {
                            method: 'POST',
                            headers: entetes,
                            credentials: 'same-origin',
                            body: corps,
                        });
                        let data = {};
                        try {
                            data = await rep.json();
                        } catch (e) {
                            data = {};
                        }

                        if (!rep.ok || data.success === false) {
                            let message;
                            if (Array.isArray(data.reasons) && data.reasons.length > 0) {
                                message = data.reasons.join(' ');
                            } else {
                                message = data.message || "Le relevé n'a pas pu être émis.";
                            }
                            throw new Error(message);
                        }

                        this.remplacement = false;
                        this.motif = '';
                        const ref = (data.releve && data.releve.reference) ? data.releve.reference : null;
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'success',
                                message: ref
                                    ? ('Le relevé est émis sous la référence ' + ref + '.')
                                    : 'Le relevé est émis.',
                            },
                        }));
                        await this.charger();
                    } catch (e) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { type: 'error', message: e.message || "Le relevé n'a pas pu être émis." },
                        }));
                    } finally {
                        this.envoi = false;
                    }
                },
            };
        };
    }
</script>
@endpush
@endif
@endcan
