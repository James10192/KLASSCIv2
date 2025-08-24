@extends('layouts.app')

@section('title', 'Mes disponibilités')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Styles cohérents avec la page admin enseignants/show */
    body {
        background-color: var(--background);
    }

    .availability-header {
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-medium);
    }

    .availability-header h1 {
        margin: 0;
        font-size: 2.5rem;
        font-weight: 700;
    }

    .availability-header p {
        margin: var(--space-sm) 0 0 0;
        font-size: 1.1rem;
        opacity: 0.9;
    }

    /* Section principale de disponibilité - identique à la page admin */
    .availability-main-section {
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        margin: var(--space-xl) 0;
        border: 1px solid var(--border-light);
        box-shadow: var(--shadow-card);
    }

    .availability-header-section {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: var(--space-lg);
    }

    .availability-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .availability-actions {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
    }
    
    .btn-edit-availability {
        background: var(--primary);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-edit-availability:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--primary-rgb), 0.3);
    }
    
    .btn-save-availability {
        background: var(--success);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-save-availability:hover {
        background: var(--success-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--success-rgb), 0.3);
    }
    
    .btn-cancel-availability {
        background: var(--danger);
        color: white;
        border: none;
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-medium);
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }
    
    .btn-cancel-availability:hover {
        background: var(--danger-dark);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(var(--danger-rgb), 0.3);
    }

    /* Grille de disponibilités - identique à la page admin */
    .availability-grid {
        display: grid;
        grid-template-columns: 80px repeat(6, 1fr);
        gap: 2px;
        background-color: var(--border-light);
        border-radius: var(--radius-small);
        padding: 2px;
        max-width: 100%;
        overflow-x: auto;
    }

    .availability-header-cell, .availability-time-slot {
        background-color: var(--surface);
        padding: var(--space-sm);
        text-align: center;
        font-weight: 600;
        font-size: 0.875rem;
        color: var(--text-secondary);
        border-radius: var(--radius-xs);
    }

    .availability-header-cell {
        background: linear-gradient(135deg, var(--primary-light), var(--primary));
        color: white;
        font-size: 0.8rem;
    }

    .availability-time-slot {
        background-color: var(--neutral-light);
        font-family: 'Courier New', monospace;
        font-weight: 700;
    }

    .availability-slot {
        background-color: var(--surface);
        border-radius: var(--radius-xs);
        height: 45px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        transition: all 0.3s ease;
    }

    /* États des créneaux - identique au système admin */
    .availability-slot.unavailable {
        background: var(--border);
        color: var(--text-muted);
    }

    .availability-slot.available {
        background: var(--success);
        color: white;
        font-weight: 600;
    }

    .availability-slot.preferred {
        background: var(--primary);
        color: white;
        font-weight: 600;
    }

    /* Mode édition pour les créneaux - identique à la page admin */
    .availability-slot.editable {
        cursor: pointer;
        border: 2px dashed transparent;
        transition: all 0.3s ease;
    }

    .availability-slot.editable:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 10;
    }

    .availability-slot.editable.unavailable:hover {
        border-color: var(--success);
        background: rgba(16, 185, 129, 0.1);
    }

    .availability-slot.editable.available:hover {
        border-color: var(--primary);
        background: rgba(30, 58, 138, 0.1);
    }

    .availability-slot.editable.preferred:hover {
        border-color: var(--border);
        background: rgba(107, 114, 128, 0.1);
    }

    /* Légende */
    .availability-legend {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        margin: var(--space-lg) 0;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: 0.9rem;
        font-weight: 500;
    }

    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: var(--radius-small);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.7rem;
    }

    /* Messages de feedback */
    .alert {
        padding: var(--space-md) var(--space-lg);
        border-radius: var(--radius-small);
        margin-bottom: var(--space-md);
        font-weight: 500;
    }

    .alert-success {
        background-color: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
    }

    .alert-error {
        background-color: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .alert-info {
        background-color: rgba(6, 182, 212, 0.1);
        color: var(--accent-blue);
        border: 1px solid rgba(6, 182, 212, 0.2);
    }

    /* Instructions d'utilisation */
    .instructions {
        background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(16, 185, 129, 0.1));
        border: 1px solid rgba(6, 182, 212, 0.2);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
    }

    .instructions h3 {
        color: var(--accent-blue);
        margin: 0 0 var(--space-md) 0;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .instructions ul {
        margin: 0;
        padding-left: var(--space-lg);
    }

    .instructions li {
        margin-bottom: var(--space-xs);
        color: var(--text-secondary);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .availability-grid {
            grid-template-columns: 60px repeat(6, 1fr);
            font-size: 0.75rem;
        }
        
        .availability-slot {
            height: 40px;
            font-size: 1.2rem;
        }
        
        .availability-header h1 {
            font-size: 2rem;
        }
        
        .availability-legend {
            flex-direction: column;
            gap: var(--space-md);
        }
        
        .availability-actions {
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        
        <!-- Header -->
        <div class="availability-header">
            <h1><i class="fas fa-calendar-check me-3"></i>Mes disponibilités</h1>
            <p>Gérez vos créneaux de disponibilité pour l'organisation des cours.</p>
        </div>

        <!-- Messages de feedback -->
        <div id="feedback-messages"></div>

        <!-- Instructions -->
        <div class="instructions">
            <h3><i class="fas fa-info-circle"></i> Instructions</h3>
            <ul>
                <li>Cliquez sur <strong>"Modifier"</strong> pour activer le mode édition</li>
                <li>Cliquez sur les créneaux pour changer votre disponibilité</li>
                <li><strong>Non disponible</strong> → <strong>Disponible</strong> → <strong>Préféré</strong></li>
                <li>Cliquez <strong>"Sauvegarder"</strong> pour enregistrer vos modifications</li>
            </ul>
        </div>

        <!-- Section principale de disponibilité -->
        <div class="availability-main-section">
            <div class="availability-header-section">
                <div class="availability-title">
                    <i class="fas fa-calendar-alt"></i>
                    Grille de disponibilité
                </div>
                <div class="availability-actions">
                    <button id="editAvailabilityBtn" class="btn-edit-availability" onclick="toggleEditMode()">
                        <i class="fas fa-edit me-1"></i>
                        <span class="edit-text">Modifier</span>
                    </button>
                    <button id="saveAvailabilityBtn" class="btn-save-availability" onclick="saveAvailability()" style="display: none;">
                        <i class="fas fa-save me-1"></i>
                        Sauvegarder
                    </button>
                    <button id="cancelAvailabilityBtn" class="btn-cancel-availability" onclick="cancelEditMode()" style="display: none;">
                        <i class="fas fa-times me-1"></i>
                        Annuler
                    </button>
                </div>
            </div>

            <!-- Grille de disponibilités -->
            <div class="availability-grid">
                <!-- En-têtes des colonnes -->
                <div class="availability-header-cell"></div>
                <div class="availability-header-cell">Lun</div>
                <div class="availability-header-cell">Mar</div>
                <div class="availability-header-cell">Mer</div>
                <div class="availability-header-cell">Jeu</div>
                <div class="availability-header-cell">Ven</div>
                <div class="availability-header-cell">Sam</div>

                <!-- Créneaux horaires et cases de disponibilité -->
                @php
                    $hours = range(8, 18); // 8h à 18h = 11 heures
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']; // Exclure dimanche
                    $dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
                    $icons = ['unavailable' => '✗', 'available' => '✓', 'preferred' => '★'];
                @endphp
                
                @foreach($hours as $index => $hour)
                    <div class="availability-time-slot">{{ sprintf('%02d:00', $hour) }}</div>
                    @foreach($days as $dayIndex => $day)
                        @php
                            $availabilityClass = $availabilityData[$day][$index] ?? 'unavailable';
                            $icon = $icons[$availabilityClass];
                        @endphp
                        <div class="availability-slot {{ $availabilityClass }}" 
                             id="slot-{{ $index }}-{{ $dayIndex }}"
                             data-day="{{ $dayIndex }}" 
                             data-hour="{{ $hour }}"
                             data-time-index="{{ $index }}" 
                             data-original-status="{{ $availabilityClass }}"
                             title="{{ $dayNames[$dayIndex] }} {{ sprintf('%02d:00', $hour) }} - {{ ucfirst($availabilityClass) }}">
                            {{ $icon }}
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>

        <!-- Légende -->
        <div class="availability-legend">
            <div class="legend-item">
                <div class="legend-color" style="background: var(--primary);">★</div>
                <span>Créneaux préférés</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: var(--success);">✓</div>
                <span>Disponible</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: var(--border);">✗</div>
                <span>Non disponible</span>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
// Variables globales - identiques à la page admin
window.isEditMode = false;
window.originalData = {};
window.modifiedSlots = new Set();

window.toggleEditMode = function() {
    window.isEditMode = !window.isEditMode;
    const slots = document.querySelectorAll('.availability-slot');
    const editBtn = document.getElementById('editAvailabilityBtn');
    const saveBtn = document.getElementById('saveAvailabilityBtn');
    const cancelBtn = document.getElementById('cancelAvailabilityBtn');
    
    if (window.isEditMode) {
        // Activer le mode édition
        slots.forEach(slot => {
            slot.classList.add('editable');
            slot.onclick = () => window.toggleSlotStatus(slot);
            // Sauvegarder l'état original
            window.originalData[slot.id] = slot.dataset.originalStatus;
        });
        
        editBtn.style.display = 'none';
        saveBtn.style.display = 'flex';
        cancelBtn.style.display = 'flex';
        
        // Changer le style du header pour indiquer le mode édition
        document.querySelector('.availability-main-section').style.background = 'linear-gradient(135deg, #fef3c7, #fde68a)';
        
        // Afficher un message d'aide
        window.showNotification('Mode édition activé. Cliquez sur les créneaux pour modifier la disponibilité.', 'info');
    } else {
        // Désactiver le mode édition
        slots.forEach(slot => {
            slot.classList.remove('editable');
            slot.onclick = null;
        });
        
        editBtn.style.display = 'flex';
        saveBtn.style.display = 'none';
        cancelBtn.style.display = 'none';
        
        document.querySelector('.availability-main-section').style.background = 'linear-gradient(135deg, #f8fafc, #e2e8f0)';
    }
}

window.toggleSlotStatus = function(slot) {
    if (!window.isEditMode) return;
    
    const statuses = ['unavailable', 'available', 'preferred'];
    const icons = ['✗', '✓', '★'];
    
    // Trouver le statut actuel
    let currentIndex = 0;
    for (let i = 0; i < statuses.length; i++) {
        if (slot.classList.contains(statuses[i])) {
            currentIndex = i;
            break;
        }
    }
    
    // Passer au statut suivant
    const nextIndex = (currentIndex + 1) % statuses.length;
    const newStatus = statuses[nextIndex];
    
    // Supprimer toutes les classes de statut
    statuses.forEach(status => slot.classList.remove(status));
    
    // Ajouter la nouvelle classe
    slot.classList.add(newStatus);
    
    // Changer l'icône
    slot.textContent = icons[nextIndex];
    
    // Mettre à jour le tooltip
    const dayNames = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const statusNames = { unavailable: 'Non disponible', available: 'Disponible', preferred: 'Préféré' };
    
    const dayIndex = parseInt(slot.dataset.day);
    const hour = parseInt(slot.dataset.hour);
    slot.title = `${dayNames[dayIndex]} ${String(hour).padStart(2, '0')}:00 - ${statusNames[newStatus]}`;
    
    // Marquer comme modifié
    window.modifiedSlots.add(slot.id);
}

window.cancelEditMode = function() {
    if (!window.isEditMode) return;
    
    // Restaurer tous les créneaux à leur état original
    document.querySelectorAll('.availability-slot').forEach(slot => {
        const originalStatus = window.originalData[slot.id];
        if (originalStatus) {
            // Supprimer toutes les classes de statut
            slot.classList.remove('unavailable', 'available', 'preferred');
            // Restaurer la classe originale
            slot.classList.add(originalStatus);
            
            // Restaurer l'icône
            const icons = { unavailable: '✗', available: '✓', preferred: '★' };
            slot.textContent = icons[originalStatus];
        }
    });
    
    // Vider les données de modification
    window.modifiedSlots.clear();
    window.originalData = {};
    
    // Désactiver le mode édition
    window.toggleEditMode();
    
    window.showNotification('Modifications annulées.', 'info');
}

window.saveAvailability = function() {
    if (!window.isEditMode || window.modifiedSlots.size === 0) {
        window.showNotification('Aucune modification à sauvegarder.', 'info');
        return;
    }
    
    // Préparer les changements
    const changes = [];
    window.modifiedSlots.forEach(slotId => {
        const slot = document.getElementById(slotId);
        const day = parseInt(slot.dataset.day);
        const hour = parseInt(slot.dataset.hour);
        const status = slot.classList.contains('unavailable') ? 'unavailable' :
                      slot.classList.contains('available') ? 'available' : 'preferred';
        
        changes.push({
            day: day,
            startTime: String(hour).padStart(2, '0') + ':00',
            endTime: String(hour + 1).padStart(2, '0') + ':00',
            status: status
        });
    });
    
    // Désactiver le bouton de sauvegarde
    const saveBtn = document.getElementById('saveAvailabilityBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...';
    
    // Envoyer la requête AJAX
    fetch('{{ route("teacher.availability.update") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ changes: changes })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.showNotification(data.message, 'success');
            
            // Mettre à jour les statuts originaux
            window.modifiedSlots.forEach(slotId => {
                const slot = document.getElementById(slotId);
                const currentStatus = slot.classList.contains('unavailable') ? 'unavailable' :
                                   slot.classList.contains('available') ? 'available' : 'preferred';
                slot.dataset.originalStatus = currentStatus;
            });
            
            // Vider les modifications
            window.modifiedSlots.clear();
            window.originalData = {};
            
            // Désactiver le mode édition
            window.toggleEditMode();
        } else {
            window.showNotification('Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        window.showNotification('Erreur de communication avec le serveur.', 'error');
        console.error('Error:', error);
    })
    .finally(() => {
        // Réactiver le bouton
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

window.showNotification = function(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-error' : 'alert-info';
    const icon = type === 'success' ? 'fas fa-check-circle' : 
                 type === 'error' ? 'fas fa-exclamation-triangle' : 'fas fa-info-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass}" role="alert">
            <i class="${icon} me-2"></i>
            ${message}
        </div>
    `;
    
    document.getElementById('feedback-messages').innerHTML = alertHtml;
    
    // Auto-hide après 5 secondes
    setTimeout(() => {
        const alertElement = document.querySelector('#feedback-messages .alert');
        if (alertElement) {
            alertElement.style.opacity = '0';
            setTimeout(() => {
                alertElement.remove();
            }, 300);
        }
    }, 5000);
    
    // Scroll vers le haut pour voir le message
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>
@endpush