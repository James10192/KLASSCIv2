@extends('layouts.app')

@section('title', 'Recouvrement quotidien')

@section('content')
<div class="container-fluid re-page"
     x-data="recouvrement(@js([
        'rows' => $rows,
        'whatsappTemplate' => $whatsappTemplate,
        'schoolName' => $schoolName,
        'logIntentUrl' => route('esbtp.comptabilite.recouvrement.log-intent'),
        'confirmSentUrl' => route('esbtp.comptabilite.recouvrement.confirm-sent'),
        'markDoneUrl' => route('esbtp.comptabilite.recouvrement.mark-done'),
        'csrf' => csrf_token(),
     ]))">

    {{-- ============================ HERO ============================ --}}
    <div class="re-hero">
        <div class="re-hero-top">
            <div class="re-hero-left">
                <div class="re-hero-icon"><i class="fas fa-hand-holding-usd"></i></div>
                <div>
                    <h1>Recouvrement quotidien</h1>
                    <p>Liste priorisée des étudiants à relancer aujourd'hui — appel direct, WhatsApp ou email en 1 clic.</p>
                </div>
            </div>
            <div class="re-hero-right">
                <a href="{{ route('esbtp.comptabilite.analytics.index') }}" class="re-btn re-btn--glass">
                    <i class="fas fa-chart-line"></i> Analytics
                </a>
            </div>
        </div>

        <div class="re-kpis">
            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-fire"></i></div>
                <div>
                    <div class="re-kpi-value">{{ $buckets['haut'] ?? 0 }}</div>
                    <div class="re-kpi-label">Étudiants à haut risque</div>
                </div>
            </div>

            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-coins"></i></div>
                <div>
                    <div class="re-kpi-value">{{ number_format($totalSoldeHaut, 0, ',', ' ') }} <span class="re-kpi-unit">FCFA</span></div>
                    <div class="re-kpi-label">Solde non recouvré (haut risque)</div>
                </div>
            </div>

            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-eye"></i></div>
                <div>
                    <div class="re-kpi-value">{{ $buckets['moyen'] ?? 0 }}</div>
                    <div class="re-kpi-label">Sous surveillance</div>
                </div>
            </div>

            <div class="re-kpi">
                <div class="re-kpi-icon"><i class="fas fa-users"></i></div>
                <div>
                    <div class="re-kpi-value">{{ $totalActifs }}</div>
                    <div class="re-kpi-label">Total étudiants actifs</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================ FILTERS ============================ --}}
    <div class="re-filters">
        <input type="text" class="re-input" placeholder="Rechercher par nom..." x-model="search">

        <select class="re-input" x-model="levelFilter">
            <option value="">Tous les niveaux</option>
            <option value="haut">Haut risque uniquement</option>
            <option value="moyen">Surveillance</option>
            <option value="bas">Bas risque</option>
        </select>

        <select class="re-input" x-model="retardFilter">
            <option value="">Tout retard</option>
            <option value="30">≥ 30 jours</option>
            <option value="60">≥ 60 jours</option>
            <option value="90">≥ 90 jours</option>
        </select>

        <div class="re-filter-stat" x-text="`${filteredRows.length} / ${rows.length} étudiants`"></div>
    </div>

    {{-- ============================ TABLE ============================ --}}
    <div class="re-card">
        <template x-if="filteredRows.length === 0">
            <div class="re-empty">
                <i class="fas fa-check-circle"></i>
                <p x-text="rows.length === 0 ? 'Aucun étudiant à risque dans ce périmètre.' : 'Aucun résultat avec ces filtres.'"></p>
            </div>
        </template>

        <template x-if="filteredRows.length > 0">
            <div class="table-responsive">
                <table class="re-table">
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Classe</th>
                            <th class="text-end">Solde</th>
                            <th class="text-center">Retard</th>
                            <th class="text-center">Niveau</th>
                            <th class="text-center" style="min-width: 280px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in filteredRows" :key="row.inscription_id">
                            <tr :class="row.confirmed ? 're-row--done' : ''">
                                <td>
                                    <div class="re-cell-student">
                                        <strong x-text="row.etudiant_nom"></strong>
                                        <small x-show="!row.has_valid_phone" class="re-warn">
                                            <i class="fas fa-exclamation-triangle"></i> Téléphone invalide
                                        </small>
                                        <small x-show="row.relances_today > 0" class="re-info">
                                            <i class="fas fa-history"></i>
                                            <span x-text="`Déjà ${row.relances_today} relance(s) aujourd'hui`"></span>
                                        </small>
                                    </div>
                                </td>
                                <td x-text="row.classe_nom"></td>
                                <td class="text-end">
                                    <strong x-text="formatMoney(row.solde_restant)"></strong> FCFA
                                </td>
                                <td class="text-center">
                                    <span class="re-chip re-chip--retard" x-text="row.jours_retard + ' j'"></span>
                                </td>
                                <td class="text-center">
                                    <span class="re-level" :class="'re-level--' + row.level" x-text="capitalize(row.level)"></span>
                                </td>
                                <td>
                                    <div class="re-actions">
                                        <button class="re-action re-action--whatsapp"
                                                :disabled="!row.has_valid_phone || row.confirmed"
                                                @click="dispatch(row, 'whatsapp_deeplink')"
                                                title="Envoyer un WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                        <button class="re-action re-action--tel"
                                                :disabled="!row.has_valid_phone || row.confirmed"
                                                @click="dispatch(row, 'tel')"
                                                title="Appeler">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="re-action re-action--email"
                                                :disabled="!row.email || row.confirmed"
                                                @click="dispatch(row, 'email')"
                                                title="Envoyer un email">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="re-action re-action--done"
                                                :disabled="row.confirmed"
                                                @click="markDone(row)"
                                                title="Marquer comme relancé">
                                            <i class="fas fa-check"></i>
                                            <span x-show="row.confirmed">Relancé</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    {{-- Toast feedback --}}
    <div class="re-toast" x-show="toast" x-transition :class="'re-toast--' + (toastType || 'info')">
        <i class="fas" :class="toastType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
        <span x-text="toast"></span>
    </div>
