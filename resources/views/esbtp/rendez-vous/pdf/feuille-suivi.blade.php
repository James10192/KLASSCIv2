@php
    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $fsAccent = $pdfCfg['primary_color'] ?? '#0453cb';
    $fsTexte = \App\Helpers\SettingsHelper::contrastingText($fsAccent, $pdfCfg['header_text_color_raw'] ?? '#ffffff');
    $fsJours = collect($lignes)->groupBy('date');
    $fsTel = fn ($n) => \App\Domain\Notifications\PhoneFormatter::toReadable($n) ?? $n;
@endphp

<x-pdf-document
    title="Suivi des rendez-vous"
    :subtitle="$sousTitre"
    :filters="$reportFilters ?? []"
    orientation="landscape">

    <style>
        /* Largeurs en pourcentage + table-layout fixe : la feuille tient dans
           les marges reglees de l'etablissement, quel que soit le contenu. */
        .fs-jour { font-size: 10.5pt; font-weight: bold; color: {{ $fsAccent }}; margin: 4px 0 2px; }
        .fs-jour-resume { font-size: 8pt; color: #64748b; margin: 0 0 6px; }
        .fs-saut { page-break-before: always; }
        .fs-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8.5pt; }
        .fs-table thead { display: table-header-group; }
        .fs-table th { background-color: {{ $fsAccent }}; color: {{ $fsTexte }}; padding: 5px 5px; text-align: left; font-size: 7pt; text-transform: uppercase; border: 1px solid {{ $fsAccent }}; }
        .fs-table td { padding: 6px 5px; border: 1px solid #cbd5e1; color: #1e293b; vertical-align: top; word-wrap: break-word; }
        .fs-table tr { page-break-inside: avoid; }
        .fs-table td.fs-creneau { background-color: #eef2f9; color: {{ $fsAccent }}; font-weight: bold; font-size: 8pt; padding: 4px 5px; }
        .fs-table td.fs-num { text-align: center; color: #64748b; }
        .fs-table td.fs-tel { font-size: 8pt; }
        .fs-table td.fs-case { text-align: center; }
        .fs-nom { font-weight: bold; }
        .fs-meta { color: #64748b; font-size: 7pt; }
        .fs-coche { display: inline-block; width: 12px; height: 12px; border: 1px solid #475569; }
        .fs-statut { font-size: 7pt; font-weight: bold; color: #334155; }
        .fs-vide { text-align: center; color: #64748b; padding: 30px 0; }
        .fs-visa { width: 100%; margin-top: 14px; font-size: 8pt; color: #475569; page-break-inside: avoid; }
        .fs-visa td { padding-top: 4px; }
    </style>

    @if($fsJours->isEmpty())
        <p class="fs-vide">Aucune famille attendue sur cette période.</p>
    @else
        @foreach($fsJours as $fsLignes)
            <div class="{{ $loop->first ? '' : 'fs-saut' }}">
                <div class="fs-jour">{{ $fsLignes->first()['jour'] }}</div>
                <p class="fs-jour-resume">
                    {{ $fsLignes->count() }} famille{{ $fsLignes->count() > 1 ? 's' : '' }} attendue{{ $fsLignes->count() > 1 ? 's' : '' }}
                    · {{ $fsLignes->pluck('heure')->unique()->count() }} créneau{{ $fsLignes->pluck('heure')->unique()->count() > 1 ? 'x' : '' }}
                </p>

                <table class="fs-table">
                    <thead>
                        <tr>
                            <th style="width: 4%">N°</th>
                            <th style="width: 21%">Candidat</th>
                            <th style="width: 9%">Dossier</th>
                            <th style="width: 14%">Téléphone</th>
                            <th style="width: 16%">Second contact</th>
                            <th style="width: 9%">Convocation</th>
                            <th style="width: 6%">Reçue</th>
                            <th style="width: 21%">Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fsLignes->groupBy('heure') as $fsHeure => $fsFamilles)
                            <tr>
                                <td class="fs-creneau" colspan="8">{{ $fsHeure }} — {{ $fsFamilles->count() }} famille{{ $fsFamilles->count() > 1 ? 's' : '' }}</td>
                            </tr>
                            @foreach($fsFamilles as $l)
                                <tr>
                                    <td class="fs-num">{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="fs-nom">{{ $l['nom'] }}</span> {{ $l['prenoms'] }}
                                        @if($l['reference'] !== '')<br><span class="fs-meta">{{ $l['reference'] }}</span>@endif
                                        @if($l['a_verifier'])<br><span class="fs-meta">Contact non vérifié</span>@endif
                                    </td>
                                    <td>{{ $l['dossier'] }}</td>
                                    <td class="fs-tel">{{ $fsTel($l['telephone']) }}</td>
                                    <td>
                                        @if($l['contact2_telephone'])
                                            {{ $l['contact2_nom'] }}<br><span class="fs-meta" style="font-size: 7.5pt">{{ $fsTel($l['contact2_telephone']) }}</span>
                                        @else
                                            <span class="fs-meta">—</span>
                                        @endif
                                    </td>
                                    <td class="fs-meta">{{ $l['convocation'] }}</td>
                                    <td class="fs-case">
                                        @if($l['statut'] !== '')
                                            <span class="fs-statut">{{ $l['statut'] }}</span>
                                        @else
                                            <span class="fs-coche"></span>
                                        @endif
                                    </td>
                                    <td></td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>

                <table class="fs-visa">
                    <tr>
                        <td style="width: 50%">Agent d'accueil : ..............................................................</td>
                        <td style="width: 50%">Visa : ..............................................................</td>
                    </tr>
                </table>
            </div>
        @endforeach
    @endif
</x-pdf-document>
