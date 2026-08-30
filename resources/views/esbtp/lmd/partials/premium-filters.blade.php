@php
    $filters = $filters ?? request()->only(['annee_universitaire_id', 'classe_id', 'parcours_id', 'semestre', 'q']);
    $showSearch = $showSearch ?? false;
    $showParcours = $showParcours ?? true;
    $showClasse = $showClasse ?? true;
    $showSemestre = $showSemestre ?? true;
    $anneeLabel = fn ($a) => $a->name ?? $a->libelle ?? $a->display_name ?? $a->id;
@endphp
@once
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">
<style>
.lmd-filters{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.25rem;margin-bottom:1.25rem;}
.lmd-filters-grid{display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;}
.lmd-field{min-width:180px;flex:1;}
.lmd-field--search{min-width:220px;flex:1.4;}
.lmd-label{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#64748b;font-weight:700;margin-bottom:.35rem;}
.lmd-filters .select2-container{width:100% !important;}
.lmd-filters .select2-container--bootstrap-5 .select2-selection{
    border-radius:10px;border:1.5px solid #e2e8f0;padding:.45rem .75rem;min-height:42px;background:#f8fafc;
}
.lmd-filters .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered{
    line-height:1.6;color:#1e293b;font-weight:600;padding-right:2.2rem;
}
.lmd-filters .select2-container--bootstrap-5.select2-container--focus .select2-selection,
.lmd-filters .select2-container--bootstrap-5.select2-container--open .select2-selection{
    border-color:#0453cb;box-shadow:0 0 0 3px rgba(4,83,203,.1);background:#fff;
}
.select2-container--bootstrap-5 .select2-dropdown{
    border-radius:12px;border:1px solid #e2e8f0;box-shadow:0 12px 40px rgba(0,0,0,.14);padding:6px;
}
.select2-container--bootstrap-5 .select2-results__option{border-radius:8px;padding:.5rem .75rem;font-weight:500;font-size:.86rem;}
.select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected]{
    background:linear-gradient(135deg,#0453cb,#5e91de) !important;color:#fff;
}
.lmd-search{width:100%;border:1.5px solid #e2e8f0;background:#f8fafc;border-radius:10px;padding:.55rem .85rem;font-size:.88rem;min-height:42px;}
.lmd-search:focus{outline:none;border-color:#0453cb;box-shadow:0 0 0 3px rgba(4,83,203,.1);background:#fff;}
.lmd-filters-reset{display:inline-flex;align-items:center;gap:.35rem;padding:.55rem .85rem;border-radius:10px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:.82rem;font-weight:600;text-decoration:none;min-height:42px;}
.lmd-filters-reset:hover{border-color:#0453cb;color:#0453cb;}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/fr.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !jQuery.fn.select2) return;
    jQuery('.lmd-js-select').select2({
        theme: 'bootstrap-5',
        language: 'fr',
        width: '100%',
        allowClear: true
    }).on('select2:select select2:clear', function () {
        this.form.submit();
    });
});
</script>
@endpush
@endonce

<form method="GET" action="{{ $action }}" class="lmd-filters">
    <div class="lmd-filters-grid">
        <div class="lmd-field">
            <label class="lmd-label" for="lmd-annee">Année</label>
            <select id="lmd-annee" name="annee_universitaire_id" class="lmd-js-select" data-placeholder="Toutes les années">
                <option value=""></option>
                @foreach($annees as $a)
                    <option value="{{ $a->id }}" @selected($annee && (int) $a->id === (int) $annee->id)>{{ $anneeLabel($a) }}</option>
                @endforeach
            </select>
        </div>
        @if($showParcours)
        <div class="lmd-field">
            <label class="lmd-label" for="lmd-parcours">Parcours</label>
            <select id="lmd-parcours" name="parcours_id" class="lmd-js-select" data-placeholder="Tous les parcours">
                <option value=""></option>
                @foreach($parcours as $p)
                    <option value="{{ $p->id }}" @selected((string) ($filters['parcours_id'] ?? '') === (string) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($showClasse)
        <div class="lmd-field">
            <label class="lmd-label" for="lmd-classe">Classe</label>
            <select id="lmd-classe" name="classe_id" class="lmd-js-select" data-placeholder="Toutes les classes">
                <option value=""></option>
                @foreach($classes as $c)
                    <option value="{{ $c->id }}" @selected((string) ($filters['classe_id'] ?? '') === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        @if($showSemestre)
        <div class="lmd-field">
            <label class="lmd-label" for="lmd-semestre">Semestre</label>
            <select id="lmd-semestre" name="semestre" class="lmd-js-select" data-placeholder="Tous les semestres">
                <option value=""></option>
                @for($s = 1; $s <= 6; $s++)
                    <option value="{{ $s }}" @selected((string) ($filters['semestre'] ?? '') === (string) $s)>S{{ $s }}</option>
                @endfor
            </select>
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
