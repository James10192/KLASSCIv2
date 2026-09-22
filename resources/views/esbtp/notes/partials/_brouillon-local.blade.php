{{-- Brouillon local des notes (localStorage) et suivi des notes refusées.
     JavaScript pur, inclus DANS le script de notes/index : il partage ses
     variables (currentClassId, notesData, NM…). Aucun script ni style ici. --}}
// ── 3. localStorage autosave (anti-perte) ───────────────────────────────
function nmDraftKey() {
    if (!currentClassId || !currentMatiereId) return null;
    // L'état, pas le <select> : pendant son événement « change », le select
    // porte déjà la nouvelle période alors que la grille est encore l'ancienne.
    const periode = currentPeriodeFilter || 'all';
    return `nm_notes_draft_${currentClassId}_${currentMatiereId}_${periode}`;
}
function nmCollectDraftNotes() {
    // Ne collecter QUE les notes dirty (modifiées + pas encore confirmées serveur).
    // Sans ce filtre, l'autosave ré-écrit en localStorage TOUS les inputs visibles
    // (y compris ceux dont la valeur vient juste d'être restaurée puis sauvée),
    // ce qui ressuscite la bannière "Brouillon non sauvegardé" indéfiniment.
    const out = {};
    if (window.nmDirtyNotes.size === 0) return out;
    window.nmDirtyNotes.forEach(function(key) {
        const sep = key.indexOf('-');
        if (sep < 0) return;
        const sid = key.substring(0, sep);
        const eid = key.substring(sep + 1);
        const $i = $(`.note-input[data-student-id="${sid}"][data-eval-id="${eid}"]`);
        if ($i.length === 0) return;
        const val = $i.val();
        const isAbsent = $(`#absent-${sid}-${eid}`).is(':checked');
        if ((val !== '' && val !== null && val !== undefined) || isAbsent) {
            if (!out[eid]) out[eid] = {};
            out[eid][sid] = { note: isAbsent ? 0 : val, isAbsent: !!isAbsent };
        }
    });
    return out;
}
function nmAutosaveDraft(expectedKey) {
    const key = nmDraftKey();
    if (!key) return;
    // Programmée pour une classe/matière/période que l'on a quittée : la
    // grille visible est déjà celle d'un autre contexte, et « aucune note
    // modifiée » y effacerait le brouillon de ce nouveau contexte.
    if (expectedKey && expectedKey !== key) return;
    const notes = nmCollectDraftNotes();
    if (Object.keys(notes).length === 0) {
        // Aucune note dirty → purger le draft ET masquer la bannière si visible.
        try { localStorage.removeItem(key); } catch (e) { /* quota */ }
        nmHideDraftBanner();
        return;
    }
    try {
        localStorage.setItem(key, JSON.stringify({
            savedAt: Date.now(),
            notes: notes,
            classLabel: currentClassname,
            matiereLabel: currentMatiereName,
        }));
    } catch (e) {
        // localStorage plein ou désactivé : silencieux
        console.warn('NM autosave failed:', e);
    }
}
function nmScheduleAutosave() {
    if (NM.autosaveDebounceTimer) clearTimeout(NM.autosaveDebounceTimer);
    const key = nmDraftKey();
    NM.autosaveDebounceTimer = setTimeout(function () {
        NM.autosaveDebounceTimer = null;
        nmAutosaveDraft(key);
    }, NM.autosaveDebounceMs);
}
// Écrit tout de suite le brouillon en attente, AVANT de quitter le contexte
// courant : les dernières frappes ne sont pas perdues.
function nmFlushAutosave() {
    if (!NM.autosaveDebounceTimer) return;
    clearTimeout(NM.autosaveDebounceTimer);
    NM.autosaveDebounceTimer = null;
    nmAutosaveDraft();
}
function nmPurgeOldDrafts() {
    try {
        const now = Date.now();
        const keys = [];
        for (let i = 0; i < localStorage.length; i++) {
            const k = localStorage.key(i);
            if (k && k.startsWith('nm_notes_draft_')) keys.push(k);
        }
        keys.forEach(k => {
            try {
                const obj = JSON.parse(localStorage.getItem(k) || '{}');
                if (!obj.savedAt || (now - obj.savedAt) > NM.draftTtlMs) {
                    localStorage.removeItem(k);
                }
            } catch (e) { localStorage.removeItem(k); }
        });
    } catch (e) { /* ignore */ }
}
function nmRelativeTime(timestamp) {
    const diff = Math.max(0, Date.now() - timestamp);
    const sec = Math.floor(diff / 1000);
    if (sec < 60) return 'quelques secondes';
    const min = Math.floor(sec / 60);
    if (min < 60) return `${min} min`;
    const h = Math.floor(min / 60);
    if (h < 24) return `${h} h`;
    const d = Math.floor(h / 24);
    return `${d} j`;
}
function nmCheckDraftBanner() {
    const key = nmDraftKey();
    if (!key) return;
    const banner = document.getElementById('nm-restore-banner');
    if (!banner) return;
    let raw;
    try { raw = localStorage.getItem(key); } catch (e) { return; }
    if (!raw) { banner.style.display = 'none'; return; }
    let obj;
    try { obj = JSON.parse(raw); } catch (e) { localStorage.removeItem(key); return; }
    if (!obj || !obj.notes) { banner.style.display = 'none'; return; }

    let count = 0;
    Object.values(obj.notes).forEach(byStud => count += Object.keys(byStud).length);
    if (count === 0) { banner.style.display = 'none'; return; }

    document.getElementById('nm-restore-time').textContent = nmRelativeTime(obj.savedAt || Date.now());
    document.getElementById('nm-restore-count').textContent = count;
    banner.style.display = 'flex';
}
function nmHideDraftBanner() {
    const banner = document.getElementById('nm-restore-banner');
    if (banner) banner.style.display = 'none';
}
function nmRestoreFromDraft() {
    const key = nmDraftKey();
    if (!key) return;
    let obj;
    try { obj = JSON.parse(localStorage.getItem(key) || '{}'); } catch (e) { return; }
    if (!obj || !obj.notes) return;

    let restored = 0;
    Object.entries(obj.notes).forEach(([eid, byStud]) => {
        Object.entries(byStud).forEach(([sid, payload]) => {
            const $input = $(`.note-input[data-student-id="${sid}"][data-eval-id="${eid}"]`);
            if ($input.length === 0) return;

            if (payload.isAbsent) {
                const $checkbox = $(`#absent-${sid}-${eid}`);
                $checkbox.prop('checked', true);
                $input.val('0').prop('disabled', true);
                if (typeof toggleAbsence === 'function') {
                    // ne pas re-déclencher AJAX si déjà absent
                }
                saveNote(sid, eid, 0);  // persist serveur
            } else {
                $input.val(payload.note);
                saveNote(sid, eid, payload.note);
            }
            restored++;
        });
    });
    nmHideDraftBanner();
    nmShowToast('success', `${restored} note(s) restaurée(s) depuis le brouillon local.`);
    try { localStorage.removeItem(key); } catch (e) { /* ignore */ }
}
function nmDiscardDraft() {
    const key = nmDraftKey();
    if (!key) return;
    try { localStorage.removeItem(key); } catch (e) { /* ignore */ }
    nmHideDraftBanner();
    nmShowToast('info', 'Brouillon local ignoré.');
}

