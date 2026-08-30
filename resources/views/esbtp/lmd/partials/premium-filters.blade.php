@php
    $filters = $filters ?? request()->only(['annee_universitaire_id', 'classe_id', 'parcours_id', 'semestre', 'q']);
    $showSearch = $showSearch ?? false;
    $showParcours = $showParcours ?? true;
    $showClasse = $showClasse ?? true;
    $showSemestre = $showSemestre ?? true;
    $anneeOptions = collect($annees)->mapWithKeys(function ($a) {
        $label = trim((string) ($a->display_name ?? $a->name ?? $a->libelle ?? ''));
        if ($label === '' || preg_match('/^année\s*#?\d+$/iu', $label)) {
            $label = $a->name ?: ($a->libelle ?: '');
        }
        return [$a->id => $label !== '' ? $label : (string) $a->id];
    })->all();
    $parcoursOptions = collect($parcours ?? [])->mapWithKeys(fn ($p) => [$p->id => $p->name])->all();
    $classeOptions = collect($classes ?? [])->mapWithKeys(fn ($c) => [$c->id => $c->name])->all();
    $semestreOptions = collect(range(1, 6))->mapWithKeys(fn ($s) => [$s => 'Semestre '.$s])->all();
@endphp

<form method="GET" action="{{ $action }}" class="lmd-filters">
    <div class="lmd-filters-grid">
        <div class="lmd-field">
            <label class="lmd-label">Année</label>
            <x-au-select
                name="annee_universitaire_id"
                :value="$annee?->id"
                placeholder="Toutes les années"
                icon="fa-calendar-alt"
                :searchable="count($anneeOptions) > 8"
                :options="$anneeOptions"
                onchange="this.form.submit()" />
        </div>
        @if($showParcours)
        <div class="lmd-field">
            <label class="lmd-label">Parcours</label>
            <x-au-select
                name="parcours_id"
                :value="$filters['parcours_id'] ?? ''"
                placeholder="Tous les parcours"
                icon="fa-route"
                :searchable="count($parcoursOptions) > 8"
                :options="$parcoursOptions"
                onchange="this.form.submit()" />
        </div>
        @endif
        @if($showClasse)
        <div class="lmd-field">
            <label class="lmd-label">Classe</label>
            <x-au-select
                name="classe_id"
                :value="$filters['classe_id'] ?? ''"
                placeholder="Toutes les classes"
                icon="fa-chalkboard"
                :searchable="count($classeOptions) > 8"
                :options="$classeOptions"
                onchange="this.form.submit()" />
        </div>
        @endif
        @if($showSemestre)
        <div class="lmd-field">
            <label class="lmd-label">Semestre</label>
            <x-au-select
                name="semestre"
                :value="$filters['semestre'] ?? ''"
                placeholder="Tous les semestres"
                icon="fa-layer-group"
                :options="$semestreOptions"
                onchange="this.form.submit()" />
        </div>
        @endif
        @if($showSearch)
        <div class="lmd-field lmd-field--search">
            <label class="lmd-label" for="lmd-q">Recherche</label>
            <input id="lmd-q" class="lmd-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, prénom, matricule…" onkeydown="if(event.key==='Enter'){this.form.submit();}">
        </div>
        @endif
        <a class="lmd-filters-reset" href="{{ $action }}"><i class="fas fa-rotate-left"></i> Réinitialiser</a>
    </div>
</form>

@once
@push('styles')
<style>
.lmd-filters{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.25rem;margin-bottom:1.25rem;position:relative;z-index:2;}
.lmd-filters-grid{display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;}
.lmd-field{min-width:200px;flex:1;position:relative;z-index:1;}
.lmd-field:focus-within{z-index:5;}
.lmd-field--search{min-width:220px;flex:1.4;}
.lmd-label{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;font-weight:700;margin-bottom:.35rem;}
.lmd-search{width:100%;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:10px;padding:.55rem .85rem;font-size:.88rem;min-height:42px;}
.lmd-search:focus{outline:none;border-color:#0453cb;box-shadow:0 0 0 3px rgba(4,83,203,.1);background:#fff;}
.lmd-filters-reset{display:inline-flex;align-items:center;gap:.35rem;padding:.55rem .85rem;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:.82rem;font-weight:600;text-decoration:none;min-height:42px;}
.lmd-filters-reset:hover{border-color:#0453cb;color:#0453cb;}
.lmd-filters .au-select{width:100%;}
</style>
@endpush
@endonce
