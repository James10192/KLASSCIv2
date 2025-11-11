@extends('layouts.app')

@section('title', 'Mes Messages')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .messages-page {
        background-color: var(--background);
        min-height: 100vh;
        padding: var(--space-lg);
    }

    .messages-header {
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        box-shadow: var(--shadow-card);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--space-md);
    }

    .header-title {
        font-size: var(--title-main);
        font-weight: 700;
        color: var(--primary);
        margin: 0;
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .header-subtitle {
        color: var(--text-secondary);
        font-size: var(--text-normal);
        margin: var(--space-xs) 0 0 0;
    }

    .filters-container {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-btn {
        background-color: transparent;
        border: 1px solid var(--accent-blue);
        color: var(--accent-blue);
        padding: var(--space-sm) var(--space-md);
        border-radius: var(--radius-large);
        font-size: var(--text-small);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        gap: var(--space-xs);
        text-decoration: none;
    }

    .filter-btn:hover {
        background-color: var(--accent-blue);
        color: white;
        transform: translateY(-1px);
        box-shadow: var(--shadow-elevated);
    }

    .filter-btn.active {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    .filter-badge {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 2px 8px;
        border-radius: var(--radius-circle);
        font-size: 11px;
        font-weight: 600;
        margin-left: var(--space-xs);
    }

    .messages-grid {
        display: grid;
        gap: var(--space-md);
    }

    .message-card {
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        overflow: hidden;
        transition: all 0.2s ease;
        position: relative;
    }

    .message-card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-1px);
    }

    .message-card.unread {
        border-left: 4px solid var(--accent-orange);
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.02), rgba(249, 115, 22, 0.01));
    }

    .message-card.urgent {
        border-left: 4px solid var(--danger);
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.05), rgba(239, 68, 68, 0.02));
    }

    .message-header {
        padding: var(--space-lg);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: var(--space-md);
    }

    .message-title-area {
        flex: 1;
        min-width: 0;
    }

    .message-title {
        font-size: var(--text-normal);
        font-weight: 700;
        color: var(--text-primary);
        margin: 0 0 var(--space-xs) 0;
        line-height: 1.3;
    }

    .message-meta {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        flex-wrap: wrap;
    }

    .message-date {
        font-size: var(--text-small);
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: var(--space-xs);
    }

    .message-actions {
        display: flex;
        align-items: flex-start;
        gap: var(--space-sm);
        flex-shrink: 0;
    }

    .message-content {
        padding: 0 var(--space-lg) var(--space-lg);
        color: var(--text-primary);
        line-height: 1.5;
    }

    .message-preview {
        margin-bottom: var(--space-md);
        color: var(--text-secondary);
    }

    .message-attachment {
        background-color: rgba(99, 102, 241, 0.05);
        border: 1px solid rgba(99, 102, 241, 0.1);
        border-radius: var(--radius-small);
        padding: var(--space-sm) var(--space-md);
        margin-top: var(--space-md);
        display: inline-flex;
        align-items: center;
        gap: var(--space-xs);
        font-size: var(--text-small);
        color: var(--primary);
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .message-attachment:hover {
        background-color: rgba(99, 102, 241, 0.1);
        color: var(--secondary);
    }

    .status-badge {
        padding: var(--space-xs) var(--space-sm);
        border-radius: var(--radius-small);
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .status-badge.new {
        background-color: var(--accent-orange);
        color: white;
    }

    .status-badge.urgent {
        background-color: var(--danger);
        color: white;
    }

    .status-badge.read {
        background-color: var(--neutral);
        color: white;
    }

    .priority-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .priority-indicator.high {
        background-color: var(--danger);
    }

    .priority-indicator.medium {
        background-color: var(--warning);
    }

    .priority-indicator.normal {
        background-color: var(--success);
    }

    .empty-state {
        text-align: center;
        padding: var(--space-xl);
        background-color: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: var(--background);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto var(--space-md);
        color: var(--text-muted);
        font-size: 32px;
    }

    .modal-moderne .modal-content {
        border-radius: var(--radius-medium);
        border: none;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    .modal-moderne .modal-header {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-bottom: none;
        padding: var(--space-lg);
        border-radius: var(--radius-medium) var(--radius-medium) 0 0;
    }

    .modal-moderne .modal-body {
        padding: var(--space-lg);
    }

    .modal-moderne .modal-footer {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding: var(--space-lg);
        background-color: var(--background);
        border-radius: 0 0 var(--radius-medium) var(--radius-medium);
    }

    .message-full-content {
        background-color: var(--background);
        padding: var(--space-lg);
        border-radius: var(--radius-small);
        line-height: 1.6;
    }

    .message-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        font-size: var(--text-small);
        color: var(--text-secondary);
    }

    .info-icon {
        color: var(--accent-blue);
        width: 16px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    .stat-card {
        background-color: var(--surface);
        padding: var(--space-md);
        border-radius: var(--radius-small);
        text-align: center;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .stat-number {
        font-size: var(--amount-medium);
        font-weight: 700;
        margin-bottom: var(--space-xs);
    }

    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .stat-card.total .stat-number { color: var(--primary); }
    .stat-card.unread .stat-number { color: var(--accent-orange); }
    .stat-card.urgent .stat-number { color: var(--danger); }

    /* Responsive */
    @media (max-width: 768px) {
        .messages-page {
            padding: var(--space-md);
        }

        .messages-header {
            flex-direction: column;
            align-items: stretch;
            text-align: center;
        }

        .filters-container {
            justify-content: center;
            margin-top: var(--space-md);
        }

        .message-header {
            flex-direction: column;
            gap: var(--space-sm);
        }

        .message-actions {
            align-self: stretch;
            justify-content: center;
        }

        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        }
    }
</style>
@endpush

@section('content')
<div class="messages-page">
    <!-- Header avec statistiques -->
    <div class="messages-header">
        <div class="header-left">
            <h1 class="header-title">
                <i class="fas fa-envelope-open-text"></i>
                Mes Messages
            </h1>
            <p class="header-subtitle">Consultez et gérez vos messages importants</p>
        </div>
        
        <div class="filters-container">
            <button class="filter-btn active" id="filterAll" data-filter="all">
                <i class="fas fa-inbox"></i>
                Tous
                <span class="filter-badge">{{ $stats['total'] }}</span>
            </button>
            
            <button class="filter-btn" id="filterUnread" data-filter="unread">
                <i class="fas fa-envelope"></i>
                Non lus
                <span class="filter-badge">{{ $stats['unread'] }}</span>
            </button>
            
            @if($stats['urgent'] > 0)
            <button class="filter-btn" id="filterUrgent" data-filter="urgent">
                <i class="fas fa-exclamation-triangle"></i>
                Urgent
                <span class="filter-badge">{{ $stats['urgent'] }}</span>
            </button>
            @endif

            @if($stats['unread'] > 0)
            <button class="btn-acasi primary" id="markAllRead">
                <i class="fas fa-check-double"></i>
                Tout marquer comme lu
            </button>
            @endif
        </div>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid">
        <div class="stat-card total card-moderne">
            <div class="stat-number">{{ $stats['total'] }}</div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card unread card-moderne">
            <div class="stat-number">{{ $stats['unread'] }}</div>
            <div class="stat-label">Non lus</div>
        </div>
        @if($stats['urgent'] > 0)
        <div class="stat-card urgent card-moderne">
            <div class="stat-number">{{ $stats['urgent'] }}</div>
            <div class="stat-label">Urgent</div>
        </div>
        @endif
    </div>

    <!-- Messages -->
    <div class="messages-grid">
        @forelse($messages as $message)
        <div class="message-card {{ $message->is_read ? '' : 'unread' }} {{ $message->priority == 'high' ? 'urgent' : '' }}" 
             data-message-type="{{ $message->is_read ? 'read' : 'unread' }} {{ $message->priority == 'high' ? 'urgent' : '' }}">
            
            <div class="message-header">
                <div class="message-title-area">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <div class="priority-indicator {{ $message->priority ?? 'normal' }}"></div>
                        <h3 class="message-title">{{ $message->titre }}</h3>
                        
                        @if(!$message->is_read)
                            <span class="status-badge new">Nouveau</span>
                        @endif
                        
                        @if($message->priority == 'high')
                            <span class="status-badge urgent">Urgent</span>
                        @endif
                    </div>
                    
                    <div class="message-meta">
                        <div class="message-date">
                            <i class="fas fa-calendar-alt info-icon"></i>
                            @if($message->created_at->isToday())
                                Aujourd'hui à {{ $message->created_at->format('H:i') }}
                            @elseif($message->created_at->isYesterday())
                                Hier à {{ $message->created_at->format('H:i') }}
                            @else
                                {{ $message->created_at->format('d/m/Y à H:i') }}
                            @endif
                        </div>
                        
                        @if($message->expiration)
                        <div class="message-date">
                            <i class="fas fa-hourglass-end info-icon"></i>
                            Expire le {{ $message->expiration->format('d/m/Y') }}
                        </div>
                        @endif
                    </div>
                </div>
                
                <div class="message-actions">
                    @if(!$message->is_read)
                    <button class="btn-acasi secondary mark-read-btn" data-id="{{ $message->id }}">
                        <i class="fas fa-check"></i>
                        Marquer lu
                    </button>
                    @endif
                    
                    <button class="btn-acasi primary" onclick="openMessageModal({{ $message->id }})">
                        <i class="fas fa-eye"></i>
                        Lire
                    </button>
                </div>
            </div>
            
            <div class="message-content">
                <div class="message-preview">
                    {!! Str::limit(strip_tags($message->contenu), 200) !!}
                </div>
                
                @if($message->fichier)
                <a href="{{ asset('storage/' . $message->fichier) }}" class="message-attachment" target="_blank">
                    <i class="fas fa-paperclip"></i>
                    Pièce jointe
                </a>
                @endif
            </div>
        </div>

        <!-- Modal pour chaque message -->
        <div class="modal fade modal-moderne" id="messageModal{{ $message->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            @if($message->priority == 'high')
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            @else
                            <i class="fas fa-envelope-open me-2"></i>
                            @endif
                            {{ $message->titre }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="message-info-grid">
                            <div class="info-item">
                                <i class="fas fa-calendar-alt info-icon"></i>
                                <strong>Date:</strong> {{ $message->created_at->format('d/m/Y H:i') }}
                            </div>
                            <div class="info-item">
                                <i class="fas fa-hourglass-end info-icon"></i>
                                <strong>Expire:</strong> {{ $message->expiration ? $message->expiration->format('d/m/Y') : 'Jamais' }}
                            </div>
                            <div class="info-item">
                                <i class="fas fa-signal info-icon"></i>
                                <strong>Priorité:</strong>
                                @if($message->priority == 'high')
                                    <span class="status-badge urgent">Urgent</span>
                                @elseif($message->priority == 'medium')
                                    <span class="badge bg-warning">Moyenne</span>
                                @else
                                    <span class="badge bg-success">Normale</span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="message-full-content">
                            {!! $message->contenu !!}
                        </div>
                        
                        @if($message->fichier)
                        <div class="mt-4">
                            <h6><i class="fas fa-paperclip me-2"></i>Pièce jointe:</h6>
                            <a href="{{ asset('storage/' . $message->fichier) }}" class="btn-acasi secondary" target="_blank">
                                <i class="fas fa-download"></i>
                                Télécharger
                            </a>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if(!$message->is_read)
                        <button type="button" class="btn-acasi primary mark-read-btn" data-id="{{ $message->id }}" data-bs-dismiss="modal">
                            <i class="fas fa-check"></i>
                            Marquer comme lu
                        </button>
                        @endif
                        <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                            Fermer
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <div class="empty-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <h3>Aucun message</h3>
            <p class="text-muted">Vous n'avez pas encore reçu de messages.</p>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($messages->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $messages->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtres
    const filterButtons = document.querySelectorAll('.filter-btn');
    const messageCards = document.querySelectorAll('.message-card');

    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Retirer active de tous les boutons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Ajouter active au bouton cliqué
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');
            
            messageCards.forEach(card => {
                const messageType = card.getAttribute('data-message-type');
                
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'unread' && messageType.includes('unread')) {
                    card.style.display = 'block';
                } else if (filter === 'urgent' && messageType.includes('urgent')) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

    // Marquer un message comme lu
    const markReadButtons = document.querySelectorAll('.mark-read-btn');
    
    markReadButtons.forEach(button => {
        button.addEventListener('click', function() {
            const messageId = this.getAttribute('data-id');
            markAsRead(messageId);
        });
    });

    // Marquer tous les messages comme lus
    const markAllReadBtn = document.getElementById('markAllRead');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            fetch('{{ route("esbtp.mes-messages.mark-all-read") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => {
                debugError('Error:', error);
            });
        });
    }

    function markAsRead(messageId) {
        fetch(`{{ route('esbtp.mes-messages.read', '') }}/${messageId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Trouver la card du message
                const messageCard = document.querySelector(`[data-id="${messageId}"]`).closest('.message-card');
                messageCard.classList.remove('unread');
                messageCard.setAttribute('data-message-type', messageCard.getAttribute('data-message-type').replace('unread', 'read').trim());
                
                // Retirer les badges et boutons "marquer lu"
                const newBadge = messageCard.querySelector('.status-badge.new');
                if (newBadge) newBadge.remove();
                
                const markReadBtns = messageCard.querySelectorAll('.mark-read-btn');
                markReadBtns.forEach(btn => btn.remove());
                
                // Mettre à jour les compteurs
                updateFilterCounts();
            }
        })
        .catch(error => {
            debugError('Error:', error);
        });
    }

    function updateFilterCounts() {
        const totalMessages = document.querySelectorAll('.message-card').length;
        const unreadMessages = document.querySelectorAll('.message-card.unread').length;
        const urgentMessages = document.querySelectorAll('.message-card.urgent').length;
        
        // Mettre à jour les badges des filtres
        document.querySelector('#filterAll .filter-badge').textContent = totalMessages;
        document.querySelector('#filterUnread .filter-badge').textContent = unreadMessages;
        
        const urgentFilter = document.querySelector('#filterUrgent .filter-badge');
        if (urgentFilter) {
            urgentFilter.textContent = urgentMessages;
        }
        
        // Mettre à jour les stats
        document.querySelector('.stat-card.unread .stat-number').textContent = unreadMessages;
        document.querySelector('.stat-card.urgent .stat-number').textContent = urgentMessages;
        
        // Masquer le bouton "tout marquer comme lu" si pas de messages non lus
        if (unreadMessages === 0 && markAllReadBtn) {
            markAllReadBtn.style.display = 'none';
        }
    }
});

function openMessageModal(messageId) {
    const modal = new bootstrap.Modal(document.getElementById(`messageModal${messageId}`));
    modal.show();
}
</script>
@endpush