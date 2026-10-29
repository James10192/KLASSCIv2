@extends('layouts.app')

@section('title', 'Accès individuels - KLASSCI')

@push('styles')
<style>
    .atp-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
    .atp-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .atp-hero-left { display: flex; align-items: center; gap: 1rem; }
    .atp-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; color: #fff; }
    .atp-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .atp-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .2rem 0 0; max-width: 640px; }
    .atp-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .atp-kpi { flex: 1; min-width: 140px; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .9rem 1rem; }
    .atp-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; }
    .atp-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .15rem; }

    .atp-grid { display: grid; grid-template-columns: minmax(320px, 420px) 1fr; gap: 1.25rem; align-items: start; }
    .atp-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); padding: 1.25rem 1.5rem; }
    .atp-section-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
    .atp-section-icon { width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #0453cb, #3b7ddb); display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem; }
    .atp-section-header h2 { font-size: 1.02rem; font-weight: 700; color: #1e293b; margin: 0; }
    .atp-section-header small { color: #64748b; font-size: .78rem; }

    .atp-field { display: flex; flex-direction: column; gap: .35rem; margin-bottom: 1rem; }
    .atp-field > label { font-size: .78rem; font-weight: 600; color: #334155; }
    .atp-field .au-select, .atp-field .au-up { display: flex !important; width: 100%; }
    .atp-hint { font-size: .74rem; color: #64748b; }
    .atp-input { border: 1px solid #cbd5e1; border-radius: 10px; padding: .55rem .75rem; font-size: .88rem; color: #1e293b; width: 100%; }
    .atp-input:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .atp-presets { display: flex; flex-wrap: wrap; gap: .4rem; }
    .atp-preset { border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 8px; padding: .35rem .7rem; font-size: .78rem; font-weight: 600; cursor: pointer; transition: all .2s ease; }
    .atp-preset:hover { border-color: #0453cb; color: #0453cb; }
    .atp-preset--active { background: #0453cb; border-color: #0453cb; color: #fff; }
    .atp-mode { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .55rem; margin-bottom: .5rem; }
    .atp-mode-choice { border: 1px solid #cbd5e1; background: #fff; color: #334155; border-radius: 10px; padding: .75rem; text-align: left; display: flex; flex-direction: column; gap: .2rem; cursor: pointer; }
    .atp-mode-choice strong { font-size: .84rem; }
    .atp-mode-choice small { color: #64748b; font-size: .72rem; }
    .atp-mode-choice--active { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); color: #0453cb; }
    .atp-warning { background: #fffbeb; border: 1px solid #fcd34d; color: #92400e; border-radius: 10px; padding: .7rem .8rem; font-size: .78rem; }
    .atp-btn { display: inline-flex; align-items: center; gap: .45rem; border: none; border-radius: 10px; padding: .6rem 1.1rem; font-size: .85rem; font-weight: 600; cursor: pointer; transition: all .2s ease; }
    .atp-btn--primary { background: #0453cb; color: #fff; }
    .atp-btn--primary:hover:not(:disabled) { background: #033a8e; }
    .atp-btn:disabled { opacity: .6; cursor: wait; }
    .atp-btn--ghost { background: transparent; color: #dc2626; border: 1px solid rgba(220,38,38,.35); padding: .3rem .65rem; font-size: .76rem; }
    .atp-btn--ghost:hover:not(:disabled) { background: rgba(220,38,38,.06); }

    .atp-alert { border-radius: 10px; padding: .7rem .9rem; font-size: .84rem; margin-bottom: 1rem; display: flex; gap: .5rem; align-items: flex-start; }
    .atp-alert--success { background: rgba(16,185,129,.1); color: #065f46; border: 1px solid rgba(16,185,129,.3); }
    .atp-alert--error { background: rgba(220,38,38,.08); color: #991b1b; border: 1px solid rgba(220,38,38,.25); }

    .atp-filters { display: flex; gap: .4rem; }
    .atp-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .atp-table th { text-align: left; font-size: .7rem; text-transform: uppercase; letter-spacing: .4px; color: #64748b; font-weight: 700; padding: .55rem .6rem; border-bottom: 1px solid #e2e8f0; }
    .atp-table td { padding: .7rem .6rem; border-bottom: 1px solid #f1f5f9; vertical-align: top; color: #1e293b; }
    .atp-perm { font-weight: 600; }
    .atp-code { font-family: 'Courier New', monospace; font-size: .72rem; color: #0453cb; background: rgba(4,83,203,.08); padding: .05rem .35rem; border-radius: 4px; }
    .atp-muted { color: #64748b; font-size: .76rem; }
    .atp-badge { display: inline-block; border-radius: 999px; padding: .15rem .6rem; font-size: .72rem; font-weight: 700; }
    .atp-badge--active { background: rgba(16,185,129,.12); color: #047857; }
    .atp-badge--definitif { background: rgba(4,83,203,.1); color: #0453cb; }
    .atp-badge--a_venir { background: rgba(4,83,203,.1); color: #0453cb; }
    .atp-badge--expiree { background: #f1f5f9; color: #64748b; }
    .atp-badge--retiree { background: rgba(220,38,38,.08); color: #b91c1c; }
    .atp-empty { text-align: center; color: #64748b; padding: 2rem 1rem; }
    .atp-empty i { font-size: 1.6rem; color: #94a3b8; display: block; margin-bottom: .5rem; }
    .atp-pagination { display: flex; justify-content: flex-end; align-items: center; gap: .5rem; margin-top: 1rem; flex-wrap: wrap; }
    .atp-pagination span { font-size: .78rem; color: #64748b; }
    .atp-pagination .atp-preset:disabled { opacity: .45; cursor: not-allowed; }
    .atp-dialog { position: fixed; inset: 0; margin: auto; box-sizing: border-box; width: min(520px, calc(100vw - 24px)); max-width: calc(100vw - 24px); max-height: calc(100dvh - 24px); overflow-y: auto; border: 0; border-radius: 16px; background: #fff; color: #1e293b; padding: 0; box-shadow: 0 20px 60px rgba(15,23,42,.2); }
    .atp-dialog::backdrop { background: rgba(8,25,51,.55); }
    .atp-dialog-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.2rem 1.4rem; border-bottom: 1px solid #e2e8f0; }
    .atp-dialog-header h2 { margin: 0; font-size: 1.08rem; font-weight: 700; }
    .atp-dialog-close { border: none; background: transparent; color: #64748b; cursor: pointer; font-size: 1rem; padding: .25rem; }
    .atp-dialog-body { padding: 1.2rem 1.4rem; }
    .atp-dialog-row { display: flex; justify-content: space-between; gap: 1rem; border-bottom: 1px solid #f1f5f9; padding: .55rem 0; font-size: .82rem; }
    .atp-dialog-row span { color: #64748b; }
    .atp-dialog-row strong { text-align: right; overflow-wrap: anywhere; }
    .atp-dialog-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: .6rem; padding: 1rem 1.4rem; border-top: 1px solid #e2e8f0; }
    .atp-btn--secondary { background: #f1f5f9; color: #334155; }
    .atp-btn--danger { background: #dc2626; color: #fff; }
    .atp-btn--danger:hover:not(:disabled) { background: #991b1b; }
    .atp-table td[data-label]::before { display: none; }

    @media (max-width: 992px) { .atp-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
        .atp-hero { padding: 1.5rem 1.25rem 1.25rem; }
        .atp-table thead { display: none; }
        .atp-table tr { display: block; border-bottom: 1px solid #e2e8f0; padding: .5rem 0; }
        .atp-table td { display: block; border: none; padding: .2rem .4rem; }
        .atp-table td[data-label]::before { display: block; content: attr(data-label); font-size: .65rem; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; color: #64748b; margin-bottom: .1rem; }
        .atp-mode { grid-template-columns: 1fr; }
        .atp-card { padding: 1rem; }
        .atp-filters { flex-wrap: wrap; }
    }
</style>
@endpush

@section('content')
@php
    $_atpConfig = [
        'acces' => $listeInitiale['acces'],
        'pagination' => $listeInitiale['pagination'],
        'statistiques' => $statistiques,
        'urls' => [
            'store' => route('esbtp.acces-temporaires.store'),
            'data' => route('esbtp.acces-temporaires.data'),
            'destroy' => url('esbtp/acces-temporaires'),
        ],
        'dureeMaxJours' => $dureeMaxJours,
        'peutAccorderDefinitif' => $peutAccorderDefinitif,
    ];
@endphp
<div class="dashboard-acasi" x-data="accesTemporaires()" data-config='@json($_atpConfig)'>
    <div class="main-content" style="padding: 1.25rem; max-width: 100%;">
    <div class="atp-hero">
        <div class="atp-hero-top">
            <div class="atp-hero-left">
                <div class="atp-hero-icon"><i class="fas fa-user-shield"></i></div>
                <div>
                    <h1>Accès individuels</h1>
                    <p>Accordez une permission à une seule personne, temporairement ou sans expiration, sans modifier les droits de son rôle. Chaque accès reste traçable et révocable.</p>
                </div>
            </div>
        </div>
        <div class="atp-kpis">
            <div class="atp-kpi"><div class="atp-kpi-value" x-text="statistiques.actifs">0</div><div class="atp-kpi-label">Accès en cours</div></div>
            <div class="atp-kpi"><div class="atp-kpi-value" x-text="statistiques.definitifs">0</div><div class="atp-kpi-label">Définitifs actifs</div></div>
            <div class="atp-kpi"><div class="atp-kpi-value" x-text="statistiques.a_venir">0</div><div class="atp-kpi-label">À venir</div></div>
            <div class="atp-kpi"><div class="atp-kpi-value" x-text="statistiques.total">0</div><div class="atp-kpi-label">Historique total</div></div>
        </div>
    </div>

    <div class="atp-grid">
        <div class="atp-card">
            <div class="atp-section-header">
                <div class="atp-section-icon"><i class="fas fa-key"></i></div>
                <div>
                    <h2>Accorder un accès</h2>
                    <small>Une permission pour une personne, sans changement de rôle.</small>
                </div>
            </div>

            <template x-if="feedback">
                <div class="atp-alert" :class="feedback.type === 'success' ? 'atp-alert--success' : 'atp-alert--error'">
                    <i class="fas" :class="feedback.type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                    <span x-text="feedback.message"></span>
                </div>
            </template>

            <form x-ref="form" x-on:submit.prevent="preparerAccord()">
                <div class="atp-field">
                    <label>Personne</label>
                    <x-au-user-picker name="user_id" :users="$personnel" :value="$personneChoisie" placeholder="— Choisir une personne —" :empty-option="false" />
                </div>

                <div class="atp-field">
                    <label>Permission</label>
                    <x-au-select name="permission" :options="$permissions" :searchable="true" icon="fa-key" placeholder="— Choisir la permission —" />
                    <span class="atp-hint">Les permissions sensibles permettant de contourner les contrôles ou modifier des rôles ne sont pas accordables.</span>
                </div>

                <div class="atp-field">
                    <label>Type d'accès</label>
                    <input type="hidden" name="mode" :value="mode">
                    <div class="atp-mode" role="group" aria-label="Durée de l'accès individuel">
                        <button type="button" class="atp-mode-choice" :class="mode === 'temporaire' ? 'atp-mode-choice--active' : ''" :aria-pressed="(mode === 'temporaire').toString()" x-on:click="mode = 'temporaire'">
                            <strong><i class="fas fa-clock"></i> Temporaire</strong><small>Expiration automatique à la date prévue</small>
                        </button>
                        <button type="button" class="atp-mode-choice" x-show="peutAccorderDefinitif" x-cloak :class="mode === 'definitif' ? 'atp-mode-choice--active' : ''" :aria-pressed="(mode === 'definitif').toString()" x-on:click="mode = 'definitif'">
                            <strong><i class="fas fa-shield-alt"></i> Définitif</strong><small>Sans expiration, retrait manuel obligatoire</small>
                        </button>
                    </div>
                    <span class="atp-hint" x-show="!peutAccorderDefinitif">Les accès définitifs nécessitent une autorisation d'administration supplémentaire.</span>
                </div>

                <div class="atp-field" x-show="mode === 'temporaire'">
                    <label>Durée</label>
                    <div class="atp-presets">
                        <template x-for="p in presets" :key="p.jours">
                            <button type="button" class="atp-preset" :class="presetActif === p.jours ? 'atp-preset--active' : ''"
                                    x-on:click="choisirDuree(p.jours)" x-text="p.label"></button>
                        </template>
                    </div>
                    <span class="atp-hint">Maximum : {{ $dureeMaxJours }} jours sur cet établissement.</span>
                </div>

                <div class="atp-field" x-show="mode === 'temporaire'">
                    <label for="atp-fin">Accès ouvert jusqu'au</label>
                    <input id="atp-fin" type="datetime-local" name="fin" class="atp-input" x-model="fin" x-on:input="presetActif = null" :disabled="mode !== 'temporaire'" :required="mode === 'temporaire'">
                </div>

                <div class="atp-warning atp-field" x-show="mode === 'definitif'" x-cloak>
                    <i class="fas fa-exclamation-triangle"></i> Cet accès n'expirera pas automatiquement. Il restera actif jusqu'à sa révocation, même après la durée maximale d'un accès temporaire.
                </div>

                <div class="atp-field">
                    <label for="atp-motif">Motif</label>
                    <textarea id="atp-motif" name="motif" rows="3" class="atp-input" minlength="10" required
                              placeholder="Ex. : correction des notes du semestre 2 en l'absence du titulaire"></textarea>
                </div>

                <button type="submit" class="atp-btn atp-btn--primary" :disabled="envoi">
                    <i class="fas" :class="envoi ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="envoi ? 'Enregistrement…' : 'Vérifier et accorder'"></span>
                </button>
            </form>
        </div>

        <div class="atp-card">
            <div class="atp-section-header" style="justify-content: space-between;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <div class="atp-section-icon"><i class="fas fa-list"></i></div>
                    <div>
                        <h2>Accès accordés</h2>
                        <small>Les accès expirés ou retirés restent dans l'historique.</small>
                    </div>
                </div>
                <div class="atp-filters">
                    <button type="button" class="atp-preset" :class="filtre === 'en_cours' ? 'atp-preset--active' : ''" x-on:click="changerFiltre('en_cours')">En cours</button>
                    <button type="button" class="atp-preset" :class="filtre === 'definitifs' ? 'atp-preset--active' : ''" x-on:click="changerFiltre('definitifs')">Définitifs</button>
                    <button type="button" class="atp-preset" :class="filtre === 'tous' ? 'atp-preset--active' : ''" x-on:click="changerFiltre('tous')">Historique</button>
                </div>
            </div>

            <template x-if="visibles().length === 0">
                <div class="atp-empty"><i class="fas fa-user-lock"></i>Aucun accès individuel pour ce filtre.</div>
            </template>

            <table class="atp-table" x-show="visibles().length > 0">
                <thead>
                    <tr><th>Personne</th><th>Permission</th><th>Période</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                    <template x-for="a in visibles()" :key="a.id">
                        <tr>
                            <td data-label="Personne">
                                <div x-text="a.personne"></div>
                                <div class="atp-muted" x-show="a.accorde_par" x-text="'Accordé par ' + a.accorde_par"></div>
                            </td>
                            <td data-label="Permission">
                                <div class="atp-perm" x-text="a.libelle"></div>
                                <span class="atp-code" x-text="a.permission"></span>
                                <div class="atp-muted" x-text="a.motif"></div>
                            </td>
                            <td data-label="Période">
                                <div class="atp-muted" x-text="'Du ' + a.debut"></div>
                                <div x-text="a.mode === 'definitif' ? 'Sans expiration' : 'au ' + a.fin"></div>
                                <div class="atp-muted" x-show="a.restant" x-text="'Fin ' + a.restant"></div>
                            </td>
                            <td data-label="Statut">
                                <span class="atp-badge" :class="'atp-badge--' + a.statut" x-text="libelleStatut(a.statut)"></span>
                                <div class="atp-muted" x-show="a.retire_par" x-text="'Par ' + a.retire_par"></div>
                            </td>
                            <td data-label="Action">
                                <button type="button" class="atp-btn atp-btn--ghost" x-show="a.statut === 'active' || a.statut === 'a_venir' || a.statut === 'definitif'" x-cloak
                                        :disabled="envoi || (a.mode === 'definitif' && !peutAccorderDefinitif)" x-on:click="preparerRetrait(a)">
                                    <i class="fas fa-ban"></i> Retirer
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="atp-pagination" x-show="pagination.pages > 1" x-cloak>
                <span x-text="pagination.total + ' accès · Page ' + pagination.page + '/' + pagination.pages"></span>
                <button type="button" class="atp-preset" :disabled="pagination.page <= 1 || chargement" x-on:click="changerPage(pagination.page - 1)">Précédent</button>
                <button type="button" class="atp-preset" :disabled="pagination.page >= pagination.pages || chargement" x-on:click="changerPage(pagination.page + 1)">Suivant</button>
            </div>
        </div>
    </div>
    <dialog class="atp-dialog" x-ref="confirmationDialog" x-on:cancel="if (envoi) $event.preventDefault()" x-on:close="fermerConfirmation()" aria-labelledby="atp-dialog-title">
        <div class="atp-dialog-header">
            <h2 id="atp-dialog-title" x-text="confirmation?.action === 'retirer' ? 'Confirmer le retrait' : 'Confirmer l’accès individuel'"></h2>
            <button type="button" class="atp-dialog-close" aria-label="Fermer la confirmation" :disabled="envoi" x-on:click="fermerDialogue()"><i class="fas fa-times"></i></button>
        </div>
        <div class="atp-dialog-body">
            <p class="atp-hint" x-text="confirmation?.action === 'retirer' ? 'Le droit sera retiré immédiatement pour cette personne.' : 'Vérifiez les éléments avant d’accorder ce droit.'"></p>
            <div class="atp-dialog-row"><span>Personne</span><strong x-text="confirmation?.personne"></strong></div>
            <div class="atp-dialog-row"><span>Permission</span><strong x-text="confirmation?.libelle"></strong></div>
            <div class="atp-dialog-row"><span>Durée</span><strong x-text="confirmation?.duree"></strong></div>
            <div class="atp-dialog-row" x-show="confirmation?.motif"><span>Motif</span><strong x-text="confirmation?.motif"></strong></div>
            <p class="atp-warning" x-show="confirmation?.mode === 'definitif' && confirmation?.action !== 'retirer'">Accès définitif : aucune expiration automatique, révocation manuelle nécessaire.</p>
            <p class="atp-alert atp-alert--error" x-show="erreurConfirmation" x-cloak x-text="erreurConfirmation"></p>
        </div>
        <div class="atp-dialog-actions">
            <button type="button" class="atp-btn atp-btn--secondary" :disabled="envoi" x-on:click="fermerDialogue()">Annuler</button>
            <button type="button" class="atp-btn" :class="confirmation?.action === 'retirer' ? 'atp-btn--danger' : 'atp-btn--primary'" :disabled="envoi" x-on:click="validerConfirmation()">
                <i class="fas" :class="envoi ? 'fa-spinner fa-spin' : confirmation?.action === 'retirer' ? 'fa-ban' : 'fa-check'"></i>
                <span x-text="envoi ? 'Traitement…' : confirmation?.action === 'retirer' ? 'Retirer l’accès' : 'Confirmer l’accès'"></span>
            </button>
        </div>
    </dialog>
    </div>
</div>
@endsection

@push('scripts')
<script>
if (typeof window.accesTemporaires !== 'function') {
    window.accesTemporaires = function () {
        return {
            acces: [],
            urls: {},
            statistiques: { actifs: 0, definitifs: 0, a_venir: 0, total: 0 },
            pagination: { page: 1, pages: 1, total: 0 },
            filtre: 'en_cours',
            mode: 'temporaire',
            peutAccorderDefinitif: false,
            fin: '',
            presetActif: null,
            envoi: false,
            chargement: false,
            confirmation: null,
            erreurConfirmation: null,
            feedback: null,
            _minuteur: null,
            _rafraichir: null,
            _requeteListe: 0,
            _focusAvantDialogue: null,
            _corpsAccord: null,
            presets: [
                { jours: 1, label: '1 jour' },
                { jours: 2, label: '2 jours' },
                { jours: 7, label: '7 jours' },
                { jours: 30, label: '30 jours' },
            ],

            init() {
                const config = JSON.parse(this.$root.dataset.config || '{}');
                this.acces = config.acces || [];
                this.urls = config.urls || {};
                this.pagination = config.pagination || this.pagination;
                this.statistiques = config.statistiques || this.statistiques;
                this.peutAccorderDefinitif = !!config.peutAccorderDefinitif;
                const max = config.dureeMaxJours || 90;
                this.presets = this.presets.filter(p => p.jours <= max);
                // Une ecole reglee sur un maximum d'un jour ne doit pas
                // proposer par defaut deux jours que l'API refuserait.
                this.choisirDuree(this.presets.some(p => p.jours === 2) ? 2 : (this.presets[0]?.jours || 1));
                // Un acces arrive a echeance doit quitter « En cours » sans rechargement.
                this._rafraichir = () => {
                    if (!document.hidden && !this.envoi && !this.$refs.confirmationDialog.open) this.recharger();
                };
                this._minuteur = setInterval(this._rafraichir, 60000);
                document.addEventListener('visibilitychange', this._rafraichir);
            },

            destroy() {
                clearInterval(this._minuteur);
                document.removeEventListener('visibilitychange', this._rafraichir);
            },

            async recharger() {
                const requete = ++this._requeteListe;
                this.chargement = true;
                try {
                    const params = new URLSearchParams({ scope: this.filtre, page: String(this.pagination.page) });
                    const res = await fetch(this.urls.data + '?' + params, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        if (requete !== this._requeteListe) return;
                        this.acces = data.acces || [];
                        this.pagination = data.pagination || this.pagination;
                        this.statistiques = data.statistiques || this.statistiques;
                    } else if (requete === this._requeteListe) {
                        this.feedback = { type: 'error', message: 'Impossible d’actualiser les accès. Réessayez dans un instant.' };
                    }
                } catch (e) {
                    // La liste affichee reste lisible si le reseau est indisponible.
                } finally {
                    if (requete === this._requeteListe) this.chargement = false;
                }
            },

            changerFiltre(filtre) {
                if (this.filtre === filtre) return;
                this.filtre = filtre;
                this.pagination.page = 1;
                this.recharger();
            },

            changerPage(page) {
                if (page < 1 || page > this.pagination.pages || this.chargement) return;
                this.pagination.page = page;
                this.recharger();
            },

            choisirDuree(jours) {
                const d = new Date(Date.now() + jours * 86400000);
                const pad = n => String(n).padStart(2, '0');
                this.fin = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
                    + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                this.presetActif = jours;
            },

            visibles() {
                return this.acces;
            },

            libelleStatut(statut) {
                return { active: 'En cours', definitif: 'Définitif', a_venir: 'À venir', expiree: 'Expiré', retiree: 'Retiré' }[statut] || statut;
            },

            jeton() {
                const meta = document.querySelector('meta[name="csrf-token"]');
                return meta ? meta.content : '';
            },

            async envoyer(url, method, body) {
                const res = await fetch(url, {
                    method: method,
                    headers: { 'X-CSRF-TOKEN': this.jeton(), 'Accept': 'application/json' },
                    body: body,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    const erreurs = data.errors ? Object.values(data.errors).flat() : [];
                    throw new Error(erreurs[0] || data.message || ('Erreur ' + res.status));
                }
                return data;
            },

            preparerAccord() {
                if (this.envoi || this.confirmation) return;
                this.feedback = null;
                const donnees = new FormData(this.$refs.form);
                if (!donnees.get('user_id') || !donnees.get('permission')) {
                    this.feedback = { type: 'error', message: 'Choisissez une personne et une permission.' };
                    return;
                }
                if (this.mode === 'definitif' && !this.peutAccorderDefinitif) {
                    this.feedback = { type: 'error', message: 'Vous n’êtes pas autorisé à accorder un accès définitif.' };
                    return;
                }
                if (!this.$refs.form.reportValidity()) return;

                // Le payload confirme reste celui que la personne a effectivement verifie.
                this._corpsAccord = donnees;

                this.ouvrirDialogue({
                    action: 'accorder',
                    mode: this.mode,
                    personne: this.$refs.form.querySelector('.au-up-trigger-name')?.textContent?.trim() || 'Personne #' + donnees.get('user_id'),
                    libelle: this.$refs.form.querySelector('.au-select-value')?.textContent?.trim() || String(donnees.get('permission')),
                    duree: this.mode === 'definitif' ? 'Sans expiration' : 'Jusqu’au ' + this.fin.replace('T', ' '),
                    motif: String(donnees.get('motif') || '').trim(),
                });
            },

            preparerRetrait(a) {
                if (this.envoi || this.confirmation || (a.mode === 'definitif' && !this.peutAccorderDefinitif)) return;
                this.ouvrirDialogue({
                    action: 'retirer', id: a.id, mode: a.mode,
                    personne: a.personne, libelle: a.libelle,
                    duree: a.mode === 'definitif' ? 'Sans expiration' : 'Jusqu’au ' + a.fin,
                    motif: a.motif,
                });
            },

            ouvrirDialogue(details) {
                this.confirmation = details;
                this.erreurConfirmation = null;
                this._focusAvantDialogue = document.activeElement;
                this.$refs.confirmationDialog.showModal();
            },

            fermerDialogue() {
                if (!this.envoi && this.$refs.confirmationDialog.open) this.$refs.confirmationDialog.close();
            },

            fermerConfirmation() {
                this.confirmation = null;
                this.erreurConfirmation = null;
                this._corpsAccord = null;
                if (this._focusAvantDialogue && this._focusAvantDialogue.isConnected) this._focusAvantDialogue.focus();
                this._focusAvantDialogue = null;
            },

            notifier(type, message) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
            },

            async validerConfirmation() {
                if (this.envoi || !this.confirmation) return;
                const operation = this.confirmation;
                this.envoi = true;
                this.erreurConfirmation = null;
                let reussi = false;
                try {
                    let data;
                    if (operation.action === 'retirer') {
                        const corps = new FormData();
                        corps.append('_method', 'DELETE');
                        data = await this.envoyer(this.urls.destroy + '/' + operation.id, 'POST', corps);
                    } else {
                        data = await this.envoyer(this.urls.store, 'POST', this._corpsAccord);
                        this.$refs.form.querySelector('textarea[name="motif"]').value = '';
                        this.filtre = 'en_cours';
                        this.pagination.page = 1;
                    }
                    this.feedback = { type: 'success', message: data.message };
                    this.notifier('success', data.message);
                    await this.recharger();
                    reussi = true;
                } catch (e) {
                    this.erreurConfirmation = e.message;
                    this.feedback = { type: 'error', message: e.message };
                    this.notifier('error', e.message);
                } finally {
                    this.envoi = false;
                    if (reussi) this.fermerDialogue();
                }
            },
        };
    };
}
</script>
@endpush
