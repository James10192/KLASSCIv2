{{-- Liste des matières du modal, rechargée à chaque classe choisie.
     JavaScript pur, inclus DANS le script de notes/index : il partage ses
     variables (currentClassId…). Aucun script ni style ici. --}}
// La page arrive avec tout le catalogue BTS ; la classe choisie le réduit à
// sa maquette (plus les matières déjà évaluées chez elle). Tant que la liste
// n'est pas arrivée, le catalogue reste en place : rien n'est bloqué.
let nmMatieresSeq = 0;
let nmMatieresPret = $.Deferred().resolve().promise();
// Catalogue d'origine, gardé pour y revenir si une liste échoue : sans lui, un
// échec laisserait affichées les matières de la classe précédente.
let nmMatieresCatalogue = null;
function nmChargerMatieres(classId) {
    const seq = ++nmMatieresSeq;
    const $select = $('#matiereSelect');
    const $aide = $('#nmMatiereAide');
    // Le composant garde son <select> caché : refléter aussi le chargement
    // sur le bouton visible pour éviter un picker qui semble actif mais ne l'est pas.
    const $trigger = $select.closest('.au-select').find('.au-select-trigger');
    if (nmMatieresCatalogue === null) nmMatieresCatalogue = $select.html();
    $select.prop('disabled', true);
    $trigger.prop('disabled', true).attr('aria-busy', 'true');
    nmMatieresPret = $.ajax({
        url: '{{ route("esbtp.notes.classes.matieres", ["classe" => ":classId"]) }}'.replace(':classId', classId),
        method: 'GET',
        dataType: 'json',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    }).then(function(reponse) {
        if (seq !== nmMatieresSeq) return;
        const options = ['<option value="">-- Sélectionner une matière --</option>'];
        (reponse.matieres || []).forEach(function(m) {
            const libelle = $('<div>').text(m.name + (m.hors_maquette ? ' (hors maquette)' : '')).html();
            options.push(`<option value="${m.id}">${libelle}</option>`);
        });
        $select.html(options.join(''));
        $aide.text(reponse.source === 'catalogue'
            ? 'Aucune maquette pour cette classe : toutes les matières sont proposées.'
            : '').toggle(reponse.source === 'catalogue');
    }, function(xhr) {
        if (seq !== nmMatieresSeq || xhr.statusText === 'abort') return;
        $select.html(nmMatieresCatalogue);
        $aide.text('Liste des matières de la classe indisponible : toutes les matières restent proposées.').show();
    }).always(function() {
        if (seq !== nmMatieresSeq) return;
        $select.prop('disabled', false);
        $trigger.prop('disabled', false).removeAttr('aria-busy');
    });
    return nmMatieresPret;
}
