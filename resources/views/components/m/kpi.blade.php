{{--
    Grille de KPIs (2 colonnes mobile, 4 sur tablette).
    Balise : x-m.kpi — ex. :items="[
        ['value' => '582', 'label' => 'Encaissements', 'delta' => '+12 % vs hier', 'tone' => 'ok'],
        ['value' => '3', 'label' => 'En attente', 'delta' => 'à valider', 'tone' => 'warn'],
        ['12', 'Rejetés', 'ce mois', 'bad'],   (forme positionnelle acceptée)
    ]"
    Props :
      items   array   chaque élément : value, label, delta (optionnel), tone (ok|warn|bad|info|mute, défaut ok),
                      href (optionnel : la carte devient un lien)
--}}
@props([
    'items' => [],
])
@php
    $tones = ['ok', 'warn', 'bad', 'info', 'mute'];
    $cards = collect($items)->map(function ($it) use ($tones) {
        if (!is_array($it)) {
            return ['value' => (string) $it, 'label' => '', 'delta' => null, 'tone' => 'ok', 'href' => null];
        }
        $value = $it['value'] ?? ($it[0] ?? '');
        $label = $it['label'] ?? ($it[1] ?? '');
        $delta = $it['delta'] ?? ($it[2] ?? null);
        $tone  = $it['tone']  ?? ($it[3] ?? 'ok');
        return [
            'value' => (string) $value,
            'label' => (string) $label,
            'delta' => $delta !== null && $delta !== '' ? (string) $delta : null,
            'tone'  => in_array($tone, $tones, true) ? $tone : 'ok',
            'href'  => $it['href'] ?? null,
        ];
    });
@endphp
<div {{ $attributes->merge(['class' => 'm-kpi']) }}>
    @foreach($cards as $c)
        @if($c['href'])
            <a href="{{ $c['href'] }}" class="m-kpi-link">
                <span class="v">{{ $c['value'] }}</span>
                <span class="l">{{ $c['label'] }}</span>
                @if($c['delta'])
                    <span class="d {{ $c['tone'] }}">{{ $c['delta'] }}</span>
                @endif
            </a>
        @else
            <div>
                <span class="v">{{ $c['value'] }}</span>
                <span class="l">{{ $c['label'] }}</span>
                @if($c['delta'])
                    <span class="d {{ $c['tone'] }}">{{ $c['delta'] }}</span>
                @endif
            </div>
        @endif
    @endforeach
</div>
