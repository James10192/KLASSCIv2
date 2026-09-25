@extends('layouts.app')
@section('title', 'Activité du personnel')

{{--
    Activité du personnel (namespace ap-*).

    Des faits prévus / réalisés, jamais une note : séances tenues sur séances
    prévues, notes reçues sur notes attendues, paiements saisis, validés et
    restés en attente. Le constat arrive en AJAX (`data`) ; chaque ligne mène
    au détail de la personne, preuves à l'appui.
--}}

@push('styles')
    @include('esbtp.personnel.performance.partials._styles')
@endpush

@section('content')
@php
    $_apConfig = ['url' => route('esbtp.personnel.performance.data'), 'periode' => $periode];
@endphp
<div class="container-fluid ap-shell" x-data="apActivite(@js($_apConfig))" x-init="charger(false)">
    <header class="ap-hero">
        <div class="ap-hero-top">
            <div class="ap-hero-left">
                <div class="ap-hero-icon"><i class="fas fa-people-group"></i></div>
                <div>
                    <h1>Activité du personnel</h1>
                    <p>Ce qui était prévu et ce qui a été fait, personne par personne<span x-show="libelle" x-cloak> · <strong x-text="libelle"></strong></span></p>
                </div>
            </div>
            <div class="ap-hero-actions">
                <a href="{{ route('esbtp.personnel.unified.index') }}" class="ap-btn ap-btn--glass"><i class="fas fa-users"></i>Personnel</a>
            </div>
        </div>
        <div class="ap-hero-kpis" x-ref="kpis">
            @for($i = 0; $i < 4; $i++)
                <div class="ap-kpi ap-kpi--attente"></div>
            @endfor
        </div>
    </header>

    <section class="ap-filtres" aria-label="Filtres">
        <x-au-select name="periode" icon="fa-calendar" :options="$periodes" :value="$periode"
                     :placeholder-is-first-option="false" x-model="periode" @change="charger()" />
        <label class="ap-recherche">
            <i class="fas fa-magnifying-glass"></i>
            <input type="search" placeholder="Rechercher une personne ou un rôle" x-model.debounce.150ms="recherche" @input="$dispatch('ap:recherche', recherche)">
        </label>
        <span class="ap-etat" x-show="chargement" x-cloak><i class="fas fa-circle-notch fa-spin"></i>Calcul…</span>
    </section>

    <div x-ref="corps" class="ap-corps" :class="{ 'ap-corps--charge': chargement }">
        <div class="ap-card ap-vide"><i class="fas fa-circle-notch fa-spin"></i><span>Lecture des séances, notes et paiements…</span></div>
    </div>

    <template x-if="erreur">
        <div class="ap-card ap-vide"><i class="fas fa-plug-circle-xmark"></i><span x-text="erreur"></span>
            <button type="button" class="ap-btn ap-btn--ghost ap-btn--compact" @click="charger()">Réessayer</button></div>
    </template>
</div>
@endsection

@push('scripts')
<script>
if (typeof window.apActivite !== 'function') {
    window.apActivite = function (config) {
        return {
            periode: config.periode,
            recherche: '',
            libelle: '',
            chargement: false,
            erreur: '',
            _requete: 0,

            async charger(historique = true) {
                const numero = ++this._requete;
                this.chargement = true;
                this.erreur = '';
                try {
                    const reponse = await fetch(config.url + '?periode=' + encodeURIComponent(this.periode), { headers: { Accept: 'application/json' } });
                    if (!reponse.ok) throw new Error('L’activité n’a pas pu être calculée.');
                    const donnees = await reponse.json();
                    if (numero !== this._requete) return;
                    const gabarit = document.createElement('template');
                    gabarit.innerHTML = donnees.html || '';
                    const kpis = gabarit.content.querySelector('[data-ap-kpis]');
                    const corps = gabarit.content.querySelector('[data-ap-corps]');
                    const titre = gabarit.content.querySelector('[data-ap-libelle]');
                    this.libelle = titre ? titre.dataset.apLibelle : '';
                    this.$refs.kpis.replaceChildren(...(kpis ? kpis.childNodes : []));
                    this.$refs.corps.replaceChildren(...(corps ? corps.childNodes : []));
                    if (historique) history.replaceState(null, '', '?periode=' + encodeURIComponent(this.periode));
                } catch (e) {
                    if (numero === this._requete) this.erreur = e.message;
                } finally {
                    if (numero === this._requete) this.chargement = false;
                }
            },

            aller(ancre) {
                const cible = document.getElementById(ancre);
                if (cible) cible.scrollIntoView({ behavior: 'smooth', block: 'start' });
            },
        };
    };
}
</script>
@endpush
