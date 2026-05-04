@props([
    'name' => null,
    'value' => '',
    'options' => [],
    'placeholder' => 'Sélectionner…',
    'icon' => null,
    'searchable' => false,
    'placeholderIsFirstOption' => true,
])

@php
    $wrapperClass = trim('au-select ' . ($attributes->get('class') ?? ''));
    $nativeAttributes = $attributes->except(['class']);
    $normalized = collect($options)->map(function ($v, $k) {
        if (is_array($v) && array_key_exists('value', $v) && array_key_exists('label', $v)) {
            return ['value' => $v['value'], 'label' => $v['label']];
        }
        if (is_object($v) && property_exists($v, 'value') && property_exists($v, 'label')) {
            return ['value' => $v->value, 'label' => $v->label];
        }
        return ['value' => $k, 'label' => (string) $v];
    })->values();
    $componentId = 'au-select-' . substr(md5(uniqid('', true)), 0, 8);
@endphp

<div class="{{ $wrapperClass }}" x-data="auSelect()" x-id="['{{ $componentId }}']" @click.outside="open = false" @keydown.escape="open = false">
    <button type="button"
            class="au-select-trigger"
            :class="{ 'au-select-trigger--open': open, 'au-select-trigger--has-value': currentValue !== '' }"
            @click="toggle()"
            :aria-expanded="open.toString()"
            :aria-controls="$id('{{ $componentId }}')"
            aria-haspopup="listbox">
        @if($icon)<i class="fas {{ $icon }} au-select-icon"></i>@endif
        <span class="au-select-value" x-text="selectedLabel || {{ \Illuminate\Support\Js::from($placeholder) }}"
              :class="{ 'au-select-value--placeholder': !selectedLabel }"></span>
        <i class="fas fa-chevron-down au-select-caret" :class="{ 'au-select-caret--open': open }"></i>
    </button>

    <div class="au-select-menu"
         :id="$id('{{ $componentId }}')"
         x-show="open"
         x-cloak
         x-transition:enter="au-select-menu--entering"
         x-transition:enter-start="au-select-menu--enter-start"
         x-transition:enter-end="au-select-menu--enter-end"
         role="listbox">
        @if($searchable)
        <div class="au-select-search">
            <i class="fas fa-search"></i>
            <input type="text"
                   x-model="search"
                   x-ref="searchInput"
                   @click.stop
                   @keydown.escape.stop="open = false"
                   @keydown.enter.prevent="selectFirstFiltered()"
                   placeholder="Rechercher…">
            <button type="button"
                    class="au-select-search-clear"
                    x-show="search.length > 0"
                    @click="search = ''; $refs.searchInput.focus()"
                    aria-label="Effacer la recherche">
                <i class="fas fa-times"></i>
            </button>
        </div>
        @endif

        <ul class="au-select-options">
            <template x-for="(opt, idx) in filteredOptions" :key="opt.value + ':' + idx">
                <li class="au-select-option"
                    :class="{ 'au-select-option--active': opt.value === currentValue, 'au-select-option--placeholder': opt.placeholder }"
                    @click="select(opt)"
                    role="option"
                    :aria-selected="(opt.value === currentValue).toString()">
                    <span class="au-select-option-label" x-text="opt.label"></span>
                    <i class="fas fa-check au-select-option-check" x-show="opt.value === currentValue"></i>
                </li>
            </template>
            <li class="au-select-empty" x-show="filteredOptions.length === 0" x-cloak>
                <i class="fas fa-search"></i>
                <span>Aucun résultat pour <strong x-text="search"></strong></span>
            </li>
        </ul>
    </div>

    <select {{ $nativeAttributes->class(['au-select-native']) }}
            x-ref="native"
            @if($name) name="{{ $name }}" @endif
            aria-hidden="true"
            tabindex="-1">
        @if($placeholderIsFirstOption)
            <option value="" data-placeholder="1">{{ $placeholder }}</option>
        @endif
        @foreach($normalized as $opt)
            <option value="{{ $opt['value'] }}" @selected((string) $opt['value'] === (string) $value)>{{ $opt['label'] }}</option>
        @endforeach
    </select>
</div>

