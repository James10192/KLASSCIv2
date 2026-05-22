@extends('layouts.app')

@section('title', 'Examens planifiés')

@push('styles')
<style>
[x-cloak] { display: none !important; }

.exp-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
}
.exp-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
.exp-hero-left { display:flex; align-items:center; gap:1rem; }
.exp-hero-icon { width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.12);
    backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.15);display:flex;align-items:center;
    justify-content:center;font-size:1.35rem;color:#fff;flex-shrink:0;}
.exp-hero h1 { font-size:1.45rem;font-weight:700;color:#fff;margin:0; }
.exp-hero p { color:rgba(255,255,255,.7);font-size:.88rem;margin:0; }
.exp-hero-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
.exp-btn { padding:.5rem 1rem;border-radius:10px;font-size:.82rem;font-weight:600;border:1px solid;
    cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;transition:all .15s; }
.exp-btn--glass { background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2); }
.exp-btn--glass:hover { background:rgba(255,255,255,.25); color:#fff;}
.exp-btn--white { background:#fff;color:#0453cb;border-color:transparent; }
.exp-btn--white:hover { background:#f1f5ff;color:#033a8e;}

.exp-kpis { display:flex; gap:.75rem; margin-top:1.5rem; flex-wrap:wrap; }
.exp-kpi { flex:1;min-width:160px;background:rgba(255,255,255,.1);
    border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:.9rem 1rem;
    display:flex;align-items:center;gap:.75rem; }
.exp-kpi-icon { width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,.15);
    display:flex;align-items:center;justify-content:center;font-size:1rem;color:#fff;}
.exp-kpi-value { font-size:1.35rem;font-weight:700;color:#fff;line-height:1; }
.exp-kpi-label { font-size:.72rem;color:rgba(255,255,255,.65);margin-top:.2rem;text-transform:uppercase;letter-spacing:.5px;}

.exp-filters { background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1rem 1.25rem;
    display:flex;gap:.6rem;flex-wrap:wrap;align-items:center; margin-bottom:1.25rem;
    box-shadow:0 1px 3px rgba(15,23,42,.04); }
.exp-filter { display:flex;align-items:center;gap:.4rem;background:#f8fafc;border:1px solid #e2e8f0;
    border-radius:8px;padding:.4rem .65rem;font-size:.82rem; }
.exp-filter input, .exp-filter select { border:none;background:transparent;outline:none;font-size:.82rem;color:#1e293b; }
.exp-filter label { color:#64748b; font-size:.7rem; text-transform:uppercase; letter-spacing:.5px; }

.exp-card { background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;
    box-shadow:0 1px 3px rgba(15,23,42,.04); }
.exp-table { width:100%; border-collapse:separate; border-spacing:0; font-size:.85rem; }
.exp-table th { background:#f8fafc;color:#475569;font-weight:600;font-size:.7rem;text-transform:uppercase;
    letter-spacing:.5px; padding:.7rem .9rem; text-align:left; border-bottom:1px solid #e2e8f0; }
.exp-table td { padding:.85rem .9rem; border-bottom:1px solid #f1f5f9; vertical-align:middle; color:#1e293b;}
.exp-table tbody tr:hover { background:#f8fafc; }

.exp-chip { display:inline-flex;align-items:center;gap:.3rem;padding:.2rem .55rem;border-radius:6px;
    font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.3px;}
.exp-chip--examen { background:rgba(4,83,203,.10);color:#0453cb;border:1px solid rgba(4,83,203,.25);}
.exp-chip--partiel { background:rgba(59,125,219,.10);color:#3b7ddb;border:1px solid rgba(59,125,219,.25);}
.exp-chip--rattrapage { background:rgba(245,158,11,.10);color:#b45309;border:1px solid rgba(245,158,11,.25);}
.exp-chip--soutenance { background:rgba(16,185,129,.10);color:#047857;border:1px solid rgba(16,185,129,.25);}

.exp-status { display:inline-flex;align-items:center;gap:.3rem;padding:.18rem .5rem;border-radius:5px;
    font-size:.68rem;font-weight:700;}
.exp-status--draft { background:rgba(100,116,139,.10);color:#475569;}
.exp-status--planned { background:rgba(4,83,203,.10);color:#0453cb;}
.exp-status--in_progress { background:rgba(245,158,11,.10);color:#b45309;}
.exp-status--completed { background:rgba(16,185,129,.10);color:#047857;}
.exp-status--notes_locked { background:rgba(220,38,38,.10);color:#b91c1c;}
.exp-status--cancelled { background:rgba(100,116,139,.10);color:#475569;text-decoration:line-through;}

.exp-empty { padding:3rem 1.5rem;text-align:center;color:#64748b; }
.exp-empty i { font-size:2.5rem;color:#cbd5e1;margin-bottom:1rem; }
.exp-empty h3 { font-size:1.05rem;color:#1e293b;margin:0 0 .5rem; }
.exp-empty p { font-size:.85rem;margin:0; }

@media (max-width: 768px) {
    .exp-hero { padding:1.25rem 1rem; }
    .exp-hero-top { flex-direction:column; align-items:flex-start; }
    .exp-kpi { min-width:48%; }
}
</style>
@endpush

@section('content')
<div x-data="examensIndex()" x-init="init()">

    {{-- Hero --}}
    <div class="exp-hero">
        <div class="exp-hero-top">
            <div class="exp-hero-left">
                <div class="exp-hero-icon"><i class="fas fa-pen-ruler"></i></div>
                <div>
                    <h1>Examens planifiés</h1>
                    <p>Année universitaire <strong>{{ $annee->libelle ?? '—' }}</strong> · workflow UEMOA</p>
                </div>
            </div>
            <div class="exp-hero-actions">
                @can('lmd.examens.manage')
                <a href="{{ route('esbtp.examens.create', ['annee_universitaire_id' => $annee->id]) }}" class="exp-btn exp-btn--white">
                    <i class="fas fa-plus"></i> Nouvel examen
                </a>
                @endcan
                <a href="{{ route('esbtp.examens.convocations.preview', request()->query()) }}" target="_blank" class="exp-btn exp-btn--glass">
                    <i class="fas fa-file-pdf"></i> Convocations PDF
                </a>
            </div>
        </div>

        <div class="exp-kpis">
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-clipboard-list"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.total">{{ $kpis['total'] }}</div>
                <div class="exp-kpi-label">Total examens</div></div></div>
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-calendar-day"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.a_venir">{{ $kpis['a_venir'] }}</div>
                <div class="exp-kpi-label">À venir</div></div></div>
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-spinner"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.en_cours">{{ $kpis['en_cours'] }}</div>
                <div class="exp-kpi-label">En cours</div></div></div>
            <div class="exp-kpi"><div class="exp-kpi-icon"><i class="fas fa-lock"></i></div>
                <div><div class="exp-kpi-value" x-text="kpis.notes_lockees">{{ $kpis['notes_lockees'] }}</div>
                <div class="exp-kpi-label">Notes verrouillées</div></div></div>
        </div>
    </div>

    {{-- Filtres --}}
    <form method="GET" action="{{ route('esbtp.examens.index') }}" class="exp-filters">
        <div class="exp-filter">
            <label>Année</label>
            <select name="annee_universitaire_id" onchange="this.form.submit()">
                @foreach($annees as $a)
                <option value="{{ $a->id }}" @selected($a->id == $annee->id)>{{ $a->libelle }}{{ $a->is_current ? ' (en cours)' : '' }}</option>
                @endforeach
            </select>
        </div>
        <div class="exp-filter">
            <label>Classe</label>
            <select name="classe_id" onchange="this.form.submit()">
                <option value="">Toutes</option>
                @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected(request('classe_id') == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="exp-filter">
            <label>Type</label>
            <select name="type" onchange="this.form.submit()">
                <option value="">Tous</option>
                @foreach(['EXAMEN', 'PARTIEL', 'RATTRAPAGE', 'SOUTENANCE'] as $t)
                <option value="{{ $t }}" @selected(request('type') == $t)>{{ ucfirst(strtolower($t)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="exp-filter">
            <label>Statut</label>
            <select name="status" onchange="this.form.submit()">
                <option value="">Tous</option>
                @foreach(['draft', 'planned', 'in_progress', 'completed', 'notes_locked', 'cancelled'] as $s)
                <option value="{{ $s }}" @selected(request('status') == $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="exp-filter">
            <label>Du</label>
            <input type="date" name="from" value="{{ request('from') }}" onchange="this.form.submit()">
        </div>
        <div class="exp-filter">
            <label>Au</label>
            <input type="date" name="to" value="{{ request('to') }}" onchange="this.form.submit()">
        </div>
        @if(request()->hasAny(['classe_id', 'type', 'status', 'from', 'to']))
        <a href="{{ route('esbtp.examens.index', ['annee_universitaire_id' => $annee->id]) }}" class="exp-btn exp-btn--glass" style="background:#f1f5f9;color:#475569;border-color:#e2e8f0;">
            <i class="fas fa-xmark"></i> Réinitialiser
        </a>
        @endif
    </form>

    {{-- Table --}}
    <div class="exp-card">
        @if($examens->isEmpty())
            <div class="exp-empty">
                <i class="fas fa-pen-ruler"></i>
                <h3>Aucun examen planifié</h3>
                <p>Créez un examen ou ajustez vos filtres pour en voir.</p>
            </div>
        @else
            <table class="exp-table">
                <thead>
                    <tr>
                        <th>Convocation</th>
                        <th>Date / Heure</th>
                        <th>Classe · Matière</th>
                        <th>Type</th>
                        <th>Salle</th>
                        <th>Coef × Bareme</th>
                        <th>Statut</th>
                        <th style="width:1%;"></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($examens as $e)
                    <tr>
                        <td style="font-family:'Courier New',monospace;font-size:.78rem;color:#0453cb;font-weight:700;">
                            {{ $e->numero_convocation ?? '—' }}
                        </td>
                        <td>
                            <div style="font-weight:600;">{{ optional($e->date_debut)->format('d/m/Y') }}</div>
                            <div style="color:#64748b;font-size:.75rem;">
                                {{ optional($e->date_debut)->format('H:i') }}–{{ optional($e->date_fin)->format('H:i') }}
                                @if($e->duree_minutes) · {{ $e->duree_minutes }}min @endif
                            </div>
                        </td>
                        <td>
                            <div style="font-weight:600;">{{ $e->classe->name ?? '—' }}</div>
                            <div style="color:#64748b;font-size:.75rem;">{{ $e->matiere->name ?? '—' }}</div>
                        </td>
                        <td><span class="exp-chip exp-chip--{{ strtolower($e->type_examen) }}">{{ $e->type_examen }}</span></td>
                        <td>{{ $e->salle ?? '—' }}</td>
                        <td style="color:#64748b;font-size:.78rem;">coef {{ rtrim(rtrim(number_format($e->coefficient, 2, '.', ''), '0'), '.') }} × /{{ (int) $e->bareme }}</td>
                        <td>
                            <span class="exp-status exp-status--{{ $e->status }}">
                                @if($e->notes_locked) <i class="fas fa-lock"></i> @endif
                                {{ ucfirst(str_replace('_', ' ', $e->status)) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('esbtp.examens.show', $e) }}" class="exp-btn exp-btn--glass" style="background:#f1f5f9;color:#0453cb;border-color:#e2e8f0;padding:.3rem .7rem;font-size:.78rem;">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if($examens->hasPages())
            <div style="padding:1rem 1.25rem;border-top:1px solid #e2e8f0;">
                {{ $examens->links() }}
            </div>
            @endif
        @endif
    </div>

</div>

@push('scripts')
<script>
function examensIndex() {
    return {
        kpis: @json($kpis),
        init() {
            window.addEventListener('examens:updated', () => this.refreshKpis());
        },
        async refreshKpis() {
            try {
                const url = new URL('{{ route('esbtp.examens.kpis') }}', window.location.origin);
                url.searchParams.set('annee_universitaire_id', {{ $annee->id }});
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                if (res.ok) this.kpis = await res.json();
            } catch (e) {
                console.warn('KPIs refresh failed', e);
            }
        }
    };
}
</script>
@endpush

@endsection
