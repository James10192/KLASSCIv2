{{-- Décision du conseil et signature du bulletin Yakro (pdf-configurable),
     côte à côte sur toute la largeur, à la même hauteur. Une décision vide
     laisse des lignes d'écriture plutôt qu'une boîte blanche. --}}
@php
    $councilDecision = $councilDecision ?? ['title' => 'Décision du conseil de classe', 'text' => $appreciation ?? ''];
    $csDecision = ($settings['bulletin_show_council_decision'] ?? '1') == '1';
    // Seul le bloc du directeur se signe : le réglage général sans lui ne montrait déjà rien.
    $csSignature = ($settings['bulletin_show_director_signature'] ?? '1') == '1';
    $csTexte = trim((string) ($decisionConseil ?? $councilDecision['text'] ?? $bulletin->decision_conseil ?? ''));
    $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
    $directorName = $settings['director_name'] ?? \App\Helpers\SettingsHelper::get('director_name', '');
@endphp
@if($csDecision || $csSignature)
<table class="cs-band">
    <tr>
        @if($csDecision)
        <td class="cs-cell" style="width: {{ $csSignature ? '62%' : '100%' }};">
            <div class="decision-container">
                <div class="decision-title">{{ $councilDecision['title'] ?? 'Décision du conseil de classe' }}</div>
                @if($csTexte !== '')
                    <div class="decision-text" style="font-size: {{ $typeScale['decision'] }}px;">{{ $csTexte }}</div>
                @else
                    <div class="decision-lignes"><div></div><div></div><div></div></div>
                @endif
            </div>
        </td>
        @elseif($csSignature)
        <td class="cs-cell" style="width: 62%;"></td>
        @endif
        @if($csSignature)
        <td class="cs-cell cs-cell--sign" style="width: 38%;">
            <div class="signature-container">
                <div class="signature-title" style="font-size: {{ $signatureFontSize }}px;">{{ $directorTitle }}</div>
                <div class="signature-space" style="height: {{ max(40, $signatureHeight - 20) }}px;"></div>
                <div class="signature-name" style="font-size: {{ $signatureFontSize }}px;">{{ $directorName ?: ' ' }}</div>
            </div>
        </td>
        @endif
    </tr>
</table>
@endif
