@php
    $school = $school ?? \App\Helpers\SettingsHelper::getSchoolInfo();
    $pdfSettings = $pdfSettings ?? \App\Helpers\SettingsHelper::getPdfSettings();
    $logo = $logo ?? \App\Helpers\SettingsHelper::resolveLogoBase64();
    $hdrBg = $pdfSettings['header_bg_color'] ?? $pdfSettings['primary_color'] ?? '#0453cb';
    $hdrText = $pdfSettings['header_text_on_bg'] ?? $pdfSettings['header_text_color'] ?? '#ffffff';
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
    $compact = $compact ?? false;
@endphp
<style>
.pdf-banner { width: 100%; border-collapse: collapse; table-layout: fixed; -webkit-print-color-adjust: exact; }
.pdf-banner-logo-cell { width: {{ $compact ? '14%' : '16%' }}; background-color: {{ $hdrBg }}; padding: {{ $compact ? '6px 4px' : '10px 8px' }}; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.25); }
.pdf-banner-logo-frame { display: inline-block; background: #fff; border-radius: 5px; padding: 4px; }
.pdf-banner-logo { max-height: {{ $compact ? '32px' : '48px' }}; max-width: {{ $compact ? '64px' : '90px' }}; display: block; }
.pdf-banner-info-cell { background-color: {{ $hdrBg }}; padding: {{ $compact ? '6px 10px' : '10px 14px' }}; vertical-align: middle; }
.pdf-school-name { font-size: {{ $compact ? '11px' : '14px' }}; font-weight: 700; color: {{ $hdrText }}; margin: 0 0 2px; }
.pdf-school-meta { font-size: 7.5px; color: {{ $hdrText }}; opacity: 0.88; margin: 0 0 4px; line-height: 1.4; }
.pdf-banner-divider { border-top: 1px solid rgba(255,255,255,0.35); padding-top: 4px; }
.pdf-banner-title { font-size: {{ $compact ? '10px' : '12px' }}; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.4px; margin: 0; text-transform: uppercase; }
.pdf-banner-subtitle { font-size: 8px; color: {{ $hdrText }}; opacity: 0.88; margin: 2px 0 0; }
</style>
<table class="pdf-banner">
    <tr>
        <td class="pdf-banner-logo-cell">
            @if($logo)
                <span class="pdf-banner-logo-frame">
                    <img src="{{ $logo['data_uri'] }}" alt="logo" class="pdf-banner-logo">
                </span>
            @endif
        </td>
        <td class="pdf-banner-info-cell">
            <div class="pdf-school-name">{{ $school['name'] ?? config('app.name') }}</div>
            <div class="pdf-school-meta">
                @if(!empty($school['address'])){{ $school['address'] }}@endif
                @if(!empty($school['city'])) · {{ $school['city'] }}@endif
                @if(!empty($school['phone'])) · Tél : {{ $school['phone'] }}@endif
                @if(!empty($school['email'])) · {{ $school['email'] }}@endif
            </div>
            <div class="pdf-banner-divider">
                <div class="pdf-banner-title">{{ $title }}</div>
                @if($subtitle)<div class="pdf-banner-subtitle">{{ $subtitle }}</div>@endif
            </div>
        </td>
    </tr>
</table>
