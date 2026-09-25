@extends('layouts.app')
@section('title', 'Pilotage académique')

{{--
    Tableau de bord pédagogique (namespace pa-*).

    Une seule question : quelles classes n'ont pas toutes leurs notes, qui
    relancer, qui ne vient plus en cours. Le constat arrive en AJAX
    (`pilotage-academique.apercu`) parce qu'il lit la couverture de chaque
    classe ; la page elle-même ne calcule rien.

    Le détail d'une classe est le bandeau « Notes reçues » partagé avec la
    saisie et les bulletins : même calcul, même cache, même chiffre.
--}}

@push('styles')
    @include('esbtp.pilotage-academique.partials._apercu-styles')
@endpush

@section('content')
@php
    $_paConfig = [
        'url' => route('esbtp.pilotage-academique.apercu'),
        'filtres' => $filtres,
    ];
    $_paAnnees = collect($annees)->mapWithKeys(fn ($nom, $id) => [(string) $id => $nom])->all();
    $_paClasses = collect($classes)->mapWithKeys(fn ($nom, $id) => [(string) $id => $nom])->all();
@endphp
<div class="container-fluid pa-shell" x-data="paApercu(@js($_paConfig))" x-init="init()">
    <header class="pa-hero">
        <div class="pa-hero-top">
            <div class="pa-hero-left">
                <div class="pa-hero-icon"><i class="fas fa-chart-line"></i></div>
                <div>
                    <h1>Pilotage académique</h1>
                    <p>Notes manquantes, enseignants à relancer et présence aux cours
                        <span x-show="periodeLabel" x-cloak>· <strong x-text="periodeLabel"></strong></span>
                        @if($annee)<span>· {{ $annee->name }}</span>@endif
                    </p>
                </div>
            </div>
            <div class="pa-hero-actions">
                <a href="{{ route('esbtp.pilotage-academique.fiches') }}#sheets" class="pa-btn pa-btn--glass">
                    <i class="fas fa-clipboard-check"></i>Fiches, alertes et affectations
                </a>
            </div>
        </div>
        <div class="pa-hero-kpis" x-ref="kpis">
            @for($i = 0; $i < 4; $i++)
                <div class="pa-kpi pa-kpi--attente"><span class="pa-squelette"></span></div>
            @endfor
        </div>
    </header>

    <section class="pa-filtres" aria-label="Filtres">
        <x-au-select name="annee" icon="fa-calendar" :options="$_paAnnees" :value="(string) ($filtres['annee'] ?? '')"
                     :placeholder-is-first-option="false" x-model="filtres.annee" @change="changerAnnee()" />
        <x-au-select name="periode" icon="fa-layer-group" :options="$periodes" :value="$filtres['periode']"
                     :placeholder-is-first-option="false" x-model="filtres.periode" @change="charger()" />
        <x-au-select name="systeme" icon="fa-graduation-cap" :options="['BTS' => 'BTS', 'LMD' => 'LMD']" :value="$filtres['systeme']"
                     placeholder="BTS et LMD" x-model="filtres.systeme" @change="charger()" />
        <x-au-select class="pa-filtre-large" name="classe" icon="fa-school" :options="$_paClasses" :value="(string) $filtres['classe']"
                     placeholder="Toutes les classes" :searchable="count($_paClasses) > 8" x-model="filtres.classe" @change="charger()" />
        <span class="pa-etat" x-show="chargement" x-cloak><i class="fas fa-circle-notch fa-spin"></i>Calcul en cours…</span>
    </section>

    <div x-ref="corps" class="pa-corps" :class="{ 'pa-corps--charge': chargement }">
        <div class="pa-card pa-attente">
            <i class="fas fa-circle-notch fa-spin"></i>
            <span>Lecture des notes et des appels…</span>
        </div>
    </div>

    <template x-if="erreur">
        <div class="pa-card pa-erreur">
            <i class="fas fa-plug-circle-xmark"></i>
            <span x-text="erreur"></span>
            <button type="button" class="pa-btn pa-btn--ghost" @click="charger()">Réessayer</button>
        </div>
    </template>

    {{-- Le détail d'une classe : le bandeau de couverture partagé, piloté par
         l'événement couverture:contexte. Il reste invisible tant qu'aucune
         classe n'est choisie. --}}
    <div class="pa-panneau" x-show="classeOuverte" x-cloak @keydown.escape.window="fermerClasse()">
        <div class="pa-panneau-fond" @click="fermerClasse()"></div>
        <aside class="pa-panneau-corps" role="dialog" aria-modal="true" aria-labelledby="pa-panneau-titre">
            <div class="pa-panneau-tete">
                <div>
                    <div class="pa-surtitre">Notes de la classe</div>
                    <h2 id="pa-panneau-titre" x-text="classeOuverte?.nom"></h2>
                </div>
                <button type="button" class="pa-fermer" @click="fermerClasse()" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </div>
            @include('esbtp.partials._couverture-notes', [
                'classeId' => null,
                'anneeId' => $filtres['annee'] ?? null,
                'periode' => $filtres['periode'],
                'replie' => false,
                'titre' => 'Notes reçues',
                'lienPilotage' => false,
            ])
        </aside>
    </div>
</div>
@endsection

<x-chart-engine />

@push('scripts')
<script>
if (typeof window.paApercu !== 'function') {
    window.paApercu = function (config) {
        return {
            filtres: Object.assign({ annee: '', periode: '', systeme: '', classe: '' }, config.filtres || {}),
            chargement: false,
            erreur: '',
            periodeLabel: '',
            classeOuverte: null,
            _requete: 0,

            init() {
                ['annee', 'classe'].forEach((cle) => { this.filtres[cle] = this.filtres[cle] ? String(this.filtres[cle]) : ''; });
                this.charger(false);
            },

            parametres() {
                const p = new URLSearchParams();
                Object.entries(this.filtres).forEach(([cle, valeur]) => { if (valeur) p.set(cle, valeur); });
                return p;
            },

            changerAnnee() {
                // Une autre année n'a pas les mêmes classes : on repart de toutes.
                this.filtres.classe = '';
                window.location.search = this.parametres().toString();
            },

            async charger(historique = true) {
                const numero = ++this._requete;
                this.chargement = true;
                this.erreur = '';
                try {
                    const reponse = await fetch(config.url + '?' + this.parametres().toString(), { headers: { Accept: 'application/json' } });
                    if (!reponse.ok) {
                        const corps = await reponse.json().catch(() => ({}));
                        throw new Error(corps.message || 'Le tableau de bord n’a pas pu être calculé.');
                    }
                    const donnees = await reponse.json();
                    // Une réponse plus ancienne qu'une autre demande déjà partie ne doit rien écraser.
                    if (numero !== this._requete) return;
                    if (donnees.periode) this.filtres.periode = donnees.periode;
                    this.injecter(donnees.html || '');
                    if (historique) history.replaceState(null, '', '?' + this.parametres().toString());
                } catch (e) {
                    if (numero === this._requete) this.erreur = e.message;
                } finally {
                    if (numero === this._requete) this.chargement = false;
                }
            },

            injecter(html) {
                const gabarit = document.createElement('template');
                gabarit.innerHTML = html;
                const kpis = gabarit.content.querySelector('[data-pa-kpis]');
                const corps = gabarit.content.querySelector('[data-pa-corps]');
                const titre = gabarit.content.querySelector('[data-pa-periode]');
                this.periodeLabel = titre ? titre.dataset.paPeriode : '';
                this.$refs.kpis.replaceChildren(...(kpis ? kpis.childNodes : []));
                this.$refs.corps.replaceChildren(...(corps ? corps.childNodes : []));
                this.$nextTick(() => window.dispatchEvent(new CustomEvent('klassci:charts', { detail: { root: this.$refs.corps } })));
            },

            // Un indicateur du bandeau mène à la liste qui le justifie.
            aller(ancre, filtre) {
                if (filtre) window.dispatchEvent(new CustomEvent('pa:filtre-classes', { detail: filtre }));
                const cible = document.getElementById(ancre);
                if (cible) cible.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },

            ouvrirClasse(id, nom) {
                this.classeOuverte = { id, nom };
                window.dispatchEvent(new CustomEvent('couverture:contexte', { detail: {
                    classe_id: id,
                    annee_universitaire_id: this.filtres.annee ? Number(this.filtres.annee) : null,
                    periode: this.filtres.periode,
                } }));
            },

            fermerClasse() {
                this.classeOuverte = null;
            },
        };
    };
}
</script>
@endpush
