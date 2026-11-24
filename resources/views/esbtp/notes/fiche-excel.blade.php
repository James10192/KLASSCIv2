@extends('layouts.app')

@section('title', 'Fiche de notes Excel - KLASSCI')

@php
    $_annee = (int) ($classe->niveauEtude?->year ?: 1);
    $_semestres = $lmd
        ? ['semestre'.(2 * $_annee - 1) => 'Semestre '.(2 * $_annee - 1), 'semestre'.(2 * $_annee) => 'Semestre '.(2 * $_annee)]
        : ['semestre1' => 'Semestre 1', 'semestre2' => 'Semestre 2'];
    $_premier = array_key_first($_semestres);
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
    .fne-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; }
    .fne-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); }
    .fne-card h2 { font-size: 1.05rem; font-weight: 700; color: #0f172a; display: flex; gap: .6rem; align-items: center; margin: 0 0 .35rem; }
    .fne-step { width: 28px; height: 28px; border-radius: 8px; background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff; font-size: .82rem; display: inline-flex; align-items: center; justify-content: center; }
    .fne-hint { color: #64748b; font-size: .85rem; margin-bottom: 1rem; }
    .fne-field { display: flex; flex-direction: column; gap: .35rem; margin-bottom: .9rem; }
    .fne-field label { font-size: .78rem; font-weight: 600; color: #475569; }
    .fne-btn { display: inline-flex; align-items: center; gap: .5rem; min-height: 44px; padding: .55rem 1.1rem; border-radius: 10px; font-weight: 600; font-size: .88rem; border: 1px solid #0453cb; background: #0453cb; color: #fff; cursor: pointer; transition: all .2s ease; text-decoration: none; }
    .fne-btn:hover { background: #033a8e; color: #fff; }
    .fne-btn--ghost { background: #fff; color: #0453cb; }
    .fne-btn--ghost:hover { background: rgba(4,83,203,.06); color: #0453cb; }
    .fne-btn:disabled { opacity: .55; cursor: wait; }
    .fne-drop { border: 2px dashed #c7d4e5; border-radius: 12px; padding: 1.25rem; text-align: center; color: #64748b; margin-bottom: 1rem; }
    .fne-sum { display: flex; flex-wrap: wrap; gap: .5rem; margin: 1rem 0; }
    .fne-chip { padding: .3rem .7rem; border-radius: 999px; font-size: .8rem; font-weight: 600; background: rgba(4,83,203,.08); color: #0453cb; }
    .fne-chip--ko { background: rgba(220,38,38,.08); color: #dc2626; }
    .fne-table { width: 100%; border-collapse: collapse; font-size: .82rem; }
    .fne-table th, .fne-table td { border-bottom: 1px solid #e2e8f0; padding: .45rem .5rem; text-align: left; vertical-align: top; }
    .fne-table th { color: #64748b; font-weight: 600; font-size: .74rem; text-transform: uppercase; }
    .fne-err { color: #dc2626; }
    .fne-empty { color: #64748b; font-size: .85rem; padding: .75rem 0; }
    @@media (max-width: 768px) { .fne-hero { padding: 1.5rem 1.25rem; } }
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="ficheNotesExcel()">
    <div class="fne-hero">
        <div class="fne-hero-left">
            <div class="fne-hero-icon"><i class="fas fa-file-excel"></i></div>
            <div>
                <h1>Fiche de notes Excel · {{ $classe->name }}</h1>
                <p>{{ $lmd ? 'LMD : une feuille par élément (ECUE) du semestre.' : 'BTS : une feuille par matière du bulletin.' }} Remplissez hors ligne, puis réimportez.</p>
            </div>
        </div>
    </div>

    <div class="fne-grid">
        <div class="fne-card">
            <h2><span class="fne-step">1</span>Télécharger la fiche</h2>
            <p class="fne-hint">Les notes déjà saisies sont reprises. Chaque matière a au moins le nombre de colonnes choisi : une colonne ajoutée et remplie devient une évaluation au réimport. Le classeur porte le logo et les couleurs de vos réglages PDF.</p>
            <div class="fne-field">
                <label for="fne-annee">Année universitaire</label>
                <x-au-select id="fne-annee" :options="$annees" :value="$courante" :placeholder-is-first-option="false" icon="fa-calendar" x-model="annee" />
            </div>
            <div class="fne-field">
                <label for="fne-periode">Semestre</label>
                <x-au-select id="fne-periode" :options="$_semestres" :value="$_premier" :placeholder-is-first-option="false" icon="fa-clock" x-model="periode" />
            </div>
            <div class="fne-field">
                <label for="fne-orientation">Orientation du papier</label>
                <x-au-select id="fne-orientation" :options="$_orientations" value="portrait" :placeholder-is-first-option="false" icon="fa-file" x-model="orientation" />
            </div>
            <div class="fne-field">
                <label for="fne-colonnes">Colonnes de notes par matière (minimum)</label>
                <x-au-select id="fne-colonnes" :options="$_colonnes" value="1" :placeholder-is-first-option="false" icon="fa-table-columns" x-model="colonnes" />
                <span class="fne-hint" style="margin:0" x-show="Number(colonnes) > limites[orientation]" x-text="'Le format A4 ' + (orientation === 'portrait' ? 'portrait' : 'paysage') + ' tient ' + limites[orientation] + ' colonnes au plus : la fiche en aura ' + limites[orientation] + '.'"></span>
            </div>
            <a class="fne-btn" :href="lienTelechargement()"><i class="fas fa-download"></i>Télécharger la fiche Excel</a>
        </div>

        <div class="fne-card">
            <h2><span class="fne-step">2</span>Réimporter la fiche remplie</h2>
            <p class="fne-hint">KLASSCI montre d'abord ce qui va changer. Rien n'est enregistré avant votre confirmation.</p>
            <div class="fne-drop">
                <i class="fas fa-file-arrow-up"></i> Fiche Excel remplie (.xlsx)
                <div><input type="file" accept=".xlsx,.xls" x-ref="fichier" x-on:change="resultat = null"></div>
            </div>
            <button type="button" class="fne-btn fne-btn--ghost" :disabled="enCours" x-on:click="envoyer(false)">
                <i class="fas fa-magnifying-glass"></i><span x-text="enCours ? 'Lecture…' : 'Vérifier le fichier'"></span>
            </button>

            <template x-if="resultat">
                <div>
                    <div class="fne-sum">
                        <span class="fne-chip" x-text="resultat.total.will_create + ' note(s) à créer'"></span>
                        <span class="fne-chip" x-text="resultat.total.will_update + ' note(s) modifiée(s)'"></span>
                        <span class="fne-chip" :class="resultat.total.errors ? 'fne-chip--ko' : ''" x-text="resultat.total.errors + ' erreur(s)'"></span>
                    </div>
                    <table class="fne-table">
                        <thead><tr><th>Feuille</th><th>Créées</th><th>Modifiées</th><th>Détail</th></tr></thead>
                        <tbody>
                            <template x-for="f in resultat.feuilles" :key="f.feuille">
                                <tr>
                                    <td x-text="f.feuille"></td>
                                    <td x-text="f.summary.will_create"></td>
                                    <td x-text="f.summary.will_update"></td>
                                    <td>
                                        <template x-for="e in f.errors"><div class="fne-err" x-text="'Ligne ' + e.row + ' ' + e.col + ' : ' + e.reason"></div></template>
                                        <template x-if="f.evaluations_a_creer.length"><div x-text="'(f.evaluations_a_creer.length > 1 ? 'Évaluations créées : ' : 'Évaluation créée : ') + f.evaluations_a_creer.join(', ')"></div></template>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                    <p class="fne-empty" x-show="!resultat.total.will_create && !resultat.total.will_update && !resultat.total.errors">Aucune note à enregistrer dans ce fichier.</p>
                    <div style="margin-top:1rem" x-show="!resultat.total.errors && (resultat.total.will_create || resultat.total.will_update)">
                        <button type="button" class="fne-btn" :disabled="enCours" x-on:click="envoyer(true)"><i class="fas fa-check"></i>Enregistrer ces notes</button>
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
            orientation: 'portrait',
            colonnes: '1',
            limites: @json($limites),
            resultat: null,
            enCours: false,
            lienTelechargement() {
                return urls.telecharger + '?annee_universitaire_id=' + encodeURIComponent(this.annee) + '&periode=' + encodeURIComponent(this.periode)
                    + '&orientation=' + encodeURIComponent(this.orientation)
                    + '&colonnes=' + Math.min(Number(this.colonnes) || 1, this.limites[this.orientation]);
            },
            toast(type, message) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
            },
            async envoyer(ecrire) {
                const fichier = this.$refs.fichier.files[0];
                if (!fichier) { this.toast('warning', "Choisissez d'abord la fiche Excel remplie."); return; }
                const corps = new FormData();
                corps.append('file', fichier);
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
                        this.resultat = null;
                        this.$refs.fichier.value = '';
                    } else {
                        this.resultat = data.data;
                        if (ecrire) this.toast('error', "Des erreurs empêchent l'enregistrement : rien n'a été écrit.");
                    }
                } catch (e) {
                    this.toast('error', e.message);
                } finally {
                    this.enCours = false;
                }
            },
        };
    };
}
</script>
@endsection
