@extends('layouts.app')

@section('title', 'Accès temporaires - KLASSCI')

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
    .atp-badge--a_venir { background: rgba(4,83,203,.1); color: #0453cb; }
    .atp-badge--expiree { background: #f1f5f9; color: #64748b; }
    .atp-badge--retiree { background: rgba(220,38,38,.08); color: #b91c1c; }
    .atp-empty { text-align: center; color: #64748b; padding: 2rem 1rem; }
    .atp-empty i { font-size: 1.6rem; color: #94a3b8; display: block; margin-bottom: .5rem; }

    @media (max-width: 992px) { .atp-grid { grid-template-columns: 1fr; } }
    @media (max-width: 768px) {
        .atp-hero { padding: 1.5rem 1.25rem 1.25rem; }
        .atp-table thead { display: none; }
        .atp-table tr { display: block; border-bottom: 1px solid #e2e8f0; padding: .5rem 0; }
        .atp-table td { display: block; border: none; padding: .2rem .4rem; }
    }
</style>
@endpush

@section('content')
@php
    $_atpConfig = [
        'acces' => $acces,
        'urls' => [
            'store' => route('esbtp.acces-temporaires.store'),
            'data' => route('esbtp.acces-temporaires.data'),
            'destroy' => url('esbtp/acces-temporaires'),
        ],
        'dureeMaxJours' => $dureeMaxJours,
    ];
@endphp
<div class="dashboard-acasi" x-data="accesTemporaires()" data-config='@json($_atpConfig)'>
    <div class="main-content" style="padding: 1.25rem; max-width: 100%;">
    <div class="atp-hero">
        <div class="atp-hero-top">
            <div class="atp-hero-left">
                <div class="atp-hero-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <h1>Accès temporaires</h1>
                    <p>Ouvrez une permission précise à une personne jusqu'à une date. L'accès tombe de lui-même à l'échéance, sans toucher à son rôle.</p>
                </div>
            </div>
        </div>
        <div class="atp-kpis">
            <div class="atp-kpi"><div class="atp-kpi-value" x-text="compte('active')">0</div><div class="atp-kpi-label">Accès en cours</div></div>
            <div class="atp-kpi"><div class="atp-kpi-value" x-text="compte('a_venir')">0</div><div class="atp-kpi-label">À venir</div></div>
            <div class="atp-kpi"><div class="atp-kpi-value" x-text="acces.length">0</div><div class="atp-kpi-label">Dans l'historique</div></div>
        </div>
    </div>

    <div class="atp-grid">
        <div class="atp-card">
            <div class="atp-section-header">
                <div class="atp-section-icon"><i class="fas fa-key"></i></div>
                <div>
                    <h2>Accorder un accès</h2>
                    <small>Durée maximale sur cette instance : {{ $dureeMaxJours }} jours</small>
                </div>
            </div>

            <template x-if="feedback">
                <div class="atp-alert" :class="feedback.type === 'success' ? 'atp-alert--success' : 'atp-alert--error'">
                    <i class="fas" :class="feedback.type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'"></i>
                    <span x-text="feedback.message"></span>
                </div>
            </template>

            <form x-ref="form" x-on:submit.prevent="accorder()">
                <div class="atp-field">
                    <label>Personne</label>
                    <x-au-user-picker name="user_id" :users="$personnel" :value="$personneChoisie" placeholder="— Choisir une personne —" />
                </div>

                <div class="atp-field">
                    <label>Permission</label>
                    <x-au-select name="permission" :options="$permissions" :searchable="true" icon="fa-key" placeholder="— Choisir la permission —" />
                    <span class="atp-hint">Les permissions d'identité et d'administration générale ne s'accordent pas pour un temps limité.</span>
                </div>

                <div class="atp-field">
                    <label>Durée</label>
                    <div class="atp-presets">
                        <template x-for="p in presets" :key="p.jours">
                            <button type="button" class="atp-preset" :class="presetActif === p.jours ? 'atp-preset--active' : ''"
                                    x-on:click="choisirDuree(p.jours)" x-text="p.label"></button>
                        </template>
                    </div>
                </div>

                <div class="atp-field">
                    <label for="atp-fin">Accès ouvert jusqu'au</label>
                    <input id="atp-fin" type="datetime-local" name="fin" class="atp-input" x-model="fin" x-on:input="presetActif = null" required>
                </div>

                <div class="atp-field">
                    <label for="atp-motif">Motif</label>
                    <textarea id="atp-motif" name="motif" rows="3" class="atp-input" minlength="10" required
                              placeholder="Ex. : correction des notes du semestre 2 en l'absence du titulaire"></textarea>
                </div>

                <button type="submit" class="atp-btn atp-btn--primary" :disabled="envoi">
                    <i class="fas" :class="envoi ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="envoi ? 'Enregistrement…' : 'Accorder l\'accès'"></span>
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
                    <button type="button" class="atp-preset" :class="filtre === 'en_cours' ? 'atp-preset--active' : ''" x-on:click="filtre = 'en_cours'">En cours</button>
                    <button type="button" class="atp-preset" :class="filtre === 'tous' ? 'atp-preset--active' : ''" x-on:click="filtre = 'tous'">Tous</button>
                </div>
            </div>

            <template x-if="visibles().length === 0">
                <div class="atp-empty"><i class="fas fa-hourglass-start"></i>Aucun accès temporaire à afficher.</div>
            </template>

            <table class="atp-table" x-show="visibles().length > 0">
                <thead>
                    <tr><th>Personne</th><th>Permission</th><th>Période</th><th>Statut</th><th></th></tr>
                </thead>
                <tbody>
                    <template x-for="a in visibles()" :key="a.id">
                        <tr>
                            <td>
                                <div x-text="a.personne"></div>
                                <div class="atp-muted" x-show="a.accorde_par" x-text="'Accordé par ' + a.accorde_par"></div>
                            </td>
                            <td>
                                <div class="atp-perm" x-text="a.libelle"></div>
                                <span class="atp-code" x-text="a.permission"></span>
                                <div class="atp-muted" x-text="a.motif"></div>
                            </td>
                            <td>
                                <div class="atp-muted" x-text="'Du ' + a.debut"></div>
                                <div x-text="'au ' + a.fin"></div>
                                <div class="atp-muted" x-show="a.restant" x-text="'Fin ' + a.restant"></div>
                            </td>
                            <td>
                                <span class="atp-badge" :class="'atp-badge--' + a.statut" x-text="libelleStatut(a.statut)"></span>
                                <div class="atp-muted" x-show="a.retire_par" x-text="'Par ' + a.retire_par"></div>
                            </td>
                            <td>
                                <button type="button" class="atp-btn atp-btn--ghost" x-show="a.statut === 'active' || a.statut === 'a_venir'"
                                        :disabled="retraitEnCours === a.id" x-on:click="retirer(a)">
                                    <i class="fas fa-ban"></i> Retirer
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
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
            filtre: 'en_cours',
            fin: '',
            presetActif: null,
            envoi: false,
            retraitEnCours: null,
            feedback: null,
            _minuteur: null,
            _rafraichir: null,
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
                const max = config.dureeMaxJours || 90;
                this.presets = this.presets.filter(p => p.jours <= max);
                this.choisirDuree(2);
                // Un acces arrive a echeance doit quitter « En cours » sans rechargement.
                this._rafraichir = () => { if (!document.hidden) this.recharger(); };
                this._minuteur = setInterval(this._rafraichir, 60000);
                document.addEventListener('visibilitychange', this._rafraichir);
            },

            destroy() {
                clearInterval(this._minuteur);
                document.removeEventListener('visibilitychange', this._rafraichir);
            },

            async recharger() {
                try {
                    const res = await fetch(this.urls.data, { headers: { 'Accept': 'application/json' } });
                    if (res.ok) {
                        const data = await res.json();
                        this.acces = data.acces || this.acces;
                    }
                } catch (e) {
                    // Hors ligne un instant : la liste affichee reste, le prochain tour la remettra a jour.
                }
            },

            choisirDuree(jours) {
                const d = new Date(Date.now() + jours * 86400000);
                const pad = n => String(n).padStart(2, '0');
                this.fin = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
                    + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
                this.presetActif = jours;
            },

            compte(statut) {
                return this.acces.filter(a => a.statut === statut).length;
            },

            visibles() {
                if (this.filtre === 'tous') return this.acces;
                return this.acces.filter(a => a.statut === 'active' || a.statut === 'a_venir');
            },

            libelleStatut(statut) {
                return { active: 'En cours', a_venir: 'À venir', expiree: 'Expiré', retiree: 'Retiré' }[statut] || statut;
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

            async accorder() {
                this.envoi = true;
                this.feedback = null;
                try {
                    const donnees = new FormData(this.$refs.form);
                    const data = await this.envoyer(this.urls.store, 'POST', donnees);
                    this.acces = data.acces || this.acces;
                    this.filtre = 'en_cours';
                    this.feedback = { type: 'success', message: data.message };
                    this.$refs.form.querySelector('textarea[name="motif"]').value = '';
                } catch (e) {
                    this.feedback = { type: 'error', message: e.message };
                } finally {
                    this.envoi = false;
                }
            },

            async retirer(a) {
                if (!window.confirm('Retirer cet accès à ' + a.personne + ' dès maintenant ?')) return;
                this.retraitEnCours = a.id;
                try {
                    const corps = new FormData();
                    corps.append('_method', 'DELETE');
                    const data = await this.envoyer(this.urls.destroy + '/' + a.id, 'POST', corps);
                    this.acces = data.acces || this.acces;
                    this.feedback = { type: 'success', message: data.message };
                } catch (e) {
                    this.feedback = { type: 'error', message: e.message };
                } finally {
                    this.retraitEnCours = null;
                }
            },
        };
    };
}
</script>
@endpush
