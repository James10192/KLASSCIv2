@props([
    'name' => 'parcours_id',
    'value' => null,
    'parcours' => collect(),
    'mentionFilter' => null,
    'placeholder' => 'Sélectionner un parcours',
    'searchable' => true,
])

@php
    $parcoursCollection = $parcours instanceof \Illuminate\Support\Collection
        ? $parcours
        : collect($parcours);

    // Flat array (pas de groupement Domaine — le picker est toujours filtre a 1 mention
    // donc grouper n'a aucune valeur). Chaque entree contient mention_id pour cascade JS.
    $parcoursJson = $parcoursCollection->map(function ($p) {
        $mentionName = optional($p->mention)->name ?? '';
        return [
            'id' => $p->id,
            'name' => $p->name,
            'code' => $p->code ?? '',
            'mentionId' => $p->mention_id,
            'mentionName' => $mentionName,
            'initial' => mb_strtoupper(mb_substr(trim($p->code ?: $p->name ?: '?'), 0, 1, 'UTF-8'), 'UTF-8'),
        ];
    })->values()->all();

    $totalParcours = $parcoursCollection->count();
@endphp

<div class="au-pp {{ $attributes->get('class') ?? '' }}"
     x-data="auParcoursPicker()"
     data-parcours='@json($parcoursJson)'
     data-current="{{ $value ?? '' }}"
     data-mention-filter="{{ $mentionFilter ?? '' }}"
     @click.outside="open = false"
     @keydown.escape="open = false">

    <button type="button"
            class="au-pp-trigger"
            :class="{ 'au-pp-trigger--open': open, 'au-pp-trigger--has-value': currentValue !== '', 'au-pp-trigger--disabled': !mentionFilter }"
            :disabled="!mentionFilter"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-haspopup="listbox">
        <template x-if="!selectedParcours && mentionFilter">
            <span class="au-pp-trigger-empty">
                <i class="fas fa-route au-pp-trigger-icon"></i>
                <span>— Aucun parcours (tronc commun mention) —</span>
            </span>
        </template>
        <template x-if="!selectedParcours && !mentionFilter">
            <span class="au-pp-trigger-empty">
                <i class="fas fa-lock au-pp-trigger-icon"></i>
                <span>Choisissez d'abord une mention</span>
            </span>
        </template>
        <template x-if="selectedParcours">
            <span class="au-pp-trigger-selected">
                <span class="au-pp-chip" x-text="selectedParcours.initial"></span>
                <span class="au-pp-trigger-info">
                    <span class="au-pp-trigger-name" x-text="selectedParcours.name"></span>
                    <span class="au-pp-trigger-code" x-show="selectedParcours.code" x-text="selectedParcours.code"></span>
                </span>
            </span>
        </template>
        <i class="fas fa-chevron-down au-pp-caret" :class="{ 'au-pp-caret--open': open }"></i>
    </button>

    <div class="au-pp-menu" x-show="open" x-cloak>
        @if($searchable)
        <div class="au-pp-search">
            <i class="fas fa-search"></i>
            <input type="text" x-model="search" x-ref="searchInput" @click.stop
                   @keydown.escape.stop="open = false"
                   placeholder="Rechercher par nom ou code…">
            <button type="button" x-show="search.length > 0"
                    @click="search = ''; $refs.searchInput.focus()"
                    class="au-pp-search-clear" aria-label="Effacer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        @endif

        <div class="au-pp-stats" x-show="!search">
            <span class="au-pp-stats-pill">
                <i class="fas fa-route"></i>
                <strong x-text="visibleParcours.length"></strong>
                <span x-text="visibleParcours.length > 1 ? 'parcours disponibles' : 'parcours disponible'"></span>
            </span>
        </div>

        <div class="au-pp-options" role="listbox">
            {{-- Sentinel "Aucun parcours (tronc commun)" toujours en tete --}}
            <button type="button" class="au-pp-option au-pp-option--sentinel"
                    :class="{ 'au-pp-option--active': currentValue === '' }"
                    @click="selectNone()" role="option"
                    :aria-selected="(currentValue === '').toString()">
                <span class="au-pp-chip au-pp-chip--muted"><i class="fas fa-layer-group"></i></span>
                <span class="au-pp-option-info">
                    <span class="au-pp-option-name">Aucun parcours (tronc commun mention)</span>
                    <span class="au-pp-option-meta">Classe au niveau de la mention, sans spécialisation</span>
                </span>
                <i class="fas fa-check au-pp-option-check" x-show="currentValue === ''"></i>
            </button>

            <template x-if="visibleParcours.length === 0 && totalParcours > 0 && !search">
                <div class="au-pp-empty">
                    <i class="fas fa-folder-open"></i>
                    <span>Aucun parcours pour cette mention. La classe sera créée en tronc commun.</span>
                </div>
            </template>

            <template x-if="visibleParcours.length === 0 && search">
                <div class="au-pp-empty">
                    <i class="fas fa-search"></i>
                    <span>Aucun parcours ne correspond à <strong x-text="search"></strong></span>
                </div>
            </template>

            <template x-for="p in visibleParcours" :key="p.id">
                <button type="button" class="au-pp-option"
                        :class="{ 'au-pp-option--active': String(currentValue) === String(p.id) }"
                        @click="select(p)" role="option"
                        :aria-selected="(String(currentValue) === String(p.id)).toString()">
                    <span class="au-pp-chip" x-text="p.initial"></span>
                    <span class="au-pp-option-info">
                        <span class="au-pp-option-name" x-text="p.name"></span>
                        <span class="au-pp-option-meta">
                            <span class="au-pp-code" x-show="p.code" x-text="p.code"></span>
                            <span class="au-pp-mention-hint" x-show="p.mentionName" x-text="p.mentionName"></span>
                        </span>
                    </span>
                    <i class="fas fa-check au-pp-option-check" x-show="String(currentValue) === String(p.id)"></i>
                </button>
            </template>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" :value="currentValue" x-ref="native">
