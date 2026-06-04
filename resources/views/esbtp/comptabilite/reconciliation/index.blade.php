@extends('layouts.app')

@section('title', 'Réconciliation Caisse - KLASSCI')

@push('styles')
<style>
    .rec-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4,83,203,.18);
    }
    .rec-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .rec-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rec-hero-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: rgba(255,255,255,.12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; flex-shrink: 0; color: #fff;
    }
    .rec-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
    .rec-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

    .rec-kpis {
        display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap;
    }
    .rec-kpi {
        flex: 1; min-width: 140px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.15);
        border-radius: 12px;
        padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem;
        cursor: pointer;
        transition: background .15s, border-color .15s;
    }
    .rec-kpi:hover { background: rgba(255,255,255,.18); border-color: rgba(255,255,255,.3); }
    .rec-kpi--active { background: rgba(255,255,255,.25); border-color: rgba(255,255,255,.5); }
    .rec-kpi-icon { font-size: 1.1rem; color: rgba(255,255,255,.85); }
    .rec-kpi-body { display: flex; flex-direction: column; }
    .rec-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .rec-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; text-transform: uppercase; letter-spacing: .3px; }

    .rec-btn--glass {
        background: rgba(255,255,255,.15); color: #fff;
        border: 1px solid rgba(255,255,255,.2); border-radius: 10px;
        padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
        transition: background .15s;
    }
    .rec-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }
    .rec-btn--white {
        background: #fff; color: #0453cb; border-color: transparent;
        padding: .5rem 1rem; border-radius: 10px; font-size: .82rem; font-weight: 600;
        text-decoration: none; display: inline-flex; align-items: center; gap: .4rem;
    }
    .rec-btn--white:hover { background: #f8fafc; color: #0453cb; }

    .rec-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 1.25rem;
        box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    }

    .rec-section-header {
        display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem;
    }
    .rec-section-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
    }
    .rec-section-title { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0; }

    .rec-filters { display: flex; gap: .75rem; flex-wrap: wrap; margin-bottom: 1rem; }

    .rec-table-wrap { overflow-x: auto; }
    .rec-table { width: 100%; border-collapse: collapse; }
    .rec-table th {
        text-align: left; font-size: .72rem; text-transform: uppercase;
        color: #64748b; font-weight: 700; padding: .65rem .85rem;
        background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        letter-spacing: .3px;
    }
    .rec-table td {
        padding: .8rem .85rem; font-size: .88rem; color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
    }
    .rec-table tbody tr { transition: background .15s; }
    .rec-table tbody tr:hover { background: #f8fafc; }

    .rec-badge {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .2rem .55rem; border-radius: 7px;
        font-size: .7rem; font-weight: 700; letter-spacing: .3px;
        text-transform: uppercase;
    }
    .rec-badge--muted { background: rgba(94,145,222,.1); color: #64748b; border: 1px solid rgba(94,145,222,.2); }
    .rec-badge--warning { background: rgba(245,158,11,.1); color: #b45309; border: 1px solid rgba(245,158,11,.25); }
    .rec-badge--info { background: rgba(4,83,203,.1); color: #0453cb; border: 1px solid rgba(4,83,203,.25); }
    .rec-badge--success { background: rgba(16,185,129,.1); color: #047857; border: 1px solid rgba(16,185,129,.25); }
    .rec-badge--danger { background: rgba(220,38,38,.1); color: #b91c1c; border: 1px solid rgba(220,38,38,.25); }

    .rec-ecart--pos { color: #047857; font-weight: 700; }
    .rec-ecart--neg { color: #b91c1c; font-weight: 700; }
    .rec-ecart--zero { color: #64748b; }

    .rec-empty {
        padding: 3rem 1rem; text-align: center; color: #64748b;
    }
    .rec-empty-icon { font-size: 2.5rem; color: #94a3b8; margin-bottom: .75rem; }
    .rec-empty-title { font-size: 1rem; font-weight: 600; color: #1e293b; margin-bottom: .25rem; }

    @media (max-width: 768px) {
        .rec-hero { padding: 1.5rem 1.25rem 1rem; }
        .rec-hero h1 { font-size: 1.2rem; }
    }
</style>
@endpush

@section('content')
<div x-data="recIndex()" x-init="init()" class="container-fluid">
    {{-- Hero gradient KLASSCI --}}
    <div class="rec-hero">
        <div class="rec-hero-top">
            <div class="rec-hero-left">
                <div class="rec-hero-icon"><i class="fas fa-balance-scale"></i></div>
                <div>
                    <h1>Réconciliation Caisse</h1>
                    <p>Bouclage périodique caisse physique vs paiements enregistrés</p>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                @can('comptabilite.reconciliation.open')
                    <a href="{{ route('esbtp.comptabilite.reconciliation.create') }}" class="rec-btn--white">
                        <i class="fas fa-plus"></i> Nouvelle session
                    </a>
                @endcan
            </div>
        </div>

        {{-- KPIs cliquables AJAX no-reload --}}
        <div class="rec-kpis">
            <div class="rec-kpi" :class="filters.status === '' ? 'rec-kpi--active' : ''" @click="toggleFilter('status', '')">
                <i class="fas fa-list rec-kpi-icon"></i>
                <div class="rec-kpi-body">
                    <span class="rec-kpi-value" x-text="kpis.total">{{ $kpis['total'] }}</span>
                    <span class="rec-kpi-label">Total</span>
                </div>
            </div>
            <div class="rec-kpi" :class="filters.status === 'draft' ? 'rec-kpi--active' : ''" @click="toggleFilter('status', 'draft')">
                <i class="fas fa-pen rec-kpi-icon"></i>
                <div class="rec-kpi-body">
                    <span class="rec-kpi-value" x-text="kpis.draft">{{ $kpis['draft'] }}</span>
                    <span class="rec-kpi-label">Brouillons</span>
                </div>
            </div>
            <div class="rec-kpi" :class="filters.status === 'review' ? 'rec-kpi--active' : ''" @click="toggleFilter('status', 'review')">
                <i class="fas fa-eye rec-kpi-icon"></i>
                <div class="rec-kpi-body">
                    <span class="rec-kpi-value" x-text="kpis.review">{{ $kpis['review'] }}</span>
                    <span class="rec-kpi-label">En revue</span>
                </div>
            </div>
            <div class="rec-kpi" :class="filters.status === 'approved' ? 'rec-kpi--active' : ''" @click="toggleFilter('status', 'approved')">
                <i class="fas fa-check rec-kpi-icon"></i>
                <div class="rec-kpi-body">
                    <span class="rec-kpi-value" x-text="kpis.approved">{{ $kpis['approved'] }}</span>
                    <span class="rec-kpi-label">Approuvées</span>
                </div>
            </div>
            <div class="rec-kpi" :class="filters.status === 'closed' ? 'rec-kpi--active' : ''" @click="toggleFilter('status', 'closed')">
                <i class="fas fa-lock rec-kpi-icon"></i>
                <div class="rec-kpi-body">
                    <span class="rec-kpi-value" x-text="kpis.closed">{{ $kpis['closed'] }}</span>
                    <span class="rec-kpi-label">Clôturées</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Card sessions --}}
    <div class="rec-card">
        <div class="rec-section-header">
            <div class="rec-section-icon"><i class="fas fa-history"></i></div>
            <h2 class="rec-section-title">Historique des sessions</h2>
        </div>

        {{-- Filtres (<x-au-select> obligatoire — rule premium-selects) --}}
        <div class="rec-filters">
            <x-au-select
                name="frequency"
                :value="''"
                placeholder="Toutes fréquences"
                icon="fa-clock"
                x-model="filters.frequency"
                @change="reload()"
                :options="['daily' => 'Quotidien', 'weekly' => 'Hebdomadaire', 'monthly' => 'Mensuel']" />
        </div>

        {{-- Table --}}
        <div class="rec-table-wrap" x-show="sessions.data.length > 0" x-cloak>
            <table class="rec-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Période</th>
                        <th>Fréquence</th>
                        <th>Statut</th>
                        <th>Comptages</th>
                        <th>Écarts</th>
                        <th>Total écart</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="session in sessions.data" :key="session.id">
                        <tr>
                            <td><strong x-text="session.code"></strong></td>
                            <td>
                                <span x-text="formatDate(session.period_start)"></span>
                                <span x-show="session.period_start !== session.period_end" x-text="' → ' + formatDate(session.period_end)"></span>
                            </td>
                            <td><span x-text="frequencyLabel(session.frequency)"></span></td>
                            <td>
                                <span class="rec-badge" :class="statusBadgeClass(session.status)">
                                    <span x-text="statusLabel(session.status)"></span>
                                </span>
                            </td>
                            <td x-text="session.cash_counts_count"></td>
                            <td x-text="session.discrepancies_count"></td>
                            <td :class="ecartClass(session.total_ecart || 0)" x-text="formatMoney(session.total_ecart || 0)"></td>
                            <td>
                                <a :href="'/esbtp/comptabilite/reconciliation/sessions/' + session.id"
                                   class="rec-btn--glass" style="background:rgba(4,83,203,.08); color:#0453cb; border-color:rgba(4,83,203,.2);">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        {{-- Empty state --}}
        <div class="rec-empty" x-show="sessions.data.length === 0" x-cloak>
            <div class="rec-empty-icon"><i class="fas fa-balance-scale"></i></div>
            <div class="rec-empty-title">Aucune session de réconciliation</div>
            <p>Démarrez votre premier bouclage caisse en cliquant sur « Nouvelle session ».</p>
        </div>
    </div>
</div>

<script>
window.recIndex = function () {
    return {
        sessions: @json($sessions),
        kpis: @json($kpis),
        filters: {
            status: @json(request('status', '')),
            frequency: @json(request('frequency', '')),
        },
        loading: false,

        init() {
            window.addEventListener('reconciliation:refresh', () => this.reload());
        },

        toggleFilter(key, value) {
            this.filters[key] = (this.filters[key] === value) ? '' : value;
            this.reload();
        },

        async reload() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => { if (v) params.set(k, v); });
                const res = await fetch('/esbtp/comptabilite/reconciliation?' + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error('Erreur HTTP ' + res.status);
                const data = await res.json();
                this.sessions = data.sessions;
                this.kpis = data.kpis;
                history.pushState({}, '', '?' + params.toString());
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.loading = false;
            }
        },

        formatDate(d) {
            if (!d) return '—';
            return new Date(d).toLocaleDateString('fr-FR');
        },
        formatMoney(v) {
            return new Intl.NumberFormat('fr-FR').format(v) + ' FCFA';
        },
        frequencyLabel(f) {
            return { daily: 'Quotidien', weekly: 'Hebdomadaire', monthly: 'Mensuel' }[f] || f;
        },
        statusLabel(s) {
            return { draft: 'Brouillon', review: 'En revue', approved: 'Approuvée', closed: 'Clôturée', reopened: 'Réouverte' }[s] || s;
        },
        statusBadgeClass(s) {
            return {
                draft: 'rec-badge--muted',
                review: 'rec-badge--warning',
                approved: 'rec-badge--info',
                closed: 'rec-badge--success',
                reopened: 'rec-badge--danger',
            }[s] || 'rec-badge--muted';
        },
        ecartClass(v) {
            if (v > 0) return 'rec-ecart--pos';
            if (v < 0) return 'rec-ecart--neg';
            return 'rec-ecart--zero';
        },
    };
};
</script>
@endsection
