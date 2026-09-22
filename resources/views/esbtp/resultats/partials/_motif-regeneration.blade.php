{{-- Motif de régénération d'un bulletin incomplet. Le serveur l'exige dès que
     des notes attendues manquent ; sans ce champ, Régénérer échouait toujours
     sur une classe incomplète. --}}
<div class="modal fade srb-modal-wrapper" id="srIncompleteReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:560px;">
        <div class="modal-content srb-modal">
            <div class="srb-modal-hero">
                <div class="srb-modal-hero-icon"><i class="fas fa-pen-to-square"></i></div>
                <div class="srb-modal-hero-body">
                    <div class="srb-modal-hero-title">Bulletin incomplet</div>
                    <div class="srb-modal-hero-sub">Des notes attendues manquent encore sur cette période.</div>
                </div>
                <button type="button" class="srb-modal-close" data-bs-dismiss="modal" aria-label="Fermer">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>
            <div class="srb-modal-body">
                <label for="srIncompleteReasonInput" class="form-label fw-semibold">Motif de la régénération</label>
                <textarea id="srIncompleteReasonInput" class="form-control" rows="3" maxlength="1000"
                          placeholder="Ex. : notes de TP attendues après le conseil de classe"></textarea>
                <div class="form-text"><span id="srIncompleteReasonCount">0</span> / 8 caractères minimum. Ce motif est conservé dans le journal.</div>
            </div>
            <div class="srb-modal-footer">
                <button type="button" class="srb-action srb-action--ghost" data-bs-dismiss="modal">Annuler</button>
                <button type="button" id="srIncompleteReasonConfirm" class="srb-action srb-action--warning" disabled>
                    <i class="fas fa-rotate-right"></i> Régénérer quand même
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    window.srSetRegenerateBusy = function(busy) {
        document.querySelectorAll('[data-regenerate-bulletin="1"], #srbDetailsRegenerateBtn, #srWarningRegenerateBtn')
            .forEach(function(button) {
                button.disabled = busy;
                button.setAttribute('aria-busy', busy ? 'true' : 'false');
                button.style.opacity = busy ? '0.6' : '';
                button.style.pointerEvents = busy ? 'none' : '';
            });
    };

    // Demande le motif exigé pour un bulletin incomplet, puis rappelle `onReason`.
    window.srAskIncompleteReason = function(onReason) {
        var modalEl = document.getElementById('srIncompleteReasonModal');
        var input = document.getElementById('srIncompleteReasonInput');
        var counter = document.getElementById('srIncompleteReasonCount');
        var confirmBtn = document.getElementById('srIncompleteReasonConfirm');
        if (!modalEl || !input || !confirmBtn) return;

        input.value = '';
        var refresh = function() {
            var length = input.value.trim().length;
            if (counter) counter.textContent = length;
            confirmBtn.disabled = length < 8;
        };
        input.oninput = refresh;
        refresh();

        confirmBtn.onclick = function() {
            var reason = input.value.trim();
            if (reason.length < 8) return;
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            onReason(reason);
        };

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        setTimeout(function() { input.focus(); }, 300);
    };
})();
</script>
