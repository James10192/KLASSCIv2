@extends('layouts.app')

@section('title', 'Gestion des Enseignants')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ═══════════════════════════════════════════════════
   TEACHERS INDEX — Premium Design (te- namespace)
   ═══════════════════════════════════════════════════ */

/* ─── Hero ─── */
.te-hero {
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    border-radius: 18px;
    padding: 2rem 2.25rem;
    color: #fff;
    margin-bottom: 1.5rem;
    position: relative;
    overflow: hidden;
}
.te-hero::before {
    content: '';
    position: absolute;
    top: -40%; right: -10%;
    width: 340px; height: 340px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.te-hero::after {
    content: '';
    position: absolute;
    bottom: -30%; left: -5%;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.te-hero-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
}
.te-hero-title {
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.te-hero-title i {
    font-size: 1.25rem;
    opacity: 0.85;
}
.te-hero-subtitle {
    font-size: 0.88rem;
    opacity: 0.75;
    margin-top: 0.35rem;
}
.te-hero-actions {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}
.te-hero-btn {
    background: rgba(255,255,255,0.15);
    border: 1px solid rgba(255,255,255,0.25);
    color: #fff;
    padding: 0.55rem 1.1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
    white-space: nowrap;
}
.te-hero-btn:hover {
    background: rgba(255,255,255,0.25);
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}
.te-hero-btn.te-btn-solid {
    background: #fff;
    color: #0453cb;
    border-color: #fff;
    font-weight: 700;
}
.te-hero-btn.te-btn-solid:hover {
    background: rgba(255,255,255,0.9);
    color: #0453cb;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.te-hero-kpis {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 0.75rem;
    position: relative;
    z-index: 1;
}
.te-hero-kpi {
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    text-align: center;
    transition: background 0.2s;
}
.te-hero-kpi:hover {
    background: rgba(255,255,255,0.18);
}
.te-hero-kpi-value {
    font-size: 1.5rem;
    font-weight: 800;
    line-height: 1.2;
}
.te-hero-kpi-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    opacity: 0.8;
    margin-top: 0.15rem;
}

/* ─── Alerts ─── */
.te-alert {
    border-radius: 12px;
    padding: 0.85rem 1.25rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.88rem;
    font-weight: 500;
    border: none;
}
.te-alert-success {
    background: rgba(16,185,129,0.1);
    color: #065f46;
    border-left: 4px solid #10b981;
}
.te-alert-danger {
    background: rgba(239,68,68,0.1);
    color: #991b1b;
    border-left: 4px solid #ef4444;
}

/* ─── Filter Bar ─── */
.te-filter-bar {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 1.25rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
}
.te-filter-row {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: flex-end;
}
.te-filter-group {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 150px;
}
.te-filter-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    margin-bottom: 0.35rem;
}
.te-filter-input,
.te-filter-select {
    width: 100%;
    padding: 0.6rem 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.88rem;
    color: #1e293b;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.te-filter-input:focus,
.te-filter-select:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
}
.te-filter-btn {
    padding: 0.6rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    white-space: nowrap;
}
.te-filter-btn:hover { opacity: 0.9; }
.te-filter-btn-reset {
    padding: 0.6rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    transition: all 0.2s;
    text-decoration: none;
    white-space: nowrap;
}
.te-filter-btn-reset:hover {
    border-color: #cbd5e1;
    color: #1e293b;
    text-decoration: none;
}

/* ─── Table Card ─── */
.te-table-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f3f4f6;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 1.5rem;
}
.te-table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid #f3f4f6;
}
.te-table-header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.te-table-header-left i {
    font-size: 1rem;
    color: #0453cb;
}
.te-table-header-left h3 {
    font-size: 0.95rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
}
.te-badge-count {
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.3rem 0.7rem;
    border-radius: 20px;
}

/* ─── Table ─── */
.te-table {
    width: 100%;
    border-collapse: collapse;
}
.te-table thead th {
    padding: 0.85rem 1rem;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #6b7280;
    background: #f8fafc;
    border-bottom: 2px solid #e5e7eb;
    text-align: left;
}
.te-table tbody tr {
    border-left: 3px solid transparent;
    transition: all 0.2s;
}
.te-table tbody tr:hover {
    background: rgba(4,83,203,0.03);
    border-left-color: #0453cb;
}
.te-table tbody td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
    font-size: 0.875rem;
}

