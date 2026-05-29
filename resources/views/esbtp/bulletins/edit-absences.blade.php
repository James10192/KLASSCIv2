@extends('layouts.app')

@section('title', 'Édition des absences - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* Cartes modernes - même style que edit-professeurs */
.absence-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.absence-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    border-color: var(--primary);
}

.absence-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #f3f4f6;
}

.absence-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, var(--primary), #667eea);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.absence-icon.calculated {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
}

.absence-icon.manual {
    background: linear-gradient(135deg, #f59e0b, #fbbf24);
}

.absence-icon i {
    color: white;
    font-size: 1.2rem;
}

.absence-info {
    flex: 1;
}

.absence-title {
    margin: 0 0 0.25rem 0;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 1.1rem;
}

.absence-subtitle {
    margin: 0;
    color: var(--text-secondary);
    font-size: 0.875rem;
    opacity: 0.8;
}

/* Section d'input */
.input-section {
    margin-bottom: 1.5rem;
}

.input-label {
    display: flex;
    align-items: center;
    margin: 0 0 0.75rem 0;
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.95rem;
}

.input-label i {
    margin-right: 0.5rem;
}

.form-control-modern {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.875rem 1rem;
    background: white;
    transition: all 0.3s ease;
    font-size: 0.95rem;
    width: 100%;
    text-align: center;
    font-weight: 600;
    font-size: 1.1rem;
}

.form-control-modern:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    outline: none;
}

.form-control-modern:hover {
    border-color: var(--primary);
}

/* Info boxes */
.info-box {
    padding: 1rem;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 4px solid var(--info);
    margin-bottom: 1rem;
}

.info-box.success {
    border-left-color: var(--success);
    background: #f0fdf4;
}

.info-box.warning {
    border-left-color: var(--warning);
    background: #fffbeb;
}

.info-box.danger {
    border-left-color: var(--danger);
    background: #fef2f2;
}

/* Badge moderne */
.badge-modern {
    display: inline-flex;
    align-items: center;
    padding: 0.35rem 0.75rem;
    border-radius: 6px;
    font-size: 0.8rem;
    font-weight: 500;
    gap: 0.35rem;
}

.badge-modern.auto {
    background: #dbeafe;
    color: #1e40af;
}

.badge-modern.manual {
    background: #fef3c7;
    color: #92400e;
}

/* Amélioration des boutons */
.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
}

.btn-outline-secondary {
    border: 2px solid #6b7280;
    color: #6b7280;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background: #6b7280;
    color: white;
    transform: translateY(-1px);
}

