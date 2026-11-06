{{-- Les listes se rechargent dès qu'un choix change ; la recherche attend la fin de la frappe. --}}
<form class="sfc-filters" @submit.prevent="refresh(1)" @change="surChangement($event)" aria-label="Filtrer les classes">
    <div class="sfc-filters-main">
        <div class="sfc-field">
            <label>Année universitaire</label>
            <x-au-select name="annee" x-model="filters.annee" icon="fa-calendar" :options="$sfOptions['annee']" :value="$filtres['annee'] ?? ''" :placeholder-is-first-option="false" :searchable="count($sfOptions['annee']) > 8" />
        </div>
        <div class="sfc-field">
            <label for="sfc-search">Rechercher une classe</label>
            <div class="sfc-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input id="sfc-search" type="search" x-model="filters.search" @input="rechercheDifferee()" placeholder="Nom de la classe…" maxlength="100" autocomplete="off">
            </div>
        </div>
        <div class="sfc-field">
            <label>Trier par</label>
            <x-au-select name="tri" x-model="filters.tri" icon="fa-arrow-down-wide-short" :options="$sfOptions['tri']" :value="$filtres['tri'] ?? \App\Domain\Comptabilite\SuiviClasses\SituationClassesService::TRI_DEFAUT" :placeholder-is-first-option="false" />
        </div>
        <div class="sfc-filters-actions">
            <button type="button" class="sfc-btn" @click="plusOuvert = !plusOuvert" :aria-expanded="plusOuvert.toString()" aria-controls="sfc-filtres-plus">
                <i class="fas fa-sliders"></i> Filtres
                <span class="sfc-more-count" x-show="nbFiltresAvances() > 0" x-text="nbFiltresAvances()" x-cloak></span>
            </button>
        </div>
    </div>

    <div class="sfc-filters-more" id="sfc-filtres-plus" x-show="plusOuvert" x-cloak x-transition.opacity>
        <div class="sfc-field">
            <label>Filière / mention</label>
            <x-au-select name="filiere" x-model="filters.filiere" icon="fa-layer-group" :options="$sfOptions['filiere']" :value="$filtres['filiere'] ?? ''" placeholder="Toutes" :searchable="true" />
        </div>
        <div class="sfc-field">
            <label>Niveau</label>
            <x-au-select name="niveau" x-model="filters.niveau" icon="fa-graduation-cap" :options="$sfOptions['niveau']" :value="$filtres['niveau'] ?? ''" placeholder="Tous" :searchable="true" />
        </div>
        <div class="sfc-field">
            <label>Classe</label>
            <x-au-select name="classe" x-model="filters.classe" icon="fa-users" :options="$sfOptions['classe']" :value="$filtres['classe'] ?? ''" placeholder="Toutes" :searchable="true" />
        </div>
        <div class="sfc-field">
            <label>Système</label>
            <x-au-select name="systeme" x-model="filters.systeme" icon="fa-building-columns" :options="$sfOptions['systeme']" :value="$filtres['systeme'] ?? ''" placeholder="BTS et LMD" />
        </div>
        <div class="sfc-field">
            <label>Catégorie de frais</label>
            <x-au-select name="categorie" x-model="filters.categorie" icon="fa-tags" :options="$sfOptions['categorie']" :value="$filtres['categorie'] ?? ''" placeholder="Toutes" :searchable="true" />
        </div>
    </div>

    <div class="sfc-chips" x-show="puces().length > 0" x-cloak aria-label="Filtres actifs">
        <template x-for="puce in puces()" :key="puce.cle">
            <span class="sfc-chip">
                <span x-text="puce.texte"></span>
                <button type="button" @click="retirer(puce.cle)" :aria-label="'Retirer le filtre ' + puce.texte"><i class="fas fa-times"></i></button>
            </span>
        </template>
        <button type="button" class="sfc-chips-clear" @click="reset()">Tout effacer</button>
    </div>
</form>
