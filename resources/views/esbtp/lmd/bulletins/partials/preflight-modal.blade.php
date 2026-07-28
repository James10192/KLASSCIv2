@once
<div class="lmd-preflight-backdrop" id="lmd-preflight-modal" aria-hidden="true">
    <div class="lmd-preflight-dialog" role="dialog" aria-modal="true" aria-labelledby="lmd-preflight-title">
        <div class="lmd-preflight-header">
            <div>
                <div id="lmd-preflight-title" class="lmd-preflight-title">Contrôle avant génération</div>
                <div class="lmd-preflight-subtitle">KLASSCI vérifie les données académiques avant de créer les bulletins officiels.</div>
            </div>
            <button type="button" class="lmd-preflight-close" data-lmd-preflight-close aria-label="Fermer">&times;</button>
        </div>
        <div class="lmd-preflight-body">
            <div class="lmd-preflight-alert" data-lmd-preflight-message></div>
            <ul class="lmd-preflight-list" data-lmd-preflight-list></ul>
            <div class="lmd-preflight-reason" data-lmd-preflight-reason-block style="display:none;">
                <label class="lmd-preflight-label" for="lmd-preflight-reason">Motif de génération incomplète</label>
                <textarea id="lmd-preflight-reason" class="lmd-preflight-textarea" rows="3" data-lmd-preflight-reason placeholder="Expliquez pourquoi le bulletin incomplet doit être généré maintenant."></textarea>
            </div>
        </div>
        <div class="lmd-preflight-footer">
            <button type="button" class="lmd-preflight-btn lmd-preflight-btn--secondary" data-lmd-preflight-close>Annuler</button>
            <button type="button" class="lmd-preflight-btn lmd-preflight-btn--primary" data-lmd-preflight-confirm style="display:none;">Continuer</button>
        </div>
    </div>
</div>

<style>
    .lmd-preflight-backdrop {
        position: fixed; inset: 0; z-index: 1070; display: none;
        align-items: center; justify-content: center; padding: 1rem;
        background: rgba(15,23,42,.48);
    }
    .lmd-preflight-backdrop.is-open { display: flex; }
    .lmd-preflight-dialog {
        width: min(680px, 100%); background: #fff; border-radius: 12px;
        box-shadow: 0 24px 70px rgba(15,23,42,.26); overflow: hidden;
    }
    .lmd-preflight-header, .lmd-preflight-footer {
        padding: 1rem 1.25rem; display: flex; justify-content: space-between; gap: .75rem; align-items: center;
        border-bottom: 1px solid #f1f5f9;
    }
    .lmd-preflight-footer { border-top: 1px solid #f1f5f9; border-bottom: 0; justify-content: flex-end; }
    .lmd-preflight-title { font-size: 1rem; font-weight: 800; color: #1e293b; }
    .lmd-preflight-subtitle { font-size: .82rem; color: #64748b; margin-top: .15rem; }
    .lmd-preflight-close { border: 0; background: #f1f5f9; color: #64748b; width: 34px; height: 34px; border-radius: 8px; font-size: 1.25rem; line-height: 1; }
    .lmd-preflight-body { padding: 1rem 1.25rem; max-height: 65vh; overflow: auto; }
    .lmd-preflight-alert { border-radius: 10px; padding: .8rem 1rem; background: #eff6ff; color: #1e40af; font-weight: 700; }
    .lmd-preflight-alert.is-danger { background: #fef2f2; color: #991b1b; }
    .lmd-preflight-alert.is-warning { background: #fffbeb; color: #92400e; }
    .lmd-preflight-list { margin: .75rem 0 0; padding-left: 1.1rem; color: #334155; }
    .lmd-preflight-list li { margin-bottom: .35rem; }
    .lmd-preflight-reason { margin-top: 1rem; }
    .lmd-preflight-label { display: block; font-size: .75rem; text-transform: uppercase; color: #64748b; font-weight: 800; margin-bottom: .35rem; }
    .lmd-preflight-textarea { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 9px; padding: .7rem; resize: vertical; }
    .lmd-preflight-textarea:focus { outline: none; border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.08); }
    .lmd-preflight-btn { border: 0; border-radius: 9px; padding: .58rem 1rem; font-weight: 800; }
    .lmd-preflight-btn--secondary { background: #f1f5f9; color: #334155; }
    .lmd-preflight-btn--primary { background: #0453cb; color: #fff; }
</style>

<script>
if (!window.lmdPreflightModalLoaded) {
    window.lmdPreflightModalLoaded = true;
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('lmd-preflight-modal');
        if (!modal) return;

        const messageNode = modal.querySelector('[data-lmd-preflight-message]');
        const listNode = modal.querySelector('[data-lmd-preflight-list]');
        const reasonBlock = modal.querySelector('[data-lmd-preflight-reason-block]');
        const reasonInput = modal.querySelector('[data-lmd-preflight-reason]');
        const confirmButton = modal.querySelector('[data-lmd-preflight-confirm]');
        let pendingForm = null;

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
        };
        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            pendingForm = null;
            confirmButton.style.display = 'none';
            reasonBlock.style.display = 'none';
            reasonInput.value = '';
        };

        modal.querySelectorAll('[data-lmd-preflight-close]').forEach((button) => button.addEventListener('click', closeModal));

        const renderPreflight = (payload, form, allowContinue) => {
            pendingForm = allowContinue ? form : null;
            messageNode.textContent = payload.message || 'Contrôle terminé.';
            messageNode.classList.toggle('is-danger', !allowContinue);
            messageNode.classList.toggle('is-warning', !!payload.requires_incomplete_reason);
            listNode.innerHTML = '';

            (payload.blocking_errors || []).slice(0, 12).forEach((entry) => {
                const li = document.createElement('li');
                const issues = (entry.issues || []).map((issue) => issue.message).join(' ');
                li.textContent = `Étudiant ${entry.student_id ?? '-'} : ${issues || entry.message || 'Blocage détecté'}`;
                listNode.appendChild(li);
            });

            reasonBlock.style.display = payload.requires_incomplete_reason ? 'block' : 'none';
            confirmButton.style.display = allowContinue ? 'inline-flex' : 'none';
            openModal();
        };

        confirmButton.addEventListener('click', () => {
            if (!pendingForm) return;
            const reasonField = pendingForm.querySelector('[data-lmd-reason-field]');
            if (reasonBlock.style.display !== 'none') {
                const reason = reasonInput.value.trim();
                if (!reason) {
                    reasonInput.focus();
                    return;
                }
                if (reasonField) reasonField.value = reason;
            }
            pendingForm.submit();
        });

        document.querySelectorAll('[data-lmd-preflight]').forEach((button) => {
            button.addEventListener('click', async () => {
                const form = document.getElementById(button.dataset.formId);
                if (!form || button.disabled) return;

                const payload = new FormData(form);
                payload.set('mode', button.dataset.mode || 'classe');

                button.disabled = true;
                try {
                    const response = await fetch(@json(route('esbtp.lmd.bulletins.preflight')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                        },
                        body: payload,
                    });
                    const data = await response.json();
                    if (response.ok && data.can_generate && !data.requires_incomplete_reason) {
                        form.submit();
                        return;
                    }
                    renderPreflight(data, form, response.ok && data.can_generate);
                } catch (error) {
                    renderPreflight({
                        message: 'Contrôle impossible pour le moment. Rechargez la page puis réessayez.',
                        blocking_errors: [],
                    }, form, false);
                } finally {
                    button.disabled = false;
                }
            });
        });
    });
}
</script>
@endonce
