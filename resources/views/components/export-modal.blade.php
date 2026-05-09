@props([
    'previewUrl',
    'pdfUrl',
    'excelUrl',
    'emailUrl' => null,
    'buttonClass' => 'an-btn an-btn--glass',
    'label' => 'Exporter',
])

<div x-data="{ open: false }" @click.outside="open = false" style="display: inline-block; position: relative;">
    <button type="button" @click="open = !open" class="{{ $buttonClass }}">
        <i class="fas fa-download"></i> {{ $label }}
        <i class="fas fa-caret-down" style="margin-left: .25rem; font-size: .7rem;"></i>
    </button>

    <div x-show="open" x-transition x-cloak
         class="export-menu"
         style="display: none;">
        <a :href="window.appendFiltersToUrl('{{ $previewUrl }}')" target="_blank" rel="noopener" class="export-menu-item" @click="open = false">
            <i class="fas fa-eye"></i>
            <div>
                <strong>Aperçu PDF</strong>
                <small>Voir avant téléchargement (nouvel onglet)</small>
            </div>
        </a>
        <a :href="window.appendFiltersToUrl('{{ $pdfUrl }}')" class="export-menu-item" @click="open = false">
            <i class="fas fa-file-pdf"></i>
            <div>
                <strong>Télécharger PDF</strong>
                <small>Format A4 imprimable</small>
            </div>
        </a>
        <a :href="window.appendFiltersToUrl('{{ $excelUrl }}')" class="export-menu-item" @click="open = false">
            <i class="fas fa-file-excel"></i>
            <div>
                <strong>Télécharger Excel</strong>
                <small>Données brutes (.xlsx)</small>
            </div>
        </a>
        @if($emailUrl)
            <button type="button" @click="window.askEmailExport('{{ $emailUrl }}'); open = false" class="export-menu-item export-menu-item--button">
                <i class="fas fa-envelope"></i>
                <div>
                    <strong>Envoyer par email</strong>
                    <small>Recevoir le PDF dans votre boîte</small>
                </div>
            </button>
        @endif
    </div>
</div>

@once
@push('scripts')
<script>
// Helpers globaux pour construire les liens avec filtres actuels.
// La page hôte expose `window.exportFilters()` qui retourne un object {key: value}.
// Si non défini, les liens sont utilisés tels quels.
window.previewLink = function (baseUrl) { return appendFiltersToUrl(baseUrl); };
window.filteredLink = function (baseUrl) { return appendFiltersToUrl(baseUrl); };
window.appendFiltersToUrl = function (baseUrl, extra) {
    const params = new URLSearchParams();
    if (typeof window.exportFilters === 'function') {
        const filters = window.exportFilters() || {};
        for (const [k, v] of Object.entries(filters)) {
            if (v !== null && v !== undefined && v !== '') params.append(k, v);
        }
    }
    if (extra) {
        for (const [k, v] of Object.entries(extra)) {
            if (v !== null && v !== undefined && v !== '') params.append(k, v);
        }
    }
    const qs = params.toString();
    if (!qs) return baseUrl;
    return baseUrl + (baseUrl.includes('?') ? '&' : '?') + qs;
};
window.askEmailExport = function (emailUrl) {
    const to = window.prompt('Adresse email destinataire (laisser vide pour la vôtre) :', '');
    if (to === null) return;
    const url = window.appendFiltersToUrl(emailUrl, { to: to || '' });
    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json',
        },
    })
        .then(r => r.json())
        .then(d => alert(d.message || 'Email envoyé.'))
        .catch(() => alert('Erreur d\'envoi.'));
};
</script>
@endpush
@endonce

@once
@push('styles')
<style>
.export-menu {
    position: absolute; top: calc(100% + 6px); right: 0; z-index: 1100;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    box-shadow: 0 8px 30px rgba(15,23,42,.12);
    min-width: 280px; padding: .5rem;
    overflow: hidden;
}
.export-menu-item {
    display: flex; align-items: center; gap: .75rem;
    padding: .75rem .85rem; border-radius: 8px;
    text-decoration: none; color: #1e293b; cursor: pointer;
    border: none; background: none; width: 100%; text-align: left;
    font-size: .88rem; transition: background .15s ease;
}
.export-menu-item:hover { background: #f1f5f9; color: #0453cb; }
.export-menu-item i { font-size: 1.1rem; color: #64748b; flex-shrink: 0; width: 24px; text-align: center; }
.export-menu-item:hover i { color: #0453cb; }
.export-menu-item strong { display: block; font-size: .88rem; }
.export-menu-item small { display: block; font-size: .72rem; color: #64748b; margin-top: .15rem; }
.export-menu-item:hover small { color: #475569; }
[x-cloak] { display: none !important; }
</style>
@endpush
@endonce
