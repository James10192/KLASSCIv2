@props(['emploiTemps', 'matiereStats' => []])

@once
<style>
    .eis-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }
    .eis-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.1rem 1.25rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }
    .eis-card-title {
        display: flex;
        align-items: center;
        gap: .55rem;
        font-size: .82rem;
        font-weight: 700;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: .85rem;
    }
    .eis-card-title i {
        width: 26px;
        height: 26px;
        border-radius: 7px;
        background: rgba(4,83,203,.1);
        color: #0453cb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .72rem;
    }

    /* Info rows */
    .eis-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        padding: .5rem 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: .85rem;
        gap: 1rem;
    }
    .eis-row:last-child { border-bottom: none; }
    .eis-row-label {
        color: #64748b;
        font-weight: 500;
    }
    .eis-row-value {
        color: #1e293b;
        font-weight: 600;
        text-align: right;
        min-width: 0;
        word-break: break-word;
    }
    .eis-row-value .badge { font-weight: 600; }

    /* Stats (types) */
    .eis-types {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
        gap: .55rem;
    }
    .eis-type {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: .6rem .5rem;
        border-radius: 10px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        transition: all .15s ease;
    }
    .eis-type:hover {
        border-color: #0453cb;
        background: #fff;
    }
    .eis-type-icon {
        width: 30px;
        height: 30px;
        border-radius: 7px;
        background: rgba(4,83,203,.1);
        color: #0453cb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: .78rem;
        margin-bottom: .35rem;
    }
    .eis-type-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1;
    }
    .eis-type-label {
        font-size: .68rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-top: .3rem;
    }

    /* Matieres count list */
    .eis-matieres-list {
        max-height: 220px;
        overflow-y: auto;
        padding-right: .25rem;
    }
    .eis-matieres-list::-webkit-scrollbar { width: 4px; }
    .eis-matieres-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
    }
    .eis-matiere-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: .4rem 0;
        font-size: .82rem;
        border-bottom: 1px solid #f8fafc;
    }
    .eis-matiere-row:last-child { border-bottom: none; }
    .eis-matiere-row-name { color: #1e293b; }
    .eis-matiere-row-count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 28px;
        padding: .2rem .5rem;
        background: rgba(4,83,203,.08);
        color: #0453cb;
        border-radius: 99px;
        font-size: .75rem;
        font-weight: 700;
    }

    /* Totals row */
    .eis-totals {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .75rem;
        margin-top: .85rem;
        padding-top: .85rem;
        border-top: 1px solid #e2e8f0;
    }
    .eis-total {
        text-align: center;
        padding: .5rem;
        background: rgba(4,83,203,.04);
        border-radius: 8px;
    }
    .eis-total-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #0453cb;
        line-height: 1;
    }
    .eis-total-label {
        font-size: .7rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .3px;
        margin-top: .3rem;
    }
</style>
@endonce

@php
    $seances = is_object($emploiTemps) && is_object($emploiTemps->seances) ? $emploiTemps->seances : collect();
    $countTypes = [
        'course' => $seances->where('type', 'course')->count(),
        'homework' => $seances->where('type', 'homework')->count(),
        'break' => $seances->where('type', 'break')->count(),
        'lunch' => $seances->where('type', 'lunch')->count(),
    ];
    $typeLabels = [
        'course' => 'Cours',
        'homework' => 'Devoirs',
        'break' => 'Récréations',
        'lunch' => 'Pauses',
    ];
    $typeIcons = [
        'course' => 'fa-chalkboard',
        'homework' => 'fa-pencil-alt',
        'break' => 'fa-coffee',
        'lunch' => 'fa-utensils',
    ];
    $totalSeances = $seances->count();
    $activesCount = $seances->where('is_active', 1)->count();
    $statut = ($emploiTemps->is_active ?? false) ? 'Actif' : 'Inactif';
    $periode = match($emploiTemps->semestre ?? null) {
        'Semestre 1' => 'Semestre 1',
        'Semestre 2' => 'Semestre 2',
        default => 'Année complète',
    };
@endphp

<div class="eis-grid">
    {{-- Card 1 : Informations détaillées --}}
    <div class="eis-card">
        <div class="eis-card-title"><i class="fas fa-info-circle"></i> Détails emploi du temps</div>

        <div class="eis-row">
            <span class="eis-row-label">Période</span>
            <span class="eis-row-value">{{ $periode }}</span>
        </div>
        @if($emploiTemps->date_debut && $emploiTemps->date_fin)
        <div class="eis-row">
            <span class="eis-row-label">Dates</span>
            <span class="eis-row-value">
                {{ \Carbon\Carbon::parse($emploiTemps->date_debut)->format('d/m/Y') }}
                →
                {{ \Carbon\Carbon::parse($emploiTemps->date_fin)->format('d/m/Y') }}
            </span>
        </div>
        @endif
        <div class="eis-row">
            <span class="eis-row-label">Statut</span>
            <span class="eis-row-value">
                @if($emploiTemps->is_active ?? false)
                    <span class="els-status-active"><i class="fas fa-check"></i> Actif</span>
                @else
                    <span class="els-status-inactive"><i class="fas fa-pause"></i> Inactif</span>
                @endif
                @if($emploiTemps->is_current ?? false)
                    <span class="ms-1 badge" style="background:rgba(4,83,203,.12); color:#0453cb;">Courant</span>
                @endif
            </span>
        </div>
        @if($emploiTemps->created_at)
        <div class="eis-row">
            <span class="eis-row-label">Créé le</span>
            <span class="eis-row-value">{{ $emploiTemps->created_at->format('d/m/Y') }}</span>
        </div>
        @endif
        @if($emploiTemps->updated_at)
        <div class="eis-row">
            <span class="eis-row-label">Dernière modif.</span>
            <span class="eis-row-value">{{ $emploiTemps->updated_at->diffForHumans() }}</span>
        </div>
        @endif
    </div>

    {{-- Card 2 : Stats types séances --}}
    <div class="eis-card">
        <div class="eis-card-title"><i class="fas fa-layer-group"></i> Types de séances</div>

        <div class="eis-types">
            @foreach($countTypes as $type => $count)
                <div class="eis-type">
                    <div class="eis-type-icon"><i class="fas {{ $typeIcons[$type] }}"></i></div>
                    <div class="eis-type-value">{{ $count }}</div>
                    <div class="eis-type-label">{{ $typeLabels[$type] }}</div>
                </div>
            @endforeach
        </div>

        <div class="eis-totals">
            <div class="eis-total">
                <div class="eis-total-value">{{ $totalSeances }}</div>
                <div class="eis-total-label">Total séances</div>
            </div>
            <div class="eis-total">
                <div class="eis-total-value">{{ $activesCount }}</div>
                <div class="eis-total-label">Séances actives</div>
            </div>
        </div>
    </div>

    {{-- Card 3 : Séances par matière (if any) --}}
    @if(!empty($matiereStats))
    <div class="eis-card">
        <div class="eis-card-title"><i class="fas fa-book"></i> Séances par matière</div>
        <div class="eis-matieres-list">
            @foreach($matiereStats as $matiere => $count)
                <div class="eis-matiere-row">
                    <span class="eis-matiere-row-name">{{ $matiere }}</span>
                    <span class="eis-matiere-row-count">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
