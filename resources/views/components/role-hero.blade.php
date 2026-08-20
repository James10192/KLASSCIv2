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
    .rdx-hero-actions { display: flex; gap: .5rem; flex-wrap: wrap; }

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

    @media (max-width: 768px) {
        .rdx-hero { padding: 1.35rem 1.25rem 1.1rem; border-radius: 14px; }
        .rdx-hero h1 { font-size: 1.2rem; }
        .rdx-kpi { min-width: 100%; }
    }
</style>
@endpush
@endonce
