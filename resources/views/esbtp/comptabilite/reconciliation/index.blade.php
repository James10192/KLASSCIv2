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

    /* ===== Écran mobile (namespace rim- : reconciliation-index-mobile) ===== */
    .rim-seg { margin-top: 2px; }
    .rim-seg button { padding: 8px 2px; font-size: 12px; }
    .rim-row .tt small { display: block; font-size: 11.5px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rim-amt.warn { color: #8a5200; }
    .rim-amt.bad { color: #b42318; }
    .rim-amt.ok { color: #0f6b4c; }
    .rim-form { display: grid; gap: 14px; padding: 0 16px 16px; }
    .rim-hint { font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.4; }
    .rim-more { margin-top: 4px; }
</style>
@endpush

@section('content')
@php
    // Shell mobile : le DOM de bureau reste dans .m-only-desktop, l'écran mobile
    // (maquette S['comptable:reconciliation']) vit à côté, sur le MÊME état Alpine.
    $rimShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
    $rimEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $rimEcoleNom = $rimEcole['name'] ?: ($rimEcole['acronym'] ?: config('app.name'));
    $rimPeutOuvrir = auth()->user()?->can('comptabilite.reconciliation.open') ?? false;
    $rimStatuts = collect(\App\Enums\ReconciliationSessionStatus::cases())
        ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
        ->all();
    $rimFrequences = [
        'daily' => ['Quotidien', 'Une session par jour'],
        'weekly' => ['Hebdomadaire', 'Une session par semaine'],
        'monthly' => ['Mensuel', 'Une session par mois'],
    ];
    $rimFrequenceDefaut = $defaultFrequency ?? 'daily';
    $rimCfg = [
        'statuts' => $rimStatuts,
        'showUrl' => route('esbtp.comptabilite.reconciliation.show', ['session' => '__ID__']),
        'openUrl' => route('esbtp.comptabilite.reconciliation.open'),
        'frequenceDefaut' => $rimFrequenceDefaut,
    ];
@endphp
<div x-data="recIndex()" x-init="init()" class="container-fluid">
<div class="{{ $rimShell ? 'm-only-desktop' : '' }}">
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

        {{-- Filtres (composant au-select obligatoire — rule premium-selects) --}}
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
                                <a :href="showUrl(session.id)"
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

@if($rimShell)
{{-- ============================ ÉCRAN MOBILE (shell m-*) ============================ --}}
{{-- La barre d'onglets et la navbar mobile sont rendues par le layout. --}}
<div class="m-only-mobile m-screen rim-screen">
    <x-m.appbar title="Réconciliation"
                :sub="$rimEcoleNom"
                :back="\App\Support\PorteDeRoute::ouverte('esbtp.comptabilite.dashboard', auth()->user()) ? route('esbtp.comptabilite.dashboard') : route('dashboard')"
                :action="$rimPeutOuvrir ? 'plus' : null"
                action-label="Ouvrir une session"
                x-on:click="mOuvrirFeuille('rim-nouvelle')" />

    <div class="m-body" data-m-ptr="reload">
        <section class="m-hero">
            <span class="k">Sessions de caisse</span>
            <span class="v"><span x-text="kpis.total">{{ $kpis['total'] }}</span><small x-text="kpis.total > 1 ? 'sessions' : 'session'">{{ $kpis['total'] > 1 ? 'sessions' : 'session' }}</small></span>
            <div class="row">
                <span class="pill" x-text="kpis.draft + (kpis.draft > 1 ? ' brouillons' : ' brouillon')">{{ $kpis['draft'] }} {{ $kpis['draft'] > 1 ? 'brouillons' : 'brouillon' }}</span>
                <span class="pill" x-text="kpis.review + ' en revue'">{{ $kpis['review'] }} en revue</span>
                <span class="pill" x-text="kpis.closed + (kpis.closed > 1 ? ' clôturées' : ' clôturée')">{{ $kpis['closed'] }} {{ $kpis['closed'] > 1 ? 'clôturées' : 'clôturée' }}</span>
            </div>
        </section>

        <div class="m-seg rim-seg" role="tablist" aria-label="Filtrer par statut">
            <button type="button" role="tab" x-bind:aria-selected="filters.status === '' ? 'true' : 'false'"
                    x-bind:class="filters.status === '' ? 'on' : ''" x-on:click="toggleFilter('status', '')"
                    x-text="'Toutes · ' + kpis.total">Toutes · {{ $kpis['total'] }}</button>
            <button type="button" role="tab" x-bind:aria-selected="filters.status === 'draft' ? 'true' : 'false'"
                    x-bind:class="filters.status === 'draft' ? 'on' : ''" x-on:click="toggleFilter('status', 'draft')"
                    x-text="'Brouillon · ' + kpis.draft">Brouillon · {{ $kpis['draft'] }}</button>
            <button type="button" role="tab" x-bind:aria-selected="filters.status === 'review' ? 'true' : 'false'"
                    x-bind:class="filters.status === 'review' ? 'on' : ''" x-on:click="toggleFilter('status', 'review')"
                    x-text="'Revue · ' + kpis.review">Revue · {{ $kpis['review'] }}</button>
            <button type="button" role="tab" x-bind:aria-selected="filters.status === 'approved' ? 'true' : 'false'"
                    x-bind:class="filters.status === 'approved' ? 'on' : ''" x-on:click="toggleFilter('status', 'approved')"
                    x-text="'Approuvée · ' + kpis.approved">Approuvée · {{ $kpis['approved'] }}</button>
            <button type="button" role="tab" x-bind:aria-selected="filters.status === 'closed' ? 'true' : 'false'"
                    x-bind:class="filters.status === 'closed' ? 'on' : ''" x-on:click="toggleFilter('status', 'closed')"
                    x-text="'Clôturée · ' + kpis.closed">Clôturée · {{ $kpis['closed'] }}</button>
        </div>

        {{-- Squelette pendant le rechargement --}}
        <div class="m-skel" x-show="loading" x-cloak aria-hidden="true"><i></i><i></i><i></i></div>

        <div class="m-list one" x-show="!loading && sessions.data.length > 0" x-cloak>
            <template x-for="session in sessions.data" :key="session.id">
                <a class="m-row rim-row" x-bind:href="showUrl(session.id)">
                    <div class="av ic" aria-hidden="true"><x-m.icon name="scale" /></div>
                    <div class="tt">
                        <b x-text="session.code"></b>
                        <span x-text="mPeriode(session)"></span>
                        <small x-text="mDetail(session)"></small>
                    </div>
                    <div class="tr">
                        <span class="amt rim-amt" x-bind:class="mTon(session.total_ecart || 0)" x-text="mEcart(session.total_ecart || 0)"></span>
                        <span class="m-chip" x-bind:class="mChipTon(session.status)" x-text="statusLabel(session.status)"></span>
                    </div>
                </a>
            </template>
        </div>

        <div x-show="!loading && sessions.data.length > 0 && (sessions.next_page_url || sessions.prev_page_url)" x-cloak class="m-seg rim-more">
            <button type="button" x-bind:disabled="!sessions.prev_page_url" x-on:click="mPage(sessions.current_page - 1)">Précédent</button>
            <span x-text="'Page ' + sessions.current_page + ' / ' + sessions.last_page"></span>
            <button type="button" x-bind:disabled="!sessions.next_page_url" x-on:click="mPage(sessions.current_page + 1)">Suivant</button>
        </div>

        <div x-show="!loading && sessions.data.length === 0" x-cloak>
            <x-m.empty icon="scale" title="Aucune session" text="Le bouclage de caisse commence par une session : on compte, on compare aux paiements enregistrés, on justifie les écarts.">
                @can('comptabilite.reconciliation.open')
                    <button type="button" class="m-btn g" x-on:click="mOuvrirFeuille('rim-nouvelle')">
                        <x-m.icon name="plus" />Ouvrir une session
                    </button>
                @endcan
            </x-m.empty>
        </div>
    </div>

    @can('comptabilite.reconciliation.open')
        <button type="button" class="m-fab" aria-label="Ouvrir une session" x-on:click="mOuvrirFeuille('rim-nouvelle')">
            <x-m.icon name="plus" />
        </button>

        {{-- ============ Feuille « Nouvelle session » ============ --}}
        <x-m.sheet id="rim-nouvelle" title="Nouvelle session" :sub="$rimEcoleNom">
            <form class="rim-form" x-on:submit.prevent="mOuvrirSession()">
                <p class="rim-hint">Une session couvre une période : on y saisit le compté par mode de paiement, puis on justifie chaque écart avec le système.</p>
                <div class="m-field">
                    <label>Fréquence</label>
                    <div class="m-opt">
                        @foreach($rimFrequences as $rimCle => [$rimLibelle, $rimAide])
                            <label x-bind:class="nouvelle.frequency === @js($rimCle) ? 'on' : ''">
                                <span class="rd" aria-hidden="true"></span>
                                <div><b>{{ $rimLibelle }}</b><span>{{ $rimAide }}</span></div>
                                <input type="radio" name="rim_frequency" value="{{ $rimCle }}" x-model="nouvelle.frequency">
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="m-field">
                    <label for="rim-date">Date de référence</label>
                    <input id="rim-date" type="date" class="m-in" x-model="nouvelle.start_date" required>
                    <p class="rim-hint">Elle fixe la période couverte (aujourd'hui par défaut).</p>
                </div>
                <button type="submit" class="m-btn p" x-bind:disabled="nouvelle.envoi || !nouvelle.start_date">
                    <span x-show="!nouvelle.envoi">Ouvrir la session</span>
                    <span x-show="nouvelle.envoi" x-cloak>Ouverture…</span>
                </button>
            </form>
        </x-m.sheet>
    @endcan
</div>
@endif
</div>
@endsection

@push('scripts')
<script>
if (typeof window.recIndex !== 'function') {
window.recIndex = function () {
    return {
        sessions: @json($sessions),
        kpis: @json($kpis),
        filters: {
            status: @json(request('status', '')),
            frequency: @json(request('frequency', '')),
        },
        loading: false,
        cfg: @json($rimCfg),
        nouvelle: {
            frequency: @json($rimFrequenceDefaut),
            start_date: new Date().toISOString().slice(0, 10),
            envoi: false,
        },

        init() {
            window.addEventListener('reconciliation:refresh', () => this.reload());
        },

        toggleFilter(key, value) {
            this.filters[key] = (this.filters[key] === value) ? '' : value;
            this.reload();
        },

        async reload(page) {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => { if (v) params.set(k, v); });
                if (page && page > 1) params.set('page', page);
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

        showUrl(id) {
            return this.cfg.showUrl.replace('__ID__', id);
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
            return this.cfg.statuts[s] || s;
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

        /* ---------- écran mobile ---------- */
        mOuvrirFeuille(id) {
            window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: id } }));
        },
        mPage(p) {
            if (p < 1 || (this.sessions.last_page && p > this.sessions.last_page)) return;
            this.reload(p);
        },
        mPeriode(s) {
            const debut = this.formatDate(s.period_start);
            if (s.period_start === s.period_end) return this.frequencyLabel(s.frequency) + ' · ' + debut;
            return this.frequencyLabel(s.frequency) + ' · du ' + debut + ' au ' + this.formatDate(s.period_end);
        },
        mDetail(s) {
            const c = parseInt(s.cash_counts_count || 0, 10);
            const d = parseInt(s.discrepancies_count || 0, 10);
            const parts = [c + (c > 1 ? ' modes comptés' : ' mode compté'), d + (d > 1 ? ' écarts' : ' écart')];
            if (s.opener && s.opener.name) parts.push(s.opener.name);
            return parts.join(' · ');
        },
        mEcart(v) {
            const n = parseFloat(v) || 0;
            if (n === 0) return 'Écart 0';
            return (n > 0 ? '+' : '−') + new Intl.NumberFormat('fr-FR').format(Math.abs(n));
        },
        mTon(v) {
            const n = parseFloat(v) || 0;
            if (n > 0) return 'warn';
            if (n < 0) return 'bad';
            return 'ok';
        },
        mChipTon(s) {
            return { draft: 'mute', review: 'warn', approved: 'info', closed: 'ok', reopened: 'bad' }[s] || 'mute';
        },
        async mOuvrirSession() {
            if (this.nouvelle.envoi) return;
            this.nouvelle.envoi = true;
            try {
                const res = await fetch(this.cfg.openUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ frequency: this.nouvelle.frequency, start_date: this.nouvelle.start_date }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) throw new Error(data.message || 'Erreur ' + res.status);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { type: 'success', message: 'Session ' + data.session.code + ' ouverte.' }
                }));
                // EXCEPTION ajax-no-reload-premium : création d'une entité majeure,
                // on passe sur son écran de comptage.
                window.location.href = this.showUrl(data.session.id);
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally {
                this.nouvelle.envoi = false;
            }
        },
    };
};
}
</script>
@endpush
