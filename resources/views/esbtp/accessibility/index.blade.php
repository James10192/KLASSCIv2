@extends('layouts.app')

@section('title', 'Accessibilité — Suivi étudiants')

@push('styles')
<style>
/* ===================================================================
   ACCESSIBILITY COHORT — KLASSCI Design System
   Namespace: acc-
=================================================================== */
.acc-page { padding: 0; }

.acc-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}
.acc-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.acc-hero-left { display: flex; align-items: center; gap: 1rem; }
.acc-hero-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.acc-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.acc-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }
.acc-hero-right { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }

.acc-btn {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 10px;
    padding: .55rem 1rem;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    transition: all .2s;
}
.acc-btn:hover { background: rgba(255,255,255,.25); color: #fff; }

.acc-kpis {
    display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
}
.acc-kpi {
    flex: 1; min-width: 160px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.acc-kpi-icon {
    width: 38px; height: 38px;
    background: rgba(255,255,255,.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem;
}
.acc-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.acc-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); margin-top: .25rem; }

.acc-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1rem;
}
.acc-card-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .75rem;
}
.acc-card-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; flex: 1; }
.acc-card-body { padding: 1.5rem; }

.acc-filters {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: .75rem;
    align-items: end;
}
.acc-filters .form-label {
    font-size: .78rem; font-weight: 600; color: #64748b;
    margin-bottom: .25rem; text-transform: uppercase; letter-spacing: .03em;
}
.acc-filters .form-select,
.acc-filters .form-control {
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    padding: .55rem .8rem; font-size: .88rem;
}
.acc-filters .form-select:focus,
.acc-filters .form-control:focus {
    border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}

