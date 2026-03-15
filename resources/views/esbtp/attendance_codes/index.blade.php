@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* ── Generate Card ───────────────────────────────────────── */
    .codes-generate-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden;
        margin-bottom: var(--space-lg);
    }

    .codes-gen-header {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        padding: 1.25rem 1.5rem;
        display: flex; align-items: center; gap: 0.75rem;
    }

    .codes-gen-header-icon {
        width: 42px; height: 42px; border-radius: 10px;
        background: rgba(255,255,255,0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.1rem; color: #fff; flex-shrink: 0;
    }

    .codes-gen-title    { color: #fff; font-weight: 700; font-size: 0.95rem; margin: 0; }
    .codes-gen-subtitle { color: rgba(255,255,255,0.8); font-size: 0.8rem; margin: 0.1rem 0 0; }

    .codes-gen-body { padding: 1.25rem 1.5rem; }

    .codes-info-box {
        background: rgba(4,83,203,0.04);
        border: 1px solid rgba(4,83,203,0.12);
        border-radius: var(--radius-medium);
        padding: 0.75rem 1rem;
        margin-bottom: 1.25rem;
        display: flex; align-items: flex-start; gap: 0.6rem;
        font-size: 0.82rem; color: var(--text-secondary);
    }

    .codes-info-box i { color: #0453cb; margin-top: 2px; flex-shrink: 0; }

    .codes-gen-body .form-label {
        font-size: 0.78rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.4px;
        color: var(--text-secondary); margin-bottom: 0.3rem;
    }

    .codes-gen-body .form-control {
        border: 1.5px solid rgba(0,0,0,0.1);
        border-radius: var(--radius-small); font-size: 0.875rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .codes-gen-body .form-control:focus {
        border-color: #0453cb;
        box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
    }

    /* ── Table Card ──────────────────────────────────────────── */
    .codes-table-card {
        background: #fff;
        border-radius: var(--radius-large);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        overflow: hidden;
    }

    .codes-table-card-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        background: #f8fafc;
        display: flex; align-items: center; justify-content: space-between;
    }

    .codes-table-card-title {
        font-weight: 700; font-size: 0.95rem; color: var(--text-primary);
        display: flex; align-items: center; gap: 0.5rem;
    }

    .codes-table-card-title i { color: #0453cb; }

    /* ── Table ───────────────────────────────────────────────── */
    .codes-table { width: 100%; border-collapse: collapse; }

    .codes-table thead th {
        background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
        color: rgba(255,255,255,0.92); font-weight: 600;
        font-size: 0.75rem; text-transform: uppercase;
        letter-spacing: 0.5px; padding: 0.85rem 1.25rem;
        border: none; white-space: nowrap;
    }

    .codes-table tbody td {
        padding: 0.9rem 1.25rem;
        border-bottom: 1px solid rgba(0,0,0,0.04);
        vertical-align: middle; font-size: 0.875rem;
    }

    .codes-table tbody tr:last-child td { border-bottom: none; }
    .codes-table tbody tr:hover { background: rgba(4,83,203,0.02); }

    /* Code badge */
    .code-value {
        display: inline-flex; align-items: center; gap: 0.5rem;
        font-family: 'Courier New', monospace;
        font-weight: 800; font-size: 1rem;
        letter-spacing: 3px; color: #0453cb;
        background: rgba(4,83,203,0.08);
        padding: 0.35rem 0.75rem; border-radius: 8px;
        border: 1px solid rgba(4,83,203,0.15);
        cursor: pointer; transition: all 0.2s ease;
    }

    .code-value:hover {
        background: rgba(4,83,203,0.14);
        border-color: rgba(4,83,203,0.3);
    }

    .code-value.copied {
        background: rgba(16,185,129,0.12) !important;
        border-color: rgba(16,185,129,0.3) !important;
        color: #065f46 !important;
    }

    .code-copy-icon {
        font-size: 0.68rem; color: rgba(4,83,203,0.45); transition: color 0.2s;
    }

    .code-value:hover .code-copy-icon { color: #0453cb; }

    /* Status pills */
    .code-status {
        display: inline-flex; align-items: center; gap: 0.35rem;
        padding: 0.28rem 0.65rem; border-radius: 20px;
        font-size: 0.75rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.3px;
    }

    .code-status.cs-valid   { background: #d1fae5; color: #065f46; }
    .code-status.cs-used    { background: #e0f2fe; color: #0369a1; }
    .code-status.cs-expired { background: #f1f5f9; color: var(--text-secondary); }

    .status-pulse {
        width: 7px; height: 7px; border-radius: 50%;
        background: #10b981;
        animation: pulse-dot 1.5s ease-in-out infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.4; transform: scale(0.75); }
    }

    /* Attempts dots */
    .att-dots { display: flex; align-items: center; gap: 0.4rem; }

    .att-dots-group { display: flex; gap: 3px; }

    .att-dot {
        width: 9px; height: 9px; border-radius: 50%;
        transition: background 0.2s;
    }

    .att-dot.filled { background: #e53e3e; }
    .att-dot.empty  { background: #e5e7eb; }

    .att-dots-num { font-weight: 700; font-size: 0.82rem; }
    .att-dots-num.danger { color: #e53e3e; }
    .att-dots-num.safe   { color: var(--text-secondary); }

    /* Teacher cell */
    .teacher-cell { display: flex; align-items: center; gap: 0.5rem; }

    .teacher-avatar {
        width: 30px; height: 30px; border-radius: 8px;
        background: rgba(4,83,203,0.1);
        display: flex; align-items: center; justify-content: center;
        font-size: 0.7rem; font-weight: 700; color: #0453cb; flex-shrink: 0;
    }

    .date-main { font-weight: 600; font-size: 0.875rem; }
    .date-sub  { font-size: 0.73rem; color: var(--text-secondary); }

    /* Empty state */
    .codes-empty {
        padding: 3.5rem 1rem; text-align: center; color: var(--text-secondary);
    }

    .codes-empty-icon {
        width: 72px; height: 72px; border-radius: 20px;
        background: rgba(4,83,203,0.06);
        display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; color: #0453cb; opacity: 0.5;
        margin: 0 auto 1rem;
    }

    /* Pagination */
    .codes-pagination {
        display: flex; justify-content: center;
        padding: 1rem 1.5rem;
        border-top: 1px solid rgba(0,0,0,0.05);
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ── Header ──────────────────────────────────────────── --}}
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-key me-3"></i>Codes d'assiduité</h1>
                <p class="header-subtitle">Générez et suivez les codes d'assiduité pour la présence</p>
            </div>
            <div class="header-actions">
                <span style="background:rgba(255,255,255,0.15);color:#fff;padding:0.4rem 0.9rem;border-radius:var(--radius-medium);font-size:0.85rem;display:flex;align-items:center;gap:0.4rem;">
                    <i class="fas fa-calendar"></i>{{ now()->format('d/m/Y') }}
                </span>
            </div>
        </div>

        {{-- ── Alertes ──────────────────────────────────────────── --}}
        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        {{-- ── Formulaire génération ───────────────────────────── --}}
        <div class="codes-generate-card">
            <div class="codes-gen-header">
                <div class="codes-gen-header-icon">
                    <i class="fas fa-plus-circle"></i>
                </div>
                <div>
                    <p class="codes-gen-title">Générer un nouveau code</p>
                    <p class="codes-gen-subtitle">Le code est valable pour la journée sélectionnée</p>
                </div>
            </div>
            <div class="codes-gen-body">
                <div class="codes-info-box">
                    <i class="fas fa-info-circle"></i>
                    <span>Les codes d'assiduité permettent aux enseignants de marquer leur présence. Chaque code est unique par date et expire à minuit. Maximum 3 tentatives avant blocage.</span>
                </div>
                <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                    @csrf
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3 col-sm-6">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" required
                                   min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn-acasi primary">
                                <i class="fas fa-key me-2"></i>Générer un code
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- ── Table des codes ─────────────────────────────────── --}}
        <div class="codes-table-card">
            <div class="codes-table-card-header">
                <div class="codes-table-card-title">
                    <i class="fas fa-list"></i>Liste des codes générés
                </div>
                <span style="background:rgba(4,83,203,0.08);color:#0453cb;padding:0.2rem 0.6rem;border-radius:20px;font-size:0.78rem;font-weight:700;">
                    {{ $codes->total() }} codes
                </span>
            </div>

            <div class="table-responsive">
                <table class="codes-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Date</th>
                            <th>Expire le</th>
                            <th>Statut</th>
                            <th>Utilisé par</th>
                            <th>Tentatives</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($codes as $code)
                        <tr>
                            <td>
                                <span class="code-value" title="Cliquer pour copier" onclick="copyCode('{{ $code->code }}', this)">
                                    {{ $code->code }}
                                    <i class="fas fa-copy code-copy-icon"></i>
                                </span>
                            </td>
                            <td>
                                <div class="date-main">{{ $code->date->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="date-main">{{ $code->expires_at->format('d/m/Y') }}</div>
                                <div class="date-sub">{{ $code->expires_at->format('H:i') }}</div>
                            </td>
                            <td>
                                @if($code->is_used)
                                    <span class="code-status cs-used">
                                        <i class="fas fa-check-circle"></i>Utilisé
                                    </span>
                                @elseif($code->expires_at->isPast())
                                    <span class="code-status cs-expired">
                                        <i class="fas fa-ban"></i>Expiré
                                    </span>
                                @else
                                    <span class="code-status cs-valid">
                                        <span class="status-pulse"></span>Valide
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($code->usedByTeacher)
                                <div class="teacher-cell">
                                    <div class="teacher-avatar">
                                        {{ strtoupper(substr($code->usedByTeacher->nom, 0, 1)) }}{{ strtoupper(substr($code->usedByTeacher->prenoms, 0, 1)) }}
                                    </div>
                                    <span style="font-weight:600;font-size:0.875rem;">{{ $code->usedByTeacher->nom }} {{ $code->usedByTeacher->prenoms }}</span>
                                </div>
                                @else
                                <span style="color:var(--text-secondary);">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="att-dots">
                                    <div class="att-dots-group">
                                        @for($i = 1; $i <= 3; $i++)
                                        <div class="att-dot {{ $i <= $code->attempts ? 'filled' : 'empty' }}"></div>
                                        @endfor
                                    </div>
                                    <span class="att-dots-num {{ $code->attempts >= 3 ? 'danger' : 'safe' }}">{{ $code->attempts }}/3</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6">
                                <div class="codes-empty">
                                    <div class="codes-empty-icon"><i class="fas fa-key"></i></div>
                                    <p style="font-weight:700;color:var(--text-primary);">Aucun code généré</p>
                                    <p style="font-size:0.82rem;">Générez votre premier code d'assiduité ci-dessus</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($codes->hasPages())
            <div class="codes-pagination">
                {{ $codes->links() }}
            </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
function copyCode(code, el) {
    navigator.clipboard.writeText(code).then(() => {
        el.classList.add('copied');
        setTimeout(() => el.classList.remove('copied'), 1200);
    }).catch(() => {});
}
</script>
@endpush
