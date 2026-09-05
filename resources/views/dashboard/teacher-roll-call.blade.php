@extends('layouts.app')

@section('title', 'Faire l\'appel')

@php
    $callType = request()->get('type', 'start');
    $callTypeText = $callType === 'start' ? 'de début' : 'de fin';
    $callTypeIcon = $callType === 'start' ? 'fa-play' : 'fa-stop';
    $mShell = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);

    // Statut initial d'un étudiant, partagé par les deux rendus : la colonne est
    // `statut` (pas `status`), 'retard' est l'ancienne graphie de 'late', et l'appel
    // de fin n'a pas de case « Retard » — un retard fusionné y redevient « présent ».
    $existingByEtudiant = $existingAttendances->keyBy('etudiant_id');
    $statutInitial = function ($etudiant) use ($existingByEtudiant, $callType) {
        $existing = $existingByEtudiant->get($etudiant->id);
        $statut = $existing ? (string) $existing->statut : 'present';
        if ($statut === 'retard') {
            $statut = 'late';
        }
        if (! in_array($statut, ['present', 'late', 'absent'], true)) {
            $statut = 'present';
        }
        if ($callType !== 'start' && $statut === 'late') {
            $statut = 'present';
        }

        return $statut;
    };
@endphp

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .roll-call-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: var(--space-lg);
    }

    .course-info-card {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        text-align: center;
    }

    .course-info-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin-bottom: var(--space-sm);
    }

    .course-info-details {
        display: flex;
        justify-content: center;
        gap: var(--space-lg);
        flex-wrap: wrap;
        margin-top: var(--space-md);
    }

    .course-info-item {
        display: flex;
        align-items: center;
        gap: var(--space-sm);
    }

    .roll-call-card {
        background: var(--surface);
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .roll-call-header {
        background: linear-gradient(135deg, var(--accent-blue), #0891b2);
        color: white;
        padding: var(--space-lg);
    }

    .roll-call-title {
        font-size: var(--title-main);
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .student-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: var(--space-md) var(--space-lg);
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.2s ease;
    }

    .student-item:hover {
        background: #f8fafc;
    }

    .student-item:last-child {
        border-bottom: none;
    }

    .student-info {
        display: flex;
        align-items: center;
        gap: var(--space-md);
    }

    .student-avatar {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-circle);
        background: linear-gradient(135deg, var(--primary), var(--accent-blue));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 16px;
    }

    .student-details h6 {
        margin: 0;
        font-weight: 600;
        color: var(--text-primary);
    }

    .student-details small {
        color: var(--text-secondary);
    }

    .attendance-options {
        display: flex;
        gap: var(--space-sm);
        align-items: center;
    }

    .attendance-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 16px;
        border: 2px solid transparent;
        border-radius: 8px;
        background: transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 13px;
        font-weight: 500;
        min-width: 90px;
        position: relative;
    }

    .attendance-btn input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .attendance-btn.present {
        border-color: #10b981;
        color: #10b981;
        background: rgba(16, 185, 129, 0.05);
    }

    .attendance-btn.present.active {
        background: #10b981 !important;
        color: white !important;
        border-color: #10b981 !important;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4) !important;
    }

    .attendance-btn.absent {
        border-color: #ef4444;
        color: #ef4444;
        background: rgba(239, 68, 68, 0.05);
    }

    .attendance-btn.absent.active {
        background: #ef4444 !important;
        color: white !important;
        border-color: #ef4444 !important;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4) !important;
    }

    .attendance-btn.late {
        border-color: #f59e0b;
        color: #f59e0b;
        background: rgba(245, 158, 11, 0.05);
    }

    .attendance-btn.late.active {
        background: #f59e0b !important;
        color: white !important;
        border-color: #f59e0b !important;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.4) !important;
    }

    .attendance-btn:hover:not(.active) {
        transform: scale(1.02);
        opacity: 0.8;
    }

    .action-buttons {
        padding: var(--space-lg);
        display: flex;
        justify-content: center;
        gap: var(--space-md);
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
    }

    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: var(--space-sm);
        padding: var(--space-md) var(--space-xl);
        border: none;
        border-radius: var(--radius-medium);
        font-weight: 600;
        font-size: var(--text-normal);
        transition: all 0.3s ease;
        text-decoration: none;
        cursor: pointer;
    }

    .btn-modern.primary {
        background: linear-gradient(135deg, var(--success), #059669);
        color: white;
        box-shadow: var(--shadow-card);
    }

    .btn-modern.primary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .btn-modern.secondary {
        background: linear-gradient(135deg, var(--neutral), #6b7280);
        color: white;
    }

    .btn-modern.secondary:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
        color: white;
    }

    .stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: var(--space-md);
        margin: var(--space-lg) 0;
        padding: var(--space-lg);
        background: #f8fafc;
        border-radius: var(--radius-medium);
    }

    .stat-item {
        text-align: center;
        padding: var(--space-md);
        border-radius: var(--radius-medium);
        background: white;
        border: 1px solid #e5e7eb;
    }

    .stat-value {
        font-size: var(--amount-medium);
        font-weight: 700;
        color: var(--primary);
        margin-bottom: var(--space-xs);
    }

    .stat-label {
        font-size: var(--text-small);
        color: var(--text-secondary);
        font-weight: 500;
    }

    .already-done-notice {
        background: rgba(16, 185, 129, 0.1);
        color: var(--success);
        border: 1px solid rgba(16, 185, 129, 0.2);
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        margin-bottom: var(--space-lg);
        text-align: center;
    }
