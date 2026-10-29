// ── UE propre à un parcours ──
// La case n'existe qu'à la création : une UE garde ensuite sa maquette.
// En création, la case demande parcours et semestre. En modification, elle
// rend propre une UE existante à son parcours actuel (déduit côté serveur).
let ueProprEdition = false;
function ueProprePreparer(creation, parcoursFiltre) {
    const groupe = document.getElementById('ue_propre_group');
    const coche = document.getElementById('ue_propre');
    ueProprEdition = !creation;
    // En modification, affichée seulement une fois l'UE chargée, si elle
    // n'est pas déjà propre à un parcours.
    groupe.style.display = creation ? '' : 'none';
    document.getElementById('ue_propre_info').style.display = 'none';
    coche.checked = false;
    ueProprePoserValeur('ue_propre_parcours', parcoursFiltre);
    ueProprePoserValeur('ue_propre_semestre', '');
    ueProprebasculer();
}
function ueProprePoserValeur(id, valeur) {
    const natif = document.getElementById(id);
    if (!natif) return;
    natif.value = valeur;
    natif.dispatchEvent(new Event('change', { bubbles: true }));
}
function ueProprebasculer() {
    const coche = document.getElementById('ue_propre').checked;
    const actif = coche && !ueProprEdition;
    const champs = document.getElementById('ue_propre_champs');
    champs.style.display = actif ? '' : 'none';
    document.getElementById('ue_propre_edition').style.display = coche && ueProprEdition ? 'flex' : 'none';
    // Sans nom, parcours et semestre ne partent pas : une UE ordinaire se
    // rattache par le bouton « Lier à des parcours », comme avant. On retire le
    // nom plutôt que de désactiver : la liste premium resterait grisée.
    champs.querySelectorAll('select').forEach(el => {
        el.dataset.nom = el.dataset.nom || el.name;
        el.name = actif ? el.dataset.nom : '';
    });
}
document.getElementById('ue_propre').addEventListener('change', () => {
    // Le refus « code déjà pris » ne vaut plus une fois la case cochée.
    document.getElementById('ue_errors').classList.add('d-none');
    ueProprebasculer();
});
