@props([
    'name' => 'filiere_id',
    'value' => null,
    'mentions' => collect(),
    'placeholder' => 'Sélectionner une mention',
    'searchable' => true,
])

@php
    $mentionsCollection = $mentions instanceof \Illuminate\Support\Collection
        ? $mentions
        : collect($mentions);

    // Grouper par Domaine. Chaque mention rapporte aussi son domaine pour l'affichage chip.
    $groupedJson = collect();
    $grouped = $mentionsCollection->groupBy(function ($m) {
        return optional($m->domaine)->id ?? 0;
    });

    foreach ($grouped as $domaineId => $bucket) {
        $domaine = $bucket->first()->domaine ?? null;
        $domaineName = $domaine ? $domaine->name : 'Sans domaine';
        $domaineCode = $domaine && $domaine->code ? $domaine->code : '';

        $groupedJson->push([
            'key' => 'd-' . ($domaine ? $domaine->id : 'none'),
            'domaineId' => $domaine ? $domaine->id : null,
            'domaineName' => $domaineName,
            'domaineCode' => $domaineCode,
            'mentions' => $bucket->map(function ($m) use ($domaineName) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'code' => $m->code ?? '',
                    'domaineName' => $domaineName,
                    'initial' => mb_strtoupper(mb_substr(trim($m->name ?? '?'), 0, 1, 'UTF-8'), 'UTF-8'),
                ];
            })->sortBy('name')->values()->all(),
            'count' => $bucket->count(),
        ]);
    }

    $totalMentions = $mentionsCollection->count();
@endphp

<div class="au-mp {{ $attributes->get('class') ?? '' }}"
     x-data="auMentionPicker()"
     data-groups='@json($groupedJson)'
     data-current="{{ $value ?? '' }}"
     @click.outside="open = false"
     @keydown.escape="open = false">

    <button type="button"
            class="au-mp-trigger"
            :class="{ 'au-mp-trigger--open': open, 'au-mp-trigger--has-value': currentValue !== '' }"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-haspopup="listbox">
        <template x-if="!selectedMention">
            <span class="au-mp-trigger-empty">
                <i class="fas fa-graduation-cap au-mp-trigger-icon"></i>
                <span>{{ $placeholder }}</span>
            </span>
        </template>
        <template x-if="selectedMention">
            <span class="au-mp-trigger-selected">
                <span class="au-mp-avatar" x-text="selectedMention.initial"></span>
                <span class="au-mp-trigger-info">
                    <span class="au-mp-trigger-name" x-text="selectedMention.name"></span>
                    <span class="au-mp-trigger-domaine" x-text="selectedMention.domaineName"></span>
                </span>
            </span>
        </template>
        <i class="fas fa-chevron-down au-mp-caret" :class="{ 'au-mp-caret--open': open }"></i>
    </button>

    <div class="au-mp-menu" x-show="open" x-cloak
         x-transition:enter="au-mp-menu--entering"
         x-transition:enter-start="au-mp-menu--enter-start"
         x-transition:enter-end="au-mp-menu--enter-end">

        @if($searchable)
        <div class="au-mp-search">
            <i class="fas fa-search"></i>
            <input type="text" x-model="search" x-ref="searchInput" @click.stop
                   @keydown.escape.stop="open = false"
                   placeholder="Rechercher par mention ou domaine…">
            <button type="button" x-show="search.length > 0"
                    @click="search = ''; $refs.searchInput.focus()"
                    class="au-mp-search-clear" aria-label="Effacer">
                <i class="fas fa-times"></i>
            </button>
        </div>
        @endif

        <div class="au-mp-stats" x-show="!search">
            <span class="au-mp-stats-pill">
                <i class="fas fa-graduation-cap"></i>
                <strong>{{ $totalMentions }}</strong> mention{{ $totalMentions > 1 ? 's' : '' }} disponible{{ $totalMentions > 1 ? 's' : '' }}
            </span>
        </div>

        <div class="au-mp-options" role="listbox">
            <template x-if="filteredGroups.length === 0">
                <div class="au-mp-empty">
                    <i class="fas fa-search"></i>
                    <template x-if="totalMentions === 0">
                        <span>Aucune mention LMD configurée. Contactez l'administration.</span>
                    </template>
                    <template x-if="totalMentions > 0">
                        <span>Aucune mention ne correspond à <strong x-text="search"></strong></span>
                    </template>
                </div>
            </template>

            <template x-for="group in filteredGroups" :key="group.key">
                <div class="au-mp-group">
                    <div class="au-mp-group-header">
                        <i class="fas fa-folder-open"></i>
                        <span class="au-mp-group-label" x-text="group.domaineName"></span>
                        <span class="au-mp-group-count" x-text="group.mentions.length"></span>
                    </div>
                    <template x-for="m in group.mentions" :key="m.id">
                        <button type="button" class="au-mp-option"
                                :class="{ 'au-mp-option--active': String(currentValue) === String(m.id) }"
                                @click="select(m, group)" role="option"
                                :aria-selected="(String(currentValue) === String(m.id)).toString()">
                            <span class="au-mp-avatar" x-text="m.initial"></span>
                            <span class="au-mp-option-info">
                                <span class="au-mp-option-name" x-text="m.name"></span>
                                <span class="au-mp-option-meta">
                                    <span class="au-mp-domaine-chip" x-text="group.domaineName"></span>
                                    <span class="au-mp-code" x-show="m.code" x-text="m.code"></span>
                                </span>
                            </span>
                            <i class="fas fa-check au-mp-option-check" x-show="String(currentValue) === String(m.id)"></i>
                        </button>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" :value="currentValue" x-ref="native">
