@extends('layouts.app')

@section('title', 'Ma caisse - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.mc { max-width: 1200px; }
.mc-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: 12px; margin-bottom: 16px; }
.mc-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:16px; }
.mc-kpi strong { display:block; font-size:1.35rem; color:#0f172a; }
.mc-kpi span { color:#64748b; font-size:.78rem; text-transform:uppercase; letter-spacing:.04em; }
.mc-grid { display:grid; grid-template-columns: 1.2fr .8fr; gap:16px; }
.mc-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:18px; }
.mc-card h2 { font-size:1rem; font-weight:700; margin:0 0 12px; color:#0f172a; }
.mc-mode { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f1f5f9; }
.mc-badge { display:inline-flex; padding:4px 10px; border-radius:999px; font-size:.75rem; font-weight:700; }
.mc-badge.open { background:#ecfdf5; color:#047857; }
.mc-badge.closed { background:#e2e8f0; color:#334155; }
.mc-badge.auto { background:#fff7ed; color:#9a3412; }
.mc-actions { display:flex; gap:8px; flex-wrap:wrap; }
@media (max-width: 900px) { .mc-kpis, .mc-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')
@php
    $locked = $session->isLocked();
    $statusClass = $session->status->value === 'open' ? 'open' : ($session->status->value === 'auto_closed' ? 'auto' : 'closed');
@endphp
<div class="main-content">
<div class="mc">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 style="font-size:1.4rem;font-weight:800;margin:0;">Ma caisse</h1>
            <div style="color:#64748b;">{{ $session->business_date->isoFormat('dddd D MMMM YYYY') }}</div>
        </div>
        <div class="mc-actions">
            <a class="btn-acasi secondary" href="{{ route('esbtp.caisse.bordereau', ['preview' => 1]) }}" target="_blank">
                <i class="fas fa-file-pdf me-1"></i>Bordereau du jour
            </a>
            @unless($locked)
            <button type="button" class="btn-acasi primary" onclick="document.getElementById('mc-close').showModal()">
                <i class="fas fa-lock me-1"></i>Clôturer ma journée
            </button>
            @endunless
            <a class="btn-acasi secondary" href="{{ route('esbtp.paiements.create') }}">
                <i class="fas fa-plus me-1"></i>Encaisser
            </a>
        </div>
    </div>

    <div class="mc-kpis">
        <div class="mc-kpi"><span>Encaissé aujourd'hui</span><strong>{{ number_format($aggregat['total'], 0, ',', ' ') }} F</strong></div>
        <div class="mc-kpi"><span>Dont espèces</span><strong>{{ number_format($aggregat['especes'], 0, ',', ' ') }} F</strong></div>
        <div class="mc-kpi"><span>Versements validés</span><strong>{{ $aggregat['count'] }}</strong></div>
        <div class="mc-kpi"><span>État</span><strong><span class="mc-badge {{ $statusClass }}">{{ $session->status->label() }}</span></strong></div>
    </div>

    <div class="mc-grid">
        <div class="mc-card">
            <h2>Par mode de paiement</h2>
            @forelse($aggregat['par_mode'] as $mode)
                <div class="mc-mode">
                    <span>{{ $mode['label'] }} · {{ $mode['count'] }}</span>
                    <strong>{{ number_format($mode['total'], 0, ',', ' ') }} F</strong>
                </div>
            @empty
                <p style="color:#94a3b8;">Aucun encaissement validé aujourd'hui.</p>
            @endforelse
            <p class="mt-3 mb-0" style="color:#64748b;font-size:.82rem;">Wave, OM, virement et chèque ne passent pas par le tiroir. Seules les espèces se comptent à la clôture.</p>
        </div>
        <div class="mc-card">
            <h2>Clôture</h2>
            @if($locked)
                <p>Attendu (espèces) : <strong>{{ number_format((float) $session->expected_amount, 0, ',', ' ') }} F</strong></p>
                <p>Compté : <strong>{{ $session->counted_amount !== null ? number_format((float) $session->counted_amount, 0, ',', ' ').' F' : 'inconnu' }}</strong></p>
                <p>Écart : <strong>
                    @if($session->variance === null) Inconnu
                    @elseif((float)$session->variance == 0) Juste
                    @elseif((float)$session->variance < 0) Manquant {{ number_format(abs((float)$session->variance), 0, ',', ' ') }} F
                    @else Excédent {{ number_format((float)$session->variance, 0, ',', ' ') }} F
                    @endif
                </strong></p>
                @if($session->notes)<p style="color:#64748b;">{{ $session->notes }}</p>@endif
            @else
                <p>Le tiroir théorique contient <strong>{{ number_format($aggregat['especes'], 0, ',', ' ') }} F</strong> d'espèces validées.</p>
                <p style="color:#64748b;">Comptez le tiroir sans recopier ce montant. Un écart n'empêche pas de clôturer.</p>
            @endif
        </div>
    </div>

    <div class="mc-card mt-3">
        <h2>Détail du jour</h2>
        <div class="table-responsive">
            <table class="table-modern">
                <thead><tr><th>Heure</th><th>Étudiant</th><th>Mode</th><th>Statut</th><th class="text-end">Montant</th></tr></thead>
                <tbody>
                @forelse($paiements as $p)
                    <tr>
                        <td>{{ $p->created_at?->format('H:i') }}</td>
                        <td>{{ $p->etudiant->nom ?? '—' }} {{ $p->etudiant->prenoms ?? '' }}</td>
                        <td>{{ $p->mode_paiement }}</td>
                        <td>{{ $p->status }}</td>
                        <td class="text-end">{{ number_format((float)$p->montant, 0, ',', ' ') }} F</td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="color:#94a3b8;">Aucun versement.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

@unless($locked)
<dialog id="mc-close" style="border:none;border-radius:16px;padding:0;max-width:440px;width:calc(100% - 32px);">
    <form method="POST" action="{{ route('esbtp.caisse.cloturer') }}" style="padding:20px;">
        @csrf
        <h3 style="margin-top:0;">Clôturer ma journée</h3>
        <p style="color:#64748b;">Espèces théoriques : <strong>{{ number_format($aggregat['especes'], 0, ',', ' ') }} F</strong>. Saisissez le comptage réel du tiroir.</p>
        <label class="form-label-moderne">Montant compté (espèces)</label>
        <input class="form-input-moderne" type="number" min="0" step="1" name="counted_amount" required placeholder="Ne pas recopier le théorique">
        <label class="form-label-moderne mt-2">Notes (écart, incident)</label>
        <textarea class="form-input-moderne" name="notes" rows="3"></textarea>
        <div class="d-flex gap-2 mt-3">
            <button type="button" class="btn-acasi secondary" onclick="document.getElementById('mc-close').close()">Annuler</button>
            <button type="submit" class="btn-acasi primary">Clôturer</button>
        </div>
    </form>
</dialog>
@endunless
@endsection
