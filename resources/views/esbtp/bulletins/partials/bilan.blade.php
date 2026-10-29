{{--
    Bas du bulletin Yakro (pdf-configurable) : un bilan en colonnes, puis la
    décision du conseil et la signature côte à côte sur toute la largeur.

    Tout en tableaux : DomPDF ne connaît ni flex ni grid. Chaque bloc suit son
    réglage d'affichage ; les colonnes présentes se partagent la largeur, sans
    trou quand l'une est masquée.
--}}
@php
    $blnResultats  = ($settings['bulletin_show_results_section'] ?? '1') == '1';
    $blnStats      = ($settings['bulletin_show_statistics'] ?? '1') == '1';
    $blnAbsences   = ($settings['bulletin_show_absences'] ?? '1') == '1'
        && (($settings['bulletin_show_justified_absences'] ?? '1') == '1' || ($settings['bulletin_show_unjustified_absences'] ?? '1') == '1');
    $blnMentions   = ($settings['bulletin_show_mentions'] ?? '1') == '1';
    $blnColonnes   = array_values(array_filter([
        $blnResultats ? 'resultats' : null,
        ($blnStats || $blnAbsences) ? 'stats' : null,
        $blnMentions ? 'mentions' : null,
    ]));
    $blnLargeur = $blnColonnes ? floor(100 / count($blnColonnes)) : 100;
    $blnHeures = fn ($v) => ($v === null || $v === '') ? '0' : $v;
    $blnJustifiees = $absencesJustifiees ?? $absences_justifiees ?? $bulletin->absences_justifiees ?? 0;
    $blnNonJustifiees = $absencesNonJustifiees ?? $absences_non_justifiees ?? $bulletin->absences_non_justifiees ?? 0;
    $blnPeriode = $periode == 'semestre1' ? 'Semestre 1' : ($periode == 'semestre2' ? 'Semestre 2' : 'Année');
@endphp