/* Note d'assiduité display */
.note-display {
    text-align: center;
    padding: 2rem;
    background: linear-gradient(135deg, #e0f2fe, #dbeafe);
    border-radius: 12px;
    border: 2px solid #3b82f6;
}

.note-value {
    font-size: 3rem;
    font-weight: bold;
    color: #1e40af;
    margin: 0.5rem 0;
}

/* Espacement responsive */
@media (max-width: 768px) {
    .absence-card {
        margin-bottom: 1rem;
    }

    .d-flex.justify-content-between {
        flex-direction: column;
        gap: 1rem;
    }

    .d-flex.gap-3 {
        justify-content: stretch;
        width: 100%;
    }

    .d-flex.gap-3 button {
        flex: 1;
    }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-user-clock me-2"></i>Édition des absences</h1>
                <p class="header-subtitle">Configurez les absences et la note d'assiduité pour {{ $etudiant->nom }} {{ $etudiant->prenoms }}</p>
            </div>
            <div class="header-actions">
                <span class="badge bg-primary fs-6">
                    <i class="fas fa-graduation-cap me-1"></i>
                    {{ $etudiant->nom }} {{ $etudiant->prenoms }}
                </span>
            </div>
        </div>

        <!-- Statistiques KPI -->
        <div class="kpi-grid mb-4">
            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Classe</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">{{ $classe->libelle ?? $classe->name ?? 'N/A' }}</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-users"></i>
                    {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Période</div>
                <div class="kpi-value" style="color: var(--primary); font-size: 1.5rem; font-weight: bold;">
                    @if($periode == 'semestre1')
                        Semestre 1
                    @elseif($periode == 'semestre2')
                        Semestre 2
                    @else
                        Annuel
                    @endif
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-calendar-alt"></i>
                    Configuration
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Absences calculées</div>
                <div class="kpi-value" style="color: #3b82f6; font-size: 2.5rem; font-weight: bold;">{{ number_format($absencesCalculees['total'] ?? 0, 1) }}h</div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-robot"></i>
                    Système automatique
                </div>
            </div>

            <div class="kpi-card card-moderne" style="background: white; border: 1px solid #e5e7eb;">
                <div class="kpi-title" style="color: #000; font-weight: 600;">Note d'assiduité</div>
                <div class="kpi-value" style="color: {{ $noteAssiduite >= 0 ? '#10b981' : '#ef4444' }}; font-size: 2.5rem; font-weight: bold;">
                    {{ $noteAssiduite >= 0 ? '+' : '' }}{{ number_format($noteAssiduite, 2) }}
                </div>
                <div class="kpi-trend" style="color: #6b7280; font-size: 0.875rem;">
                    <i class="fas fa-star"></i>
                    Points bonus/malus
                </div>
            </div>
        </div>

        <!-- Guide d'utilisation -->
        <div class="main-card mb-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-lightbulb"></i>
                    Guide d'utilisation
                </div>
            </div>
            <div class="main-card-body">
                <div class="alert alert-info mb-0">
                    <div class="row">
                        <div class="col-md-6">
                            <p><i class="fas fa-robot me-2"></i><strong>Calcul automatique :</strong> Les absences sont calculées depuis le système d'émargement</p>
                            <p><i class="fas fa-edit me-2"></i><strong>Modification manuelle :</strong> Vous pouvez ajuster les valeurs si nécessaire</p>
                        </div>
                        <div class="col-md-6">
                            <p><i class="fas fa-calculator me-2"></i>La note d'assiduité est recalculée automatiquement</p>
                            <p><i class="fas fa-save me-2"></i>N'oubliez pas d'enregistrer vos modifications avant de quitter</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages d'erreur de validation -->
        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <h6><i class="fas fa-exclamation-triangle me-2"></i>Erreurs de validation :</h6>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Messages de succès -->
        @if(session('success'))
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            </div>
        @endif

        <!-- Messages d'erreur -->
        @if(session('error'))
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            </div>
        @endif

        <form id="absencesForm" action="{{ route('esbtp.bulletins.save-absences') }}" method="POST">
            @csrf
            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

            <!-- Section absences -->
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-user-clock"></i>
                        Gestion des absences
                    </div>
                    <div class="main-card-subtitle">
                        Source:
                        @if($source == 'manuelle')
                            <span class="badge-modern manual"><i class="fas fa-edit"></i>Modifiées manuellement</span>
                        @else
                            <span class="badge-modern auto"><i class="fas fa-robot"></i>Calculées automatiquement</span>
                        @endif
                    </div>
                </div>
                <div class="main-card-body">
                    <div class="row g-4">
                        <!-- Carte absences calculées -->
                        <div class="col-lg-6">
                            <div class="absence-card">
                                <div class="absence-header">
                                    <div class="absence-icon calculated">
                                        <i class="fas fa-robot"></i>
                                    </div>
                                    <div class="absence-info">
                                        <h6 class="absence-title">Absences calculées automatiquement</h6>
                                        <p class="absence-subtitle">Depuis le système d'émargement</p>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="info-box success">
                                            <div class="text-muted small mb-1">Justifiées</div>
                                            <div class="h4 text-success mb-0">{{ number_format($absencesCalculees['justifiees'] ?? 0, 1) }}h</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="info-box danger">
                                            <div class="text-muted small mb-1">Non justifiées</div>
                                            <div class="h4 text-danger mb-0">{{ number_format($absencesCalculees['non_justifiees'] ?? 0, 1) }}h</div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="info-box">
                                            <div class="text-muted small mb-1">Total</div>
                                            <div class="h3 text-primary mb-0">{{ number_format($absencesCalculees['total'] ?? 0, 1) }}h</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Carte saisie manuelle -->
                        <div class="col-lg-6">
                            <div class="absence-card">
                                <div class="absence-header">
                                    <div class="absence-icon manual">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                    <div class="absence-info">
                                        <h6 class="absence-title">Absences à enregistrer</h6>
                                        <p class="absence-subtitle">Modifiez les valeurs si nécessaire</p>
                                    </div>
                                </div>

                                <div class="input-section">
                                    <label for="absences_justifiees" class="input-label">
                                        <i class="fas fa-check-circle text-success"></i>
                                        Absences justifiées (heures)
                                    </label>
                                    <input type="number"
                                           class="form-control-modern"
                                           id="absences_justifiees"
                                           name="absences_justifiees"
                                           value="{{ old('absences_justifiees', $absencesJustifiees) }}"
                                           min="0"
                                           step="0.5"
                                           required
                                           onchange="calculerTotalAbsences()">
                                </div>

                                <div class="input-section">
                                    <label for="absences_non_justifiees" class="input-label">
                                        <i class="fas fa-times-circle text-danger"></i>
                                        Absences non justifiées (heures)
                                    </label>
                                    <input type="number"
                                           class="form-control-modern"
                                           id="absences_non_justifiees"
                                           name="absences_non_justifiees"
                                           value="{{ old('absences_non_justifiees', $absencesNonJustifiees) }}"
                                           min="0"
                                           step="0.5"
                                           required
                                           onchange="calculerTotalAbsences()">
                                </div>

                                <div class="alert alert-primary mb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong><i class="fas fa-calculator me-2"></i>Total absences:</strong>
                                        <span class="h4 mb-0" id="total_absences_display">{{ number_format($totalAbsences, 1) }}h</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carte note d'assiduité -->
            <div class="main-card mb-4">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-star"></i>
                        Note d'assiduité et barème
                    </div>
                    <div class="main-card-subtitle">Calculée selon les absences non justifiées</div>
                </div>
                <div class="main-card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <div class="note-display">
                                <div class="text-muted small">Note d'assiduité</div>
                                <div class="note-value" id="note_assiduite_display">
                                    {{ $noteAssiduite >= 0 ? '+' : '' }}{{ number_format($noteAssiduite, 2) }}
                                </div>
                                <div class="text-muted small">points</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <h6 class="mb-3"><i class="fas fa-calculator me-2"></i>Barème de calcul :</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="info-box success">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <strong>0</strong> absence non justifiée = <strong>+0.13</strong> point
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box">
                                        <i class="fas fa-minus-circle me-2"></i>
                                        <strong>1</strong> absence non justifiée = <strong>0</strong> point
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box warning">
                                        <i class="fas fa-exclamation-circle me-2"></i>
                                        <strong>2</strong> absences non justifiées = <strong>-0.13</strong> point
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-box danger">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <strong>3-4</strong> absences non justifiées = <strong>-0.39</strong> point
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="info-box danger">
                                        <i class="fas fa-ban me-2"></i>
                                        <strong>5+</strong> absences non justifiées = <strong>-0.50</strong> point
                                    </div>
                                </div>
                            </div>
                            <p class="text-muted mt-3 mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                <small>La note d'assiduité est ajoutée/soustraite à la moyenne générale du bulletin</small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="main-card">
                <div class="main-card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('esbtp.resultats.etudiant', [
                                'etudiant' => $etudiant->id,
                                'classe_id' => $classe->id,
                                'periode' => $periode == 'semestre1' ? '1' : ($periode == 'semestre2' ? '2' : $periode),
                                'annee_universitaire_id' => $anneeUniversitaire->id
                            ]) }}" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i> Retour aux résultats
                            </a>
                        </div>
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn-acasi success btn-lg" name="action" value="save_and_back">
                                <i class="fas fa-save me-2"></i> Enregistrer et retourner
                            </button>
                            <button type="submit" class="btn-acasi primary btn-lg" name="action" value="generate">
                                <i class="fas fa-file-pdf me-2"></i> Enregistrer et générer bulletin
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const attendanceNoteEnabled = @json($attendanceNoteEnabled ?? true);
const attendanceNoteRules = @json([
    'zero' => (float) (($attendanceNoteRules['zero_unjustified'] ?? null) ?? 0.13),
    'one' => (float) (($attendanceNoteRules['one_unjustified'] ?? null) ?? 0.0),
    'two_or_more' => (float) (($attendanceNoteRules['two_or_more_unjustified'] ?? null) ?? -0.13),
]);

