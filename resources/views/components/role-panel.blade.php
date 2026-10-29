{{--
    Carte premium partagée par les écrans de rôle : en-tête avec pastille
    d'icône dégradée, titre, sous-titre optionnel et zone d'actions.

    @param string      icon
    @param string      title
    @param string|null subtitle
    @param int|null    count     Badge de comptage à droite du titre
    @slot  actions
--}}
@props([
    'icon' => 'fa-list',
    'title' => '',
    'subtitle' => null,
    'count' => null,
])

<section {{ $attributes->merge(['class' => 'rdx-panel']) }}>
    <header class="rdx-panel-head">
        <div class="rdx-panel-icon"><i class="fas {{ $icon }}"></i></div>
        <div class="rdx-panel-titles">
            <h2>
                {{ $title }}
                @if(!is_null($count))<span class="rdx-panel-count">{{ $count }}</span>@endif
            </h2>
            @if($subtitle)<p>{{ $subtitle }}</p>@endif
        </div>
        @isset($actions)
            <div class="rdx-panel-actions">{{ $actions }}</div>
        @endisset
    </header>
    <div class="rdx-panel-body">
        {{ $slot }}
    </div>
</section>

@once
@push('styles')
<style>
    /* ===== Carte de rôle ===== */
    .rdx-panel {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
        display: flex; flex-direction: column;
        height: 100%;
    }
    .rdx-panel-head {
        display: flex; align-items: center; gap: .75rem;
        padding: 1.1rem 1.35rem;
        border-bottom: 1px solid #eef2f7;
    }
    .rdx-panel-icon {
        width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
        background: linear-gradient(135deg, #0453cb, #3b7ddb);
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: .95rem;
    }
    .rdx-panel-titles { flex: 1; min-width: 0; }
    .rdx-panel-titles h2 {
        font-size: .98rem; font-weight: 700; color: #1e293b; margin: 0;
        display: flex; align-items: center; gap: .5rem;
    }
    .rdx-panel-titles p { font-size: .78rem; color: #64748b; margin: .15rem 0 0; }
    .rdx-panel-count {
        background: rgba(4, 83, 203, .08); color: #0453cb;
        border-radius: 999px; padding: .05rem .5rem;
        font-size: .72rem; font-weight: 700;
    }
    .rdx-panel-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
    .rdx-panel-body { padding: 1.1rem 1.35rem 1.35rem; flex: 1; }

    /* ===== Lignes de liste dans une carte ===== */
    .rdx-row {
        display: flex; align-items: center; gap: .85rem;
        padding: .8rem .9rem;
        border: 1px solid #e2e8f0; border-radius: 10px;
        background: #fff;
        transition: border-color .2s ease, box-shadow .2s ease;
    }
    .rdx-row + .rdx-row { margin-top: .55rem; }
    .rdx-row:hover { border-color: #c7d4e5; box-shadow: 0 4px 16px rgba(4, 83, 203, .06); }
    .rdx-row-icon {
        width: 36px; height: 36px; border-radius: 9px; flex-shrink: 0;
        background: rgba(4, 83, 203, .08); color: #0453cb;
        display: flex; align-items: center; justify-content: center; font-size: .85rem;
    }
    .rdx-row-main { flex: 1; min-width: 0; }
    .rdx-row-title { font-size: .88rem; font-weight: 600; color: #1e293b; }
    .rdx-row-meta { font-size: .75rem; color: #64748b; margin-top: .1rem; }
    .rdx-row-actions { display: flex; gap: .4rem; flex-shrink: 0; }

    /* ===== Boutons compacts ===== */
    .rdx-act {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .35rem .7rem; border-radius: 8px;
        font-size: .76rem; font-weight: 600; text-decoration: none;
        border: 1px solid transparent; cursor: pointer;
        transition: background .2s ease, border-color .2s ease;
    }
    .rdx-act--primary { background: #0453cb; color: #fff; }
    .rdx-act--primary:hover { background: #033a8e; color: #fff; }
    .rdx-act--ghost { background: #fff; color: #0453cb; border-color: #c7d4e5; }
    .rdx-act--ghost:hover { background: rgba(4, 83, 203, .06); color: #0453cb; }
    .rdx-act--danger { background: #fff; color: #dc2626; border-color: #fecaca; }
    .rdx-act--danger:hover { background: #fef2f2; color: #dc2626; }

    /* ===== État vide ===== */
    .rdx-empty { text-align: center; padding: 2rem 1rem; }
    .rdx-empty-icon {
        width: 52px; height: 52px; border-radius: 14px; margin: 0 auto .75rem;
        background: #f1f5f9; color: #94a3b8;
        display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
    }
    .rdx-empty-title { font-size: .88rem; font-weight: 600; color: #1e293b; }
    .rdx-empty-hint { font-size: .78rem; color: #64748b; margin-top: .2rem; }

    /* ===== Grille de cartes ===== */
    .rdx-grid { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); }
    .rdx-grid--full { grid-template-columns: 1fr; }

    @media (max-width: 768px) {
        .rdx-grid { grid-template-columns: 1fr; }
        .rdx-panel-body { padding: 1rem; }
        .rdx-row { flex-wrap: wrap; }
        .rdx-row-actions { width: 100%; }
    }
</style>
@endpush
@endonce
