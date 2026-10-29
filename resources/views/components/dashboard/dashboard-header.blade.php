@props([
    'title' => '',
    'subtitle' => '',
    'icon' => 'fa-chart-pie',
    'actions' => null
])

<div class="dashboard-header">
    <div class="header-left">
        <h1><i class="fas {{ $icon }} me-2"></i>{{ $title }}</h1>
        <p class="header-subtitle">{{ $subtitle }}</p>
    </div>
    <div class="header-actions">
        @if($actions)
            {!! $actions !!}
        @else
            <button class="btn-acasi outline" onclick="refreshData()" title="Actualiser les données">
                <i class="fas fa-sync-alt"></i>Actualiser
            </button>
            <div class="dropdown">
                <button class="btn-acasi primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-cog"></i>Actions
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="{{ route('esbtp.teacher-attendance.report') }}">
                        <i class="fas fa-clipboard-list me-2"></i>Rapport Émargements
                    </a></li>
                    <li><a class="dropdown-item" href="{{ route('esbtp.attendances.index') }}">
                        <i class="fas fa-users me-2"></i>Gérer Présences
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" onclick="generateReport()">
                        <i class="fas fa-file-export me-2"></i>Export Journalier
                    </a></li>
                </ul>
            </div>
        @endif
    </div>
</div>