@if($blnColonnes)
<table class="bln-grid">
    <tr>
        @foreach($blnColonnes as $blnCol)
        <td class="bln-col {{ $loop->first ? 'bln-col--first' : '' }} {{ $loop->last ? 'bln-col--last' : '' }}" style="width: {{ $blnLargeur }}%;">
            @if($blnCol === 'resultats')
            <table class="bln-card">
                <thead><tr><th colspan="2">Résultats</th></tr></thead>
                <tbody>
                @if(($settings['bulletin_show_raw_average'] ?? '1') == '1')
                <tr>
                    <td>Moyenne Brute</td>
                    <td class="bln-val"><span class="result-value-box">{{ number_format($moyenneGlobale, 2) }}</span></td>
                </tr>
                @endif
                @if(($settings['bulletin_show_attendance_note'] ?? '1') == '1')
                <tr>
                    <td>Note d'assiduité</td>
                    <td class="bln-val"><span class="result-value-box">{{ $note_assiduite > 0 ? '+'.number_format($note_assiduite, 2) : number_format($note_assiduite, 2) }}</span></td>
                </tr>
                @endif
                @if(($settings['bulletin_show_semester_average'] ?? '1') == '1')
                @if($periode == 'semestre1' || $periode == 'semestre2')
                <tr>
                    <td>Moyenne {{ $periode == 'semestre1' ? '1er' : '2e' }} Semestre</td>
                    <td class="bln-val"><span class="result-value-box">{{ number_format($moyenneAvecAssiduite, 2) }}</span></td>
                </tr>
                @endif
                @if($periode == 'semestre2')
                <tr>
                    <td>Moyenne Semestre 1</td>
                    <td class="bln-val"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                </tr>
                <tr>
                    <td>Moyenne Annuelle</td>
                    <td class="bln-val"><span class="result-value-box">{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</span></td>
                </tr>
                @endif
                @if($periode == 'annuel')
                <tr>
                    <td>Moyenne Semestre 1</td>
                    <td class="bln-val"><span class="result-value-box">{{ $moyenneSemestre1 !== null ? number_format($moyenneSemestre1, 2) : '-' }}</span></td>
                </tr>
                <tr>
                    <td>Moyenne Semestre 2</td>
                    <td class="bln-val"><span class="result-value-box">{{ $moyenneSemestre2 !== null ? number_format($moyenneSemestre2, 2) : '-' }}</span></td>
                </tr>
                <tr>
                    <td><strong>Moyenne Annuelle</strong></td>
                    <td class="bln-val"><span class="result-value-box"><strong>{{ $moyenneAnnuelle !== null ? number_format($moyenneAnnuelle, 2) : '-' }}</strong></span></td>
                </tr>
                @endif
                @endif
                @if(($settings['bulletin_show_student_rank'] ?? '1') == '1')
                <tr>
                    <td>{{ in_array($periode, ['semestre2', 'annuel'], true) ? 'Rang semestre 2' : 'Rang' }}</td>
                    <td class="bln-val"><span class="result-value-box">{{ $rang ?: '-' }}</span></td>
                </tr>
                @if(in_array($periode, ['semestre2', 'annuel'], true))
                <tr>
                    <td>Rang annuel</td>
                    <td class="bln-val"><span class="result-value-box">{{ ($rangAnnuel ?? null) ?: '-' }}</span></td>
                </tr>
                @endif
                @endif

                </tbody>
            </table>
            @elseif($blnCol === 'stats')
                @if($blnStats)
                <table class="bln-card">
                    <thead><tr><th colspan="2">Statistiques · {{ $blnPeriode }}</th></tr></thead>
                    <tbody>
                @if(($settings['bulletin_show_highest_average'] ?? '1') == '1')
                <tr><td>Plus forte moyenne</td><td class="bln-val">{{ number_format($meilleure_moyenne, 2) }}</td></tr>
                @endif
                @if(($settings['bulletin_show_lowest_average'] ?? '1') == '1')
                <tr><td>Plus faible moyenne</td><td class="bln-val">{{ number_format($plus_faible_moyenne, 2) }}</td></tr>
                @endif
                @if(($settings['bulletin_show_class_average'] ?? '1') == '1')
                <tr><td>Moyenne de la classe</td><td class="bln-val">{{ number_format($moyenne_classe, 2) }}</td></tr>
                @endif

                    </tbody>
                </table>
                @endif
                @if($blnAbsences)
                <table class="bln-card {{ $blnStats ? 'bln-card--suite' : '' }}">
                    <thead><tr><th colspan="2">Absences</th></tr></thead>
                    <tbody>
                        @if(($settings['bulletin_show_justified_absences'] ?? '1') == '1')
                        <tr><td>Justifiées</td><td class="bln-val">{{ $blnHeures($blnJustifiees) }} h</td></tr>
                        @endif
                        @if(($settings['bulletin_show_unjustified_absences'] ?? '1') == '1')
                        <tr><td>Non justifiées</td><td class="bln-val">{{ $blnHeures($blnNonJustifiees) }} h</td></tr>
                        @endif
                    </tbody>
                </table>
                @endif
            @else
                @php
                    $_mentions = \App\Services\BulletinMentionResolver::resolveFromSettings(
                        isset($moyenneGlobale) ? (float) $moyenneGlobale : null,
                        isset($noteConduite) ? (float) $noteConduite : null
                    );
                @endphp
                <table class="bln-card bln-mentions">
                    <thead><tr><th colspan="2">Mentions du conseil</th></tr></thead>
                    <tbody>
                        @foreach($_mentions as $m)
                        <tr class="{{ $m['checked'] ? 'bln-mention--on' : '' }}">
                            <td>{{ $m['label'] }}</td>
                            <td class="bln-check"><span class="bln-box">{{ $m['checked'] ? '✓' : '' }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </td>
        @endforeach
    </tr>
</table>
@endif

@include('esbtp.bulletins.partials.conseil-signature')