function calculerTotalAbsences() {
    const justifiees = parseFloat(document.getElementById('absences_justifiees').value) || 0;
    const nonJustifiees = parseFloat(document.getElementById('absences_non_justifiees').value) || 0;
    const total = justifiees + nonJustifiees;

    document.getElementById('total_absences_display').textContent = total.toFixed(1) + 'h';

    // Calculer la note d'assiduité
    let noteAssiduite = 0;
    if (nonJustifiees == 0) {
        noteAssiduite = 0.13;
    } else if (nonJustifiees == 1) {
        noteAssiduite = 0;
    } else if (nonJustifiees == 2) {
        noteAssiduite = -0.13;
    } else if (nonJustifiees >= 3 && nonJustifiees <= 4) {
        noteAssiduite = -0.39;
    } else if (nonJustifiees >= 5) {
        noteAssiduite = -0.50;
    }

    if (attendanceNoteEnabled) {
        if (nonJustifiees <= 0) {
            noteAssiduite = Number(attendanceNoteRules.zero || 0);
        } else if (nonJustifiees < 2) {
            noteAssiduite = Number(attendanceNoteRules.one || 0);
        } else {
            noteAssiduite = Number(attendanceNoteRules.two_or_more || 0);
        }
    } else {
        noteAssiduite = 0;
    }

    const noteDisplay = attendanceNoteEnabled
        ? (noteAssiduite >= 0 ? '+' + noteAssiduite.toFixed(2) : noteAssiduite.toFixed(2))
        : 'Masquee';
    document.getElementById('note_assiduite_display').textContent = noteDisplay;

    // Changer la couleur selon la note
    const noteElement = document.getElementById('note_assiduite_display');
    if (!attendanceNoteEnabled) {
        noteElement.style.color = '#6b7280';
    } else if (noteAssiduite >= 0) {
        noteElement.style.color = '#10b981';
    } else {
        noteElement.style.color = '#ef4444';
    }
}

