@php
    $hasTimetableShortcut = !empty($timetableShortcut) && ($timetableShortcut['show'] ?? false);
    $hasEvaluationShortcut = !empty($evaluationShortcut) && ($evaluationShortcut['show'] ?? false);
    $hasEvaluationGradingShortcut = !empty($evaluationGradingShortcut) && ($evaluationGradingShortcut['show'] ?? false);
    $gradingCtaUrl = null;
    if (auth()->user()?->can('exams.view') || auth()->user()?->can('evaluations.view')) {
        $gradingCtaUrl = route('esbtp.evaluations.index');
    } elseif (auth()->user()?->can('notes.view') || auth()->user()?->can('notes.create') || auth()->user()?->can('notes.edit') || auth()->user()?->can('notes.manage_own')) {
        $gradingCtaUrl = route('esbtp.notes.index');
    }
@endphp

@if(!$hasTimetableShortcut && !$hasEvaluationShortcut && !$hasEvaluationGradingShortcut && $notifications->isEmpty())
    <div class="text-center p-3">
        <small>Aucune notification</small>
    </div>
@else
    @if($hasEvaluationGradingShortcut && $gradingCtaUrl)
        <div class="notification-item evaluation-grading-shortcut-item"
             onclick="window.location.href='{{ $gradingCtaUrl }}';"
             style="cursor: pointer;">
            <div class="d-flex align-items-center mb-1">
                <span class="notification-icon bg-danger-light text-danger me-2">
                    <i class="fas fa-pen-to-square"></i>
                </span>
                <div>
                    <h6 class="notification-title mb-0">Notes a saisir</h6>
                    <small class="text-muted">Evaluations passees, saisie attendue</small>
                </div>
            </div>
            <p class="notification-message mb-0">
                {{ $evaluationGradingShortcut['total'] ?? 0 }} evaluation(s) a noter
                @if(($evaluationGradingShortcut['missing_notes'] ?? 0) > 0)
                    • {{ $evaluationGradingShortcut['missing_notes'] }} sans notes
                @endif
                @if(($evaluationGradingShortcut['notes_unpublished'] ?? 0) > 0)
                    • {{ $evaluationGradingShortcut['notes_unpublished'] }} notes non publiees
                @endif
            </p>
        </div>
    @endif
    @if($hasEvaluationShortcut)
        <div class="notification-item evaluation-shortcut-item"
             onclick="window.location.href='{{ route('esbtp.evaluations.index') }}';"
             style="cursor: pointer;">
            <div class="d-flex align-items-center mb-1">
                <span class="notification-icon bg-info-light text-info me-2">
                    <i class="fas fa-clipboard-check"></i>
                </span>
                <div>
                    <h6 class="notification-title mb-0">Évaluations à activer</h6>
                    <small class="text-muted">Publiez-les pour activer la saisie</small>
                </div>
            </div>
            <p class="notification-message mb-0">
                {{ $evaluationShortcut['total'] ?? 0 }} brouillon(s)
                @if(($evaluationShortcut['overdue'] ?? 0) > 0)
                    • {{ $evaluationShortcut['overdue'] }} en retard
                @endif
                @if(($evaluationShortcut['soon'] ?? 0) > 0)
                    • {{ $evaluationShortcut['soon'] }} bientôt
                @endif
                @if(($evaluationShortcut['undated'] ?? 0) > 0)
                    • {{ $evaluationShortcut['undated'] }} sans date
                @endif
            </p>
        </div>
    @endif
    @if($hasTimetableShortcut)
        <div class="notification-item timetable-shortcut-item"
             onclick="window.location.href='{{ route('esbtp.emploi-temps.index', ['quick_generate' => 1]) }}';"
             style="cursor: pointer;">
            <div class="d-flex align-items-center mb-1">
                <span class="notification-icon bg-warning-light text-warning me-2">
                    <i class="fas fa-calendar-exclamation"></i>
                </span>
                <div>
                    <h6 class="notification-title mb-0">Emplois du temps à renouveler</h6>
                    <small class="text-muted">Clique pour lancer la génération rapide</small>
                </div>
            </div>
            <p class="notification-message mb-0">
                @if($timetableShortcut['missing'] > 0)
                    {{ $timetableShortcut['missing'] }} classe(s) sans emploi du temps
                @endif
                @if($timetableShortcut['expired'] > 0)
                    {{ $timetableShortcut['missing'] > 0 ? ' • ' : '' }}{{ $timetableShortcut['expired'] }} expiré(s)
                @endif
                @if($timetableShortcut['expiring_soon'] > 0)
                    {{ ($timetableShortcut['missing'] > 0 || $timetableShortcut['expired'] > 0) ? ' • ' : '' }}{{ $timetableShortcut['expiring_soon'] }} expire(nt) bientôt
                @endif
            </p>
        </div>
    @endif
    @foreach($notifications as $notification)
        <div class="notification-item {{ !$notification->is_read ? 'unread' : '' }}"
             id="notification-{{ $notification->id }}"
             @if($notification->link)
                 onclick="window.location.href='{{ $notification->link }}'; markAsRead('{{ $notification->id }}');"
             @else
                 onclick="markAsRead('{{ $notification->id }}');"
             @endif
             style="cursor: pointer; opacity: 1; transition: opacity 0.3s ease;">
            <div class="d-flex align-items-center mb-1">
                @if($notification->type === 'danger' || $notification->type === 'error')
                    <span class="notification-icon bg-danger-light text-danger me-2">
                        <i class="fas fa-exclamation-circle"></i>
                    </span>
                @elseif($notification->type === 'warning')
                    <span class="notification-icon bg-warning-light text-warning me-2">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                @elseif($notification->type === 'success')
                    <span class="notification-icon bg-success-light text-success me-2">
                        <i class="fas fa-check-circle"></i>
                    </span>
                @else
                    <span class="notification-icon bg-info-light text-info me-2">
                        <i class="fas fa-info-circle"></i>
                    </span>
                @endif
                <h6 class="notification-title mb-0">{{ $notification->title ?? 'Notification' }}</h6>
                @if(!$notification->is_read)
                    <span class="ms-auto badge bg-warning">Nouveau</span>
                @endif
            </div>
            <p class="notification-message mb-0">{{ Str::limit(strip_tags($notification->message ?? ''), 100) }}</p>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <small class="notification-time text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                @if(str_contains(strtolower($notification->title ?? ''), 'absence'))
                    <a href="{{ $notification->link ?? route('esbtp.mes-absences.index') }}" class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                        Justifier
                    </a>
                @endif
            </div>
        </div>
    @endforeach
@endif

<style>
.notification-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.notification-message {
    color: #495057;
    font-size: 0.9rem;
    white-space: normal;
    word-break: break-word;
}
.notification-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f1f1f1;
    transition: all 0.3s ease;
    height: auto;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.notification-item:hover {
    background-color: rgba(1, 99, 47, 0.05);
}
.notification-item.unread {
    background-color: rgba(242, 148, 0, 0.1);
    border-left: 3px solid #f29400;
}
.custom-dropdown .notification-item {
    align-items: flex-start;
}
.custom-dropdown .notification-title {
    white-space: normal;
}
.timetable-shortcut-item {
    background: rgba(245, 158, 11, 0.08);
    border-left: 3px solid #f59e0b;
}
.evaluation-shortcut-item {
    background: rgba(59, 130, 246, 0.08);
    border-left: 3px solid #3b82f6;
}
.evaluation-grading-shortcut-item {
    background: rgba(239, 68, 68, 0.08);
    border-left: 3px solid #ef4444;
}
.notification-item.fadeOut {
    opacity: 0;
    height: 0;
    padding: 0;
    margin: 0;
    border: none;
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
