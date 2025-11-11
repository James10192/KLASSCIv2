@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0">
                            @if(auth()->user()->hasRole('coordinateur'))
                                Notifications - Coordination
                            @else
                                Notifications
                            @endif
                        </h5>
                        @if(auth()->user()->hasRole('coordinateur'))
                            <small class="text-muted">Suivi des activités d'émargement et d'appel</small>
                        @endif
                    </div>
                    <div class="d-flex gap-2">
                        @if(auth()->user()->hasRole('coordinateur'))
                            {{-- Lien vers le tableau de bord des présences --}}
                            <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-chart-bar me-1"></i> Présences & Tableau de Bord
                            </a>
                            {{-- Filtres rapides pour coordinateur --}}
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm filter-notifications" data-filter="all">
                                    Toutes
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm filter-notifications" data-filter="émargement">
                                    Émargements
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm filter-notifications" data-filter="appel">
                                    Appels
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm filter-notifications" data-filter="retard">
                                    Retards
                                </button>
                            </div>
                        @endif
                        @if($notifications->where('is_read', false)->isNotEmpty())
                            <button class="btn btn-outline-secondary btn-sm mark-all-read">
                                <i class="fas fa-check-double me-1"></i> Tout marquer comme lu
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($notifications->isEmpty())
                        <div class="text-center p-5">
                            <div class="empty-state mb-3">
                                <i class="fas fa-bell-slash fa-3x text-muted"></i>
                            </div>
                            <h6 class="text-muted">Aucune notification</h6>
                            <p class="small text-muted">Vous n'avez pas encore reçu de notifications</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($notifications as $notification)
                                <div class="list-group-item notification-item {{ !$notification->is_read ? 'unread' : '' }}"
                                     id="notification-{{ $notification->id }}"
                                     @if($notification->link)
                                         onclick="window.location.href='{{ $notification->link }}'; markAsRead('{{ $notification->id }}');"
                                     @else
                                         onclick="markAsRead('{{ $notification->id }}');"
                                     @endif
                                     style="cursor: pointer;">
                                    <div class="d-flex align-items-start justify-content-between">
                                        <div class="flex-grow-1 me-3">
                                            <div class="d-flex align-items-center mb-2">
                                                @if($notification->type == 'danger' || $notification->type == 'error')
                                                    <span class="notification-icon bg-danger-light text-danger me-2">
                                                        <i class="fas fa-exclamation-circle"></i>
                                                    </span>
                                                @elseif($notification->type == 'warning')
                                                    <span class="notification-icon bg-warning-light text-warning me-2">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                @elseif($notification->type == 'success')
                                                    <span class="notification-icon bg-success-light text-success me-2">
                                                        <i class="fas fa-check-circle"></i>
                                                    </span>
                                                @else
                                                    <span class="notification-icon bg-info-light text-info me-2">
                                                        <i class="fas fa-info-circle"></i>
                                                    </span>
                                                @endif
                                                <h6 class="mb-0 fw-semibold">{{ $notification->title ?? 'Notification' }}</h6>
                                                @if(!$notification->is_read)
                                                    <span class="ms-2 badge bg-warning">Nouveau</span>
                                                @endif
                                            </div>
                                            <p class="notification-message">{{ $notification->message ?? '' }}</p>
                                            <div class="d-flex align-items-center mt-2">
                                                <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                                @if($notification->sender)
                                                    <small class="text-muted ms-2">• Par {{ $notification->sender->name }}</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-start gap-2 flex-shrink-0">
                                            {{-- Actions spécifiques selon le type de notification et le rôle --}}
                                            @if(auth()->user()->hasRole('coordinateur'))
                                                {{-- Actions pour coordinateurs --}}
                                                @if(str_contains(strtolower($notification->title ?? ''), 'émargement'))
                                                    <a href="{{ $notification->link ?? route('esbtp.teacher-attendance.report') }}" class="btn btn-info btn-sm" onclick="event.stopPropagation();">
                                                        <i class="fas fa-eye me-1"></i> Voir émargements
                                                    </a>
                                                @elseif(str_contains(strtolower($notification->title ?? ''), 'appel'))
                                                    <a href="{{ $notification->link ?? route('esbtp.attendances.index') }}" class="btn btn-success btn-sm" onclick="event.stopPropagation();">
                                                        <i class="fas fa-users me-1"></i> Voir présences
                                                    </a>
                                                @elseif(str_contains(strtolower($notification->title ?? ''), 'clôturé'))
                                                    <a href="{{ $notification->link ?? route('esbtp.attendances.index') }}" class="btn btn-primary btn-sm" onclick="event.stopPropagation();">
                                                        <i class="fas fa-check me-1"></i> Voir séances
                                                    </a>
                                                @elseif(str_contains(strtolower($notification->title ?? ''), 'retard'))
                                                    <a href="{{ $notification->link ?? route('esbtp.teacher-attendance.report') }}" class="btn btn-warning btn-sm" onclick="event.stopPropagation();">
                                                        <i class="fas fa-clock me-1"></i> Vérifier retards
                                                    </a>
                                                @elseif(str_contains(strtolower($notification->title ?? ''), 'récapitulatif'))
                                                    <a href="{{ $notification->link ?? route('esbtp.teacher-attendance.report') }}" class="btn btn-info btn-sm" onclick="event.stopPropagation();">
                                                        <i class="fas fa-chart-line me-1"></i> Voir rapport
                                                    </a>
                                                @endif
                                            @elseif(auth()->user()->hasRole('etudiant'))
                                                {{-- Actions pour étudiants --}}
                                                @if(str_contains(strtolower($notification->title ?? ''), 'absence'))
                                                    <a href="{{ $notification->link ?? route('esbtp.mes-absences.index') }}" class="btn btn-primary btn-sm" onclick="event.stopPropagation();">
                                                        <i class="fas fa-file-alt me-1"></i> Justifier l'absence
                                                    </a>
                                                @endif
                                            @endif
                                            
                                            {{-- Bouton de suppression pour tous les rôles --}}
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteNotificationPage({{ $notification->id }})" title="Supprimer cette notification">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="d-flex justify-content-center p-3">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
.notification-message {
    color: #495057;
    margin-bottom: 0;
}
.notification-item {
    padding: 15px;
    transition: all 0.2s ease;
}
.notification-item:hover {
    background-color: rgba(1, 99, 47, 0.05);
}
.notification-item.unread {
    background-color: rgba(242, 148, 0, 0.1);
    border-left: 3px solid #f29400;
}
.empty-state {
    padding: 20px;
    border-radius: 50%;
    background-color: #f8f9fa;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
.bg-danger-light {
    background-color: rgba(220, 53, 69, 0.1);
}
.bg-warning-light {
    background-color: rgba(255, 193, 7, 0.1);
}
.bg-success-light {
    background-color: rgba(40, 167, 69, 0.1);
}
.bg-info-light {
    background-color: rgba(23, 162, 184, 0.1);
}
</style>
@endpush

@push('scripts')
<script>
function markAsRead(id) {
    fetch(`{{ route('notifications.mark-as-read', '') }}/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(() => {
        const item = document.querySelector(`#notification-${id}`);
        if (item) {
            item.classList.remove('unread');
            const badge = item.querySelector('.badge');
            if (badge) badge.remove();
        }
    });
}

