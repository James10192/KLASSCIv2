@can('lmd.planning.edit')
<style>
    /* Dialogue différé : clone du user-picker, sans <select> natif. */
    .lqt-backdrop {
        position: fixed; inset: 0; z-index: 10950; display: none;
        align-items: center; justify-content: center; padding: 1rem;
        background: rgba(15, 23, 42, .48); backdrop-filter: blur(3px);
    }
    .lqt-backdrop.is-open { display: flex; }
    .lqt-dialog {
        width: min(520px, 100%); max-height: min(720px, 88vh); overflow: hidden;
        background: #fff; border: 1px solid #dbe4f0; border-radius: 18px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .22); display: flex; flex-direction: column;
    }
    .lqt-head { padding: 1.15rem 1.25rem .95rem; border-bottom: 1px solid #edf2f7; display: flex; gap: .85rem; align-items: flex-start; }
    .lqt-icon { width: 40px; height: 40px; border-radius: 12px; display:flex; align-items:center; justify-content:center; flex:0 0 auto; background:rgba(4,83,203,.1); color:#0453cb; }
    .lqt-title { margin: 0; color: #172033; font-size: 1rem; font-weight: 800; }
    .lqt-context { margin-top: .15rem; color: #64748b; font-size: .78rem; line-height: 1.35; }
    .lqt-close { margin-left:auto; width:34px; height:34px; border:0; border-radius:10px; background:#f8fafc; color:#64748b; cursor:pointer; }
    .lqt-body { padding: 1rem 1.25rem 1.2rem; min-height: 0; display: flex; flex-direction: column; gap: .8rem; }
    .lqt-current { padding: .7rem .8rem; border:1px solid #dbeafe; background:#eff6ff; border-radius:11px; color:#1e3a8a; font-size:.78rem; }
    .lqt-search-wrap { position: relative; }
    .lqt-search-wrap i { position:absolute; left:.8rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:.78rem; }
    .lqt-search { width:100%; border:1px solid #d8e1ec; border-radius:11px; padding:.7rem .8rem .7rem 2.25rem; outline:0; font-size:.84rem; }
    .lqt-search:focus { border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.1); }
    .lqt-list { overflow:auto; max-height: 340px; display:grid; gap:.4rem; }
    .lqt-option { width:100%; border:1px solid #e2e8f0; background:#fff; border-radius:11px; padding:.7rem .8rem; display:flex; gap:.7rem; align-items:center; text-align:left; cursor:pointer; transition:.15s ease; }
    .lqt-option:hover, .lqt-option.is-current { border-color:#93b7ef; background:#f7fbff; }
    .lqt-avatar { width:32px; height:32px; border-radius:9px; background:#eef4ff; color:#0453cb; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:.75rem; flex:0 0 auto; }
    .lqt-name { color:#1e293b; font-weight:700; font-size:.82rem; }
    .lqt-sub { color:#94a3b8; font-size:.7rem; margin-top:.08rem; }
    .lqt-empty { padding:1rem; text-align:center; color:#64748b; font-size:.8rem; border:1px dashed #cbd5e1; border-radius:11px; }
    .lqt-foot { display:flex; justify-content:space-between; gap:.6rem; padding:.85rem 1.25rem; border-top:1px solid #edf2f7; background:#fbfdff; }
    .lqt-btn { border:1px solid #d8e1ec; border-radius:10px; padding:.55rem .8rem; background:#fff; color:#475569; font-size:.78rem; font-weight:700; cursor:pointer; }
    .lqt-btn:hover { background:#f8fafc; }
    .lqt-btn--danger { color:#b91c1c; border-color:#fecaca; }
    .lqt-loading { opacity:.58; pointer-events:none; }
    @media (max-width: 576px) {
        .lqt-backdrop { align-items:flex-end; padding:0; }
        .lqt-dialog { width:100%; max-height:88dvh; border-radius:20px 20px 0 0; }
        .lqt-list { max-height:42dvh; }
    }
</style>

<div class="lqt-backdrop" id="lmdTeacherQuickBackdrop" aria-hidden="true">
    <div class="lqt-dialog" role="dialog" aria-modal="true" aria-labelledby="lmdTeacherQuickTitle">
        <div class="lqt-head">
            <div class="lqt-icon"><i class="fas fa-chalkboard-teacher"></i></div>
            <div>
                <h3 class="lqt-title" id="lmdTeacherQuickTitle">Enseignant officiel de l’ECUE</h3>
                <div class="lqt-context" id="lmdTeacherQuickContext"></div>
            </div>
            <button type="button" class="lqt-close" id="lmdTeacherQuickClose" aria-label="Fermer"><i class="fas fa-times"></i></button>
        </div>
        <div class="lqt-body" id="lmdTeacherQuickBody">
            <div class="lqt-current" id="lmdTeacherQuickCurrent">Lecture du planning…</div>
            <div class="lqt-search-wrap">
                <i class="fas fa-search"></i>
                <input type="search" class="lqt-search" id="lmdTeacherQuickSearch" placeholder="Rechercher un enseignant…" autocomplete="off">
            </div>
            <div class="lqt-list" id="lmdTeacherQuickList"></div>
        </div>
        <div class="lqt-foot">
            <button type="button" class="lqt-btn lqt-btn--danger" id="lmdTeacherQuickClear"><i class="fas fa-user-times"></i> Désassigner</button>
            <button type="button" class="lqt-btn" id="lmdTeacherQuickCancel">Annuler</button>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.lmdTeacherQuick) return;

    const endpoints = {
        teachers: @json(route('esbtp.lmd.planning.enseignants')),
        partial: @json(route('esbtp.lmd.planning.partial')),
        update: @json(route('esbtp.lmd.planifications.update', ['ecueId' => '__ECUE__'])),
    };
    const csrf = @json(csrf_token());
    const backdrop = document.getElementById('lmdTeacherQuickBackdrop');
    const body = document.getElementById('lmdTeacherQuickBody');
    const contextEl = document.getElementById('lmdTeacherQuickContext');
    const currentEl = document.getElementById('lmdTeacherQuickCurrent');
    const search = document.getElementById('lmdTeacherQuickSearch');
    const list = document.getElementById('lmdTeacherQuickList');
    let teachers = null;
    let state = null;

    function esc(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function initials(name) {
        return String(name || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0] || '').join('').toUpperCase() || '?';
    }

    function close() {
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        state = null;
    }

    async function loadTeachers() {
        if (teachers) return teachers;
        const response = await fetch(endpoints.teachers, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error('Impossible de charger les enseignants.');
        const payload = await response.json();
        teachers = Array.isArray(payload.users) ? payload.users : [];
        return teachers;
    }

    function contextParams(context) {
        return new URLSearchParams({
            parcours_id: context.parcours_id || '',
            niveau_id: context.niveau_id || '',
            semestre: context.semestre || '',
        });
    }

    async function resolveCurrent(options) {
        if (options.currentTeacherId !== undefined) {
            return {
                id: options.currentTeacherId ? Number(options.currentTeacherId) : null,
                name: options.currentTeacherName || '',
            };
        }
        if (!options.context?.parcours_id || !options.context?.niveau_id || !options.context?.semestre) {
            return { id: null, name: '' };
        }
        try {
            const response = await fetch(endpoints.partial + '?' + contextParams(options.context).toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!response.ok) return { id: null, name: '' };
            const html = await response.text();
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const row = doc.querySelector('[data-lpe-ecue-id="' + String(options.ecueId).replace(/"/g, '') + '"]');
            if (!row) return { id: null, name: '' };
            return {
                id: row.dataset.lpeTeacherId ? Number(row.dataset.lpeTeacherId) : null,
                name: row.dataset.lpeTeacherName || '',
            };
        } catch (_) {
            return { id: null, name: '' };
        }
    }

    function render() {
        if (!state || !teachers) return;
        const needle = String(search.value || '').trim().toLocaleLowerCase('fr');
        const visible = teachers.filter(user => {
            const haystack = [user.name, user.email, user.username].filter(Boolean).join(' ').toLocaleLowerCase('fr');
            return !needle || haystack.includes(needle);
        });
        if (visible.length === 0) {
            list.innerHTML = '<div class="lqt-empty"><i class="fas fa-user-slash"></i><br>Aucun enseignant ne correspond.</div>';
            return;
        }
        list.innerHTML = visible.map(user => {
            const active = Number(state.current?.id || 0) === Number(user.id);
            return '<button type="button" class="lqt-option ' + (active ? 'is-current' : '') + '" data-teacher-id="' + Number(user.id) + '">'
                + '<span class="lqt-avatar">' + esc(initials(user.name)) + '</span>'
                + '<span><span class="lqt-name">' + esc(user.name || ('Utilisateur #' + user.id)) + '</span>'
                + '<span class="lqt-sub">' + esc(user.email || user.username || 'Enseignant KLASSCI') + '</span></span>'
                + '</button>';
        }).join('');
        list.querySelectorAll('[data-teacher-id]').forEach(button => {
            button.addEventListener('click', () => {
                const user = teachers.find(u => Number(u.id) === Number(button.dataset.teacherId));
                if (user) save(user.id, user.name || '');
            });
        });
    }

    async function save(teacherId, teacherName) {
        if (!state) return;
        body.classList.add('lqt-loading');
        try {
            const context = state.context || {};
            const response = await fetch(endpoints.update.replace('__ECUE__', encodeURIComponent(state.ecueId)), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    filiere_id: context.filiere_id || null,
                    niveau_id: context.niveau_id || null,
                    semestre: context.semestre || null,
                    annee_universitaire_id: context.annee_universitaire_id || null,
                    enseignant_principal_id: teacherId || null,
                }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.success) throw new Error(data.message || ('Erreur ' + response.status));

            const detail = {
                ecueId: Number(state.ecueId),
                teacherId: teacherId ? Number(teacherId) : null,
                teacherName: teacherName || '',
                planification: data.planification || null,
                context,
            };
            const callback = state.onSaved;
            close();
            window.dispatchEvent(new CustomEvent('lmd:teacher-planning-updated', { detail }));
            window.dispatchEvent(new CustomEvent('toast', { detail: {
                type: 'success',
                message: teacherId ? 'Enseignant affecté dans le planning LMD.' : 'Enseignant retiré du planning LMD.',
            } }));
            if (typeof callback === 'function') callback(detail);
        } catch (error) {
            currentEl.textContent = error.message || 'Impossible d’enregistrer l’affectation.';
            currentEl.style.color = '#b91c1c';
        } finally {
            body.classList.remove('lqt-loading');
        }
    }

    async function open(options) {
        state = {
            ecueId: Number(options.ecueId),
            label: options.label || 'ECUE',
            context: options.context || {},
            current: null,
            onSaved: options.onSaved || null,
        };
        contextEl.textContent = state.label + ' · Semestre ' + (state.context.semestre || '—');
        currentEl.textContent = 'Lecture du planning…';
        currentEl.style.color = '';
        search.value = '';
        list.innerHTML = '<div class="lqt-empty"><i class="fas fa-spinner fa-spin"></i> Chargement…</div>';
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        try {
            const [loadedTeachers, current] = await Promise.all([loadTeachers(), resolveCurrent(options)]);
            if (!state) return;
            teachers = loadedTeachers;
            state.current = current;
            currentEl.textContent = current.id
                ? 'Planning actuel : ' + (current.name || ('enseignant #' + current.id))
                : 'Aucun enseignant principal n’est encore affecté dans le planning.';
            render();
            search.focus();
        } catch (error) {
            currentEl.textContent = error.message || 'Impossible de préparer le dialogue.';
            currentEl.style.color = '#b91c1c';
            list.innerHTML = '';
        }
    }

    search.addEventListener('input', render);
    document.getElementById('lmdTeacherQuickClose').addEventListener('click', close);
    document.getElementById('lmdTeacherQuickCancel').addEventListener('click', close);
    document.getElementById('lmdTeacherQuickClear').addEventListener('click', () => save(null, ''));
    backdrop.addEventListener('click', event => { if (event.target === backdrop) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && backdrop.classList.contains('is-open')) close(); });

    window.lmdTeacherQuick = { open, close };
})();
</script>
@endcan