/* ─── Avatar ─── */
.te-avatar {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.te-teacher-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.te-teacher-name {
    font-weight: 700;
    color: #111827;
    font-size: 0.88rem;
    line-height: 1.3;
}
.te-teacher-matricule {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 500;
}
.te-teacher-email {
    font-size: 0.82rem;
    color: #374151;
}
.te-teacher-phone {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* ─── Status Badge ─── */
.te-status {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.te-status-active {
    background: rgba(16,185,129,0.1);
    color: #065f46;
}
.te-status-active::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #10b981;
}
.te-status-inactive {
    background: rgba(107,114,128,0.1);
    color: #4b5563;
}
.te-status-inactive::before {
    content: '';
    width: 6px; height: 6px;
    border-radius: 50%;
    background: #9ca3af;
}

/* ─── Department Tag ─── */
.te-dept-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    background: rgba(4,83,203,0.06);
    color: #0453cb;
}

/* ─── Action Buttons ─── */
.te-actions {
    display: flex;
    gap: 0.35rem;
}
.te-action-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
}
.te-action-btn:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: rgba(4,83,203,0.04);
    text-decoration: none;
}
.te-action-btn.te-action-view:hover {
    border-color: #0453cb;
    color: #0453cb;
    background: rgba(4,83,203,0.04);
}
.te-action-btn.te-action-edit:hover {
    border-color: #f59e0b;
    color: #d97706;
    background: rgba(245,158,11,0.04);
}
.te-action-btn.te-action-toggle-off:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239,68,68,0.04);
}
.te-action-btn.te-action-toggle-on:hover {
    border-color: #10b981;
    color: #10b981;
    background: rgba(16,185,129,0.04);
}

/* ─── Bulk Checkbox ─── */
.te-checkbox {
    width: 18px;
    height: 18px;
    accent-color: #0453cb;
    cursor: pointer;
}

/* ─── Pagination ─── */
.te-pagination {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
}

/* ─── Empty State ─── */
.te-empty {
    padding: 3.5rem 2rem;
    text-align: center;
}
.te-empty-icon {
    width: 64px;
    height: 64px;
    border-radius: 16px;
    background: #f1f5f9;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
}
.te-empty-icon i {
    font-size: 1.75rem;
    color: #cbd5e1;
}
.te-empty h3 {
    font-size: 1rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 0.5rem;
}
.te-empty p {
    font-size: 0.85rem;
    color: #6b7280;
    margin: 0 0 1.25rem;
}
.te-empty-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.6rem 1.25rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    text-decoration: none;
    transition: all 0.2s;
}
.te-empty-btn:hover {
    opacity: 0.9;
    color: #fff;
    text-decoration: none;
    transform: translateY(-1px);
}

/* ─── Animations ─── */
@keyframes te-fade-up {
    from { opacity: 0; transform: translateY(12px); }
    to { opacity: 1; transform: translateY(0); }
}
.te-animate {
    animation: te-fade-up 0.5s ease-out both;
}
.te-delay-1 { animation-delay: 0.1s; }
.te-delay-2 { animation-delay: 0.2s; }
.te-delay-3 { animation-delay: 0.3s; }

