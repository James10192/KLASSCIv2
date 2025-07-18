@extends('layouts.app')

@section('title', 'Tableau de bord Admin')

@section('content')
<div class="container-fluid">
    <!-- Hero Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 bg-gradient-primary text-white overflow-hidden animate__animated animate__fadeIn" 
                 style="background: linear-gradient(135deg, var(--esbtp-green), var(--esbtp-green-dark)); border-radius: 15px;">
                <div class="card-body p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h2 class="display-5 fw-bold mb-3 animate__animated animate__fadeInUp">Bienvenue, {{ Auth::user()->name }}</h2>
                            <p class="lead mb-4 animate__animated animate__fadeInUp animate__delay-1">Gérez efficacement votre établissement depuis votre tableau de bord administrateur.</p>
                            <div class="d-flex gap-2 animate__animated animate__fadeInUp animate__delay-2">
                                <a href="{{ route('students.index') }}" class="btn btn-light px-4 py-2">
                                    <i class="fas fa-user-graduate me-2"></i> Gérer les étudiants
                                </a>
                                <a href="{{ route('teachers.index') }}" class="btn btn-outline-light px-4 py-2">
                                    <i class="fas fa-chalkboard-teacher me-2"></i> Gérer les enseignants
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 d-none d-md-block position-relative">
                            <div class="position-absolute top-0 end-0 mt-3 me-4 animate__animated animate__fadeInRight">
                                <div class="d-flex align-items-center">
                                    <div class="bg-white rounded-circle p-3 me-3 shadow-sm">
                                        <i class="fas fa-calendar-alt text-success fa-2x"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-white">{{ now()->format('d M Y') }}</h6>
                                        <small class="text-white-50">{{ now()->format('l') }}</small>
                                    </div>
                                </div>
                            </div>
                            <img src="https://img.freepik.com/free-vector/college-university-students-group-young-happy-people-standing-isolated-white-background_575670-66.jpg" 
                                 alt="Education" class="img-fluid rounded-3 mt-3 animate__animated animate__fadeInUp" style="max-height: 200px; opacity: 0.9;">
                        </div>
                    </div>
                </div>
                <div class="position-absolute bottom-0 end-0 mb-n3 me-n3 d-none d-lg-block">
                    <svg width="200" height="200" viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="100" r="100" fill="rgba(255,255,255,0.1)"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card animate__animated animate__fadeInUp">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Étudiants</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $totalStudents ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-primary-light rounded-circle p-3">
                            <i class="fas fa-user-graduate text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 100%"></div>
                    </div>
                    <a href="{{ route('students.index') }}" class="stretched-link text-decoration-none">
                        <div class="d-flex align-items-center mt-3">
                            <small class="text-primary fw-semibold">Voir tous</small>
                            <i class="fas fa-arrow-right ms-auto text-primary"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card animate__animated animate__fadeInUp animate__delay-1">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Enseignants</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $totalTeachers ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-success-light rounded-circle p-3">
                            <i class="fas fa-chalkboard-teacher text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <a href="{{ route('teachers.index') }}" class="stretched-link text-decoration-none">
                        <div class="d-flex align-items-center mt-3">
                            <small class="text-success fw-semibold">Voir tous</small>
                            <i class="fas fa-arrow-right ms-auto text-success"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card animate__animated animate__fadeInUp animate__delay-2">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">Présences</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $todayAttendances ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-warning-light rounded-circle p-3">
                            <i class="fas fa-clipboard-check text-warning fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 100%"></div>
                    </div>
                    <a href="{{ route('attendance.mark-page') }}" class="stretched-link text-decoration-none">
                        <div class="d-flex align-items-center mt-3">
                            <small class="text-warning fw-semibold">Marquer</small>
                            <i class="fas fa-arrow-right ms-auto text-warning"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100 overflow-hidden stat-card animate__animated animate__fadeInUp animate__delay-3">
                <div class="card-body position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="text-uppercase fw-semibold text-muted mb-1">En attente</h6>
                            <h2 class="mb-0 display-5 fw-bold">{{ $pendingAttendances ?? 0 }}</h2>
                        </div>
                        <div class="stat-icon bg-danger-light rounded-circle p-3">
                            <i class="fas fa-clock text-danger fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-danger" role="progressbar" style="width: 100%"></div>
                    </div>
                    <a href="{{ route('attendance.mark-page') }}" class="stretched-link text-decoration-none">
                        <div class="d-flex align-items-center mt-3">
                            <small class="text-danger fw-semibold">Traiter</small>
                            <i class="fas fa-arrow-right ms-auto text-danger"></i>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides et Notifications -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-bolt text-warning me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Actions rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="{{ route('attendance.mark-page') }}" class="card action-card border-0 shadow-sm h-100 text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="action-icon bg-primary-light rounded-circle mx-auto mb-3">
                                        <i class="fas fa-clipboard-check text-primary"></i>
                                    </div>
                                    <h6 class="fw-semibold">Marquer les présences</h6>
                                    <p class="text-muted small mb-0">Enregistrer les présences des étudiants</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('grades.index') }}" class="card action-card border-0 shadow-sm h-100 text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="action-icon bg-success-light rounded-circle mx-auto mb-3">
                                        <i class="fas fa-graduation-cap text-success"></i>
                                    </div>
                                    <h6 class="fw-semibold">Gérer les notes</h6>
                                    <p class="text-muted small mb-0">Consulter et modifier les notes</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('notifications.create') }}" class="card action-card border-0 shadow-sm h-100 text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="action-icon bg-info-light rounded-circle mx-auto mb-3">
                                        <i class="fas fa-bell text-info"></i>
                                    </div>
                                    <h6 class="fw-semibold">Notifications</h6>
                                    <p class="text-muted small mb-0">Envoyer des notifications</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('timetables.index') }}" class="card action-card border-0 shadow-sm h-100 text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="action-icon bg-warning-light rounded-circle mx-auto mb-3">
                                        <i class="fas fa-calendar-alt text-warning"></i>
                                    </div>
                                    <h6 class="fw-semibold">Emplois du temps</h6>
                                    <p class="text-muted small mb-0">Gérer les emplois du temps</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('esbtp.paiements.index') }}" class="card action-card border-0 shadow-sm h-100 text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="action-icon bg-danger-light rounded-circle mx-auto mb-3">
                                        <i class="fas fa-credit-card text-danger"></i>
                                    </div>
                                    <h6 class="fw-semibold">Suivi Paiements</h6>
                                    <p class="text-muted small mb-0">Monitoring des paiements étudiants</p>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('esbtp.frais.index') }}" class="card action-card border-0 shadow-sm h-100 text-decoration-none">
                                <div class="card-body text-center p-4">
                                    <div class="action-icon bg-purple-light rounded-circle mx-auto mb-3">
                                        <i class="fas fa-money-bill text-purple"></i>
                                    </div>
                                    <h6 class="fw-semibold">Gestion Frais</h6>
                                    <p class="text-muted small mb-0">Configuration des frais scolaires</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-bell text-danger me-2"></i>
                        <h5 class="card-title mb-0 fw-bold">Notifications récentes</h5>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                        Voir toutes
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="notification-list">
                        @forelse($recentNotifications as $notification)
                            <div class="notification-item p-3 border-bottom">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="notification-icon bg-light rounded-circle p-2">
                                            <i class="fas fa-info-circle text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1 fw-semibold">{{ $notification->title }}</h6>
                                        <p class="mb-1 text-muted small">{{ Str::limit($notification->message, 80) }}</p>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                            <span class="ms-auto">
                                                <a href="#" class="btn btn-sm btn-link text-decoration-none p-0">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <i class="fas fa-bell-slash fa-3x text-muted"></i>
                                </div>
                                <h6 class="text-muted">Aucune notification récente</h6>
                                <p class="text-muted small mb-0">Les nouvelles notifications apparaîtront ici</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messagerie interne -->
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex align-items-center">
                    <i class="fas fa-envelope text-info me-2"></i>
                    <h5 class="card-title mb-0 fw-bold">Messagerie interne</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="card border shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 fw-semibold">Envoyer un message</h6>
                                </div>
                                <div class="card-body">
                                    <form action="{{ route('messages.send-to-group') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="recipient_type" class="form-label">Destinataire</label>
                                            <select class="form-select form-select-sm" id="recipient_type" name="recipient_type" required>
                                                <option value="">Sélectionner un destinataire</option>
                                                <option value="all">Tous les utilisateurs</option>
                                                <option value="students">Tous les étudiants</option>
                                                <option value="teachers">Tous les enseignants</option>
                                                <option value="admins">Tous les administrateurs</option>
                                                <option value="class">Une classe spécifique</option>
                                            </select>
                                        </div>
                                        <div class="mb-3" id="recipient_group_container" style="display: none;">
                                            <label for="recipient_group" class="form-label">Identifiant du groupe</label>
                                            <input type="text" class="form-control form-control-sm" id="recipient_group" name="recipient_group">
                                        </div>
                                        <div class="mb-3">
                                            <label for="subject" class="form-label">Sujet</label>
                                            <input type="text" class="form-control form-control-sm" id="subject" name="subject" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="content" class="form-label">Message</label>
                                            <textarea class="form-control form-control-sm" id="content" name="content" rows="4" required></textarea>
                                        </div>
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane me-2"></i> Envoyer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="card border shadow-sm h-100">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-semibold">Messages récents</h6>
                                    <a href="{{ route('messages.inbox') }}" class="btn btn-sm btn-outline-primary rounded-pill">
                                        Voir tous
                                    </a>
                                </div>
                                <div class="card-body p-0">
                                    <div class="message-list">
                                        @forelse($recentMessages as $message)
                                            <a href="{{ route('messages.show', $message) }}" class="message-item p-3 border-bottom d-block text-decoration-none">
                                                <div class="d-flex align-items-center mb-2">
                                                    <div class="message-sender fw-semibold text-dark">{{ $message->subject }}</div>
                                                    <div class="ms-auto">
                                                        <span class="badge bg-light text-dark rounded-pill">{{ $message->created_at->diffForHumans() }}</span>
                                                    </div>
                                                </div>
                                                <p class="message-preview text-muted mb-1 small">{{ Str::limit($message->content, 100) }}</p>
                                                <div class="message-meta small">
                                                    <span class="text-primary">
                                                        @if($message->isGroupMessage())
                                                            Envoyé à: 
                                                            @if($message->recipient_type == 'all')
                                                                <span class="badge bg-info text-white">Tous les utilisateurs</span>
                                                            @elseif($message->recipient_type == 'students')
                                                                <span class="badge bg-primary text-white">Tous les étudiants</span>
                                                            @elseif($message->recipient_type == 'teachers')
                                                                <span class="badge bg-success text-white">Tous les enseignants</span>
                                                            @elseif($message->recipient_type == 'admins')
                                                                <span class="badge bg-danger text-white">Tous les administrateurs</span>
                                                            @elseif($message->recipient_type == 'class')
                                                                <span class="badge bg-warning text-dark">Classe #{{ $message->recipient_group }}</span>
                                                            @endif
                                                        @else
                                                            Envoyé à: {{ $message->recipient->name ?? 'Destinataire inconnu' }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="text-center py-5">
                                                <div class="mb-3">
                                                    <i class="fas fa-envelope-open fa-3x text-muted"></i>
                                                </div>
                                                <h6 class="text-muted">Aucun message récent</h6>
                                                <p class="text-muted small mb-0">Envoyez un message pour commencer une conversation</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Styles pour les cartes statistiques */
    .stat-card {
        transition: all 0.3s ease;
        border-radius: 15px;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .bg-primary-light {
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .bg-success-light {
        background-color: rgba(25, 135, 84, 0.1);
    }
    
    .bg-warning-light {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .bg-danger-light {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .bg-info-light {
        background-color: rgba(13, 202, 240, 0.1);
    }
    
    /* Styles pour les cartes d'action */
    .action-card {
        transition: all 0.3s ease;
        border-radius: 15px;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
    }
    
    .action-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    /* Styles pour les notifications */
    .notification-list {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notification-item {
        transition: all 0.2s ease;
    }
    
    .notification-item:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    .notification-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Styles pour les messages */
    .message-list {
        max-height: 500px;
        overflow-y: auto;
    }
    
    .message-item {
        transition: all 0.2s ease;
    }
    
    .message-item:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const recipientTypeSelect = document.getElementById('recipient_type');
        const recipientGroupContainer = document.getElementById('recipient_group_container');
        
        // Afficher/masquer le champ de groupe en fonction du type de destinataire
        recipientTypeSelect.addEventListener('change', function() {
            if (this.value === 'class') {
                recipientGroupContainer.style.display = 'block';
            } else {
                recipientGroupContainer.style.display = 'none';
            }
        });
    });
</script>
@endsection 