@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const quickGenerateModal = document.getElementById('quickGenerateModal');
    if (!quickGenerateModal) {
        return;
    }

    const quickGenerateForm = quickGenerateModal.querySelector('form');
    const submitButton = quickGenerateForm ? quickGenerateForm.querySelector('button[type="submit"]') : null;
    const confirmModalEl = document.getElementById('quickGenerateConfirmModal');
    const confirmList = document.getElementById('quickGenerateConflictList');
    const confirmBtn = document.getElementById('quickGenerateConfirmBtn');
    const confirmModal = confirmModalEl ? new bootstrap.Modal(confirmModalEl) : null;
    let quickGenerateConfirmed = false;
    // Les libellés (classes, matières, enseignants) viennent de la base :
    // ne jamais les injecter bruts dans la liste des conflits.
    const escapeConflictHtml = (value) => String(value ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));

    const selectAllCheckbox = document.getElementById('quick-generate-select-all');
    const checkboxes = () => quickGenerateModal.querySelectorAll('input[name="classes[]"]');

    const updateHeaderState = () => {
        const boxes = Array.from(checkboxes());
        const checked = boxes.filter((box) => box.checked).length;
        if (!selectAllCheckbox) {
            return;
        }
        selectAllCheckbox.checked = checked > 0 && checked === boxes.length;
        selectAllCheckbox.indeterminate = checked > 0 && checked < boxes.length;
    };

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            selectAllCheckbox.indeterminate = false;
        });
    }

    checkboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateHeaderState);
    });

    updateHeaderState();

    if (quickGenerateForm) {
        quickGenerateForm.addEventListener('submit', async (event) => {
            if (quickGenerateConfirmed) {
                return;
            }

            event.preventDefault();

            const formData = new FormData(quickGenerateForm);
            const actionUrl = "{{ route('esbtp.emploi-temps.quick-generate.preview') }}";
            const token = quickGenerateForm.querySelector('input[name="_token"]')?.value || '';

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Analyse...';
            }

            try {
                const response = await fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    throw new Error(payload.message || 'Erreur lors de la prévisualisation.');
                }

                if (payload.total_conflicts > 0 && confirmModal && confirmList) {
                    confirmList.innerHTML = payload.conflicts.map((group) => {
                        const itemsHtml = (group.items || []).map((item) => {
                            const badgeClass = item.reason === 'occupied' ? 'bg-danger' : 'bg-warning text-dark';
                            const reasonLabel = item.reason === 'occupied' ? 'Déjà occupé' : 'Indisponible';
                            return `
                                <li class="d-flex flex-wrap align-items-center gap-2 py-1">
                                    <span class="badge ${badgeClass}">${reasonLabel}</span>
                                    <span class="fw-semibold">${escapeConflictHtml(item.matiere)}</span>
                                    <span class="text-muted">${escapeConflictHtml(item.enseignant)}</span>
                                    <span class="text-muted">${escapeConflictHtml(item.jour)} ${escapeConflictHtml(item.heure_debut)}–${escapeConflictHtml(item.heure_fin)}</span>
                                </li>
                            `;
                        }).join('');

                        return `
                            <div class="p-3 border rounded-3 bg-light">
                                <div class="fw-semibold mb-2">${escapeConflictHtml(group.class_name)}</div>
                                <ul class="list-unstyled mb-0">
                                    ${itemsHtml}
                                </ul>
                            </div>
                        `;
                    }).join('');

                    confirmModal.show();
                } else {
                    quickGenerateConfirmed = true;
                    quickGenerateForm.submit();
                }
            } catch (error) {
                alert(error.message || 'Erreur lors de la prévisualisation.');
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<i class="fas fa-bolt me-1"></i>Générer';
                }
            }
        });
    }

    if (confirmBtn && quickGenerateForm) {
        confirmBtn.addEventListener('click', () => {
            quickGenerateConfirmed = true;
            if (confirmModal) {
                confirmModal.hide();
            }
            quickGenerateForm.submit();
        });
    }
});
</script>
@endpush
