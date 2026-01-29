<div class="bulk-enseignant-block" data-enseignant-id="{{ $enseignant->id }}">
    <!-- Stats rapides -->
    <div class="availability-stats">
        <div class="stat-mini">
            <div class="legend-color" style="background: var(--primary); width: 12px; height: 12px;"></div>
            <span>Préféré: <span class="stat-value">{{ $stats['preferred'] }}</span></span>
        </div>
        <div class="stat-mini">
            <div class="legend-color" style="background: var(--success); width: 12px; height: 12px;"></div>
            <span>Disponible: <span class="stat-value">{{ $stats['available'] }}</span></span>
        </div>
        <div class="stat-mini">
            <div class="legend-color" style="background: var(--border); width: 12px; height: 12px;"></div>
            <span>Indisponible: <span class="stat-value">{{ $stats['unavailable'] }}</span></span>
        </div>
    </div>

    <!-- Actions -->
    <div class="availability-actions">
        <button type="button" class="btn-edit-availability" onclick="toggleEditMode({{ $enseignant->id }})">
            <i class="fas fa-edit me-1"></i>
            <span class="edit-text">Modifier</span>
        </button>
        <button type="button" class="btn-save-availability" onclick="saveAvailability({{ $enseignant->id }})" style="display: none;">
            <i class="fas fa-save me-1"></i>
            Sauvegarder
        </button>
        <button type="button" class="btn-cancel-availability" onclick="cancelEditMode({{ $enseignant->id }})" style="display: none;">
            <i class="fas fa-times me-1"></i>
            Annuler
        </button>
    </div>

    <!-- Grille de disponibilité -->
    <div class="availability-grid" id="availability-grid-{{ $enseignant->id }}">
        <!-- En-têtes -->
        <div class="availability-time-header">Horaires</div>
        @foreach($joursNoms as $dayKey => $dayName)
            <div class="availability-day-header">{{ substr($dayName, 0, 3) }}</div>
        @endforeach

        <!-- Créneaux horaires -->
        @foreach($hours as $index => $hour)
            <div class="availability-time-slot">{{ sprintf('%02d:00', $hour) }}</div>
            @foreach($days as $dayIndex => $day)
                @php
                    $status = $availability[$day][$index] ?? 'unavailable';
                    $icon = $status === 'preferred' ? '★' : ($status === 'available' ? '✓' : '✗');
                @endphp
                <div class="availability-slot {{ $status }}"
                     id="slot-{{ $enseignant->id }}-{{ $index }}-{{ $dayIndex }}"
                     data-enseignant-id="{{ $enseignant->id }}"
                     data-day="{{ $dayIndex }}"
                     data-hour="{{ $hour }}"
                     data-time-index="{{ $index }}"
                     data-original-status="{{ $status }}"
                     title="{{ $joursNoms[$day] }} {{ sprintf('%02d:00', $hour) }} - {{ ucfirst($status) }}">
                    {{ $icon }}
                </div>
            @endforeach
        @endforeach
    </div>

    <!-- Légende -->
    <div class="availability-legend">
        <div class="legend-item">
            <div class="legend-color" style="background: var(--primary);"></div>
            <span>Préféré</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: var(--success);"></div>
            <span>Disponible</span>
        </div>
        <div class="legend-item">
            <div class="legend-color" style="background: var(--border);"></div>
            <span>Indisponible</span>
        </div>
    </div>
</div>