// Calculer au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    const subtitle = document.querySelector('.main-card-subtitle');
    if (subtitle) {
        subtitle.textContent = attendanceNoteEnabled
            ? 'Calculee selon les absences non justifiees'
            : 'Le toggle global d\\'assiduite est actuellement desactive';
    }

    const infoBoxes = document.querySelectorAll('.main-card .info-box');
    if (infoBoxes.length >= 3) {
        infoBoxes[0].innerHTML = `<i class="fas fa-check-circle me-2"></i><strong>0</strong> absence non justifiee = <strong>${attendanceNoteRules.zero >= 0 ? '+' : ''}${Number(attendanceNoteRules.zero).toFixed(2)}</strong> point`;
        infoBoxes[1].innerHTML = `<i class="fas fa-minus-circle me-2"></i><strong>1</strong> absence non justifiee = <strong>${attendanceNoteRules.one >= 0 ? '+' : ''}${Number(attendanceNoteRules.one).toFixed(2)}</strong> point`;
        infoBoxes[2].innerHTML = `<i class="fas fa-exclamation-circle me-2"></i><strong>2+</strong> absences non justifiees = <strong>${attendanceNoteRules.two_or_more >= 0 ? '+' : ''}${Number(attendanceNoteRules.two_or_more).toFixed(2)}</strong> point`;
        for (let index = 3; index < infoBoxes.length; index += 1) {
            infoBoxes[index].style.display = attendanceNoteEnabled ? 'none' : '';
        }
    }

    calculerTotalAbsences();
});
</script>
@endsection