</div>

<script>
function recouvrement(config) {
    return {
        rows: config.rows.map(r => ({ ...r, confirmed: false, lastRelanceId: null })),
        search: '',
        levelFilter: '',
        retardFilter: '',
        toast: null,
        toastType: 'info',
        config,

        get filteredRows() {
            return this.rows.filter(row => {
                if (this.levelFilter && row.level !== this.levelFilter) return false;
                if (this.retardFilter && row.jours_retard < parseInt(this.retardFilter)) return false;
                if (this.search && !row.etudiant_nom.toLowerCase().includes(this.search.toLowerCase())) return false;
                return true;
            });
        },

        formatMoney(value) {
            return new Intl.NumberFormat('fr-FR').format(Math.round(value || 0));
        },

        capitalize(s) {
            return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
        },

        buildMessage(row) {
            const prenom = row.prenoms || row.etudiant_nom.split(' ')[0];
            return this.config.whatsappTemplate
                .replace(/\{prenom\}/g, prenom)
                .replace(/\{nom\}/g, row.etudiant_nom)
                .replace(/\{solde\}/g, this.formatMoney(row.solde_restant))
                .replace(/\{retard\}/g, row.jours_retard)
                .replace(/\{ecole\}/g, this.config.schoolName);
        },

        async dispatch(row, channel) {
            const message = this.buildMessage(row);
            try {
                const response = await fetch(this.config.logIntentUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        inscription_id: row.inscription_id,
                        channel: channel,
                        message: message,
                    }),
                });
                const data = await response.json();

                if (!data.success) {
                    this.showToast(data.error_reason || data.error || 'Erreur', 'error');
                    return;
                }

                row.lastRelanceId = data.relance_id;
                if (data.deeplink_url && data.deeplink_url !== '#') {
                    window.open(data.deeplink_url, '_blank', 'noopener');
                }
                this.showToast('Action enregistrée — pensez à confirmer après envoi', 'info');
            } catch (e) {
                this.showToast('Erreur réseau', 'error');
            }
        },

        async markDone(row) {
            try {
                let url, body;
                if (row.lastRelanceId) {
                    url = this.config.confirmSentUrl;
                    body = { relance_id: row.lastRelanceId };
                } else {
                    url = this.config.markDoneUrl;
                    body = { inscription_id: row.inscription_id };
                }

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.config.csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(body),
                });
                const data = await response.json();

                if (data.success) {
                    row.confirmed = true;
                    this.showToast('Relance confirmée', 'success');
                } else {
                    this.showToast(data.error || 'Erreur', 'error');
                }
            } catch (e) {
                this.showToast('Erreur réseau', 'error');
            }
        },

        showToast(message, type = 'info') {
            this.toast = message;
            this.toastType = type;
            setTimeout(() => { this.toast = null; }, 3500);
        },
    };
}
</script>
@endsection

