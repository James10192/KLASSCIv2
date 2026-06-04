@extends('layouts.app')

@section('title', 'Session ' . $session->code . ' - KLASSCI')

@push('styles')
<style>
    .rec-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px; padding: 2rem 2.5rem 1.5rem;
        color: #fff; margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .rec-hero-top { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
    .rec-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rec-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff;
    }
    .rec-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .rec-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

    .rec-status-tag {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .3rem .7rem; border-radius: 999px;
        background: rgba(255,255,255,.18); border: 1px solid rgba(255,255,255,.25);
        color: #fff; font-size: .75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .3px;
    }

    .rec-meta {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: .75rem; margin-top: 1.5rem;
    }
    .rec-meta-card {
        background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15);
        border-radius: 10px; padding: .75rem .9rem;
    }
    .rec-meta-label { font-size: .7rem; color: rgba(255,255,255,.65); text-transform: uppercase; letter-spacing: .3px; }
    .rec-meta-value { font-size: .92rem; font-weight: 600; color: #fff; margin-top: .1rem; }

    .rec-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .rec-btn--glass {
        background: rgba(255,255,255,.15); color: #fff;
        border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
        padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        cursor: pointer; transition: background .15s;
    }
    .rec-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
    .rec-btn--white {
        background: #fff; color: #0453cb;
        padding: .5rem 1rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        border: 1px solid #fff; cursor: pointer;
    }
    .rec-btn--white:disabled { opacity: .6; cursor: wait; }

    .rec-tabs { display: flex; gap: .25rem; margin-bottom: 1rem; background: #f1f5f9; padding: .25rem; border-radius: 10px; }
    .rec-tab {
        flex: 1; background: transparent; border: none; padding: .55rem 1rem;
        border-radius: 8px; font-size: .85rem; font-weight: 600; color: #64748b;
        cursor: pointer; transition: background .15s, color .15s;
    }
    .rec-tab:hover { color: #1e293b; }
    .rec-tab--active { background: #fff; color: #0453cb; box-shadow: 0 1px 3px rgba(15,23,42,.06); }

    .rec-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 1.25rem; box-shadow: 0 1px 3px rgba(15,23,42,.04);
    }

    .rec-cash-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
    }
    .rec-cash-card {
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 12px; padding: 1rem;
    }
    .rec-cash-card.has-ecart-pos { border-color: rgba(16,185,129,.4); background: rgba(16,185,129,.03); }
    .rec-cash-card.has-ecart-neg { border-color: rgba(220,38,38,.4); background: rgba(220,38,38,.03); }
    .rec-cash-mode { font-weight: 700; color: #1e293b; margin-bottom: .5rem; display: flex; align-items: center; gap: .4rem; }
    .rec-cash-mode i { color: #0453cb; }
    .rec-cash-fields { display: flex; flex-direction: column; gap: .5rem; }
    .rec-cash-row { display: flex; justify-content: space-between; align-items: center; font-size: .82rem; }
    .rec-cash-row label { color: #64748b; }
    .rec-cash-input {
        width: 130px; padding: .35rem .55rem; border: 1px solid #cbd5e1; border-radius: 7px;
        font-size: .85rem; text-align: right; font-variant-numeric: tabular-nums;
    }
    .rec-cash-input:focus { outline: 2px solid rgba(4,83,203,.25); border-color: #0453cb; }
    .rec-ecart-tag {
        font-weight: 700; padding: .2rem .55rem; border-radius: 6px; font-size: .78rem;
        font-variant-numeric: tabular-nums;
    }
    .rec-ecart-tag.pos { background: rgba(16,185,129,.12); color: #047857; }
    .rec-ecart-tag.neg { background: rgba(220,38,38,.12); color: #b91c1c; }
    .rec-ecart-tag.zero { background: rgba(94,145,222,.12); color: #64748b; }

    .rec-save-row {
        margin-top: .55rem; display: flex; justify-content: flex-end; gap: .5rem;
    }
    .rec-save-btn {
        background: #0453cb; color: #fff; border: none; padding: .35rem .85rem;
        border-radius: 7px; font-size: .78rem; font-weight: 600; cursor: pointer;
    }
    .rec-save-btn:disabled { opacity: .5; cursor: wait; }

    .rec-empty { padding: 2.5rem 1rem; text-align: center; color: #64748b; }
    .rec-empty i { font-size: 2rem; color: #94a3b8; margin-bottom: .5rem; }

    .rec-workflow {
        display: flex; gap: .5rem; margin-top: 1rem; flex-wrap: wrap;
        padding-top: 1rem; border-top: 1px solid #f1f5f9;
    }
    .rec-workflow button {
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        color: #fff; border: none; padding: .55rem 1.15rem;
        border-radius: 8px; font-size: .82rem; font-weight: 600; cursor: pointer;
    }
    .rec-workflow button:disabled { opacity: .5; cursor: not-allowed; background: #94a3b8; }
    .rec-workflow button.danger { background: linear-gradient(135deg, #dc2626, #b91c1c); }
</style>
@endpush

@section('content')
@php
    $cashCountsData = $cash_counts->map(fn ($c) => [
        'id' => $c->id,
        'mode' => $c->mode_paiement,
        'mode_label' => $c->modeLabel(),
        'montant_compte' => (float) $c->montant_compte,
        'montant_systeme' => (float) $c->montant_systeme,
        'ecart' => $c->ecart,
        'counted_at' => optional($c->counted_at)->toIso8601String(),
    ])->keyBy('mode')->all();
    $modes = \App\Enums\ModePaiement::cases();
    $modesPayload = collect($modes)->map(fn ($m) => ['value' => $m->value, 'label' => $m->label(), 'icon' => $m->icon()])->all();
@endphp

<div class="container-fluid" x-data="recShow()" x-init="init()">
    <div class="rec-hero">
        <div class="rec-hero-top">
            <div class="rec-hero-left">
                <div class="rec-hero-icon"><i class="fas fa-balance-scale"></i></div>
                <div>
                    <h1>{{ $session->code }}</h1>
                    <p>
                        {{ ucfirst($session->frequency) }} · du {{ optional($session->period_start)->format('d/m/Y') }}
                        @if($session->period_start != $session->period_end)
                            au {{ optional($session->period_end)->format('d/m/Y') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="rec-actions">
                <span class="rec-status-tag" x-text="statusLabel">{{ $session->status->label() }}</span>
                <a href="{{ route('esbtp.comptabilite.reconciliation.index') }}" class="rec-btn--glass">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                @if($session->status->value === 'closed')
                    @can('comptabilite.reconciliation.export')
                        <a href="{{ route('esbtp.comptabilite.reconciliation.export-pv', $session) }}"
                           class="rec-btn--white" target="_blank">
                            <i class="fas fa-file-pdf"></i> PV PDF
                        </a>
                    @endcan
                @endif
            </div>
        </div>

        <div class="rec-meta">
            <div class="rec-meta-card">
                <div class="rec-meta-label">Ouverte par</div>
                <div class="rec-meta-value">{{ optional($session->opener)->name ?? '—' }}</div>
            </div>
            <div class="rec-meta-card">
                <div class="rec-meta-label">Ouverte le</div>
                <div class="rec-meta-value">{{ optional($session->opened_at)->format('d/m/Y H:i') }}</div>
            </div>
            <div class="rec-meta-card">
                <div class="rec-meta-label">Approbateur</div>
                <div class="rec-meta-value">{{ optional($session->approver)->name ?? '—' }}</div>
            </div>
            <div class="rec-meta-card">
                <div class="rec-meta-label">Total écart</div>
                <div class="rec-meta-value" x-text="formatMoney(totalEcart)">{{ number_format($total_ecart, 0, ',', ' ') }} FCFA</div>
            </div>
        </div>
    </div>

    {{-- Tabs (Alpine state) --}}
    <div class="rec-tabs">
        <button class="rec-tab" :class="tab === 'counts' ? 'rec-tab--active' : ''" @click="tab = 'counts'">
            <i class="fas fa-cash-register"></i> Comptages
        </button>
        <button class="rec-tab" :class="tab === 'discrepancies' ? 'rec-tab--active' : ''" @click="tab = 'discrepancies'">
            <i class="fas fa-exclamation-triangle"></i> Écarts
            <span x-show="discrepancies.length > 0" x-text="'(' + discrepancies.length + ')'"></span>
        </button>
        <button class="rec-tab" :class="tab === 'workflow' ? 'rec-tab--active' : ''" @click="tab = 'workflow'">
            <i class="fas fa-tasks"></i> Workflow
        </button>
    </div>

    {{-- Tab Comptages --}}
    <div class="rec-card" x-show="tab === 'counts'" x-cloak>
        @if($session->isModifiable())
            <div style="background:rgba(4,83,203,.06);border:1px solid rgba(4,83,203,.18);border-radius:8px;padding:.65rem .85rem;margin-bottom:1rem;font-size:.82rem;color:#0453cb;">
                <i class="fas fa-info-circle"></i> Saisissez les montants physiquement constatés pour chaque mode utilisé. L'écart se calcule automatiquement vs les paiements validés en système.
            </div>
        @endif
        <div class="rec-cash-grid">
            <template x-for="mode in modes" :key="mode.value">
                <div class="rec-cash-card" :class="cardClass(mode.value)">
                    <div class="rec-cash-mode">
                        <i :class="'fas ' + mode.icon"></i> <span x-text="mode.label"></span>
                    </div>
                    <div class="rec-cash-fields">
                        <div class="rec-cash-row">
                            <label>Système (validés)</label>
                            <strong x-text="formatMoney(getCount(mode.value).montant_systeme || 0)"></strong>
                        </div>
                        <div class="rec-cash-row">
                            <label>Physique compté</label>
                            <input type="number" step="0.01" min="0" class="rec-cash-input"
                                   :value="getCount(mode.value).montant_compte || 0"
                                   @input="onInput(mode.value, $event.target.value)"
                                   :disabled="!editable">
                        </div>
                        <div class="rec-cash-row">
                            <label>Écart</label>
                            <span class="rec-ecart-tag" :class="ecartTagClass(mode.value)" x-text="formatMoney(getCount(mode.value).ecart || 0)"></span>
                        </div>
                    </div>
                    <div class="rec-save-row" x-show="editable && hasDraft(mode.value)" x-cloak>
                        <button class="rec-save-btn" @click="saveCount(mode.value)" :disabled="saving[mode.value]">
                            <span x-show="!saving[mode.value]"><i class="fas fa-save"></i> Enregistrer</span>
                            <span x-show="saving[mode.value]" x-cloak>…</span>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Tab Écarts --}}
    <div class="rec-card" x-show="tab === 'discrepancies'" x-cloak>
        <div x-show="discrepancies.length === 0" class="rec-empty">
            <i class="fas fa-check-circle"></i>
            <div>Aucun écart à traiter pour le moment.</div>
        </div>
        <div x-show="discrepancies.length > 0" x-cloak>
            <div style="display:flex;flex-direction:column;gap:.65rem;">
                <template x-for="d in discrepancies" :key="d.id">
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.85rem;">
                        <div style="display:flex;justify-content:space-between;gap:.5rem;">
                            <strong x-text="discrepancyTypeLabel(d.type)"></strong>
                            <span class="rec-ecart-tag" :class="d.montant_ecart > 0 ? 'pos' : 'neg'" x-text="formatMoney(d.montant_ecart)"></span>
                        </div>
                        <div style="margin-top:.35rem;color:#64748b;font-size:.82rem;" x-text="d.motif"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Tab Workflow --}}
    <div class="rec-card" x-show="tab === 'workflow'" x-cloak>
        <h3 style="font-size:1rem;font-weight:700;color:#1e293b;margin-bottom:.5rem;">Étapes du workflow OHADA</h3>
        <p style="color:#64748b;font-size:.85rem;">
            Brouillon → En revue → Approuvée (séparation des devoirs) → Clôturée
        </p>
        <div class="rec-workflow">
            <button @click="transition('review')" :disabled="!canReview">
                <i class="fas fa-eye"></i> Passer en revue
            </button>
            @can('comptabilite.reconciliation.approve')
                <button @click="transition('approve')" :disabled="!canApprove">
                    <i class="fas fa-check-double"></i> Approuver
                </button>
                <button @click="transition('close')" :disabled="!canClose">
                    <i class="fas fa-lock"></i> Clôturer
                </button>
            @endcan
            @can('comptabilite.reconciliation.bypass_lock')
                <button class="danger" @click="reopenPrompt()" :disabled="!canReopen">
                    <i class="fas fa-unlock"></i> Rouvrir (exception)
                </button>
            @endcan
        </div>
    </div>
</div>

@php
    $jsPayload = [
        'sessionId' => $session->id,
        'status' => $session->status->value,
        'statusLabel' => $session->status->label(),
        'totalEcart' => (float) $total_ecart,
        'isModifiable' => $session->isModifiable(),
        'cashCounts' => $cashCountsData,
        'modes' => $modesPayload,
        'discrepancies' => $discrepancies->map(fn ($d) => [
            'id' => $d->id,
            'type' => $d->type,
            'montant_ecart' => (float) $d->montant_ecart,
            'motif' => $d->motif,
        ])->all(),
    ];
@endphp

<script>
window.recShow = function () {
    return {
        tab: 'counts',
        sessionId: @json($jsPayload['sessionId']),
        status: @json($jsPayload['status']),
        statusLabel: @json($jsPayload['statusLabel']),
        totalEcart: @json($jsPayload['totalEcart']),
        editable: @json($jsPayload['isModifiable']),
        cashCounts: @json($jsPayload['cashCounts']),
        modes: @json($jsPayload['modes']),
        discrepancies: @json($jsPayload['discrepancies']),
        drafts: {},
        saving: {},

        init() {
            window.addEventListener('reconciliation:refresh', () => this.reload());
        },

        getCount(mode) {
            if (this.drafts[mode] !== undefined) {
                const existing = this.cashCounts[mode] || { montant_systeme: 0, ecart: 0 };
                return {
                    montant_compte: this.drafts[mode],
                    montant_systeme: existing.montant_systeme || 0,
                    ecart: (parseFloat(this.drafts[mode]) || 0) - (existing.montant_systeme || 0),
                };
            }
            return this.cashCounts[mode] || { montant_compte: 0, montant_systeme: 0, ecart: 0 };
        },

        hasDraft(mode) { return this.drafts[mode] !== undefined; },

        onInput(mode, value) { this.drafts[mode] = value; },

        cardClass(mode) {
            const e = this.getCount(mode).ecart || 0;
            if (e > 0) return 'has-ecart-pos';
            if (e < 0) return 'has-ecart-neg';
            return '';
        },
        ecartTagClass(mode) {
            const e = this.getCount(mode).ecart || 0;
            if (e > 0) return 'pos';
            if (e < 0) return 'neg';
            return 'zero';
        },

        async saveCount(mode) {
            this.saving[mode] = true;
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/cash-counts`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        mode_paiement: mode,
                        montant_compte: parseFloat(this.drafts[mode]) || 0,
                    }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);

                this.cashCounts[mode] = data.cash_count;
                delete this.drafts[mode];
                this.recalcTotal();
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: 'Comptage enregistré.' }
                }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.saving[mode] = false;
            }
        },

        recalcTotal() {
            this.totalEcart = Object.values(this.cashCounts).reduce((s, c) => s + (parseFloat(c.ecart) || 0), 0);
        },

        async reload() {
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                const data = await res.json();
                this.status = data.session.status;
                this.totalEcart = data.total_ecart;
                this.editable = ['draft', 'reopened'].includes(this.status);
            } catch (e) { /* silent */ }
        },

        get canReview() { return this.status === 'draft' || this.status === 'reopened'; },
        get canApprove() { return this.status === 'review'; },
        get canClose() { return this.status === 'approved'; },
        get canReopen() { return this.status === 'closed'; },

        async transition(action) {
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/${action}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);

                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: data.message }
                }));
                window.location.reload(); // EXCEPTION ajax-no-reload-premium : changement de statut majeur (workflow final)
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        },

        async reopenPrompt() {
            const reason = prompt('Motif de réouverture (minimum 30 caractères, audit fiscal) :');
            if (!reason || reason.length < 30) {
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'warning', message: 'Motif trop court (30 caractères min).' }
                }));
                return;
            }
            try {
                const res = await fetch(`/esbtp/comptabilite/reconciliation/sessions/${this.sessionId}/reopen`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ reason }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);
                window.location.reload(); // EXCEPTION ajax-no-reload-premium : réouverture change tout
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            }
        },

        formatMoney(v) {
            const n = parseFloat(v) || 0;
            return new Intl.NumberFormat('fr-FR').format(n) + ' FCFA';
        },

        discrepancyTypeLabel(t) {
            return {
                paiement_manquant: 'Paiement manquant',
                paiement_en_trop: 'Paiement en trop',
                montant_errone: 'Montant erroné',
                mode_errone: 'Mode erroné',
                date_erronee: 'Date erronée',
                autre: 'Autre',
            }[t] || t;
        },
    };
};
</script>
@endsection
