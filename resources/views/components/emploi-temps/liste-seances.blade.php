@props(['seances' => collect(), 'emploiTemps', 'classe' => null])

@php
    // Detection LMD : si classe LMD on affiche chips type_seance + filtre
    $isClasseLmd = $classe ? (($classe->systeme_academique ?? '') === 'LMD') : false;

    // Labels pour le filtre dropdown (UEMOA)
    $typeSeanceFilterOptions = [
        ''       => 'Tous les types',
        'CM'     => 'CM — Cours Magistral',
        'TD'     => 'TD — Travaux Dirigés',
        'TP'     => 'TP — Travaux Pratiques',
        'PROJET' => 'Projet',
        'TPE'    => 'TPE — Travail Personnel',
        'EXAMEN' => 'Examen',
        'AUTRE'  => 'Autre',
    ];
@endphp

@once
<style>
    .els-table thead th {
        background: #f8fafc;
        color: #64748b;
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .4px;
        padding: .7rem .9rem;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
    }
    .els-table tbody td {
        padding: .8rem .9rem;
        border-bottom: 1px solid #f1f5f9;
        font-size: .86rem;
        color: #1e293b;
        vertical-align: middle;
    }
    .els-table tbody tr:hover { background: rgba(4,83,203,.025); }
    .els-type-badge {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .28rem .6rem;
        border-radius: 8px;
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .3px;
    }
    .els-type-badge--course {
        background: rgba(4,83,203,.1);
        color: #033a8e;
    }
    .els-type-badge--homework {
        background: rgba(59,125,219,.12);
        color: #0453cb;
        border: 1px dashed rgba(4,83,203,.35);
    }
    .els-type-badge--break {
        background: rgba(94,145,222,.15);
        color: #0453cb;
        opacity: .75;
    }
    .els-type-badge--lunch {
        background: rgba(148,163,184,.18);
        color: #475569;
    }
    .els-matiere-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .75rem;
        flex-shrink: 0;
    }
    .els-status-active {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .55rem;
        border-radius: 7px;
        font-size: .7rem;
        font-weight: 600;
        background: rgba(16,185,129,.1);
        color: #065f46;
    }
    .els-status-inactive {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .25rem .55rem;
        border-radius: 7px;
        font-size: .7rem;
        font-weight: 600;
        background: rgba(148,163,184,.15);
        color: #475569;
    }
    .els-repartition {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.25rem;
        margin-top: 1rem;
    }
    .els-repartition-item {
        text-align: center;
        padding: .5rem;
    }
    .els-repartition-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0453cb;
        line-height: 1;
    }
    .els-repartition-label {
        font-size: .72rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-top: .35rem;
    }
    .els-repartition-pct {
        font-size: .7rem;
        color: #94a3b8;
        margin-top: .15rem;
    }
    .els-wrap {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
        overflow: visible;
    }

    /* ===== Chips type_seance UEMOA (monochrome bleu, 3 tones) ===== */
    .ets-chip {
        display: inline-flex;
        align-items: center;
        padding: .22rem .55rem;
        border-radius: 6px;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .3px;
        white-space: nowrap;
    }
    .ets-chip--primary {
        background: rgba(4,83,203,.10);
        color: #0453cb;
        border: 1px solid rgba(4,83,203,.25);
    }
    .ets-chip--accent {
        background: rgba(59,125,219,.10);
        color: #3b7ddb;
        border: 1px solid rgba(59,125,219,.25);
    }
    .ets-chip--muted {
        background: rgba(94,145,222,.08);
        color: #5e91de;
        border: 1px solid rgba(94,145,222,.20);
    }

    /* ===== Bandeau filtre (LMD) ===== */
    .ets-seances-filter {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .85rem 1.25rem;
        background: linear-gradient(135deg, rgba(4,83,203,.03), rgba(59,125,219,.05));
        border-bottom: 1px solid #e2e8f0;
        flex-wrap: wrap;
    }
    .ets-seances-filter > .au-select {
        flex: 1;
        min-width: 240px;
        max-width: 360px;
    }
    .ets-seances-count {
        font-size: .82rem;
        color: #64748b;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .ets-count-value {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        height: 24px;
        padding: 0 .55rem;
        background: rgba(4,83,203,.1);
        color: #0453cb;
        border-radius: 99px;
        font-size: .78rem;
        font-weight: 700;
    }
    .ets-seances-empty-filter {
        text-align: center;
        padding: 2rem 1rem;
        color: #64748b;
        font-size: .9rem;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
    }
    .ets-seances-empty-filter i {
        font-size: 1.5rem;
        color: #94a3b8;
        margin-bottom: .5rem;
        display: block;
    }
    [x-cloak] { display: none !important; }