</div>

{{--
    INLINE <style> + <script> (pas @once @push) pour que le composant soit AJAX-safe.
    En reponse AJAX standalone (modal load via fetch + injectHtmlWithScripts), @push
    sans @stack parent = drop silencieux. Idempotency guards `if (typeof window.X
    !== 'function')` empechent le double-register au render multiple sur la meme page.
    Voir rule .claude/rules/premium-selects.md section "AJAX-safe pattern".
--}}
<style>
.au-mp { position: relative; flex: 1; min-width: 0; display: flex; }
.au-mp-trigger {
    width: 100%; display: flex; align-items: center; gap: .65rem;
    padding: .5rem .8rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    cursor: pointer; transition: border-color .15s, box-shadow .15s;
    text-align: left; line-height: 1.2;
}
.au-mp-trigger:hover { border-color: #cbd5e1; }
.au-mp-trigger--open { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.au-mp-trigger-empty { display: flex; align-items: center; gap: .5rem; flex: 1; color: #64748b; font-size: .85rem; min-width: 0; }
.au-mp-trigger-empty span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-mp-trigger-icon { color: #94a3b8; }
.au-mp-trigger-selected { display: flex; align-items: center; gap: .65rem; flex: 1; min-width: 0; }
.au-mp-trigger-info { display: flex; flex-direction: column; min-width: 0; }
.au-mp-trigger-name { font-size: .85rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-mp-trigger-domaine { font-size: .68rem; color: #0453cb; text-transform: uppercase; letter-spacing: .4px; font-weight: 600; }
.au-mp-caret { color: #94a3b8; font-size: .72rem; flex-shrink: 0; transition: transform .2s; margin-left: auto; }
.au-mp-caret--open { transform: rotate(180deg); color: #0453cb; }
.au-mp-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: .82rem;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(4,83,203,.25);
}
.au-mp-menu {
    position: absolute; top: calc(100% + 6px);
    left: 0; right: 0;
    min-width: 360px;
    z-index: 1050;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 16px 50px rgba(15,23,42,.14), 0 4px 12px rgba(15,23,42,.06);
    overflow: hidden; max-height: 480px;
    display: flex; flex-direction: column;
    transform-origin: top center;
}
.au-mp-menu--enter-start { opacity: 0; transform: translateY(-8px) scale(.98); transition: opacity .14s, transform .14s; }
.au-mp-menu--enter-end { opacity: 1; transform: translateY(0) scale(1); }
.au-mp-search {
    position: relative; padding: .65rem .8rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .55rem;
    background: #f8fafc;
}
.au-mp-search > i:first-child { color: #94a3b8; }
.au-mp-search input { flex: 1; border: none; background: transparent; outline: none; font-size: .88rem; color: #0f172a; }
.au-mp-search input::placeholder { color: #94a3b8; }
.au-mp-search-clear {
    background: #e2e8f0; border: none; width: 24px; height: 24px;
    border-radius: 50%; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    color: #475569; font-size: .7rem;
}
.au-mp-search-clear:hover { background: #cbd5e1; color: #0f172a; }
.au-mp-stats { padding: .5rem .8rem; background: #fafafa; border-bottom: 1px solid #f1f5f9; }
.au-mp-stats-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
    padding: .2rem .65rem; font-size: .72rem; color: #475569;
}
.au-mp-stats-pill strong { color: #0453cb; }
.au-mp-options { flex: 1; overflow-y: auto; padding: .35rem 0;
    scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;
}
.au-mp-options::-webkit-scrollbar { width: 6px; }
.au-mp-options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.au-mp-option {
    width: 100%; display: flex; align-items: center; gap: .75rem;
    padding: .6rem .8rem;
    background: transparent; border: none; cursor: pointer;
    text-align: left; transition: background .12s;
    border-left: 3px solid transparent;
}
.au-mp-option:hover { background: #f8fafc; border-left-color: #cbd5e1; }
.au-mp-option--active { background: #eff6ff !important; border-left-color: #0453cb !important; }
.au-mp-option-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .15rem; }
.au-mp-option-name { font-size: .87rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-mp-option-meta { display: flex; align-items: center; gap: .45rem; font-size: .72rem; color: #64748b; flex-wrap: wrap; }
.au-mp-domaine-chip {
    padding: .08rem .45rem; border-radius: 999px;
    font-weight: 600; font-size: .68rem;
    border: 1px solid rgba(4,83,203,.2);
    background: rgba(4,83,203,.08);
    color: #0453cb;
    text-transform: uppercase; letter-spacing: .3px;
}
.au-mp-code { color: #94a3b8; font-style: italic; }
.au-mp-option-check { color: #0453cb; font-size: .82rem; flex-shrink: 0; }
.au-mp-group { padding: .25rem 0; }
.au-mp-group + .au-mp-group { border-top: 1px solid #f1f5f9; margin-top: .25rem; padding-top: .35rem; }
.au-mp-group-header {
    display: flex; align-items: center; gap: .5rem;
    padding: .35rem .85rem;
    font-size: .68rem; text-transform: uppercase; letter-spacing: .6px;
    color: #475569; font-weight: 700;
    background: #fafafa;
}
.au-mp-group-header > i { color: #0453cb; }
.au-mp-group-label { flex: 1; }
.au-mp-group-count {
    background: #e2e8f0; color: #475569;
    border-radius: 999px; padding: .1rem .55rem; font-size: .65rem;
}
.au-mp-empty {
    padding: 2rem 1rem; text-align: center;
    color: #64748b; font-size: .85rem;
    display: flex; flex-direction: column; align-items: center; gap: .5rem;
}
.au-mp-empty i { color: #cbd5e1; font-size: 1.6rem; }
@@media (max-width: 768px) {
    .au-mp-menu { width: calc(100vw - 2rem); min-width: 0; max-height: 70vh; }
}
</style>

<script>
if (typeof window.auMentionPicker !== 'function') {
    window.auMentionPicker = function () {
        return {
            open: false,
            search: '',
            groups: [],
            currentValue: '',
            totalMentions: 0,
            init() {
                try { this.groups = JSON.parse(this.$el.dataset.groups || '[]'); }
                catch (e) { this.groups = []; }
                this.currentValue = this.$el.dataset.current || '';
                this.totalMentions = this.groups.reduce((sum, g) => sum + (g.mentions ? g.mentions.length : 0), 0);
                this.$nextTick(() => {
                    if (this.$refs.native) this.$refs.native.value = this.currentValue;
                    // Si une valeur initiale est presente, dispatcher l'event de cascade
                    if (this.currentValue && this.selectedMention) {
                        this.dispatchChange(this.selectedMention);
                    }
                });
            },
            toggle() {
                this.open = !this.open;
                if (this.open) this.$nextTick(() => { if (this.$refs.searchInput) this.$refs.searchInput.focus(); });
            },
            get filteredGroups() {
                const s = this.search.trim().toLowerCase();
                if (!s) return this.groups;
                return this.groups.map(g => ({
                    ...g,
                    mentions: g.mentions.filter(m =>
                        m.name.toLowerCase().includes(s) ||
                        (m.code || '').toLowerCase().includes(s) ||
                        (g.domaineName || '').toLowerCase().includes(s)
                    ),
                })).filter(g => g.mentions.length > 0);
            },
            get selectedMention() {
                if (!this.currentValue) return null;
                for (const g of this.groups) {
                    for (const m of g.mentions) {
                        if (String(m.id) === String(this.currentValue)) {
                            return { ...m, domaineName: g.domaineName, domaineId: g.domaineId };
                        }
                    }
                }
                return null;
            },
            select(m, group) {
                this.currentValue = String(m.id);
                this.open = false; this.search = '';
                this.$nextTick(() => {
                    if (this.$refs.native) {
                        this.$refs.native.value = this.currentValue;
                        this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    this.dispatchChange({ ...m, domaineName: group.domaineName, domaineId: group.domaineId });
                });
            },
            reset() {
                this.currentValue = '';
                if (this.$refs.native) {
                    this.$refs.native.value = '';
                    this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                }
                window.dispatchEvent(new CustomEvent('mention:changed', {
                    detail: { mentionId: null, domaineId: null, domaineName: null }
                }));
            },
            dispatchChange(m) {
                window.dispatchEvent(new CustomEvent('mention:changed', {
                    detail: {
                        mentionId: m.id,
                        domaineId: m.domaineId,
                        domaineName: m.domaineName,
                    }
                }));
            },
        };
    };
}
</script>