$(document).on('click', '#nm-restore-btn', nmRestoreFromDraft);
$(document).on('click', '#nm-restore-discard', nmDiscardDraft);

// Hook autosave + dirty flag sur tous les inputs notes.
// On marque la note dirty AVANT que saveNote()/AJAX soit appelé : l'autosave
// suivant la persistera localement le temps que le serveur confirme.
$(document).on('input change', '.note-input, .absence-checkbox', function() {
    const $el = $(this);
    let sid = $el.data('student-id');
    let eid = $el.data('eval-id');
    if (!sid || !eid) {
        // Cas checkbox absence : on extrait depuis l'id (absent-${sid}-${eid})
        const id = $el.attr('id') || '';
        const m = id.match(/^absent-(\d+)-(\d+)$/);
        if (m) { sid = m[1]; eid = m[2]; }
    }
    if (sid && eid) nmMarkDirty(sid, eid);
    window.nmHasUnsavedChanges = true;
    nmScheduleAutosave();
});

// Quand un save serveur réussit, la note est confirmée : la marquer "clean"
// pour qu'elle ne soit plus collectée par l'autosave. Si plus aucune note
// dirty → le prochain nmAutosaveDraft purgera le draft + cachera la bannière.
$(document).ajaxSuccess(function(_event, _jqxhr, settings) {
    if (typeof settings.url === 'string' && /(save-ajax|save-ajax-bulk)/.test(settings.url)) {
        // Parser le payload pour récupérer les paires (etudiant_id, evaluation_id)
        // à marquer comme clean. Le payload peut être :
        //   - save-ajax : `etudiant_id=X&evaluation_id=Y` (1 paire)
        //   - save-ajax-bulk : `notes[0][etudiant_id]=X&notes[0][evaluation_id]=Y&notes[1]...`
        // Une paire refusée par le serveur reste un brouillon : la marquer
        // propre effacerait la saisie au prochain autosave.
        const reponse = (_jqxhr && _jqxhr.responseJSON) || {};
        const refusees = new Set((reponse.refused || [])
            .map(function(r) { return nmDirtyKey(r.etudiant_id, r.evaluation_id); }));
        // Échec partiel sans liste des refus : rien n'est marqué propre.
        const refusInconnus = reponse.success === false && !Array.isArray(reponse.refused);
        const marquerPropre = function(sid, eid) {
            if (!refusInconnus && !refusees.has(nmDirtyKey(sid, eid))) nmMarkClean(sid, eid);
        };
        const data = settings.data || '';
        if (typeof data === 'string' && data.length) {
            const params = new URLSearchParams(data);
            // Cas simple
            const sid = params.get('etudiant_id');
            const eid = params.get('evaluation_id');
            if (sid && eid) marquerPropre(sid, eid);
            // Cas bulk : reconstituer les paires via notes[i][etudiant_id] / notes[i][evaluation_id]
            const bulkSids = {}, bulkEids = {};
            for (const [key, val] of params.entries()) {
                let m = key.match(/^notes\[(\d+)\]\[etudiant_id\]$/);
                if (m) { bulkSids[m[1]] = val; continue; }
                m = key.match(/^notes\[(\d+)\]\[evaluation_id\]$/);
                if (m) { bulkEids[m[1]] = val; continue; }
            }
            Object.keys(bulkSids).forEach(function(idx) {
                if (bulkEids[idx]) marquerPropre(bulkSids[idx], bulkEids[idx]);
            });
        }
        if (NM.pendingSaves === 0 && window.nmDirtyNotes.size === 0) {
            window.nmHasUnsavedChanges = false;
        }
        nmScheduleAutosave();
    }
});

