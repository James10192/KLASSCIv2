@props([
    'parcoursId' => null,
    'mentionId' => null,
    'label' => 'Détecter les doublons',
    'variant' => 'solid',
])

@can('lmd.reconciliation.manage')
<div class="lrt-root"
     x-data="lmdReconcileTrigger({ parcoursId: {{ $parcoursId ? (int) $parcoursId : 'null' }}, mentionId: {{ $mentionId ? (int) $mentionId : 'null' }} })"
     x-init="init()">

    <button type="button" class="lrt-btn lrt-btn--{{ $variant }}" @click="open()" :disabled="loading">
        <i class="fas fa-clone" x-show="!loading"></i>
        <i class="fas fa-circle-notch fa-spin" x-show="loading" x-cloak></i>
        <span>{{ $label }}</span>
        <span class="lrt-pill" x-show="badge !== null && badge > 0" x-cloak x-text="badge"></span>
    </button>

    {{-- Drawer (téléporté vers body au open pour échapper aux stacking contexts) --}}
    <div class="lrt-overlay" x-ref="drawerWrap" x-show="panel" x-cloak
         @click="close()"
         x-transition:enter="lrt-fade-enter" x-transition:leave="lrt-fade-leave">
        <aside class="lrt-drawer" @click.stop role="dialog" aria-label="Réconciliation des doublons"
               x-show="panel"
               x-transition:enter="lrt-slide-enter" x-transition:enter-start="lrt-slide-start"
               x-transition:leave="lrt-slide-leave" x-transition:leave-end="lrt-slide-start">

            <header class="lrt-head">
                <div class="lrt-head-l">
                    <span class="lrt-head-ic"><i class="fas fa-object-group"></i></span>
                    <div>
                        <h3>Réconciliation des doublons</h3>
                        <p x-text="scopeLabel">Toutes les UE / ECUE</p>
                    </div>
                </div>
                <button type="button" class="lrt-close" @click="close()" aria-label="Fermer"><i class="fas fa-xmark"></i></button>
            </header>

            <div class="lrt-kpis">
                <div class="lrt-kpi">
                    <span class="lrt-kpi-v" x-text="data.kpis?.ue_duplicate_groups ?? 0"></span>
                    <span class="lrt-kpi-l">Groupes UE</span>
                </div>
                <div class="lrt-kpi">
                    <span class="lrt-kpi-v" x-text="data.kpis?.ecue_duplicate_groups ?? 0"></span>
                    <span class="lrt-kpi-l">Groupes ECUE</span>
                </div>
                <div class="lrt-kpi">
                    <span class="lrt-kpi-v" x-text="data.kpis?.parcours_concerned ?? 0"></span>
                    <span class="lrt-kpi-l">Parcours</span>
                </div>
            </div>

            <div class="lrt-body">
                {{-- Loading --}}
                <div class="lrt-state" x-show="loading">
                    <i class="fas fa-circle-notch fa-spin"></i>
                    <span>Analyse des doublons…</span>
                </div>

                {{-- Empty --}}
                <div class="lrt-state lrt-state--ok" x-show="!loading && totalGroups === 0">
                    <i class="fas fa-circle-check"></i>
                    <span>Aucun doublon détecté.</span>
                    <small>Les UE et ECUE de ce périmètre sont distinctes.</small>
                </div>

                {{-- Groupes --}}
                <template x-for="(group, gi) in allGroups" :key="group._kind + '-' + gi">
                    <div class="lrt-group" :style="'animation-delay:' + (gi * 45) + 'ms'">
                        <div class="lrt-group-top">
                            <span class="lrt-tag" :class="group._kind === 'ue' ? 'lrt-tag--ue' : 'lrt-tag--ecue'" x-text="group._kind === 'ue' ? 'UE' : 'ECUE'"></span>
                            <span class="lrt-group-name" x-text="(group.candidates && group.candidates[0] && group.candidates[0].name) || group.normalized_name || '—'"></span>
                            <span class="lrt-count" x-text="(group.candidates?.length || group.count || 0) + ' variantes'"></span>
                        </div>
                        <div class="lrt-cands">
                            <template x-for="cand in (group.candidates || [])" :key="cand.id">
                                <div class="lrt-cand">
                                    <code class="lrt-code" x-text="cand.code || '—'"></code>
                                    <span class="lrt-cand-meta">
                                        <span class="lrt-chip" x-show="(cand.credit != null) || (cand.credit_ecue != null)"><i class="fas fa-coins"></i><span x-text="cand.credit != null ? cand.credit : cand.credit_ecue"></span> cr</span>
                                        <span class="lrt-chip" x-show="cand.coefficient_ecue != null"><i class="fas fa-scale-balanced"></i><span x-text="cand.coefficient_ecue"></span></span>
                                        <span class="lrt-chip" x-show="cand.niveau" x-text="cand.niveau"></span>
                                        <template x-for="p in (cand.parcours || [])" :key="p.id">
                                            <span class="lrt-chip lrt-chip--p" x-text="p.code || p.name"></span>
                                        </template>
                                        <span class="lrt-chip lrt-chip--p" x-show="cand.ue" x-text="(cand.ue && (cand.ue.code || cand.ue.name)) || ''"></span>
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <footer class="lrt-foot" x-show="!loading">
                <a href="{{ route('esbtp.lmd.reconciliation.index') }}" class="lrt-cta">
                    <i class="fas fa-arrow-right-arrow-left"></i>
                    <span x-text="totalGroups > 0 ? 'Ouvrir la réconciliation complète' : 'Ouvrir la réconciliation'"></span>
                </a>
            </footer>
        </aside>
    </div>