@push('styles')
<style>
:root {
    --re-primary: #0453cb;
    --re-primary-d: #033a8e;
    --re-secondary: #5e91de;
    --re-dark: #0f172a;
    --re-text: #1e293b;
    --re-muted: #64748b;
    --re-border: #e2e8f0;
    --re-success: #10b981;
    --re-warning: #f59e0b;
    --re-danger: #dc2626;
    --re-whatsapp: #25D366;
}

.re-page { padding: 1rem 0; }

/* Hero */
.re-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.75rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.re-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.re-hero-left { display: flex; align-items: center; gap: 1rem; }
.re-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.re-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.re-hero p { color: rgba(255,255,255,.72); font-size: .88rem; margin: 0; }
.re-hero-right { display: flex; gap: .5rem; flex-wrap: wrap; }

.re-btn {
    display: inline-flex; align-items: center; gap: .5rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; border: none; cursor: pointer;
    transition: all .2s ease;
}
.re-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border: 1px solid rgba(255,255,255,.2);
}
.re-btn--glass:hover {
    background: rgba(255,255,255,.25); color: #fff;
    transform: translateY(-1px);
}

.re-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.re-kpi {
    flex: 1; min-width: 180px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.re-kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem; color: #fff; flex-shrink: 0;
}
.re-kpi-value { font-size: 1.25rem; font-weight: 700; color: #fff; line-height: 1.1; }
.re-kpi-unit { font-size: .68rem; font-weight: 500; opacity: .65; }
.re-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .15rem; }

