<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @@page { margin: 1.4cm 1.4cm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 11px; margin: 0; }
        .cov-head { border-bottom: 2px solid #0453cb; padding-bottom: 10px; margin-bottom: 14px; }
        .cov-logo { height: 46px; }
        .cov-school { font-size: 15px; font-weight: bold; color: #0f172a; }
        .cov-title { margin-top: 12px; font-size: 18px; font-weight: bold; color: #0453cb; }
        .cov-sub { color: #64748b; font-size: 10.5px; margin-top: 2px; }
        .cov-meta { width: 100%; border-collapse: collapse; margin: 14px 0; }
        .cov-meta td { padding: 5px 8px; border: 1px solid #e2e8f0; font-size: 10.5px; }
        .cov-meta td.k { background: #f8fafc; color: #64748b; width: 32%; font-weight: bold; }
        .cov-note {
            background: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #0453cb;
            border-radius: 6px; padding: 9px 12px; color: #1e3a8a; font-size: 10.5px; margin: 12px 0;
        }
        .cov-warn {
            background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b;
            border-radius: 6px; padding: 9px 12px; color: #92400e; font-size: 11px; margin: 12px 0; font-weight: bold;
        }
        .cov-tbl { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .cov-tbl th {
            background: #0453cb; color: #fff; font-size: 9.5px; text-align: left;
            padding: 6px 8px; border: 1px solid #0453cb;
        }
        .cov-tbl td { padding: 5px 8px; border: 1px solid #e2e8f0; font-size: 9.5px; }
        .cov-tbl tr:nth-child(even) td { background: #f8fafc; }
        .cov-badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 8.5px; font-weight: bold; }
        .cov-badge--pending { background: rgba(245,158,11,.14); color: #b45309; }
        .cov-badge--failed { background: rgba(220,38,38,.12); color: #b91c1c; }
        .cov-sec { margin-top: 14px; font-size: 12px; font-weight: bold; color: #0f172a; }
        .cov-foot { margin-top: 16px; font-size: 9px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="cov-head">
        @if(!empty($logoBase64))
            <img src="{{ $logoBase64 }}" class="cov-logo" alt="">
        @endif
        <div class="cov-school">{{ $config['school_name'] ?? 'Établissement' }}</div>
    </div>

    <div class="cov-title">Export groupé des bulletins</div>
    <div class="cov-sub">Récapitulatif de l'export et des bulletins non inclus</div>

    <table class="cov-meta">
        <tr><td class="k">Année universitaire</td><td>{{ $annee ?? 'Toutes' }}</td></tr>
        <tr><td class="k">Classe</td><td>{{ $classe ?? 'Toutes les classes' }}</td></tr>
        <tr><td class="k">Période</td><td>{{ $periode ?? 'Toutes' }}</td></tr>
        <tr><td class="k">Bulletins inclus</td><td><strong>{{ $included }}</strong></td></tr>
        <tr><td class="k">Date d'export</td><td>{{ now()->format('d/m/Y à H:i') }}</td></tr>
    </table>

    @php
        $totalAbsent = $ungenerated->count() + $failed->count();
    @endphp

    @if($totalAbsent > 0)
        <div class="cov-warn">
            ⚠ {{ $totalAbsent }} bulletin{{ $totalAbsent > 1 ? 's' : '' }} du filtre {{ $totalAbsent > 1 ? 'sont' : 'est' }} absent{{ $totalAbsent > 1 ? 's' : '' }} de ce PDF (voir liste ci-dessous).
        </div>
        <div class="cov-note">
            Un bulletin doit être <strong>généré</strong> (moyenne calculée) pour figurer dans l'export.
            Les bulletins ci-dessous ne l'étaient pas encore, ou n'ont pas pu être rendus. Générez-les depuis
            la page Bulletins, puis relancez l'export pour les inclure.
        </div>

        @if($ungenerated->isNotEmpty())
            <div class="cov-sec">Bulletins non encore générés ({{ $ungenerated->count() }})</div>
            <table class="cov-tbl">
                <thead>
                    <tr><th style="width:22%">Matricule</th><th>Étudiant</th><th style="width:26%">Classe</th><th style="width:18%">État</th></tr>
                </thead>
                <tbody>
                    @foreach($ungenerated as $b)
                        <tr>
                            <td>{{ optional($b->etudiant)->matricule ?? '—' }}</td>
                            <td>{{ trim((optional($b->etudiant)->nom ?? '').' '.(optional($b->etudiant)->prenoms ?? '')) ?: '—' }}</td>
                            <td>{{ optional($b->classe)->name ?? '—' }}</td>
                            <td><span class="cov-badge cov-badge--pending">Non généré</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($failed->isNotEmpty())
            <div class="cov-sec">Bulletins non imprimables ({{ $failed->count() }})</div>
            <table class="cov-tbl">
                <thead>
                    <tr><th style="width:22%">Matricule</th><th>Étudiant</th><th style="width:26%">Classe</th><th style="width:18%">État</th></tr>
                </thead>
                <tbody>
                    @foreach($failed as $b)
                        <tr>
                            <td>{{ optional($b->etudiant)->matricule ?? '—' }}</td>
                            <td>{{ trim((optional($b->etudiant)->nom ?? '').' '.(optional($b->etudiant)->prenoms ?? '')) ?: '—' }}</td>
                            <td>{{ optional($b->classe)->name ?? '—' }}</td>
                            <td><span class="cov-badge cov-badge--failed">Erreur de rendu</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @else
        <div class="cov-note">Tous les bulletins du filtre sont inclus dans ce PDF.</div>
    @endif

    <div class="cov-foot">KLASSCI — page de récapitulatif générée automatiquement. Les bulletins suivent cette page.</div>
</body>
</html>
