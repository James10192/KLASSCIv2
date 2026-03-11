@extends('layouts.app')

@section('title', 'Détails du Département')
@section('page_title', 'Détails du Département')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===== SHOW DÉPARTEMENT — DESIGN PREMIUM ===== */
.dept-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.dept-status-badge.active {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dept-status-badge.inactive {
    background: rgba(245, 158, 11, 0.1);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.25);
}

/* Info rows */
.dept-info-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.dept-info-row:last-child { border-bottom: none; }

.dept-info-icon {
    width: 36px; height: 36px;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(4,83,203,0.08) 0%, rgba(94,145,222,0.08) 100%);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    color: #0453cb; font-size: 0.85rem;
}
.dept-info-content { flex: 1; min-width: 0; }
.dept-info-label {
    font-size: 0.73rem; font-weight: 600;
    color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;
    margin-bottom: 3px;
}
.dept-info-value {
    font-size: 0.95rem; font-weight: 600; color: #1e293b; word-break: break-word;
}
.dept-info-value.muted { font-weight: 400; color: #94a3b8; font-style: italic; }
.dept-info-value a { color: #0453cb; text-decoration: none; font-weight: 600; }
.dept-info-value a:hover { text-decoration: underline; }
.dept-info-value .code-badge {
    display: inline-block; padding: 3px 12px;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: #fff; border-radius: 6px;
    font-size: 0.88rem; font-weight: 700; letter-spacing: 1.5px;
}

/* KPI grid */
.dept-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}
@media (max-width: 992px) { .dept-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .dept-kpi-grid { grid-template-columns: 1fr; } }

.dept-kpi-card {
    background: #fff; border-radius: 12px; padding: 20px 18px;
    border: 1px solid #e8eef8;
    display: flex; align-items: center; gap: 16px;
    transition: all 0.2s ease;
    box-shadow: 0 1px 4px rgba(4,83,203,0.04);
}
.dept-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(4,83,203,0.1);
    border-color: rgba(4,83,203,0.2);
}
.dept-kpi-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; flex-shrink: 0;
}
.dept-kpi-icon.blue   { background: rgba(4,83,203,0.1);    color: #0453cb; }
.dept-kpi-icon.green  { background: rgba(16,185,129,0.1);  color: #10b981; }
.dept-kpi-icon.indigo { background: rgba(94,145,222,0.1);  color: #5e91de; }
.dept-kpi-icon.slate  { background: rgba(30,41,59,0.08);   color: #1e293b; }
.dept-kpi-value { font-size: 1.9rem; font-weight: 800; color: #1e293b; line-height: 1; }
.dept-kpi-label { font-size: 0.76rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 4px; }

/* Dates footer */
.dept-dates-row {
    display: flex; gap: 24px; flex-wrap: wrap;
    padding-top: 12px; border-top: 1px solid #f1f5f9; margin-top: 4px;
}
.dept-date-item { display: flex; align-items: center; gap: 6px; font-size: 0.82rem; color: #94a3b8; }
.dept-date-item i { color: #c7d5e8; font-size: 0.8rem; }
.dept-date-item span { font-weight: 500; color: #64748b; }

/* Actions bar */
.dept-actions-bar {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 16px;
    padding: 20px 24px;
    background: linear-gradient(135deg, #f8faff 0%, #eef3fb 100%);
    border: 1px solid rgba(4, 83, 203, 0.12);
    border-radius: 12px;
    border-left: 4px solid #0453cb;
}
.dept-actions-bar-info { display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: #64748b; }
.dept-actions-bar-info i { color: #0453cb; }
.dept-actions-bar-btns { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }

.dept-btn-submit {
    display: inline-flex; align-items: center; gap: 9px;
    padding: 11px 28px;
    background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%);
    color: #fff; border: none; border-radius: 8px;
    font-weight: 700; font-size: 0.95rem; cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 14px rgba(4, 83, 203, 0.3);
    text-decoration: none; position: relative; overflow: hidden;
}
.dept-btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(4, 83, 203, 0.4);
    color: #fff; text-decoration: none;
}
.dept-btn-danger {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; background: transparent;
    color: #ef4444; border: 2px solid #fecaca;
    border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer;
    transition: all 0.2s ease;
}
.dept-btn-danger:hover { background: #fef2f2; border-color: #fca5a5; transform: translateY(-1px); }

@media (max-width: 576px) {
    .dept-actions-bar { flex-direction: column; align-items: stretch; }
    .dept-actions-bar-btns { flex-direction: column-reverse; }
    .dept-btn-submit, .dept-btn-danger { justify-content: center; width: 100%; }
}
</style>
@endpush

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        <!-- Header -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-building me-2"></i>{{ $department->name }}</h1>
                <p class="header-subtitle">Détails du département {{ $department->code }} — ESBTP</p>
                <div class="mt-2">
                    @if($department->is_active)
                        <span class="dept-status-badge active">
                            <i class="fas fa-check-circle"></i>Département Actif
                        </span>
                    @else
                        <span class="dept-status-badge inactive">
                            <i class="fas fa-pause-circle"></i>Département Inactif
                        </span>
                    @endif
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.departments.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
                <a href="{{ route('esbtp.departments.edit', $department) }}" class="btn-acasi primary">
                    <i class="fas fa-edit"></i> Modifier
                </a>
            </div>
        </div>

        <!-- KPI statistiques -->
        <div class="main-card" style="margin-bottom: 24px;">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-chart-bar"></i>
                    Statistiques du département
                </div>
                <div class="main-card-subtitle">Vue d'ensemble des données</div>
            </div>
            <div class="main-card-body">
                <div class="dept-kpi-grid">
                    <div class="dept-kpi-card">
                        <div class="dept-kpi-icon blue"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <div class="dept-kpi-value">{{ $department->specialties ? $department->specialties->count() : 0 }}</div>
                            <div class="dept-kpi-label">Spécialités</div>
                        </div>
                    </div>
                    <div class="dept-kpi-card">
                        <div class="dept-kpi-icon green"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div>
                            <div class="dept-kpi-value">{{ $department->teachers ? $department->teachers->count() : 0 }}</div>
                            <div class="dept-kpi-label">Enseignants</div>
                        </div>
                    </div>
                    <div class="dept-kpi-card">
                        <div class="dept-kpi-icon indigo"><i class="fas fa-user-graduate"></i></div>
                        <div>
                            <div class="dept-kpi-value">{{ $department->students ? $department->students->count() : 0 }}</div>
                            <div class="dept-kpi-label">Étudiants</div>
                        </div>
                    </div>
                    <div class="dept-kpi-card">
                        <div class="dept-kpi-icon slate"><i class="fas fa-book-open"></i></div>
                        <div>
                            <div class="dept-kpi-value">{{ $department->continuingEducationPrograms ? $department->continuingEducationPrograms->count() : 0 }}</div>
                            <div class="dept-kpi-label">Formations continues</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grille infos 2 colonnes -->
        <div class="form-grid-2">
            <!-- Informations de base -->
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-info-circle"></i>
                        Informations de base
                    </div>
                </div>
                <div class="main-card-body">

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-code"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Code du département</div>
                            <div class="dept-info-value">
                                <span class="code-badge">{{ $department->code }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-toggle-on"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Statut</div>
                            <div class="dept-info-value">
                                @if($department->is_active)
                                    <span class="dept-status-badge active"><i class="fas fa-check-circle"></i>Actif</span>
                                @else
                                    <span class="dept-status-badge inactive"><i class="fas fa-pause-circle"></i>Inactif</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-align-left"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Description</div>
                            <div class="dept-info-value {{ $department->description ? '' : 'muted' }}">
                                {{ $department->description ?: 'Aucune description renseignée' }}
                            </div>
                        </div>
                    </div>

                    <div class="dept-dates-row">
                        <div class="dept-date-item">
                            <i class="far fa-calendar-alt"></i>
                            Créé le <span>{{ $department->created_at->format('d/m/Y à H:i') }}</span>
                        </div>
                        <div class="dept-date-item">
                            <i class="far fa-clock"></i>
                            Modifié le <span>{{ $department->updated_at->format('d/m/Y à H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations du responsable -->
            <div class="main-card">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-user-tie"></i>
                        Informations du responsable
                    </div>
                </div>
                <div class="main-card-body">

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-user"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Chef de département</div>
                            <div class="dept-info-value {{ $department->head_name ? '' : 'muted' }}">
                                {{ $department->head_name ?: 'Non défini' }}
                            </div>
                        </div>
                    </div>

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Titre académique</div>
                            <div class="dept-info-value {{ $department->head_title ? '' : 'muted' }}">
                                {{ $department->head_title ?: 'Non défini' }}
                            </div>
                        </div>
                    </div>

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Email</div>
                            <div class="dept-info-value {{ $department->email ? '' : 'muted' }}">
                                @if($department->email)
                                    <a href="mailto:{{ $department->email }}">{{ $department->email }}</a>
                                @else
                                    Non défini
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-phone"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Téléphone</div>
                            <div class="dept-info-value {{ $department->phone ? '' : 'muted' }}">
                                @if($department->phone)
                                    <a href="tel:{{ $department->phone }}">{{ $department->phone }}</a>
                                @else
                                    Non défini
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="dept-info-row">
                        <div class="dept-info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="dept-info-content">
                            <div class="dept-info-label">Bureau</div>
                            <div class="dept-info-value {{ $department->office_location ? '' : 'muted' }}">
                                {{ $department->office_location ?: 'Non défini' }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Actions bar -->
        <div class="main-card" style="margin-top: 8px;">
            <div class="main-card-body">
                <div class="dept-actions-bar">
                    <div class="dept-actions-bar-info">
                        <i class="fas fa-cogs"></i>
                        <span>Actions disponibles pour ce département</span>
                    </div>
                    <div class="dept-actions-bar-btns">
                        <form action="{{ route('esbtp.departments.destroy', $department) }}" method="POST"
                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce département ?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dept-btn-danger">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </form>
                        <a href="{{ route('esbtp.departments.edit', $department) }}" class="dept-btn-submit">
                            <i class="fas fa-edit"></i> Modifier le département
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
