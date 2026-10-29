{{--
    Les dispenses de matiere de l'etudiant, pour l'annee affichee.

    Ce bloc explique les trous du bulletin la ou on les regarde. Il charge et
    ecrit en AJAX : la fiche etudiant ne se recharge jamais.

    Absent en LMD, ou les dispenses attendent la validation des regles de jury.

    @param \App\Models\ESBTPInscription|null $acadRef inscription de reference
--}}
@php
    $_dspAnneeId = optional(optional($acadRef)->anneeUniversitaire)->id;
    $_dspAnneeLibelle = optional(optional($acadRef)->anneeUniversitaire)->name ?? '';
    $_dspSystemeLmd = optional(optional($acadRef)->classe)->systeme_academique === 'LMD';
@endphp

@can('dispenses.view')
@if($_dspAnneeId && ! $_dspSystemeLmd)
<div class="dsp-panel"
     x-data="dispensesPanel({
         anneeId: {{ (int) $_dspAnneeId }},
         urlIndex: '{{ route('esbtp.etudiants.dispenses.index', $etudiant) }}',
         urlStore: '{{ route('esbtp.etudiants.dispenses.store', $etudiant) }}',
         urlRevoquer: '{{ url('esbtp/dispenses') }}',
         peutGerer: {{ auth()->user()?->can('dispenses.manage') ? 'true' : 'false' }}
     })"
     x-init="charger()">

    <div class="dsp-head">
        <div class="dsp-title">
            <i class="fas fa-file-circle-check"></i>
            <span>Dispenses de matière</span>
            <span class="dsp-annee">{{ $_dspAnneeLibelle }}</span>
        </div>
        <template x-if="peutGerer">
            <button type="button" class="dsp-btn dsp-btn--primary" @click="ouvrirFormulaire()">
                <i class="fas fa-plus"></i> Accorder une dispense
            </button>
        </template>
    </div>

    {{-- Chargement --}}
    <template x-if="chargement">
        <div class="dsp-etat"><i class="fas fa-circle-notch fa-spin"></i> Chargement des dispenses…</div>
    </template>

    {{-- Echec du chargement : on le dit, on ne laisse pas un bloc vide --}}
    <template x-if="!chargement && erreurChargement">
        <div class="dsp-etat dsp-etat--erreur">
            <i class="fas fa-triangle-exclamation"></i>
            <span x-text="erreurChargement"></span>
            <button type="button" class="dsp-lien" @click="charger()">Réessayer</button>
        </div>
    </template>

    {{-- Aucune dispense --}}
    <template x-if="!chargement && !erreurChargement && dispenses.length === 0">
        <div class="dsp-etat dsp-etat--vide">
            <i class="fas fa-circle-info"></i>
            Aucune dispense pour cette année. Toutes les matières du bulletin sont notées.
        </div>
    </template>

    {{-- Liste --}}
    <template x-if="!chargement && dispenses.length > 0">
        <div class="dsp-liste">
            <template x-for="d in dispenses" :key="d.id">
                <div class="dsp-ligne" :class="d.active ? '' : 'dsp-ligne--revoquee'">
                    <div class="dsp-ligne-main">
                        <div class="dsp-matiere">
                            <span x-text="d.matiere"></span>
                            <span class="dsp-badge" :class="d.active ? 'dsp-badge--active' : 'dsp-badge--off'"
                                  x-text="d.active ? d.portee : 'Révoquée'"></span>
                        </div>
                        <div class="dsp-motif" x-text="d.motif"></div>
                        <div class="dsp-meta">
                            <span x-text="'Accordée le ' + (d.accordee_le || '—') + (d.accordee_par ? ' par ' + d.accordee_par : '')"></span>
                            <template x-if="!d.active">
                                <span x-text="' · Révoquée le ' + (d.revoquee_le || '—') + ' : ' + (d.motif_revocation || '')"></span>
                            </template>
                        </div>
                    </div>
                    <template x-if="peutGerer && d.active">
                        <button type="button" class="dsp-btn dsp-btn--ghost" @click="ouvrirRevocation(d)">
                            <i class="fas fa-rotate-left"></i> Révoquer
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </template>

    {{-- Accorder --}}
    <div class="dsp-modal" x-show="formOuvert" x-cloak x-transition.opacity
         @keydown.escape.window="fermerFormulaire()">
        <div class="dsp-modal-box" @click.outside="fermerFormulaire()">
            <div class="dsp-modal-head">
                <span>Accorder une dispense</span>
                <button type="button" class="dsp-close" @click="fermerFormulaire()" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <form @submit.prevent="accorder()">
                <div class="dsp-field">
                    <label>Matière</label>
                    <select class="dsp-input" x-model="form.matiere_id" required>
                        <option value="">Choisir une matière…</option>
                        <template x-for="m in matieres" :key="m.value">
                            <option :value="m.value" x-text="m.label"></option>
                        </template>
                    </select>
                </div>
                <div class="dsp-field">
                    <label>Portée</label>
                    <select class="dsp-input" x-model="form.periode">
                        <option value="annuel">Année complète</option>
                        <option value="semestre1">Semestre 1</option>
                        <option value="semestre2">Semestre 2</option>
                    </select>
                </div>
                <div class="dsp-field">
                    <label>Motif <span class="dsp-req">obligatoire</span></label>
                    <textarea class="dsp-input" rows="2" maxlength="160" minlength="10"
                              x-model="form.motif" required
                              placeholder="Ex. : matière validée lors du parcours antérieur"></textarea>
                    <div class="dsp-aide">Ce motif figure sur le bulletin. <span x-text="form.motif.length"></span>/160</div>
                </div>
                <template x-if="erreurForm">
                    <div class="dsp-erreur" x-text="erreurForm"></div>
                </template>
                <div class="dsp-modal-actions">
                    <button type="button" class="dsp-btn dsp-btn--ghost" @click="fermerFormulaire()">Annuler</button>
                    <button type="submit" class="dsp-btn dsp-btn--primary" :disabled="envoi">
                        <span x-show="!envoi">Accorder</span>
                        <span x-show="envoi" x-cloak>Enregistrement…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Revoquer. Une boite de dialogue du navigateur ne saurait pas exiger un
         motif, et ce motif est ce qui rendra la decision lisible plus tard. --}}
    <div class="dsp-modal" x-show="revocationOuverte" x-cloak x-transition.opacity
         @keydown.escape.window="fermerRevocation()">
        <div class="dsp-modal-box" @click.outside="fermerRevocation()">
            <div class="dsp-modal-head">
                <span>Révoquer la dispense</span>
                <button type="button" class="dsp-close" @click="fermerRevocation()" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <form @submit.prevent="revoquer()">
                <p class="dsp-aide">
                    <strong x-text="cible ? cible.matiere : ''"></strong> redeviendra notée au prochain calcul du bulletin.
                    La dispense reste consultable dans l'historique.
                </p>
                <div class="dsp-field">
                    <label>Motif de la révocation <span class="dsp-req">obligatoire</span></label>
                    <textarea class="dsp-input" rows="2" maxlength="160" minlength="10"
                              x-model="motifRevocation" required
                              placeholder="Ex. : dispense accordée par erreur"></textarea>
                </div>
                <template x-if="erreurRevocation">
                    <div class="dsp-erreur" x-text="erreurRevocation"></div>
                </template>
                <div class="dsp-modal-actions">
                    <button type="button" class="dsp-btn dsp-btn--ghost" @click="fermerRevocation()">Annuler</button>
                    <button type="submit" class="dsp-btn dsp-btn--primary" :disabled="envoi">
                        <span x-show="!envoi">Révoquer</span>
                        <span x-show="envoi" x-cloak>Enregistrement…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.dsp-panel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.1rem 1.25rem; margin-bottom:1.25rem; box-shadow:0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
