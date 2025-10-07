@extends('layouts.app')

@section('title', 'Faire l\'appel')

@php
    $callType = request()->get('type', 'start');
    $callTypeText = $callType === 'start' ? 'de début' : 'de fin';
    $callTypeIcon = $callType === 'start' ? 'fa-play' : 'fa-stop';
@endphp

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .roll-call-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: var(--space-lg);
    }

    .course-info-card {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        text-align: center;
    }

    .course-info-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin-bottom: var(--space-sm);
    }

    .course-info-details {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        flex-wrap: wrap;
        margin-top: var(--space-md);
    }

    .course-info-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .roll-call-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .roll-call-header {
        background: linear-gradient(135deg, var(--accent-blue), #0891b2);
        color: white;
        padding: var(--space-lg);
    }

    .roll-call-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .student-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.2s ease;
    }

    .student-item:hover {
        background: #f8fafc;
    }

    .student-item:last-child {
        border-bottom: none;
    }

    .student-info {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 16px;
    }

    .student-details h6 {
        margin: 0;
        font-weight: 600;
        color: var(--text-primary);
    }

    .student-details small {
        color: var(--text-secondary);
    }

    .attendance-options {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
    }

    .attendance-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 16px;
        border: 2px solid transparent;
        border-radius: 8px;
        background: transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 13px;
        font-weight: 500;
        min-width: 90px;
        position: relative;
    }

    .attendance-btn input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .attendance-btn.present {
        border-color: #10b981;
        color: #10b981;
        background: rgba(16, 185, 129, 0.05);
    }

    .attendance-btn.present.active {
        background: #10b981 !important;
        color: white !important;
        border-color: #10b981 !important;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4) !important;
    }

    .attendance-btn.absent {
        border-color: #ef4444;
        color: #ef4444;
        background: rgba(239, 68, 68, 0.05);
    }

    .attendance-btn.absent.active {
        background: #ef4444 !important;
        color: white !important;
        border-color: #ef4444 !important;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4) !important;
    }

    .attendance-btn.late {
        border-color: #f59e0b;
        color: #f59e0b;
        background: rgba(245, 158, 11, 0.05);
    }

    .attendance-btn.late.active {
        background: #f59e0b !important;
        color: white !important;
        border-color: #f59e0b !important;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4) !important;
    }

    .attendance-btn:hover:not(.active) {
        transform: scale(1.02);
        opacity: 0.8;
    }

    .action-buttons {
        padding: var(--space-lg);
        display: flex;
        justify-content: center;
        gap: var(--space-md);
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-md) var(--space-xl);
        border: none;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-modern.primary {
        background: linear-gradient(135deg, var(--success), #059669);
        color: white;
        box-shadow: var(--shadow-card);
    }

    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .btn-modern.secondary {
        background: linear-gradient(135deg, var(--neutral), #6b7280);
        color: white;
    }

    .btn-modern.secondary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: var(--space-md);
        margin: var(--space-lg) 0;
        padding: var(--space-lg);
        background: #f8fafc;
        border-radius: var(--radius-medium);
    }

    .stat-item {
        text-align: center;
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        background: white;
        border: 1px solid #e5e7eb;
    }

    .stat-value {
        font-size: var(--amount-medium);
        font-weight: 700;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }

    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        font-weight: 500;
    }

    .already-done-notice {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        text-align: center;
    }
</style>
@endsection

