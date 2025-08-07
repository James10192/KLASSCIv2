@extends('layouts.app')

@section('title', 'Émargement')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .attendance-form-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-elevated);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }

    .attendance-form-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: var(--space-lg);
        text-align: center;
    }

    .attendance-form-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-md);
    }

    .code-input-group {
        position: relative;
        margin: var(--space-lg) 0;
    }

    .code-input {
        font-family: 'Courier New', monospace;
        font-size: 2rem;
        font-weight: 700;
        text-align: center;
        padding: var(--space-lg);
        border: 2px solid #e5e7eb;
        border-radius: var(--radius-medium);
        background: #f8fafc;
        transition: all 0.3s ease;
        width: 100%;
        text-transform: uppercase;
        letter-spacing: 0.5em;
    }

    .code-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        background: white;
    }

    .sign-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: var(--space-sm);
        padding: var(--space-lg) var(--space-xl);
        background: linear-gradient(135deg, var(--success), #059669);
        color: white;
        border: none;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        width: 100%;
        margin-top: var(--space-md);
    }

    .sign-btn:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
    }

    .sign-btn:disabled {
        background: #d1d5db;
        color: #9ca3af;
        cursor: not-allowed;
    }

    .course-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }

    .course-header {
        background: linear-gradient(135deg, var(--accent-blue), #0891b2);
        color: white;
        padding: var(--space-lg);
    }

    .course-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .course-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.2s ease;
    }

    .course-item:hover {
        background: #f8fafc;
    }

    .course-item:last-child {
        border-bottom: none;
    }

    .course-time {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: var(--primary);
        min-width: 100px;
    }

    .course-info h6 {
        margin: 0;
        font-weight: 600;
        color: var(--text-primary);
    }

    .course-info small {
        color: var(--text-secondary);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: var(--space-xs) var(--space-md);
        border-radius: var(--radius-large);
        font-size: var(--text-small);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .status-badge.present { 
        background-color: rgba(16, 185, 129, 0.1); 
        color: var(--success); 
        border: 1px solid rgba(16, 185, 129, 0.2); 
    }

    .status-badge.late { 
        background-color: rgba(245, 158, 11, 0.1); 
        color: var(--warning); 
        border: 1px solid rgba(245, 158, 11, 0.2); 
    }

    .status-badge.not-signed { 
        background-color: rgba(107, 114, 128, 0.1); 
        color: var(--neutral); 
        border: 1px solid rgba(107, 114, 128, 0.2); 
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: var(--space-lg);
        margin: var(--space-lg) 0;
    }

    .stat-item {
        text-align: center;
        padding: var(--space-lg);
        border-radius: var(--radius-medium);
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border: 1px solid #e5e7eb;
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }

    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        font-weight: 500;
    }

    .location-status {
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        margin: var(--space-md) 0;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .location-status.info {
        background: rgba(6, 182, 212, 0.1);
        color: var(--accent-blue);
        border: 1px solid rgba(6, 182, 212, 0.2);
    }

    .location-status.error {
        background: rgba(239, 68, 68, 0.1);
        color: var(--danger);
        border: 1px solid rgba(239, 68, 68, 0.2);
    }

    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: var(--space-lg);
    }

    .dashboard-header {
        text-align: center;
        margin-bottom: var(--space-xl);
    }

    .dashboard-title {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: var(--space-sm);
    }

    .dashboard-subtitle {
        color: var(--text-secondary);
        font-size: var(--text-normal);
    }

    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        color: var(--text-secondary);
    }

    .empty-state i {
        font-size: 3rem;
        color: var(--text-muted);
        margin-bottom: var(--space-md);
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <h1 class="dashboard-title">
            <i class="fas fa-user-check"></i>
            Émargement enseignant
        </h1>
        <p class="dashboard-subtitle">Signez votre présence pour les cours d'aujourd'hui</p>
    </div>

    <div class="row">
        <!-- Formulaire d'émargement -->
        <div class="col-lg-5">
            <div class="attendance-form-card">
                <div class="attendance-form-header">
                    <h2 class="attendance-form-title">
                        <i class="fas fa-signature"></i>
                        Signer ma présence
                    </h2>
                </div>
                <div class="p-4">
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

                    <form id="attendanceForm" action="{{ route('esbtp.teacher.attendance.sign') }}" method="POST">
                        @csrf
                        <div class="code-input-group">
                            <label class="form-label" style="font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-md);">
                                <i class="fas fa-key me-2"></i>
                                Saisissez le code d'émargement du jour
                            </label>
                            <input type="text" 
                                   class="code-input @error('code') is-invalid @enderror"
                                   name="code" 
                                   maxlength="6" 
                                   required 
                                   placeholder="XXXXXX"
                                   autocomplete="off">
                            @error('code')
                                <div class="invalid-feedback" style="text-align: center; margin-top: var(--space-sm);">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="sign-btn" id="signButton">
                            <i class="fas fa-signature"></i>
                            <span>Signer ma présence</span>
                        </button>

                        <input type="hidden" name="latitude" id="latitude">
                        <input type="hidden" name="longitude" id="longitude">
                        <input type="hidden" name="accuracy" id="accuracy">
                    </form>

                    <div id="locationStatus" class="location-status d-none">
                        <i class="fas fa-location-arrow"></i>
                        <span>Récupération de votre position...</span>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="course-card">
                <div class="course-header">
                    <h2 class="course-title">
                        <i class="fas fa-chart-pie"></i>
                        Mes statistiques
                    </h2>
                </div>
                <div class="p-4">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                            <div class="stat-label">Total</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $stats['present'] ?? 0 }}</div>
                            <div class="stat-label">À l'heure</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">{{ $stats['late'] ?? 0 }}</div>
                            <div class="stat-label">En retard</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cours du jour et historique -->
        <div class="col-lg-7">
            <div class="course-card">
                <div class="course-header">
                    <h2 class="course-title">
                        <i class="fas fa-calendar-day"></i>
                        Mes cours aujourd'hui
                    </h2>
                </div>
                <div class="p-0">
                    @if($todayCourses && $todayCourses->count() > 0)
                        @foreach($todayCourses as $course)
                            <div class="course-item">
                                <div class="course-time">
                                    {{ $course->heure_debut ? $course->heure_debut->format('H:i') : 'N/A' }} - 
                                    {{ $course->heure_fin ? $course->heure_fin->format('H:i') : 'N/A' }}
                                </div>
                                <div class="course-info">
                                    <h6>{{ $course->matiere->nom ?? 'Matière non définie' }}</h6>
                                    <small>{{ $course->classe->nom ?? 'Classe non définie' }}</small>
                                </div>
                                <div>
                                    @if($course->teacherAttendance && $course->teacherAttendance->count() > 0)
                                        @php $attendance = $course->teacherAttendance->first(); @endphp
                                        @if($attendance->status === 'present')
                                            <span class="status-badge present">
                                                <i class="fas fa-check-circle me-1"></i>À l'heure
                                            </span>
                                        @else
                                            <span class="status-badge late">
                                                <i class="fas fa-clock me-1"></i>En retard
                                            </span>
                                        @endif
                                    @else
                                        <span class="status-badge not-signed">
                                            <i class="fas fa-minus-circle me-1"></i>Non émargé
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>Aucun cours programmé aujourd'hui</p>
                            <p class="text-muted">Profitez de votre journée libre !</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Derniers émargements -->
            <div class="course-card mt-4">
                <div class="course-header">
                    <h2 class="course-title">
                        <i class="fas fa-history"></i>
                        Derniers émargements
                    </h2>
                </div>
                <div class="p-0">
                    @if(isset($recentAttendances) && $recentAttendances->count() > 0)
                        @foreach($recentAttendances as $attendance)
                            <div class="course-item">
                                <div class="course-time">
                                    {{ $attendance->validated_at->format('d/m H:i') }}
                                </div>
                                <div class="course-info">
                                    <h6>{{ $attendance->emploiDuTemps->matiere->nom ?? 'Matière' }}</h6>
                                    <small>{{ $attendance->emploiDuTemps->classe->nom ?? 'Classe' }}</small>
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    @if($attendance->status === 'present')
                                        <span class="status-badge present">
                                            <i class="fas fa-check-circle me-1"></i>À l'heure
                                        </span>
                                    @else
                                        <span class="status-badge late">
                                            <i class="fas fa-clock me-1"></i>En retard
                                        </span>
                                    @endif
                                    
                                    @if($attendance->validation_status === 'validated')
                                        <span class="status-badge present" style="font-size: 10px;">
                                            <i class="fas fa-check me-1"></i>Validé
                                        </span>
                                    @elseif($attendance->validation_status === 'rejected')
                                        <span class="status-badge" style="background-color: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); font-size: 10px;">
                                            <i class="fas fa-times me-1"></i>Rejeté
                                        </span>
                                    @else
                                        <span class="status-badge" style="background-color: rgba(6, 182, 212, 0.1); color: var(--accent-blue); border: 1px solid rgba(6, 182, 212, 0.2); font-size: 10px;">
                                            <i class="fas fa-hourglass-half me-1"></i>Attente
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        <div class="p-3 text-center border-top">
                            <a href="{{ route('esbtp.teacher.attendance.history') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list me-2"></i>Voir tout l'historique
                            </a>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="fas fa-history"></i>
                            <p>Aucun émargement récent</p>
                            <p class="text-muted">Vos derniers émargements apparaîtront ici</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('attendanceForm');
    const locationStatus = document.getElementById('locationStatus');
    const signButton = document.getElementById('signButton');
    const codeInput = form.querySelector('input[name="code"]');
    let watchId = null;
    let locationObtained = false;

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

    // Format code input automatically (uppercase, max 6 chars)
    codeInput.addEventListener('input', function(e) {
        let value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        if (value.length > 6) value = value.substring(0, 6);
        e.target.value = value;
        
        // Auto-submit when 6 characters are entered
        if (value.length === 6 && locationObtained) {
            form.submit();
        }
    });

    // Function to update location coordinates
    function updateLocation(position) {
        document.getElementById('latitude').value = position.coords.latitude;
        document.getElementById('longitude').value = position.coords.longitude;
        document.getElementById('accuracy').value = position.coords.accuracy;
        
        locationStatus.classList.add('d-none');
        locationObtained = true;
        signButton.disabled = false;
        
        // Update button text to show location is ready
        signButton.querySelector('span').textContent = 'Position obtenue - Prêt à signer';
        signButton.style.background = 'linear-gradient(135deg, var(--success), #059669)';
        
        // If code is complete, auto-submit
        if (codeInput.value.length === 6) {
            setTimeout(() => form.submit(), 500);
        }
        
        // Clear watch after successful location
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
    }

    // Function to handle geolocation errors
    function handleLocationError(error) {
        let message = 'Erreur de géolocalisation: ';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                message = 'Autorisez la géolocalisation pour pouvoir vous émarger';
                break;
            case error.POSITION_UNAVAILABLE:
                message = 'Position GPS non disponible. Réessayez dans quelques instants.';
                break;
            case error.TIMEOUT:
                message = 'Délai d\'attente GPS dépassé. Réessayez.';
                break;
            default:
                message = 'Erreur GPS inconnue. Contactez l\'administrateur.';
        }
        
        locationStatus.querySelector('span').textContent = message;
        locationStatus.classList.remove('d-none', 'info');
        locationStatus.classList.add('error');
        signButton.disabled = true;
        signButton.querySelector('span').textContent = 'Géolocalisation requise';
    }

    // Start geolocation on page load
    if (navigator.geolocation) {
        locationStatus.classList.remove('d-none');
        signButton.disabled = true;
        signButton.querySelector('span').textContent = 'Obtention de votre position...';
        
        watchId = navigator.geolocation.watchPosition(updateLocation, handleLocationError, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 30000
        });
    } else {
        handleLocationError({ code: 0, message: 'Géolocalisation non supportée' });
    }

    // Handle form submission
    form.addEventListener('submit', function(e) {
        if (!locationObtained) {
            e.preventDefault();
            locationStatus.classList.remove('d-none');
            locationStatus.classList.add('info');
            locationStatus.querySelector('span').textContent = 'Veuillez patienter, obtention de votre position...';
            return;
        }
        
        if (codeInput.value.length !== 6) {
            e.preventDefault();
            codeInput.focus();
            codeInput.style.borderColor = 'var(--danger)';
            setTimeout(() => {
                codeInput.style.borderColor = '';
            }, 3000);
            return;
        }
        
        // Disable button and show loading state
        signButton.disabled = true;
        signButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Vérification en cours...</span>';
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
        }
    });
});
</script>
@endsection
