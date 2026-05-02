<div data-enseignant-id="{{ $enseignant->id }}">
    <div class="bav-edit-banner">
        <i class="fas fa-edit"></i>
        <span>Mode édition actif. Cliquez sur un créneau pour cycler entre <strong>Indisponible → Disponible → Préféré</strong>.</span>
    </div>

    <div class="bav-stats">
        <div class="bav-stat bav-stat--preferred">
            <div class="bav-stat-icon"><i class="fas fa-star"></i></div>
            <div class="bav-stat-content">
                <div class="bav-stat-value">{{ $stats['preferred'] }}</div>
                <div class="bav-stat-label">Préférés</div>
            </div>
        </div>
        <div class="bav-stat bav-stat--available">
            <div class="bav-stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="bav-stat-content">
                <div class="bav-stat-value">{{ $stats['available'] }}</div>
                <div class="bav-stat-label">Disponibles</div>
            </div>
        </div>
        <div class="bav-stat bav-stat--unavailable">
            <div class="bav-stat-icon"><i class="fas fa-times-circle"></i></div>
            <div class="bav-stat-content">
                <div class="bav-stat-value">{{ $stats['unavailable'] }}</div>
                <div class="bav-stat-label">Indisponibles</div>
            </div>
        </div>
    </div>

    <div class="bav-actions">
        <button type="button" class="bav-btn bav-btn--edit" onclick="toggleEditMode({{ $enseignant->id }})">
            <i class="fas fa-edit"></i>
            <span class="edit-text">Modifier</span>
        </button>
        <button type="button" class="bav-btn bav-btn--save" onclick="saveAvailability({{ $enseignant->id }})" style="display: none;">
            <i class="fas fa-save"></i>
            <span>Sauvegarder</span>
        </button>
        <button type="button" class="bav-btn bav-btn--cancel" onclick="cancelEditMode({{ $enseignant->id }})" style="display: none;">
            <i class="fas fa-times"></i>
            <span>Annuler</span>
        </button>
    </div>

    <div class="bav-grid" id="bav-grid-{{ $enseignant->id }}">
        <div class="bav-grid-time-header">Horaires</div>
        @foreach($joursNoms as $dayKey => $dayName)
            <div class="bav-grid-day-header">{{ substr($dayName, 0, 3) }}</div>
        @endforeach

        @foreach($hours as $index => $hour)
            <div class="bav-grid-time">{{ sprintf('%02d:00', $hour) }}</div>
            @foreach($days as $dayIndex => $day)
                @php
                    $status = $availability[$day][$index] ?? 'unavailable';
                @endphp
                <div class="bav-grid-cell {{ $status }}"
                     id="slot-{{ $enseignant->id }}-{{ $index }}-{{ $dayIndex }}"
                     data-enseignant-id="{{ $enseignant->id }}"
                     data-day="{{ $dayIndex }}"
                     data-hour="{{ $hour }}"
                     data-time-index="{{ $index }}"
                     data-original-status="{{ $status }}"
                     title="{{ $joursNoms[$day] }} {{ sprintf('%02d:00', $hour) }} — {{ ucfirst($status) }}">
                    @if($status === 'preferred')
                        <i class="fas fa-star"></i><span class="bav-slot-label">Préf.</span>
                    @elseif($status === 'available')
                        <i class="fas fa-check"></i><span class="bav-slot-label">Dispo</span>
                    @else
                        <i class="fas fa-minus"></i>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>

    <div class="bav-legend">
        <div class="bav-legend-item">
            <div class="bav-legend-swatch preferred"><i class="fas fa-star"></i></div>
            <span>Préféré</span>
        </div>
        <div class="bav-legend-item">
            <div class="bav-legend-swatch available"><i class="fas fa-check"></i></div>
            <span>Disponible</span>
        </div>
        <div class="bav-legend-item">
            <div class="bav-legend-swatch unavailable"><i class="fas fa-minus"></i></div>
            <span>Indisponible</span>
        </div>
    </div>
</div>