</div>

@once
@push('styles')
<style>
    .lrt-root { display: inline-flex; }
    /* Bouton */
    .lrt-btn {
        display: inline-flex; align-items: center; gap: .5rem;
        border-radius: 10px; padding: .55rem 1rem; font-size: .82rem; font-weight: 600;
        border: 1px solid transparent; cursor: pointer;
        transition: background .15s, color .15s, border-color .15s, box-shadow .15s;
    }
    .lrt-btn:disabled { opacity: .65; cursor: wait; }
    .lrt-btn--solid { background: #0453cb; color: #fff; }
    .lrt-btn--solid:hover:not(:disabled) { background: #033a8e; box-shadow: 0 6px 18px rgba(4,83,203,.25); }
    .lrt-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.25); }
    .lrt-btn--glass:hover:not(:disabled) { background: rgba(255,255,255,.25); }
    .lrt-btn--ghost { background: rgba(4,83,203,.07); color: #0453cb; border-color: rgba(4,83,203,.2); }
    .lrt-btn--ghost:hover:not(:disabled) { background: rgba(4,83,203,.12); }
    .lrt-pill {
        display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px;
        padding: 0 5px; border-radius: 9px; font-size: .68rem; font-weight: 800;
        background: #fff; color: #0453cb;
    }
    .lrt-btn--solid .lrt-pill { background: #fff; color: #0453cb; }
    .lrt-btn--glass .lrt-pill { background: #fff; color: #0453cb; }

    /* Overlay + Drawer */
    .lrt-overlay {
        position: fixed; inset: 0; z-index: 100000;
        background: rgba(15,23,42,.45); backdrop-filter: blur(3px);
        display: flex; justify-content: flex-end;
    }
    .lrt-drawer {
        width: 440px; max-width: 92vw; height: 100%;
        background: #f8fafc; display: flex; flex-direction: column;
        box-shadow: -12px 0 40px rgba(15,23,42,.25);
    }
    /* Transitions */
    .lrt-fade-enter { transition: opacity .2s ease; }
    .lrt-fade-leave { transition: opacity .2s ease; }
    .lrt-slide-enter { transition: transform .28s cubic-bezier(.22,.61,.36,1); }
    .lrt-slide-leave { transition: transform .22s ease-in; }
    .lrt-slide-start { transform: translateX(100%); }

    .lrt-head {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 45%, #3b7ddb 100%);
        color: #fff; padding: 1.1rem 1.25rem;
        display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem;
    }
    .lrt-head-l { display: flex; align-items: center; gap: .75rem; }
    .lrt-head-ic {
        width: 40px; height: 40px; border-radius: 11px; flex-shrink: 0;
        background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center; font-size: 1rem;
    }
    .lrt-head h3 { margin: 0; font-size: 1rem; font-weight: 700; }
    .lrt-head p { margin: .1rem 0 0; font-size: .74rem; color: rgba(255,255,255,.72); }
    .lrt-close {
        background: rgba(255,255,255,.12); border: none; color: #fff; cursor: pointer;
        width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
        transition: background .15s;
    }
    .lrt-close:hover { background: rgba(255,255,255,.25); }

    .lrt-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; padding: .9rem 1.25rem; background: #fff; border-bottom: 1px solid #eef2f7; }
    .lrt-kpi { text-align: center; }
    .lrt-kpi-v { display: block; font-size: 1.3rem; font-weight: 800; color: #0453cb; line-height: 1; }
    .lrt-kpi-l { font-size: .65rem; color: #64748b; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; }

    .lrt-body { flex: 1; overflow-y: auto; padding: 1rem 1.25rem; }
    .lrt-state { display: flex; flex-direction: column; align-items: center; gap: .5rem; padding: 2.5rem 1rem; color: #64748b; font-size: .85rem; text-align: center; }
    .lrt-state i { font-size: 1.6rem; color: #94a3b8; }
    .lrt-state small { font-size: .72rem; color: #94a3b8; }
    .lrt-state--ok i { color: #10b981; }
    .lrt-state--ok span { color: #047857; font-weight: 700; }

    .lrt-group {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
        padding: .75rem .85rem; margin-bottom: .65rem;
        animation: lrtIn .35s ease both;
    }
    @keyframes lrtIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .lrt-group-top { display: flex; align-items: center; gap: .5rem; }
    .lrt-tag { font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; padding: .15rem .4rem; border-radius: 5px; flex-shrink: 0; }
    .lrt-tag--ue { background: rgba(4,83,203,.1); color: #0453cb; }
    .lrt-tag--ecue { background: rgba(59,125,219,.12); color: #3b7ddb; }
    .lrt-group-name { font-size: .85rem; font-weight: 700; color: #1e293b; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .lrt-count { font-size: .68rem; color: #94a3b8; font-weight: 600; flex-shrink: 0; }
    .lrt-cands { margin-top: .5rem; display: flex; flex-direction: column; gap: .35rem; }
    .lrt-cand { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .lrt-code { font-family: 'Courier New', monospace; font-size: .72rem; font-weight: 700; color: #0453cb; background: rgba(4,83,203,.07); padding: .12rem .45rem; border-radius: 5px; }
    .lrt-cand-meta { display: flex; align-items: center; gap: .3rem; flex-wrap: wrap; }
    .lrt-chip { display: inline-flex; align-items: center; gap: .2rem; font-size: .66rem; font-weight: 600; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; padding: .1rem .4rem; border-radius: 5px; }
    .lrt-chip i { font-size: .6rem; color: #94a3b8; }
    .lrt-chip--p { color: #0453cb; background: rgba(4,83,203,.06); border-color: rgba(4,83,203,.18); }

    .lrt-foot { padding: .9rem 1.25rem; background: #fff; border-top: 1px solid #eef2f7; }
    .lrt-cta {
        display: flex; align-items: center; justify-content: center; gap: .5rem;
        width: 100%; padding: .65rem; border-radius: 10px; text-decoration: none;
        background: #0453cb; color: #fff; font-size: .84rem; font-weight: 700;
        transition: background .15s, box-shadow .15s;
    }
    .lrt-cta:hover { background: #033a8e; color: #fff; box-shadow: 0 6px 18px rgba(4,83,203,.25); }

    @media (max-width: 576px) { .lrt-drawer { width: 100vw; } }
    [x-cloak] { display: none !important; }
</style>
@endpush

@push('scripts')
<script>
if (typeof window.lmdReconcileTrigger !== 'function') {
    window.lmdReconcileTrigger = function (cfg) {
        return {
            parcoursId: cfg.parcoursId,
            mentionId: cfg.mentionId,
            loading: false,
            panel: false,
            badge: null,
            data: { ue_groups: [], ecue_groups: [], kpis: {} },
            _ph: null,
            _esc: null,

            init() {
                this._esc = (e) => { if (e.key === 'Escape' && this.panel) { this.close(); } };
                window.addEventListener('keydown', this._esc);
            },
            destroy() {
                if (this._esc) { window.removeEventListener('keydown', this._esc); this._esc = null; }
                this._restore();
            },

            get allGroups() {
                const ue = (this.data.ue_groups || []).map((g) => Object.assign({ _kind: 'ue' }, g));
                const ecue = (this.data.ecue_groups || []).map((g) => Object.assign({ _kind: 'ecue' }, g));
                return ue.concat(ecue);
            },
            get totalGroups() {
                return (this.data.ue_groups || []).length + (this.data.ecue_groups || []).length;
            },
            get scopeLabel() {
                if (this.parcoursId) { return 'Périmètre : parcours sélectionné'; }
                if (this.mentionId) { return 'Périmètre : mention sélectionnée'; }
                return 'Toutes les UE / ECUE';
            },

            _teleport() {
                const d = this.$refs.drawerWrap;
                if (!d || d._tp) { return; }
                this._ph = document.createComment('lrt-placeholder');
                d.parentNode.insertBefore(this._ph, d);
                document.body.appendChild(d);
                d._tp = true;
            },
            _restore() {
                const d = this.$refs.drawerWrap;
                if (d && d._tp && this._ph && this._ph.parentNode) {
                    this._ph.parentNode.insertBefore(d, this._ph);
                    this._ph.remove();
                    this._ph = null;
                    d._tp = false;
                }
            },

            async open() {
                this._teleport();
                this.panel = true;
                await this.detect();
            },
            close() {
                this.panel = false;
                setTimeout(() => this._restore(), 260);
            },

            async detect() {
                this.loading = true;
                try {
                    const params = new URLSearchParams();
                    if (this.parcoursId) { params.set('parcours_id', this.parcoursId); }
                    if (this.mentionId) { params.set('mention_id', this.mentionId); }
                    const url = "{{ route('esbtp.lmd.reconciliation.detect') }}" + (params.toString() ? ('?' + params.toString()) : '');
                    const res = await fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                    if (!res.ok) {
                        const body = await res.json().catch(() => ({}));
                        throw new Error(body.message || ('Erreur ' + res.status));
                    }
                    this.data = await res.json();
                    this.badge = ((this.data.kpis && this.data.kpis.ue_duplicate_groups) || 0)
                               + ((this.data.kpis && this.data.kpis.ecue_duplicate_groups) || 0);
                } catch (err) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: err.message || 'Détection impossible.' } }));
                } finally {
                    this.loading = false;
                }
            },
        };
    };
}
</script>
@endpush
@endonce
@endcan
