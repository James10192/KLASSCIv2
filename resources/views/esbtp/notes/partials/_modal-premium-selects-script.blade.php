{{-- Code exécuté au sein du script notes/index ; ne contient aucune balise <script>. --}}
// Fermer les menus Alpine, notamment quand ils ont été portés sous <body>.
$('#classSelectionModal').on('hide.bs.modal', function() {
    if (!window.Alpine?.$data) return;
    document.querySelectorAll('#classSelectionModal .au-select').forEach(function(el) {
        const selectData = window.Alpine.$data(el);
        if (selectData?.open && typeof selectData.fermer === 'function') selectData.fermer();
    });
});

// Un .trigger('change') jQuery ne prévient pas addEventListener('change') d'Alpine.
// Les changements programmatiques doivent atteindre EN MÊME TEMPS la grille BTS
// et la valeur affichée par <x-au-select>, sans double événement ni reload.
function nmSetNativeSelectValue(picker, value) {
    const native = typeof picker === 'string' ? document.querySelector(picker)
        : (picker && picker.jquery ? picker[0] : picker);
    if (!native) return;
    native.value = value;
    native.dispatchEvent(new Event('change', { bubbles: true }));
}

