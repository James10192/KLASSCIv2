{{-- Fenêtre « Lier à des parcours » : une ligne par parcours (semestres et rang
     de l'UE au bulletin), puis l'enregistrement. JavaScript seul, inclus dans le
     bloc de script de la page. --}}
// ── Build parcours checkbox with sem chips ──
function buildParcoursCheckbox(p, checked) {
    const activeSems = p.semestres || [];
    const hasAnySem = activeSems.length > 0;
    const semChips = [1,2,3,4,5,6,7,8,9,10].map(s => {
        const active = activeSems.includes(s);
        return `<span class="lp-sem-chip ${active ? 'lp-sem-chip--on' : ''}" data-parcours-id="${p.id}" data-sem="${s}" onclick="this.classList.toggle('lp-sem-chip--on'); var row=this.closest('.lp-row'); var cb=row.querySelector('.lp-parcours-check'); cb.checked=!!row.querySelector('.lp-sem-chip--on');">S${s}</span>`;
    }).join('');
    return `<div class="lp-row" style="display:flex; align-items:center; gap:.65rem; padding:.6rem .85rem; border-radius:10px; background:${hasAnySem ? '#eef2ff' : '#f8fafc'}; border:1.5px solid ${hasAnySem ? '#4338ca' : '#e8ecf1'}; margin-bottom:.1rem;">
        <input type="checkbox" class="lp-parcours-check" value="${p.id}" ${hasAnySem ? 'checked' : ''} style="width:1.1em; height:1.1em; accent-color:#4338ca; cursor:pointer; flex-shrink:0;">
        <div style="flex:1; min-width:0;">
            <div style="font-size:.86rem; font-weight:600; color:#1e293b;">${escHtml(p.code || '')} — ${escHtml(p.name)}</div>
            <div style="display:flex; gap:.25rem; flex-wrap:wrap; margin-top:.35rem;">${semChips}</div>
        </div>
        <label class="lp-ordre" title="Rang de cette UE sur le bulletin de ce parcours : ordre croissant, numérotez toutes les UE du semestre">
            <span>Rang au bulletin</span>
            <input type="number" min="0" max="99" class="lp-ordre-input" data-parcours-id="${p.id}" value="${p.ordre ? p.ordre : ''}" placeholder="—">
        </label>
    </div>`;
}

// ── Save Link Parcours (global, called by button onclick) ──
document.getElementById('lp_submit').addEventListener('click', async function() {
    const mgr = ueManagerData();
    const btn = this;
    btn.disabled = true;
    document.getElementById('lp_submit_text').textContent = 'Enregistrement...';

    const checkboxes = document.querySelectorAll('#lp_checkboxes .lp-parcours-check:checked');
    const parcours = Array.from(checkboxes).map(cb => {
        const semChips = document.querySelectorAll(`.lp-sem-chip--on[data-parcours-id="${cb.value}"]`);
        const ordre = document.querySelector(`.lp-ordre-input[data-parcours-id="${cb.value}"]`)?.value;
        const lien = { id: cb.value, semestres: Array.from(semChips).map(c => parseInt(c.dataset.sem)) || [1] };
        // Vide : le rang actuel est gardé.
        if (ordre !== undefined && ordre !== '') lien.ordre = parseInt(ordre, 10);
        return lien;
    }).filter(p => p.semestres.length > 0);

    try {
        const resp = await fetch(`${BASE}/${mgr._linkParcoursUeId}/sync-parcours`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ parcours })
        });
        const data = await resp.json().catch(() => ({}));
        if (resp.ok && data.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalLinkParcours')).hide();
            mgr.loadUes(mgr.pagination.current_page);
            mgr.showToast('Parcours liés');
        } else {
            // Un refus (422) se taisait : le bouton se rearmait, la fenetre
            // restait ouverte, et rien ne disait pourquoi.
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || ('Erreur ' + resp.status));
            mgr.showToast(msgs, 'error');
        }
    } catch (e) { document.getElementById('lp_error').style.display = 'block'; }
    btn.disabled = false;
    document.getElementById('lp_submit_text').textContent = 'Enregistrer';
});