<script>
(function() {
    const enseignantId = {{ $enseignant->id }};

    if (!window.editModes) window.editModes = {};
    if (!window.originalData) window.originalData = {};
    if (!window.modifiedSlots) window.modifiedSlots = {};

    window.editModes[enseignantId] = false;
    window.originalData[enseignantId] = {};
    window.modifiedSlots[enseignantId] = new Set();

    window.toggleEditMode = function(id) {
        window.editModes[id] = !window.editModes[id];
        const item = document.getElementById('bav-item-' + id);
        const slots = item.querySelectorAll('.bav-grid-cell');
        const editBtn = item.querySelector('.bav-btn--edit');
        const saveBtn = item.querySelector('.bav-btn--save');
        const cancelBtn = item.querySelector('.bav-btn--cancel');

        if (window.editModes[id]) {
            item.classList.add('bav-edit-active');
            slots.forEach(slot => {
                slot.onclick = () => toggleSlotStatus(id, slot);
                window.originalData[id][slot.id] = slot.dataset.originalStatus;
            });
            editBtn.style.display = 'none';
            saveBtn.style.display = 'inline-flex';
            cancelBtn.style.display = 'inline-flex';
            showNotification('Mode édition activé', 'info');
        } else {
            item.classList.remove('bav-edit-active');
            slots.forEach(slot => { slot.onclick = null; });
            editBtn.style.display = 'inline-flex';
            saveBtn.style.display = 'none';
            cancelBtn.style.display = 'none';
        }
    };

    window.toggleSlotStatus = function(id, slot) {
        if (!window.editModes[id]) return;
        const statuses = ['unavailable', 'available', 'preferred'];
        const icons = [
            '<i class="fas fa-minus"></i>',
            '<i class="fas fa-check"></i><span class="bav-slot-label">Dispo</span>',
            '<i class="fas fa-star"></i><span class="bav-slot-label">Préf.</span>',
        ];
        const currentClasses = Array.from(slot.classList);
        let currentStatus = statuses.find(s => currentClasses.includes(s)) || 'unavailable';
        const nextIndex = (statuses.indexOf(currentStatus) + 1) % statuses.length;
        const nextStatus = statuses[nextIndex];

        statuses.forEach(s => slot.classList.remove(s));
        slot.classList.add(nextStatus);
        slot.innerHTML = icons[nextIndex];

        if (nextStatus !== window.originalData[id][slot.id]) {
            slot.classList.add('modified');
            window.modifiedSlots[id].add(slot.id);
        } else {
            slot.classList.remove('modified');
            window.modifiedSlots[id].delete(slot.id);
        }

        const joursNoms = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        const statusNames = { unavailable: 'Indisponible', available: 'Disponible', preferred: 'Préféré' };
        slot.title = `${joursNoms[parseInt(slot.dataset.day)]} ${slot.dataset.hour}:00 — ${statusNames[nextStatus]}`;
    };

    window.cancelEditMode = function(id) {
        const icons = {
            unavailable: '<i class="fas fa-minus"></i>',
            available: '<i class="fas fa-check"></i><span class="bav-slot-label">Dispo</span>',
            preferred: '<i class="fas fa-star"></i><span class="bav-slot-label">Préf.</span>',
        };
        window.modifiedSlots[id].forEach(slotId => {
            const slot = document.getElementById(slotId);
            const originalStatus = window.originalData[id][slotId];
            ['unavailable', 'available', 'preferred'].forEach(s => slot.classList.remove(s));
            slot.classList.add(originalStatus);
            slot.innerHTML = icons[originalStatus];
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
            const currentStatus = statuses.find(s => slot.classList.contains(s));
            const startHour = 8 + parseInt(slot.dataset.timeIndex);
            changedSlots.push({
                day: parseInt(slot.dataset.day),
                startTime: String(startHour).padStart(2, '0') + ':00',
                endTime: String(startHour + 1).padStart(2, '0') + ':00',
                status: currentStatus,
            });
        });

        fetch(`/esbtp/enseignants/${id}/update-availability`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ changes: changedSlots }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showNotification('Disponibilités mises à jour', 'success');
                window.modifiedSlots[id].forEach(slotId => {
                    const slot = document.getElementById(slotId);
                    const statuses = ['unavailable', 'available', 'preferred'];
                    const currentStatus = statuses.find(s => slot.classList.contains(s));
                    window.originalData[id][slotId] = currentStatus;
                    slot.dataset.originalStatus = currentStatus;
                    slot.classList.remove('modified');
                });
                window.modifiedSlots[id].clear();
                toggleEditMode(id);
                refreshBlock(id);
            } else {
                showNotification('Erreur : ' + (data.message || 'Erreur inconnue'), 'danger');
            }
        })
        .catch(error => showNotification('Erreur de connexion : ' + error.message, 'danger'));
    };
})();
</script>
