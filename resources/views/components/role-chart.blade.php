{{--
    Graphique premium pour les écrans de rôle.

    Trois états sont gérés, jamais une zone blanche : chargement, vide avec
    explication, et rendu. La palette reste monochrome bleu ; le rouge et
    l'orange ne servent qu'à un segment porteur d'alerte.

    @param string      id        Identifiant du canvas
    @param string      type      bar | doughnut | line
    @param array       data      Données Chart.js (labels + datasets)
    @param array       options   Surcharge d'options Chart.js
    @param string|null emptyIcon
    @param string|null emptyTitle
    @param string|null emptyHint
    @param bool        isEmpty
    @param int         height    Hauteur du canvas en pixels
--}}
@props([
    'id',
    'type' => 'bar',
    'data' => [],
    'options' => [],
    'emptyIcon' => 'fa-chart-simple',
    'emptyTitle' => 'Pas encore de données',
    'emptyHint' => null,
    'isEmpty' => false,
    'height' => 260,
])

<div class="rch" data-chart-id="{{ $id }}">
    @if($isEmpty)
        <div class="rch-empty" style="min-height: {{ $height }}px;">
            <div class="rch-empty-icon"><i class="fas {{ $emptyIcon }}"></i></div>
            <div class="rch-empty-title">{{ $emptyTitle }}</div>
            @if($emptyHint)<div class="rch-empty-hint">{{ $emptyHint }}</div>@endif
        </div>
    @else
        <div class="rch-canvas" style="height: {{ $height }}px;">
            <canvas id="{{ $id }}"
                    data-chart-type="{{ $type }}"
                    data-chart-payload='@json(['data' => $data, 'options' => $options])'></canvas>
        </div>
    @endif
</div>

<x-chart-engine />
