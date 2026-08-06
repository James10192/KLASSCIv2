@extends('layouts.app')

@section('title', 'Édition des absences - KLASSCI')

@php
// Règle d'assiduité à tranches configurables. Le controller passe $attendanceRule
// (AttendanceNoteRule::toArray) ; fallback legacy depuis $attendanceNoteRules si absent.
$_attRule = $attendanceRule ?? \App\Support\Attendance\AttendanceNoteRule::fromLegacySettings([
    'zero_unjustified' => (float) (($attendanceNoteRules['zero_unjustified'] ?? null) ?? 0.13),
    'one_unjustified' => (float) (($attendanceNoteRules['one_unjustified'] ?? null) ?? 0.0),
    'two_unjustified' => (float) (($attendanceNoteRules['two_unjustified'] ?? $attendanceNoteRules['two_or_more_unjustified'] ?? null) ?? -0.13),
    'three_to_four_unjustified' => (float) (($attendanceNoteRules['three_to_four_unjustified'] ?? null) ?? -0.39),
    'five_or_more_unjustified' => (float) (($attendanceNoteRules['five_or_more_unjustified'] ?? null) ?? -0.50),
])->toArray();
// Le barème justifié n'est affiché que s'il porte un effet (au moins une note non nulle).
$_hasJustifiedMalus = collect($_attRule['justified'] ?? [])->contains(fn ($b) => (float) ($b['note'] ?? 0) !== 0.0);
$_fmtBracket = function (array $b): string {
    $from = rtrim(rtrim(number_format((float) $b['min'], 1, '.', ''), '0'), '.');
    if ($b['max'] === null) {
        return $from.'h et plus';
    }
    $to = rtrim(rtrim(number_format((float) $b['max'], 1, '.', ''), '0'), '.');
    return $from.'–'.$to.'h';
};
$_periodeLabel = $periode === 'semestre1' ? 'Semestre 1' : ($periode === 'semestre2' ? 'Semestre 2' : 'Annuel');
$_returnUrl = route('esbtp.resultats.etudiant', [
    'etudiant' => $etudiant->id,
    'classe_id' => $classe->id,
    'periode' => $periode === 'semestre1' ? '1' : ($periode === 'semestre2' ? '2' : $periode),
    'annee_universitaire_id' => $anneeUniversitaire->id,
]);
@endphp

@push('styles')
<style>
/* ═══════════════════════ NAMESPACE ea-* (Édition Absences premium) ═══════════════════════ */

