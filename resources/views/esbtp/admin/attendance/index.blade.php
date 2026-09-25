@extends('layouts.app')

@section('title', 'Émargements des enseignants - KLASSCI')

@push('styles')
<style>
    .aem-hero { background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%); border-radius:18px; padding:2rem 2.5rem 1.5rem; color:#fff; margin-bottom:1.25rem; }
    .aem-hero-top { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
    .aem-hero-left { display:flex; align-items:center; gap:1rem; }
    .aem-hero-icon { width:52px; height:52px; border-radius:14px; background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center; font-size:1.35rem; flex-shrink:0; }
    .aem-hero h1 { font-size:1.45rem; font-weight:700; color:#fff; margin:0; }
    .aem-hero p { color:rgba(255,255,255,.75); font-size:.88rem; margin:0; }
    .aem-actions { display:flex; gap:.5rem; flex-wrap:wrap; }
    .aem-btn--glass { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.2); border-radius:10px; padding:.5rem 1rem; font-size:.82rem; font-weight:600; text-decoration:none; }
    .aem-btn--glass:hover { background:rgba(255,255,255,.25); color:#fff; }
    .aem-kpis { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:.75rem; margin-top:1.5rem; }
    .aem-kpi { background:rgba(255,255,255,.1); border:1px solid rgba(255,255,255,.15); border-radius:12px; padding:.9rem 1rem; text-decoration:none; color:#fff; }
    .aem-kpi:hover { background:rgba(255,255,255,.16); color:#fff; }
    .aem-kpi-value { font-size:1.35rem; font-weight:700; white-space:nowrap; }
    .aem-kpi-label { font-size:.72rem; color:rgba(255,255,255,.72); }
    .aem-grid { display:grid; grid-template-columns:minmax(260px,320px) minmax(0,1fr); gap:1.25rem; align-items:start; }
    .aem-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04); padding:1.25rem; }
    .aem-card h2 { font-size:1rem; font-weight:700; color:#1e293b; margin:0 0 1rem; display:flex; align-items:center; gap:.5rem; }
    .aem-card h2 i { color:#0453cb; }
    .aem-code { font-family:'Courier New',monospace; font-size:2.2rem; font-weight:700; letter-spacing:.3em; color:#0453cb; text-align:center; margin:.25rem 0; }
    .aem-sub { text-align:center; font-size:.82rem; color:#64748b; }
    .aem-btn { border-radius:10px; padding:.55rem 1rem; font-size:.84rem; font-weight:600; border:1px solid transparent; width:100%; margin-top:1rem; }
    .aem-btn--primary { background:#0453cb; color:#fff; }
    .aem-btn--danger { background:#fff; color:#dc2626; border-color:rgba(220,38,38,.35); }
    .aem-btn:disabled { opacity:.55; }
    .aem-stats { display:flex; justify-content:space-around; margin-top:1rem; font-size:.78rem; color:#64748b; text-align:center; }
    .aem-stats strong { display:block; font-size:1.05rem; color:#1e293b; }
    .aem-table { width:100%; border-collapse:collapse; font-size:.86rem; }
    .aem-table th { font-size:.72rem; text-transform:uppercase; letter-spacing:.4px; color:#64748b; font-weight:700; padding:.5rem .6rem; border-bottom:1px solid #e2e8f0; text-align:left; }
    .aem-table td { padding:.7rem .6rem; border-bottom:1px solid #f1f5f9; vertical-align:top; color:#1e293b; }
    .aem-badge { display:inline-block; border-radius:999px; padding:.2rem .65rem; font-size:.74rem; font-weight:700; white-space:nowrap; }
    .aem-badge--present { background:rgba(16,185,129,.12); color:#047857; }
    .aem-badge--late { background:rgba(245,158,11,.14); color:#92400e; }
    .aem-badge--absent { background:rgba(220,38,38,.1); color:#b91c1c; }
    .aem-muted { color:#64748b; font-size:.8rem; }
    .aem-just { font-size:.8rem; color:#475569; margin-top:.25rem; font-style:italic; }
    .aem-vide { text-align:center; padding:1.5rem; color:#64748b; }
    .aem-scroll { overflow-x:auto; }
    @@media (max-width: 992px) { .aem-grid { grid-template-columns:1fr; } }
    @@media (max-width: 576px) { .aem-hero { padding:1.5rem 1.25rem; } }
</style>
@endpush

@section('content')
@php
    $aemEmarges = $todayAttendances->where('type', '!=', 'end');
    $aemPresents = $aemEmarges->where('status', 'present')->count();
    $aemRetards = $aemEmarges->where('status', 'late')->count();
    $aemAbsents = $aemEmarges->where('status', 'absent')->count();
    $aemCodeActif = $dailyCode && $dailyCode->isValid();
    $aemStatut = ['present' => 'À l’heure', 'late' => 'En retard', 'absent' => 'Absent'];
@endphp
<div class="container-fluid" x-data="{
        code: @js($aemCodeActif ? $dailyCode->code : null),
        codeId: @js($aemCodeActif ? $dailyCode->id : null),
        jusqua: @js($aemCodeActif ? $dailyCode->valid_until->format('H:i') : null),
        envoi: false, confirmer: false,
        entetes() { return { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }; },
        toast(type, message) { window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } })); },
        async generer() {
            this.envoi = true;
            try {
                const r = await fetch(@js(route('esbtp.admin.attendance.generate-code')), { method: 'POST', headers: this.entetes() });
                const d = await r.json().catch(() => ({}));
                if (!r.ok || !d.success) throw new Error(d.message || 'Génération impossible.');
                this.code = d.code; this.jusqua = (d.valid_until || '').substring(11, 16); this.codeId = d.id || null;
                this.toast('success', 'Code du jour généré : ' + d.code);
            } catch (e) { this.toast('error', e.message); } finally { this.envoi = false; }
        },
        async annuler() {
            if (!this.codeId) return;
            this.envoi = true;
            try {
                const r = await fetch(@js(url('esbtp/admin/attendance/cancel-code')) + '/' + this.codeId, { method: 'POST', headers: this.entetes() });
                const d = await r.json().catch(() => ({}));
                if (!r.ok || !d.success) throw new Error(d.message || 'Annulation impossible.');
                this.code = null; this.codeId = null; this.confirmer = false;
                this.toast('success', 'Code annulé : les enseignants ne peuvent plus l’utiliser.');
            } catch (e) { this.toast('error', e.message); } finally { this.envoi = false; }
        }
    }">
    <div class="aem-hero">
        <div class="aem-hero-top">
            <div class="aem-hero-left">
                <div class="aem-hero-icon"><i class="fas fa-signature"></i></div>
                <div>
                    <h1>Émargements des enseignants</h1>
                    <p>{{ \Carbon\Carbon::parse($date ?? now())->locale('fr')->isoFormat('dddd D MMMM YYYY') }} — code du jour, émargements et demandes de prolongation.</p>
                </div>
            </div>
            <div class="aem-actions">
                @can('emargement.prolongation.decide')
                    <a href="{{ route('esbtp.prolongations.index') }}" class="aem-btn--glass"><i class="fas fa-clock-rotate-left me-1"></i>Prolongations</a>
                @endcan
                @can('attendances.view')
                    <a href="{{ route('esbtp.teacher-attendance.report') }}" class="aem-btn--glass"><i class="fas fa-chart-column me-1"></i>Rapport et heures</a>
                @endcan
            </div>
        </div>
        <div class="aem-kpis">
            <div class="aem-kpi"><div class="aem-kpi-value">{{ $aemEmarges->count() }}</div><div class="aem-kpi-label">Émargements de début</div></div>
            <div class="aem-kpi"><div class="aem-kpi-value">{{ $aemPresents }}</div><div class="aem-kpi-label">À l’heure</div></div>
            <div class="aem-kpi"><div class="aem-kpi-value">{{ $aemRetards }}</div><div class="aem-kpi-label">En retard</div></div>
            <div class="aem-kpi"><div class="aem-kpi-value">{{ $aemAbsents }}</div><div class="aem-kpi-label">Absences enregistrées</div></div>
            @can('emargement.prolongation.decide')
                <a class="aem-kpi" href="{{ route('esbtp.prolongations.index') }}"><div class="aem-kpi-value">{{ $prolongationsEnAttente ?? 0 }}</div><div class="aem-kpi-label">Prolongations à décider</div></a>
            @endcan
        </div>
    </div>

    <div class="aem-grid">
        <div class="aem-card">
            <h2><i class="fas fa-key"></i>Code du jour</h2>
            <template x-if="code">
                <div>
                    <div class="aem-code" x-text="code"></div>
                    <div class="aem-sub">Valide jusqu’à <strong x-text="jusqua"></strong>. À afficher en salle des professeurs.</div>
                    @if($codeStats && $aemCodeActif)
                        <div class="aem-stats">
                            <div><strong>{{ $codeStats['total'] }}</strong>tentatives</div>
                            <div><strong>{{ $codeStats['successful'] }}</strong>réussies</div>
                            <div><strong>{{ $codeStats['success_rate'] }} %</strong>succès</div>
                        </div>
                    @endif
                    <button type="button" class="aem-btn aem-btn--danger" x-show="!confirmer" x-on:click="confirmer = true" :disabled="envoi || !codeId">Annuler ce code</button>
                    <div x-show="confirmer" x-cloak>
                        <p class="aem-sub" style="margin-top:1rem">Les enseignants ne pourront plus émarger avec ce code.</p>
                        <button type="button" class="aem-btn aem-btn--danger" x-on:click="annuler()" :disabled="envoi">Confirmer l’annulation</button>
                        <button type="button" class="aem-btn" style="background:#fff;border-color:#e2e8f0;margin-top:.5rem" x-on:click="confirmer = false">Garder le code</button>
                    </div>
                </div>
            </template>
            <template x-if="!code">
                <div>
                    <p class="aem-sub">Aucun code actif : les enseignants ne peuvent pas émarger.</p>
                    <button type="button" class="aem-btn aem-btn--primary" x-on:click="generer()" :disabled="envoi">
                        <span x-show="!envoi">Générer le code du jour</span><span x-show="envoi" x-cloak>Génération…</span>
                    </button>
                </div>
            </template>
        </div>

        <div class="aem-card">
            <h2><i class="fas fa-list-check"></i>Émargements du jour</h2>
            @if($todayAttendances->isEmpty())
                <div class="aem-vide">Aucun émargement pour l’instant.</div>
            @else
                <div class="aem-scroll">
                    <table class="aem-table">
                        <thead>
                            <tr><th>Enseignant</th><th>Cours</th><th>Moment</th><th>Heure</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            @foreach($todayAttendances as $attendance)
                                <tr>
                                    <td>{{ $attendance->teacher->name ?? '—' }}</td>
                                    <td>
                                        {{ $attendance->course->matiere->name ?? 'Cours supprimé' }}
                                        <div class="aem-muted">{{ $attendance->course->emploiTemps->classe->name ?? $attendance->course->classe->name ?? '' }}</div>
                                    </td>
                                    <td>{{ $attendance->type === 'end' ? 'Fin' : 'Début' }}</td>
                                    <td>{{ optional($attendance->validated_at ?? $attendance->created_at)->format('H:i') }}</td>
                                    <td>
                                        <span class="aem-badge aem-badge--{{ $attendance->status }}">{{ $aemStatut[$attendance->status] ?? $attendance->status }}</span>
                                        @if($attendance->status === 'late' && $attendance->minutes_retard)
                                            <div class="aem-muted">+{{ $attendance->minutes_retard }} min</div>
                                        @endif
                                        @if($attendance->justification)
                                            <div class="aem-just">« {{ $attendance->justification }} »</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
