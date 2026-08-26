@php
    $showCouncil = ($settings['bulletin_show_council_decision'] ?? '1') == '1';
    $showSignature = ($settings['bulletin_show_signature'] ?? '1') == '1'
        || ($settings['bulletin_show_director_signature'] ?? '1') == '1';
    $councilTitle = $councilDecision['title'] ?? 'Appréciation du conseil de classe';
    $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur des études');
    $directorName = $settings['director_name'] ?? \App\Helpers\SettingsHelper::get('director_name', '');
    $mentionItems = (($settings['bulletin_show_mentions'] ?? '1') == '1')
        ? \App\Services\BulletinMentionResolver::resolveFromSettings(
            isset($moyenneGlobale) ? (float) $moyenneGlobale : null,
            isset($noteConduite) ? (float) $noteConduite : null
        )
        : [];
    $mentionRows = array_chunk($mentionItems, 2);
    $faitALe = \App\Services\BulletinMentionResolver::faitALeLine($date_edition ?? null);
    $statsLabel = $periode == 'semestre1' ? 'SEMESTRE 1' : ($periode == 'semestre2' ? 'SEMESTRE 2' : 'ANNUEL');
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
                                @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
                                    <th class="pair-split">STATISTIQUES — {{ $statsLabel }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <table class="pair-table">
                                        <tbody>
                                            @if(($settings['bulletin_show_raw_average'] ?? '1') == '1')
                                                <tr>
                                                    <td>Moyenne brute</td>
                                                    <td class="center"><span class="result-value-box">{{ number_format($moyenneGlobale, 2) }}</span></td>
                                                </tr>
                                            @endif
                                            @if(($settings['bulletin_show_attendance_note'] ?? '1') == '1')
                                                <tr>
                                                    <td>Note d'assiduité</td>
                                                    <td class="center"><span class="result-value-box">{{ $note_assiduite > 0 ? '+'.number_format($note_assiduite, 2) : number_format($note_assiduite, 2) }}</span></td>
                                                </tr>
                                            @endif
                                            @if(($settings['bulletin_show_semester_average'] ?? '1') == '1')
                                                @if($periode == 'semestre1' || $periode == 'semestre2')
                                                    <tr>
                                                        <td class="result-key">Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} semestre</td>
                                                        <td class="center"><span class="result-value-box">{{ number_format($moyenneAvecAssiduite, 2) }}</span></td>
                                                    </tr>
                                                @endif
                                                @if($periode == 'semestre2')
                                                    <tr>
                                                        <td>Moyenne semestre 1</td>
                                                        <td class="center"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="result-key">Moyenne annuelle</td>
                                                        <td class="center"><span class="result-value-box">{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</span></td>
                                                    </tr>
                                                @endif
                                                @if($periode == 'annuel')
                                                    <tr>
                                                        <td>Moyenne semestre 1</td>
                                                        <td class="center"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Moyenne semestre 2</td>
                                                        <td class="center"><span class="result-value-box">{{ $moyenneSemestre2 !== null ? number_format($moyenneSemestre2, 2) : '-' }}</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="result-key">Moyenne annuelle</td>
                                                        <td class="center"><span class="result-value-box">{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</span></td>
                                                    </tr>
                                                @endif
                                            @endif
                                            @if(($settings['bulletin_show_student_rank'] ?? '1') == '1')
                                                <tr>
                                                    <td class="result-key">{{ in_array($periode, ['semestre2', 'annuel'], true) ? 'Rang semestre 2' : 'Rang' }}</td>
                                                    <td class="center"><span class="result-value-box">{{ $rang ?: '-' }}</span></td>
                                                </tr>
                                                @if(in_array($periode, ['semestre2', 'annuel'], true))
                                                    <tr>
                                                        <td class="result-key">Rang annuel</td>
                                                        <td class="center"><span class="result-value-box">{{ ($rangAnnuel ?? null) ?: '-' }}</span></td>
                                                    </tr>
                                                @endif
                                            @endif
                                        </tbody>
                                    </table>
                                </td>
                                @if(($settings['bulletin_show_statistics'] ?? '1') == '1')
                                    <td class="pair-split">
                                        <table class="pair-table">
                                            <tbody>
                                                @if(($settings['bulletin_show_highest_average'] ?? '1') == '1')
                                                    <tr><td>Plus forte moyenne</td><td class="center result-key">{{ number_format($meilleure_moyenne, 2) }}</td></tr>
                                                @endif
                                                @if(($settings['bulletin_show_lowest_average'] ?? '1') == '1')
                                                    <tr><td>Plus faible moyenne</td><td class="center">{{ number_format($plus_faible_moyenne, 2) }}</td></tr>
                                                @endif
                                                @if(($settings['bulletin_show_class_average'] ?? '1') == '1')
                                                    <tr><td class="result-key">Moyenne de la classe</td><td class="center result-key">{{ number_format($moyenne_classe, 2) }}</td></tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </td>
                                @endif
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if(count($mentionRows) > 0)
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
                @endif
            </td>
            @if($showCouncil)
                <td class="results-council">
                    <table class="council-table council-card">
                        <tr>
                            <td class="council-label">{{ $councilTitle }}</td>
                        </tr>
                        <tr>
                            <td class="council-grow">
                                <div class="council-text">{{ $decisionConseil ?? $councilDecision['text'] ?? $bulletin->decision_conseil ?? '' }}</div>
                                @if($faitALe !== '')
                                    <div class="council-place">{{ $faitALe }}</div>
                                @endif
                            </td>
                        </tr>
                        @if($showSignature)
                            <tr>
                                <td class="council-sign">
                                    <div class="council-sign-title">{{ $directorTitle }}</div>
                                    <div class="council-sign-space"></div>
                                    @if($directorName)
                                        <div class="council-sign-name">{{ $directorName }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </table>
                </td>
            @endif
        </tr>
    </table>
</div>