@section('content')
<div class="roll-call-container">
    <!-- Information du cours -->
    <div class="course-info-card">
        <h1 class="course-info-title">
            <i class="fas {{ $callTypeIcon }}"></i>
            Appel {{ $callTypeText }}
        </h1>
        <div class="course-info-details">
            <div class="course-info-item">
                <i class="fas fa-book"></i>
                <span>{{ $seance->matiere->name ?? 'Matière non définie' }}</span>
            </div>
            <div class="course-info-item">
                <i class="fas fa-users"></i>
                <span>{{ $seance->classe->name ?? 'Classe non définie' }}</span>
            </div>
            <div class="course-info-item">
                <i class="fas fa-clock"></i>
                <span>
                    {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }} - 
                    {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
                </span>
            </div>
            <div class="course-info-item">
                <i class="fas fa-calendar"></i>
                <span>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($hasRollCall)
        <div class="already-done-notice">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Appel déjà effectué</strong> - Vous pouvez modifier les présences si nécessaire
        </div>
    @endif

    <form id="rollCallForm" method="POST" action="{{ route('teacher.roll-call.store', $seance->id) }}">
        @csrf
        <input type="hidden" name="call_type" value="{{ $callType }}">
        
        <div class="roll-call-card">
            <div class="roll-call-header">
                <h2 class="roll-call-title">
                    <i class="fas {{ $callTypeIcon }}"></i>
                    Appel {{ $callTypeText }} - {{ $etudiants->count() }} étudiants
                </h2>
            </div>
            
            <div class="p-0">
                @forelse($etudiants as $etudiant)
                    @php
                        $existingAttendance = $existingAttendances->where('etudiant_id', $etudiant->id)->first();
                        $currentStatus = $existingAttendance ? $existingAttendance->status : 'present';
                    @endphp
                    <div class="student-item">
                        <div class="student-info">
                            <div class="student-avatar">
                                {{ substr($etudiant->prenoms ?? $etudiant->nom ?? 'E', 0, 1) }}
                            </div>
                            <div class="student-details">
                                <h6>{{ ($etudiant->prenoms && $etudiant->nom) ? $etudiant->prenoms . ' ' . $etudiant->nom : ($etudiant->user->name ?? 'Nom non défini') }}</h6>
                                <small>{{ $etudiant->matricule ?? 'Matricule non défini' }}</small>
                            </div>
                        </div>
                        <div class="attendance-options">
                            <label class="attendance-btn present {{ $currentStatus === 'present' ? 'active' : '' }}" for="present_{{ $etudiant->id }}">
                                <input type="radio" name="attendances[{{ $etudiant->id }}]" value="present" id="present_{{ $etudiant->id }}" style="display: none;" {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                <i class="fas fa-check"></i>
                                <span>Présent</span>
                            </label>
                            @if($callType === 'start')
                            <label class="attendance-btn late {{ $currentStatus === 'late' ? 'active' : '' }}" for="late_{{ $etudiant->id }}">
                                <input type="radio" name="attendances[{{ $etudiant->id }}]" value="late" id="late_{{ $etudiant->id }}" style="display: none;" {{ $currentStatus === 'late' ? 'checked' : '' }}>
                                <i class="fas fa-clock"></i>
                                <span>Retard</span>
                            </label>
                            @endif
                            <label class="attendance-btn absent {{ $currentStatus === 'absent' ? 'active' : '' }}" for="absent_{{ $etudiant->id }}">
                                <input type="radio" name="attendances[{{ $etudiant->id }}]" value="absent" id="absent_{{ $etudiant->id }}" style="display: none;" {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                <i class="fas fa-times"></i>
                                <span>Absent</span>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="empty-state p-5">
                        <i class="fas fa-users-slash"></i>
                        <p>Aucun étudiant inscrit dans cette classe</p>
                        <p class="text-muted">Contactez l'administration pour vérifier les inscriptions</p>
                    </div>
                @endforelse
            </div>

            @if($etudiants->count() > 0)
                <!-- Résumé des statistiques -->
                <div class="stats-summary" id="attendanceStats">
                    <div class="stat-item">
                        <div class="stat-value" id="presentCount">0</div>
                        <div class="stat-label">Présents</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="lateCount">0</div>
                        <div class="stat-label">En retard</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="absentCount">0</div>
                        <div class="stat-label">Absents</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="totalCount">{{ $etudiants->count() }}</div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="action-buttons">
                    <button type="submit" class="btn-modern primary">
                        <i class="fas fa-save"></i>
                        <span>{{ $hasRollCall ? 'Mettre à jour' : 'Enregistrer' }} l'appel</span>
                    </button>
                    <a href="{{ route('teacher.select-call-type', $seance->id) }}" class="btn-modern secondary">
                        <i class="fas fa-arrow-left"></i>
                        <span>Retour à la sélection</span>
                    </a>
                </div>
            @endif
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    console.log('🎯 Initialisation du système d\'appel...');

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        });
    }, 5000);

    // Handle attendance button clicks with SIMPLE event handling
    const attendanceButtons = document.querySelectorAll('.attendance-btn');
    console.log('📊 Nombre de boutons trouvés:', attendanceButtons.length);

    attendanceButtons.forEach(function(button, index) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            console.log('🖱️ Clic sur bouton', index + 1);

            // Get the student's other buttons
            const studentItem = this.closest('.student-item');
            if (!studentItem) {
                console.error('❌ Impossible de trouver student-item');
                return;
            }

            const allButtons = studentItem.querySelectorAll('.attendance-btn');

            // Remove active class from all buttons for this student
            allButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });

            // Add active class to clicked button
            this.classList.add('active');
            console.log('✅ Classe active ajoutée');

            // Check the radio button inside this label
            const radioInput = this.querySelector('input[type="radio"]');
            if (radioInput) {
                radioInput.checked = true;
                console.log('✅ Radio checked:', radioInput.value);
            } else {
                console.error('❌ Radio input non trouvé');
            }

            // Update statistics
            updateStats();
        }, true); // Use capture phase
    });

    // Function to update attendance statistics
    function updateStats() {
        let presentCount = 0;
        let lateCount = 0;
        let absentCount = 0;
        
        document.querySelectorAll('.attendance-btn.active').forEach(activeBtn => {
            if (activeBtn.classList.contains('present')) presentCount++;
            else if (activeBtn.classList.contains('late')) lateCount++;
            else if (activeBtn.classList.contains('absent')) absentCount++;
        });
        
        document.getElementById('presentCount').textContent = presentCount;
        document.getElementById('lateCount').textContent = lateCount;
        document.getElementById('absentCount').textContent = absentCount;
    }

    // Bulk actions
    const form = document.getElementById('rollCallForm');
    
    // Add bulk action buttons
    const rollCallHeader = document.querySelector('.roll-call-header');
    const bulkActions = document.createElement('div');
    bulkActions.style.cssText = 'display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; justify-content: center;';
    bulkActions.innerHTML = `
        <button type="button" class="btn btn-sm btn-success" onclick="bulkSetAttendance('present')">
            <i class="fas fa-check me-1"></i>Tous présents
        </button>
        <button type="button" class="btn btn-sm btn-warning" onclick="bulkSetAttendance('late')">
            <i class="fas fa-clock me-1"></i>Tous en retard
        </button>
        <button type="button" class="btn btn-sm btn-danger" onclick="bulkSetAttendance('absent')">
            <i class="fas fa-times me-1"></i>Tous absents
        </button>
    `;
    rollCallHeader.appendChild(bulkActions);

    // Bulk set attendance function
    window.bulkSetAttendance = function(status) {
        document.querySelectorAll('.attendance-btn.' + status).forEach(button => {
            button.click();
        });
    };

    // Initialize stats
    updateStats();
    console.log('✅ Stats initialisées');

    // Form submission confirmation
    if (form) {
        form.addEventListener('submit', function(e) {
            const activeButtons = document.querySelectorAll('.attendance-btn.active');
            if (activeButtons.length === 0) {
                e.preventDefault();
                alert('Veuillez marquer au moins un étudiant avant d\'enregistrer.');
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Enregistrement...</span>';
            }
        });
    }

    console.log('✅ Système d\'appel prêt !');
})();
</script>
@endpush