// Notes refusées par une validation partielle. La grille est relue depuis le
// serveur juste après : sans ceci, la saisie refusée y était remplacée par
// l'ancienne valeur, sans brouillon ni trace. On la remet, en rouge, avec la
// raison du refus, et elle repartira au prochain « Valider les notes ».
window.nmRefusEnAttente = null;
function nmMemoriserRefus(notesPayload, refused) {
    const saisies = {};
    notesPayload.forEach(function(e) { saisies[nmDirtyKey(e.etudiant_id, e.evaluation_id)] = e; });
    const liste = (refused || []).map(function(r) {
        const saisie = saisies[nmDirtyKey(r.etudiant_id, r.evaluation_id)] || {};
        return { sid: String(r.etudiant_id), eid: String(r.evaluation_id), note: saisie.note, absent: saisie.is_absent === 'on', raison: r.raison };
    });
    window.nmRefusEnAttente = liste.length ? { classId: currentClassId, liste: liste } : null;
}
window.addEventListener('nm:grid-rendered', function() {
    const attente = window.nmRefusEnAttente;
    window.nmRefusEnAttente = null;
    if (!attente || attente.classId !== currentClassId) return;
    attente.liste.forEach(function(r) {
        const $input = $(`.note-input[data-student-id="${r.sid}"][data-eval-id="${r.eid}"]`);
        if (!$input.length) return;
        $input.addClass('nm-note-refused').attr('title', 'Refusée : ' + r.raison);
        if ($input.hasClass('nm-note-locked')) return;
        $(`#absent-${r.sid}-${r.eid}`).prop('checked', !!r.absent);
        $input.val(r.absent ? '0' : r.note).prop('disabled', !!r.absent);
        if (!notesData[r.sid]) notesData[r.sid] = {};
        notesData[r.sid][r.eid] = r.absent ? 0 : r.note;
        notesData[r.sid][r.eid + '_absent'] = !!r.absent;
        nmMarkDirty(r.sid, r.eid);
        calculateStudentAverage(r.sid);
    });
    calculateClassAverages();
    window.nmHasUnsavedChanges = true;
    nmScheduleAutosave();
});
$(document).on('input change', '.note-input.nm-note-refused', function() {
    $(this).removeClass('nm-note-refused').removeAttr('title');
});