</style>
@endsection

@push('styles')
<style>
    /* =====================================================================
       APPEL MOBILE — namespace trm-* (teacher roll-call, mobile)
       Complète le socle m-* (mobile-shell.css) ; ne le redéfinit pas.
       ===================================================================== */
    /* Bandeau « appel déjà fait » */
    .trm-banner { display: flex; align-items: center; gap: 8px; background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.18); border-radius: 12px; padding: 10px 12px; font-size: 12.5px; font-weight: 600; }
    .trm-banner svg { width: 18px; height: 18px; flex: 0 0 18px; }

    /* Compteurs : 3 cases en appel de fin (pas de retard) */
    .trm-cnt3 { grid-template-columns: repeat(3, 1fr); }

    /* Sens du geste : gauche = absent (rouge), droite = présent (vert) */
    .trm-sw .lft { color: #a12016; }
    .trm-sw .rgt { color: #0f6b4c; }

    /* Actions en masse, en tête */
    .trm-bulk { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .trm-bulk .m-btn { height: 44px; font-size: 14px; border-radius: 12px; }

    /* Une ligne par étudiant : avatar + bloc (nom, matricule, trois cibles) */
    .trm-note { grid-template-columns: 44px 1fr; touch-action: pan-y; user-select: none; -webkit-user-select: none; position: relative; will-change: transform; transition: transform .22s cubic-bezier(.22,1,.36,1), background-color .15s ease, border-color .15s ease; }
    .trm-note.is-drag { transition: background-color .15s ease, border-color .15s ease; }
    .trm-note.to-p { background: #e6f6ef; border-color: #9fd9bf; }
    .trm-note.to-a { background: #fdecea; border-color: #f3b3ab; }
    .trm-note .nm { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .trm-tri { margin-top: 6px; }
    .trm-tri2 { grid-template-columns: repeat(2, 1fr); }
    .trm-tri button { -webkit-tap-highlight-color: transparent; }
    .trm-tri button.off:active { opacity: .8; }

    /* Message sous le bouton quand le réseau manque */
    .trm-off { text-align: center; font-size: 12px; font-weight: 600; color: #8a5200; }

    /* Squelette pendant l'envoi */
    .trm-screen .m-btn .trm-spin { width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: trm-spin .8s linear infinite; }
    @keyframes trm-spin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
<div class="{{ $mShell ? 'm-only-desktop' : '' }}">
<div class="roll-call-container">
    <!-- Information du cours -->
    <div class="course-info-card">
        <h1 class="course-info-title">
            <i class="fas {{ $callTypeIcon }}"></i>
            Appel {{ $callTypeText }}
        </h1>
        <div class="course-info-details">
            <div class="course-info-item">
                <i class="fas fa-book"></i>
                <span>{{ $seance->matiere->name ?? 'Matière non définie' }}</span>
            </div>
            <div class="course-info-item">
                <i class="fas fa-users"></i>
                <span>{{ $seance->classe->name ?? 'Classe non définie' }}</span>
            </div>
            <div class="course-info-item">
                <i class="fas fa-clock"></i>
                <span>
                    {{ $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : 'N/A' }} -
                    {{ $seance->heure_fin ? \Carbon\Carbon::parse($seance->heure_fin)->format('H:i') : 'N/A' }}
                </span>
            </div>
            <div class="course-info-item">
                <i class="fas fa-calendar"></i>
                <span>{{ \Carbon\Carbon::now()->format('d/m/Y') }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999;">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($hasRollCall)
        <div class="already-done-notice">
            <i class="fas fa-check-circle me-2"></i>
            <strong>Appel déjà effectué</strong> - Vous pouvez modifier les présences si nécessaire
        </div>
    @endif

    <form id="rollCallForm" method="POST" action="{{ route('teacher.roll-call.store', $seance->id) }}">
        @csrf
        <input type="hidden" name="call_type" value="{{ $callType }}">

        <div class="roll-call-card">
            <div class="roll-call-header">
                <h2 class="roll-call-title">
                    <i class="fas {{ $callTypeIcon }}"></i>
                    Appel {{ $callTypeText }} - {{ $etudiants->count() }} étudiants
                </h2>
            </div>

            <div class="p-0">
                @forelse($etudiants as $etudiant)
                    @php
                        $currentStatus = $statutInitial($etudiant);
                    @endphp
                    <div class="student-item">
                        <div class="student-info">
                            <div class="student-avatar">
                                {{ substr($etudiant->prenoms ?? $etudiant->nom ?? 'E', 0, 1) }}
                            </div>
                            <div class="student-details">
                                <h6>{{ ($etudiant->prenoms && $etudiant->nom) ? $etudiant->nom . ' ' . $etudiant->prenoms : ($etudiant->user->name ?? 'Nom non défini') }}</h6>
                                <small>{{ $etudiant->matricule ?? 'Matricule non défini' }}</small>
                            </div>
                        </div>
                        <div class="attendance-options">
                            <label class="attendance-btn present {{ $currentStatus === 'present' ? 'active' : '' }}" for="present_{{ $etudiant->id }}">
                                <input type="radio" name="attendances[{{ $etudiant->id }}]" value="present" id="present_{{ $etudiant->id }}" style="display: none;" {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                <i class="fas fa-check"></i>
                                <span>Présent</span>
                            </label>
                            @if($callType === 'start')
                            <label class="attendance-btn late {{ $currentStatus === 'late' ? 'active' : '' }}" for="late_{{ $etudiant->id }}">
                                <input type="radio" name="attendances[{{ $etudiant->id }}]" value="late" id="late_{{ $etudiant->id }}" style="display: none;" {{ $currentStatus === 'late' ? 'checked' : '' }}>
                                <i class="fas fa-clock"></i>
                                <span>Retard</span>
                            </label>
                            @endif
                            <label class="attendance-btn absent {{ $currentStatus === 'absent' ? 'active' : '' }}" for="absent_{{ $etudiant->id }}">
                                <input type="radio" name="attendances[{{ $etudiant->id }}]" value="absent" id="absent_{{ $etudiant->id }}" style="display: none;" {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                <i class="fas fa-times"></i>
                                <span>Absent</span>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="empty-state p-5">
                        <i class="fas fa-users-slash"></i>
                        <p>Aucun étudiant inscrit dans cette classe</p>
                        <p class="text-muted">Contactez l'administration pour vérifier les inscriptions</p>
                    </div>
                @endforelse
            </div>

            @if($etudiants->count() > 0)
                <!-- Résumé des statistiques -->
                <div class="stats-summary" id="attendanceStats">
                    <div class="stat-item">
                        <div class="stat-value" id="presentCount">0</div>
                        <div class="stat-label">Présents</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="lateCount">0</div>
                        <div class="stat-label">En retard</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="absentCount">0</div>
                        <div class="stat-label">Absents</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="totalCount">{{ $etudiants->count() }}</div>
                        <div class="stat-label">Total</div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="action-buttons">
                    @canany(['attendances.create', 'attendances.edit'])
                    <button type="submit" class="btn-modern primary">
                        <i class="fas fa-save"></i>
                        <span>{{ $hasRollCall ? 'Mettre à jour' : 'Enregistrer' }} l'appel</span>
                    </button>
                    @endcanany
                    <a href="{{ route('teacher.select-call-type', $seance->id) }}" class="btn-modern secondary">
                        <i class="fas fa-arrow-left"></i>
                        <span>Retour à la sélection</span>
                    </a>
                </div>
            @endif
        </div>
    </form>
</div>
</div>

@if($mShell)
@php
    // Matière : en LMD la séance porte une ECUE, son code aide à la reconnaître.
    $trmMatiere = $seance->matiere;
    $trmMatiereLabel = $trmMatiere->name ?? 'Matière non définie';
    if ($trmMatiere && method_exists($trmMatiere, 'isECUE') && $trmMatiere->isECUE() && ! empty($trmMatiere->code)) {
        $trmMatiereLabel = $trmMatiere->code . ' · ' . $trmMatiereLabel;
    }
    $trmHeure = $seance->heure_debut ? \Carbon\Carbon::parse($seance->heure_debut)->format('H:i') : null;
    $trmSub = implode(' · ', array_filter([
        $trmMatiereLabel,
        $seance->classe->name ?? null,
        $trmHeure,
    ]));

    $trmEtudiants = $etudiants->map(function ($etudiant) use ($statutInitial) {
        $nom = ($etudiant->prenoms && $etudiant->nom)
            ? $etudiant->nom . ' ' . $etudiant->prenoms
            : ($etudiant->user->name ?? 'Nom non défini');
        $initiales = mb_strtoupper(
            mb_substr((string) ($etudiant->nom ?? ''), 0, 1, 'UTF-8') . mb_substr((string) ($etudiant->prenoms ?? ''), 0, 1, 'UTF-8'),
            'UTF-8'
        );
        if ($initiales === '') {
            $initiales = mb_strtoupper(mb_substr($nom, 0, 1, 'UTF-8'), 'UTF-8');
        }

        return [
            'id' => $etudiant->id,
            'nom' => $nom,
            'matricule' => $etudiant->matricule ?? 'Matricule non défini',
            'initiales' => $initiales,
            'statut' => $statutInitial($etudiant),
            'dx' => 0,
        ];
    })->values();

    $trmConfig = [
        'etudiants' => $trmEtudiants,
        'retardPossible' => $callType === 'start',
        'dashboardUrl' => route('teacher.dashboard'),
        'hasRollCall' => (bool) $hasRollCall,
    ];
@endphp
{{-- ÉCRAN MOBILE (shell m-*) : la barre d'onglets est rendue par le layout --}}
<div class="m-only-mobile m-screen trm-screen" x-data="trmAppel()">
    <x-m.appbar :title="'Appel ' . $callTypeText" :sub="$trmSub !== '' ? $trmSub : null" :back="route('teacher.select-call-type', $seance->id)" back-label="Retour à la sélection" />

    <div class="m-body">
        @if($etudiants->isEmpty())
            <x-m.empty icon="users" title="Aucun étudiant inscrit" text="Aucune inscription active dans cette classe. Contactez l'administration pour vérifier les inscriptions." />
        @else
            <div class="m-sticky">
                <div class="m-cnt {{ $callType === 'start' ? '' : 'trm-cnt3' }}">
                    <div class="p"><b x-text="counts.present">{{ $trmEtudiants->where('statut', 'present')->count() }}</b><span>Présents</span></div>
                    @if($callType === 'start')
                        <div class="r"><b x-text="counts.late">{{ $trmEtudiants->where('statut', 'late')->count() }}</b><span>Retards</span></div>
                    @endif
                    <div class="a"><b x-text="counts.absent">{{ $trmEtudiants->where('statut', 'absent')->count() }}</b><span>Absents</span></div>
                    <div><b x-text="counts.total">{{ $trmEtudiants->count() }}</b><span>Total</span></div>
                </div>
                <div class="m-sw trm-sw" style="margin-top:8px" aria-hidden="true">
                    <span class="lft">← absent</span>
                    <x-m.icon name="hand" />
                    <span class="rgt">présent →</span>
                </div>
            </div>

            @if($hasRollCall)
                <div class="trm-banner" role="status">
                    <x-m.icon name="check" />
                    <span>Appel déjà effectué — vous pouvez modifier les présences.</span>
                </div>
            @endif

            <div class="trm-bulk">
                <button type="button" class="m-btn g" x-on:click="toutMarquer('present')" :disabled="saving">
                    <x-m.icon name="check" />Tout présent
                </button>
                <button type="button" class="m-btn g" x-on:click="toutMarquer('absent')" :disabled="saving">
                    <x-m.icon name="x" />Tout absent
                </button>
            </div>

            <label class="m-search">
                <x-m.icon name="search" />
                <input type="search" x-model="q" placeholder="Filtrer la liste…" autocomplete="off" aria-label="Filtrer la liste des étudiants">
            </label>

            <form id="trmForm" method="POST" action="{{ route('teacher.roll-call.store', $seance->id) }}" x-on:submit.prevent="enregistrer()">
                @csrf
                <input type="hidden" name="call_type" value="{{ $callType }}">

                <div class="m-list one">
                    <template x-for="s in etudiants" :key="s.id">
                        <div class="m-note trm-note"
                             x-show="visible(s)"
                             :class="{ 'is-drag': s.dx !== 0, 'to-p': s.dx >= seuil, 'to-a': s.dx <= -seuil }"
                             :style="s.dx !== 0 ? 'transform:translateX(' + s.dx + 'px)' : ''"
                             x-on:pointerdown="dragStart($event, s)"
                             x-on:pointermove="dragMove($event, s)"
                             x-on:pointerup="dragEnd($event, s)"
                             x-on:pointercancel="dragEnd($event, s)">
                            <div class="av" x-text="s.initiales"></div>
                            <div>
                                <div class="nm" x-text="s.nom"></div>
                                <div class="mt" x-text="s.matricule"></div>
                                <input type="hidden" :name="'attendances[' + s.id + ']'" :value="s.statut">
                                <div class="m-tri trm-tri {{ $callType === 'start' ? '' : 'trm-tri2' }}" role="group" :aria-label="'Présence de ' + s.nom">
                                    <button type="button" :class="s.statut === 'present' ? 'p' : 'off'" :aria-pressed="s.statut === 'present'" x-on:click="marquer(s, 'present')">
                                        <span x-text="s.statut === 'present' ? 'Présent ✓' : 'Présent'"></span>
                                    </button>
                                    @if($callType === 'start')
                                        <button type="button" :class="s.statut === 'late' ? 'r' : 'off'" :aria-pressed="s.statut === 'late'" x-on:click="marquer(s, 'late')">
                                            <span x-text="s.statut === 'late' ? 'Retard ✓' : 'Retard'"></span>
                                        </button>
                                    @endif
                                    <button type="button" :class="s.statut === 'absent' ? 'a' : 'off'" :aria-pressed="s.statut === 'absent'" x-on:click="marquer(s, 'absent')">
                                        <span x-text="s.statut === 'absent' ? 'Absent ✓' : 'Absent'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <x-m.empty x-show="q !== '' && filtres.length === 0" x-cloak icon="search" title="Aucun étudiant trouvé" text="Essayez un autre nom ou un autre matricule." />
            </form>

            @canany(['attendances.create', 'attendances.edit'])
                <x-m.actionbar>
                    <button type="submit" form="trmForm" class="m-btn p" :disabled="saving || !online">
                        <span class="trm-spin" x-show="saving" x-cloak aria-hidden="true"></span>
                        <x-m.icon name="check" x-show="!saving" />
                        <span x-text="libelleBouton"></span>
                    </button>
                    <div class="trm-off" x-show="!online" x-cloak role="status">Hors connexion — l'appel sera envoyé quand le réseau reviendra.</div>
                </x-m.actionbar>
            @endcanany
        @endif
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($mShell)
<script>
function trmAppel() {
    return {
        etudiants: @json($trmConfig['etudiants']),
        retardPossible: @json($trmConfig['retardPossible']),
        dashboardUrl: @json($trmConfig['dashboardUrl']),
        hasRollCall: @json($trmConfig['hasRollCall']),
        q: '',
        saving: false,
        online: navigator.onLine !== false,
        seuil: 60,
        _drag: null,
        _onOnline: null,
        _onOffline: null,

        init() {
            this._onOnline = () => { this.online = true; };
            this._onOffline = () => { this.online = false; };
            window.addEventListener('online', this._onOnline);
            window.addEventListener('offline', this._onOffline);
        },
        destroy() {
            if (this._onOnline) window.removeEventListener('online', this._onOnline);
            if (this._onOffline) window.removeEventListener('offline', this._onOffline);
        },

        get counts() {
            const c = { present: 0, late: 0, absent: 0, total: this.etudiants.length };
            this.etudiants.forEach((s) => { if (c[s.statut] !== undefined) c[s.statut]++; });
            return c;
        },
        get filtres() {
            return this.etudiants.filter((s) => this.visible(s));
        },
        get libelleBouton() {
            if (this.saving) return 'Enregistrement…';
            return (this.hasRollCall ? 'Mettre à jour l\'appel · ' : 'Enregistrer l\'appel · ') + this.etudiants.length;
        },

        normaliser(texte) {
            return String(texte || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        },
        visible(s) {
            const q = this.normaliser(this.q).trim();
            if (q === '') return true;
            return this.normaliser(s.nom).includes(q) || this.normaliser(s.matricule).includes(q);
        },

        marquer(s, statut) {
            if (this.saving) return;
            if (statut === 'late' && !this.retardPossible) return;
            s.statut = statut;
        },
        toutMarquer(statut) {
            if (this.saving) return;
            this.etudiants.forEach((s) => { s.statut = statut; });
        },

        /* Geste : glisser la ligne à droite = présent, à gauche = absent.
           Le pointeur n'est capturé qu'une fois le mouvement franchement horizontal,
           pour que les boutons répondent au toucher et que la liste défile encore. */
        dragStart(ev, s) {
            if (this.saving) return;
            if (ev.pointerType === 'mouse' && ev.button !== 0) return;
            this._drag = { id: s.id, x: ev.clientX, y: ev.clientY, pid: ev.pointerId, axe: null, el: ev.currentTarget };
        },
        dragMove(ev, s) {
            const d = this._drag;
            if (!d || d.id !== s.id || d.pid !== ev.pointerId) return;
            const dx = ev.clientX - d.x;
            const dy = ev.clientY - d.y;
            if (d.axe === null) {
                if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
                d.axe = Math.abs(dx) > Math.abs(dy) ? 'x' : 'y';
                if (d.axe === 'x' && d.el && d.el.setPointerCapture) {
                    try { d.el.setPointerCapture(ev.pointerId); } catch (e) {}
                }
            }
            if (d.axe !== 'x') return;
            s.dx = Math.max(-110, Math.min(110, dx));
        },
        dragEnd(ev, s) {
            const d = this._drag;
            if (!d || d.id !== s.id || d.pid !== ev.pointerId) return;
            const dx = s.dx;
            const horizontal = d.axe === 'x';
            if (horizontal && d.el && d.el.releasePointerCapture) {
                try { d.el.releasePointerCapture(ev.pointerId); } catch (e) {}
            }
            this._drag = null;
            s.dx = 0;
            if (!horizontal || ev.type === 'pointercancel') return;
            if (dx >= this.seuil) this.marquer(s, 'present');
            else if (dx <= -this.seuil) this.marquer(s, 'absent');
        },

        toast(type, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: type, message: message } }));
        },

        async enregistrer() {
            if (this.saving || !this.online || this.etudiants.length === 0) return;
            const form = document.getElementById('trmForm');
            if (!form) return;
            this.saving = true;
            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                    credentials: 'same-origin',
                });
                let data = {};
                try { data = await res.json(); } catch (e) { data = {}; }
                if (!res.ok || data.success === false) {
                    let message = data.message || '';
                    if (!message && data.errors) {
                        message = Object.values(data.errors).flat().join(' ');
                    }
                    if (!message) {
                        message = res.status === 419
                            ? 'Votre session a expiré, rechargez la page puis recommencez.'
                            : 'L\'enregistrement a échoué (erreur ' + res.status + ').';
                    }
                    throw new Error(message);
                }
                this.toast('success', data.message || 'Appel enregistré.');
                const cible = data.redirect || this.dashboardUrl;
                setTimeout(() => { window.location.assign(cible); }, 800);
            } catch (err) {
                this.toast('error', err && err.message ? err.message : 'L\'enregistrement a échoué.');
                this.saving = false;
            }
        },
    };
}
</script>
@endif
<script>
(function() {
    'use strict';

    debugLog('🎯 Initialisation du système d\'appel...');

    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(() => alert.remove(), 150);
            }
        });
    }, 5000);

    // Handle attendance button clicks with SIMPLE event handling
    const attendanceButtons = document.querySelectorAll('.attendance-btn');
    debugLog('📊 Nombre de boutons trouvés:', attendanceButtons.length);

    attendanceButtons.forEach(function(button, index) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            debugLog('🖱️ Clic sur bouton', index + 1);

            // Get the student's other buttons
            const studentItem = this.closest('.student-item');
            if (!studentItem) {
                debugError('❌ Impossible de trouver student-item');
                return;
            }

            const allButtons = studentItem.querySelectorAll('.attendance-btn');

            // Remove active class from all buttons for this student
            allButtons.forEach(function(btn) {
                btn.classList.remove('active');
            });

            // Add active class to clicked button
            this.classList.add('active');
            debugLog('✅ Classe active ajoutée');

            // Check the radio button inside this label
            const radioInput = this.querySelector('input[type="radio"]');
            if (radioInput) {
                radioInput.checked = true;
                debugLog('✅ Radio checked:', radioInput.value);
            } else {
                debugError('❌ Radio input non trouvé');
            }

            // Update statistics
            updateStats();
        }, true); // Use capture phase
    });

    // Function to update attendance statistics
    function updateStats() {
        let presentCount = 0;
        let lateCount = 0;
        let absentCount = 0;

        document.querySelectorAll('.attendance-btn.active').forEach(activeBtn => {
            if (activeBtn.classList.contains('present')) presentCount++;
            else if (activeBtn.classList.contains('late')) lateCount++;
            else if (activeBtn.classList.contains('absent')) absentCount++;
        });

        const presentEl = document.getElementById('presentCount');
        const lateEl = document.getElementById('lateCount');
        const absentEl = document.getElementById('absentCount');
        if (presentEl) presentEl.textContent = presentCount;
        if (lateEl) lateEl.textContent = lateCount;
        if (absentEl) absentEl.textContent = absentCount;
    }

    // Bulk actions
    const form = document.getElementById('rollCallForm');

    // Add bulk action buttons
    const rollCallHeader = document.querySelector('.roll-call-header');
    if (rollCallHeader) {
        const bulkActions = document.createElement('div');
        bulkActions.style.cssText = 'display: flex; gap: 8px; margin-top: 16px; flex-wrap: wrap; justify-content: center;';
        bulkActions.innerHTML = `
            <button type="button" class="btn btn-sm btn-success" onclick="bulkSetAttendance('present')">
                <i class="fas fa-check me-1"></i>Tous présents
            </button>
            <button type="button" class="btn btn-sm btn-warning" onclick="bulkSetAttendance('late')">
                <i class="fas fa-clock me-1"></i>Tous en retard
            </button>
            <button type="button" class="btn btn-sm btn-danger" onclick="bulkSetAttendance('absent')">
                <i class="fas fa-times me-1"></i>Tous absents
            </button>
        `;
        rollCallHeader.appendChild(bulkActions);
    }

    // Bulk set attendance function
    window.bulkSetAttendance = function(status) {
        document.querySelectorAll('.attendance-btn.' + status).forEach(button => {
            button.click();
        });
    };

    // Initialize stats
    updateStats();
    debugLog('✅ Stats initialisées');

    // Form submission confirmation
    if (form) {
        form.addEventListener('submit', function(e) {
            const activeButtons = document.querySelectorAll('.attendance-btn.active');
            if (activeButtons.length === 0) {
                e.preventDefault();
                alert('Veuillez marquer au moins un étudiant avant d\'enregistrer.');
                return;
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Enregistrement...</span>';
            }
        });
    }

    debugLog('✅ Système d\'appel prêt !');
})();
</script>
@endpush
