@props([
    'name' => 'matiere_id',
    'id' => null,
    'value' => null,
    'matieres' => null,
    'required' => false,
    'placeholder' => 'Rechercher une ECUE…',
    'onchangeJs' => null,
])
@php
    // Default id = name so legacy JS document.getElementById('matiere_id') keeps working
    $nativeId = $id ?? $name;
@endphp

@php
    use App\Enums\TypeUE;
    // Groupement par UE → données pour Alpine
    $groups = collect($matieres ?? [])
        ->map(function ($entry) {
            $m = $entry['matiere'];
            $ue = $m->uniteEnseignement ?? null;
            $heuresRestantes = $entry['heures_restantes_formatted'] ?? ($entry['heures_restantes'] ?? 0);
            $volumeTotal = $entry['volume_horaire_total_formatted'] ?? ($entry['volume_horaire_total'] ?? 0);
            return [
                'id' => (int) $m->id,
                'code' => (string) ($m->code ?? ''),
                'name' => (string) $m->name,
                'ue_id' => $ue?->id ? (int) $ue->id : 0,
                'ue_code' => (string) ($ue->code ?? ''),
                'ue_name' => (string) ($ue->name ?? 'Hors UE'),
                'ue_type' => $ue && $ue->type_ue ? ($ue->type_ue->label() ?? 'UE') : 'UE',
                'heures_restantes' => (string) $heuresRestantes,
                'volume_total' => (string) $volumeTotal,
                'enseignants' => ($entry['enseignants_selectables'] ?? collect())->pluck('id')->values()->all(),
                'planification_id' => $entry['planification_id'] ?? null,
                'heures_restantes_raw' => (float) ($entry['heures_restantes'] ?? 0),
                'volume_total_raw' => (float) ($entry['volume_horaire_total'] ?? 0),
            ];
        })
        ->groupBy(fn ($item) => sprintf('%d|%s|%s', $item['ue_id'], $item['ue_type'], $item['ue_code'] ?: $item['ue_name']))
        ->map(function ($items, $key) {
            $first = $items->first();
            return [
                'ue_id' => $first['ue_id'],
                'ue_label' => trim(sprintf('%s · %s', $first['ue_type'], $first['ue_code'] ?: $first['ue_name'])),
                'ue_subtitle' => $first['ue_code'] && $first['ue_name'] && $first['ue_code'] !== $first['ue_name']
                    ? $first['ue_name']
                    : '',
                'ecues' => $items->values()->all(),
            ];
        })
        ->sortBy(fn ($g) => $g['ue_id'] === 0 ? 'zzz' : $g['ue_label'])
        ->values();
@endphp