@once
@push('styles')
<style>
.au-select { position: relative; display: inline-flex; flex: 1 1 0%; min-width: 0; }
.au-filter-grow { flex-grow: 4; }
.au-select-native { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; clip: rect(0 0 0 0); }
.au-select-trigger {
    width: 100%; display: inline-flex; align-items: center; gap: .5rem;
    padding: .55rem .85rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    font-size: .85rem; color: #1e293b; cursor: pointer;
    transition: border-color .15s, box-shadow .15s;
    text-align: left; line-height: 1.2;
}
.au-select-trigger:hover { border-color: #cbd5e1; }
.au-select-trigger:focus-visible { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); }
.au-select-trigger--open { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.10); }
.au-select-icon { color: #64748b; font-size: .85rem; flex-shrink: 0; }
.au-select-value { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
.au-select-value--placeholder { color: #94a3b8; font-weight: 400; }
.au-select-caret { color: #94a3b8; font-size: .72rem; flex-shrink: 0; transition: transform .2s ease; }
.au-select-caret--open { transform: rotate(180deg); color: #0453cb; }
.au-select-menu {
    position: absolute; top: calc(100% + 6px);
    left: 8px; right: 8px;
    z-index: 1050;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 12px 40px rgba(15,23,42,.12), 0 4px 12px rgba(15,23,42,.06);
    overflow: hidden; max-height: 360px;
    display: flex; flex-direction: column;
    transform-origin: top center;
}
.au-select-menu--enter-start { opacity: 0; transform: translateY(-6px) scale(.98); transition: opacity .14s ease, transform .14s ease; }
.au-select-menu--enter-end { opacity: 1; transform: translateY(0) scale(1); }
.au-select-search {
    position: relative; padding: .55rem .75rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .5rem;
}
.au-select-search > i:first-child { color: #94a3b8; font-size: .8rem; }
.au-select-search input { flex: 1; border: none; background: transparent; outline: none; font-size: .85rem; color: #1e293b; }
.au-select-search-clear {
    background: #f1f5f9; border: none; width: 22px; height: 22px;
    border-radius: 50%; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    color: #64748b; font-size: .65rem;
}
.au-select-search-clear:hover { background: #e2e8f0; color: #0f172a; }
.au-select-options {
    list-style: none; margin: 0; padding: .35rem 0;
    overflow-y: auto; flex: 1;
    scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;
}
.au-select-options::-webkit-scrollbar { width: 6px; }
.au-select-options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.au-select-option {
    display: flex; align-items: center; justify-content: space-between;
    padding: .55rem .85rem; cursor: pointer;
    font-size: .85rem; color: #1e293b;
    transition: background .12s;
    border-left: 3px solid transparent;
}
.au-select-option:hover { background: #f8fafc; border-left-color: #cbd5e1; }
.au-select-option--active { background: #eff6ff; color: #0453cb; font-weight: 600; border-left-color: #0453cb; }
.au-select-option--active:hover { background: #dbeafe; }
.au-select-option--placeholder { color: #94a3b8; font-style: italic; }
.au-select-option-label { flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-select-option-check { color: #0453cb; font-size: .78rem; flex-shrink: 0; margin-left: .5rem; }
.au-select-empty {
    padding: 1.25rem 1rem; text-align: center;
    color: #64748b; font-size: .82rem;
    display: flex; flex-direction: column; align-items: center; gap: .35rem;
}
.au-select-empty i { color: #cbd5e1; font-size: 1.4rem; }
@media (max-width: 576px) {
    .au-select-menu { max-height: 60vh; }
    .au-select-trigger { padding: .65rem .85rem; font-size: .9rem; }
    .au-select-option { padding: .7rem .85rem; font-size: .9rem; }
}
</style>
@endpush

@push('scripts')
<script>
if (typeof window.auSelect !== 'function') {
    window.auSelect = function () {
        return {
            open: false,
            search: '',
            _value: '',
            optionsVersion: 0,
            _optionsObserver: null,
            init() {
                this._value = this.$refs.native.value;

                this.observeNativeOptions();

                this.$refs.native.addEventListener('change', () => {
                    if (this._value !== this.$refs.native.value) {
                        this._value = this.$refs.native.value;
                    }
                });
                this.$watch('_value', (v) => {
                    if (this.$refs.native.value !== v) {
                        this.$refs.native.value = v;
                        this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                        this.$refs.native.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                });

                this.$el.addEventListener('alpine:destroy', () => {
                    if (this._optionsObserver) {
                        this._optionsObserver.disconnect();
                    }
                }, { once: true });
            },
            observeNativeOptions() {
                if (!this.$refs.native || typeof MutationObserver === 'undefined') {
                    return;
                }

                const bump = () => {
                    this.optionsVersion++;

                    const hasCurrentValue = Array.from(this.$refs.native.options || []).some(
                        option => option.value === this._value
                    );

                    if (!hasCurrentValue) {
                        this._value = this.$refs.native.value || '';
                    }
                };

                bump();

                this._optionsObserver = new MutationObserver(() => {
                    bump();
                });

                this._optionsObserver.observe(this.$refs.native, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['value', 'label', 'selected', 'data-placeholder'],
                });
            },
            toggle() {
                this.open = !this.open;
                if (this.open) {
                    this.$nextTick(() => this.$refs.searchInput?.focus());
                }
            },
            get currentValue() { return this._value; },
            get rawOptions() {
                this.optionsVersion;

                if (!this.$refs.native) return [];
                return Array.from(this.$refs.native.options).map(o => ({
                    value: o.value,
                    label: o.textContent.trim(),
                    placeholder: o.dataset.placeholder === '1',
                }));
            },
            get filteredOptions() {
                const s = this.search.trim().toLowerCase();
                if (!s) return this.rawOptions;
                return this.rawOptions.filter(o => o.placeholder || o.label.toLowerCase().includes(s));
            },
            get selectedLabel() {
                const opt = this.rawOptions.find(o => o.value === this._value);
                return opt && !opt.placeholder ? opt.label : '';
            },
            select(opt) {
                this._value = opt.value;
                this.open = false;
                this.search = '';
            },
            selectFirstFiltered() {
                const first = this.filteredOptions.find(o => !o.placeholder);
                if (first) this.select(first);
            },
        };
    };
}
</script>
@endpush
@endonce
