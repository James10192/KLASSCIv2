@props([
    'summary' => '',
    'size'    => 9, // px
])
{{-- Pill pictogramme accessibilité PDF — SVG inline (silhouette stick-figure
     bras écartés). Utilisé inline dans les listings PDFs pour signaler un
     étudiant avec profil d'accessibilité. SVG = pas de dépendance font (font
     subsetting peut stripper les glyphes Unicode rares). --}}
<span style="display:inline-block;background:#0453cb;border-radius:50px;padding:1px 5px;margin-left:3px;line-height:1;-webkit-print-color-adjust:exact;color-adjust:exact;vertical-align:middle;" title="{{ $summary }}"><svg viewBox="0 0 16 16" width="{{ $size }}" height="{{ $size }}" xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle;"><circle cx="8" cy="3" r="1.7" fill="#fff"/><path d="M3 6.5 H13 M8 5 V10.5 M8 10.5 L5 13.5 M8 10.5 L11 13.5" stroke="#fff" stroke-width="1.4" fill="none" stroke-linecap="round"/></svg></span>
