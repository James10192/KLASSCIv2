@php
    $doc = $snapshot['document'] ?? [];
    $student = $snapshot['student'] ?? [];
    $scope = $snapshot['scope'] ?? [];
    // Vocabulaire gele a l'emission ; un releve anterieur ne le porte pas.
    $rangs = ($scope['vocabulary'] ?? []) + ['domaine' => 'Domaine', 'mention' => 'Mention', 'parcours' => 'Parcours'];
    $libelleDomaine = $scope['domain_nature'] ?? $rangs['domaine'];
    $semesters = $snapshot['semesters'] ?? [];
    $totals = $snapshot['totals'] ?? [];
    $rules = $snapshot['rules'] ?? [];

    $note = static fn ($value): string => $value === null ? '—' : number_format((float) $value, 2, ',', ' ');
    $texte = static fn ($value): string => trim((string) ($value ?? '')) !== '' ? (string) $value : '—';

    $identite = trim(mb_strtoupper((string) ($student['last_name'] ?? ''), 'UTF-8').' '.($student['first_names'] ?? ''));
    $anneeLibelle = $scope['year']['label'] ?? '';

    $methodeMoyenne = match ($totals['average_method'] ?? null) {
        'weighted_by_expected_credits' => 'pondérée par les crédits attendus de chaque semestre',
        'arithmetic_mean' => 'moyenne arithmétique des semestres',
        default => null,
    };
@endphp

<x-pdf-document
    title="Relevé de notes"
    :subtitle="'Année universitaire '.$anneeLibelle"
    orientation="portrait"
    signature-block="director">

