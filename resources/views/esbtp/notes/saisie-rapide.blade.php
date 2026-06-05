@extends('layouts.app')

@section('title', 'Saisie des notes : ' . $evaluation->titre . ' - KLASSCI')

@section('page_title', 'Saisie rapide des notes')

@push('styles')
<style>
    /* ─── Scoped Premium — Saisie Rapide ─────────────────────────── */

    /* ── Header ──────────────────────────────────────────────────── */
    .nm-sr-header {
        background: linear-gradient(135deg, var(--primary) 0%, #5e91de 100%);
        color: #fff;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        position: relative;
        overflow: hidden;
    }
    .nm-sr-header::before {
        content: '';
        position: absolute;
        top: -40%; right: -10%;
        width: 280px; height: 280px;
        background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .nm-sr-header-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: var(--space-md);
        position: relative;
        z-index: 1;
    }
    .nm-sr-header-left { display: flex; align-items: center; gap: var(--space-md); }
    .nm-sr-header-icon {
        width: 56px; height: 56px;
        border-radius: var(--radius-medium);
        background: rgba(255,255,255,0.15);
        backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        color: #fff;
        flex-shrink: 0;
    }
    .nm-sr-header h1 { color: #fff; margin: 0; font-size: 1.2rem; font-weight: 700; }
    .nm-sr-header .nm-sr-subtitle { color: rgba(255,255,255,0.8); margin: 3px 0 0; font-size: 0.84rem; }
    .nm-sr-header-actions { display: flex; align-items: center; gap: var(--space-sm); flex-wrap: wrap; }
    .nm-sr-header-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: var(--radius-small);
        font-size: 0.8rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        border: 1px solid rgba(255,255,255,0.3);
        color: #fff;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(4px);
    }
    .nm-sr-header-btn:hover { background: rgba(255,255,255,0.25); color: #fff; }
    .nm-sr-header-btn--danger { background: rgba(220,38,38,0.8); border-color: rgba(220,38,38,0.6); }
    .nm-sr-header-btn--danger:hover { background: rgba(220,38,38,1); }

    /* ── Info Strip ───────────────────────────────────────────────── */
    .nm-sr-info-strip {
        display: flex;
        flex-wrap: wrap;
        gap: var(--space-sm);
        padding: var(--space-md) var(--space-lg);
        background: var(--surface);
        border-radius: var(--radius-medium);
        border: 1px solid rgba(0,0,0,0.06);
        margin-bottom: var(--space-lg);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .nm-sr-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: var(--radius-small);
        background: rgba(4,83,203,0.04);
        font-size: 0.82rem;
        white-space: nowrap;
    }
    .nm-sr-info-item i { color: var(--primary); font-size: 0.85rem; opacity: 0.7; }
    .nm-sr-info-label { color: var(--text-secondary); font-weight: 500; }
    .nm-sr-info-value { color: var(--text-primary); font-weight: 700; }
    .nm-sr-info-item--status {
        background: rgba(16,185,129,0.08);
    }
    .nm-sr-info-item--status.warning {
        background: rgba(245,158,11,0.08);
    }

    /* ── Alert ────────────────────────────────────────────────────── */
    .nm-sr-alert {
        display: flex;
        align-items: center;
        gap: var(--space-md);
        padding: var(--space-md) var(--space-lg);
        border-radius: var(--radius-medium);
        margin-bottom: var(--space-lg);
        font-size: 0.88rem;
    }
    .nm-sr-alert--info {
        background: rgba(4,83,203,0.05);
        border: 1px solid rgba(4,83,203,0.15);
        color: var(--text-primary);
    }
    .nm-sr-alert--info i { color: var(--primary); }
    .nm-sr-alert--success {
        background: rgba(16,185,129,0.05);
        border: 1px solid rgba(16,185,129,0.2);
    }
    .nm-sr-alert--success i { color: var(--success); }
    .nm-sr-alert--danger {
        background: rgba(239,68,68,0.05);
        border: 1px solid rgba(239,68,68,0.2);
    }
    .nm-sr-alert--danger i { color: #dc2626; }
    .nm-sr-alert-dismiss {
        margin-left: auto;
        background: none;
        border: none;
        font-size: 1.1rem;
        color: var(--text-muted);
        cursor: pointer;
        padding: 4px;
    }

    /* ── Card ─────────────────────────────────────────────────────── */
    .nm-sr-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .nm-sr-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid rgba(0,0,0,0.06);
        flex-wrap: wrap;
        gap: var(--space-sm);
    }
    .nm-sr-card-title {
        font-size: 0.9rem;
        font-weight: 700;
        color: var(--text-primary);
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }
    .nm-sr-card-title i { color: var(--primary); }
    .nm-sr-card-actions { display: flex; align-items: center; gap: var(--space-sm); flex-wrap: wrap; }
    .nm-sr-search {
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: var(--radius-small);
        padding: 7px 14px;
        font-size: 0.84rem;
        width: 220px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .nm-sr-search:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
    }
    .nm-sr-badge-readonly {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 5px 12px;
        border-radius: var(--radius-small);
        font-size: 0.75rem;
        font-weight: 600;
        background: rgba(4,83,203,0.08);
        color: var(--primary);
        border: 1px solid rgba(4,83,203,0.15);
    }
    .nm-sr-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 16px;
        border-radius: var(--radius-small);
        font-size: 0.82rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .nm-sr-btn--primary {
        background: linear-gradient(135deg, var(--primary), #3b7ddb);
        color: #fff;
        box-shadow: 0 2px 8px rgba(4,83,203,0.25);
    }
    .nm-sr-btn--primary:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(4,83,203,0.35); }
    .nm-sr-btn--secondary {
        background: rgba(0,0,0,0.04);
        color: var(--text-primary);
        border: 1px solid rgba(0,0,0,0.1);
    }
    .nm-sr-btn--secondary:hover { background: rgba(0,0,0,0.08); }
    .nm-sr-btn--success {
        background: linear-gradient(135deg, var(--success), #34d399);
        color: #fff;
        box-shadow: 0 2px 8px rgba(16,185,129,0.25);
    }
    .nm-sr-btn--success:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(16,185,129,0.35); }

    /* ── Premium Table ─────────────────────────────────────────────── */
    .nm-sr-table { width: 100%; border-collapse: collapse; }
    .nm-sr-table thead tr { background: linear-gradient(135deg, var(--primary), #3b7ddb); }
    .nm-sr-table th {
        color: #fff;
        padding: 12px 16px;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        font-weight: 600;
        border: none;
        white-space: nowrap;
    }
    .nm-sr-table tbody tr {
        border-bottom: 1px solid rgba(0,0,0,0.04);
        transition: background 0.15s ease;
    }
    .nm-sr-table tbody tr:last-child { border-bottom: none; }
    .nm-sr-table tbody tr:hover { background: rgba(4,83,203,0.03); }
    .nm-sr-table tbody tr.table-active { background: rgba(4,83,203,0.06); }
    .nm-sr-table td {
        padding: 10px 16px;
        font-size: 0.84rem;
        color: var(--text-primary);
        vertical-align: middle;
    }
    .nm-sr-table .nm-sr-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.3px;
        text-transform: uppercase;
        flex-shrink: 0;
        background: rgba(4,83,203,0.1);
        color: var(--primary);
    }
    .nm-sr-student-info { display: flex; align-items: center; gap: 10px; }
    .nm-sr-student-name { font-weight: 600; font-size: 0.84rem; }
    .nm-sr-student-matricule { font-size: 0.72rem; color: var(--text-muted); font-family: monospace; }

    /* Input note dans la table */
    .nm-sr-table .note-input {
        width: 90px;
        text-align: center;
        border: 1px solid rgba(0,0,0,0.12);
        border-radius: var(--radius-small);
        padding: 6px 10px;
        font-size: 0.88rem;
        font-weight: 600;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .nm-sr-table .note-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.12);
        outline: none;
    }
    .nm-sr-table .note-input:disabled {
        background: rgba(0,0,0,0.03);
        color: var(--text-muted);
    }
    .nm-sr-table .note-input.is-invalid {
        border-color: #dc2626;
        box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
    }
    .nm-sr-table .commentaire-input {
        width: 100%;
        border: 1px solid rgba(0,0,0,0.08);
        border-radius: var(--radius-small);
        padding: 6px 10px;
        font-size: 0.82rem;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }
    .nm-sr-table .commentaire-input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(4,83,203,0.08);
        outline: none;
    }
    .nm-sr-table .commentaire-input:disabled { background: rgba(0,0,0,0.02); }

    /* Row states */
    .nm-sr-table tbody tr.bg-light-success { background: rgba(16,185,129,0.04); }
    .nm-sr-table tbody tr.bg-light-danger { background: rgba(239,68,68,0.04); }
    .nm-sr-table tbody tr.modified { background: rgba(245,158,11,0.06); }

    /* Absent toggle */
    .nm-sr-table .form-check-input {
        width: 2.2em;
        height: 1.1em;
        cursor: pointer;
    }
    .nm-sr-table .form-check-input:checked {
        background-color: #dc2626;
        border-color: #dc2626;
    }

    /* ── Progress Footer ──────────────────────────────────────────── */
    .nm-sr-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-md) var(--space-lg);
        border-top: 1px solid rgba(0,0,0,0.06);
        flex-wrap: wrap;
        gap: var(--space-md);
    }
    .nm-sr-progress-wrap { flex: 1; max-width: 500px; min-width: 200px; }
    .nm-sr-progress {
        height: 10px;
        background: rgba(0,0,0,0.06);
        border-radius: 5px;
        overflow: hidden;
    }
    .nm-sr-progress-bar {
        height: 100%;
        border-radius: 5px;
        transition: width 0.4s cubic-bezier(0.22, 1, 0.36, 1), background 0.3s ease;
    }
    .nm-sr-progress-bar.bg-danger { background: linear-gradient(90deg, #dc2626, #ef4444); }
    .nm-sr-progress-bar.bg-warning { background: linear-gradient(90deg, #d97706, #f59e0b); }
    .nm-sr-progress-bar.bg-info { background: linear-gradient(90deg, #0891b2, #22d3ee); }
    .nm-sr-progress-bar.bg-success { background: linear-gradient(90deg, var(--success), #34d399); }
    .nm-sr-progress-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--text-secondary);
        margin-top: 4px;
    }

    /* ── Notification ─────────────────────────────────────────────── */
    .save-notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: var(--space-md) var(--space-lg);
        background: linear-gradient(135deg, var(--primary), #3b7ddb);
        color: white;
        border-radius: var(--radius-small);
        box-shadow: 0 4px 20px rgba(4,83,203,0.3);
        z-index: 1050;
        animation: nm-sr-fadeInOut 2s forwards;
    }
    @keyframes nm-sr-fadeInOut {
        0% { opacity: 0; transform: translateY(-10px); }
        10% { opacity: 1; transform: translateY(0); }
        90% { opacity: 1; }
        100% { opacity: 0; }
    }

    /* ── Responsive ────────────────────────────────────────────────── */
    @media (max-width: 768px) {
        .nm-sr-header-inner { flex-direction: column; text-align: center; }
        .nm-sr-header-left { flex-direction: column; }
        .nm-sr-header-actions { justify-content: center; }
        .nm-sr-info-strip { flex-direction: column; }
        .nm-sr-card-header { flex-direction: column; align-items: stretch; }
        .nm-sr-card-actions { justify-content: stretch; }
        .nm-sr-search { width: 100%; }
        .nm-sr-footer { flex-direction: column; }
    }
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content" style="padding: 1.5rem; max-width: 100%; overflow-x: hidden;">

        {{-- ── Premium Header ──────────────────────────────────────── --}}
        <div class="nm-sr-header">
            <div class="nm-sr-header-inner">
                <div class="nm-sr-header-left">
                    <div class="nm-sr-header-icon">
                        <i class="fas fa-pen-alt"></i>
                    </div>
                    <div>
                        <h1>{{ $evaluation->titre }}</h1>
                        <p class="nm-sr-subtitle">
                            {{ $evaluation->matiere->name ?? $evaluation->matiere->nom ?? '-' }}
                            &middot; {{ $evaluation->classe->name ?? $evaluation->classe->nom ?? '-' }}
                            &middot; {{ date('d/m/Y', strtotime($evaluation->date_evaluation)) }}
                        </p>
                    </div>
                </div>
                <div class="nm-sr-header-actions">
                    <a href="{{ route('esbtp.notes.saisie-rapide.pdf-preview', $evaluation) }}" class="nm-sr-header-btn" target="_blank" title="Aperçu du PDF vierge dans un nouvel onglet">
                        <i class="fas fa-eye"></i> Aperçu vierge
                    </a>
                    <a href="{{ route('esbtp.notes.saisie-rapide.pdf', $evaluation) }}" class="nm-sr-header-btn nm-sr-header-btn--danger">
                        <i class="fas fa-file-pdf"></i> PDF vierge
                    </a>
                    <a href="{{ route('esbtp.evaluations.pdf-preview', $evaluation) }}" class="nm-sr-header-btn" target="_blank" title="Aperçu PDF avec notes">
                        <i class="fas fa-eye"></i> Aperçu
                    </a>
                    <a href="{{ route('esbtp.evaluations.pdf', $evaluation) }}" class="nm-sr-header-btn">
                        <i class="fas fa-file-export"></i> Exporter
                    </a>
                    <a href="{{ route('esbtp.evaluations.show', $evaluation) }}" class="nm-sr-header-btn">
                        <i class="fas fa-eye"></i> Voir
                    </a>
                    <a href="{{ route('esbtp.evaluations.index') }}" class="nm-sr-header-btn">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- ── Flash messages ──────────────────────────────────────── --}}
        @if (session('success'))
            <div class="nm-sr-alert nm-sr-alert--success">
                <i class="fas fa-check-circle"></i>
                <span>{{ session('success') }}</span>
                <button class="nm-sr-alert-dismiss" onclick="this.closest('.nm-sr-alert').remove()">&times;</button>
            </div>
        @endif
        @if (session('error'))
            <div class="nm-sr-alert nm-sr-alert--danger">
                <i class="fas fa-exclamation-circle"></i>
                <span>{{ session('error') }}</span>
                <button class="nm-sr-alert-dismiss" onclick="this.closest('.nm-sr-alert').remove()">&times;</button>
            </div>
        @endif

        {{-- ── Info Strip ──────────────────────────────────────────── --}}
        <div class="nm-sr-info-strip">
            <div class="nm-sr-info-item">
                <i class="fas fa-users"></i>
                <span class="nm-sr-info-label">Classe</span>
                <span class="nm-sr-info-value">{{ $evaluation->classe->name ?? $evaluation->classe->nom ?? '-' }}</span>
            </div>
            <div class="nm-sr-info-item">
                <i class="fas fa-book"></i>
                <span class="nm-sr-info-label">Matiere</span>
                <span class="nm-sr-info-value">{{ $evaluation->matiere->name ?? $evaluation->matiere->nom ?? '-' }}</span>
            </div>
            @php
                $typeLabels = [
                    'examen' => 'Examen', 'devoir' => 'Devoir', 'tp' => 'TP',
                    'projet' => 'Projet', 'controle' => 'Controle', 'rattrapage' => 'Rattrapage',
                ];
            @endphp
            <div class="nm-sr-info-item">
                <i class="fas fa-tag"></i>
                <span class="nm-sr-info-label">Type</span>
                <span class="nm-sr-info-value">{{ $typeLabels[$evaluation->type] ?? ucfirst($evaluation->type) }}</span>
            </div>
            <div class="nm-sr-info-item">
                <i class="far fa-calendar-alt"></i>
                <span class="nm-sr-info-label">Date</span>
                <span class="nm-sr-info-value">{{ date('d/m/Y', strtotime($evaluation->date_evaluation)) }}</span>
            </div>
            <div class="nm-sr-info-item">
                <i class="fas fa-calculator"></i>
                <span class="nm-sr-info-label">Bareme</span>
                <span class="nm-sr-info-value">{{ $evaluation->bareme }} pts</span>
            </div>
            <div class="nm-sr-info-item">
                <i class="fas fa-balance-scale"></i>
                <span class="nm-sr-info-label">Coeff</span>
                <span class="nm-sr-info-value">{{ $evaluation->coefficient }}</span>
            </div>
            <div class="nm-sr-info-item nm-sr-info-item--status {{ $notes->count() > 0 ? '' : 'warning' }}">
                <i class="fas {{ $notes->count() > 0 ? 'fa-check-circle' : 'fa-exclamation-circle' }}" style="color: {{ $notes->count() > 0 ? 'var(--success)' : '#d97706' }};"></i>
                <span class="nm-sr-info-value" style="color: {{ $notes->count() > 0 ? '#065f46' : '#92400e' }};">
                    {{ $notes->count() > 0 ? $notes->count() . ' notes saisies' : 'Aucune note saisie' }}
                </span>
            </div>
        </div>

        {{-- ── Read-only alert ─────────────────────────────────────── --}}
        @php
            $hasExistingNotes = $notes->isNotEmpty();
            // Lecture seule = des notes existent ET l'utilisateur ne peut ni les modifier (notes.edit)
            // ni gérer ses propres notes en tant qu'enseignant créateur (notes.manage_own + créateur).
            // Le superAdmin et tout rôle custom avec notes.edit passe via Gate::before — pas de hasRole hardcodé.
            $authUser = Auth::user();
            $canEditNotes = $authUser && $authUser->can('notes.edit');
            $isOwnerTeacher = $authUser
                && $authUser->can('notes.manage_own')
                && ($evaluation->enseignant_id === $authUser->id || $evaluation->created_by === $authUser->id);
            $isReadOnly = $hasExistingNotes && ! $canEditNotes && ! $isOwnerTeacher;
        @endphp

        @if($isReadOnly)
            <div class="nm-sr-alert nm-sr-alert--info">
                <i class="fas fa-lock"></i>
                <span><strong>Consultation uniquement</strong> — Des notes existent déjà. Vous pouvez les consulter mais pas les modifier.</span>
            </div>
        @endif

        {{-- ── Notes Table ─────────────────────────────────────────── --}}
        <form action="{{ route('esbtp.notes.store-batch') }}" method="POST" id="notesForm">
            @csrf
            <input type="hidden" name="evaluation_id" value="{{ $evaluation->id }}">

            <div class="nm-sr-card">
                <div class="nm-sr-card-header">
                    <div class="nm-sr-card-title">
                        <i class="fas fa-list-ol"></i>
                        Etudiants ({{ $etudiants->count() }})
                    </div>
                    <div class="nm-sr-card-actions">
                        <input type="search" class="nm-sr-search" id="searchStudent" placeholder="Rechercher...">
                        @if(!$isReadOnly)
                            <button type="button" class="nm-sr-btn nm-sr-btn--secondary" id="resetForm">
                                <i class="fas fa-undo"></i> Reinitialiser
                            </button>
                            <button type="submit" class="nm-sr-btn nm-sr-btn--primary" id="saveAllBtn">
                                <i class="fas fa-save"></i> Enregistrer
                            </button>
                        @else
                            <span class="nm-sr-badge-readonly">
                                <i class="fas fa-lock"></i> Lecture seule
                            </span>
                        @endif
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="nm-sr-table" id="notesTable">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="30%">Etudiant</th>
                                <th width="15%" class="text-center">Note / {{ $evaluation->bareme }}</th>
                                <th width="8%" class="text-center" title="Absent"><i class="fas fa-user-slash"></i></th>
                                <th width="42%">Commentaire</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($etudiants as $index => $etudiant)
                                @php
                                    $note = $notes->where('etudiant_id', $etudiant->id)->first();
                                    $rowClass = $note ? 'bg-light-success' : '';
                                    $initials = mb_strtoupper(mb_substr($etudiant->prenoms ?? '', 0, 1) . mb_substr($etudiant->nom ?? '', 0, 1));
                                    $colors = ['#0453cb','#0891b2','#10b981','#1b64d4','#059669','#0e7490'];
                                    $bgColor = $colors[crc32($etudiant->id ?? '0') % count($colors)];
                                @endphp
                                <tr class="{{ $rowClass }} student-row">
                                    <td class="fw-medium" style="color: var(--text-muted);">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="nm-sr-student-info">
                                            <div class="nm-sr-avatar" style="background: {{ $bgColor }}15; color: {{ $bgColor }};">
                                                {{ $initials }}
                                            </div>
                                            <div>
                                                <div class="nm-sr-student-name student-name">{{ $etudiant->nom_complet ?? ($etudiant->nom . ' ' . $etudiant->prenoms) }}</div>
                                                <div class="nm-sr-student-matricule">{{ $etudiant->matricule }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <input type="hidden" name="notes[{{ $etudiant->id }}][etudiant_id]" value="{{ $etudiant->id }}">
                                        <input type="number"
                                            class="note-input @error('notes.' . $etudiant->id . '.valeur') is-invalid @enderror"
                                            name="notes[{{ $etudiant->id }}][valeur]"
                                            value="{{ old('notes.' . $etudiant->id . '.valeur', $note ? $note->valeur : '') }}"
                                            min="0"
                                            max="{{ $evaluation->bareme }}"
                                            step="0.01"
                                            inputmode="decimal"
                                            lang="fr"
                                            {{ ($note && $note->absent) || $isReadOnly ? 'disabled' : '' }}
                                            autocomplete="off">
                                        @error('notes.' . $etudiant->id . '.valeur')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center m-0">
                                            <input class="form-check-input absent-checkbox"
                                                type="checkbox"
                                                name="notes[{{ $etudiant->id }}][absent]"
                                                value="1"
                                                role="switch"
                                                {{ old('notes.' . $etudiant->id . '.absent', $note && $note->absent ? '1' : '') ? 'checked' : '' }}
                                                {{ $isReadOnly ? 'disabled' : '' }}>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text"
                                            class="commentaire-input"
                                            name="notes[{{ $etudiant->id }}][commentaire]"
                                            value="{{ old('notes.' . $etudiant->id . '.commentaire', $note ? $note->commentaire : '') }}"
                                            maxlength="255"
                                            placeholder="Commentaire optionnel"
                                            {{ $isReadOnly ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center" style="padding: var(--space-xl);">
                                        <div style="color: var(--text-muted);">
                                            <i class="fas fa-users" style="font-size: 2rem; opacity: 0.3; margin-bottom: var(--space-sm);"></i>
                                            <p style="margin: 0;">Aucun etudiant inscrit dans cette classe.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- ── Footer: Progress + Save ─────────────────────── --}}
                <div class="nm-sr-footer">
                    <div class="nm-sr-progress-wrap">
                        <div class="nm-sr-progress" id="progressBar">
                            <div class="nm-sr-progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                        </div>
                        <div class="nm-sr-progress-label"><span id="progressText">0%</span> des notes saisies</div>
                    </div>
                    @if(!$isReadOnly)
                        <button type="submit" class="nm-sr-btn nm-sr-btn--success" id="saveAllBtnBottom">
                            <i class="fas fa-save"></i> Enregistrer toutes les notes
                        </button>
                    @endif
                </div>
            </div>
        </form>

    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Progress bar
    var updateProgressBar = function() {
        var totalStudents = {{ $etudiants->count() }};
        if (totalStudents === 0) return;

        var filledNotes = 0;
        $('.note-input').each(function() {
            if ($(this).val() !== '' || $(this).closest('tr').find('.absent-checkbox').is(':checked')) {
                filledNotes++;
            }
        });

        var percentage = Math.round((filledNotes / totalStudents) * 100);
        $('#progressBar .nm-sr-progress-bar').css('width', percentage + '%');
        $('#progressText').text(percentage + '%');

        var $bar = $('#progressBar .nm-sr-progress-bar');
        $bar.removeClass('bg-success bg-warning bg-info bg-danger');
        if (percentage < 25) $bar.addClass('bg-danger');
        else if (percentage < 50) $bar.addClass('bg-warning');
        else if (percentage < 75) $bar.addClass('bg-info');
        else $bar.addClass('bg-success');
    };

    updateProgressBar();

    // Absent toggle
    $('.absent-checkbox').change(function() {
        var noteInput = $(this).closest('tr').find('.note-input');
        if ($(this).is(':checked')) {
            noteInput.prop('disabled', true).val('');
            $(this).closest('tr').addClass('bg-light-danger').removeClass('bg-light-success');
        } else {
            noteInput.prop('disabled', false);
            $(this).closest('tr').removeClass('bg-light-danger');
        }
        $(this).closest('tr').addClass('modified');
        updateProgressBar();
    });

    // Mark modified
    $('.note-input, .commentaire-input').on('input', function() {
        $(this).closest('tr').addClass('modified');
        updateProgressBar();
    });

    // Reset
    $('#resetForm').click(function() {
        if (confirm('Reinitialiser le formulaire ? Les modifications non enregistrees seront perdues.')) {
            $('.note-input').val('').prop('disabled', false);
            $('.absent-checkbox').prop('checked', false);
            $('input[name$="[commentaire]"]').val('');
            $('.modified').removeClass('modified');
            $('.bg-light-danger').removeClass('bg-light-danger');
            updateProgressBar();
        }
    });

    // Form validation
    $('#notesForm').submit(function() {
        var valid = true;
        $('.note-input:not(:disabled)').each(function() {
            var value = $(this).val();
            if (value !== '' && (isNaN(value) || parseFloat(value) < 0 || parseFloat(value) > {{ $evaluation->bareme }})) {
                alert('Note invalide (0 a {{ $evaluation->bareme }}). Verifiez les champs en rouge.');
                $(this).focus().addClass('is-invalid');
                valid = false;
                return false;
            }
        });
        if (valid) {
            $('#saveAllBtn, #saveAllBtnBottom').html('<i class="fas fa-spinner fa-spin me-1"></i> Enregistrement...');
            $('#saveAllBtn, #saveAllBtnBottom').prop('disabled', true);
        }
        return valid;
    });

    // Search
    $('#searchStudent').on('input', function() {
        var value = $(this).val().toLowerCase();
        $('.student-row').each(function() {
            var name = $(this).find('.student-name').text().toLowerCase();
            var matricule = $(this).find('.nm-sr-student-matricule').text().toLowerCase();
            $(this).toggle(name.includes(value) || matricule.includes(value));
        });
    });

    // Enter key navigation
    $('.note-input').keydown(function(e) {
        if (e.which === 13) {
            e.preventDefault();
            var currentRow = $(this).closest('tr');
            var idx = $('#notesTable tbody tr').index(currentRow);
            var nextRow = $('#notesTable tbody tr').eq(idx + 1);
            if (nextRow.length) nextRow.find('.note-input').focus();
            else $('#notesForm').submit();
        }
    });

    // Row highlight on focus
    $('.note-input, .commentaire-input, .absent-checkbox').focus(function() {
        $('.student-row').removeClass('table-active');
        $(this).closest('tr').addClass('table-active');
    });
});
</script>
@endpush
