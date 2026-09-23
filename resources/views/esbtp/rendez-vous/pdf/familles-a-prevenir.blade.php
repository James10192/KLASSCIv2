@php
    $pdfCfg = \App\Helpers\SettingsHelper::getPdfSettings();
    $fapAccent = $pdfCfg['primary_color'] ?? '#0453cb';
    $fapTexte = \App\Helpers\SettingsHelper::contrastingText($fapAccent, $pdfCfg['header_text_color_raw'] ?? '#ffffff');
    $fapJours = collect($lignes)->groupBy('date');
@endphp

<x-pdf-document
    title="Familles à prévenir"
    subtitle="Rendez-vous d'inscription à venir sans convocation reçue par e-mail"
    :filters="['Familles' => (string) count($lignes), 'Arrêtée le' => now()->format('d/m/Y H:i')]"
    orientation="landscape">

    <style>
        .fap-jour { font-size: 10pt; font-weight: bold; color: {{ $fapAccent }}; margin: 12px 0 4px; }
        .fap-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .fap-table th { background-color: {{ $fapAccent }}; color: {{ $fapTexte }}; padding: 5px 6px; text-align: left; font-size: 7.5pt; text-transform: uppercase; }
        .fap-table td { padding: 5px 6px; border-bottom: 1px solid #e2e8f0; color: #1e293b; vertical-align: top; }
        .fap-table tr:nth-child(even) td { background: #f8fafc; }
        .fap-heure { white-space: nowrap; font-weight: bold; }
        .fap-tel { white-space: nowrap; }
        .fap-meta { color: #64748b; font-size: 7.5pt; }
        .fap-case { width: 11px; height: 11px; border: 1px solid #94a3b8; display: inline-block; }
        .fap-vide { text-align: center; color: #64748b; padding: 30px 0; }
    </style>

    @if($fapJours->isEmpty())
        <p class="fap-vide">Aucune famille à prévenir : toutes les convocations à venir sont parties par e-mail ou en attente d'envoi.</p>
    @else
        @foreach($fapJours as $fapLignes)
            <div class="fap-jour">{{ $fapLignes->first()['jour'] }} — {{ $fapLignes->count() }} famille{{ $fapLignes->count() > 1 ? 's' : '' }}</div>
            <table class="fap-table">
                <thead>
                    <tr>
                        <th style="width: 11%">Créneau</th>
                        <th style="width: 22%">Candidat</th>
                        <th style="width: 14%">Téléphone</th>
                        <th style="width: 22%">Second contact</th>
                        <th style="width: 23%">Pourquoi</th>
                        <th style="width: 8%">Prévenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fapLignes as $l)
                        <tr>
                            <td class="fap-heure">{{ $l['heure'] }}</td>
                            <td>{{ $l['nom'] }} {{ $l['prenoms'] }}@if($l['reference'] !== '')<br><span class="fap-meta">{{ $l['reference'] }}</span>@endif</td>
                            <td class="fap-tel">{{ \App\Domain\Notifications\PhoneFormatter::toReadable($l['telephone']) ?? $l['telephone'] }}</td>
                            <td>
                                @if($l['contact2_telephone'])
                                    {{ $l['contact2_nom'] }}<br><span class="fap-tel">{{ \App\Domain\Notifications\PhoneFormatter::toReadable($l['contact2_telephone']) ?? $l['contact2_telephone'] }}</span>
                                @else
                                    <span class="fap-meta">—</span>
                                @endif
                            </td>
                            <td class="fap-meta">{{ $l['motif'] }}</td>
                            <td><span class="fap-case"></span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif
</x-pdf-document>
