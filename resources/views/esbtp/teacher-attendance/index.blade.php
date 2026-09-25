@extends('layouts.app')

@section('title', 'Émargement des cours - KLASSCI')

@push('styles')
<style>
    .tae-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:2rem 2.5rem 1.5rem; color:#fff; margin-bottom:1.25rem; }
    .tae-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
    .tae-hero-left { display:flex; align-items:center; gap:1rem; }
    .tae-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .tae-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0; }
    .tae-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:0; }
    .tae-btn--glass { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.2); border-radius:10px; padding:.5rem 1rem; font-size:.82rem; font-weight:600; text-decoration:none; }
    .tae-btn--glass:hover { background:rgba(255,255,255,.25); color:#fff; }
    .tae-regles { margin-top:1.25rem; display:flex; gap:.5rem; flex-wrap:wrap; font-size:.78rem; }
    .tae-regle { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:999px; padding:.3rem .75rem; }
    .tae-list { display:flex; flex-direction:column; gap:.75rem; }
    .tae-cours { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1rem 1.25rem; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:1rem; align-items:center; box-shadow:0 1px 3px rgba(15,23,42,.04); }
    .tae-cours h3 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 .25rem; }
    .tae-meta { display:flex; gap:1rem; flex-wrap:wrap; font-size:.82rem; color:#64748b; }
    .tae-meta i { color:#0453cb; margin-right:.3rem; }
    .tae-fenetre { font-size:.78rem; color:#64748b; margin-top:.35rem; }
    .tae-form { display:flex; gap:.5rem; align-items:flex-start; flex-wrap:wrap; justify-content:flex-end; }
    .tae-code { width:9.5rem; border:1px solid #cbd5e1; border-radius:10px; padding:.55rem .75rem; font-family:'Courier New',monospace; font-size:1rem; letter-spacing:.2em; text-transform:uppercase; text-align:center; }
    .tae-code:focus, .tae-just:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.15); }
    .tae-just { width:100%; min-width:16rem; border:1px solid #cbd5e1; border-radius:10px; padding:.5rem .75rem; font-size:.85rem; }
    .tae-btn { background:#0453cb; color:#fff; border:none; border-radius:10px; padding:.6rem 1.1rem; font-weight:600; font-size:.85rem; white-space:nowrap; }
    .tae-btn--ghost { background:#fff; color:#0453cb; border:1px solid #bfd3f2; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; }
    .tae-ok { display:flex; flex-direction:column; align-items:flex-end; gap:.4rem; font-size:.85rem; color:#047857; font-weight:600; }
    .tae-badge-absent { color:#b91c1c; }
    .tae-vide { background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:2rem; text-align:center; color:#64748b; }
    .tae-alert { border-radius:12px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.88rem; }
    .tae-alert--ok { background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.3); color:#065f46; }
    .tae-alert--ko { background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.25); color:#991b1b; }
    @@media (max-width: 768px) {
        .tae-hero { padding:1.5rem 1.25rem; }
        .tae-cours { grid-template-columns:1fr; }
        .tae-form, .tae-ok { justify-content:flex-start; align-items:flex-start; }
        .tae-code { flex:1; }
    }
</style>
@endpush

@section('content')
@php
    $taeFenetres = app(\App\Domain\EmploiTemps\FenetresDEmargement::class);
    $taeJustification = $taeFenetres->exigeJustification();
    $taeJustifPour = (int) session('justification_requise');
@endphp
<div class="container-fluid">
    <div class="tae-hero">
        <div class="tae-hero-top">
            <div class="tae-hero-left">
                <div class="tae-hero-icon"><i class="fas fa-signature"></i></div>
                <div>
                    <h1>Émargement des cours</h1>
                    <p>{{ \Carbon\Carbon::now()->locale('fr')->isoFormat('dddd D MMMM YYYY') }} — saisissez le code du jour dans la ligne de votre cours.</p>
                </div>
            </div>
            <a href="{{ route('esbtp.teacher.attendance.history') }}" class="tae-btn--glass"><i class="fas fa-clock-rotate-left me-1"></i>Mon historique</a>
        </div>
        <div class="tae-regles">
            <span class="tae-regle">Présent jusqu’à {{ $taeFenetres->minutes(\App\Domain\EmploiTemps\FenetresDEmargement::CLE_PRESENT) }} min après le début</span>
            <span class="tae-regle">En retard jusqu’à {{ $taeFenetres->minutes(\App\Domain\EmploiTemps\FenetresDEmargement::CLE_RETARD) }} min</span>
            <span class="tae-regle">{{ $taeJustification ? 'Au-delà : motif du retard demandé' : 'Au-delà : absence enregistrée' }}</span>
        </div>
    </div>

    @if(session('success'))<div class="tae-alert tae-alert--ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="tae-alert tae-alert--ko">{{ session('error') }}</div>@endif
    @if(session('info'))<div class="tae-alert tae-alert--ok">{{ session('info') }}</div>@endif

    @if($todayCourses->isEmpty())
        <div class="tae-vide"><i class="fas fa-calendar-check me-2"></i>Aucun cours programmé pour vous aujourd’hui.</div>
    @else
        <div class="tae-list">
            @foreach($todayCourses as $course)
                @php
                    $taeDebut = \App\Domain\EmploiTemps\HeureDeSeance::hi($course->getAttributes()['heure_debut'] ?? null);
                    $taeFin = \App\Domain\EmploiTemps\HeureDeSeance::hi($course->getAttributes()['heure_fin'] ?? null);
                    $taeClasse = $course->emploiTemps->classe->name ?? $course->classe->name ?? null;
                    $taeOuverture = $taeDebut ? $taeFenetres->ouvertureDebut(\Carbon\Carbon::today()->setTimeFromTimeString($taeDebut))->format('H:i') : null;
                    $taeEmarge = $course->teacherAttendance;
                @endphp
                <div class="tae-cours">
                    <div>
                        <h3>{{ $course->matiere->name ?? 'Matière non définie' }}</h3>
                        <div class="tae-meta">
                            <span><i class="fas fa-clock"></i>{{ $taeDebut ?? '--:--' }} – {{ $taeFin ?? '--:--' }}</span>
                            <span><i class="fas fa-users"></i>{{ $taeClasse ?? 'Classe non renseignée' }}</span>
                            @if($course->salle)<span><i class="fas fa-door-open"></i>Salle {{ $course->salle }}</span>@endif
                        </div>
                        @if(! $taeEmarge && $taeOuverture)
                            <div class="tae-fenetre">Émargement possible à partir de {{ $taeOuverture }}.</div>
                        @endif
                    </div>

                    @if($taeEmarge)
                        <div class="tae-ok">
                            @if($taeEmarge->status === 'absent')
                                <span class="tae-badge-absent"><i class="fas fa-circle-xmark me-1"></i>Absence enregistrée à {{ optional($taeEmarge->validated_at)->format('H:i') }}</span>
                            @else
                                <span><i class="fas fa-check me-1"></i>{{ $taeEmarge->status === 'late' ? 'Émargé en retard' : 'Émargé' }} à {{ optional($taeEmarge->validated_at)->format('H:i') }}</span>
                                <a href="{{ route('teacher.select-call-type', $course->id) }}" class="tae-btn tae-btn--ghost"><i class="fas fa-list-check"></i>Gérer la séance</a>
                            @endif
                        </div>
                    @else
                        <form action="{{ route('esbtp.teacher.attendance.sign') }}" method="POST" class="tae-form">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $course->id }}">
                            <input type="text" class="tae-code" name="code" required minlength="6" maxlength="6" autocomplete="off"
                                   aria-label="Code d’émargement pour {{ $course->matiere->name ?? 'ce cours' }}"
                                   placeholder="CODE" value="{{ $taeJustifPour === (int) $course->id ? old('code') : '' }}">
                            @if($taeJustification && $taeJustifPour === (int) $course->id)
                                <textarea name="justification" class="tae-just" rows="2" required minlength="5" maxlength="1000"
                                          placeholder="Motif du retard (visible par la coordination)">{{ old('justification') }}</textarea>
                            @endif
                            <button type="submit" class="tae-btn"><i class="fas fa-signature me-1"></i>Émarger</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
