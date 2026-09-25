@extends('layouts.app')

@section('title', 'Émargement des cours - KLASSCI')

@push('styles')
<style>
    .tae-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:2rem 2.5rem 1.5rem; color:#fff; margin-bottom:1.25rem; }
    .tae-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
    .tae-hero-left { display:flex; align-items:center; gap:1rem; min-width:0; }
    .tae-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .tae-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0; }
    .tae-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:0; }
    .tae-hero-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
    .tae-btn--glass { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.2); border-radius:10px; padding:.5rem 1rem; font-size:.82rem; font-weight:600; text-decoration:none; white-space:nowrap; }
    .tae-btn--glass:hover { background:rgba(255,255,255,.25); color:#fff; }
    .tae-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:.75rem; margin-top:1.5rem; }
    .tae-kpi { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.85rem 1rem; color:#fff; text-decoration:none; display:block; }
    a.tae-kpi:hover { background:rgba(255,255,255,.18); color:#fff; }
    .tae-kpi-label { font-size:.72rem; color:rgba(255,255,255,.7); text-transform:uppercase; letter-spacing:.05em; font-weight:600; }
    .tae-kpi-value { font-size:clamp(1.25rem,2.2vw,1.55rem); font-weight:700; margin-top:.15rem; white-space:nowrap; font-variant-numeric:tabular-nums; }
    .tae-kpi-rep { font-size:.76rem; color:rgba(255,255,255,.78); margin-top:.15rem; }
    .tae-regles { margin-top:1rem; display:flex; gap:.5rem; flex-wrap:wrap; font-size:.78rem; }
    .tae-regle { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:999px; padding:.3rem .75rem; }

    .tae-layout { display:grid; grid-template-columns:minmax(0,1fr) 320px; gap:1.25rem; align-items:start; }
    .tae-list { display:flex; flex-direction:column; gap:.85rem; }
    .tae-cours { background:#fff; border:1px solid #e2e8f0; border-left:4px solid #94a3b8; border-radius:14px; padding:1.1rem 1.25rem; box-shadow:0 1px 3px rgba(15,23,42,.04); scroll-margin-top:90px; }
    .tae-cours--success { border-left-color:#10b981; }
    .tae-cours--warning { border-left-color:#f59e0b; }
    .tae-cours--danger { border-left-color:#dc2626; }
    .tae-cours--primary { border-left-color:#0453cb; }
    .tae-cours:target { box-shadow:0 0 0 3px rgba(4,83,203,.18),0 8px 30px rgba(4,83,203,.08); }
    .tae-cours-top { display:grid; grid-template-columns:auto minmax(0,1fr) auto; gap:1rem; align-items:center; }
    .tae-horaire { text-align:center; min-width:74px; padding:.45rem .6rem; border-radius:12px; background:#f1f6fe; border:1px solid #dbe7fb; font-variant-numeric:tabular-nums; }
    .tae-horaire strong { display:block; font-size:1.2rem; color:#0453cb; line-height:1.1; }
    .tae-horaire span { font-size:.76rem; color:#64748b; }
    .tae-cours h3 { font-size:1.02rem; font-weight:700; color:#1e293b; margin:0 0 .25rem; }
    .tae-meta { display:flex; gap:1rem; flex-wrap:wrap; font-size:.82rem; color:#64748b; }
    .tae-meta i { color:#0453cb; margin-right:.3rem; }
    .tae-chip { display:inline-flex; align-items:center; gap:.3rem; font-size:.74rem; font-weight:700; border-radius:999px; padding:.25rem .65rem; border:1px solid; white-space:nowrap; }
    .tae-chip--success { color:#047857; background:rgba(16,185,129,.08); border-color:rgba(16,185,129,.3); }
    .tae-chip--warning { color:#b45309; background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.35); }
    .tae-chip--danger { color:#b91c1c; background:rgba(220,38,38,.06); border-color:rgba(220,38,38,.3); }
    .tae-chip--primary { color:#0453cb; background:rgba(4,83,203,.07); border-color:rgba(4,83,203,.25); }
    .tae-chip--muted { color:#64748b; background:#f8fafc; border-color:#e2e8f0; }

    .tae-frise { margin-top:.95rem; }
    .tae-frise-bar { position:relative; display:flex; height:10px; border-radius:999px; overflow:visible; }
    .tae-frise-bar > span { height:100%; }
    .tae-frise-bar > span:first-child { border-radius:999px 0 0 999px; }
    .tae-frise-bar > span:last-of-type { border-radius:0 999px 999px 0; }
    .tae-z-avant { background:#dbe7fb; }
    .tae-z-ok { background:rgba(16,185,129,.55); }
    .tae-z-retard { background:rgba(245,158,11,.6); }
    .tae-z-apres { background:rgba(220,38,38,.35); }
    .tae-maintenant { position:absolute; top:-5px; width:2px; height:20px; background:#0f172a; border-radius:2px; }
    .tae-maintenant::after { content:attr(data-heure); position:absolute; top:-18px; left:50%; transform:translateX(-50%); font-size:.68rem; font-weight:700; color:#0f172a; white-space:nowrap; }
    .tae-frise-legende { position:relative; height:1.1rem; margin-top:.3rem; font-size:.7rem; color:#64748b; font-variant-numeric:tabular-nums; }
    .tae-frise-legende span { position:absolute; transform:translateX(-50%); white-space:nowrap; }
    .tae-frise-legende span:first-child { transform:none; }
    .tae-frise-legende span:last-child { transform:translateX(-100%); }
    .tae-compte { font-size:.8rem; color:#475569; margin-top:.55rem; }
    .tae-compte strong { color:#1e293b; }

    .tae-action { margin-top:.95rem; padding-top:.95rem; border-top:1px dashed #e2e8f0; display:flex; flex-wrap:wrap; gap:.75rem; align-items:center; justify-content:space-between; }
    .tae-form { display:flex; gap:.5rem; align-items:flex-start; flex-wrap:wrap; }
    .tae-code { width:10rem; border:1px solid #cbd5e1; border-radius:10px; padding:.6rem .75rem; font-family:'Courier New',monospace; font-size:1.05rem; font-weight:700; letter-spacing:.25em; text-transform:uppercase; text-align:center; }
    .tae-lien { border:0; background:none; color:#0453cb; font-size:.8rem; font-weight:600; padding:0; cursor:pointer; text-decoration:underline; text-underline-offset:2px; }
    .tae-code:focus, .tae-just:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.15); }
    .tae-just { width:100%; min-width:16rem; flex-basis:100%; border:1px solid #cbd5e1; border-radius:10px; padding:.5rem .75rem; font-size:.85rem; }
    .tae-btn { background:#0453cb; color:#fff; border:none; border-radius:10px; padding:.65rem 1.1rem; font-weight:600; font-size:.85rem; white-space:nowrap; }
    .tae-btn:hover { background:#033a8e; }
    .tae-btn--ghost { background:#fff; color:#0453cb; border:1px solid #bfd3f2; text-decoration:none; display:inline-flex; align-items:center; gap:.4rem; }
    .tae-btn--ghost:hover { background:#eef4fd; color:#033a8e; }
    .tae-ok { display:flex; flex-direction:column; gap:.2rem; font-size:.85rem; color:#047857; font-weight:600; }
    .tae-ok small { color:#64748b; font-weight:500; }
    .tae-badge-absent { color:#b91c1c; }

    .tae-side { display:flex; flex-direction:column; gap:1rem; position:sticky; top:84px; }
    .tae-panel { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1rem 1.1rem; box-shadow:0 1px 3px rgba(15,23,42,.04); }
    .tae-panel h2 { font-size:.95rem; font-weight:700; color:#1e293b; margin:0 0 .75rem; display:flex; align-items:center; gap:.5rem; }
    .tae-panel h2 i { color:#0453cb; }
    .tae-etapes { margin:0; padding:0; list-style:none; display:flex; flex-direction:column; gap:.6rem; counter-reset:tae; }
    .tae-etapes li { counter-increment:tae; display:grid; grid-template-columns:24px minmax(0,1fr); gap:.55rem; font-size:.82rem; color:#475569; }
    .tae-etapes li::before { content:counter(tae); width:24px; height:24px; border-radius:50%; background:rgba(4,83,203,.1); color:#0453cb; font-weight:700; font-size:.74rem; display:flex; align-items:center; justify-content:center; }
    .tae-recent { display:flex; flex-direction:column; }
    .tae-recent-row { display:flex; justify-content:space-between; gap:.6rem; padding:.55rem 0; border-top:1px solid #f1f5f9; font-size:.8rem; }
    .tae-recent-row:first-child { border-top:0; padding-top:0; }
    .tae-recent-row strong { display:block; color:#1e293b; font-size:.83rem; }
    .tae-recent-row small { color:#64748b; }
    .tae-mini-stat { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.4rem; margin-bottom:.75rem; }
    .tae-mini-stat div { border:1px solid #e2e8f0; border-radius:10px; padding:.45rem .5rem; text-align:center; }
    .tae-mini-stat strong { display:block; font-size:1.05rem; color:#1e293b; font-variant-numeric:tabular-nums; }
    .tae-mini-stat span { font-size:.7rem; color:#64748b; }

    .tae-vide { background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:2.25rem 1.5rem; text-align:center; color:#64748b; }
    .tae-vide i { display:block; font-size:1.8rem; color:#94a3b8; margin-bottom:.6rem; }
    .tae-alert { border-radius:12px; padding:.75rem 1rem; margin-bottom:1rem; font-size:.88rem; }
    .tae-alert--ok { background:rgba(16,185,129,.08); border:1px solid rgba(16,185,129,.3); color:#065f46; }
    .tae-alert--ko { background:rgba(220,38,38,.06); border:1px solid rgba(220,38,38,.25); color:#991b1b; }
    @@media (max-width: 1100px) {
        .tae-layout { grid-template-columns:1fr; }
        .tae-side { position:static; }
    }
    @@media (max-width: 768px) {
        .tae-hero { padding:1.35rem 1.1rem 1.1rem; }
        .tae-hero-icon { display:none; }
        .tae-kpis { grid-template-columns:repeat(2,minmax(0,1fr)); gap:.5rem; }
        .tae-cours { padding:1rem; }
        .tae-cours-top { grid-template-columns:auto minmax(0,1fr); }
        .tae-cours-top .tae-chip { grid-column:1 / -1; justify-self:start; }
        .tae-form { width:100%; }
        .tae-code { flex:1; width:auto; }
        .tae-action { flex-direction:column; align-items:stretch; }
    }
</style>
@endpush

@section('content')
@php
    $taeFenetres = app(\App\Domain\EmploiTemps\FenetresDEmargement::class);
    $taeJustification = $taeFenetres->exigeJustification();
    $taeJustifPour = (int) session('justification_requise');
    $taeEmarges = $coursDuJour->filter->estEmarge()->count();
    $taeMaintenant = $coursDuJour->filter->demandeUnEmargement()->count();
    $taeProchain = $coursDuJour->firstWhere('etat', \App\Domain\EmploiTemps\CoursDuJour::A_VENIR);
@endphp
<div class="container-fluid">
    <div class="tae-hero">
        <div class="tae-hero-top">
            <div class="tae-hero-left">
                <div class="tae-hero-icon"><i class="fas fa-signature"></i></div>
                <div>
                    <h1>Émargement des cours</h1>
                    <p>{{ $maintenant->locale('fr')->isoFormat('dddd D MMMM YYYY') }} — saisissez le code du jour dans la ligne de votre cours.</p>
                </div>
            </div>
            <div class="tae-hero-actions">
                <a href="{{ route('teacher.dashboard') }}" class="tae-btn--glass"><i class="fas fa-house me-1"></i>Tableau de bord</a>
                <a href="{{ route('esbtp.teacher.attendance.history') }}" class="tae-btn--glass"><i class="fas fa-clock-rotate-left me-1"></i>Mon historique</a>
            </div>
        </div>
        <div class="tae-kpis">
            <div class="tae-kpi">
                <div class="tae-kpi-label">Cours aujourd’hui</div>
                <div class="tae-kpi-value">{{ $coursDuJour->count() }}</div>
                <div class="tae-kpi-rep">{{ $taeEmarges }} déjà émargé{{ $taeEmarges > 1 ? 's' : '' }}</div>
            </div>
            <div class="tae-kpi">
                <div class="tae-kpi-label">À émarger maintenant</div>
                <div class="tae-kpi-value">{{ $taeMaintenant }}</div>
                <div class="tae-kpi-rep">
                    @if($taeMaintenant === 0 && $taeProchain)
                        Prochain : {{ $taeProchain->ouverture->format('H:i') }}
                    @elseif($taeMaintenant === 0)
                        Rien d’ouvert en ce moment
                    @else
                        fenêtre ouverte
                    @endif
                </div>
            </div>
            <a href="{{ route('esbtp.teacher.attendance.history') }}" class="tae-kpi">
                <div class="tae-kpi-label">À l’heure ce mois</div>
                <div class="tae-kpi-value">{{ $bilan['ponctualite'] === null ? '—' : $bilan['ponctualite'].' %' }}</div>
                <div class="tae-kpi-rep">
                    @if($bilan['ponctualite'] === null)
                        Aucun émargement ce mois
                    @elseif($bilan['precedent']['ponctualite'] === null)
                        {{ $bilan['present'] }} sur {{ $bilan['total'] }} cours
                    @else
                        {{ $bilan['ponctualite'] - $bilan['precedent']['ponctualite'] >= 0 ? '▲ +' : '▼ ' }}{{ $bilan['ponctualite'] - $bilan['precedent']['ponctualite'] }} pts vs {{ $bilan['libelle_precedent'] }}
                    @endif
                </div>
            </a>
        </div>
        <div class="tae-regles">
            <span class="tae-regle">Ouvert {{ $taeFenetres->minutes(\App\Domain\EmploiTemps\FenetresDEmargement::CLE_AVANCE) }} min avant le début</span>
            <span class="tae-regle">Présent jusqu’à {{ $taeFenetres->minutes(\App\Domain\EmploiTemps\FenetresDEmargement::CLE_PRESENT) }} min après</span>
            <span class="tae-regle">En retard jusqu’à {{ $taeFenetres->minutes(\App\Domain\EmploiTemps\FenetresDEmargement::CLE_RETARD) }} min</span>
            <span class="tae-regle">{{ $taeJustification ? 'Au-delà : motif du retard demandé' : 'Au-delà : absence enregistrée' }}</span>
        </div>
    </div>

    @if(session('success'))<div class="tae-alert tae-alert--ok">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="tae-alert tae-alert--ko">{{ session('error') }}</div>@endif
    @if(session('info'))<div class="tae-alert tae-alert--ok">{{ session('info') }}</div>@endif

    <div class="tae-layout">
        <div>
            @if($coursDuJour->isEmpty())
                <div class="tae-vide"><i class="fas fa-calendar-check"></i>Aucun cours programmé pour vous aujourd’hui.</div>
            @else
                <div class="tae-list">
                    @foreach($coursDuJour as $cours)
                        @php
                            $course = $cours->seance;
                            $taeEmarge = $cours->emargementDebut;
                            // Frise du début : de l'ouverture à un quart d'heure après la limite de retard.
                            $taeDe = $cours->ouverture;
                            $taeA = $cours->limiteRetard->copy()->addMinutes(15);
                            $taeSpan = max(1, $taeDe->diffInSeconds($taeA));
                            $taePct = fn ($t) => round(max(0, min(100, $taeDe->diffInSeconds($t, false) * 100 / $taeSpan)), 2);
                            $taeDebutPct = $taePct($cours->debut);
                            $taePresentPct = $taePct($cours->limitePresent);
                            $taeRetardPct = $taePct($cours->limiteRetard);
                            $taeFrise = ! $taeEmarge;
                            $taeEcheance = match ($cours->etat) {
                                \App\Domain\EmploiTemps\CoursDuJour::A_VENIR => [$cours->ouverture, 'ouvre', 'L’émargement ouvre à'],
                                \App\Domain\EmploiTemps\CoursDuJour::OUVERT => [$cours->limitePresent, 'present', 'Présent si vous émargez avant'],
                                \App\Domain\EmploiTemps\CoursDuJour::RETARD => [$cours->limiteRetard, 'retard', 'Retard enregistré — encore possible jusqu’à'],
                                \App\Domain\EmploiTemps\CoursDuJour::FIN_OUVERTE => [$cours->finFermeture, 'fin', 'Émargez la fin avant'],
                                \App\Domain\EmploiTemps\CoursDuJour::EN_COURS => [$cours->finOuverture, 'finouvre', 'Émargement de fin à partir de'],
                                default => null,
                            };
                        @endphp
                        <article class="tae-cours tae-cours--{{ $cours->ton() }}" id="cours-{{ $course->id }}">
                            <div class="tae-cours-top">
                                <div class="tae-horaire"><strong>{{ $cours->debut->format('H:i') }}</strong><span>→ {{ $cours->fin->format('H:i') }}</span></div>
                                <div style="min-width:0">
                                    <h3>{{ $cours->matiere() }}</h3>
                                    <div class="tae-meta">
                                        <span><i class="fas fa-users"></i>{{ $cours->classe() ?? 'Classe non renseignée' }}</span>
                                        @if($cours->salle())<span><i class="fas fa-door-open"></i>Salle {{ $cours->salle() }}</span>@endif
                                    </div>
                                </div>
                                <span class="tae-chip tae-chip--{{ $cours->ton() }}">{{ $cours->libelle() }}</span>
                            </div>

                            @if($taeFrise)
                                <div class="tae-frise" aria-hidden="true">
                                    <div class="tae-frise-bar">
                                        <span class="tae-z-avant" style="width:{{ $taeDebutPct }}%"></span>
                                        <span class="tae-z-ok" style="width:{{ $taePresentPct - $taeDebutPct }}%"></span>
                                        <span class="tae-z-retard" style="width:{{ $taeRetardPct - $taePresentPct }}%"></span>
                                        <span class="tae-z-apres" style="width:{{ 100 - $taeRetardPct }}%"></span>
                                        @if($maintenant->between($taeDe, $taeA))
                                            <i class="tae-maintenant" style="left:{{ $taePct($maintenant) }}%" data-heure="{{ $maintenant->format('H:i') }}"></i>
                                        @endif
                                    </div>
                                    <div class="tae-frise-legende">
                                        <span style="left:0">{{ $cours->ouverture->format('H:i') }}</span>
                                        <span style="left:{{ $taeDebutPct }}%">{{ $cours->debut->format('H:i') }}</span>
                                        <span style="left:{{ $taePresentPct }}%">{{ $cours->limitePresent->format('H:i') }}</span>
                                        <span style="left:{{ $taeRetardPct }}%">{{ $cours->limiteRetard->format('H:i') }}</span>
                                    </div>
                                </div>
                            @endif

                            @if($taeEcheance)
                                <div class="tae-compte" data-tae-echeance="{{ $taeEcheance[0]->valueOf() }}" data-tae-sens="{{ $taeEcheance[1] }}">
                                    {{ $taeEcheance[2] }} <strong>{{ $taeEcheance[0]->format('H:i') }}</strong>.
                                    <span class="tae-reste"></span>
                                </div>
                            @endif

                            <div class="tae-action">
                                @if($taeEmarge && $cours->etat !== \App\Domain\EmploiTemps\CoursDuJour::FIN_OUVERTE)
                                    <div class="tae-ok">
                                        @if($taeEmarge->status === 'absent')
                                            <span class="tae-badge-absent"><i class="fas fa-circle-xmark me-1"></i>Absence enregistrée à {{ $taeEmarge->validated_at?->format('H:i') }}</span>
                                        @else
                                            <span><i class="fas fa-check me-1"></i>{{ $taeEmarge->status === 'late' ? 'Émargé en retard' : 'Émargé' }} à {{ $taeEmarge->validated_at?->format('H:i') }}</span>
                                            @if($cours->emargementFin)
                                                <small>Fin émargée à {{ $cours->emargementFin->validated_at?->format('H:i') }}</small>
                                            @endif
                                        @endif
                                    </div>
                                    @if($taeEmarge->status !== 'absent')
                                        <a href="{{ route('teacher.select-call-type', $course->id) }}" class="tae-btn tae-btn--ghost"><i class="fas fa-list-check"></i>{{ $cours->appelAFaire() ? 'Faire l’appel' : 'Gérer la séance' }}</a>
                                    @endif
                                @else
                                    <form action="{{ route('esbtp.teacher.attendance.sign') }}" method="POST" class="tae-form">
                                        @csrf
                                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                                        <input type="text" class="tae-code" name="code" required minlength="6" maxlength="6" autocomplete="off"
                                               aria-label="Code d’émargement pour {{ $cours->matiere() }}"
                                               placeholder="CODE" value="{{ $taeJustifPour === (int) $course->id ? old('code') : '' }}">
                                        @if($taeJustification && $taeJustifPour === (int) $course->id)
                                            <textarea name="justification" class="tae-just" rows="2" required minlength="5" maxlength="1000"
                                                      placeholder="Motif du retard (visible par la coordination)">{{ old('justification') }}</textarea>
                                        @endif
                                        <button type="submit" class="tae-btn"><i class="fas fa-signature me-1"></i>{{ $cours->etat === \App\Domain\EmploiTemps\CoursDuJour::FIN_OUVERTE ? 'Émarger la fin' : 'Émarger' }}</button>
                                    </form>
                                    @if(! $taeEmarge)
                                        <form action="{{ route('esbtp.teacher-attendance.demander-code') }}" method="POST" class="tae-demande"
                                              x-data="{ envoi: false, reponse: null }"
                                              @submit.prevent="
                                                  envoi = true;
                                                  fetch($el.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: new FormData($el) })
                                                      .then(async r => { const d = await r.json().catch(() => ({ message: 'Erreur ' + r.status })); reponse = d; window.dispatchEvent(new CustomEvent('toast', { detail: { type: r.ok ? (d.type === 'info' ? 'info' : 'success') : 'error', message: d.message } })); })
                                                      .catch(() => { reponse = { message: 'La demande n’a pas pu être envoyée. Réessayez.' }; })
                                                      .finally(() => envoi = false);
                                              ">
                                            @csrf
                                            <input type="hidden" name="course_id" value="{{ $course->id }}">
                                            <button type="submit" class="tae-lien" x-show="!reponse || !reponse.success" :disabled="envoi"><i class="fas fa-bell me-1"></i>Je n’ai pas le code — le demander à la coordination</button>
                                            <span class="tae-lien" x-show="reponse" x-cloak x-text="reponse && reponse.message"></span>
                                        </form>
                                    @else
                                        <a href="{{ route('teacher.select-call-type', $course->id) }}" class="tae-btn tae-btn--ghost"><i class="fas fa-list-check"></i>Gérer la séance</a>
                                    @endif
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>

        <aside class="tae-side">
            <section class="tae-panel">
                <h2><i class="fas fa-key"></i>Où trouver le code</h2>
                <ol class="tae-etapes">
                    <li>Le code du jour est affiché en salle des professeurs. Il change chaque jour.</li>
                    <li>Saisissez-le dans la ligne du cours, dès l’ouverture de l’émargement.</li>
                    <li>Pas de code sous la main ? Demandez-le à la coordination depuis la ligne du cours.</li>
                </ol>
            </section>
            <section class="tae-panel">
                <h2><i class="fas fa-clock-rotate-left"></i>Récemment</h2>
                <div class="tae-mini-stat">
                    <div><strong>{{ $bilan['present'] }}</strong><span>À l’heure</span></div>
                    <div><strong>{{ $bilan['retard'] }}</strong><span>En retard</span></div>
                    <div><strong>{{ $bilan['absent'] }}</strong><span>Absences</span></div>
                </div>
                @if($derniers->isEmpty())
                    <div class="tae-meta">Aucun émargement enregistré pour l’instant.</div>
                @else
                    <div class="tae-recent">
                        @foreach($derniers as $em)
                            @php
                                $taeTon = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger'][$em->status] ?? 'muted';
                                $taeLib = ['present' => 'À l’heure', 'late' => 'Retard', 'absent' => 'Absent'][$em->status] ?? ucfirst((string) $em->status);
                            @endphp
                            <div class="tae-recent-row">
                                <div style="min-width:0">
                                    <strong>{{ $em->course->matiere->name ?? 'Cours supprimé' }}</strong>
                                    <small>{{ $em->date?->locale('fr')->isoFormat('ddd D MMM') }}{{ $em->validated_at ? ' · '.$em->validated_at->format('H:i') : '' }}</small>
                                </div>
                                <span class="tae-chip tae-chip--{{ $taeTon }}">{{ $taeLib }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
                <div style="margin-top:.75rem"><a href="{{ route('esbtp.teacher.attendance.history') }}" class="tae-lien">Tout l’historique</a></div>
            </section>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function libelle(ms) {
        var min = Math.round(ms / 60000);
        if (min <= 0) return '';
        if (min < 60) return min + ' min';
        return Math.floor(min / 60) + ' h ' + String(min % 60).padStart(2, '0');
    }
    function majComptes() {
        document.querySelectorAll('[data-tae-echeance]').forEach(function (el) {
            var reste = Number(el.dataset.taeEcheance) - Date.now();
            var cible = el.querySelector('.tae-reste');
            if (!cible) return;
            var txt = libelle(reste);
            cible.textContent = txt ? '(dans ' + txt + ')' : '';
        });
    }
    majComptes();
    setInterval(majComptes, 30000);
})();
</script>
@endpush
