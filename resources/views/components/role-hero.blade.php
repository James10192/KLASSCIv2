{{--
    Hero premium partagé par les écrans de rôle (dashboards, rapports, fiches).
    Reprend le pattern planning-header : bandeau dégradé, carré d'icône 52px,
    titre blanc, puis une rangée de KPIs semi-transparents dans le hero.

    Namespace CSS : rdx-* (role dashboard). Volontairement sans overflow:hidden
    ni position:relative pour ne pas rogner un dropdown qui s'ouvrirait dedans
    (cf. .claude/rules/css-stacking-pitfalls.md).

    @param string      icon      Classe Font Awesome, ex. fa-graduation-cap
    @param string      title
    @param string|null subtitle
    @param array       kpis      [['icon','value','label','href'?,'tone'?]]
    @slot  actions              Boutons alignés à droite de la première rangée
--}}
@props([
    'icon' => 'fa-gauge-high',
    'title' => '',
    'subtitle' => null,
    'kpis' => [],
])

<div class="rdx-hero">
    <div class="rdx-hero-top">
        <div class="rdx-hero-left">
            <div class="rdx-hero-icon"><i class="fas {{ $icon }}"></i></div>
            <div>
                <h1>{{ $title }}</h1>
                @if($subtitle)<p>{{ $subtitle }}</p>@endif
            </div>
        </div>
        @isset($actions)
            <div class="rdx-hero-actions">{{ $actions }}</div>
        @endisset
    </div>

    @if(!empty($kpis))
        <div class="rdx-kpis">
            @foreach($kpis as $kpi)
                @php $href = $kpi['href'] ?? null; @endphp
                <{{ $href ? 'a' : 'div' }}
                    class="rdx-kpi {{ ($kpi['tone'] ?? null) === 'alert' ? 'rdx-kpi--alert' : '' }}"
                    @if($href) href="{{ $href }}" @endif>
                    <span class="rdx-kpi-icon"><i class="fas {{ $kpi['icon'] ?? 'fa-circle-info' }}"></i></span>
                    <span class="rdx-kpi-body">
                        <span class="rdx-kpi-value" @isset($kpi['data_kpi']) data-kpi="{{ $kpi['data_kpi'] }}" @endisset>{{ $kpi['value'] }}</span>
                        <span class="rdx-kpi-label">{{ $kpi['label'] }}</span>
                        @isset($kpi['hint'])
                            <span class="rdx-kpi-hint">{{ $kpi['hint'] }}</span>
                        @endisset
                    </span>
                    @if($href)<i class="fas fa-arrow-right rdx-kpi-go"></i>@endif
                </{{ $href ? 'a' : 'div' }}>
            @endforeach
        </div>
    @endif
</div>