/* ─── Responsive ─── */
@media (max-width: 992px) {
    .te-hero-top { flex-direction: column; }
    .te-hero-actions { width: 100%; }
    .te-hero-kpis { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .te-hero { padding: 1.5rem; border-radius: 14px; }
    .te-hero-title { font-size: 1.25rem; }
    .te-hero-kpis { grid-template-columns: 1fr 1fr; }
    .te-filter-row { flex-direction: column; }
    .te-filter-group { min-width: 100%; }
    .te-table-header { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
    .te-hero-actions { flex-direction: column; }
    .te-hero-btn { justify-content: center; }
}
@media (max-width: 576px) {
    .te-hero-kpis { grid-template-columns: 1fr; }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- ═══ Hero ═══ --}}
        <div class="te-hero te-animate">
            <div class="te-hero-top">
                <div>
                    <div class="te-hero-title">
                        <i class="fas fa-chalkboard-teacher"></i>Gestion des Enseignants
                    </div>
                    <div class="te-hero-subtitle">Profils, disponibilités et affectations du corps enseignant</div>
                </div>
                <div class="te-hero-actions">
                    <button type="button" class="te-hero-btn" data-bs-toggle="modal" data-bs-target="#bulkAvailabilityModal">
                        <i class="fas fa-calendar-check"></i>Disponibilités
                    </button>
                    <a href="{{ route('esbtp.enseignants.create') }}" class="te-hero-btn te-btn-solid">
                        <i class="fas fa-plus"></i>Nouvel Enseignant
                    </a>
                </div>
            </div>
            <div class="te-hero-kpis">
                <div class="te-hero-kpi">
                    <div class="te-hero-kpi-value">{{ $stats['total'] ?? 0 }}</div>
                    <div class="te-hero-kpi-label">Total</div>
                </div>
                <div class="te-hero-kpi">
                    <div class="te-hero-kpi-value">{{ $stats['active'] ?? 0 }}</div>
                    <div class="te-hero-kpi-label">Actifs</div>
                </div>
                <div class="te-hero-kpi">
                    <div class="te-hero-kpi-value">{{ $stats['inactive'] ?? 0 }}</div>
                    <div class="te-hero-kpi-label">Inactifs</div>
                </div>
                @if(($stats['permanent'] ?? 0) > 0 || ($stats['temporary'] ?? 0) > 0)
                <div class="te-hero-kpi">
                    <div class="te-hero-kpi-value">{{ $stats['permanent'] ?? 0 }}</div>
                    <div class="te-hero-kpi-label">Permanents</div>
                </div>
                @endif
            </div>
        </div>

        {{-- ═══ Alerts ═══ --}}
        @if(session('success'))
            <div class="te-alert te-alert-success te-animate te-delay-1">
                <i class="fas fa-check-circle"></i>{{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="te-alert te-alert-danger te-animate te-delay-1">
                <i class="fas fa-exclamation-circle"></i>{{ session('error') }}
            </div>
        @endif

        {{-- ═══ Filter Bar ═══ --}}
        <div class="te-filter-bar te-animate te-delay-1">
            <form method="GET" action="{{ route('esbtp.enseignants.index') }}">
                <div class="te-filter-row">
                    <div class="te-filter-group" style="flex: 2;">
                        <label class="te-filter-label">Recherche</label>
                        <input type="text" name="search" class="te-filter-input" placeholder="Nom, email, matricule..." value="{{ request('search') }}">
                    </div>
                    <div class="te-filter-group">
                        <label class="te-filter-label">Statut</label>
                        <select name="status" class="te-filter-select">
                            <option value="">Tous</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Actifs</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactifs</option>
                        </select>
                    </div>
                    <div class="te-filter-group">
                        <label class="te-filter-label">Département</label>
                        <select name="department_id" class="te-filter-select">
                            <option value="">Tous</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="te-filter-group" style="flex: 0 0 auto; min-width: auto;">
                        <label class="te-filter-label">&nbsp;</label>
                        <div style="display: flex; gap: 0.5rem;">
                            <button type="submit" class="te-filter-btn">
                                <i class="fas fa-search"></i>Filtrer
                            </button>
                            @if(request()->hasAny(['search', 'status', 'department_id']))
                                <a href="{{ route('esbtp.enseignants.index') }}" class="te-filter-btn-reset">
                                    <i class="fas fa-times"></i>Reset
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- ═══ Table Card ═══ --}}
        <div class="te-table-card te-animate te-delay-2">
            <div class="te-table-header">
                <div class="te-table-header-left">
                    <i class="fas fa-list"></i>
                    <h3>Liste des Enseignants</h3>
                </div>
                <span class="te-badge-count">{{ $teachers->total() ?? 0 }} résultat{{ ($teachers->total() ?? 0) > 1 ? 's' : '' }}</span>
            </div>

            @if(isset($teachers) && $teachers->count() > 0)
                <div class="table-responsive">
                    <table class="te-table">
                        <thead>
                            <tr>
                                <th style="width: 40px; padding-left: 1.25rem;"></th>
                                <th>Enseignant</th>
                                <th>Contact</th>
                                <th>Spécialisation</th>
                                <th>Département</th>
                                <th>Statut</th>
                                <th style="width: 130px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teachers as $teacher)
                                <tr>
                                    <td style="padding-left: 1.25rem;">
                                        <input type="checkbox" class="te-checkbox bulk-select-checkbox" value="{{ $teacher->id }}">
                                    </td>
                                    <td>
                                        <div class="te-teacher-info">
                                            <div class="te-avatar">
                                                {{ $teacher->user ? strtoupper(substr($teacher->user->name, 0, 2)) : 'NA' }}
                                            </div>
                                            <div>
                                                <div class="te-teacher-name">{{ $teacher->user->name ?? 'N/A' }}</div>
                                                <div class="te-teacher-matricule">{{ $teacher->matricule ?? '—' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="te-teacher-email">{{ $teacher->user->email ?? 'N/A' }}</div>
                                        @if($teacher->user && $teacher->user->phone)
                                            <div class="te-teacher-phone">{{ $teacher->user->phone }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span style="font-size: 0.85rem; color: #374151;">{{ $teacher->specialization ?? '—' }}</span>
                                    </td>
                                    <td>
                                        @if($teacher->department)
                                            <span class="te-dept-tag">
                                                <i class="fas fa-building" style="font-size: 0.65rem;"></i>
                                                {{ $teacher->department->name }}
                                            </span>
                                        @else
                                            <span style="color: #94a3b8; font-size: 0.82rem;">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="te-status {{ $teacher->status === 'active' ? 'te-status-active' : 'te-status-inactive' }}">
                                            {{ $teacher->status === 'active' ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="te-actions" style="justify-content: center;">
                                            <a href="{{ route('esbtp.enseignants.show', $teacher) }}"
                                               class="te-action-btn te-action-view" title="Voir le profil">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.enseignants.edit', $teacher) }}"
                                               class="te-action-btn te-action-edit" title="Modifier">
                                                <i class="fas fa-pen"></i>
                                            </a>
                                            <form action="{{ route('esbtp.enseignants.toggleStatus', $teacher) }}"
                                                  method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit"
                                                        class="te-action-btn {{ $teacher->status === 'active' ? 'te-action-toggle-off' : 'te-action-toggle-on' }}"
                                                        title="{{ $teacher->status === 'active' ? 'Désactiver' : 'Activer' }}">
                                                    <i class="fas fa-{{ $teacher->status === 'active' ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="te-pagination">
                    {{ $teachers->appends(request()->query())->links() }}
                </div>
            @else
                <div class="te-empty">
                    <div class="te-empty-icon">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <h3>Aucun enseignant trouvé</h3>
                    <p>Ajoutez votre premier enseignant ou modifiez vos filtres de recherche.</p>
                    <a href="{{ route('esbtp.enseignants.create') }}" class="te-empty-btn">
                        <i class="fas fa-plus"></i>Nouvel Enseignant
                    </a>
                </div>
            @endif
        </div>

    </div>
</div>

<!-- Modal Sélection pour Bulk Availability -->
<div class="modal fade" id="bulkAvailabilityModal" tabindex="-1" aria-labelledby="bulkAvailabilityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 18px; border: none; box-shadow: 0 25px 50px rgba(0,0,0,0.25);">
            <form method="GET" action="{{ route('esbtp.enseignants.bulk-availability') }}" id="bulk-availability-form">
                <div class="modal-header" style="background: linear-gradient(135deg, #0f3f87 0%, #0453cb 100%); color: white; border-radius: 18px 18px 0 0; padding: 1.25rem 1.5rem;">
                    <h5 class="modal-title fw-bold" id="bulkAvailabilityModalLabel">
                        <i class="fas fa-calendar-check me-2"></i>Modifier les disponibilités
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <p class="text-muted mb-3">Sélectionnez les enseignants dont vous souhaitez modifier les disponibilités.</p>

                    {{-- Barre de recherche --}}
                    <div class="mb-3">
                        <input type="text" id="bulk-modal-search" class="form-control" placeholder="Rechercher un enseignant...">
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bulk-select-all-modal">
                            <label class="form-check-label fw-semibold" for="bulk-select-all-modal">
                                Tout sélectionner
                            </label>
                        </div>
                        <span class="badge bg-light text-dark" id="selected-count">0 sélectionné(s)</span>
                    </div>

                    @php
                        $allTeachers = \App\Models\ESBTPTeacher::with(['user', 'department'])
                            ->where('status', 'active')
                            ->orderBy('id')
                            ->get();
                    @endphp

                    @if($allTeachers->isEmpty())
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Aucun enseignant actif disponible.
                        </div>
                    @else
                        <div class="list-group" style="max-height: 400px; overflow-y: auto;">
                            @foreach($allTeachers as $t)
                                <label class="list-group-item d-flex align-items-center gap-3 bulk-modal-item"
                                       style="cursor: pointer;"
                                       data-name="{{ strtolower($t->user->name ?? '') }}"
                                       data-spec="{{ strtolower($t->specialization ?? '') }}">
                                    <input class="form-check-input bulk-modal-checkbox"
                                           type="checkbox" name="ids[]"
                                           value="{{ $t->id }}">
                                    <div class="te-avatar" style="width: 36px; height: 36px; font-size: 0.75rem; border-radius: 10px;">
                                        {{ $t->user ? strtoupper(substr($t->user->name, 0, 2)) : 'NA' }}
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $t->user->name ?? 'N/A' }}</div>
                                        <div class="small text-muted">
                                            {{ $t->specialization ?? 'Pas de spécialisation' }}
                                            @if($t->department)
                                                · {{ $t->department->name }}
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer" style="border-top: 1px solid #e2e8f0; padding: 1rem 1.5rem;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" id="bulk-availability-submit" {{ $allTeachers->isEmpty() ? 'disabled' : '' }}>
                        <i class="fas fa-arrow-right me-1"></i>Modifier les disponibilités
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // ═══ Modal Bulk Availability ═══
    const bulkModal = document.getElementById('bulkAvailabilityModal');
    if (bulkModal) {
        const selectAll = document.getElementById('bulk-select-all-modal');
        const submitButton = document.getElementById('bulk-availability-submit');
        const selectedCount = document.getElementById('selected-count');
        const searchInput = document.getElementById('bulk-modal-search');
        const items = bulkModal.querySelectorAll('.bulk-modal-item');
        const checkboxes = () => bulkModal.querySelectorAll('.bulk-modal-checkbox');

        const isVisible = item => !item.classList.contains('d-none');

        const updateState = () => {
            const boxes = Array.from(checkboxes());
            const visibleBoxes = boxes.filter(box => isVisible(box.closest('.bulk-modal-item')));
            const checkedCount = boxes.filter(box => box.checked).length;
            const visibleCheckedCount = visibleBoxes.filter(box => box.checked).length;

            if (selectAll) {
                selectAll.checked = visibleBoxes.length > 0 && visibleCheckedCount === visibleBoxes.length;
                selectAll.indeterminate = visibleCheckedCount > 0 && visibleCheckedCount < visibleBoxes.length;
            }
            if (submitButton) submitButton.disabled = checkedCount === 0;
            if (selectedCount) selectedCount.textContent = checkedCount + ' sélectionné(s)';
        };

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                items.forEach(item => {
                    const name = item.dataset.name || '';
                    const spec = item.dataset.spec || '';
                    item.classList.toggle('d-none', query && !name.includes(query) && !spec.includes(query));
                });
                updateState();
            });
        }

        if (selectAll) {
            selectAll.addEventListener('change', () => {
                items.forEach(item => {
                    if (isVisible(item)) {
                        const cb = item.querySelector('.bulk-modal-checkbox');
                        if (cb) cb.checked = selectAll.checked;
                    }
                });
                selectAll.indeterminate = false;
                updateState();
            });
        }

        checkboxes().forEach(cb => cb.addEventListener('change', updateState));
        updateState();
    }

    // ═══ Sync table checkboxes → modal ═══
    document.querySelectorAll('.bulk-select-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const modalCb = document.querySelector(`.bulk-modal-checkbox[value="${this.value}"]`);
            if (modalCb) {
                modalCb.checked = this.checked;
                modalCb.dispatchEvent(new Event('change'));
            }
        });
    });
});
</script>
@endpush
@endsection
