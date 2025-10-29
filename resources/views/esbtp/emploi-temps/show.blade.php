@extends('layouts.app')

@section('title', 'Emploi du temps - ' . (is_object($emploiTemps) && is_object($emploiTemps->classe) ? $emploiTemps->classe->name : 'Non défini') . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .timetable-container {
        overflow-x: auto;
    }

    .timetable {
        min-width: 900px;
    }

    .timetable th, .timetable td {
        min-width: 150px;
        height: 60px;
        position: relative;
    }

    .time-column {
        width: 80px;
        font-weight: bold;
        background-color: #f8f9fa;
    }

    .session-cell {
        padding: 5px;
        border-radius: 4px;
        font-size: 0.85rem;
        color: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    /* Styles pour les séances qui durent plus d'une heure */
    .session-long {
        position: relative;
        z-index: 10;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: scale(1.02);
        transition: transform 0.2s;
    }

    .session-long:hover {
        transform: scale(1.05);
        z-index: 20;
    }

    .session-cours {
        background-color: var(--primary);
    }

    .session-td {
        background-color: var(--success);
    }

    .session-tp {
        background-color: var(--secondary);
    }

    .session-examen {
        background-color: var(--danger);
    }

    .session-autre {
        background-color: var(--warning);
    }

    /* Nouveaux styles pour les pauses et déjeuners */
    .session-pause {
        background-color: var(--neutral);
    }

    .session-dejeuner {
        background-color: var(--accent-orange);
    }

    .session-info {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .session-matiere {
        font-weight: bold;
        font-size: 0.9rem;
    }

    .session-enseignant {
        font-size: 0.8rem;
        opacity: 0.9;
    }

    .session-details {
        font-size: 0.75rem;
        opacity: 0.8;
    }

    .session-actions {
        position: absolute;
        top: 5px;
        right: 5px;
        display: none;
    }

    .session-cell:hover .session-actions {
        display: block;
    }

    .session-inactive {
        opacity: 0.6;
    }

    .btn-add-session {
        border: 2px dashed #dee2e6;
        background-color: rgba(0,0,0,0.02);
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        transition: all 0.2s;
    }

    .btn-add-session:hover {
        background-color: rgba(0,0,0,0.05);
        color: #343a40;
    }

    .legend-item {
        display: inline-flex;
        align-items: center;
        margin-right: 15px;
    }

    .legend-color {
        width: 15px;
        height: 15px;
        border-radius: 3px;
        margin-right: 5px;
    }

    .seance-list-item {
        border-left: 4px solid var(--primary);
    }

    .seance-list-item.td {
        border-left-color: var(--success);
    }

    .seance-list-item.tp {
        border-left-color: var(--secondary);
    }

    .seance-list-item.examen {
        border-left-color: var(--danger);
    }

    .seance-list-item.autre {
        border-left-color: var(--warning);
    }

    /* ================================
       STYLES POUR LE MODAL DE CONFIGURATION
    ================================ */
    
    /* Configuration variables supplémentaires pour le modal */
    .config-section {
        --border: #e5e7eb;
        --text-sm: 0.875rem;
        --primary-rgb: 30, 58, 138;
    }
    
    .config-matiere-card {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: var(--space-lg);
        padding: var(--space-lg);
        border: 1px solid #e5e7eb;
        border-radius: var(--radius-large);
        margin-bottom: var(--space-md);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: var(--surface);
        box-shadow: var(--shadow-card);
        position: relative;
    }
    
    .config-matiere-card:hover {
        border-color: var(--primary);
        box-shadow: 0 4px 12px rgba(30, 58, 138, 0.15);
        transform: translateY(-1px);
    }
    
    .config-matiere-card.configured {
        border-color: var(--success);
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.1);
    }
    
    .config-matiere-card.configured::before {
        content: '✓';
        position: absolute;
        top: var(--space-sm);
        right: var(--space-sm);
        width: 24px;
        height: 24px;
        background: var(--success);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: bold;
    }
    
    .matiere-details {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }
    
    .matiere-name {
        font-weight: 700;
        font-size: 1.1rem;
        color: var(--text-primary);
        margin: 0;
        line-height: 1.2;
    }
    
    .matiere-description {
        font-size: 0.875rem;
        color: var(--text-secondary);
        line-height: 1.4;
        margin: 0;
    }
    
    .matiere-config {
        display: flex;
        flex-direction: column;
        gap: var(--space-md);
        min-width: 280px;
    }
    
    .config-section {
        display: flex;
        flex-direction: column;
        gap: var(--space-sm);
    }
    
    .config-label {
        font-weight: 600;
        font-size: var(--text-sm);
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
    }
    
    .volume-config {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    
    .volume-input {
        flex: 1;
        min-width: 80px;
        padding: 0.5rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .volume-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        outline: none;
    }
    
    .volume-unit {
        font-size: var(--text-sm);
        color: var(--text-secondary);
        font-weight: 500;
    }
    
    .teacher-config {
        position: relative;
    }
    
    .teacher-config .form-select {
        padding: 0.5rem;
        border: 1px solid var(--border);
        border-radius: 6px;
        transition: all 0.2s;
    }
    
    .teacher-config .form-select:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        outline: none;
    }
    
    /* Modal loading state */
    .config-loading {
        min-height: 200px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .config-matiere-card {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
            grid-template-columns: 1fr;
        }
        
        .matiere-config {
            justify-content: center;
            min-width: auto;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-calendar-alt me-2"></i>{{ $emploiTemps->titre ?? 'Emploi du Temps' }}</h1>
                <p class="header-subtitle">Emploi du temps de {{ $emploiTemps->classe->name ?? 'Non défini' }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.emploi-temps.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
                <a href="{{ route('esbtp.emploi-temps.preview', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-acasi info" target="_blank">
                    <i class="fas fa-eye"></i>Prévisualiser PDF
                </a>
                <a href="{{ route('esbtp.emploi-temps.export-pdf', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-acasi danger" target="_blank">
                    <i class="fas fa-file-pdf"></i>Générer PDF
                </a>
                <a href="{{ route('esbtp.emploi-temps.edit', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-acasi warning">
                    <i class="fas fa-edit"></i>Modifier
                </a>
                <a href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id]) }}" class="btn-acasi primary">
                    <i class="fas fa-plus"></i>Ajouter une séance
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success border-start border-success border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                    <div>{{ session('success') }}</div>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fs-4"></i>
                    </div>
                    <div>{{ session('error') }}</div>
                </div>
            </div>
        @endif
        <!-- Section: Planification Académique -->
        <x-emploi-temps.planification-section 
            :planificationData="$planificationData" 
            :emploiTemps="$emploiTemps" />

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Attention</h5>
                <p>{{ session('warning') }}</p>
                @if (session('show_force_delete'))
                    <hr>
                    <div class="d-flex justify-content-end">
                        @if(auth()->user()->hasRole('superAdmin') && auth()->user()->can('delete_timetables'))
                        <form action="{{ route('esbtp.emploi-temps.destroy', ['emploi_temp' => $emploiTemps->id]) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="force_delete" value="1">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i>Confirmer la suppression forcée
                            </button>
                        </form>
                        @endif
                    </div>
                @endif
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Section: Informations et Statistiques -->
        <x-emploi-temps.info-stats-section 
            :emploiTemps="$emploiTemps" 
            :matiereStats="$matiereStats ?? []" />


        @php
            if (!isset($timeSlots) || !is_array($timeSlots) || empty($timeSlots)) {
                $timeSlots = [];
                for ($hour = 8; $hour < 18; $hour++) {
                    $timeSlots[] = sprintf('%02d:00', $hour);
                }
            }

            if (!isset($days) || !is_array($days) || empty($days)) {
                $days = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            }
        @endphp

        <!-- Section: Grille horaire (pleine largeur) -->
        <x-emploi-temps.grille-horaire 
            :seances="$emploiTemps->seances ?? collect()" 
            :emploiTemps="$emploiTemps"
            :timeSlots="$timeSlots"
            :days="$days" />

        <!-- Section: Liste des séances (pleine largeur) -->
        <x-emploi-temps.liste-seances 
            :seances="$emploiTemps->seances ?? collect()" 
            :emploiTemps="$emploiTemps" />
    </div>
</div>

<!-- Modal de Configuration des Volumes Horaires -->
<div class="modal fade" id="volumeConfigModal" tabindex="-1" aria-labelledby="volumeConfigModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="volumeConfigModalLabel">
                    <i class="fas fa-cog me-2"></i>
                    Configuration des Volumes Horaires
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="config-header mb-4">
                    <h6 class="mb-1">Combinaison sélectionnée</h6>
                    <p class="text-muted mb-0" id="config-combination-name">{{ $emploiTemps->classe->filiere->name ?? 'Filière' }} - {{ $emploiTemps->classe->niveau->name ?? 'Niveau' }}</p>
                </div>
                
                <form id="volume-config-form">
                    <input type="hidden" id="config-filiere-id" name="filiere_id" value="{{ $emploiTemps->classe->filiere_id ?? '' }}">
                    <input type="hidden" id="config-niveau-id" name="niveau_id" value="{{ $emploiTemps->classe->niveau_etude_id ?? '' }}">
                    <input type="hidden" id="config-annee-id" name="annee_id" value="{{ $emploiTemps->annee->id ?? '' }}">
                    
                    <div class="config-loading text-center py-4" id="config-loading" style="display: none;">
                        <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                        <p>Chargement des matières...</p>
                    </div>
                    
                    <div id="matieres-container">
                        <!-- Les matières seront chargées ici via AJAX -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-primary" id="save-volume-config">
                    <i class="fas fa-save me-1"></i>Sauvegarder
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Variables globales pour le modal de configuration
    let currentFiliereId = null;
    let currentNiveauId = null;
    let currentCombinaisonName = '';
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    // Fonction pour ouvrir le modal de configuration des volumes
    function openVolumeConfigModal(button) {
        const filiereId = button.getAttribute('data-filiere-id');
        const niveauId = button.getAttribute('data-niveau-id');
        const anneeId = button.getAttribute('data-annee-id');
        const combinationName = button.getAttribute('data-combination-name');
        
        // Stocker les valeurs globalement
        currentFiliereId = filiereId;
        currentNiveauId = niveauId;
        currentCombinaisonName = combinationName;
        
        // Mettre à jour les champs cachés
        $('#config-filiere-id').val(filiereId);
        $('#config-niveau-id').val(niveauId);
        $('#config-annee-id').val(anneeId);
        $('#config-combination-name').text(combinationName);
        
        // Charger les matières
        loadMatieresForConfiguration(filiereId, niveauId, anneeId);
    }
    
    // Fonction pour charger les matières via AJAX
    function loadMatieresForConfiguration(filiereId, niveauId, anneeId) {
        $('#config-loading').show();
        $('#matieres-container').empty();
        
        $.ajax({
            url: '{{ route("esbtp.planning-general.get-matieres-configuration") }}',
            method: 'GET',
            data: {
                filiere_id: filiereId,
                niveau_id: niveauId,
                annee_id: anneeId
            },
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                $('#config-loading').hide();
                
                if (response.success) {
                    $('#matieres-container').html(response.html).show();
                    
                    // Ajouter les event listeners sur les inputs
                    $('.volume-input').on('input', function() {
                        const $card = $(this).closest('.config-matiere-card');
                        const value = parseInt($(this).val()) || 0;
                        
                        if (value > 0) {
                            $card.addClass('configured');
                        } else {
                            $card.removeClass('configured');
                        }
                    });
                } else {
                    showAlert('error', response.message || 'Erreur lors du chargement des matières');
                    $('#matieres-container').html('<div class="text-center text-muted py-4">Erreur lors du chargement</div>').show();
                }
            },
            error: function(xhr) {
                $('#config-loading').hide();
                console.error('Erreur AJAX:', xhr);
                showAlert('error', 'Erreur de communication avec le serveur');
                $('#matieres-container').html('<div class="text-center text-muted py-4">Erreur de chargement</div>').show();
            }
        });
    }
    
    // Gestionnaire pour la sauvegarde des volumes
    $(document).on('click', '#save-volume-config', function() {
        const $btn = $(this);
        
        // Validation des champs requis
        if (!currentFiliereId || !currentNiveauId) {
            showAlert('error', 'Informations de combinaison manquantes');
            return;
        }
        
        // Collecter les données du formulaire
        const formData = {
            filiere_id: currentFiliereId,
            niveau_id: currentNiveauId,
            annee_id: $('#config-annee-id').val(),
            volumes: {},
            teachers: {}
        };
        
        // Collecter tous les volumes
        $('.volume-input').each(function() {
            const matiereId = $(this).attr('name').match(/volumes\[(\d+)\]/)[1];
            const volume = parseInt($(this).val()) || 0;
            formData.volumes[matiereId] = volume;
        });
        
        // Collecter toutes les assignations de professeurs
        $('.teacher-select').each(function() {
            const matiereId = $(this).attr('name').match(/teachers\[(\d+)\]/)[1];
            const selectedTeachers = $(this).val() || [];
            formData.teachers[matiereId] = selectedTeachers;
        });
        
        // Validation
        if (!formData.filiere_id || !formData.niveau_id || !formData.annee_id) {
            showAlert('error', 'Données manquantes pour la sauvegarde');
            return;
        }
        
        // Afficher loading sur le bouton
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Sauvegarde...');
        
        $.ajax({
            url: '{{ route("esbtp.planning-general.save-volume-configuration") }}',
            method: 'POST',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    
                    // Fermer le modal
                    $('#volumeConfigModal').modal('hide');
                    
                    // Recharger la page pour mettre à jour les données de planification
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showAlert('error', response.message || 'Erreur lors de la sauvegarde');
                }
            },
            error: function(xhr) {
                console.error('Erreur AJAX:', xhr);
                let message = 'Erreur lors de la sauvegarde';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    message = errors.join(', ');
                }
                
                showAlert('error', message);
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="fas fa-save me-1"></i>Sauvegarder');
            }
        });
    });
    
    // Reset du modal à la fermeture
    $('#volumeConfigModal').on('hidden.bs.modal', function() {
        $('#matieres-container').empty();
        $('#config-combination-name').text('-');
        currentFiliereId = null;
        currentNiveauId = null;
        currentCombinaisonName = '';
    });
    
    // Fonction utilitaire pour afficher les alertes
    function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        
        const $alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        // Insérer l'alerte au début du container principal
        $('.container-fluid').prepend($alert);
        
        // Auto-dismiss après 5 secondes
        setTimeout(() => {
            $alert.alert('close');
        }, 5000);
    }
</script>
@endsection