</div>

{{--
    INLINE <style> + <script> (pattern AJAX-safe valide en PR1 le 14 mai 2026).
    @push sans @stack parent = drop silencieux en reponse AJAX standalone.
    Idempotency guard `if typeof window.auParcoursPicker !== 'function'` empeche
    le double-register au render multiple sur la meme page.
    Listener cleanup destroy() pour anti memory leak (bug Critic identifie PR1).
    Voir rule .claude/rules/premium-selects.md section AJAX-safe pattern.
--}}
<style>
.au-pp { position: relative; flex: 1; min-width: 0; display: flex; }
.au-pp-trigger {
    width: 100%; display: flex; align-items: center; gap: .65rem;
    padding: .5rem .8rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    cursor: pointer; transition: border-color .15s, box-shadow .15s;
    text-align: left; line-height: 1.2;
}
.au-pp-trigger:hover:not(.au-pp-trigger--disabled) { border-color: #cbd5e1; }
.au-pp-trigger--open { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.au-pp-trigger--disabled { background: #f8fafc; cursor: not-allowed; opacity: .65; }
.au-pp-trigger-empty { display: flex; align-items: center; gap: .5rem; flex: 1; color: #64748b; font-size: .85rem; min-width: 0; }
.au-pp-trigger-empty span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-pp-trigger-icon { color: #94a3b8; }
.au-pp-trigger-selected { display: flex; align-items: center; gap: .65rem; flex: 1; min-width: 0; }
.au-pp-trigger-info { display: flex; flex-direction: column; min-width: 0; }
.au-pp-trigger-name { font-size: .85rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-pp-trigger-code { font-size: .68rem; color: #0453cb; text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }
.au-pp-caret { color: #94a3b8; font-size: .72rem; flex-shrink: 0; transition: transform .2s; margin-left: auto; }
.au-pp-caret--open { transform: rotate(180deg); color: #0453cb; }
.au-pp-chip {
    width: 32px; height: 32px; border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: .82rem;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(4,83,203,.25);
}
.au-pp-chip--muted {
    background: linear-gradient(135deg, #94a3b8, #cbd5e1);
    box-shadow: 0 1px 3px rgba(100,116,139,.2);
}
.au-pp-menu {
    position: absolute; top: calc(100% + 6px);
    left: 0; right: 0;
    min-width: 360px;
    z-index: 1050;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 16px 50px rgba(15,23,42,.14), 0 4px 12px rgba(15,23,42,.06);
    overflow: hidden; max-height: 480px;
    display: flex; flex-direction: column;
}
.au-pp-search {
    display: flex; align-items: center; gap: .6rem;
    padding: .65rem .85rem;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}
.au-pp-search i { color: #94a3b8; font-size: .8rem; flex-shrink: 0; }
.au-pp-search input {
    flex: 1; border: 0; background: transparent;
    font-size: .85rem; color: #0f172a; padding: .15rem 0; outline: none;
}
.au-pp-search input::placeholder { color: #94a3b8; }
.au-pp-search-clear {
    border: 0; background: transparent; color: #94a3b8; cursor: pointer;
    width: 22px; height: 22px; border-radius: 50%; display: flex;
    align-items: center; justify-content: center; padding: 0; font-size: .68rem;
    transition: background .12s, color .12s;
}
.au-pp-search-clear:hover { background: #e2e8f0; color: #475569; }
.au-pp-stats {
    padding: .5rem .85rem;
    border-bottom: 1px solid #f1f5f9;
}
.au-pp-stats-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    background: rgba(4,83,203,.08);
    color: #033a8e;
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 999px;
    padding: .15rem .65rem;
    font-size: .72rem; font-weight: 600;
}
.au-pp-stats-pill i { font-size: .68rem; opacity: .8; }
.au-pp-options { overflow-y: auto; padding: .25rem; max-height: 380px; }
.au-pp-options::-webkit-scrollbar { width: 6px; }
.au-pp-options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.au-pp-option {
    width: 100%; display: flex; align-items: center; gap: .65rem;
    padding: .5rem .65rem;
    background: transparent; border: 0; border-radius: 10px;
    cursor: pointer; text-align: left;
    transition: background .12s;
}
.au-pp-option:hover { background: rgba(4,83,203,.06); }
.au-pp-option--active { background: rgba(4,83,203,.12); }
.au-pp-option--sentinel {
    margin-bottom: .25rem;
    border: 1px dashed rgba(148,163,184,.35);
}
.au-pp-option--sentinel:hover { background: rgba(148,163,184,.08); }
.au-pp-option-info { display: flex; flex-direction: column; min-width: 0; flex: 1; }
.au-pp-option-name { font-size: .85rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-pp-option-meta { display: flex; align-items: center; gap: .4rem; margin-top: .15rem; font-size: .7rem; color: #64748b; flex-wrap: wrap; }
.au-pp-code {
    background: rgba(4,83,203,.1); color: #0453cb;
    padding: .05rem .4rem; border-radius: 4px;
    font-weight: 600; font-size: .68rem; letter-spacing: .3px;
}
.au-pp-mention-hint {
    font-size: .7rem; color: #94a3b8; font-style: italic;
}
.au-pp-option-check { color: #0453cb; font-size: .78rem; flex-shrink: 0; }
.au-pp-empty {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 1.5rem 1rem;
    color: #64748b; text-align: center; font-size: .82rem; gap: .4rem;
}
.au-pp-empty i { color: #cbd5e1; font-size: 1.6rem; }
@@media (max-width: 768px) {
    .au-pp-menu { width: calc(100vw - 2rem); min-width: 0; max-height: 70vh; }
}
</style>

<script>
if (typeof window.auParcoursPicker !== 'function') {
    window.auParcoursPicker = function () {
        return {
            open: false,
            search: '',
            parcours: [],
            currentValue: '',
            totalParcours: 0,
            mentionFilter: '',
            _mentionChangedHandler: null,

            init() {
                try { this.parcours = JSON.parse(this.$el.dataset.parcours || '[]'); }
                catch (e) { this.parcours = []; }
                this.currentValue = this.$el.dataset.current || '';
                this.mentionFilter = this.$el.dataset.mentionFilter || '';
                this.totalParcours = this.parcours.length;

                this.$nextTick(() => {
                    if (this.$refs.native) this.$refs.native.value = this.currentValue;
                });

                // Listener cascade depuis mention picker (pattern AJAX-safe valide en PR1).
                // Stocker la reference pour cleanup dans destroy() — anti memory leak modal reopen.
                this._mentionChangedHandler = (ev) => {
                    var newMention = ev.detail.mentionId ? String(ev.detail.mentionId) : '';
                    this.mentionFilter = newMention;
                    // Si le parcours actuellement choisi n'appartient plus a la mention selectionnee, reset
                    if (this.currentValue) {
                        var current = this.parcours.find(p => String(p.id) === String(this.currentValue));
                        if (current && String(current.mentionId) !== this.mentionFilter) {
                            this.resetValue();
                        }
                    }
                };
                window.addEventListener('mention:changed', this._mentionChangedHandler);
            },

            destroy() {
                // Alpine appelle destroy() automatiquement quand le composant est retire du DOM.
                if (this._mentionChangedHandler) {
                    window.removeEventListener('mention:changed', this._mentionChangedHandler);
                    this._mentionChangedHandler = null;
                }
            },

            toggle() {
                if (!this.mentionFilter) return;
                this.open = !this.open;
                if (this.open) this.$nextTick(() => { if (this.$refs.searchInput) this.$refs.searchInput.focus(); });
            },

            get visibleParcours() {
                var s = this.search.trim().toLowerCase();
                var mention = this.mentionFilter;
                return this.parcours.filter(p => {
                    if (mention && String(p.mentionId) !== String(mention)) return false;
                    if (!s) return true;
                    return p.name.toLowerCase().includes(s)
                        || (p.code || '').toLowerCase().includes(s)
                        || (p.mentionName || '').toLowerCase().includes(s);
                });
            },

            get selectedParcours() {
                if (!this.currentValue) return null;
                return this.parcours.find(p => String(p.id) === String(this.currentValue)) || null;
            },

            select(p) {
                this.currentValue = String(p.id);
                this.open = false;
                this.search = '';
                this.$nextTick(() => {
                    if (this.$refs.native) {
                        this.$refs.native.value = this.currentValue;
                        this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    this.dispatchChange(p);
                });
            },

            selectNone() {
                this.currentValue = '';
                this.open = false;
                this.search = '';
                this.$nextTick(() => {
                    if (this.$refs.native) {
                        this.$refs.native.value = '';
                        this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    this.dispatchChange(null);
                });
            },

            reset() {
                this.resetValue();
                this.mentionFilter = '';
                this.search = '';
                this.open = false;
            },

            resetValue() {
                this.currentValue = '';
                if (this.$refs.native) {
                    this.$refs.native.value = '';
                    this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                }
            },

            dispatchChange(p) {
                window.dispatchEvent(new CustomEvent('parcours:changed', {
                    detail: p ? {
                        parcoursId: p.id,
                        mentionId: p.mentionId,
                        code: p.code,
                        name: p.name,
                    } : {
                        parcoursId: null,
                        mentionId: null,
                        code: null,
                        name: null,
                    }
                }));
            },
        };
    };
}
</script>