</style>
@endonce

<div class="els-wrap"
     x-data="{
        filterType: '',
        filteredCount: 0,
        recomputeCount() {
            this.$nextTick(() => {
                const rows = this.$root.querySelectorAll('tbody tr[data-seance-row]');
                let visible = 0;
                rows.forEach(r => {
                    if (r.offsetParent !== null) visible++;
                });
                this.filteredCount = visible;
            });
        }
     }"
     x-init="recomputeCount(); $watch('filterType', () => recomputeCount());"
     @et:filter-by-type.window="filterType = $event.detail.type || ''">

    <div style="padding: 1rem 1.25rem; border-bottom: 1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem;">
        <div style="display:flex; align-items:center; gap:.55rem; font-weight:700; color:#0f172a; font-size:.9rem;">
            <i class="fas fa-list-ul" style="color:#0453cb;"></i>
            {{ $seances->count() }} séance(s) programmée(s)
        </div>
        @can('timetables.create')
        <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id]) }}"
           class="btn btn-sm btn-primary" style="border-radius:9px; font-weight:600; background:#0453cb; border-color:#0453cb;">
            <i class="fas fa-plus me-1"></i>Ajouter
        </a>
        @endcan
    </div>

    {{-- Bandeau filtre (LMD seulement) --}}
    @if($isClasseLmd && $seances && $seances->count() > 0)
    <div class="ets-seances-filter">
        <x-au-select
            name="filterType"
            x-model="filterType"
            :options="$typeSeanceFilterOptions"
            placeholder="Filtrer par type pédagogique"
            icon="fa-filter" />
        <div class="ets-seances-count">
            <span x-text="filteredCount" class="ets-count-value">{{ $seances->count() }}</span>
            <span x-show="!filterType">séance(s) au total</span>
            <span x-show="filterType" x-cloak>séance(s) filtrée(s)</span>
        </div>
    </div>
    @endif

    <div style="padding: 0 1.25rem 1rem;">
        @if($seances && $seances->count() > 0)
            <div class="table-responsive">
                <table class="table els-table mb-0">
                    <thead>
                        <tr>
                            <th width="6%">#</th>
                            <th width="22%">Matière</th>
                            <th width="18%">Enseignant</th>
                            <th width="10%">Type</th>
                            @if($isClasseLmd)
                            <th width="9%">Pédagogie</th>
                            @endif
                            <th width="10%">Jour</th>
                            <th width="{{ $isClasseLmd ? '10%' : '12%' }}">Heure</th>
                            <th width="6%">Durée</th>
                            <th width="7%">Statut</th>
                            <th width="7%" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($seances->sortBy([['jour', 'asc'], ['heure_debut', 'asc']]) as $index => $seance)
                            @php
                                $typeSeanceValue = null;
                                if ($seance->type_seance) {
                                    $typeSeanceValue = $seance->type_seance instanceof \App\Enums\TypeSeance
                                        ? $seance->type_seance->value
                                        : (string) $seance->type_seance;
                                }
                            @endphp
                        <tr data-seance-row
                            data-type-seance="{{ $typeSeanceValue }}"
                            x-show="!filterType || '{{ $typeSeanceValue }}' === filterType"
                            x-cloak>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="els-matiere-icon">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <strong>{{ $seance->matiere->name ?? 'Non définie' }}</strong>
                                </div>
                            </td>
                            <td>
                                @if($seance->teacher)
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-user-tie text-secondary me-1"></i>
                                        <small>{{ $seance->teacher->name }}</small>
                                    </div>
                                @else
                                    <small class="text-muted">
                                        <i class="fas fa-user-slash me-1"></i>Non assigné
                                    </small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $typeLabels = [
                                        'course' => 'COURS',
                                        'homework' => 'DEVOIR',
                                        'break' => 'RÉCRÉATION',
                                        'lunch' => 'PAUSE'
                                    ];
                                    $typeKey = $seance->type ?? 'course';
                                    $typeLabel = $typeLabels[$typeKey] ?? 'COURS';
                                @endphp
                                {{-- Affiche le sous-type type_seance (CM/TD/EXAMEN/...) quand defini,
                                     sinon le type generique (Cours/Devoir/Récréation/Pause). --}}
                                <span class="els-type-badge els-type-badge--{{ $typeKey }}">
                                    {{ $typeSeanceValue ?: $typeLabel }}
                                </span>
                            </td>
                            @if($isClasseLmd)
                            <td>
                                @if($typeSeanceValue)
                                    @php
                                        $tone = match (true) {
                                            in_array($typeSeanceValue, ['CM', 'TD'], true)        => 'primary',
                                            in_array($typeSeanceValue, ['TP', 'PROJET'], true)    => 'accent',
                                            default                                                => 'muted',
                                        };
                                    @endphp
                                    <span class="ets-chip ets-chip--{{ $tone }}">{{ $typeSeanceValue }}</span>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            @endif
                            <td>
                                @php
                                    $joursMapping = [
                                        1 => 'Lundi',
                                        2 => 'Mardi',
                                        3 => 'Mercredi',
                                        4 => 'Jeudi',
                                        5 => 'Vendredi',
                                        6 => 'Samedi',
                                        0 => 'Dimanche',
                                        7 => 'Dimanche'
                                    ];
                                    $jourNom = $joursMapping[$seance->jour] ?? 'Jour ' . $seance->jour;
                                @endphp
                                <small class="fw-bold">{{ $jourNom }}</small>
                            </td>
                            <td>
                                <small>
                                    <i class="fas fa-clock text-muted me-1"></i>
                                    {{ \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') }} -
                                    {{ \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') }}
                                </small>
                            </td>
                            <td class="text-center">
                                @php
                                    $debut = \Carbon\Carbon::parse($seance->heure_debut);
                                    $fin = \Carbon\Carbon::parse($seance->heure_fin);
                                    $duree = $debut->diffInHours($fin);
                                @endphp
                                <small class="text-muted">{{ $duree }}h</small>
                            </td>
                            <td class="text-center">
                                @if($seance->is_active ?? true)
                                    <span class="els-status-active">
                                        <i class="fas fa-check"></i> Actif
                                    </span>
                                @else
                                    <span class="els-status-inactive">
                                        <i class="fas fa-pause"></i> Inactif
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Actions séance">
                                    @can('timetables.edit')
                                    <a href="{{ route('esbtp.seances-cours.edit', $seance->id) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       data-bs-toggle="tooltip" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('timetables.delete')
                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="tooltip" title="Supprimer"
                                            onclick="window.etsDeleteSeance({{ $seance->id }}, @js($seance->matiere->name ?? 'Séance'))">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Empty state quand filtre ne renvoie rien (LMD only) --}}
            @if($isClasseLmd)
            <div class="ets-seances-empty-filter"
                 x-show="filterType && filteredCount === 0"
                 x-cloak>
                <i class="fas fa-filter-circle-xmark"></i>
                Aucune séance pour le type <strong x-text="filterType"></strong>.
                <a href="#" @click.prevent="filterType = ''" style="color:#0453cb; font-weight:600;">Réinitialiser le filtre</a>
            </div>
            @endif

            <!-- Résumé par type de séance -->
            <div class="els-repartition">
                <h6 class="mb-3" style="color: #0453cb; font-size: .88rem; font-weight: 700;">
                    <i class="fas fa-chart-bar me-1"></i>
                    Répartition par type de séance
                </h6>
                <div class="row g-2">
                    @php
                        $repartition = [
                            'course' => $seances->where('type', 'course')->count(),
                            'homework' => $seances->where('type', 'homework')->count(),
                            'break' => $seances->where('type', 'break')->count(),
                            'lunch' => $seances->where('type', 'lunch')->count(),
                        ];
                        $total = $seances->count();
                        $typeLabels = [
                            'course' => 'COURS',
                            'homework' => 'DEVOIRS',
                            'break' => 'RÉCRÉATIONS',
                            'lunch' => 'PAUSES'
                        ];
                    @endphp

                    @foreach($repartition as $type => $count)
                        @if($count > 0)
                        <div class="col-md-3 col-6">
                            <div class="els-repartition-item">
                                <div class="els-repartition-value">{{ $count }}</div>
                                <div class="els-repartition-label">{{ $typeLabels[$type] }}</div>
                                <div class="els-repartition-pct">{{ $total > 0 ? round(($count / $total) * 100, 1) : 0 }}%</div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @else
            <div class="text-center py-4" style="border: 1px dashed #cbd5e1; border-radius: 12px; background: #f8fafc;">
                <div style="width:64px; height:64px; margin:0 auto 1rem; border-radius:50%; background:linear-gradient(135deg, rgba(4,83,203,.08), rgba(59,125,219,.12)); display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-calendar-plus" style="color:#0453cb; font-size:1.5rem;"></i>
                </div>
                <h6 style="color:#1e293b; font-weight:600;">Aucune séance programmée</h6>
                <p class="text-muted mb-3" style="font-size:.88rem;">Cet emploi du temps ne contient aucune séance.</p>
                @can('timetables.create')
                <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id]) }}"
                   class="btn btn-primary btn-sm" style="border-radius:9px; font-weight:600;">
                    <i class="fas fa-plus me-1"></i>Ajouter la première séance
                </a>
                @endcan
            </div>
        @endif
    </div>
</div>