<style>
    .rn-id { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    .rn-id td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; }
    .rn-id .rn-k { width: 22%; color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 7.5px; letter-spacing: .3px; }
    .rn-id .rn-v { width: 28%; font-weight: 700; }

    .rn-sem { margin-top: 14px; page-break-inside: auto; }
    .rn-sem-title { background-color: #eff6ff; border-left: 3px solid #0453cb; color: #0453cb;
                    font-size: 11px; font-weight: 700; padding: 6px 8px; margin-bottom: 4px; page-break-after: avoid; }

    .rn-grid { width: 100%; border-collapse: collapse; }
    .rn-grid th { background-color: #0453cb; color: #ffffff; font-size: 7.5px; font-weight: 700;
                  text-transform: uppercase; letter-spacing: .3px; padding: 5px 4px; border: 1px solid #0453cb; }
    .rn-grid td { border: 1px solid #cbd5e1; padding: 4px; font-size: 8.5px; vertical-align: middle; }
    .rn-grid .rn-c { text-align: center; }
    .rn-ue td { background-color: #f1f5f9; font-weight: 700; }
    .rn-ecue td.rn-lib { padding-left: 14px; color: #334155; }
    .rn-empty td { text-align: center; color: #64748b; font-style: italic; }

    .rn-sem-foot { width: 100%; border-collapse: collapse; margin-top: 5px; }
    .rn-sem-foot td { border: 1px solid #cbd5e1; padding: 5px 7px; font-size: 8.5px; }
    .rn-sem-foot .rn-k { color: #64748b; font-weight: 700; text-transform: uppercase; font-size: 7.5px; }

    .rn-total { width: 100%; border-collapse: collapse; margin-top: 16px; page-break-inside: avoid; }
    .rn-total td { border: 2px solid #0453cb; padding: 8px 10px; text-align: center; }
    .rn-total .rn-k { font-size: 7.5px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; }
    .rn-total .rn-big { font-size: 15px; font-weight: 700; color: #0453cb; }

    .rn-note { margin-top: 10px; font-size: 7.5px; color: #64748b; line-height: 1.5; }
    .rn-verif { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
    .rn-verif td { border: 1px solid #cbd5e1; padding: 5px 8px; font-size: 8px; font-family: DejaVu Sans Mono, monospace; }
    .rn-verif .rn-k { background-color: #f8fafc; color: #64748b; width: 26%; font-family: DejaVu Sans, sans-serif; font-weight: 700; }
</style>

{{-- Identité de l'étudiant et rattachement académique --}}
<table class="rn-id">
    <tr>
        <td class="rn-k">Nom et prénoms</td>
        <td class="rn-v">{{ $texte($identite) }}</td>
        <td class="rn-k">Matricule</td>
        <td class="rn-v">{{ $texte($student['matricule'] ?? null) }}</td>
    </tr>
    <tr>
        <td class="rn-k">Né(e) le</td>
        <td class="rn-v">
            {{ $student['birth_date'] ? \Carbon\Carbon::parse($student['birth_date'])->format('d/m/Y') : '—' }}
            @if(!empty($student['birth_place'])) à {{ $student['birth_place'] }} @endif
        </td>
        <td class="rn-k">Année universitaire</td>
        <td class="rn-v">{{ $texte($anneeLibelle) }}</td>
    </tr>
    <tr>
        <td class="rn-k">{{ $libelleDomaine }}</td>
        <td class="rn-v">{{ $texte($scope['domain'] ?? null) }}</td>
        <td class="rn-k">{{ $rangs['mention'] }}</td>
        <td class="rn-v">{{ $texte($scope['mention'] ?? null) }}</td>
    </tr>
    <tr>
        <td class="rn-k">{{ $rangs['parcours'] }}</td>
        <td class="rn-v">
            {{ $texte($scope['parcours']['label'] ?? null) }}
            @if(!empty($scope['parcours']['code'])) ({{ $scope['parcours']['code'] }}) @endif
        </td>
        <td class="rn-k">Niveau</td>
        <td class="rn-v">{{ $texte($scope['level'] ?? null) }}</td>
    </tr>
</table>

{{-- Un bloc par semestre : unités d'enseignement puis leurs éléments constitutifs --}}
@foreach($semesters as $semester)
    <div class="rn-sem">
        <div class="rn-sem-title">
            Semestre {{ $semester['semester'] }}@if(!empty($semester['class'])) — {{ $semester['class'] }}@endif
        </div>

        <table class="rn-grid">
            <thead>
                <tr>
                    <th style="width: 13%;">Code</th>
                    <th style="width: 47%;">Intitulé</th>
                    <th style="width: 10%;">Crédits</th>
                    <th style="width: 12%;">Moy. / 20</th>
                    <th style="width: 18%;">Acquisition</th>
                </tr>
            </thead>
            <tbody>
            @forelse($semester['units'] as $unit)
                <tr class="rn-ue">
                    <td>{{ $texte($unit['code'] ?? null) }}</td>
                    <td>{{ $texte($unit['name'] ?? null) }}</td>
                    <td class="rn-c">{{ $unit['credits'] }}</td>
                    <td class="rn-c">{{ $note($unit['average'] ?? null) }}</td>
                    <td class="rn-c">
                        {{ $texte($unit['status'] ?? null) }}
                        @if(!empty($unit['status_label'])) — {{ $unit['status_label'] }} @endif
                    </td>
                </tr>
                @foreach($unit['elements'] as $element)
                    <tr class="rn-ecue">
                        <td>{{ $texte($element['code'] ?? null) }}</td>
                        <td class="rn-lib">{{ $texte($element['name'] ?? null) }}</td>
                        <td class="rn-c">{{ $element['credits'] > 0 ? $element['credits'] : '—' }}</td>
                        <td class="rn-c">{{ $note($element['average'] ?? null) }}</td>
                        <td class="rn-c"></td>
                    </tr>
                @endforeach
            @empty
                <tr class="rn-empty">
                    <td colspan="5">Aucune unité d'enseignement enregistrée pour ce semestre.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <table class="rn-sem-foot">
            <tr>
                <td class="rn-k">Moyenne du semestre</td>
                <td><strong>{{ $note($semester['average'] ?? null) }}</strong></td>
                <td class="rn-k">Mention</td>
                <td>{{ $texte($semester['mention'] ?? null) }}</td>
                <td class="rn-k">Crédits capitalisés</td>
                <td><strong>{{ $semester['credits_earned'] }}</strong> / {{ $semester['credits_expected'] }}</td>
            </tr>
            <tr>
                <td class="rn-k">Rang</td>
                <td>
                    @if($semester['rank'] !== null)
                        {{ $semester['rank'] }}@if($semester['headcount'] !== null) / {{ $semester['headcount'] }}@endif
                    @else
                        —
                    @endif
                </td>
                <td class="rn-k">Décision</td>
                <td colspan="3">{{ $texte($semester['decision'] ?? null) }}</td>
            </tr>
        </table>
    </div>
@endforeach

{{-- Cumul de l'année --}}
<table class="rn-total">
    <tr>
        <td style="width: 34%;">
            <div class="rn-k">Crédits capitalisés</div>
            <div class="rn-big">{{ $totals['credits_earned'] ?? 0 }} / {{ $totals['credits_expected'] ?? 0 }}</div>
        </td>
        <td style="width: 33%;">
            <div class="rn-k">Moyenne annuelle</div>
            <div class="rn-big">{{ $note($totals['average'] ?? null) }}</div>
        </td>
        <td style="width: 33%;">
            <div class="rn-k">Mention</div>
            <div class="rn-big">{{ $texte($totals['mention'] ?? null) }}</div>
        </td>
    </tr>
</table>

<div class="rn-note">
    <strong>AQ</strong> : unité acquise · <strong>APC</strong> : unité acquise par compensation ·
    <strong>NAQ</strong> : unité non acquise. Un crédit est capitalisable et transférable ;
    un élément constitutif d'unité d'enseignement, pris isolément, ne l'est pas.
    @if(!empty($rules['validation_threshold']))
        <br>Seuil de validation d'une unité : {{ $note($rules['validation_threshold']) }} / 20.
    @endif
    @if($methodeMoyenne)
        <br>Moyenne annuelle {{ $methodeMoyenne }}.
    @endif
</div>

{{-- Vérification d'authenticité --}}
<table class="rn-verif">
    <tr>
        <td class="rn-k">Référence</td>
        <td>{{ $doc['reference'] ?? '' }}</td>
    </tr>
    <tr>
        <td class="rn-k">Code de vérification</td>
        <td>{{ $verificationCode }}</td>
    </tr>
    <tr>
        <td class="rn-k">Vérifier ce document</td>
        <td style="font-family: DejaVu Sans, sans-serif;">
            {{ route('official-documents.verify.form') }}
        </td>
    </tr>
</table>

</x-pdf-document>