/* ─── HERO ─── */
.ea-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.ea-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.ea-hero-left {
    display: flex; align-items: center; gap: 1rem; min-width: 0; flex: 1;
}
.ea-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.ea-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.2; }
.ea-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: .25rem 0 0; display: inline-flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
.ea-hero-chip {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 999px;
    padding: .15rem .65rem;
    font-size: .75rem; font-weight: 600; color: #fff;
}
.ea-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border: 1px solid transparent; border-radius: 10px;
    padding: .55rem 1rem; font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer; transition: all .15s; white-space: nowrap;
}
.ea-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.2); }
.ea-btn--glass:hover { background: rgba(255,255,255,.22); color: #fff; }
.ea-btn--white { background: #fff; color: #0453cb; }
.ea-btn--white:hover { background: #f1f5fc; color: #0453cb; }
.ea-btn--ghost { background: #fff; color: #0453cb; border-color: rgba(4,83,203,.25); }
.ea-btn--ghost:hover { background: rgba(4,83,203,.05); border-color: rgba(4,83,203,.4); }
.ea-btn--success { background: #10b981; color: #fff; }
.ea-btn--success:hover { background: #059669; color: #fff; }
.ea-btn--primary { background: #0453cb; color: #fff; }
.ea-btn--primary:hover { background: #033a8e; color: #fff; }

/* ─── KPIs rangée 2 du hero ─── */
.ea-kpis {
    display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
}
.ea-kpi {
    flex: 1; min-width: 150px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .85rem;
}
.ea-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    background: rgba(255,255,255,.15); color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; flex-shrink: 0;
}
.ea-kpi-value { font-size: 1.4rem; font-weight: 700; color: #fff; line-height: 1; }
.ea-kpi-label { font-size: .7rem; color: rgba(255,255,255,.7); margin-top: .25rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 600; }
.ea-kpi-value--positive { color: #d1fae5; }
.ea-kpi-value--negative { color: #fecaca; }

/* ─── Cards ─── */
.ea-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.ea-card-header {
    padding: 1.1rem 1.4rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.ea-card-body { padding: 1.4rem; }
.ea-section-header {
    display: flex; align-items: center; gap: .75rem;
}
.ea-section-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem; flex-shrink: 0;
}
.ea-section-header h3 { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
.ea-section-header p { font-size: .78rem; color: #64748b; margin: 0; }

/* ─── Badges Source ─── */
.ea-source-badge {
    display: inline-flex; align-items: center; gap: .35rem;
    font-size: .72rem; font-weight: 700;
    padding: .3rem .65rem;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .04em;
    border: 1px solid;
}
.ea-source-badge--auto { background: rgba(4,83,203,.08); color: #0453cb; border-color: rgba(4,83,203,.25); }
.ea-source-badge--manuel { background: rgba(245,158,11,.1); color: #b45309; border-color: rgba(245,158,11,.3); }

/* ─── Sub-cards (calculées vs manuelles) ─── */
.ea-subcard {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 1.25rem;
    height: 100%;
}
.ea-subcard-header {
    display: flex; align-items: center; gap: .75rem;
    padding-bottom: .85rem; margin-bottom: 1rem;
    border-bottom: 1px solid #f1f5f9;
}
.ea-subcard-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem; flex-shrink: 0;
}
.ea-subcard-icon--blue { background: linear-gradient(135deg, #0453cb, #3b7ddb); }
.ea-subcard-icon--green { background: linear-gradient(135deg, #10b981, #34d399); }
.ea-subcard-title { font-size: .9rem; font-weight: 700; color: #1e293b; margin: 0; }
.ea-subcard-subtitle { font-size: .73rem; color: #94a3b8; margin: .1rem 0 0; }

.ea-stat-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .75rem;
}
.ea-stat {
    background: rgba(248,250,252,.6);
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: .8rem;
}
.ea-stat-label { font-size: .68rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .04em; font-weight: 600; margin-bottom: .35rem; }
.ea-stat-value { font-size: 1.45rem; font-weight: 700; line-height: 1; }
.ea-stat-value--success { color: #10b981; }
.ea-stat-value--danger  { color: #dc2626; }
.ea-stat-value--primary { color: #0453cb; }
.ea-stat--full { grid-column: 1 / -1; }
.ea-stat--full .ea-stat-value { font-size: 1.75rem; }

/* ─── Inputs ─── */
.ea-input-group {
    display: flex; flex-direction: column; gap: .35rem;
    margin-bottom: 1rem;
}
.ea-input-label {
    display: inline-flex; align-items: center; gap: .4rem;
    font-size: .8rem; font-weight: 700; color: #475569;
    text-transform: uppercase; letter-spacing: .03em;
}
.ea-input-label--success i { color: #10b981; }
.ea-input-label--danger i  { color: #dc2626; }
.ea-input-wrap {
    position: relative;
    display: flex; align-items: center;
    background: #fff;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    padding: 0 1rem;
    transition: border-color .15s, box-shadow .15s;
}
.ea-input-wrap:focus-within {
    border-color: rgba(4,83,203,.5);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.ea-input-wrap input {
    flex: 1; min-width: 0;
    border: none; outline: none; background: transparent;
    padding: .85rem 0; font-size: 1rem; color: #1e293b;
    font-weight: 600;
}
.ea-input-suffix {
    color: #94a3b8; font-size: .85rem; font-weight: 600;
    margin-left: .5rem;
}

.ea-total-banner {
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(59,125,219,.08));
    border: 1px solid rgba(4,83,203,.15);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    margin-top: .5rem;
}
.ea-total-label { font-size: .85rem; color: #475569; font-weight: 600; display: inline-flex; align-items: center; gap: .5rem; }
.ea-total-value { font-size: 1.6rem; color: #0453cb; font-weight: 700; }

/* ─── Section Assiduité (5 paliers) ─── */
.ea-note-display {
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(59,125,219,.08));
    border: 1px solid rgba(4,83,203,.15);
    border-radius: 14px;
    padding: 1.5rem;
    text-align: center;
}
.ea-note-label { font-size: .72rem; color: #64748b; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; margin-bottom: .5rem; }
.ea-note-value {
    font-size: 2.75rem; font-weight: 800; line-height: 1;
    color: #10b981;
    margin: .25rem 0;
    transition: color .2s;
}
.ea-note-value--negative { color: #dc2626; }
.ea-note-value--disabled { color: #94a3b8; font-size: 1.5rem; }
.ea-note-unit { font-size: .82rem; color: #94a3b8; }

.ea-bareme-title {
    display: flex; align-items: center; gap: .5rem;
    font-size: .82rem; font-weight: 700; color: #1e293b;
    margin-bottom: .85rem;
    text-transform: uppercase; letter-spacing: .03em;
}
.ea-bareme-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: .65rem;
}
.ea-bareme-item {
    display: flex; align-items: center; gap: .65rem;
    padding: .75rem .9rem;
    background: rgba(248,250,252,.7);
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    font-size: .85rem;
    color: #1e293b;
    transition: all .15s;
}
.ea-bareme-item--success { background: rgba(16,185,129,.06); border-color: rgba(16,185,129,.2); }
.ea-bareme-item--neutral { background: rgba(100,116,139,.05); border-color: rgba(100,116,139,.18); }
.ea-bareme-item--warning { background: rgba(245,158,11,.06); border-color: rgba(245,158,11,.25); }
.ea-bareme-item--danger  { background: rgba(220,38,38,.05); border-color: rgba(220,38,38,.2); }
.ea-bareme-item--critical { background: rgba(127,29,29,.05); border-color: rgba(127,29,29,.25); }
.ea-bareme-item i { font-size: .9rem; flex-shrink: 0; }
.ea-bareme-item--success i { color: #10b981; }
.ea-bareme-item--neutral i { color: #64748b; }
.ea-bareme-item--warning i { color: #f59e0b; }
.ea-bareme-item--danger i, .ea-bareme-item--critical i { color: #dc2626; }
.ea-bareme-item strong { font-weight: 700; }
.ea-bareme-item.active {
    box-shadow: 0 0 0 2px rgba(4,83,203,.3);
    transform: scale(1.02);
}

.ea-disabled-banner {
    display: flex; align-items: center; gap: .85rem;
    background: rgba(100,116,139,.08);
    border: 1px dashed rgba(100,116,139,.3);
    border-radius: 12px;
    padding: 1.25rem;
    color: #475569;
}
.ea-disabled-banner i { font-size: 1.5rem; color: #94a3b8; }

.ea-footnote {
    display: flex; align-items: flex-start; gap: .5rem;
    margin-top: 1.25rem;
    padding: .75rem;
    background: rgba(4,83,203,.04);
    border-left: 3px solid #0453cb;
    border-radius: 6px;
    font-size: .78rem;
    color: #475569;
}
.ea-footnote i { color: #0453cb; margin-top: .15rem; flex-shrink: 0; }

/* ─── Actions footer ─── */
.ea-actions {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.ea-actions-right { display: flex; gap: .75rem; flex-wrap: wrap; }

/* ─── Flash ─── */
.ea-flash {
    display: flex; align-items: center; gap: .75rem;
    padding: .85rem 1.1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-size: .9rem;
    border: 1px solid;
}
.ea-flash--success { background: rgba(16,185,129,.08); border-color: rgba(16,185,129,.3); color: #047857; }
.ea-flash--danger  { background: rgba(220,38,38,.08); border-color: rgba(220,38,38,.3); color: #b91c1c; }

@media (max-width: 768px) {
    .ea-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .ea-hero h1 { font-size: 1.2rem; }
    .ea-actions { flex-direction: column; align-items: stretch; }
    .ea-actions-right { width: 100%; }
    .ea-actions-right .ea-btn { flex: 1; justify-content: center; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ═══════════════════════ HERO ═══════════════════════ --}}
        <div class="ea-hero">
            <div class="ea-hero-top">
                <div class="ea-hero-left">
                    <div class="ea-hero-icon"><i class="fas fa-user-clock"></i></div>
                    <div>
                        <h1>Édition des absences</h1>
                        <p>
                            {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                            <span class="ea-hero-chip"><i class="fas fa-chalkboard"></i>{{ $classe->name }}</span>
                            <span class="ea-hero-chip"><i class="fas fa-calendar"></i>{{ $_periodeLabel }}</span>
                            @if($source === 'manuelle')
                                <span class="ea-hero-chip" style="background:rgba(245,158,11,.2);border-color:rgba(245,158,11,.4);"><i class="fas fa-edit"></i>Saisie manuelle</span>
                            @else
                                <span class="ea-hero-chip"><i class="fas fa-robot"></i>Calcul auto</span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="ea-hero-actions">
                    <a href="{{ $_returnUrl }}" class="ea-btn ea-btn--glass">
                        <i class="fas fa-arrow-left"></i><span>Retour</span>
                    </a>
                </div>
            </div>

            <div class="ea-kpis">
                <div class="ea-kpi">
                    <div class="ea-kpi-icon"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="ea-kpi-value">{{ number_format($absencesCalculees['justifiees'] ?? 0, 1) }}h</div>
                        <div class="ea-kpi-label">Justifiées</div>
                    </div>
                </div>
                <div class="ea-kpi">
                    <div class="ea-kpi-icon"><i class="fas fa-times-circle"></i></div>
                    <div>
                        <div class="ea-kpi-value">{{ number_format($absencesCalculees['non_justifiees'] ?? 0, 1) }}h</div>
                        <div class="ea-kpi-label">Non justifiées</div>
                    </div>
                </div>
                <div class="ea-kpi">
                    <div class="ea-kpi-icon"><i class="fas fa-hourglass-half"></i></div>
                    <div>
                        <div class="ea-kpi-value">{{ number_format($absencesCalculees['total'] ?? 0, 1) }}h</div>
                        <div class="ea-kpi-label">Total auto</div>
                    </div>
                </div>
                @if($attendanceNoteEnabled)
                <div class="ea-kpi">
                    <div class="ea-kpi-icon"><i class="fas fa-star"></i></div>
                    <div>
                        <div class="ea-kpi-value {{ $noteAssiduite >= 0 ? 'ea-kpi-value--positive' : 'ea-kpi-value--negative' }}">
                            {{ $noteAssiduite >= 0 ? '+' : '' }}{{ number_format($noteAssiduite, 2) }}
                        </div>
                        <div class="ea-kpi-label">Note assiduité</div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- ═══════════════════════ FLASH ═══════════════════════ --}}
        @if($errors->any())
            <div class="ea-flash ea-flash--danger">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <strong>Erreurs de validation :</strong>
                    <ul class="mb-0 mt-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            </div>
        @endif
        @if(session('success'))
            <div class="ea-flash ea-flash--success">
                <i class="fas fa-check-circle"></i><span>{{ session('success') }}</span>
            </div>
        @endif
        @if(session('error'))
            <div class="ea-flash ea-flash--danger">
                <i class="fas fa-exclamation-circle"></i><span>{{ session('error') }}</span>
            </div>
        @endif

        <form id="absencesForm" action="{{ route('esbtp.bulletins.save-absences') }}" method="POST">
            @csrf
            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

            {{-- ═══════════════════════ GESTION ABSENCES ═══════════════════════ --}}
            <div class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-section-header">
                        <div class="ea-section-icon"><i class="fas fa-user-clock"></i></div>
                        <div>
                            <h3>Gestion des absences</h3>
                            <p>Comparez les valeurs calculées automatiquement à votre saisie manuelle</p>
                        </div>
                    </div>
                    <span class="ea-source-badge ea-source-badge--{{ $source === 'manuelle' ? 'manuel' : 'auto' }}">
                        <i class="fas fa-{{ $source === 'manuelle' ? 'edit' : 'robot' }}"></i>
                        Source : {{ $source === 'manuelle' ? 'Manuelle' : 'Auto' }}
                    </span>
                </div>
                <div class="ea-card-body">
                    <div class="row g-3">
                        {{-- Calculées --}}
                        <div class="col-lg-6">
                            <div class="ea-subcard">
                                <div class="ea-subcard-header">
                                    <div class="ea-subcard-icon ea-subcard-icon--blue"><i class="fas fa-robot"></i></div>
                                    <div>
                                        <h4 class="ea-subcard-title">Calculées automatiquement</h4>
                                        <p class="ea-subcard-subtitle">Depuis le système d'émargement</p>
                                    </div>
                                </div>
                                <div class="ea-stat-grid">
                                    <div class="ea-stat">
                                        <div class="ea-stat-label">Justifiées</div>
                                        <div class="ea-stat-value ea-stat-value--success">{{ number_format($absencesCalculees['justifiees'] ?? 0, 1) }}h</div>
                                    </div>
                                    <div class="ea-stat">
                                        <div class="ea-stat-label">Non justifiées</div>
                                        <div class="ea-stat-value ea-stat-value--danger">{{ number_format($absencesCalculees['non_justifiees'] ?? 0, 1) }}h</div>
                                    </div>
                                    <div class="ea-stat ea-stat--full">
                                        <div class="ea-stat-label">Total calculé</div>
                                        <div class="ea-stat-value ea-stat-value--primary">{{ number_format($absencesCalculees['total'] ?? 0, 1) }}h</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Manuelles --}}
                        <div class="col-lg-6">
                            <div class="ea-subcard">
                                <div class="ea-subcard-header">
                                    <div class="ea-subcard-icon ea-subcard-icon--green"><i class="fas fa-edit"></i></div>
                                    <div>
                                        <h4 class="ea-subcard-title">À enregistrer</h4>
                                        <p class="ea-subcard-subtitle">Ajustez si nécessaire — ces valeurs seront utilisées sur le bulletin</p>
                                    </div>
                                </div>

                                <div class="ea-input-group">
                                    <label for="absences_justifiees" class="ea-input-label ea-input-label--success">
                                        <i class="fas fa-check-circle"></i>Absences justifiées
                                    </label>
                                    <div class="ea-input-wrap">
                                        <input type="number" id="absences_justifiees" name="absences_justifiees"
                                            value="{{ old('absences_justifiees', $absencesJustifiees) }}"
                                            min="0" step="0.5" required oninput="calculerTotalAbsences()">
                                        <span class="ea-input-suffix">heures</span>
                                    </div>
                                </div>

                                <div class="ea-input-group">
                                    <label for="absences_non_justifiees" class="ea-input-label ea-input-label--danger">
                                        <i class="fas fa-times-circle"></i>Absences non justifiées
                                    </label>
                                    <div class="ea-input-wrap">
                                        <input type="number" id="absences_non_justifiees" name="absences_non_justifiees"
                                            value="{{ old('absences_non_justifiees', $absencesNonJustifiees) }}"
                                            min="0" step="0.5" required oninput="calculerTotalAbsences()">
                                        <span class="ea-input-suffix">heures</span>
                                    </div>
                                </div>

                                <div class="ea-total-banner">
                                    <span class="ea-total-label"><i class="fas fa-calculator"></i>Total absences</span>
                                    <span class="ea-total-value" id="total_absences_display">{{ number_format($totalAbsences, 1) }}h</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════════════ NOTE D'ASSIDUITÉ + BARÈME ═══════════════════════ --}}
            <div class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-section-header">
                        <div class="ea-section-icon" style="background:linear-gradient(135deg,#10b981,#34d399);"><i class="fas fa-star"></i></div>
                        <div>
                            <h3>Note d'assiduité &amp; barème</h3>
                            <p id="ea-assiduite-subtitle">
                                @if($attendanceNoteEnabled)
                                    Calculée selon les absences non justifiées — barème 5 paliers
                                @else
                                    Désactivée dans les paramètres globaux
                                @endif
                            </p>
                        </div>
                    </div>
                    @if($attendanceNoteEnabled)
                        <a href="{{ route('esbtp.settings.index') }}" class="ea-btn ea-btn--ghost" title="Configurer le barème">
                            <i class="fas fa-sliders-h"></i><span>Configurer barème</span>
                        </a>
                    @endif
                </div>
                <div class="ea-card-body">
                    @if($attendanceNoteEnabled)
                        <div class="row g-3 align-items-stretch">
                            <div class="col-md-4">
                                <div class="ea-note-display">
                                    <div class="ea-note-label">Note actuelle</div>
                                    <div class="ea-note-value {{ $noteAssiduite < 0 ? 'ea-note-value--negative' : '' }}" id="note_assiduite_display">
                                        {{ $noteAssiduite >= 0 ? '+' : '' }}{{ number_format($noteAssiduite, 2) }}
                                    </div>
                                    <div class="ea-note-unit">points</div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="ea-bareme-title"><i class="fas fa-calculator"></i>Barème d'assiduité (non justifiées)</div>
                                <div class="ea-bareme-grid" id="ea-bareme-grid">
                                    <div class="ea-bareme-item ea-bareme-item--success" data-bracket="zero">
                                        <i class="fas fa-check-circle"></i>
                                        <span><strong>0</strong> absence = <strong class="b-val">{{ ((float) $_attRule['zero_bonus'] >= 0 ? '+' : '') . number_format((float) $_attRule['zero_bonus'], 2) }}</strong> pt</span>
                                    </div>
                                    @foreach($_attRule['unjustified'] as $_i => $_b)
                                    <div class="ea-bareme-item {{ (float) $_b['note'] > 0 ? 'ea-bareme-item--success' : ((float) $_b['note'] === 0.0 ? 'ea-bareme-item--neutral' : 'ea-bareme-item--danger') }}" data-bracket="{{ $_i }}">
                                        <i class="fas fa-hourglass-half"></i>
                                        <span><strong>{{ $_fmtBracket($_b) }}</strong> = <strong class="b-val">{{ ((float) $_b['note'] >= 0 ? '+' : '') . number_format((float) $_b['note'], 2) }}</strong> pt</span>
                                    </div>
                                    @endforeach
                                </div>
                                @if($_hasJustifiedMalus)
                                <div class="ea-bareme-title" style="margin-top:10px;"><i class="fas fa-user-clock"></i>Barème additionnel (justifiées)</div>
                                <div class="ea-bareme-grid">
                                    @foreach($_attRule['justified'] as $_b)
                                    <div class="ea-bareme-item {{ (float) $_b['note'] > 0 ? 'ea-bareme-item--success' : ((float) $_b['note'] === 0.0 ? 'ea-bareme-item--neutral' : 'ea-bareme-item--danger') }}">
                                        <i class="fas fa-hourglass-half"></i>
                                        <span><strong>{{ $_fmtBracket($_b) }}</strong> = <strong class="b-val">{{ ((float) $_b['note'] >= 0 ? '+' : '') . number_format((float) $_b['note'], 2) }}</strong> pt</span>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                <div class="ea-footnote">
                                    <i class="fas fa-info-circle"></i>
                                    <div>La note d'assiduité est <strong>ajoutée à la moyenne générale</strong> du bulletin. Le barème est configurable globalement dans <a href="{{ route('esbtp.settings.index') }}" style="color:#0453cb;font-weight:600;">settings</a>.</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="ea-disabled-banner">
                            <i class="fas fa-toggle-off"></i>
                            <div>
                                <strong>Note d'assiduité désactivée</strong><br>
                                <small>Le toggle global est OFF — la note vaut <code>0</code> partout et n'apparaît ni sur le bulletin ni dans les calculs. Activez-le dans <a href="{{ route('esbtp.settings.index') }}" style="color:#0453cb;font-weight:600;">settings</a> pour configurer le barème.</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════ ACTIONS ═══════════════════════ --}}
            <div class="ea-card">
                <div class="ea-card-body">
                    <div class="ea-actions">
                        <a href="{{ $_returnUrl }}" class="ea-btn ea-btn--ghost">
                            <i class="fas fa-arrow-left"></i><span>Retour aux résultats</span>
                        </a>
                        <div class="ea-actions-right">
                            <button type="submit" name="action" value="save_and_back" class="ea-btn ea-btn--success">
                                <i class="fas fa-save"></i><span>Enregistrer et retourner</span>
                            </button>
                            <button type="submit" name="action" value="generate" class="ea-btn ea-btn--primary">
                                <i class="fas fa-file-pdf"></i><span>Enregistrer et générer bulletin</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const attendanceNoteEnabled = @json((bool) ($attendanceNoteEnabled ?? true));
const attendanceRule = @json($_attRule);

function bracketNote(brackets, hours) {
    for (const b of (brackets || [])) {
        if (b.max === null || b.max === undefined || hours < Number(b.max)) return Number(b.note || 0);
    }
    return 0;
}

// Index de la tranche non justifiée active (pour le highlight), 'zero' si aucune absence.
function activeUnjustifiedIndex(nonJust) {
    if (nonJust <= 0) return 'zero';
    const brackets = attendanceRule.unjustified || [];
    for (let i = 0; i < brackets.length; i++) {
        const b = brackets[i];
        if (b.max === null || b.max === undefined || nonJust < Number(b.max)) return String(i);
    }
    return brackets.length ? String(brackets.length - 1) : 'zero';
}

function resolveNote(just, nonJust) {
    if (!attendanceNoteEnabled) return null;
    if ((just + nonJust) <= 0) return Number(attendanceRule.zero_bonus || 0);
    return bracketNote(attendanceRule.unjustified, nonJust) + bracketNote(attendanceRule.justified, just);
}

function calculerTotalAbsences() {
    const justifiees = parseFloat(document.getElementById('absences_justifiees').value) || 0;
    const nonJustifiees = parseFloat(document.getElementById('absences_non_justifiees').value) || 0;
    document.getElementById('total_absences_display').textContent = (justifiees + nonJustifiees).toFixed(1) + 'h';

    const noteEl = document.getElementById('note_assiduite_display');
    if (!noteEl) return;

    const note = resolveNote(justifiees, nonJustifiees);
    if (note === null) {
        noteEl.textContent = 'Masquée';
        noteEl.classList.add('ea-note-value--disabled');
        return;
    }
    noteEl.textContent = (note >= 0 ? '+' : '') + note.toFixed(2);
    noteEl.classList.toggle('ea-note-value--negative', note < 0);
    noteEl.classList.remove('ea-note-value--disabled');

    // Highlight de la tranche non justifiée active.
    const active = (justifiees + nonJustifiees) <= 0 ? 'zero' : activeUnjustifiedIndex(nonJustifiees);
    document.querySelectorAll('#ea-bareme-grid .ea-bareme-item').forEach(item => {
        item.classList.toggle('active', item.dataset.bracket === active);
    });
}

document.addEventListener('DOMContentLoaded', calculerTotalAbsences);
</script>
@endsection
