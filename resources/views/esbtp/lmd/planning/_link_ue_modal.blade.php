{{--
    Premium modal "Lier des UE au parcours" — namespace lpm-*
    Self-contained : CSS + Alpine factory + markup.

    Pattern : Alpine.data() registered in <script>, state via data-* + JSON.parse
    (Blade-safe — pas de {{ }} dans des object literals Alpine).

    Communication :
      - Reçoit  : @lpm:open.window={ detail: { parcoursId, parcoursName } }
      - Émet    : lpm:saved → écouté par lpPlanning pour fetchPartial()
--}}

@once
@push('styles')
<style>
    [x-cloak] { display: none !important; }

    .lpm-overlay {
        position: fixed; inset: 0; z-index: 1080;
        background: rgba(10, 15, 30, .72);
        backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
        display: flex; align-items: flex-start; justify-content: center;
        padding: 2rem 1rem;
        overflow-y: auto;
    }
    .lpm-card {
        background: #fff; border-radius: 18px; width: 100%; max-width: 860px;
        box-shadow: 0 32px 80px rgba(0,0,0,.35), 0 0 0 1px rgba(255,255,255,.06);
        overflow: hidden; display: flex; flex-direction: column; max-height: calc(100vh - 4rem);
    }
    .lpm-header {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%);
        padding: 1.5rem 2rem 1.25rem; color: #fff;
        display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;
    }
    .lpm-header-left { display: flex; align-items: center; gap: 1rem; }
    .lpm-header-icon {
        width: 48px; height: 48px; border-radius: 12px;
        background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .lpm-header h2 { font-size: 1.15rem; font-weight: 700; margin: 0; color: #fff; }
    .lpm-header p { font-size: .82rem; color: rgba(255,255,255,.72); margin: .15rem 0 0; }
    .lpm-close {
        background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
        color: #fff; width: 36px; height: 36px; border-radius: 10px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; font-size: .95rem;
        transition: all .15s ease;
    }
    .lpm-close:hover { background: rgba(255,255,255,.22); }

    .lpm-toolbar {
        padding: .85rem 2rem; border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        flex-wrap: wrap;
    }
    .lpm-search {
        flex: 1 1 280px; min-width: 200px;
        padding: .55rem .85rem; border: 1px solid #e2e8f0; border-radius: 10px;
        font-size: .88rem; color: #1e293b;
        transition: border-color .15s, box-shadow .15s;
    }
    .lpm-search:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
    .lpm-counter {
        font-size: .78rem; color: #64748b; font-weight: 600;
        white-space: nowrap;
    }
    .lpm-counter strong { color: #0453cb; font-weight: 700; }

    .lpm-body {
        flex: 1 1 auto; overflow-y: auto; padding: 0;
        max-height: 60vh;
    }
    .lpm-table { width: 100%; border-collapse: collapse; }
    .lpm-table th {
        text-align: left; font-size: .68rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: .04em;
        padding: .75rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        position: sticky; top: 0; z-index: 1;
    }
    .lpm-table th.lpm-th-num { text-align: center; width: 100px; }
    .lpm-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .12s ease; }
    .lpm-table tbody tr:hover { background: #f8fafc; }
    .lpm-table tbody tr.lpm-row-selected { background: rgba(4,83,203,.04); }
    .lpm-table td { padding: .65rem 1rem; vertical-align: middle; font-size: .87rem; color: #1e293b; }

    .lpm-cell-check { width: 36px; }
    .lpm-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #0453cb; }
    .lpm-ue-code {
        font-family: 'SF Mono', Consolas, monospace; font-size: .76rem;
        background: rgba(4,83,203,.08); color: #0453cb;
        padding: .15rem .5rem; border-radius: 6px; margin-right: .5rem;
        font-weight: 600;
    }
    .lpm-ue-code-virtual { background: rgba(100,116,139,.1); color: #64748b; font-style: italic; font-family: inherit; }
    .lpm-cell-sem select, .lpm-cell-opt {
        cursor: pointer;
    }
    .lpm-mini-select {
        padding: .35rem .55rem; border: 1px solid #e2e8f0; border-radius: 8px;
        font-size: .82rem; background: #fff; color: #1e293b;
        font-family: inherit;
    }
    .lpm-mini-select:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 2px rgba(4,83,203,.12); }
    .lpm-mini-select:disabled { background: #f8fafc; color: #94a3b8; cursor: not-allowed; }

    .lpm-empty { padding: 3rem 2rem; text-align: center; color: #64748b; font-size: .9rem; }
    .lpm-empty-icon { font-size: 2rem; color: #cbd5e1; margin-bottom: .75rem; }
    .lpm-loading { padding: 3rem; text-align: center; color: #64748b; font-size: .88rem; }
    .lpm-spin {
        display: inline-block; width: 18px; height: 18px;
        border: 2px solid #e2e8f0; border-top-color: #0453cb;
        border-radius: 50%; animation: lpm-spin .7s linear infinite;
        margin-right: .5rem; vertical-align: -3px;
    }
    @@keyframes lpm-spin { to { transform: rotate(360deg); } }

    .lpm-footer {
        padding: 1rem 2rem; border-top: 1px solid #e2e8f0; background: #f8fafc;
        display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        flex-wrap: wrap;
    }
    .lpm-feedback { font-size: .82rem; color: #64748b; }
    .lpm-feedback.lpm-feedback-error { color: #b91c1c; font-weight: 600; }
    .lpm-actions { display: flex; gap: .65rem; }
    .lpm-btn {
        padding: .55rem 1.15rem; border-radius: 10px; font-size: .85rem; font-weight: 600;
        border: 1px solid transparent; cursor: pointer; transition: all .15s ease;
        display: inline-flex; align-items: center; gap: .4rem;
    }
    .lpm-btn-secondary { background: #fff; color: #475569; border-color: #e2e8f0; }
    .lpm-btn-secondary:hover { background: #f1f5f9; }
    .lpm-btn-primary { background: #0453cb; color: #fff; }
    .lpm-btn-primary:hover { background: #033a8e; }
    .lpm-btn:disabled { opacity: .55; cursor: not-allowed; }

    @@media (max-width: 640px) {
        .lpm-overlay { padding: 0; }
        .lpm-card { border-radius: 0; max-height: 100vh; height: 100vh; }
        .lpm-header, .lpm-toolbar, .lpm-footer { padding-left: 1rem; padding-right: 1rem; }
        .lpm-table th, .lpm-table td { padding: .55rem .65rem; font-size: .8rem; }
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('lpmModal', () => ({
        open: false,
        loading: false,
        saving: false,
        error: null,
        parcoursId: null,
        parcoursName: '',
        search: '',
        rows: [],

        init() {
            // Listen for trigger button anywhere in the page
            window.addEventListener('lpm:open', (event) => {
                this.openWith(event.detail || {});
            });
        },

        get filteredRows() {
            if (!this.search.trim()) return this.rows;
            const q = this.search.trim().toLowerCase();
            return this.rows.filter(r =>
                (r.name || '').toLowerCase().includes(q) ||
                (r.code || '').toLowerCase().includes(q)
            );
        },

        get selectedCount() {
            return this.rows.filter(r => r.selected).length;
        },

        async openWith(detail) {
            if (!detail.parcoursId) {
                console.error('lpm:open requires parcoursId in detail');
                return;
            }
            this.parcoursId = detail.parcoursId;
            this.parcoursName = detail.parcoursName || '';
            this.search = '';
            this.error = null;
            this.open = true;
            await this.fetchUes();
        },

        close() {
            this.open = false;
            this.rows = [];
            this.error = null;
        },

        async fetchUes() {
            this.loading = true;
            this.error = null;
            try {
                const url = `/esbtp/lmd/parcours/${this.parcoursId}/ues-disponibles`;
                const resp = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                const json = await resp.json();
                this.rows = this.buildRows(json.liees || [], json.disponibles || []);
            } catch (e) {
                console.error('Failed to load UEs:', e);
                this.error = 'Impossible de charger les UE. Réessayez.';
            } finally {
                this.loading = false;
            }
        },

        buildRows(liees, disponibles) {
            const rows = [];
            // Existing links: pre-selected, semestre = first existing
            for (const ue of liees) {
                rows.push({
                    id: ue.id,
                    code: ue.code || '',
                    name: ue.name,
                    selected: true,
                    semestre: (ue.semestres && ue.semestres[0]) || 1,
                    is_optional: false,
                });
            }
            // Available: not selected, semestre default 1
            for (const ue of disponibles) {
                rows.push({
                    id: ue.id,
                    code: ue.code || '',
                    name: ue.name,
                    selected: false,
                    semestre: 1,
                    is_optional: false,
                });
            }
            // Sort: selected first, then by code
            rows.sort((a, b) => {
                if (a.selected !== b.selected) return b.selected - a.selected;
                return (a.code || a.name).localeCompare(b.code || b.name);
            });
            return rows;
        },

        async submit() {
            this.saving = true;
            this.error = null;
            const payload = {
                ues: this.rows
                    .filter(r => r.selected)
                    .map(r => ({
                        id: r.id,
                        semestres: [parseInt(r.semestre, 10) || 1],
                        is_optional: !!r.is_optional,
                    })),
            };
            try {
                const url = `/esbtp/lmd/parcours/${this.parcoursId}/sync-ues`;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const resp = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrf,
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });
                if (!resp.ok) {
                    const errJson = await resp.json().catch(() => ({}));
                    throw new Error(errJson.message || 'HTTP ' + resp.status);
                }
                const json = await resp.json();
                // Notify parent component to re-render listing + KPIs
                window.dispatchEvent(new CustomEvent('lpm:saved', { detail: json }));
                this.close();
            } catch (e) {
                console.error('Save failed:', e);
                this.error = e.message || 'Échec de l\'enregistrement.';
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endpush

<div
    x-data="lpmModal"
    x-show="open"
    x-cloak
    @keydown.escape.window="open && !saving && close()"
    x-transition.opacity.duration.150ms
    class="lpm-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="lpm-title">

    <div
        @click.outside="open && !saving && close()"
        x-trap.inert.noscroll="open"
        x-transition:enter="lpm-card-enter"
        class="lpm-card">

        {{-- Header --}}
        <div class="lpm-header">
            <div class="lpm-header-left">
                <div class="lpm-header-icon"><i class="fas fa-link"></i></div>
                <div>
                    <h2 id="lpm-title">Lier des UE au parcours</h2>
                    <p x-text="parcoursName ? parcoursName : 'Sélectionnez les unités d\'enseignement à associer'"></p>
                </div>
            </div>
            <button type="button" class="lpm-close" @click="close()" aria-label="Fermer">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Toolbar --}}
        <div class="lpm-toolbar">
            <input
                type="text"
                class="lpm-search"
                placeholder="Rechercher une UE par nom ou code..."
                x-model="search"
                aria-label="Recherche UE">
            <div class="lpm-counter">
                <strong x-text="selectedCount"></strong> UE sélectionnée<span x-show="selectedCount > 1">s</span>
                <span x-show="rows.length > 0"> / <span x-text="rows.length"></span></span>
            </div>
        </div>

        {{-- Body --}}
        <div class="lpm-body">
            <template x-if="loading">
                <div class="lpm-loading">
                    <span class="lpm-spin"></span> Chargement des UE...
                </div>
            </template>

            <template x-if="!loading && rows.length === 0 && !error">
                <div class="lpm-empty">
                    <div class="lpm-empty-icon"><i class="fas fa-cubes"></i></div>
                    Aucune UE disponible. Créez-en d'abord via le module Unités d'enseignement.
                </div>
            </template>

            <template x-if="!loading && rows.length > 0">
                <table class="lpm-table">
                    <thead>
                        <tr>
                            <th class="lpm-cell-check"></th>
                            <th>Unité d'enseignement</th>
                            <th class="lpm-th-num">Semestre</th>
                            <th class="lpm-th-num">Optionnelle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in filteredRows" :key="row.id">
                            <tr :class="row.selected ? 'lpm-row-selected' : ''">
                                <td class="lpm-cell-check">
                                    <input type="checkbox" class="lpm-checkbox"
                                        x-model="row.selected"
                                        :aria-label="'Sélectionner ' + row.name">
                                </td>
                                <td>
                                    <template x-if="row.code">
                                        <span class="lpm-ue-code" x-text="row.code"></span>
                                    </template>
                                    <template x-if="!row.code">
                                        <span class="lpm-ue-code lpm-ue-code-virtual">virtuelle</span>
                                    </template>
                                    <span x-text="row.name"></span>
                                </td>
                                <td class="lpm-cell-sem" style="text-align:center;">
                                    <select class="lpm-mini-select"
                                        x-model.number="row.semestre"
                                        :disabled="!row.selected"
                                        :aria-label="'Semestre pour ' + row.name">
                                        <template x-for="n in 10" :key="n">
                                            <option :value="n" x-text="'S' + n"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="lpm-cell-opt" style="text-align:center;">
                                    <input type="checkbox" class="lpm-checkbox"
                                        x-model="row.is_optional"
                                        :disabled="!row.selected"
                                        :aria-label="'UE optionnelle: ' + row.name">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </template>
        </div>

        {{-- Footer --}}
        <div class="lpm-footer">
            <div class="lpm-feedback" :class="error ? 'lpm-feedback-error' : ''">
                <template x-if="error">
                    <span><i class="fas fa-exclamation-triangle"></i> <span x-text="error"></span></span>
                </template>
                <template x-if="!error && !loading">
                    <span x-show="selectedCount > 0">Cliquez sur "Enregistrer" pour appliquer.</span>
                </template>
            </div>
            <div class="lpm-actions">
                <button type="button" class="lpm-btn lpm-btn-secondary" @click="close()" :disabled="saving">
                    Annuler
                </button>
                <button type="button" class="lpm-btn lpm-btn-primary" @click="submit()" :disabled="saving || loading">
                    <template x-if="saving"><span class="lpm-spin"></span></template>
                    <i x-show="!saving" class="fas fa-check"></i>
                    <span x-text="saving ? 'Enregistrement...' : 'Enregistrer'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endonce