@once
<style>
[x-cloak] { display: none !important; }
.scep-wrap { position: relative; }
.scep-native {
    position: absolute !important;
    opacity: 0 !important;
    clip: rect(0,0,0,0) !important;
    pointer-events: none !important;
    height: 1px !important;
    width: 1px !important;
}
.scep-trigger {
    width: 100%;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: .75rem 2.5rem .75rem 2.65rem;
    text-align: left;
    cursor: pointer;
    font-size: .88rem;
    color: #1e293b;
    font-weight: 500;
    position: relative;
    transition: all .15s ease;
    display: flex;
    align-items: center;
    gap: .5rem;
    min-height: 44px;
}
.scep-trigger:hover { border-color: rgba(4,83,203,.35); }
.scep-trigger:focus, .scep-trigger.is-open {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.12);
}
.scep-trigger-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .85rem; pointer-events: none; }
.scep-trigger-caret { position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: .75rem; pointer-events: none; transition: transform .2s; }
.scep-trigger.is-open .scep-trigger-caret { transform: translateY(-50%) rotate(180deg); color: #0453cb; }
.scep-trigger-placeholder { color: #94a3b8; }

.scep-trigger-selected { display: flex; align-items: center; gap: .55rem; flex: 1; min-width: 0; }
.scep-trigger-code {
    font-family: 'Courier New', monospace; font-size: .7rem; font-weight: 700;
    color: #0453cb; background: rgba(4,83,203,.08);
    padding: .15rem .45rem; border-radius: 4px; flex-shrink: 0;
}
.scep-trigger-name { font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.scep-menu {
    position: absolute;
    top: calc(100% + 6px);
    left: 0; right: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(15,23,42,.12), 0 4px 8px rgba(15,23,42,.06);
    z-index: 1080;
    max-height: 420px;
    display: flex; flex-direction: column;
    overflow: hidden;
}
.scep-search {
    padding: .65rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    background: #fff;
    flex-shrink: 0;
    position: sticky; top: 0;
}
.scep-search-input {
    width: 100%;
    padding: .5rem .75rem .5rem 2.2rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: .85rem;
    background: #f8fafc url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="%2394a3b8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>') no-repeat .75rem center;
}
.scep-search-input:focus {
    outline: none; border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.10);
    background-color: #fff;
}

.scep-list { overflow-y: auto; flex: 1; padding: .25rem 0; }
.scep-group { padding: .25rem 0; }
.scep-group-header {
    padding: .45rem .9rem .25rem;
    font-size: .65rem;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: .5px;
    background: #f8fafc;
    border-top: 1px solid #f1f5f9;
    position: sticky; top: 0;
    display: flex; align-items: center; gap: .45rem;
    z-index: 1;
}
.scep-group:first-child .scep-group-header { border-top: none; }
.scep-group-header-icon {
    width: 18px; height: 18px; border-radius: 5px;
    background: rgba(4,83,203,.1); color: #0453cb;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .55rem;
}
.scep-group-header-sub { color: #94a3b8; font-weight: 500; text-transform: none; letter-spacing: 0; }

.scep-option {
    padding: .55rem .9rem;
    cursor: pointer;
    display: flex; align-items: center; gap: .65rem;
    border-left: 3px solid transparent;
    transition: background .12s;
}
.scep-option:hover { background: rgba(4,83,203,.05); border-left-color: #0453cb; }
.scep-option.is-selected { background: rgba(4,83,203,.08); border-left-color: #0453cb; }
.scep-option-code {
    font-family: 'Courier New', monospace; font-size: .68rem; font-weight: 700;
    color: #0453cb; background: rgba(4,83,203,.08);
    padding: .12rem .4rem; border-radius: 4px; flex-shrink: 0;
}
.scep-option-name { flex: 1; min-width: 0; font-size: .85rem; color: #1e293b; font-weight: 500; line-height: 1.3; }
.scep-option-volume {
    font-size: .68rem; color: #64748b; flex-shrink: 0;
    background: #f1f5f9; padding: .12rem .4rem; border-radius: 4px;
    font-weight: 600; white-space: nowrap;
}
.scep-option-volume.is-low { background: rgba(245,158,11,.12); color: #b45309; }
.scep-option-volume.is-done { background: rgba(220,38,38,.12); color: #b91c1c; }

.scep-empty {
    padding: 1.5rem 1rem;
    text-align: center;
    color: #94a3b8;
    font-size: .85rem;
}
.scep-empty i { display: block; font-size: 1.5rem; margin-bottom: .5rem; color: #cbd5e1; }
</style>
@endonce

<div class="scep-wrap"
    x-data="sceEcuePicker({
        groups: {{ Js::from($groups) }},
        initialId: {{ Js::from($value ? (int) $value : null) }},
        placeholder: {{ Js::from($placeholder) }},
        name: {{ Js::from($name) }},
        @if($onchangeJs) onChange: function(value, ecue) { {{ $onchangeJs }} } @endif
    })"
    x-init="init()"
    @keydown.escape.window="open = false"
    @click.outside="open = false">

    {{-- Native hidden select : preserve JS legacy data-attributes for update teacher logic --}}
    {{-- ATTENTION : id="matiere_id" obligatoire pour document.getElementById('matiere_id') (JS legacy create.blade) --}}
    <select name="{{ $name }}" id="{{ $nativeId }}" x-ref="native" class="scep-native @error($name) is-invalid @enderror" @if($required) required @endif>
        <option value="">—</option>
        @foreach($matieres as $matiere)
            @php $m = $matiere['matiere']; @endphp
            <option value="{{ $m->id }}"
                data-heures-restantes="{{ $matiere['heures_restantes'] ?? 0 }}"
                data-heures-restantes-formatted="{{ $matiere['heures_restantes_formatted'] ?? ($matiere['heures_restantes'] ?? 0) }}"
                data-volume-total="{{ $matiere['volume_horaire_total'] ?? 0 }}"
                data-volume-total-formatted="{{ $matiere['volume_horaire_total_formatted'] ?? ($matiere['volume_horaire_total'] ?? 0) }}"
                data-enseignants="{{ ($matiere['enseignants_selectables'] ?? collect())->pluck('id')->toJson() }}"
                data-planification-id="{{ $matiere['planification_id'] ?? '' }}"
                {{ old($name, $value) == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
        @endforeach
    </select>

    <button type="button" class="scep-trigger" :class="open ? 'is-open' : ''"
            @click="open = !open" :aria-expanded="open ? 'true' : 'false'">
        <i class="fas fa-graduation-cap scep-trigger-icon"></i>
        <template x-if="selected">
            <span class="scep-trigger-selected">
                <span class="scep-trigger-code" x-show="selected?.code" x-text="selected?.code"></span>
                <span class="scep-trigger-name" x-text="selected?.name"></span>
            </span>
        </template>
        <template x-if="!selected">
            <span class="scep-trigger-placeholder" x-text="placeholder"></span>
        </template>
        <i class="fas fa-chevron-down scep-trigger-caret"></i>
    </button>

    <div class="scep-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms>
        <div class="scep-search">
            <input type="text" x-model="search" class="scep-search-input"
                   placeholder="Rechercher par code, nom ou UE…"
                   x-ref="searchInput"
                   @keydown.escape.prevent="open = false">
        </div>
        <div class="scep-list">
            <template x-for="group in filteredGroups" :key="group.ue_id || group.ue_label">
                <div class="scep-group">
                    <div class="scep-group-header">
                        <span class="scep-group-header-icon"><i class="fas fa-layer-group"></i></span>
                        <span x-text="group.ue_label"></span>
                        <template x-if="group.ue_subtitle">
                            <span class="scep-group-header-sub" x-text="'— ' + group.ue_subtitle"></span>
                        </template>
                    </div>
                    <template x-for="ecue in group.ecues" :key="ecue.id">
                        <div class="scep-option"
                             :class="selected?.id === ecue.id ? 'is-selected' : ''"
                             @click="select(ecue)">
                            <span class="scep-option-code" x-show="ecue.code" x-text="ecue.code"></span>
                            <span class="scep-option-name" x-text="ecue.name"></span>
                            <span class="scep-option-volume"
                                  :class="volumeClass(ecue)"
                                  x-text="ecue.heures_restantes + 'h / ' + ecue.volume_total + 'h'"></span>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="filteredGroups.length === 0">
                <div class="scep-empty">
                    <i class="fas fa-inbox"></i>
                    Aucune ECUE ne correspond à votre recherche.
                </div>
            </template>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
if (typeof window.sceEcuePicker !== 'function') {
    window.sceEcuePicker = function (config) {
        return {
            groups: config.groups || [],
            search: '',
            open: false,
            selected: null,
            name: config.name,
            placeholder: config.placeholder || 'Sélectionner…',
            _onChange: config.onChange || null,

            init() {
                if (config.initialId) {
                    for (const group of this.groups) {
                        const match = group.ecues.find(e => e.id === config.initialId);
                        if (match) { this.selected = match; break; }
                    }
                }
                this.$watch('open', (val) => {
                    if (val) {
                        this.$nextTick(() => this.$refs.searchInput?.focus());
                    }
                });
            },

            get filteredGroups() {
                const q = (this.search || '').toLowerCase().trim();
                if (!q) return this.groups;
                return this.groups
                    .map(g => ({
                        ...g,
                        ecues: g.ecues.filter(e => {
                            return (e.code || '').toLowerCase().includes(q)
                                || (e.name || '').toLowerCase().includes(q)
                                || (g.ue_label || '').toLowerCase().includes(q)
                                || (g.ue_subtitle || '').toLowerCase().includes(q);
                        })
                    }))
                    .filter(g => g.ecues.length > 0);
            },

            select(ecue) {
                this.selected = ecue;
                this.open = false;
                this.search = '';

                const native = this.$refs.native;
                if (native) {
                    native.value = String(ecue.id);
                    native.dispatchEvent(new Event('change', { bubbles: true }));
                }
                if (typeof this._onChange === 'function') {
                    this._onChange(ecue.id, ecue);
                }
            },

            volumeClass(ecue) {
                const r = ecue.heures_restantes_raw;
                const t = ecue.volume_total_raw;
                if (t <= 0) return '';
                if (r <= 0) return 'is-done';
                if (r / t < 0.2) return 'is-low';
                return '';
            }
        };
    };
}
</script>
@endpush
@endonce
