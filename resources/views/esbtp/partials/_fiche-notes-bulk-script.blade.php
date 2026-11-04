  // Edition groupée sur UNE inscription de la fiche. Les écritures restent
  // dans SaisieGroupeeDeNotes (barème, cohorte, permissions et verrous serveur).
  function fngBulkFields(row) {
    return {
      source: row.querySelector('[data-fng-note]'),
      score: row.querySelector('[data-fng-inline-note]'),
      absence: row.querySelector('[data-fng-inline-absent]'),
      comment: row.querySelector('[data-fng-inline-comment]'),
      error: row.querySelector('[data-fng-inline-error]'),
    };
  }
  function fngBulkValue(input) {
    const value = input.value.trim().replace(',', '.');
    return value === '' ? null : Number(value);
  }
  function fngBulkBefore(source) {
    return source.dataset.existing === '1' ? {
      note: source.dataset.absent === '1' ? null : Number(source.dataset.note),
      absent: source.dataset.absent === '1',
      validee: source.dataset.submitted === '1',
    } : null;
  }
  function fngBulkDirty(row) {
    const f = fngBulkFields(row);
    if (!f.source || !f.score || !f.absence || !f.comment) return false;
    const before = fngBulkBefore(f.source);
    const absent = f.absence.checked;
    const value = absent ? null : fngBulkValue(f.score);
    return (before === null ? (value !== null || absent) :
      (before.absent !== absent || (!absent && before.note !== value))) ||
      f.comment.value !== (f.source.dataset.comment || '');
  }
  function fngBulkDirtyRows(block) {
    return [...block.querySelectorAll('[data-fng-eval-row]')].filter(fngBulkDirty);
  }
  function fngBulkError(row, message) {
    const el = row.querySelector('[data-fng-inline-error]');
    if (!el) return;
    el.textContent = message || '';
    el.hidden = !message;
  }
  function fngBulkUpdate(block, text) {
    const rows = fngBulkDirtyRows(block);
    const count = block.querySelector('[data-fng-bulk-count]');
    const button = block.querySelector('[data-fng-bulk-save]');
    if (count) count.textContent = rows.length + ' modification' + (rows.length > 1 ? 's' : '') + ' en attente';
    if (button) button.disabled = rows.length === 0 || block.dataset.fngBulkBusy === '1';
    const info = block.querySelector('[data-fng-bulk-feedback]');
    if (info && text !== undefined) info.textContent = text;
    block.querySelectorAll('[data-fng-eval-row]').forEach(row => {
      row.classList.toggle('fng-row-dirty', fngBulkDirty(row));
    });
  }
  function fngBulkStop(block, refreshServer) {
    block.dataset.fngBulkActive = '0';
    const bar = block.querySelector('[data-fng-bulk-bar]');
    const trigger = block.querySelector('[data-fng-bulk-start]');
    if (bar) bar.hidden = true;
    if (trigger) { trigger.hidden = false; trigger.focus({preventScroll:true}); }
    block.querySelectorAll('[data-fng-inline-editor]').forEach(el => { el.hidden = true; });
    block.querySelectorAll('[data-fng-display-score]').forEach(el => { el.hidden = false; });
    if (refreshServer) reload(); // panneau AJAX seulement, jamais de navigation complète
  }
  function fngBulkStart(block) {
    const active = panel.querySelector('[data-fng-bulk-active="1"]');
    if (active && active !== block) {
      if (fngBulkDirtyRows(active).length) {
        fngBulkUpdate(active, 'Enregistrez ou annulez les modifications de cette inscription avant de passer à une autre.');
        return;
      }
      fngBulkStop(active, false);
    }
    block.dataset.fngBulkActive = '1';
    block.dataset.fngBulkSaved = '0';
    const bar = block.querySelector('[data-fng-bulk-bar]');
    const trigger = block.querySelector('[data-fng-bulk-start]');
    if (bar) bar.hidden = false;
    if (trigger) trigger.hidden = true;
    block.querySelectorAll('[data-fng-inline-editor]').forEach(el => { el.hidden = false; });
    block.querySelectorAll('[data-fng-display-score]').forEach(el => { el.hidden = true; });
    fngBulkUpdate(block, 'Modifiez les notes, puis enregistrez une seule fois. Les notes ne seront pas validées officiellement.');
    block.querySelector('[data-fng-inline-note]:not([disabled])')?.focus({preventScroll:true});
  }
  function fngBulkCollect(block, rows) {
    const notes = [], expected = {}, byKey = new Map();
    let invalid = null;
    for (const row of rows) {
      const f = fngBulkFields(row);
      const absent = f.absence.checked;
      const value = absent ? null : fngBulkValue(f.score);
      const bareme = Number(f.source.dataset.bareme);
      let error = '';
      if (!absent && (value === null || !Number.isFinite(value))) error = 'Saisissez une note ou cochez Absent.';
      else if (!absent && (value < 0 || value > bareme || !/^\d+(?:[.,]\d{1,2})?$/.test(f.score.value.trim()))) {
        error = 'La note doit être comprise entre 0 et ' + bareme + ' (2 décimales maximum).';
      }
      if (f.comment.value.length > 500) error = 'Commentaire limité à 500 caractères.';
      fngBulkError(row, error);
      if (error) { invalid ||= f.score; continue; }
      const evalId = f.source.dataset.evaluation;
      const studentId = block.dataset.student;
      const key = studentId + '_' + evalId;
      notes.push({etudiant_id:Number(studentId),evaluation_id:Number(evalId),note:value,is_absent:absent,commentaire:f.comment.value});
      expected[key] = fngBulkBefore(f.source);
      byKey.set(key, row);
    }
    return {notes, expected, byKey, invalid};
  }
  function fngBulkLockRows(rows, locked) {
    rows.forEach(row => {
      const f = fngBulkFields(row);
      f.absence.disabled = locked;
      f.comment.disabled = locked;
      f.score.disabled = locked || f.absence.checked;
    });
  }
  function fngBulkAcknowledge(block, data, notes, byKey) {
    const refused = new Map(data.refused.map(item => [item.etudiant_id + '_' + item.evaluation_id, item.raison]));
    const successes = [...byKey].filter(([key]) => !refused.has(key));
    if (Number(data.total) !== notes.length || Number(data.saved) !== successes.length) {
      throw new Error('Confirmation incomplète du serveur. Actualisez les notes avant de recommencer.');
    }
    successes.forEach(([key,row]) => {
      const f = fngBulkFields(row);
      const absent = f.absence.checked;
      const note = absent ? null : fngBulkValue(f.score);
      f.source.dataset.note = absent ? '0' : String(note);
      f.source.dataset.absent = absent ? '1' : '0';
      f.source.dataset.comment = f.comment.value;
      f.source.dataset.existing = '1';
      const display = row.querySelector('[data-fng-display-score]');
      if (display) {
        display.textContent = absent ? 'ABS' : note.toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2});
        display.classList.remove('fng-muted');
        display.classList.add('fng-score');
      }
      const status = row.querySelector('[data-fng-note-status-label]');
      if (status && f.source.dataset.submitted !== '1') {
        status.textContent = 'Brouillon (non validée)';
        status.classList.remove('fng-status-missing');
        status.classList.add('fng-status-draft');
      }
      fngBulkError(row, '');
    });
    data.refused.forEach(item => {
      const row = byKey.get(item.etudiant_id + '_' + item.evaluation_id);
      if (row) fngBulkError(row, item.raison || 'Refusée par le serveur.');
    });
    if (successes.length) {
      block.dataset.fngBulkSaved = '1';
      document.dispatchEvent(new CustomEvent('klassci:notes-updated', {
        detail:{etudiant_id:Number(block.dataset.student),inscription_id:Number(block.dataset.fngInscription),count:successes.length},
      }));
    }
    fngBulkUpdate(block, data.refused.length
      ? successes.length + ' enregistrée(s), ' + data.refused.length + ' refusée(s). Corrigez les lignes signalées ou actualisez les notes.'
      : successes.length + ' note(s) enregistrée(s). La validation officielle reste distincte.');
  }
  async function fngBulkSave(block) {
    if (block.dataset.fngBulkBusy === '1') return;
    const rows = fngBulkDirtyRows(block);
    if (!rows.length) return;
    const limit = Number(block.dataset.fngBulkLimit);
    if (rows.length > limit) {
      fngBulkUpdate(block, 'Trop de notes pour une seule sauvegarde : maximum ' + limit + '.');
      return;
    }
    const {notes,expected,byKey,invalid} = fngBulkCollect(block, rows);
    if (invalid) {
      fngBulkUpdate(block, 'Corrigez les champs signalés avant de sauvegarder. Aucune modification n’a été transmise.');
      invalid.focus({preventScroll:true});
      return;
    }
    block.dataset.fngBulkBusy = '1';
    fngBulkLockRows(rows, true); // la réponse ne doit acquitter que les valeurs effectivement envoyées
    fngBulkUpdate(block, 'Enregistrement en cours…');
    try {
      const response = await fetch(block.dataset.saveBulk, {
        method:'POST',credentials:'same-origin',
        headers:{'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':token},
        body:JSON.stringify({notes,expected,submit_final:false}),
      });
      const data = await response.json().catch(() => ({}));
      // Un lot partiellement enregistré répond success=false avec un HTTP 200.
      if (!response.ok || !Array.isArray(data.refused)) {
        const error = Object.values(data.errors || {}).flat().find(Boolean);
        throw new Error(error || data.message || 'Sauvegarde non confirmée. Actualisez les notes avant de réessayer.');
      }
      fngBulkAcknowledge(block, data, notes, byKey);
    }catch(error){
      // Réponse perdue ≠ aucune écriture : ne pas rejouer automatiquement.
      fngBulkUpdate(block, error.message + ' Les corrections locales restent affichées.');
    }finally{
      fngBulkLockRows(rows, false);
      block.dataset.fngBulkBusy = '0';
      fngBulkUpdate(block);
    }
  }
  panel.addEventListener('input', e => {
    const row = e.target.closest('[data-fng-eval-row]');
    const block = row?.closest('[data-fng-bulk-active="1"]');
    if (!block || !e.target.matches('[data-fng-inline-note],[data-fng-inline-comment]')) return;
    fngBulkError(row, '');
    block.querySelector('[data-fng-bulk-cancel]')?.removeAttribute('data-confirm');
    fngBulkUpdate(block);
  });
  panel.addEventListener('change', e => {
    if (!e.target.matches('[data-fng-inline-absent]')) return;
    const row=e.target.closest('[data-fng-eval-row]');
    const block=row?.closest('[data-fng-bulk-active="1"]');
    if (!block) return;
    const f = fngBulkFields(row);
    f.score.disabled = f.absence.checked;
    fngBulkError(row, '');
    fngBulkUpdate(block);
  });
  // Capture: aucune action destructive concurrente ne doit recharger le panneau
  // pendant que des modifications de notes sont en attente.
  panel.addEventListener('click', e => {
    const button = e.target.closest('button');
    if (!button || !panel.contains(button)) return;
    const block = button.closest('[data-fng-inscription]');
    if (!block) return;
    if (button.matches('[data-fng-bulk-start],[data-fng-bulk-save],[data-fng-bulk-cancel]')) {
      e.preventDefault(); e.stopImmediatePropagation();
      if (button.hasAttribute('data-fng-bulk-start')) fngBulkStart(block);
      else if (button.hasAttribute('data-fng-bulk-save')) fngBulkSave(block);
      else if (block.dataset.fngBulkBusy !== '1') {
        const dirty = fngBulkDirtyRows(block).length;
        if (dirty && button.dataset.confirm !== '1') {
          button.dataset.confirm = '1';
          button.textContent = 'Confirmer l’abandon';
          fngBulkUpdate(block, 'Cliquez à nouveau pour abandonner les modifications non enregistrées.');
        } else fngBulkStop(block, block.dataset.fngBulkSaved === '1');
      }
      return;
    }
    const active = block.dataset.fngBulkActive === '1';
    if (active && button.matches('[data-fng-note],[data-fng-publish],[data-fng-edit-eval],[data-fng-new-eval],[data-fng-delete]')) {
      e.preventDefault(); e.stopImmediatePropagation();
      fngBulkUpdate(block, 'Terminez la saisie multiple avant cette autre action.');
    }
  }, true);
  window.addEventListener('beforeunload', e => {
    const unsaved = [...panel.querySelectorAll('[data-fng-bulk-active="1"]')].some(block => fngBulkDirtyRows(block).length > 0);
    if (unsaved) { e.preventDefault(); e.returnValue = ''; }
  });
