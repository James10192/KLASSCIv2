@extends('layouts.app')

@section('title', 'Validation TPE')

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════
   Validation TPE — vue enseignant
   Namespace : tv-* (TPE Validation)
   ═══════════════════════════════════════════════════════════ */
.tv-page { max-width: 1200px; margin: 0 auto; }

.tv-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.tv-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.tv-hero-left { display: flex; align-items: center; gap: 1rem; }
.tv-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.tv-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.tv-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

.tv-kpis {
    display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
}
.tv-kpi {
    flex: 1; min-width: 160px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.tv-kpi-icon {
    width: 36px; height: 36px; border-radius: 9px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem;
}
.tv-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.1; }
.tv-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

.tv-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.tv-card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .75rem;
}
.tv-card-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff;
}
.tv-card-title { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0; }
.tv-card-subtitle { font-size: .78rem; color: #64748b; margin: 0; }

.tv-decl-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr auto auto;
    gap: 1rem;
    align-items: center;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f8fafc;
}
.tv-decl-row:last-child { border-bottom: none; }
.tv-decl-etudiant {
    display: flex; align-items: center; gap: .65rem;
}
.tv-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700;
    font-size: .85rem;
    flex-shrink: 0;
}
.tv-decl-name { font-weight: 600; color: #1e293b; font-size: .9rem; }
.tv-decl-meta { font-size: .75rem; color: #64748b; margin-top: .15rem; }
.tv-decl-heures {
    font-weight: 700; color: #0453cb; font-size: 1.05rem;
    background: rgba(4,83,203,.06); padding: .35rem .7rem; border-radius: 8px;
    display: inline-block;
}

.tv-actions { display: flex; gap: .5rem; }
.tv-btn--validate {
    background: #10b981; color: #fff; border: none;
    padding: .45rem .85rem; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    cursor: pointer; transition: background .15s;
    display: inline-flex; align-items: center; gap: .4rem;
}
.tv-btn--validate:hover { background: #047857; }
.tv-btn--reject {
    background: transparent; color: #dc2626; border: 1px solid #fecaca;
    padding: .45rem .85rem; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    cursor: pointer; transition: background .15s;
    display: inline-flex; align-items: center; gap: .4rem;
}
.tv-btn--reject:hover { background: #fef2f2; }

.tv-empty {
    text-align: center; padding: 3rem 1rem; color: #64748b;
}
.tv-empty-icon {
    font-size: 2.4rem; color: #cbd5e1; margin-bottom: .75rem;
}

/* Reject modal */
.tv-modal-bg {
    position: fixed; inset: 0; background: rgba(15,23,42,.55);
    z-index: 1050; display: flex; align-items: center; justify-content: center;
}
.tv-modal {
    background: #fff; border-radius: 14px;
    box-shadow: 0 8px 30px rgba(15,23,42,.18);
    max-width: 500px; width: 90%;
    overflow: hidden;
}
.tv-modal-header {
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #b91c1c, #dc2626);
    color: #fff;
    display: flex; align-items: center; gap: .65rem;
}
.tv-modal-body { padding: 1.25rem; }
.tv-modal-footer {
    padding: .85rem 1.25rem;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    display: flex; justify-content: flex-end; gap: .5rem;
}
.tv-input {
    width: 100%; padding: .65rem .9rem;
    border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: .9rem; resize: vertical; min-height: 90px;
}
.tv-input:focus {
    outline: none; border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,.12);
}

[x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="tv-page" x-data="{
    showReject: false,
    rejectId: null,
    rejectAction: '',
    openReject(id, action) { this.showReject = true; this.rejectId = id; this.rejectAction = action; }
}">

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- ═══ Hero ═══ --}}
    <div class="tv-hero">
        <div class="tv-hero-top">
            <div class="tv-hero-left">
                <div class="tv-hero-icon"><i class="fas fa-check-double"></i></div>
                <div>
                    <h1>Validation TPE</h1>
                    <p>Validez les heures de Travail Personnel déclarées par vos étudiants</p>
                </div>
            </div>
        </div>

        <div class="tv-kpis">
            <div class="tv-kpi">
                <div class="tv-kpi-icon"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="tv-kpi-value">{{ $kpis['en_attente'] }}</div>
                    <div class="tv-kpi-label">En attente de validation</div>
                </div>
            </div>
            <div class="tv-kpi">
                <div class="tv-kpi-icon"><i class="fas fa-check-circle"></i></div>
                <div>
                    <div class="tv-kpi-value">{{ $kpis['validees_semaine'] }}</div>
                    <div class="tv-kpi-label">Validées cette semaine</div>
                </div>
            </div>
            <div class="tv-kpi">
                <div class="tv-kpi-icon"><i class="fas fa-times-circle"></i></div>
                <div>
                    <div class="tv-kpi-value">{{ $kpis['rejetees_mois'] }}</div>
                    <div class="tv-kpi-label">Rejetées ce mois</div>
                </div>
            </div>
            <div class="tv-kpi">
                <div class="tv-kpi-icon"><i class="fas fa-book"></i></div>
                <div>
                    <div class="tv-kpi-value">{{ $myEcues->count() }}</div>
                    <div class="tv-kpi-label">Mes ECUEs</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Liste déclarations en attente ═══ --}}
    <div class="tv-card">
        <div class="tv-card-header">
            <div class="tv-card-icon"><i class="fas fa-list-check"></i></div>
            <div>
                <p class="tv-card-title">Déclarations en attente</p>
                <p class="tv-card-subtitle">
                    Triées par date de soumission, plus récentes en premier
                </p>
            </div>
        </div>

        @if ($declarations->isEmpty())
            <div class="tv-empty">
                <div class="tv-empty-icon"><i class="fas fa-circle-check"></i></div>
                <div><strong>Aucune déclaration en attente.</strong></div>
                <div style="margin-top: .5rem; font-size: .85rem;">
                    Tout est à jour. Les nouvelles déclarations apparaîtront ici.
                </div>
            </div>
        @else
            @foreach ($declarations as $decl)
                @php
                    $initial = mb_strtoupper(mb_substr($decl->etudiant->prenoms ?? $decl->etudiant->nom ?? '?', 0, 1, 'UTF-8'), 'UTF-8');
                    $nomComplet = trim(($decl->etudiant->prenoms ?? '') . ' ' . ($decl->etudiant->nom ?? '')) ?: 'Étudiant inconnu';
                    $semaine = optional($decl->semaine_debut)->isoFormat('D MMM YYYY');
                @endphp
                <div class="tv-decl-row">
                    <div class="tv-decl-etudiant">
                        <div class="tv-avatar">{{ $initial }}</div>
                        <div>
                            <div class="tv-decl-name">{{ $nomComplet }}</div>
                            <div class="tv-decl-meta">
                                {{ $decl->matiere->name ?? 'Matière supprimée' }}
                                @if ($decl->matiere && $decl->matiere->uniteEnseignement)
                                    · {{ $decl->matiere->uniteEnseignement->name }}
                                @endif
                            </div>
                            @if ($decl->description)
                                <div class="tv-decl-meta" style="margin-top:.35rem; max-width: 480px;">
                                    <em>{{ \Illuminate\Support\Str::limit($decl->description, 200) }}</em>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="tv-decl-meta">Semaine du</div>
                        <div style="font-weight: 600; color: #1e293b; font-size: .88rem;">{{ $semaine ?: '—' }}</div>
                    </div>
                    <div class="tv-decl-heures">
                        {{ number_format((float) $decl->heures, 2, ',', ' ') }}h
                    </div>
                    <div class="tv-actions">
                        <form method="POST" action="{{ route('esbtp.tpe-validation.validate', $decl) }}" style="display:inline;">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="tv-btn--validate" title="Valider">
                                <i class="fas fa-check"></i>Valider
                            </button>
                        </form>
                        <button type="button" class="tv-btn--reject"
                                @click="openReject({{ $decl->id }}, '{{ route('esbtp.tpe-validation.reject', $decl) }}')"
                                title="Rejeter">
                            <i class="fas fa-times"></i>Rejeter
                        </button>
                    </div>
                </div>
            @endforeach

            @if (method_exists($declarations, 'links'))
                <div style="padding: 1rem 1.25rem;">
                    {{ $declarations->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- ═══ Modal rejet (Alpine) ═══ --}}
    <template x-if="showReject">
        <div class="tv-modal-bg" @click.self="showReject = false" x-cloak>
            <form method="POST" :action="rejectAction" class="tv-modal" @keydown.escape.window="showReject = false">
                @csrf
                @method('PATCH')
                <div class="tv-modal-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Rejeter la déclaration</strong>
                </div>
                <div class="tv-modal-body">
                    <label class="tj-form-label" style="font-size:.75rem; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.5px; display:block; margin-bottom:.4rem;">
                        Motif du rejet (5-500 caractères)
                    </label>
                    <textarea name="commentaire_rejet" class="tv-input" minlength="5" maxlength="500"
                              placeholder="Ex: Heures déclarées trop élevées par rapport au volume attendu, manque de détail dans la description..."
                              required></textarea>
                    <div style="margin-top:.65rem; font-size:.78rem; color:#64748b;">
                        L'étudiant recevra ce motif par notification.
                    </div>
                </div>
                <div class="tv-modal-footer">
                    <button type="button" class="tj-btn--ghost" @click="showReject = false">Annuler</button>
                    <button type="submit" class="tv-btn--reject" style="font-size:.85rem;">
                        <i class="fas fa-times"></i>Confirmer le rejet
                    </button>
                </div>
            </form>
        </div>
    </template>
</div>
@endsection
