@extends('layouts.app')

@section('title', 'Codes d\'assiduité - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* === KPI STAT CARDS === */
    .kpi-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-xl);
    }

    .kpi-stat-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        box-shadow: var(--shadow-card);
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: var(--space-md);
        transition: all 0.2s ease;
    }

    .kpi-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .kpi-stat-icon {
        width: 52px;
        height: 52px;
        border-radius: var(--radius-medium);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        flex-shrink: 0;
    }

    .kpi-stat-value {
        font-size: 1.8rem;
        font-weight: 800;
        color: var(--text-primary);
        line-height: 1;
    }

    .kpi-stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 4px;
    }

    /* === GENERATE FORM CARD === */
    .generate-section {
        background: linear-gradient(135deg, #eff6ff 0%, #ffffff 60%);
        border: 1px solid #bfdbfe;
        border-radius: var(--radius-medium);
        padding: var(--space-xl);
        margin-bottom: var(--space-xl);
        box-shadow: var(--shadow-card);
    }

    .generate-section-title {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: var(--space-lg);
        padding-bottom: var(--space-md);
        border-bottom: 1px solid #dbeafe;
    }

    .generate-section-title .title-icon {
        width: 36px;
        height: 36px;
        background: var(--primary);
        border-radius: var(--radius-small);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 0.875rem;
        flex-shrink: 0;
    }

    /* === TABLE === */
    .table-section {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .table-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-lg) var(--space-xl);
        border-bottom: 1px solid #f1f5f9;
    }

    .table-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
        margin: 0;
    }

    .total-badge {
        background: rgba(4, 83, 203, 0.1);
        color: var(--primary);
        font-weight: 600;
        font-size: var(--text-small);
        padding: 5px 12px;
        border-radius: 20px;
    }

    /* === CODE DISPLAY === */
    .code-display {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: var(--radius-small);
        padding: 5px 10px;
        font-family: 'SF Mono', 'Fira Code', 'Roboto Mono', 'Courier New', monospace;
        font-size: 0.95rem;
        font-weight: 700;
        color: var(--primary);
        letter-spacing: 2px;
    }

    .copy-btn {
        background: none;
        border: none;
        padding: 0 2px;
        cursor: pointer;
        color: var(--text-muted);
        font-size: 0.7rem;
        transition: all 0.2s;
        line-height: 1;
    }

    .copy-btn:hover { color: var(--primary); }

    /* === STATUS PILLS === */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: var(--text-small);
        font-weight: 600;
        white-space: nowrap;
    }

    .status-pill.valide {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .status-pill.expire {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    .status-pill.utilise {
        background: rgba(99, 102, 241, 0.1);
        color: #4f46e5;
    }

    /* === TABLE STYLES === */
    .codes-table {
        margin: 0;
    }

    .codes-table thead th {
        background: #f8fafc;
        font-size: var(--text-small);
        font-weight: 700;
        color: var(--text-secondary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 2px solid #e5e7eb;
        border-top: none;
        white-space: nowrap;
    }

    .codes-table tbody tr {
        transition: background 0.15s;
    }

    .codes-table tbody tr:hover {
        background: #f8fafc;
    }

    .codes-table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        border-color: #f1f5f9;
        font-size: var(--text-normal);
    }

    .teacher-mini-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #34d399);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: white;
        font-weight: 700;
        flex-shrink: 0;
    }

    .attempts-circle {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        font-size: var(--text-small);
        font-weight: 700;
    }

    .attempts-circle.danger {
        background: rgba(239, 68, 68, 0.1);
        color: #dc2626;
    }

    .attempts-circle.safe {
        background: #f1f5f9;
        color: var(--text-secondary);
    }

    .empty-state-row td {
        padding: var(--space-xl) !important;
        text-align: center;
        color: var(--text-secondary);
    }

    .empty-icon {
        font-size: 3rem;
        opacity: 0.25;
        margin-bottom: var(--space-md);
        color: var(--primary);
    }

    @media (max-width: 768px) {
        .kpi-stat-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 480px) {
        .kpi-stat-grid { grid-template-columns: 1fr; }
        .generate-section { padding: var(--space-lg); }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        @php
            $pageItems = collect($codes->items());
            $totalCodes = $codes->total();
            $codesValides = $pageItems->filter(fn($c) => !$c->is_used && !$c->expires_at->isPast())->count();
            $codesExpires = $pageItems->filter(fn($c) => $c->expires_at->isPast())->count();
            $codesUtilises = $pageItems->filter(fn($c) => $c->is_used)->count();
        @endphp

        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-key me-2" style="color: var(--primary);"></i>Codes d'assiduité</h1>
                <p class="header-subtitle">Générez et suivez les codes de présence pour les enseignants</p>
            </div>
        </div>

        <!-- Flash Messages -->
        @if(session('success'))
            <div style="background: rgba(16,185,129,0.08); border-left: 4px solid var(--success); border-radius: var(--radius-small); padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-lg); display: flex; align-items: center; gap: var(--space-sm); color: #065f46; font-weight: 600;">
                <i class="fas fa-check-circle fa-lg" style="color: var(--success);"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div style="background: rgba(239,68,68,0.08); border-left: 4px solid var(--danger); border-radius: var(--radius-small); padding: var(--space-md) var(--space-lg); margin-bottom: var(--space-lg); display: flex; align-items: center; gap: var(--space-sm); color: #991b1b; font-weight: 600;">
                <i class="fas fa-exclamation-triangle fa-lg" style="color: var(--danger);"></i>
                {{ session('error') }}
            </div>
        @endif

        <!-- KPI Cards -->
        <div class="kpi-stat-grid">
            <div class="kpi-stat-card">
                <div class="kpi-stat-icon" style="background: linear-gradient(135deg, #0453cb, #5e91de);">
                    <i class="fas fa-key"></i>
                </div>
                <div>
                    <div class="kpi-stat-value">{{ $totalCodes }}</div>
                    <div class="kpi-stat-label">Total codes</div>
                </div>
            </div>
            <div class="kpi-stat-card">
                <div class="kpi-stat-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <div class="kpi-stat-value" style="color: #059669;">{{ $codesValides }}</div>
                    <div class="kpi-stat-label">Valides</div>
                </div>
            </div>
            <div class="kpi-stat-card">
                <div class="kpi-stat-icon" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                    <i class="fas fa-hourglass-end"></i>
                </div>
                <div>
                    <div class="kpi-stat-value" style="color: #dc2626;">{{ $codesExpires }}</div>
                    <div class="kpi-stat-label">Expirés</div>
                </div>
            </div>
            <div class="kpi-stat-card">
                <div class="kpi-stat-icon" style="background: linear-gradient(135deg, #6366f1, #818cf8);">
                    <i class="fas fa-user-check"></i>
                </div>
                <div>
                    <div class="kpi-stat-value" style="color: #4f46e5;">{{ $codesUtilises }}</div>
                    <div class="kpi-stat-label">Utilisés</div>
                </div>
            </div>
        </div>

        <!-- Generate Form -->
        <div class="generate-section">
            <div class="generate-section-title">
                <div class="title-icon"><i class="fas fa-magic"></i></div>
                Générer un nouveau code
            </div>
            <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4 col-sm-6">
                        <label for="date" class="form-label" style="font-size: var(--text-small); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">
                            <i class="fas fa-calendar me-1"></i> Date de validité
                        </label>
                        <input type="date" class="form-control form-control-lg" id="date" name="date"
                               required min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}"
                               style="border-radius: var(--radius-small); border: 1px solid #d1d5db; font-size: 1rem;">
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn-acasi primary" style="height: 50px; padding: 0 var(--space-xl); font-size: 1rem;">
                            <i class="fas fa-magic me-2"></i> Générer le code
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Codes Table -->
        <div class="table-section">
            <div class="table-section-header">
                <h5 class="table-section-title">
                    <i class="fas fa-list-ul" style="color: var(--primary);"></i>
                    Historique des codes générés
                </h5>
                <span class="total-badge">{{ $totalCodes }} code{{ $totalCodes > 1 ? 's' : '' }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover codes-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Expire le</th>
                            <th>Statut</th>
                            <th>Utilisé par</th>
                            <th class="text-center">Tentatives</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($codes as $code)
                        <tr>
                            <td>
                                <span class="code-display">
                                    {{ $code->code }}
                                    <button class="copy-btn" onclick="copyCode('{{ $code->code }}', this)" title="Copier le code">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </span>
                            </td>
                            <td>
                                <span style="color: var(--text-secondary); font-size: var(--text-small); display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-calendar-day" style="color: var(--primary);"></i>
                                    {{ $code->date->format('d/m/Y') }}
                                </span>
                            </td>
                            <td>
                                <span style="color: {{ $code->expires_at->isPast() ? '#dc2626' : 'var(--text-secondary)' }}; font-size: var(--text-small); display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-clock"></i>
                                    {{ $code->expires_at->format('d/m/Y H:i') }}
                                </span>
                            </td>
                            <td>
                                @if($code->is_used)
                                    <span class="status-pill utilise">
                                        <i class="fas fa-check" style="font-size: 9px;"></i> Utilisé
                                    </span>
                                @elseif($code->expires_at->isPast())
                                    <span class="status-pill expire">
                                        <i class="fas fa-times" style="font-size: 9px;"></i> Expiré
                                    </span>
                                @else
                                    <span class="status-pill valide">
                                        <i class="fas fa-circle" style="font-size: 6px;"></i> Valide
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($code->usedByTeacher)
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="teacher-mini-avatar">
                                            {{ strtoupper(substr($code->usedByTeacher->nom, 0, 1)) }}{{ strtoupper(substr($code->usedByTeacher->prenoms ?? '', 0, 1)) }}
                                        </div>
                                        <span style="font-size: var(--text-small); font-weight: 500;">
                                            {{ $code->usedByTeacher->nom }} {{ $code->usedByTeacher->prenoms }}
                                        </span>
                                    </div>
                                @else
                                    <span style="color: var(--text-muted);">—</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($code->attempts >= 3)
                                    <span class="attempts-circle danger">{{ $code->attempts }}</span>
                                @else
                                    <span class="attempts-circle safe">{{ $code->attempts }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr class="empty-state-row">
                            <td colspan="6">
                                <div class="empty-icon"><i class="fas fa-key"></i></div>
                                <div style="font-weight: 700; font-size: 1rem; color: var(--text-primary); margin-bottom: 6px;">Aucun code généré</div>
                                <div style="font-size: var(--text-small);">Utilisez le formulaire ci-dessus pour générer votre premier code d'assiduité.</div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($codes->hasPages())
            <div class="d-flex justify-content-center" style="padding: var(--space-lg); border-top: 1px solid #f1f5f9;">
                {{ $codes->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
        const icon = btn.querySelector('i');
        icon.className = 'fas fa-check';
        btn.style.color = 'var(--success)';
        setTimeout(() => {
            icon.className = 'fas fa-copy';
            btn.style.color = '';
        }, 1800);
    }).catch(() => {
        // Fallback
        const ta = document.createElement('textarea');
        ta.value = code;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    });
}
</script>
@endpush
