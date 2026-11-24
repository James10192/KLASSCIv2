@extends('layouts.app')

@section('title', 'Fiche de notes Excel - KLASSCI')

@php
    $_premier = array_key_first($semestres);
    $_orientations = ['portrait' => 'A4 portrait (jusqu\'à '.$limites['portrait'].' colonnes)', 'landscape' => 'A4 paysage (jusqu\'à '.$limites['landscape'].' colonnes)'];
    $_colonnes = [];
    for ($_i = 1; $_i <= max($limites); $_i++) { $_colonnes[(string) $_i] = $_i === 1 ? '1 colonne' : $_i.' colonnes'; }
    $_urls = [
        'telecharger' => route('esbtp.classes.fiche-notes-excel.telecharger', $classe),
        'simuler' => route('esbtp.classes.fiche-notes-excel.simuler', $classe),
        'appliquer' => route('esbtp.classes.fiche-notes-excel.appliquer', $classe),
    ];
@endphp

@push('styles')
<style>
    .fne-hero { background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%); border-radius: 18px; padding: 2rem 2.5rem 1.5rem; color: #fff; margin-bottom: 1.25rem; }
    .fne-hero-left { display: flex; align-items: center; gap: 1rem; }
    .fne-hero-icon { width: 52px; height: 52px; border-radius: 14px; background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.15); display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
    .fne-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .fne-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: 0; }
    .fne-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 1.25rem; align-items: start; }
    .fne-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .fne-card h2 { font-size: 1.05rem; font-weight: 700; color: #0f172a; display: flex; gap: .6rem; align-items: center; margin: 0 0 .35rem; }
    .fne-step { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; font-size: .82rem; display: inline-flex; align-items: center; justify-content: center; }
    .fne-hint { color: #64748b; font-size: .85rem; margin-bottom: 1rem; }
    .fne-row { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
    .fne-field { display: flex; flex-direction: column; gap: .35rem; margin-bottom: .9rem; min-width: 0; }
    .fne-field label { font-size: .78rem; font-weight: 600; color: #475569; }
    .fne-full { display: flex !important; width: 100%; }
    .fne-full .au-select-trigger { width: 100%; }
    .fne-note { font-size: .78rem; color: #64748b; }
    .fne-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 44px; padding: .55rem 1.1rem; border-radius: 10px; font-weight: 600; font-size: .88rem; border: 1px solid #0453cb; background: #0453cb; color: #fff; cursor: pointer; transition: all .2s ease; text-decoration: none; }
    .fne-btn:hover { background: #033a8e; color: #fff; }
    .fne-btn--ghost { background: #fff; color: #0453cb; }
    .fne-btn--ghost:hover { background: rgba(4,83,203,.06); color: #0453cb; }
    .fne-btn:disabled { opacity: .55; cursor: not-allowed; }
    .fne-depot { border: 2px dashed #c7d4e5; border-radius: 12px; padding: 1.5rem 1rem; text-align: center; color: #64748b; font-size: .88rem; cursor: pointer; transition: all .2s ease; margin-bottom: 1rem; }
    .fne-depot:hover, .fne-depot.is-survol { border-color: #0453cb; background: rgba(4,83,203,.04); color: #0453cb; }
    .fne-depot i { font-size: 1.5rem; display: block; margin-bottom: .4rem; color: #0453cb; }
    .fne-depot strong { color: #0f172a; }
    .fne-bilan { display: flex; flex-wrap: wrap; gap: .5rem; margin: .25rem 0 1rem; }
    .fne-chip { padding: .3rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 600; background: rgba(4,83,203,.08); color: #0453cb; }
    .fne-chip--ok { background: rgba(16,185,129,.1); color: #047857; }
    .fne-chip--ko { background: rgba(220,38,38,.08); color: #dc2626; }
    .fne-feuilles { display: flex; flex-direction: column; gap: .6rem; margin-bottom: 1rem; }
    .fne-feuille { border: 1px solid #e2e8f0; border-radius: 12px; padding: .75rem .9rem; }
    .fne-feuille--ko { border-color: rgba(220,38,38,.35); background: rgba(220,38,38,.02); }
    .fne-feuille-tete { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
    .fne-feuille-nom { font-weight: 700; color: #0f172a; font-size: .9rem; display: flex; align-items: center; gap: .45rem; min-width: 0; }
    .fne-feuille-nom span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fne-feuille-compte { font-size: .8rem; color: #475569; white-space: nowrap; }
    .fne-nouvelle { display: inline-flex; align-items: center; gap: .3rem; margin-top: .45rem; font-size: .78rem; font-weight: 600; color: #0453cb; background: #eff6ff; border-radius: 6px; padding: .2rem .5rem; }
    .fne-erreur { margin-top: .4rem; font-size: .8rem; color: #dc2626; }
    .fne-inchangees { font-size: .82rem; color: #64748b; margin-bottom: 1rem; }
    .fne-actions { display: flex; flex-wrap: wrap; gap: .5rem; }
    @@media (max-width: 768px) { .fne-hero { padding: 1.5rem 1.25rem; } .fne-row { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="ficheNotesExcel()">
    <div class="fne-hero">
        <div class="fne-hero-left">
            <div class="fne-hero-icon"><i class="fas fa-file-excel"></i></div>
            <div>
                <h1>Fiche de notes Excel · {{ $classe->name }}</h1>
                <p>{{ $lmd ? 'LMD : une feuille par élément (ECUE).' : 'BTS : une feuille par matière.' }} Téléchargez toutes les matières ou une seule, remplissez hors ligne, puis réimportez.</p>
            </div>
        </div>
    </div>

    <div class="fne-grid">
        <div class="fne-card">
            <h2><span class="fne-step">1</span>Télécharger la fiche</h2>
            <p class="fne-hint">Les notes déjà saisies sont reprises. Une colonne « Évaluation » ajoutée et remplie devient une évaluation au réimport.</p>
            <div class="fne-row">
                <div class="fne-field">
                    <label for="fne-annee">Année universitaire</label>
                    <x-au-select id="fne-annee" class="fne-full" :options="$annees" :value="$courante" :placeholder-is-first-option="false" icon="fa-calendar" x-model="annee" />
                </div>
                <div class="fne-field">
                    <label for="fne-periode">Semestre</label>
                    <x-au-select id="fne-periode" class="fne-full" :options="$semestres" :value="$_premier" :placeholder-is-first-option="false" icon="fa-clock" x-model="periode" />
                </div>
            </div>
            @foreach ($matieresParSemestre as $_periode => $_matieres)
                <div class="fne-field" x-show="periode === @js($_periode)" @if($_periode !== $_premier) x-cloak @endif>
                    <label for="fne-matiere-{{ $_periode }}">{{ $lmd ? 'Élément constitutif (ECUE)' : 'Matière' }}</label>
                    <x-au-select id="fne-matiere-{{ $_periode }}" class="fne-full" :options="$_matieres" value=""
                        :placeholder="'Toutes les '.($lmd ? 'ECUE' : 'matières').' ('.count($_matieres).')'" :searchable="count($_matieres) > 8" icon="fa-book"
                        x-on:change="matiere = $event.target.value" />
                </div>
            @endforeach
            <div class="fne-row">
                <div class="fne-field">
                    <label for="fne-orientation">Orientation du papier</label>
                    <x-au-select id="fne-orientation" class="fne-full" :options="$_orientations" value="portrait" :placeholder-is-first-option="false" icon="fa-file" x-model="orientation" />
                </div>
                <div class="fne-field">
                    <label for="fne-colonnes">Colonnes de notes (minimum)</label>
                    <x-au-select id="fne-colonnes" class="fne-full" :options="$_colonnes" value="1" :placeholder-is-first-option="false" icon="fa-table-columns" x-model="colonnes" />
                </div>
            </div>
            <p class="fne-note" x-show="Number(colonnes) > limites[orientation]" x-cloak x-text="'Le format A4 ' + (orientation === 'portrait' ? 'portrait' : 'paysage') + ' tient ' + limites[orientation] + ' colonnes au plus : la fiche en aura ' + limites[orientation] + '.'"></p>
            <a class="fne-btn" :href="lienTelechargement()"><i class="fas fa-download"></i><span x-text="matiere ? 'Télécharger la fiche de cette matière' : 'Télécharger la fiche complète'"></span></a>
        </div>

        <div class="fne-card">
            <h2><span class="fne-step">2</span>Réimporter la fiche remplie</h2>
            <p class="fne-hint">KLASSCI vérifie d'abord le fichier et montre ce qui va changer. Rien n'est enregistré avant votre confirmation, et un fichier avec une erreur n'enregistre rien.</p>

            <input type="file" accept=".xlsx,.xls" x-ref="fichier" hidden x-on:change="choisir($event.target.files[0])">
            <div class="fne-depot" role="button" tabindex="0" :class="survol ? 'is-survol' : ''"
                 x-on:click="$refs.fichier.click()" x-on:keydown.enter.prevent="$refs.fichier.click()"
                 x-on:dragover.prevent="survol = true" x-on:dragleave="survol = false" x-on:drop.prevent="survol = false; choisir($event.dataTransfer.files[0])">
                <i class="fas" :class="enCours ? 'fa-spinner fa-spin' : 'fa-file-arrow-up'"></i>
                <template x-if="!nomFichier"><span>Choisissez ou déposez la fiche remplie (.xlsx)</span></template>
                <template x-if="nomFichier"><span><strong x-text="nomFichier"></strong><br><span x-text="enCours ? 'Lecture en cours…' : 'Cliquez pour choisir un autre fichier'"></span></span></template>
            </div>

            <template x-if="resultat">
                <div>
                    <div class="fne-bilan">
                        <span class="fne-chip" x-text="resultat.total.will_create + ' note(s) à créer'"></span>
                        <span class="fne-chip" x-text="resultat.total.will_update + ' note(s) à modifier'"></span>
                        <span class="fne-chip" :class="resultat.total.errors ? 'fne-chip--ko' : 'fne-chip--ok'" x-text="resultat.total.errors ? resultat.total.errors + ' erreur(s) à corriger' : 'Aucune erreur'"></span>
                    </div>

                    <div class="fne-feuilles">
                        <template x-for="f in feuillesUtiles()" :key="f.feuille">
                            <div class="fne-feuille" :class="f.errors.length ? 'fne-feuille--ko' : ''">
                                <div class="fne-feuille-tete">
                                    <div class="fne-feuille-nom"><i class="fas fa-table" style="color:#0453cb"></i><span x-text="f.matiere && f.matiere !== f.feuille ? f.feuille + ' · ' + f.matiere : f.feuille"></span></div>
                                    <div class="fne-feuille-compte" x-text="resumeFeuille(f)"></div>
                                </div>
                                <template x-for="titre in f.evaluations_a_creer" :key="titre">
                                    <span class="fne-nouvelle" style="margin-right:.35rem"><i class="fas fa-plus"></i><span x-text="'Nouvelle évaluation : ' + titre"></span></span>
                                </template>
                                <template x-for="e in f.errors.slice(0, 5)">
                                    <div class="fne-erreur" x-text="'Ligne ' + e.row + (e.col && e.col !== '-' ? ', colonne ' + e.col : '') + ' : ' + e.reason"></div>
                                </template>
                                <div class="fne-erreur" x-show="f.errors.length > 5" x-text="'… et ' + (f.errors.length - 5) + ' autre(s) erreur(s) sur cette feuille.'"></div>
                            </div>
                        </template>
                    </div>
                    <p class="fne-inchangees" x-show="feuillesInchangees() > 0" x-text="feuillesInchangees() + ' feuille(s) sans changement.'"></p>
                    <p class="fne-inchangees" x-show="!resultat.total.will_create && !resultat.total.will_update && !resultat.total.errors">Aucune note à enregistrer dans ce fichier.</p>

                    <div class="fne-actions">
                        <button type="button" class="fne-btn" :disabled="enCours" x-show="!resultat.total.errors && (resultat.total.will_create || resultat.total.will_update)" x-on:click="envoyer(true)">
                            <i class="fas fa-check"></i><span x-text="enCours ? 'Enregistrement…' : 'Enregistrer ces notes'"></span>
                        </button>
                        <button type="button" class="fne-btn fne-btn--ghost" :disabled="enCours" x-on:click="reinitialiser()"><i class="fas fa-rotate-left"></i>Choisir un autre fichier</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
if (typeof window.ficheNotesExcel !== 'function') {
    window.ficheNotesExcel = function () {
        const urls = @json($_urls);
        return {
            annee: @json((string) $courante),
            periode: @json($_premier),
            matiere: '',
            orientation: 'portrait',
            colonnes: '1',
            limites: @json($limites),
            resultat: null,
            fichier: null,
            nomFichier: '',
            survol: false,
            enCours: false,
            init() {
                // Une matière n'appartient qu'à son semestre : changer de semestre repart sur toutes.
                this.$watch('periode', () => {
                    this.matiere = '';
                    this.$root.querySelectorAll('select[id^="fne-matiere-"]').forEach(function (s) {
                        if (s.value) { s.value = ''; s.dispatchEvent(new Event('change', { bubbles: true })); }
                    });
                });
            },
            lienTelechargement() {
                return urls.telecharger + '?annee_universitaire_id=' + encodeURIComponent(this.annee)
                    + '&periode=' + encodeURIComponent(this.periode)
                    + (this.matiere ? '&matiere_id=' + encodeURIComponent(this.matiere) : '')
                    + '&orientation=' + encodeURIComponent(this.orientation)
                    + '&colonnes=' + Math.min(Number(this.colonnes) || 1, this.limites[this.orientation]);
            },
            feuillesUtiles() {
                return (this.resultat?.feuilles || []).filter(f => f.errors.length || f.summary.will_create || f.summary.will_update);
            },
            feuillesInchangees() {
                return (this.resultat?.feuilles || []).length - this.feuillesUtiles().length;
            },
            resumeFeuille(f) {
                if (f.errors.length) return f.errors.length + ' erreur(s)';
                const parts = [];
                if (f.summary.will_create) parts.push(f.summary.will_create + ' à créer');
                if (f.summary.will_update) parts.push(f.summary.will_update + ' à modifier');
                return parts.join(' · ');
            },
            toast(type, message) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
            },
            reinitialiser() {
                this.resultat = null;
                this.fichier = null;
                this.nomFichier = '';
                this.$refs.fichier.value = '';
            },
            choisir(fichier) {
                if (!fichier || this.enCours) return;
                if (!/\.(xlsx|xls)$/i.test(fichier.name)) { this.toast('error', 'Choisissez un fichier Excel (.xlsx).'); return; }
                this.fichier = fichier;
                this.nomFichier = fichier.name;
                this.resultat = null;
                this.envoyer(false);
            },
            async envoyer(ecrire) {
                if (!this.fichier) { this.toast('warning', "Choisissez d'abord la fiche Excel remplie."); return; }
                const corps = new FormData();
                corps.append('file', this.fichier);
                this.enCours = true;
                try {
                    const res = await fetch(ecrire ? urls.appliquer : urls.simuler, {
                        method: 'POST', body: corps,
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    });
                    const data = await res.json().catch(function () { return {}; });
                    if (!res.ok || !data.success) throw new Error(data.message || "Le fichier n'a pas pu être traité.");
                    if (ecrire && data.data.applique) {
                        this.toast('success', 'Notes enregistrées. Pensez à régénérer les bulletins.');
                        this.reinitialiser();
                    } else {
                        this.resultat = data.data;
                        if (ecrire) this.toast('error', "Des erreurs empêchent l'enregistrement : rien n'a été écrit.");
                    }
                } catch (e) {
                    this.toast('error', e.message);
                    if (!ecrire) this.reinitialiser();
                } finally {
                    this.enCours = false;
                }
            },
        };
    };
}
</script>
@endsection
