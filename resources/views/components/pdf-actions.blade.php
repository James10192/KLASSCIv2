@props([
    'previewUrl',
    'downloadUrl',
    'label' => 'PDF',
    'buttonClass' => 'btn-acasi primary',
    'previewClass' => null,
    'downloadClass' => null,
    'previewLabel' => 'Aperçu',
    'downloadLabel' => 'Télécharger',
    'size' => null, // null | 'sm'
])

@php
    $previewBtn = $previewClass ?: $buttonClass;
    $downloadBtn = $downloadClass ?: $buttonClass;
    $sizeStyle = $size === 'sm' ? 'padding:.35rem .7rem;font-size:.78rem;' : '';
@endphp

<span class="pdf-actions" role="group" aria-label="{{ $label }}">
    <a href="{{ $previewUrl }}"
       target="_blank"
       rel="noopener"
       class="{{ $previewBtn }} pdf-actions__btn pdf-actions__btn--preview"
       title="Aperçu {{ $label }} dans un nouvel onglet"
       style="{{ $sizeStyle }}">
        <i class="fas fa-eye"></i>
        <span class="pdf-actions__label">{{ $previewLabel }}</span>
    </a>
    <a href="{{ $downloadUrl }}"
       class="{{ $downloadBtn }} pdf-actions__btn pdf-actions__btn--download"
       title="Télécharger le {{ $label }}"
       style="{{ $sizeStyle }}">
        <i class="fas fa-download"></i>
        <span class="pdf-actions__label">{{ $downloadLabel }}</span>
    </a>
</span>

@once
@push('styles')
<style>
.pdf-actions {
    display: inline-flex;
    gap: .4rem;
    align-items: center;
    flex-wrap: wrap;
}
.pdf-actions__btn {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
}
.pdf-actions__btn i {
    font-size: .85em;
}
@media (max-width: 576px) {
    .pdf-actions__label { display: none; }
    .pdf-actions__btn { padding-left: .55rem; padding-right: .55rem; }
}
</style>
@endpush
@endonce
