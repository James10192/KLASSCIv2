@extends('layouts.app')
@section('title', $session->libelle)

@push('styles')
<style>
[x-cloak]{display:none !important;}
.rtp-hero{background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;padding:2rem 2.5rem;color:#fff;margin-bottom:1.25rem;}
.rtp-hero h1{margin:0;font-size:1.45rem;}
.rtp-hero p{margin:.3rem 0 0;color:rgba(255,255,255,.75);font-size:.88rem;}
.rtp-meta{display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:1.25rem;}
.rtp-meta-item{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:10px;padding:.6rem 1rem;}
.rtp-meta-label{font-size:.65rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.5px;}
.rtp-meta-value{font-size:.95rem;font-weight:700;color:#fff;margin-top:.2rem;}
.rtp-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem;margin-bottom:1.25rem;}
.rtp-card h2{margin:0 0 1rem;font-size:1rem;color:#1e293b;display:flex;align-items:center;gap:.5rem;}
.rtp-card h2 i{color:#0453cb;}
.rtp-actions{display:flex;gap:.5rem;flex-wrap:wrap;}
.rtp-btn{padding:.55rem 1rem;border-radius:9px;font-size:.82rem;font-weight:600;border:1px solid;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;}
.rtp-btn--primary{background:#0453cb;color:#fff;border-color:#0453cb;}
.rtp-btn--secondary{background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
.rtp-btn--warning{background:rgba(245,158,11,.10);color:#b45309;border-color:rgba(245,158,11,.25);}
.rtp-btn--success{background:rgba(16,185,129,.10);color:#047857;border-color:rgba(16,185,129,.25);}
.rtp-btn:disabled{opacity:.5;cursor:not-allowed;}
.kv-row{display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f1f5f9;font-size:.85rem;}
.kv-row:last-child{border-bottom:none;}
.kv-row>span:first-child{color:#64748b;}
.kv-row>span:last-child{color:#1e293b;font-weight:600;}
.rtp-examen-row{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;background:#f8fafc;border-radius:8px;margin-bottom:.4rem;font-size:.82rem;}
.rtp-examen-row:hover{background:#f1f5f9;}
</style>
@endpush

@section('content')
<div x-data="sessionShow()" x-init="init()">

<div class="rtp-hero">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div>
            <h1>{{ $session->libelle }}</h1>
            <p>
                <span style="text-transform:uppercase;font-weight:600;font-size:.78rem;">{{ $session->type }}</span> ·
                {{ $session->parcours->name ?? 'Tous parcours' }} ·
                {{ $session->semestre ? 'S'.$session->semestre : 'Semestre n/a' }}
            </p>
        </div>
        <a href="{{ route('esbtp.lmd.rattrapage.index', ['annee_universitaire_id' => $session->annee_universitaire_id]) }}" class="rtp-btn" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2);">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <div class="rtp-meta">
        <div class="rtp-meta-item"><div class="rtp-meta-label">Statut</div><div class="rtp-meta-value">{{ str_replace('_',' ', $session->status) }}</div></div>
        <div class="rtp-meta-item"><div class="rtp-meta-label">Période</div><div class="rtp-meta-value">{{ optional($session->date_debut)->format('d/m/Y') }} → {{ optional($session->date_fin)->format('d/m/Y') }}</div></div>
        <div class="rtp-meta-item"><div class="rtp-meta-label">Examens</div><div class="rtp-meta-value">{{ $session->examens->count() }}</div></div>
        @if($session->parentSession)
        <div class="rtp-meta-item"><div class="rtp-meta-label">Session parent</div>
            <div class="rtp-meta-value"><a href="{{ route('esbtp.lmd.rattrapage.show', $session->parentSession) }}" style="color:#fff;text-decoration:underline;">{{ $session->parentSession->libelle }}</a></div></div>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.25rem;">
<div>
    <div class="rtp-card">
        <h2><i class="fas fa-list-check"></i> Examens de la session ({{ $session->examens->count() }})</h2>
        @forelse($session->examens as $exam)
        <div class="rtp-examen-row">
            <div style="flex:1;">
                <div style="font-weight:600;color:#1e293b;">{{ $exam->titre }}</div>
                <div style="color:#64748b;font-size:.72rem;">
                    {{ optional($exam->date_debut)->format('d/m/Y H:i') }} ·
                    {{ $exam->classe->name ?? '—' }} ·
                    Salle {{ $exam->salle ?? '—' }}
                </div>
            </div>
            <a href="{{ route('esbtp.examens.show', $exam) }}" style="padding:.3rem .7rem;border-radius:6px;background:#fff;color:#0453cb;text-decoration:none;font-size:.75rem;font-weight:600;border:1px solid #e2e8f0;">
                <i class="fas fa-eye"></i>
            </a>
        </div>
        @empty
        <div style="padding:1rem;text-align:center;color:#94a3b8;font-size:.85rem;">Aucun examen rattaché à cette session.</div>
        @endforelse
    </div>

    @if($session->childrenSessions->isNotEmpty())
    <div class="rtp-card">
        <h2><i class="fas fa-sitemap"></i> Sessions enfantes ({{ $session->childrenSessions->count() }})</h2>
        @foreach($session->childrenSessions as $child)
        <div class="rtp-examen-row">
            <div style="flex:1;">
                <div style="font-weight:600;">{{ $child->libelle }}</div>
                <div style="color:#64748b;font-size:.72rem;">{{ $child->type }} · {{ $child->status }}</div>
            </div>
            <a href="{{ route('esbtp.lmd.rattrapage.show', $child) }}" style="padding:.3rem .7rem;border-radius:6px;background:#fff;color:#0453cb;text-decoration:none;font-size:.75rem;font-weight:600;border:1px solid #e2e8f0;">
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        @endforeach
    </div>
    @endif
</div>

<div>
    <div class="rtp-card">
        <h2><i class="fas fa-info-circle"></i> Détails</h2>
        <div class="kv-row"><span>Année</span><span>{{ $session->anneeUniversitaire->libelle ?? '—' }}</span></div>
        <div class="kv-row"><span>Créée le</span><span>{{ $session->created_at?->format('d/m/Y') }}</span></div>
        @if($session->published_at)
        <div class="kv-row"><span>Publiée le</span><span>{{ $session->published_at?->format('d/m/Y H:i') }}</span></div>
        @endif

        <div class="rtp-actions" style="margin-top:1rem;">
            @can('lmd.rattrapage.manage')
            @if($session->type === 'normale' && in_array($session->status, ['completed', 'published']))
            <button type="button" class="rtp-btn rtp-btn--warning" @click="lancerRattrapage()" :disabled="busy">
                <i class="fas fa-rotate-right"></i> <span x-text="busy ? 'Lancement…' : 'Lancer rattrapage'"></span>
            </button>
            @endif

            @if($session->type === 'rattrapage')
            <button type="button" class="rtp-btn rtp-btn--primary" @click="recalculer()" :disabled="busy">
                <i class="fas fa-calculator"></i> <span x-text="busy ? 'Recalcul…' : 'Recalculer notes'"></span>
            </button>
            <button type="button" class="rtp-btn rtp-btn--secondary" @click="inscrireTous()" :disabled="busy">
                <i class="fas fa-user-check"></i> Inscrire éligibles
            </button>
            @endif

            @if($session->status !== 'published')
            <button type="button" class="rtp-btn rtp-btn--success" @click="publier()" :disabled="busy">
                <i class="fas fa-flag-checkered"></i> <span x-text="busy ? 'Publication…' : 'Publier'"></span>
            </button>
            @endif
            @endcan
        </div>

        <template x-if="lastResult">
            <div style="margin-top:1rem;padding:.75rem;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:.82rem;color:#075985;">
                <i class="fas fa-circle-info"></i> <span x-text="lastResult"></span>
            </div>
        </template>
    </div>
</div>
</div>

</div>

@push('scripts')
<script>
function sessionShow() {
    return {
        busy: false,
        lastResult: '',
        init(){},
        async post(url, body = {}) {
            this.busy = true;
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || ('Erreur ' + res.status));
                return data;
            } finally { this.busy = false; }
        },
        async lancerRattrapage() {
            try {
                const data = await this.post('{{ route('esbtp.lmd.rattrapage.lancer', $session) }}');
                this.lastResult = `Session rattrapage créée (${data.session_rattrapage.libelle}) — ${data.eligibles_count} éligibles, ${data.examens_count} examens.`;
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Workflow rattrapage lancé.' } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        },
        async recalculer() {
            try {
                const data = await this.post('{{ route('esbtp.lmd.rattrapage.recalculer', $session) }}');
                this.lastResult = `Notes recalculées : ${data.updated_count} ECUE pour ${data.etudiants_count} étudiants.`;
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Notes recalculées.' } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        },
        async inscrireTous() {
            try {
                const data = await this.post('{{ route('esbtp.lmd.rattrapage.inscrire', $session) }}');
                this.lastResult = `${data.inscrits_count} ECUE marqués inscrits en rattrapage.`;
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Inscriptions effectuées.' } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        },
        async publier() {
            if (!confirm('Publier cette session ? Les décisions deviendront officielles.')) return;
            try {
                const data = await this.post('{{ route('esbtp.lmd.rattrapage.publier', $session) }}');
                this.lastResult = 'Session publiée.';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Session publiée.' } }));
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        }
    };
}
</script>
@endpush

@endsection