document.querySelector('.mark-all-read')?.addEventListener('click', function(e) {
    e.preventDefault();

    fetch('{{ route("notifications.mark-all-as-read") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(() => {
        document.querySelectorAll('.notification-item.unread').forEach(item => {
            item.classList.remove('unread');
            const badge = item.querySelector('.badge');
            if (badge) badge.remove();
        });

        // Masquer le bouton "Tout marquer comme lu" s'il n'y a plus de notifications non lues
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        if (unreadCount === 0) {
            this.style.display = 'none';
        }
    });
});

// Filtres pour coordinateurs
@if(auth()->user()->hasRole('coordinateur'))
document.querySelectorAll('.filter-notifications').forEach(button => {
    button.addEventListener('click', function() {
        // Réinitialiser l'état des boutons
        document.querySelectorAll('.filter-notifications').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('btn-outline-primary');
            btn.classList.remove('btn-primary');
        });
        
        // Marquer le bouton actuel comme actif
        this.classList.add('active');
        this.classList.remove('btn-outline-primary');
        this.classList.add('btn-primary');
        
        const filter = this.dataset.filter;
        const notifications = document.querySelectorAll('.notification-item');
        
        notifications.forEach(notification => {
            const title = notification.querySelector('h6').textContent.toLowerCase();
            
            if (filter === 'all') {
                notification.style.display = 'block';
            } else {
                if (title.includes(filter)) {
                    notification.style.display = 'block';
                } else {
                    notification.style.display = 'none';
                }
            }
        });
    });
});

// Auto-refresh pour les coordinateurs (toutes les 30 secondes)
@if(auth()->user()->hasRole('coordinateur'))
setInterval(function() {
    fetch('{{ route('notifications.unreadCount') }}')
        .then(response => response.json())
        .then(data => {
            // Mettre à jour le compteur si nécessaire
            const badge = document.querySelector('.notification-badge');
            if (badge && data.count > 0) {
                badge.textContent = data.count;
                badge.style.display = 'inline';
            }
        })
        .catch(console.error);
}, 30000);
@endif

// Fonction pour supprimer une notification depuis la page
function deleteNotificationPage(notificationId) {
    event.stopPropagation();
    
    if (confirm('Êtes-vous sûr de vouloir supprimer cette notification ?')) {
        fetch(`/notifications/${notificationId}/delete`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Supprimer visuellement l'élément
                const notificationElement = document.getElementById(`notification-${notificationId}`);
                if (notificationElement) {
                    notificationElement.style.transition = 'all 0.3s ease';
                    notificationElement.style.opacity = '0';
                    notificationElement.style.transform = 'translateX(-100%)';
                    
                    setTimeout(() => {
                        notificationElement.remove();
                        
                        // Vérifier s'il ne reste plus de notifications
                        const remainingNotifications = document.querySelectorAll('.notification-item');
                        if (remainingNotifications.length === 0) {
                            location.reload(); // Recharger pour afficher l'état vide
                        }
                    }, 300);
                }
                
                debugLog('✅ Notification supprimée:', notificationId);
            } else {
                alert('Erreur lors de la suppression de la notification.');
            }
        })
        .catch(error => {
            debugError('❌ Erreur suppression notification:', error);
            alert('Erreur lors de la suppression de la notification.');
        });
    }
}
@endif
</script>
@endpush
