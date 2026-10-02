{{-- Décision du conseil et signature du bulletin Yakro (pdf-configurable).
     Incluse à un seul des deux endroits selon $conseilADroite. --}}
@php
    $councilDecision = $councilDecision ?? ['title' => 'Décision du conseil de classe', 'text' => $appreciation ?? ''];
@endphp
{{-- Décision du conseil --}}
@if(($settings['bulletin_show_council_decision'] ?? '1') == '1')
<div class="decision-container">
    <div class="decision-title">{{ $councilDecision['title'] ?? 'Décision du conseil de classe' }}</div>
    <div style="min-height: 36px; font-size: {{ $typeScale['decision'] }}px;">{{ $decisionConseil ?? $councilDecision['text'] ?? $bulletin->decision_conseil ?? '' }}</div>
</div>
@endif

{{-- Signature --}}
@if(($settings['bulletin_show_signature'] ?? '1') == '1' || ($settings['bulletin_show_director_signature'] ?? '1') == '1')
@php
    $directorTitle = $settings['director_title'] ?? \App\Helpers\SettingsHelper::get('director_title', 'Directeur');
    $directorName  = $settings['director_name']  ?? \App\Helpers\SettingsHelper::get('director_name', '');
@endphp
<div class="signature-container">
    @if(($settings['bulletin_show_director_signature'] ?? '1') == '1')
    <div class="signature-box">
        <div style="font-size: {{ $signatureFontSize }}px;">{{ $directorTitle }}</div>
        <div class="signature-line"></div>
        @if($directorName)
            <div style="margin-top: 4px; font-weight: 700; font-size: {{ $signatureFontSize }}px;">{{ $directorName }}</div>
        @endif
    </div>
    @endif
</div>
@endif