.dsp-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:.85rem; }
.dsp-title { display:flex; align-items:center; gap:.55rem; font-size:.95rem; font-weight:700; color:#1e293b; }
.dsp-title i { color:#0453cb; }
.dsp-annee { font-size:.7rem; font-weight:600; color:#0453cb; background:rgba(4,83,203,.08); padding:.15rem .5rem; border-radius:5px; }
.dsp-etat { display:flex; align-items:center; gap:.5rem; font-size:.82rem; color:#64748b; padding:.85rem; background:#f8fafc; border-radius:10px; }
.dsp-etat--erreur { color:#b45309; background:rgba(245,158,11,.08); }
.dsp-liste { display:flex; flex-direction:column; gap:.5rem; }
.dsp-ligne { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; padding:.7rem .85rem; border:1px solid #e2e8f0; border-radius:10px; }
.dsp-ligne--revoquee { opacity:.62; }
.dsp-ligne-main { min-width:0; }
.dsp-matiere { display:flex; align-items:center; gap:.5rem; font-size:.88rem; font-weight:700; color:#1e293b; }
.dsp-badge { font-size:.66rem; font-weight:700; padding:.12rem .45rem; border-radius:5px; text-transform:uppercase; }
.dsp-badge--active { color:#0453cb; background:rgba(4,83,203,.10); border:1px solid rgba(4,83,203,.25); }
.dsp-badge--off { color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0; }
.dsp-motif { font-size:.8rem; color:#1e293b; margin-top:.2rem; }
.dsp-meta { font-size:.7rem; color:#64748b; margin-top:.15rem; }
.dsp-btn { display:inline-flex; align-items:center; gap:.4rem; border-radius:9px; padding:.45rem .85rem; font-size:.78rem; font-weight:600; border:1px solid transparent; cursor:pointer; transition:all .2s ease; }
.dsp-btn--primary { background:#0453cb; color:#fff; }
.dsp-btn--primary:hover:not(:disabled) { background:#033a8e; }
.dsp-btn--primary:disabled { opacity:.6; cursor:wait; }
.dsp-btn--ghost { background:#fff; color:#0453cb; border-color:#c7d4e5; }
.dsp-btn--ghost:hover { background:rgba(4,83,203,.06); }
.dsp-lien { background:none; border:none; color:#0453cb; font-size:.78rem; font-weight:600; cursor:pointer; text-decoration:underline; }
.dsp-modal { position:fixed; inset:0; background:rgba(15,23,42,.45); display:flex; align-items:center; justify-content:center; z-index:1080; padding:1rem; }
.dsp-modal-box { background:#fff; border-radius:14px; width:100%; max-width:460px; padding:1.15rem 1.25rem; box-shadow:0 20px 45px rgba(15,23,42,.22); }
.dsp-modal-head { display:flex; align-items:center; justify-content:space-between; font-size:.95rem; font-weight:700; color:#1e293b; margin-bottom:.9rem; }
.dsp-close { background:none; border:none; color:#64748b; font-size:1rem; cursor:pointer; }
.dsp-field { display:flex; flex-direction:column; gap:.3rem; margin-bottom:.85rem; }
.dsp-field label { font-size:.72rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.4px; }
.dsp-req { color:#dc2626; text-transform:none; letter-spacing:0; font-weight:600; }
.dsp-input { width:100%; border:1px solid #e2e8f0; border-radius:9px; padding:.5rem .7rem; font-size:.85rem; color:#1e293b; background:#fff; }
.dsp-input:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.10); }
.dsp-aide { font-size:.7rem; color:#64748b; }
.dsp-erreur { font-size:.78rem; color:#dc2626; background:rgba(220,38,38,.07); border-radius:8px; padding:.5rem .7rem; margin-bottom:.6rem; }
.dsp-modal-actions { display:flex; justify-content:flex-end; gap:.5rem; }
@@media (max-width: 576px) {
    .dsp-ligne { flex-direction:column; align-items:stretch; }
}
</style>

<script>
if (typeof window.dispensesPanel !== 'function') {
    window.dispensesPanel = function (config) {
        return {
            anneeId: config.anneeId,
            urlIndex: config.urlIndex,
            urlStore: config.urlStore,
            urlRevoquer: config.urlRevoquer,
            peutGerer: config.peutGerer,

            dispenses: [],
            matieres: [],
            chargement: true,
            erreurChargement: '',

            formOuvert: false,
            form: { matiere_id: '', periode: 'annuel', motif: '' },
            erreurForm: '',

            revocationOuverte: false,
            cible: null,
            motifRevocation: '',
            erreurRevocation: '',

            envoi: false,

            entetes() {
                var jeton = document.querySelector('meta[name="csrf-token"]');
                return {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': jeton ? jeton.content : '',
                };
            },

            async charger() {
                this.chargement = true;
                this.erreurChargement = '';
                try {
                    var url = this.urlIndex + '?annee_universitaire_id=' + this.anneeId;
                    var res = await fetch(url, { headers: this.entetes() });
                    if (!res.ok) { throw new Error('Chargement des dispenses impossible (' + res.status + ').'); }
                    var data = await res.json();
                    this.dispenses = data.dispenses || [];
                    this.matieres = data.matieres || [];
                } catch (err) {
                    this.erreurChargement = err.message;
                } finally {
                    this.chargement = false;
                }
            },

            ouvrirFormulaire() {
                this.form = { matiere_id: '', periode: 'annuel', motif: '' };
                this.erreurForm = '';
                this.formOuvert = true;
            },
            fermerFormulaire() { this.formOuvert = false; },

            ouvrirRevocation(dispense) {
                this.cible = dispense;
                this.motifRevocation = '';
                this.erreurRevocation = '';
                this.revocationOuverte = true;
            },
            fermerRevocation() { this.revocationOuverte = false; this.cible = null; },

            toast(type, message) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
            },

            async accorder() {
                this.envoi = true;
                this.erreurForm = '';
                try {
                    var res = await fetch(this.urlStore, {
                        method: 'POST',
                        headers: this.entetes(),
                        body: JSON.stringify({
                            matiere_id: this.form.matiere_id,
                            annee_universitaire_id: this.anneeId,
                            periode: this.form.periode,
                            motif: this.form.motif,
                        }),
                    });
                    var data = await res.json().catch(function () { return {}; });
                    if (!res.ok) { throw new Error(data.message || 'Enregistrement impossible.'); }
                    this.fermerFormulaire();
                    this.toast('success', data.message || 'Dispense accordée.');
                    await this.charger();
                } catch (err) {
                    this.erreurForm = err.message;
                } finally {
                    this.envoi = false;
                }
            },

            async revoquer() {
                if (!this.cible) { return; }
                this.envoi = true;
                this.erreurRevocation = '';
                try {
                    var res = await fetch(this.urlRevoquer + '/' + this.cible.id + '/revoquer', {
                        method: 'PATCH',
                        headers: this.entetes(),
                        body: JSON.stringify({ motif: this.motifRevocation }),
                    });
                    var data = await res.json().catch(function () { return {}; });
                    if (!res.ok) { throw new Error(data.message || 'Révocation impossible.'); }
                    this.fermerRevocation();
                    this.toast('success', data.message || 'Dispense révoquée.');
                    await this.charger();
                } catch (err) {
                    this.erreurRevocation = err.message;
                } finally {
                    this.envoi = false;
                }
            },
        };
    };
}
</script>
@endif
@endcan
