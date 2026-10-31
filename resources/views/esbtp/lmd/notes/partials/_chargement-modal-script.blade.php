{{-- Code exécuté dans le script principal des notes LMD. Ne pas ajouter de balise <script> ici. --}}
// ══ Open modal for a class ══
async function openNotesModal(classeId, classeName) {
    const requestSeq = ++lmdClasseRequestSeq;
    ++lmdEcueRequestSeq;
    const academicYearId = lmdAcademicYearId;
    currentClasseId = classeId;
    document.getElementById('ln-secondary-panel')?.removeAttribute('open');
    currentClasseData = null;
    lmdMasquerExamensAnonymes();
    currentMatiereId = null;
    currentMatiereName = '';
    evaluationsData = {};
    notesData = {};

    // Reset UI
    document.getElementById('notesModalTitle').textContent = classeName;
    document.getElementById('notesModalSubtitle').textContent = 'Chargement...';
    lmdAfficherChargementNotes();
    document.getElementById('notesGridWrap').style.display = 'none';
    document.getElementById('notesEmpty').style.display = 'none';
    document.getElementById('autosaveInfo').style.display = 'none';
    document.getElementById('createEvalBtn').style.display = 'none';
    document.getElementById('saveAllNotesBtn').style.display = 'none';
    document.getElementById('validateNotesBtn').style.display = 'none';
    document.getElementById('nkpi_etudiants').textContent = '—';
    document.getElementById('nkpi_evals').textContent = '—';
    document.getElementById('nkpi_matieres').textContent = '—';

    // Reset selects
    const ueSelect = document.getElementById('ueSelect');
    const ecueSelect = document.getElementById('ecueSelect');
    lmdSetNativeSelectDisabled('ueSelect', true);
    ueSelect.innerHTML = '<option value="">— Choisir une UE —</option>';
    ecueSelect.innerHTML = '<option value="">— Choisir un ECUE —</option>';
    lmdSetNativeSelectDisabled('ecueSelect', true);
    ueSelect.dispatchEvent(new Event('change', { bubbles: false }));
    ecueSelect.dispatchEvent(new Event('change', { bubbles: false }));

    notesModal.show();
    chargerRequalification(classeId);

    try {
        const resp = await fetch('/esbtp/lmd/notes/classe/' + classeId + '/data?annee_universitaire_id=' + encodeURIComponent(academicYearId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!resp.ok) throw new Error('Chargement des données de classe : HTTP ' + resp.status);
        const data = await resp.json();
        if (requestSeq !== lmdClasseRequestSeq || String(currentClasseId) !== String(classeId)
            || String(lmdAcademicYearId) !== String(academicYearId)) return;
        currentClasseData = data;
        lmdSetNativeSelectDisabled('ueSelect', false);

        // Store class semestres for dynamic period options
        classeSemestres = data.classe.semestres || [1, 2];
        if (!document.getElementById('notesModalTitle').textContent) {
            document.getElementById('notesModalTitle').textContent = data.classe.name || '';
        }
        lmdSuiviContexte(classeSemestres[0]);

        // Update hero
        const sub = [data.classe.filiere, data.classe.niveau].filter(Boolean).join(' · ');
        document.getElementById('notesModalSubtitle').textContent = sub || 'Sélectionnez une UE puis un ECUE';

        // KPIs
        document.getElementById('nkpi_etudiants').textContent = data.etudiants.length;
        document.getElementById('nkpi_evals').textContent = data.evaluations.length;
        document.getElementById('nkpi_matieres').textContent = data.matieres.length;

        // Build dynamic period filter options
        const periodeFilter = document.getElementById('periodeFilter');
        periodeFilter.innerHTML = '<option value="all">Toutes</option>';
        classeSemestres.forEach(s => {
            const opt = document.createElement('option');
            opt.value = String(s);
            opt.textContent = 'Semestre ' + s;
            periodeFilter.appendChild(opt);
        });

        // Build UE list from matières (group by ue_code)
        const ueMap = {};
        data.matieres.forEach(m => {
            const key = m.ue_code || 'SANS_UE';
            if (!ueMap[key]) ueMap[key] = { code: m.ue_code, name: m.ue_name, ecues: [] };
            ueMap[key].ecues.push(m);
        });

        Object.values(ueMap).forEach(ue => {
            const opt = document.createElement('option');
            opt.value = ue.code || '';
            opt.textContent = (ue.code ? ue.code + ' — ' : '') + (ue.name || 'Sans UE');
            ueSelect.appendChild(opt);
        });

        ueSelect.dispatchEvent(new Event('change', { bubbles: false }));
        periodeFilter.dispatchEvent(new Event('change', { bubbles: false }));
        currentClasseData._ueMap = ueMap;
        document.getElementById('notesLoading').style.display = 'none';

        // Si aucune UE liée → message explicatif
        if (data.matieres.length === 0) {
            document.getElementById('notesEmpty').innerHTML =
                '<i class="fas fa-exclamation-triangle" style="color:#d97706; font-size:2rem; opacity:.7; display:block; margin-bottom:.75rem;"></i>' +
                '<strong style="color:#1e293b;">Aucune UE liée à cette classe</strong><br>' +
                (data.classe.parcours_id
                    ? '<span style="font-size:.84rem;">Le parcours de cette classe n\'a aucune UE pour ses semestres.</span>' +
                      '<div style="margin-top:.85rem;"><a class="ln-modal-fbtn ln-modal-fbtn--action" style="display:inline-flex;text-decoration:none;" href="' + data.classe.ue_url + '"><i class="fas fa-link"></i> Lier des UE à ce parcours</a></div>'
                    : '<span style="font-size:.84rem;">Cette classe n\'est rattachée à aucun parcours.</span>' +
                      '<div style="margin-top:.85rem;"><a class="ln-modal-fbtn ln-modal-fbtn--action" style="display:inline-flex;text-decoration:none;" href="' + data.classe.edit_url + '"><i class="fas fa-route"></i> Rattacher la classe à son parcours</a></div>');
        } else {
            document.getElementById('notesEmpty').innerHTML =
                '<i class="fas fa-hand-pointer"></i>Sélectionnez une UE puis un ECUE pour afficher la grille de notes.';
        }
        document.getElementById('notesEmpty').style.display = 'block';

    } catch (err) {
        if (requestSeq !== lmdClasseRequestSeq || String(currentClasseId) !== String(classeId)
            || String(lmdAcademicYearId) !== String(academicYearId)) return;
        console.error('Error loading classe data:', err);
        document.getElementById('notesLoading').innerHTML =
            '<i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>' +
            '<div style="margin-top:.75rem; color:#dc2626;">Erreur de chargement.</div>';
    }
}