<script>
(function() {
    const enseignantId = {{ $enseignant->id }};
    const blockId = `enseignant-block-${enseignantId}`;

    // Initialiser les données pour cet enseignant
    if (!window.editModes) window.editModes = {};
    if (!window.originalData) window.originalData = {};
    if (!window.modifiedSlots) window.modifiedSlots = {};

    window.editModes[enseignantId] = false;
    window.originalData[enseignantId] = {};
    window.modifiedSlots[enseignantId] = new Set();

    // Fonctions d'édition
    window.toggleEditMode = function(id) {
        window.editModes[id] = !window.editModes[id];
        const block = document.querySelector(`[data-enseignant-id="${id}"]`);
        const slots = block.querySelectorAll('.availability-slot');
        const editBtn = block.querySelector('.btn-edit-availability');
        const saveBtn = block.querySelector('.btn-save-availability');
        const cancelBtn = block.querySelector('.btn-cancel-availability');

        if (window.editModes[id]) {
            // Activer le mode édition
            slots.forEach(slot => {
                slot.style.cursor = 'pointer';
                slot.onclick = () => toggleSlotStatus(id, slot);
                window.originalData[id][slot.id] = slot.dataset.originalStatus;
            });

            editBtn.style.display = 'none';
            saveBtn.style.display = 'flex';
            cancelBtn.style.display = 'flex';
            block.style.background = 'linear-gradient(135deg, #fef3c7, #fde68a)';

            showNotification('Mode édition activé. Cliquez sur les créneaux pour modifier.', 'info');
        } else {
            // Désactiver le mode édition
            slots.forEach(slot => {
                slot.style.cursor = 'default';
                slot.onclick = null;
            });

            editBtn.style.display = 'flex';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
            block.style.background = 'var(--surface)';
        }
    };

    window.toggleSlotStatus = function(id, slot) {
        if (!window.editModes[id]) return;

        const statuses = ['unavailable', 'available', 'preferred'];
        const icons = ['✗', '✓', '★'];
        const currentClasses = Array.from(slot.classList);
        let currentStatus = statuses.find(status => currentClasses.includes(status)) || 'unavailable';

        const currentIndex = statuses.indexOf(currentStatus);
        const nextIndex = (currentIndex + 1) % statuses.length;
        const nextStatus = statuses[nextIndex];

        statuses.forEach(status => slot.classList.remove(status));
        slot.classList.add(nextStatus);
        slot.textContent = icons[nextIndex];

        if (nextStatus !== window.originalData[id][slot.id]) {
            slot.classList.add('modified');
            window.modifiedSlots[id].add(slot.id);
        } else {
            slot.classList.remove('modified');
            window.modifiedSlots[id].delete(slot.id);
        }

        // Mettre à jour le tooltip
        const joursNoms = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        const statusNames = { unavailable: 'Indisponible', available: 'Disponible', preferred: 'Préféré' };
        const dayIndex = parseInt(slot.dataset.day);
        const hour = slot.dataset.hour;
        slot.title = `${joursNoms[dayIndex]} ${hour}:00 - ${statusNames[nextStatus]}`;
    };

    window.cancelEditMode = function(id) {
        window.modifiedSlots[id].forEach(slotId => {
            const slot = document.getElementById(slotId);
            const originalStatus = window.originalData[id][slotId];
            const statuses = ['unavailable', 'available', 'preferred'];
            const icons = ['✗', '✓', '★'];

            statuses.forEach(status => slot.classList.remove(status));
            slot.classList.add(originalStatus);
            slot.textContent = icons[statuses.indexOf(originalStatus)];
            slot.classList.remove('modified');
        });

        window.modifiedSlots[id].clear();
        toggleEditMode(id);
        showNotification('Modifications annulées', 'warning');
    };

    window.saveAvailability = function(id) {
        if (window.modifiedSlots[id].size === 0) {
            showNotification('Aucune modification à sauvegarder', 'warning');
            return;
        }

        const changedSlots = [];
        window.modifiedSlots[id].forEach(slotId => {
            const slot = document.getElementById(slotId);
            const statuses = ['unavailable', 'available', 'preferred'];
            const currentStatus = statuses.find(status => slot.classList.contains(status));

            const timeIndex = parseInt(slot.dataset.timeIndex);
            const startHour = 8 + timeIndex;
            const endHour = startHour + 1;

            changedSlots.push({
                day: parseInt(slot.dataset.day),
                startTime: String(startHour).padStart(2, '0') + ':00',
                endTime: String(endHour).padStart(2, '0') + ':00',
                status: currentStatus
            });
        });

        fetch(`/esbtp/enseignants/${id}/update-availability`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ changes: changedSlots })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Disponibilités mises à jour !', 'success');

                // Mettre à jour les données originales
                window.modifiedSlots[id].forEach(slotId => {
                    const slot = document.getElementById(slotId);
                    const statuses = ['unavailable', 'available', 'preferred'];
                    const currentStatus = statuses.find(status => slot.classList.contains(status));
                    window.originalData[id][slotId] = currentStatus;
                    slot.dataset.originalStatus = currentStatus;
                    slot.classList.remove('modified');
                });

                window.modifiedSlots[id].clear();
                toggleEditMode(id);

                // Rafraîchir le bloc pour mettre à jour les stats
                refreshBlock(id);
            } else {
                showNotification('Erreur: ' + (data.message || 'Erreur inconnue'), 'danger');
            }
        })
        .catch(error => {
            showNotification('Erreur de connexion: ' + error.message, 'danger');
        });
    };
})();
</script>
