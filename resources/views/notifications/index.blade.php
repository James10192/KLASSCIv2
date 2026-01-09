@extends('layouts.app')

@section('title', 'Notifications')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    body {
        background-color: var(--background);
    }

    .notification-avatar {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(4, 83, 203, 0.12);
        color: var(--primary);
        font-size: 1.4rem;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
    }

    .notifications-panel {
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid #e7edf5;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }

    .notifications-toolbar {
        padding: 16px 20px;
        border-bottom: 1px solid #edf2f7;
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.06), rgba(4, 83, 203, 0.02));
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
    }

    .notifications-body {
        padding: 12px 0;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">
        <div class="dashboard-header mb-4">
            <div class="header-left">
                <div class="d-flex align-items-center gap-3">
                    <div class="notification-avatar">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div>
                        <h1 class="mb-1">
                            @if(auth()->user()->hasRole('coordinateur'))
                                Notifications - Coordination
                            @else
                                Notifications
                            @endif
                        </h1>
                        @if(auth()->user()->hasRole('coordinateur'))
                            <p class="header-subtitle">Suivi des activités d'émargement et d'appel</p>
                        @else
                            <p class="header-subtitle">Restez informé des événements récents</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <span class="badge rounded-pill bg-light text-dark">
                    <i class="fas fa-calendar me-1"></i>
                    {{ now()->format('d/m/Y') }}
                </span>
            </div>
        </div>

        <div class="notifications-panel">
            <div class="notifications-toolbar">
                <div class="d-flex flex-wrap gap-2 align-items-center">
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
                </div>
                <div>
                    @if($notifications->where('is_read', false)->isNotEmpty())
                        <button class="btn btn-outline-secondary btn-sm mark-all-read">
                            <i class="fas fa-check-double me-1"></i> Tout marquer comme lu
                        </button>
                    @endif
                </div>
            </div>
                <div class="notifications-body">
                    @php
                        $hasTimetableShortcut = !empty($timetableShortcut) && ($timetableShortcut['show'] ?? false);
                        $hasEvaluationShortcut = !empty($evaluationShortcut) && ($evaluationShortcut['show'] ?? false);
                    @endphp
                    @if($notifications->isEmpty() && !$hasTimetableShortcut && !$hasEvaluationShortcut)
                        <div class="text-center p-5">
                            <div class="empty-state mb-3">
                                <i class="fas fa-bell-slash fa-3x text-muted"></i>
                            </div>
                            <h6 class="text-muted">Aucune notification</h6>
                            <p class="small text-muted">Vous n'avez pas encore reçu de notifications</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @if($hasEvaluationShortcut)
                                <div class="list-group-item notification-item evaluation-shortcut-item"
                                     onclick="window.location.href='{{ route('esbtp.evaluations.index') }}';"
                                     style="cursor: pointer;">
                                    <div class="d-flex align-items-start justify-content-between notification-row">
                                        <div class="flex-grow-1 me-3">
                                            <div class="d-flex align-items-center mb-2 notification-title-row">
                                                <span class="notification-icon bg-info-light text-info me-2">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </span>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Évaluations à activer</h6>
                                                    <small class="text-muted">Publiez-les pour rendre la saisie disponible</small>
                                                </div>
                                            </div>
                                            <div class="notification-meta-row">
                                                <span class="notification-meta-pill meta-info">
                                                    <i class="fas fa-layer-group"></i>
                                                    brouillons: {{ $evaluationShortcut['total'] ?? 0 }}
                                                </span>
                                                @if(($evaluationShortcut['overdue'] ?? 0) > 0)
                                                    <span class="notification-meta-pill meta-danger">
                                                        <i class="fas fa-calendar-times"></i>
                                                        en retard: {{ $evaluationShortcut['overdue'] }}
                                                    </span>
                                                @endif
                                                @if(($evaluationShortcut['soon'] ?? 0) > 0)
                                                    <span class="notification-meta-pill meta-warning">
                                                        <i class="fas fa-hourglass-half"></i>
                                                        à publier bientôt: {{ $evaluationShortcut['soon'] }}
                                                    </span>
                                                @endif
                                                @if(($evaluationShortcut['undated'] ?? 0) > 0)
                                                    <span class="notification-meta-pill meta-neutral">
                                                        <i class="fas fa-question-circle"></i>
                                                        sans date: {{ $evaluationShortcut['undated'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-info text-white">Action rapide</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @if($hasTimetableShortcut)
                                <div class="list-group-item notification-item timetable-shortcut-item"
                                     onclick="window.location.href='{{ route('esbtp.emploi-temps.index', ['quick_generate' => 1]) }}';"
                                     style="cursor: pointer;">
                                    <div class="d-flex align-items-start justify-content-between notification-row">
                                        <div class="flex-grow-1 me-3">
                                            <div class="d-flex align-items-center mb-2 notification-title-row">
                                                <span class="notification-icon bg-warning-light text-warning me-2">
                                                    <i class="fas fa-calendar-exclamation"></i>
                                                </span>
                                                <div>
                                                    <h6 class="mb-0 fw-semibold">Emplois du temps à renouveler</h6>
                                                    <small class="text-muted">Génération rapide disponible</small>
                                                </div>
                                            </div>
                                            <div class="notification-meta-row">
                                                @if($timetableShortcut['missing'] > 0)
                                                    <span class="notification-meta-pill meta-info">
                                                        <i class="fas fa-layer-group"></i>
                                                        classes sans emploi du temps: {{ $timetableShortcut['missing'] }}
                                                    </span>
                                                @endif
                                                @if($timetableShortcut['expired'] > 0)
                                                    <span class="notification-meta-pill meta-danger">
                                                        <i class="fas fa-calendar-times"></i>
                                                        classes avec emploi du temps expiré: {{ $timetableShortcut['expired'] }}
                                                    </span>
                                                @endif
                                                @if($timetableShortcut['expiring_soon'] > 0)
                                                    <span class="notification-meta-pill meta-warning">
                                                        <i class="fas fa-clock"></i>
                                                        classes expirant bientôt: {{ $timetableShortcut['expiring_soon'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-warning text-dark">Action rapide</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                            @foreach($notifications as $notification)
                                <div class="list-group-item notification-item {{ !$notification->is_read ? 'unread' : '' }}"
                                     id="notification-{{ $notification->id }}"
                                     @if($notification->link)
                                         onclick="markAsReadAndNavigate('{{ $notification->id }}', '{{ $notification->link }}');"
                                     @else
                                         onclick="markAsRead('{{ $notification->id }}');"
                                     @endif
                                     style="cursor: pointer;">
                                    <div class="d-flex align-items-start justify-content-between notification-row">
                                        <div class="flex-grow-1 me-3">
                                            <div class="d-flex align-items-center mb-2 notification-title-row">
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
                                            @php
                                                $labels = [];
                                                $cta = null;
                                                $primaryLine = $notification->display_primary;

                                                if (!$primaryLine) {
                                                    $safeMessage = strip_tags($notification->message ?? '', '<i><strong><em><b><br>');
                                                    $primaryLine = trim(preg_split('/(Statut:|Étape:|Paiement:|Cliquez)/i', $safeMessage)[0] ?? '');

                                                    if (preg_match_all('/(<i[^>]*>.*?<\\/i>\\s*)?(Statut:|Étape:|Paiement:)\\s*([^<|]*)/i', $safeMessage, $matches, PREG_SET_ORDER)) {
                                                        foreach ($matches as $match) {
                                                            $labels[] = trim($match[0]);
                                                        }
                                                    }

                                                    if (preg_match('/Cliquez[^<]*/i', $safeMessage, $ctaMatch)) {
                                                        $cta = trim($ctaMatch[0]);
                                                    }
                                                } else {
                                                    $labels = $notification->display_labels ?? [];
                                                    $cta = $notification->display_cta;
                                                }
                                            @endphp
                                            <div class="notification-lines">
                                                @if($primaryLine !== '')
                                                    <div class="notification-line">{!! $primaryLine !!}</div>
                                                @endif
                                                @if(!empty($labels))
                                                    <div class="notification-meta-row">
                                                        @foreach($labels as $label)
                                                            @php
                                                                $key = trim(Str::before($label, ':'));
                                                                $value = trim(Str::after($label, ':'));
                                                                $valueLower = Str::lower($value);
                                                                $pillClass = 'meta-neutral';
                                                                $icon = 'fas fa-tag';

                                                                if (Str::lower($key) === 'classe') {
                                                                    $icon = 'fas fa-school';
                                                                    $pillClass = 'meta-info';
                                                                } elseif (Str::lower($key) === 'statut') {
                                                                    $icon = 'fas fa-info-circle';
                                                                    if (Str::contains($valueLower, ['active', 'valid', 'valide'])) {
                                                                        $pillClass = 'meta-success';
                                                                    } elseif (Str::contains($valueLower, ['attente', 'pending'])) {
                                                                        $pillClass = 'meta-warning';
                                                                    } elseif (Str::contains($valueLower, ['rejet', 'refus', 'annul'])) {
                                                                        $pillClass = 'meta-danger';
                                                                    }
                                                                } elseif (Str::lower($key) === 'étape' || Str::lower($key) === 'etape') {
                                                                    $icon = 'fas fa-clipboard-check';
                                                                    if (Str::contains($valueLower, ['prospect'])) {
                                                                        $pillClass = 'meta-secondary';
                                                                    } elseif (Str::contains($valueLower, ['document'])) {
                                                                        $pillClass = 'meta-info';
                                                                    } elseif (Str::contains($valueLower, ['validation'])) {
                                                                        $pillClass = 'meta-warning';
                                                                    } elseif (Str::contains($valueLower, ['valid', 'valide'])) {
                                                                        $pillClass = 'meta-success';
                                                                    } elseif (Str::contains($valueLower, ['étudiant', 'etudiant'])) {
                                                                        $pillClass = 'meta-primary';
                                                                    }
                                                                } elseif (Str::lower($key) === 'paiement') {
                                                                    $icon = 'fas fa-money-bill-wave';
                                                                    if (Str::contains($valueLower, ['valid', 'payé', 'paye', 'réglé', 'regle'])) {
                                                                        $pillClass = 'meta-success';
                                                                    } elseif (Str::contains($valueLower, ['attente', 'pending'])) {
                                                                        $pillClass = 'meta-warning';
                                                                    } elseif (Str::contains($valueLower, ['rejet', 'refus'])) {
                                                                        $pillClass = 'meta-danger';
                                                                    }
                                                                }
                                                            @endphp
                                                            <span class="notification-meta-pill {{ $pillClass }}">
                                                                <i class="{{ $icon }}"></i>
                                                                {{ $key }}: {{ $value }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
                                                @if($cta)
                                                    <div class="notification-cta">{!! $cta !!}</div>
                                                @endif
                                            </div>
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
    white-space: normal;
    word-break: break-word;
}
.notification-item {
    padding: 15px;
    transition: all 0.2s ease;
    border: 1px solid #eef2f7;
    border-radius: 14px;
    margin: 12px 16px;
    background: #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
    display: block;
}
.notification-row {
    width: 100%;
}
.notifications-panel .list-group-item {
    width: 100%;
}
.notifications-panel .notification-item {
    width: 100%;
}
.notification-lines {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 10px 12px;
    border-radius: 12px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
}
.notification-line {
    display: flex;
    align-items: flex-start;
    gap: 6px;
    color: #475569;
    font-size: 0.92rem;
    line-height: 1.45;
}
.notification-line i {
    color: var(--primary);
}
.notification-item .badge.bg-warning {
    border-radius: 999px;
    padding: 4px 10px;
    font-weight: 600;
}
.notification-title-row {
    gap: 10px;
}
.notification-meta-row {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}
.notification-meta-pill {
    background: #eef2ff;
    color: #1e3a8a;
    border: 1px solid #c7d2fe;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.notification-cta {
    color: #1e293b;
    font-weight: 600;
}
.notification-meta-pill.meta-success {
    background: rgba(16, 185, 129, 0.12);
    color: #047857;
    border-color: rgba(16, 185, 129, 0.4);
}
.notification-meta-pill.meta-warning {
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
    border-color: rgba(245, 158, 11, 0.4);
}
.notification-meta-pill.meta-danger {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
    border-color: rgba(239, 68, 68, 0.4);
}
.notification-meta-pill.meta-info {
    background: rgba(59, 130, 246, 0.12);
    color: #1d4ed8;
    border-color: rgba(59, 130, 246, 0.4);
}
.notification-meta-pill.meta-primary {
    background: rgba(4, 83, 203, 0.12);
    color: #1e3a8a;
    border-color: rgba(4, 83, 203, 0.4);
}
.notification-meta-pill.meta-secondary {
    background: rgba(100, 116, 139, 0.12);
    color: #475569;
    border-color: rgba(100, 116, 139, 0.4);
}
.notification-meta-pill.meta-neutral {
    background: rgba(148, 163, 184, 0.14);
    color: #475569;
    border-color: rgba(148, 163, 184, 0.4);
}
.notification-item:hover {
    background-color: rgba(59, 130, 246, 0.06);
    transform: translateY(-1px);
}
.notification-item.unread {
    background-color: rgba(242, 148, 0, 0.1);
    border-left: 3px solid #f29400;
}
.timetable-shortcut-item {
    background: rgba(245, 158, 11, 0.08);
    border-left: 3px solid #f59e0b;
}
.evaluation-shortcut-item {
    background: rgba(59, 130, 246, 0.08);
    border-left: 3px solid #3b82f6;
}
.list-group.list-group-flush {
    padding-bottom: 12px;
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

function markAsReadAndNavigate(id, url) {
    fetch(`{{ route('notifications.mark-as-read', '') }}/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .finally(() => {
        window.location.href = url;
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