@once
@push('styles')
<style>
    /* ===== Hero de rôle ===== */
    .rdx-hero {
        background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
        border-radius: 18px;
        padding: 2rem 2.5rem 1.5rem;
        color: #fff;
        margin-bottom: 1.25rem;
        box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
    }
    .rdx-hero-top {
        display: flex; align-items: flex-start; justify-content: space-between;
        flex-wrap: wrap; gap: 1rem;
    }
    .rdx-hero-left { display: flex; align-items: center; gap: 1rem; }
    .rdx-hero-icon {
        width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
        background: rgba(255, 255, 255, .12);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, .15);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.35rem; color: #fff;
    }
    .rdx-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.25; }
    .rdx-hero p { color: rgba(255, 255, 255, .7); font-size: .88rem; margin: .2rem 0 0; }
    /* margin-left auto : les actions restent alignées à droite même quand
       elles passent à la ligne sous un titre long. */
    .rdx-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; margin-left: auto; }

    /* ===== KPIs dans le hero ===== */
    .rdx-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .rdx-kpi {
        flex: 1; min-width: 150px;
        background: rgba(255, 255, 255, .1);
        border: 1px solid rgba(255, 255, 255, .15);
        border-radius: 12px; padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem;
        color: #fff; text-decoration: none;
        transition: background .2s ease, border-color .2s ease;
    }
    a.rdx-kpi:hover { background: rgba(255, 255, 255, .18); border-color: rgba(255, 255, 255, .3); color: #fff; }
    .rdx-kpi--alert { border-color: rgba(255, 255, 255, .38); }
    .rdx-kpi-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        background: rgba(255, 255, 255, .14);
        display: flex; align-items: center; justify-content: center; font-size: .82rem;
    }
    .rdx-kpi-body { display: flex; flex-direction: column; min-width: 0; }
    .rdx-kpi-value { font-size: 1.35rem; font-weight: 700; line-height: 1.1; }
    .rdx-kpi-label { font-size: .72rem; color: rgba(255, 255, 255, .65); margin-top: .15rem; }
    .rdx-kpi-hint { display: block; font-size: .66rem; color: rgba(255, 255, 255, .5); margin-top: .1rem; }
    .rdx-kpi-go { margin-left: auto; font-size: .7rem; opacity: .55; }

    /* ===== Boutons du hero ===== */
    .rdx-btn {
        display: inline-flex; align-items: center; gap: .45rem;
        padding: .5rem 1rem; border-radius: 10px;
        font-size: .82rem; font-weight: 600; text-decoration: none;
        border: 1px solid rgba(255, 255, 255, .2);
        background: rgba(255, 255, 255, .15); color: #fff;
        cursor: pointer; transition: background .2s ease;
    }
    .rdx-btn:hover { background: rgba(255, 255, 255, .25); color: #fff; }
    .rdx-btn--white { background: #fff; color: #0453cb; border-color: transparent; }
    .rdx-btn--white:hover { background: #eef4ff; color: #0453cb; }

    /* ===== Téléphone : grand titre (M2) et tuiles en mosaïque (M3) =====
       Le bandeau dégradé plein écran mangeait la moitié du premier écran et
       empilait les KPIs en une colonne. Sous 768px, le titre se pose sur le fond
       de page, les actions deviennent des pastilles qui passent à la ligne, et les
       KPIs une grille de tuiles : la première occupe toute la largeur quand leur
       nombre est impair, pour que la grille se remplisse. */
    @media (max-width: 767.98px) {
        .rdx-hero { background: transparent; color: #0f172a; padding: .25rem 0 0; border-radius: 0; box-shadow: none; margin-bottom: 1rem; }
        .rdx-hero-top { flex-direction: column; flex-wrap: nowrap; align-items: stretch; gap: .75rem; }
        .rdx-hero-icon { display: none; }
        .rdx-hero h1 { font-size: 1.65rem; font-weight: 800; color: #0f172a; letter-spacing: -.02em; line-height: 1.15; }
        .rdx-hero p { color: #64748b; font-size: .84rem; margin-top: .3rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .rdx-hero-actions { margin-left: 0; flex-wrap: wrap; }
        .rdx-btn { flex: 0 0 auto; white-space: nowrap; min-height: 40px; border-radius: 99px; background: #fff; color: #0453cb; border: 1px solid #dfe6f1; box-shadow: 0 1px 2px rgba(15, 23, 42, .05); }
        .rdx-btn:hover { background: #fff; color: #0453cb; }
        .rdx-btn--white { background: #0453cb; color: #fff; border-color: #0453cb; }
        .rdx-btn--white:hover { background: #033a8e; color: #fff; }

        .rdx-kpis { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 1rem; }
        .rdx-kpi { position: relative; min-width: 0; flex-direction: column; align-items: flex-start; gap: .55rem; padding: 14px; border: 0; border-radius: 20px; background: #fff; color: #0f172a; box-shadow: 0 1px 3px rgba(15, 23, 42, .06); }
        a.rdx-kpi:hover { background: #fff; color: #0f172a; border-color: transparent; }
        a.rdx-kpi:active { transform: scale(.98); }
        .rdx-kpi-icon { width: 32px; height: 32px; border-radius: 10px; background: #e8f0fc; color: #0453cb; }
        .rdx-kpi-value { font-size: 1.5rem; font-weight: 800; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
        .rdx-kpi-label { font-size: .78rem; color: #64748b; font-weight: 600; }
        .rdx-kpi-hint { color: #94a3b8; }
        .rdx-kpi-go { position: absolute; top: 16px; right: 14px; margin: 0; color: #94a3b8; opacity: 1; }

        .rdx-kpi:first-child:nth-last-child(2n+1) { grid-column: span 2; background: #0453cb; color: #fff; }
        .rdx-kpi:first-child:nth-last-child(2n+1) .rdx-kpi-icon { background: rgba(255, 255, 255, .16); color: #fff; }
        .rdx-kpi:first-child:nth-last-child(2n+1) .rdx-kpi-label,
        .rdx-kpi:first-child:nth-last-child(2n+1) .rdx-kpi-hint,
        .rdx-kpi:first-child:nth-last-child(2n+1) .rdx-kpi-go { color: rgba(255, 255, 255, .78); }
        a.rdx-kpi:first-child:nth-last-child(2n+1):hover { background: #0453cb; color: #fff; }

        /* Une alerte reste une alerte, même en première tuile. */
        .rdx-kpi.rdx-kpi--alert,
        a.rdx-kpi.rdx-kpi--alert:hover { background: #fff7ed; color: #9a3412; }
        .rdx-kpi.rdx-kpi--alert .rdx-kpi-icon { background: #ffedd5; color: #c2410c; }
        .rdx-kpi.rdx-kpi--alert .rdx-kpi-label,
        .rdx-kpi.rdx-kpi--alert .rdx-kpi-go { color: #9a3412; }
        .rdx-kpi.rdx-kpi--alert .rdx-kpi-hint { color: #c2410c; }
    }
</style>
@endpush
@endonce
