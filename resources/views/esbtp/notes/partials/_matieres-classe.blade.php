{{-- Liste des matières du modal, rechargée à chaque classe choisie.
     JavaScript pur, inclus DANS le script de notes/index : il partage ses
     variables (currentClassId…). Aucun script ni style ici. --}}
// La page arrive avec tout le catalogue BTS ; la classe choisie le réduit à
// sa maquette (plus les matières déjà évaluées chez elle). Tant que la liste
// n'est pas arrivée, le catalogue reste en place : rien n'est bloqué.
let nmMatieresSeq = 0;
let nmMatieresPret = $.Deferred().resolve().promise();
function nmChargerMatieres(classId) {
    const seq = ++nmMatieresSeq;
    const $select = $('#matiereSelect');
    const $aide = $('#nmMatiereAide');
    $select.prop('disabled', true);
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
        $aide.text('Liste des matières de la classe indisponible : toutes les matières restent proposées.').show();
    }).always(function() {
        if (seq === nmMatieresSeq) $select.prop('disabled', false);
    });
    return nmMatieresPret;
}
