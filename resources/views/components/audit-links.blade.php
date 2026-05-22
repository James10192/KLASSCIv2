@props([
    'links' => [],
    'title' => 'Liens vers les entités liées',
    'compact' => false,
])

@php
    $linksArray = is_iterable($links) ? collect($links)->all() : [];
@endphp

@if (count($linksArray) > 0)
<div class="al-wrap {{ $compact ? 'al-wrap--compact' : '' }}">
    @if (! $compact)
        <div class="al-header">
            <div class="al-title"><i class="fas fa-project-diagram"></i> {{ $title }}</div>
            <span class="al-count">{{ count($linksArray) }} lien{{ count($linksArray) > 1 ? 's' : '' }}</span>
        </div>
    @endif
    <div class="al-grid {{ $compact ? 'al-grid--compact' : '' }}">
        @foreach ($linksArray as $link)
            @php
                $emphasis = $link['emphasis'] ?? 'normal';
                $hasRoute = ! empty($link['route']);
                $tag = $hasRoute ? 'a' : 'div';
            @endphp
            <{{ $tag }}
                @if ($hasRoute) href="{{ $link['route'] }}" @endif
                class="al-item al-item--{{ $emphasis }} {{ $hasRoute ? 'al-item--linkable' : '' }}"
                @if ($hasRoute) title="Ouvrir : {{ $link['value'] }}" @endif>
                <span class="al-icon"><i class="fas {{ $link['icon'] ?? 'fa-link' }}"></i></span>
                <div class="al-body">
                    <div class="al-label">{{ $link['label'] }}</div>
                    <div class="al-value">{{ $link['value'] }}</div>
                    @if (! empty($link['sublabel']))
                        <div class="al-sub">{{ $link['sublabel'] }}</div>
                    @endif
                </div>
                @if ($hasRoute)
                    <span class="al-arrow"><i class="fas fa-arrow-up-right-from-square"></i></span>
                @endif
            </{{ $tag }}>
        @endforeach
    </div>
</div>

@once
@push('styles')
<style>
/* ════════════════════════════════════════════════════════════════════
   AUDIT LINKS (composant Blade `audit-links`)
   Namespace : al-*
   Palette : monochrome KLASSCI bleu (rule premium-redesign)
   ════════════════════════════════════════════════════════════════════ */
.al-wrap {
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(59,125,219,.06));
    border: 1px solid rgba(4,83,203,.18);
    border-radius: 14px;
    padding: 1rem 1.15rem 1.15rem;
}
.al-wrap--compact {
    background: transparent;
    border: none;
    padding: .35rem 0 0;
}

.al-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: .65rem;
    margin-bottom: .85rem;
    padding-bottom: .65rem;
    border-bottom: 1px dashed rgba(4,83,203,.18);
    flex-wrap: wrap;
}
.al-title {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    font-size: .88rem;
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -.01em;
}
.al-title i { color: #0453cb; font-size: .82rem; }
.al-count {
    background: #eff6ff;
    color: #0453cb;
    border: 1px solid #dbeafe;
    padding: .2rem .55rem;
    border-radius: 8px;
    font-size: .7rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
}

/* ───── GRID ───── */
.al-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: .65rem;
}
.al-grid--compact {
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: .45rem;
}

/* ───── ITEM CARD ───── */
.al-item {
    display: flex;
    align-items: center;
    gap: .7rem;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 11px;
    padding: .65rem .8rem;
    text-decoration: none;
    color: inherit;
    transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease, background .15s ease;
    position: relative;
    min-width: 0;
}
.al-grid--compact .al-item {
    padding: .5rem .65rem;
    gap: .55rem;
    border-radius: 9px;
}
.al-item--linkable:hover {
    transform: translateY(-1px);
    border-color: rgba(4,83,203,.35);
    background: #f8faff;
    box-shadow: 0 4px 12px rgba(4,83,203,.08), 0 1px 3px rgba(15,23,42,.04);
}
.al-item--linkable:hover .al-arrow { opacity: 1; transform: translateX(0); }
.al-item--linkable:focus-visible {
    outline: 2px solid #0453cb;
    outline-offset: 2px;
}

/* ───── ICON ───── */
.al-icon {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    border-radius: 9px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    color: #fff;
    font-size: .85rem;
    box-shadow: 0 2px 6px rgba(4,83,203,.22);
}
.al-grid--compact .al-icon {
    width: 30px;
    height: 30px;
    font-size: .75rem;
    border-radius: 8px;
}

.al-item--primary .al-icon { background: linear-gradient(135deg, #033a8e, #0453cb); }
.al-item--muted .al-icon {
    background: linear-gradient(135deg, #94a3b8, #cbd5e1);
    box-shadow: 0 1px 3px rgba(100,116,139,.20);
}

/* ───── BODY ───── */
.al-body {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: .1rem;
}
.al-label {
    font-size: .64rem;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    line-height: 1.1;
}
.al-value {
    font-size: .85rem;
    color: #0f172a;
    font-weight: 600;
    line-height: 1.25;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.al-grid--compact .al-value { font-size: .8rem; }
.al-item--primary .al-value { color: #033a8e; }
.al-item--muted .al-value { color: #64748b; font-style: italic; }
.al-sub {
    font-size: .72rem;
    color: #64748b;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-variant-numeric: tabular-nums;
}

/* ───── ARROW ───── */
.al-arrow {
    color: #0453cb;
    font-size: .75rem;
    opacity: 0;
    transform: translateX(-4px);
    transition: opacity .15s ease, transform .15s ease;
    flex-shrink: 0;
    margin-left: .15rem;
}

/* ───── RESPONSIVE ───── */
@@media (max-width: 768px) {
    .al-grid, .al-grid--compact {
        grid-template-columns: 1fr;
    }
    .al-item {
        padding: .6rem .7rem;
    }
}
</style>
@endpush
@endonce
@endif
