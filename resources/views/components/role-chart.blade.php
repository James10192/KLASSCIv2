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

@once
@push('styles')
<style>
    /* ===== Graphique de rôle ===== */
    .rch { width: 100%; }
    .rch-canvas { position: relative; width: 100%; }
    .rch-empty {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        text-align: center; padding: 1.5rem 1rem;
        background: #f8fafc; border: 1px dashed #dbe3ec; border-radius: 12px;
    }
    .rch-empty-icon {
        width: 48px; height: 48px; border-radius: 13px; margin-bottom: .7rem;
        background: #eef2f7; color: #94a3b8;
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
    }
    .rch-empty-title { font-size: .88rem; font-weight: 600; color: #1e293b; }
    .rch-empty-hint { font-size: .78rem; color: #64748b; margin-top: .25rem; max-width: 34ch; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    // Palette monochrome bleu KLASSCI. Les deux dernières teintes ne servent
    // qu'aux segments porteurs d'alerte, jamais à différencier des catégories
    // neutres (cf. rule premium-redesign).
    var PALETTE = ['#0453cb', '#3b7ddb', '#5e91de', '#8fb4e8', '#c3d7f3'];
    var ALERTE = '#f59e0b';
    var CRITIQUE = '#dc2626';

    function resoudreCouleur(nom, index) {
        if (nom === 'alerte') return ALERTE;
        if (nom === 'critique') return CRITIQUE;
        if (nom && nom.charAt(0) === '#') return nom;
        return PALETTE[index % PALETTE.length];
    }

    function appliquerTheme(type, payload) {
        var data = payload.data || {};
        (data.datasets || []).forEach(function (dataset, i) {
            if (Array.isArray(dataset.tones)) {
                dataset.backgroundColor = dataset.tones.map(resoudreCouleur);
            } else if (!dataset.backgroundColor) {
                dataset.backgroundColor = type === 'line'
                    ? 'rgba(4,83,203,.10)'
                    : PALETTE[i % PALETTE.length];
            }
            if (type === 'line') {
                dataset.borderColor = dataset.borderColor || PALETTE[i % PALETTE.length];
                dataset.tension = dataset.tension === undefined ? 0.35 : dataset.tension;
                dataset.fill = dataset.fill === undefined ? true : dataset.fill;
                dataset.pointRadius = dataset.pointRadius === undefined ? 3 : dataset.pointRadius;
            }
            if (type === 'bar') {
                dataset.borderRadius = dataset.borderRadius === undefined ? 6 : dataset.borderRadius;
                dataset.borderSkipped = false;
            }
        });
        return data;
    }

    var BASE = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#0f172a',
                padding: 10,
                cornerRadius: 8,
                titleFont: { size: 12, weight: '700' },
                bodyFont: { size: 12 },
            },
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
            y: {
                beginAtZero: true,
                grid: { color: '#eef2f7', drawBorder: false },
                ticks: { color: '#64748b', font: { size: 11 }, precision: 0 },
            },
        },
    };

    function fusionner(base, surcharge) {
        var out = JSON.parse(JSON.stringify(base));
        Object.keys(surcharge || {}).forEach(function (cle) {
            var v = surcharge[cle];
            out[cle] = (v && typeof v === 'object' && !Array.isArray(v))
                ? fusionner(out[cle] || {}, v)
                : v;
        });
        return out;
    }

    function rendre(canvas) {
        if (!canvas || canvas.dataset.chartRendu === '1') return;
        if (typeof Chart === 'undefined') return;
        canvas.dataset.chartRendu = '1';

        var payload;
        try {
            payload = JSON.parse(canvas.dataset.chartPayload || '{}');
        } catch (e) {
            return;
        }

        var type = canvas.dataset.chartType || 'bar';
        var options = fusionner(BASE, payload.options || {});

        // Un graphique de tableau de bord est un sélecteur : cliquer un segment
        // ouvre la liste filtrée correspondante quand une URL est fournie.
        options.onClick = function (evt, elements) {
            if (!elements.length) return;
            var idx = elements[0].index;
            var liens = (payload.data && payload.data.links) || [];
            if (liens[idx]) window.location.href = liens[idx];
        };
        if (((payload.data || {}).links || []).length) {
            canvas.style.cursor = 'pointer';
        }
        if (type === 'doughnut') {
            delete options.scales;
            options.cutout = '62%';
            options.plugins.legend = { display: true, position: 'bottom', labels: { boxWidth: 10, font: { size: 11 }, color: '#64748b', padding: 12 } };
        }

        new Chart(canvas, { type: type, data: appliquerTheme(type, payload), options: options });
    }

    function rendreTout(racine) {
        (racine || document).querySelectorAll('canvas[data-chart-payload]').forEach(rendre);
    }

    window.klassciRendreGraphiques = rendreTout;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { rendreTout(document); });
    } else {
        rendreTout(document);
    }

    // Les blocs chargés en AJAX signalent leur arrivée par cet événement.
    window.addEventListener('klassci:charts', function (ev) {
        rendreTout((ev.detail && ev.detail.root) || document);
    });
})();
</script>
@endpush
@endonce
