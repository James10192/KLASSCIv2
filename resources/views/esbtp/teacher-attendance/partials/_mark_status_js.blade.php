{{-- JS partagé report + fiche enseignant : marquage présent/absent/retard.
     Flow : clic → badge en LOADING + actions désactivées → POST → (DB à jour) →
     animation travelling-light → à la FIN de l'animation le statut change en place.
     Inclus DANS un <script> existant. Guards d'idempotence (page report ET fiche). --}}
if (typeof window.__tarStatusMeta !== 'object') {
    window.__tarStatusMeta = {
        present: { label: 'Présent',   bg: 'rgba(16,185,129,.12)', color: '#065f46' },
        late:    { label: 'En retard', bg: 'rgba(245,158,11,.14)', color: '#92400e' },
        absent:  { label: 'Absent',    bg: 'rgba(220,38,38,.12)',  color: '#b91c1c' },
    };
}
if (typeof window.__tarActionButtons !== 'function') {
    window.__tarActionButtons = function (seanceId, status) {
        let h = '';
        if (status !== 'present') h += '<button type="button" class="tar-act tar-act--ok" title="Marquer présent" onclick="markSeanceStatus(' + seanceId + ',\'present\')"><i class="fas fa-check"></i></button>';
        if (status !== 'late')    h += '<button type="button" class="tar-act tar-act--late" title="Marquer en retard" onclick="markSeanceStatus(' + seanceId + ',\'late\')"><i class="fas fa-clock"></i></button>';
        if (status !== 'absent')  h += '<button type="button" class="tar-act tar-act--no" title="Marquer absent" onclick="markSeanceStatus(' + seanceId + ',\'absent\')"><i class="fas fa-user-xmark"></i></button>';
        return h;
    };
}
if (typeof window.__tarApplyStatus !== 'function') {
    // Met à jour le statut d'une ligne EN PLACE (badge + warning + boutons).
    window.__tarApplyStatus = function (row, seanceId, status) {
        const meta = window.__tarStatusMeta[status] || { label: status, bg: 'rgba(100,116,139,.12)', color: '#475569' };
        const col = row.querySelector('.tar-seance-statut');
        const badge = col ? col.querySelector('.tar-statut-badge') : null;
        if (badge) {
            badge.textContent = meta.label;
            badge.style.background = meta.bg;
            badge.style.color = meta.color;
        }
        // Le warning « non émargé » disparaît (séance désormais émargée) ; retard conservé.
        col.querySelectorAll('.tar-warn').forEach(function (w) { w.remove(); });
        if (status === 'late' && badge) {
            const warn = document.createElement('span');
            warn.className = 'tar-warn tar-warn--late';
            warn.innerHTML = '<i class="fas fa-triangle-exclamation"></i> Retard';
            badge.insertAdjacentElement('afterend', warn);
        }
        const actions = col ? col.querySelector('.tar-seance-actions') : null;
        if (actions) {
            actions.innerHTML = window.__tarActionButtons(seanceId, status);
            actions.style.opacity = '';
            actions.style.pointerEvents = '';
        }
        row.dataset.busy = '';
    };
}
if (typeof window.triggerRowHighlight !== 'function') {
    // Sweep lumineux ; appelle onEnd à la FIN de l'animation.
    window.triggerRowHighlight = function (seanceId, status, onEnd) {
        const row = document.querySelector('.tar-seance-row[data-seance-id="' + seanceId + '"]');
        if (!row) { if (onEnd) onEnd(); return; }
        const hl = document.createElement('div');
        hl.className = 'tar-rowhl' + (status === 'absent' ? ' tar-rowhl--absent' : (status === 'late' ? ' tar-rowhl--late' : ''));
        row.appendChild(hl);
        requestAnimationFrame(() => hl.classList.add('animate'));
        hl.addEventListener('animationend', function () { hl.remove(); if (onEnd) onEnd(); });
    };
}
if (typeof window.markSeanceStatus !== 'function') {
    window.markSeanceStatus = async function (seanceId, status) {
        const row = document.querySelector('.tar-seance-row[data-seance-id="' + seanceId + '"]');
        if (!row || row.dataset.busy === '1') return;
        row.dataset.busy = '1';

        const col = row.querySelector('.tar-seance-statut');
        const badge = col ? col.querySelector('.tar-statut-badge') : null;
        const actions = col ? col.querySelector('.tar-seance-actions') : null;
        const saved = badge ? { html: badge.innerHTML, css: badge.getAttribute('style') || '' } : null;

        // 1. État LOADING : le badge courant devient un spinner, actions désactivées.
        if (badge) badge.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
        if (actions) { actions.style.opacity = '.4'; actions.style.pointerEvents = 'none'; }

        const token = document.querySelector('meta[name="csrf-token"]').content;
        const base = @json(url('esbtp/teacher-attendance/seance'));
        try {
            const res = await fetch(base + '/' + seanceId + '/update-status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ status: status, type: 'start' }),
            });
            if (!res.ok) { const b = await res.json().catch(() => ({})); throw new Error(b.message || ('Erreur ' + res.status)); }

            // 2. DB à jour → on déclenche l'animation, et à sa FIN le statut change.
            window.triggerRowHighlight(seanceId, status, function () {
                window.__tarApplyStatus(row, seanceId, status);
                const labels = { present: 'présent', late: 'en retard', absent: 'absent' };
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Enseignant marqué ' + (labels[status] || status) + '.' } }));
                // KPIs + baromètre se mettent à jour sans toucher la ligne.
                window.dispatchEvent(new CustomEvent('seance:status-updated'));
            });
        } catch (e) {
            if (badge && saved) { badge.innerHTML = saved.html; badge.setAttribute('style', saved.css); }
            if (actions) { actions.style.opacity = ''; actions.style.pointerEvents = ''; }
            row.dataset.busy = '';
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
        }
    };
}
if (typeof window.openSeanceModal !== 'function') {
    window.openSeanceModal = function (seanceId) {
        const row = document.querySelector('.tar-seance-row[data-seance-id="' + seanceId + '"]');
        const modal = document.getElementById('tarSeanceModal');
        if (!row || !modal) return;
        const d = row.dataset;
        const set = function (id, val) { const el = document.getElementById(id); if (el) el.textContent = val || '—'; };
        set('tsmMatiere', d.matiere);
        set('tsmTeacher', d.teacher);
        set('tsmClasse', d.classe);
        set('tsmDate', d.date);
        set('tsmHoraire', d.horaire);
        set('tsmDuree', d.duree);
        // Type chip
        const typeEl = document.getElementById('tsmType');
        if (typeEl) { typeEl.innerHTML = '<i class="fas ' + (d.typeIcon || '') + '"></i> ' + (d.type || ''); typeEl.setAttribute('style', d.typeStyle || ''); }
        // Statut badge
        const st = document.getElementById('tsmStatut');
        if (st) { st.textContent = d.statutLabel || '—'; st.style.background = d.statutBg || ''; st.style.color = d.statutColor || ''; }
        // Salle (cacher la ligne si vide)
        const salleRow = document.getElementById('tsmSalleRow');
        if (salleRow) { salleRow.style.display = d.salle ? 'flex' : 'none'; set('tsmSalle', d.salle); }
        // Lien page complète
        const link = document.getElementById('tsmShowLink');
        if (link) link.setAttribute('href', d.showUrl || '#');
        modal.classList.add('show');
    };
}
if (typeof window.closeSeanceModal !== 'function') {
    window.closeSeanceModal = function () {
        const modal = document.getElementById('tarSeanceModal');
        if (modal) modal.classList.remove('show');
    };
}
if (!window.__tarModalEsc) {
    window.__tarModalEsc = true;
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.closeSeanceModal(); });
}