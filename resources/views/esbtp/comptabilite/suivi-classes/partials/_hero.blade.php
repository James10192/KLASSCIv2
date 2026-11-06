<header class="sfc-hero">
    <div class="sfc-hero-top">
        <div class="sfc-hero-left">
            <div class="sfc-hero-icon" aria-hidden="true"><i class="fas fa-chart-column"></i></div>
            <div>
                @can('comptabilite.dashboard.view')
                    <div class="sfc-crumb"><a href="{{ route('esbtp.comptabilite.dashboard') }}">Analyse financière</a> · Classes</div>
                @endcan
                <h1>Situation financière des classes</h1>
                <p class="sfc-hero-sub">Ce que chaque classe doit, a réglé et reste à payer, sur l’année choisie.</p>
            </div>
        </div>
        <div class="sfc-hero-actions">
            @can('comptabilite.suivi_classes.export')
                <x-export-modal
                    :preview-url="route('esbtp.comptabilite.suivi-classes.pdf-preview')"
                    :pdf-url="route('esbtp.comptabilite.suivi-classes.pdf')"
                    :excel-url="route('esbtp.comptabilite.suivi-classes.excel')"
                    button-class="sfc-btn" />
            @endcan
        </div>
    </div>

    <div class="sfc-kpis" aria-label="Totaux des classes filtrées">
        <div class="sfc-kpi">
            <div class="sfc-kpi-label"><i class="fas fa-file-invoice"></i> Montant attendu</div>
            <div class="sfc-kpi-value"><span x-text="money(totaux.attendu)">{{ number_format($rapport['totaux']['attendu'], 0, ',', ' ') }}</span><small>FCFA</small></div>
            <div class="sfc-kpi-note" x-text="totaux.effectif + ' inscriptions · ' + totaux.classes + ' classes'">{{ $rapport['totaux']['effectif'] }} inscriptions · {{ $rapport['totaux']['classes'] }} classes</div>
        </div>
        <div class="sfc-kpi">
            <div class="sfc-kpi-label"><i class="fas fa-circle-check"></i> Réglé</div>
            <div class="sfc-kpi-value"><span x-text="money(totaux.regle)">{{ number_format($rapport['totaux']['regle'], 0, ',', ' ') }}</span><small>FCFA</small></div>
            <div class="sfc-kpi-bar" aria-hidden="true"><i :style="'width:' + Math.min(100, Number(totaux.taux || 0)) + '%'" style="width:{{ min(100, (float) ($rapport['totaux']['taux'] ?? 0)) }}%"></i></div>
            <div class="sfc-kpi-note" x-text="percent(totaux.taux) + ' recouvré'"></div>
        </div>
        <div class="sfc-kpi">
            <div class="sfc-kpi-label"><i class="fas fa-hourglass-half"></i> Reste à payer</div>
            <div class="sfc-kpi-value"><span x-text="money(totaux.restant)">{{ number_format($rapport['totaux']['restant'], 0, ',', ' ') }}</span><small>FCFA</small></div>
            <div class="sfc-kpi-note">Échu ou pas encore</div>
        </div>
        <div class="sfc-kpi">
            <div class="sfc-kpi-label"><i class="fas fa-triangle-exclamation"></i> Échu impayé</div>
            <div class="sfc-kpi-value"><span x-text="money(totaux.echu)">{{ number_format($rapport['totaux']['echu'], 0, ',', ' ') }}</span><small>FCFA</small></div>
            <div class="sfc-kpi-note">Tranches dépassées au {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>
</header>