/* Filters */
.re-filters {
    display: flex; gap: .75rem; flex-wrap: wrap; align-items: center;
    background: #fff; padding: 1rem 1.25rem; border-radius: 12px;
    border: 1px solid var(--re-border); margin-bottom: 1rem;
}
.re-input {
    padding: .55rem .85rem; border-radius: 10px;
    border: 1px solid var(--re-border); font-size: .88rem;
    transition: border-color .15s ease;
    flex: 1; min-width: 180px; max-width: 280px;
}
.re-input:focus {
    outline: none; border-color: var(--re-primary);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.re-filter-stat {
    margin-left: auto; font-size: .82rem; color: var(--re-muted); font-weight: 600;
}

/* Card */
.re-card {
    background: #fff;
    border: 1px solid var(--re-border);
    border-radius: 14px;
    padding: 0;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    overflow: hidden;
}

/* Table */
.re-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.re-table thead th {
    background: #fafbfc; font-weight: 600; color: var(--re-text);
    border-bottom: 2px solid var(--re-border); padding: 1rem .85rem;
    text-align: left; font-size: .8rem; text-transform: uppercase;
    letter-spacing: .03em;
}
.re-table .text-end { text-align: right; }
.re-table .text-center { text-align: center; }
.re-table tbody td { padding: .85rem; vertical-align: middle; border-bottom: 1px solid var(--re-border); }
.re-table tbody tr:last-child td { border-bottom: none; }
.re-table tbody tr:hover { background: rgba(4,83,203,.02); }
.re-row--done { opacity: .55; background: rgba(16,185,129,.04) !important; }

.re-cell-student strong { color: var(--re-dark); }
.re-cell-student small { display: block; margin-top: .15rem; font-size: .72rem; }
.re-warn { color: var(--re-warning); }
.re-info { color: var(--re-muted); display: inline-flex; align-items: center; gap: .35rem; }
.re-info i { font-size: .65rem; }

.re-chip {
    display: inline-block; padding: .25rem .6rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600;
}
.re-chip--retard { background: rgba(245,158,11,.12); color: #b45309; }

.re-level {
    display: inline-block; padding: .25rem .65rem;
    border-radius: 999px; font-size: .72rem; font-weight: 600;
}
.re-level--haut { background: rgba(220,38,38,.12); color: var(--re-danger); }
.re-level--moyen { background: rgba(245,158,11,.12); color: #b45309; }
.re-level--bas { background: rgba(16,185,129,.12); color: #047857; }

/* Actions */
.re-actions { display: flex; gap: .35rem; justify-content: center; }
.re-action {
    width: 38px; height: 38px; border-radius: 10px;
    border: 1px solid var(--re-border); background: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .95rem; cursor: pointer; transition: all .15s ease;
    color: var(--re-muted);
}
.re-action:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(15,23,42,.08);
}
.re-action:disabled { opacity: .35; cursor: not-allowed; }

.re-action--whatsapp { color: var(--re-whatsapp); border-color: rgba(37,211,102,.2); }
.re-action--whatsapp:hover:not(:disabled) { background: rgba(37,211,102,.08); }

.re-action--tel { color: var(--re-primary); border-color: rgba(4,83,203,.2); }
.re-action--tel:hover:not(:disabled) { background: rgba(4,83,203,.08); }

.re-action--email { color: var(--re-muted); }
.re-action--email:hover:not(:disabled) { background: rgba(100,116,139,.08); color: var(--re-text); }

.re-action--done {
    color: var(--re-success); border-color: rgba(16,185,129,.25);
    width: auto; padding: 0 .85rem; gap: .35rem;
    font-size: .82rem; font-weight: 600;
}
.re-action--done:hover:not(:disabled) { background: rgba(16,185,129,.08); }

/* Empty */
.re-empty { text-align: center; padding: 3rem 1rem; color: var(--re-muted); }
.re-empty i { font-size: 2.5rem; color: var(--re-success); margin-bottom: .75rem; }
.re-empty p { margin: 0; font-size: .9rem; }

/* Toast */
.re-toast {
    position: fixed; bottom: 24px; right: 24px;
    padding: .85rem 1.25rem; border-radius: 12px;
    background: #fff; box-shadow: 0 8px 30px rgba(15,23,42,.15);
    border: 1px solid var(--re-border);
    display: flex; align-items: center; gap: .65rem;
    font-size: .9rem; z-index: 1000; max-width: 400px;
}
.re-toast--success { border-color: rgba(16,185,129,.3); color: #047857; }
.re-toast--success i { color: var(--re-success); }
.re-toast--error { border-color: rgba(220,38,38,.3); color: var(--re-danger); }
.re-toast--error i { color: var(--re-danger); }
.re-toast--info i { color: var(--re-primary); }

/* Responsive */
@media (max-width: 992px) {
    .re-actions { flex-wrap: wrap; }
}
@media (max-width: 768px) {
    .re-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .re-hero h1 { font-size: 1.2rem; }
    .re-kpi { min-width: 140px; }
    .re-table { font-size: .82rem; }
    .re-table thead th, .re-table tbody td { padding: .65rem .5rem; }
    .re-action { width: 34px; height: 34px; font-size: .85rem; }
    .re-action--done { padding: 0 .65rem; }
}
</style>
@endpush