.acc-toggle-mini {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 12px;
    border: 1.5px solid #e2e8f0; border-radius: 10px;
    background: #fff; cursor: pointer; transition: all .2s;
    font-size: .82rem; color: #475569; font-weight: 500;
}
.acc-toggle-mini:hover { border-color: #5e91de; }
.acc-toggle-mini input { accent-color: #0453cb; }
.acc-toggle-mini:has(input:checked) {
    border-color: #0453cb;
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(94,145,222,.04));
    color: #0453cb; font-weight: 600;
}

.acc-table {
    width: 100%; border-collapse: separate; border-spacing: 0;
}
.acc-table th {
    background: #f8fafc; color: #475569;
    text-transform: uppercase; letter-spacing: .03em;
    font-size: .72rem; font-weight: 700;
    padding: .9rem 1rem; text-align: left;
    border-bottom: 2px solid #e2e8f0;
}
.acc-table td {
    padding: .85rem 1rem;
    border-bottom: 1px solid #f1f5f9;
    font-size: .88rem; color: #1e293b; vertical-align: middle;
}
.acc-table tbody tr:hover { background: #fafbfc; }

.acc-student {
    display: flex; align-items: center; gap: .65rem;
}
.acc-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff; font-weight: 700; font-size: .82rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.acc-student-name { font-weight: 600; color: #1e293b; }
.acc-student-mat { font-size: .72rem; color: #64748b; font-family: 'JetBrains Mono', monospace; }

.acc-tag {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 50px;
    font-size: .72rem; font-weight: 600;
    margin: 1px 2px;
}
.acc-tag--cat { background: #eff6ff; color: #0453cb; }
.acc-tag--acc { background: #f0fdf4; color: #065f46; }
.acc-tag--ttp { background: rgba(4,83,203,.1); color: #0453cb; }
.acc-tag--ass { background: rgba(94,145,222,.1); color: #0453cb; }
.acc-tag--rec { background: #fef3c7; color: #78350f; }

.acc-empty {
    text-align: center; padding: 3rem 1rem; color: #64748b;
}
.acc-empty i { font-size: 2.5rem; color: #cbd5e1; margin-bottom: 1rem; }

.acc-applied-filters {
    display: flex; flex-wrap: wrap; gap: .4rem;
    margin-bottom: .75rem; align-items: center;
}
.acc-filter-chip {
    background: rgba(4,83,203,.08); color: #0453cb;
    padding: 4px 12px; border-radius: 50px;
    font-size: .75rem; font-weight: 600;
}

@media (max-width: 768px) {
    .acc-hero { padding: 1.25rem 1.5rem; }
    .acc-hero h1 { font-size: 1.15rem; }
    .acc-table th, .acc-table td { padding: .65rem .5rem; font-size: .8rem; }
}
</style>
@endpush

@section('content')
<div class="acc-page container-fluid">

    {{-- HERO --}}
    <div class="acc-hero">
        <div class="acc-hero-top">
            <div class="acc-hero-left">
                <div class="acc-hero-icon"><i class="fas fa-universal-access"></i></div>
                <div>
                    <h1>Suivi accessibilité étudiants</h1>
                    <p>Cohorte des étudiants en situation de handicap et leurs aménagements pédagogiques.</p>
                </div>
            </div>
            <div class="acc-hero-right">
                <a href="{{ route('esbtp.etudiants.index') }}" class="acc-btn">
                    <i class="fas fa-arrow-left"></i> Étudiants
                </a>
                @can('students.accessibility.export')
                    <x-export-modal
                        :preview-url="route('esbtp.accessibility.preview-pdf')"
                        :pdf-url="route('esbtp.accessibility.export-pdf')"
                        :excel-url="route('esbtp.accessibility.export-excel')"
                        button-class="acc-btn" />
                @endcan
            </div>
        </div>

        <div class="acc-kpis">
            <div class="acc-kpi">
                <div class="acc-kpi-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="acc-kpi-value">{{ $kpis['total'] }}</div>
                    <div class="acc-kpi-label">Étudiants suivis</div>
                </div>
            </div>
            <div class="acc-kpi">
                <div class="acc-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <div class="acc-kpi-value">{{ $kpis['tiers_temps'] }}</div>
                    <div class="acc-kpi-label">Tiers-temps actif</div>
                </div>
            </div>
            <div class="acc-kpi">
                <div class="acc-kpi-icon"><i class="fas fa-hands-helping"></i></div>
                <div>
                    <div class="acc-kpi-value">{{ $kpis['assistant'] }}</div>
                    <div class="acc-kpi-label">Assistant requis</div>
                </div>
            </div>
            <div class="acc-kpi">
                <div class="acc-kpi-icon"><i class="fas fa-stamp"></i></div>
                <div>
                    <div class="acc-kpi-value">{{ $kpis['recognition'] }}</div>
                    <div class="acc-kpi-label">Reconnaissance officielle</div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTERS --}}
    <div class="acc-card">
        <div class="acc-card-header">
            <i class="fas fa-filter" style="color:#0453cb;"></i>
            <h3 class="acc-card-title">Filtres</h3>
        </div>
        <div class="acc-card-body">
            <form method="GET" action="{{ route('esbtp.accessibility.index') }}">
                <div class="acc-filters">
                    <div>
                        <label class="form-label">Catégorie</label>
                        <select name="category" class="form-select">
                            <option value="">Toutes</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Aménagement</label>
                        <select name="accommodation" class="form-select">
                            <option value="">Tous</option>
                            @foreach($accommodations as $key => $label)
                                <option value="{{ $key }}" @selected(request('accommodation') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Classe</label>
                        <select name="classe" class="form-select">
                            <option value="">Toutes</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" @selected(request('classe') == $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Filière</label>
                        <select name="filiere" class="form-select">
                            <option value="">Toutes</option>
                            @foreach($filieres as $f)
                                <option value="{{ $f->id }}" @selected(request('filiere') == $f->id)>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Niveau</label>
                        <select name="niveau" class="form-select">
                            <option value="">Tous</option>
                            @foreach($niveaux as $n)
                                <option value="{{ $n->id }}" @selected(request('niveau') == $n->id)>{{ $n->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-top:1rem;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
                    <label class="acc-toggle-mini">
                        <input type="checkbox" name="third_time_only" value="1" @checked(request()->boolean('third_time_only'))>
                        Tiers-temps seulement
                    </label>
                    <label class="acc-toggle-mini">
                        <input type="checkbox" name="assistant_only" value="1" @checked(request()->boolean('assistant_only'))>
                        Assistant requis
                    </label>
                    <label class="acc-toggle-mini">
                        <input type="checkbox" name="recognition_only" value="1" @checked(request()->boolean('recognition_only'))>
                        Reconnaissance officielle
                    </label>
                    <div style="flex:1;"></div>
                    <button type="submit" class="btn btn-primary" style="border-radius:10px;">
                        <i class="fas fa-search me-1"></i> Filtrer
                    </button>
                    @if(! empty($appliedFilters))
                        <a href="{{ route('esbtp.accessibility.index') }}" class="btn btn-outline-secondary" style="border-radius:10px;">
                            <i class="fas fa-times me-1"></i> Réinitialiser
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- APPLIED FILTERS --}}
    @if(! empty($appliedFilters))
        <div class="acc-applied-filters">
            <span style="font-size:.78rem;color:#64748b;font-weight:600;">Filtres :</span>
            @foreach($appliedFilters as $label => $value)
                <span class="acc-filter-chip">{{ $label }} : {{ $value }}</span>
            @endforeach
        </div>
    @endif

    {{-- TABLE --}}
    <div class="acc-card">
        <div class="acc-card-header">
            <i class="fas fa-list" style="color:#0453cb;"></i>
            <h3 class="acc-card-title">{{ $rows->count() }} étudiant{{ $rows->count() > 1 ? 's' : '' }}</h3>
        </div>

        @if($rows->isEmpty())
            <div class="acc-empty">
                <i class="fas fa-universal-access"></i>
                <p>Aucun étudiant ne correspond aux filtres sélectionnés.</p>
                <p style="font-size:.82rem;">Pour ajouter un profil, allez sur la fiche d'un étudiant et utilisez la section « Accessibilité &amp; aménagements ».</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="acc-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th>Catégories</th>
                            <th>Aménagements</th>
                            <th>Tiers-temps</th>
                            <th>Reconnaissance</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rows as $row)
                        @php $e = $row['etudiant']; $p = $row['profile']; $i = $row['inscription']; @endphp
                        <tr>
                            <td>
                                <div class="acc-student">
                                    <div class="acc-avatar">{{ strtoupper(substr($e->prenoms ?? '', 0, 1)) }}{{ strtoupper(substr($e->nom ?? '', 0, 1)) }}</div>
                                    <div>
                                        <div class="acc-student-name">{{ $e->nom_complet }}</div>
                                        <div class="acc-student-mat">{{ $e->matricule }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div>{{ $i?->classe?->name ?? '—' }}</div>
                                <div style="font-size:.72rem;color:#64748b;">{{ $i?->filiere?->name ?? '' }}</div>
                            </td>
                            <td>
                                @foreach($p->categoryLabels() as $catLabel)
                                    <span class="acc-tag acc-tag--cat">{{ $catLabel }}</span>
                                @endforeach
                                @if(empty($p->categoryLabels()))<span style="color:#94a3b8;">—</span>@endif
                            </td>
                            <td>
                                @foreach($p->accommodationLabels() as $accLabel)
                                    <span class="acc-tag acc-tag--acc">{{ $accLabel }}</span>
                                @endforeach
                                @if(empty($p->accommodationLabels()))<span style="color:#94a3b8;">—</span>@endif
                            </td>
                            <td>
                                @if($p->requires_third_time)
                                    <span class="acc-tag acc-tag--ttp">{{ $p->third_time_percentage }}%</span>
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                                @if($p->assistant_required)
                                    <span class="acc-tag acc-tag--ass">Assistant</span>
                                @endif
                            </td>
                            <td>
                                @if($p->has_official_recognition)
                                    <span class="acc-tag acc-tag--rec"><i class="fas fa-stamp me-1"></i>Officielle</span>
                                @else
                                    <span style="color:#94a3b8;">Non</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('esbtp.etudiants.show', $e) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;" title="Voir la fiche">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
