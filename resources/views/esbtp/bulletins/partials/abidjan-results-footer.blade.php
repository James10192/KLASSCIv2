@php
    $showCouncil = ($settings['bulletin_show_council_decision'] ?? '1') == '1';
    $showSignature = ($settings['bulletin_show_signature'] ?? '1') == '1'
        || ($settings['bulletin_show_director_signature'] ?? '1') == '1';
    $councilTitle = $councilDecision['title'] ?? 'Appréciation du conseil de classe';
    $councilText = (string) ($councilDecision['text'] ?? '');
    $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur des études');
    $directorName = $settings['director_name'] ?? \App\Helpers\SettingsHelper::get('director_name', '');
    $mentionItems = \App\Services\BulletinMentionResolver::resolveFromSettings(
        isset($moyenneGlobale) ? (float) $moyenneGlobale : null,
        isset($noteConduite) ? (float) $noteConduite : null
    );
    $mentionRows = array_chunk($mentionItems, 2);
    $statsLabel = $periode == 'semestre1' ? 'SEMESTRE 1' : ($periode == 'semestre2' ? 'SEMESTRE 2' : 'ANNUEL');
    $fmt = static fn ($value) => $value === null ? '-' : number_format((float) $value, 2);
    $resultLines = [];
    if (($settings['bulletin_show_raw_average'] ?? '1') == '1') {
        $resultLines[] = ['label' => 'Moyenne brute', 'value' => $fmt($moyenneGlobale ?? null), 'boxed' => true, 'strong' => false];
    }
    if (($settings['bulletin_show_attendance_note'] ?? '1') == '1') {
        $assiduite = (float) ($note_assiduite ?? 0);
        $resultLines[] = [
            'label' => "Note d'assiduité",
            'value' => ($assiduite > 0 ? '+' : '').number_format($assiduite, 2),
            'boxed' => true,
            'strong' => false,
        ];
    }
    if (($settings['bulletin_show_semester_average'] ?? '1') == '1') {
        if ($periode == 'semestre1' || $periode == 'semestre2') {
            $resultLines[] = [
                'label' => 'Moyenne '.($periode == 'semestre1' ? '1er' : '2e').' semestre',
                'value' => $fmt($moyenneAvecAssiduite ?? null),
                'boxed' => true,
                'strong' => true,
            ];
        }
        if ($periode == 'semestre2' || $periode == 'annuel') {
            $resultLines[] = ['label' => 'Moyenne semestre 1', 'value' => $fmt($moyenneSemestre1 ?? null), 'boxed' => true, 'strong' => false];
        }
        if ($periode == 'annuel') {
            $resultLines[] = ['label' => 'Moyenne semestre 2', 'value' => $fmt($moyenneSemestre2 ?? null), 'boxed' => true, 'strong' => false];
        }
        if ($periode == 'semestre2' || $periode == 'annuel') {
            $resultLines[] = ['label' => 'Moyenne annuelle', 'value' => $fmt($moyenneAnnuelle ?? null), 'boxed' => true, 'strong' => true];
        }
    }
    if (($settings['bulletin_show_student_rank'] ?? '1') == '1') {
        $resultLines[] = [
            'label' => in_array($periode, ['semestre2', 'annuel'], true) ? 'Rang semestre 2' : 'Rang',
            'value' => $rang ?: '-',
            'boxed' => true,
            'strong' => true,
        ];
        if (in_array($periode, ['semestre2', 'annuel'], true)) {
            $resultLines[] = ['label' => 'Rang annuel', 'value' => ($rangAnnuel ?? null) ?: '-', 'boxed' => true, 'strong' => true];
        }
    }
    $statLines = [];
    if (($settings['bulletin_show_statistics'] ?? '1') == '1') {
        if (($settings['bulletin_show_highest_average'] ?? '1') == '1') {
            $statLines[] = ['label' => 'Plus forte moyenne', 'value' => $fmt($meilleure_moyenne ?? null), 'boxed' => true, 'strong' => true];
        }
        if (($settings['bulletin_show_lowest_average'] ?? '1') == '1') {
            $statLines[] = ['label' => 'Plus faible moyenne', 'value' => $fmt($plus_faible_moyenne ?? null), 'boxed' => true, 'strong' => false];
        }
        if (($settings['bulletin_show_class_average'] ?? '1') == '1') {
            $statLines[] = ['label' => 'Moyenne de la classe', 'value' => $fmt($moyenne_classe ?? null), 'boxed' => true, 'strong' => true];
        }
    }
    $decisionHeight = $decisionHeight ?? 84;
    $signatureHeight = $signatureHeight ?? 44;
@endphp
<div class="results-container">
    <table class="results-container-table">
        <tr>
            <td class="results-main">
                <div class="pair-card">
                    <table class="pair-table">
                        <thead>
                            <tr>
                                <th>RÉSULTATS</th>
                                @if(count($statLines) > 0)
                                    <th class="pair-split">STATISTIQUES — {{ $statsLabel }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <table class="pair-table">
                                        <tbody>
                                            @foreach($resultLines as $line)
                                                <tr>
                                                    <td class="{{ $line['strong'] ? 'result-key' : '' }}">{{ $line['label'] }}</td>
                                                    <td class="center">
                                                        @if($line['boxed'])
                                                            <span class="result-value-box">{{ $line['value'] }}</span>
                                                        @else
                                                            {{ $line['value'] }}
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                                @if(count($statLines) > 0)
                                    <td class="pair-split">
                                        <table class="pair-table">
                                            <tbody>
                                                @foreach($statLines as $line)
                                                    <tr>
                                                        <td class="{{ $line['strong'] ? 'result-key' : '' }}">{{ $line['label'] }}</td>
                                                        <td class="center">
                                                            @if($line['boxed'] ?? false)
                                                                <span class="result-value-box">{{ $line['value'] }}</span>
                                                            @else
                                                                {{ $line['value'] }}
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                    @if(count($mentionRows) > 0)
                        <div class="mention-in-card">
                            <table class="mention-columns">
                                @foreach($mentionRows as $row)
                                    <tr>
                                        @foreach($row as $i => $item)
                                            <td class="mention-col {{ $i === 0 ? 'mention-col--left' : 'mention-col--right' }}">
                                                <div class="mention-box"><table class="mention-table"><tr><td class="mention-label">{{ $item['label'] }}</td><td class="mention-value"><input type="checkbox" {{ $item['checked'] ? 'checked' : '' }}></td></tr></table></div>
                                            </td>
                                        @endforeach
                                        @if(count($row) === 1)
                                            <td class="mention-col mention-col--right"></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </table>
                        </div>
                    @endif
                </div>
            </td>
            @if($showCouncil)
                <td class="results-council">
                    <div class="pair-card">
                        <table class="council-table">
                            <thead>
                                <tr>
                                    <th>CONSEIL DE CLASSE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="council-half-cell" height="{{ $decisionHeight }}" valign="top">
                                        <div class="council-sub">{{ $councilTitle }}</div>
                                        <div class="council-text">{{ $councilText }}</div>
                                    </td>
                                </tr>
                                @if($showSignature)
                                    <tr>
                                        <td class="council-sign council-sign-start council-half-cell--sign" align="center" valign="top">{{ $directorTitle }}</td>
                                    </tr>
                                    <tr>
                                        <td class="council-sign council-sign-gap" height="{{ $signatureHeight }}">&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td class="council-sign council-half-cell--sign" align="center" valign="bottom">
                                            @if($directorName)
                                                <div class="council-sign-name">{{ $directorName }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </td>
            @endif
        </tr>
    </table>
</div>
