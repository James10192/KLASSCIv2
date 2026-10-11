@props(['situation'])

{{-- Lecture financière commune à la fiche étudiant et à la réinscription.
     Un reliquat reste attaché à son année d'origine. --}}
<section class="sfe-card" aria-label="Situation financière par inscription"
    data-sfe-inscription-id="{{ $situation['inscription_courante_id'] }}"
    x-data="{ pending: 0 }"
    @reliquat-encaissement-refresh.window="if (Number($event.detail.inscriptionId) === {{ (int) $situation['inscription_courante_id'] }}) pending = $event.detail.pending">
    <div class="sfe-head">
        <div class="sfe-icon"><i class="fas fa-wallet"></i></div>
        <div class="sfe-title">
            <h3>Situation financière globale</h3>
            <p>Une dette conserve son année d'origine, même si elle a été reportée.</p>
        </div>
    </div>
    @php($fcfaSfe = fn ($n) => number_format((float) $n, 0, ',', ' ').' FCFA')
    <div class="sfe-kpis">
        <div class="sfe-kpi"><small>Inscription la plus récente</small><strong>{{ $fcfaSfe($situation['courant']) }}</strong><span>Frais propres à cette année, restant dus</span></div>
        <div class="sfe-kpi"><small>Années antérieures</small><strong>{{ $fcfaSfe($situation['arrieres']) }}</strong><span>Impayés et reliquats antérieurs</span></div>
        <div class="sfe-kpi sfe-kpi--primary"><small>Total à régulariser</small><strong>{{ $fcfaSfe($situation['total']) }}</strong><span>Après paiements validés, sans double report</span></div>
    </div>
    @if($situation['inscription_courante_id'])
    <div class="sfe-actions">
        <x-reliquat-caisse-modal :inscription-id="$situation['inscription_courante_id']" />
        @canany(['paiements.create', 'paiements.create.non_cash', 'paiements.create.mobile_money'])
            <a class="sfe-pay-current" href="{{ route('esbtp.paiements.create', ['inscription_id' => $situation['inscription_courante_id']]) }}">
                <i class="fas fa-credit-card"></i> Régler les frais de l'année
            </a>
        @endcanany
    </div>
    @endif
    <p class="sfe-note" x-show="pending > 0" x-cloak>
        <i class="fas fa-clock"></i>
        <span>Versements sur dettes anciennes en attente de validation : <strong x-text="new Intl.NumberFormat('fr-FR').format(pending) + ' FCFA'"></strong>.
        Ils ne sont pas encore déduits du total dû.</span>
    </p>
    @if($situation['anomalies'] > 0)
    <div class="sfe-warning" role="alert"><i class="fas fa-triangle-exclamation"></i>
        {{ $situation['anomalies'] }} créance(s) semblent avoir été reportées plusieurs fois. Demandez une vérification comptable avant correction.
    </div>
    @endif
    <h4 class="sfe-subhead">Historique des inscriptions</h4>
    <div class="sfe-scroll">
        <table class="sfe-table">
            <thead><tr><th>Année et classe</th><th>Frais dus</th><th>Paiements validés</th><th>Transféré en reliquat</th><th>Restant non reporté</th></tr></thead>
            <tbody>
            @forelse($situation['annees'] as $ligne)
                <tr>
                    <td><strong>{{ $ligne['annee'] }}</strong><small>{{ $ligne['classe'] }}</small>@if($ligne['courante']) <span class="sfe-chip">Référence</span>@endif</td>
                    <td>{{ $fcfaSfe($ligne['du']) }}</td>
                    <td>{{ $fcfaSfe($ligne['paye']) }}</td>
                    <td>{{ $fcfaSfe($ligne['reporte']) }}</td>
                    <td><strong>{{ $fcfaSfe($ligne['non_reporte']) }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="5" class="sfe-empty">Aucune inscription à présenter.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($situation['reliquats']->isNotEmpty())
        <h4 class="sfe-subhead">Créances reportées, encore ouvertes</h4>
        <div class="sfe-scroll">
            <table class="sfe-table">
                <thead><tr><th>Dette née en</th><th>Reportée en</th><th>Frais</th><th>Montant initial</th><th>Réglé</th><th>Reste dû</th></tr></thead>
                <tbody>
                @foreach($situation['reliquats'] as $r)
                <tr>
                    <td>{{ $r['annee_origine'] }}</td>
                    <td>{{ $r['annee_report'] }}</td>
                    <td>{{ $r['frais'] }}</td>
                    <td>{{ $fcfaSfe($r['initial']) }}</td>
                    <td>{{ $fcfaSfe($r['regle']) }}</td>
                    <td><strong>{{ $fcfaSfe($r['restant']) }}</strong></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    <p class="sfe-note"><i class="fas fa-info-circle"></i> Les versements en attente ne soldent pas une dette. Les éventuels avoirs validés sont pris en compte dans le net des paiements de l'inscription. Un solde négatif de l'année d'origine n'efface pas automatiquement un reliquat : rapprochez-le en comptabilité.</p>
</section>
@once
@push('styles')
<style>
.sfe-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; padding:1.3rem; box-shadow:0 1px 3px rgba(15,23,42,.05); margin-bottom:1.25rem; }
.sfe-actions {display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin:0 0 1rem}
.sfe-pay-current {display:inline-flex;gap:.35rem;align-items:center;padding:.6rem .85rem;border:1px solid #bfdbfe;border-radius:9px;background:#eff6ff;color:#0453cb;text-decoration:none;font-size:.77rem;font-weight:750}
.sfe-pay-current:hover {background:#dbeafe;color:#033a8e}
.sfe-head { display:flex; align-items:center; gap:.8rem; margin-bottom:1rem; }
.sfe-icon { width:40px; height:40px; display:grid; place-items:center; flex-shrink:0; color:#fff; background:linear-gradient(135deg,#0453cb,#3b7ddb); border-radius:10px; }
.sfe-title h3 { font-size:1rem; font-weight:800; color:#0f172a; margin:0 0 .2rem; }
.sfe-title p { color:#64748b; font-size:.77rem; margin:0; }
.sfe-kpis { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.7rem; margin-bottom:1rem; }
.sfe-kpi { border:1px solid #e2e8f0; border-radius:12px; padding:.85rem; background:#f8fafc; display:flex; flex-direction:column; gap:.3rem; }
.sfe-kpi small { font-size:.73rem; color:#64748b; }
.sfe-kpi strong { font-size:1.18rem; color:#0f172a; font-variant-numeric:tabular-nums; }
.sfe-kpi span { font-size:.67rem; color:#64748b; }
.sfe-kpi--primary { border-color:#a9c6f9; background:#eff6ff; }
.sfe-kpi--primary strong { color:#0453cb; }
.sfe-subhead { color:#334155; font-size:.82rem; font-weight:800; margin:1.1rem 0 .5rem; }
.sfe-scroll { overflow-x:auto; max-width:100%; }
.sfe-table { border-collapse:collapse; width:100%; min-width:640px; font-size:.78rem; }
.sfe-table th { text-align:left; background:#f1f5f9; color:#475569; font-weight:750; font-size:.72rem; padding:.65rem .7rem; white-space:nowrap; }
.sfe-table td { border-bottom:1px solid #e2e8f0; padding:.7rem; color:#334155; vertical-align:top; }
.sfe-table td strong { color:#0f172a; }
.sfe-table td small { display:block; color:#64748b; margin-top:.2rem; }
.sfe-chip { display:inline-block; margin-top:.25rem; padding:.1rem .4rem; border-radius:5px; background:#eff6ff; color:#0453cb; font-size:.65rem; }
.sfe-warning { display:flex; gap:.5rem; padding:.75rem; border:1px solid #fcd34d; background:#fffbeb; color:#92400e; border-radius:9px; font-size:.77rem; }
.sfe-note { display:flex; gap:.4rem; color:#64748b; margin:.9rem 0 0; font-size:.72rem; line-height:1.5; }
.sfe-empty { text-align:center; color:#64748b; }
@media(max-width:680px){.sfe-kpis {grid-template-columns:1fr}.sfe-card{padding:1rem}.sfe-table{min-width:560px}}
</style>
@endpush
@endonce
