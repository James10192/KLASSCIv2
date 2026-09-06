@extends('layouts.app')

@section('title', 'Tableau de bord Étudiant')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    /* Accueil étudiant mobile — namespace mab-* (le socle m-* vient de mobile-shell.css) */
    .mab-screen .m-hero .v { font-size: 22px; line-height: 1.15; }
    .mab-screen .m-hero.mab-hero-calme .v { font-size: 17px; font-weight: 700; opacity: .92; }
    .mab-screen .m-hero .v small { display: inline-block; white-space: nowrap; }
    .mab-screen .m-grade .n.mab-abs { color: #a12016; font-size: 15px; }
    .mab-screen .m-empty.mab-empty-court { padding: 18px 14px; }
</style>
@endpush

@section('content')
@php
    // Accueil mobile : rendu seulement quand le shell est actif pour un étudiant.
    $mab = $mobileAccueil ?? null;
    $mabShell = ($mobileShellEnabled ?? false) && (($mobileProfile ?? null) === 'etudiant') && is_array($mab);
@endphp
@if($mabShell)
@php
    $mabUser = auth()->user();
    $mabPeutNotes = $mabUser->canAny(['notes.view_own', 'notes.view']);
    $mabPeutAbsences = $mabUser->canAny(['attendances.view_own', 'attendances.view']);
    $mabPeutBulletins = $mabUser->canAny(['bulletins.view_own', 'bulletins.view']);
    // Même garde que la route esbtp.mes-paiements.index.
    $mabPeutPaiements = $mabUser->canAny(['profile.view_own', 'notes.view_own']);

    $mabMontant = fn ($v) => number_format((float) $v, 0, ',', ' ');
    // 13,40 → « 13,4 » ; 13,00 → « 13 » ; null → « — ».
    $mabNombre = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $mabDate = fn ($d, $fmt = 'D MMMM') => $d ? mb_strtolower($d->copy()->locale('fr')->isoFormat($fmt), 'UTF-8') : '';

    $mabAujourdhui = \Illuminate\Support\Str::ucfirst($mabDate($mab['aujourdhui'], 'dddd D MMMM'));
    $mabPrenom = $mab['prenom'] ?: 'à vous';
    $mabHeroLabel = 'Bonjour ' . $mabPrenom . ($mab['classe'] ? ' · ' . $mab['classe'] : '');

    $mabCours = $mab['prochain_cours'];
    if (! $mab['classe']) {
        $mabHeroValeur = 'Aucune inscription active';
        $mabHeroUnite = null;
        $mabHeroCalme = true;
    } elseif ($mabCours) {
        $mabHeroValeur = ($mabCours['en_cours'] ? 'En cours depuis ' : 'Prochain cours ') . $mabCours['heure'];
        $mabHeroUnite = $mabCours['salle'];
        $mabHeroCalme = false;
    } else {
        $mabHeroValeur = 'Plus de cours aujourd\'hui';
        $mabHeroUnite = null;
        $mabHeroCalme = true;
    }
    $mabHeroPills = [];
    if ($mabCours) {
        $mabHeroPills[] = implode(' · ', array_filter([$mabCours['matiere'], $mabCours['enseignant']]));
    }

    // Quatre indicateurs : chacun renvoie vers son écran quand le droit existe.
    $mabAssiduite = $mab['assiduite'];
    $mabAbsences = (int) ($mab['absences'] ?? 0);
    $mabAJustifier = (int) ($mab['a_justifier']['total'] ?? 0);
    $mabResteDu = $mab['finances']['reste_du'] ?? null;
    $mabEcheance = $mab['finances']['prochaine_echeance'] ?? null;

    if ($mab['credits']) {
        $mabKpiResultat = [
            'value' => $mab['credits']['acquis'] . ' / ' . $mab['credits']['total'],
            'label' => 'Crédits acquis',
            'delta' => 'bulletins publiés',
            'tone' => $mab['credits']['acquis'] >= $mab['credits']['total'] ? 'ok' : 'info',
            'href' => $mabPeutBulletins ? route('esbtp.mon-bulletin.index') : null,
        ];
    } else {
        $mabKpiResultat = [
            'value' => $mabNombre($mab['moyenne']),
            'label' => 'Moyenne',
            'delta' => $mab['moyenne'] === null ? 'pas encore de note' : 'sur 20',
            'tone' => $mab['moyenne'] === null ? 'mute' : ($mab['moyenne'] >= 10 ? 'ok' : 'bad'),
            'href' => $mabPeutNotes ? route('esbtp.mes-notes.index') : null,
        ];
    }
    $mabKpis = [
        $mabKpiResultat,
        [
            'value' => $mabAssiduite === null ? '—' : $mabNombre($mabAssiduite) . ' %',
            'label' => 'Assiduité',
            'delta' => $mabAssiduite === null ? 'aucun appel' : ($mabAbsences . ' absence' . ($mabAbsences > 1 ? 's' : '')),
            'tone' => $mabAssiduite === null ? 'mute' : ($mabAbsences > 0 ? 'warn' : 'ok'),
            'href' => $mabPeutAbsences ? route('esbtp.mes-absences.index') : null,
        ],
        [
            'value' => (string) $mabAJustifier,
            'label' => $mabAJustifier > 1 ? 'Absences à justifier' : 'Absence à justifier',
            'delta' => $mabAJustifier > 0 ? 'à traiter' : 'rien en attente',
            'tone' => $mabAJustifier > 0 ? 'bad' : 'ok',
            'href' => $mabPeutAbsences ? route('esbtp.mes-absences.index') : null,
        ],
        [
            'value' => $mabResteDu === null ? '—' : $mabMontant($mabResteDu),
            'label' => 'Reste dû · FCFA',
            'delta' => $mabResteDu === null ? 'indisponible' : ($mabResteDu > 0 ? ($mabEcheance ? mb_strtolower($mabEcheance['label'], 'UTF-8') : 'à régler') : 'à jour'),
            'tone' => $mabResteDu === null ? 'mute' : ($mabResteDu > 0 ? (($mabEcheance['en_retard'] ?? false) ? 'bad' : 'warn') : 'ok'),
            'href' => $mabPeutPaiements ? route('esbtp.mes-paiements.index') : null,
        ],
    ];

    // Lignes « À faire » : les droits sont revérifiés au rendu par les gardes de permission.
    $mabAbs = $mab['a_justifier']['derniere'] ?? null;
    $mabAFaireAbsence = $mabPeutAbsences && $mabAJustifier > 0;
    $mabAFairePaiement = $mabPeutPaiements && $mabResteDu !== null && $mabResteDu > 0;

    $mabAbsTitre = 'Justifier l\'absence' . ($mabAbs && $mabAbs['date'] ? ' du ' . $mabDate($mabAbs['date']) : '');
    if ($mabAJustifier > 1) {
        $mabAbsTitre .= ' (+' . ($mabAJustifier - 1) . ')';
    }
    $mabAbsSous = $mabAbs ? implode(' · ', array_filter([
        $mabAbs['matiere'],
        $mabAbs['heure_debut'] && $mabAbs['heure_fin'] ? $mabAbs['heure_debut'] . '–' . $mabAbs['heure_fin'] : null,
    ])) : null;

    if ($mabEcheance) {
        $mabPayTitre = $mabEcheance['en_retard']
            ? \Illuminate\Support\Str::ucfirst($mabEcheance['label']) . ' en retard depuis le ' . $mabDate($mabEcheance['date'])
            : 'Payer ' . mb_strtolower($mabEcheance['label'], 'UTF-8') . ' avant le ' . $mabDate($mabEcheance['date']);
        $mabJours = now()->startOfDay()->diffInDays($mabEcheance['date'], false);
        if ($mabEcheance['en_retard']) {
            [$mabPayChip, $mabPayTon] = ['En retard', 'bad'];
        } elseif ($mabJours <= 15) {
            [$mabPayChip, $mabPayTon] = ['Bientôt', 'warn'];
        } else {
            [$mabPayChip, $mabPayTon] = ['À venir', 'mute'];
        }
    } else {
        $mabPayTitre = 'Régler le reste dû';
        [$mabPayChip, $mabPayTon] = ['À régler', 'warn'];
    }
    $mabPaySous = $mabResteDu !== null ? $mabMontant($mabResteDu) . ' FCFA restants' : null;
    $mabNotifUrl = Route::has('esbtp.mes-notifications.index') ? route('esbtp.mes-notifications.index') : null;
@endphp
<div class="m-only-mobile m-screen mab-screen">
    {{-- La navbar porte deja KLASSCI et le nom de l'ecole : repeter l'ecole
         ici ferait doublon a trois lignes d'intervalle. --}}
    <x-m.appbar title="Accueil" :sub="$mabAujourdhui" :action="$mabNotifUrl ? 'bell' : null" :action-url="$mabNotifUrl" action-label="Notifications" />

    <div class="m-body" data-m-ptr="reload">
        <x-m.hero :label="$mabHeroLabel" :value="$mabHeroValeur" :unit="$mabHeroUnite" :pills="$mabHeroPills" :class="$mabHeroCalme ? 'mab-hero-calme' : ''" />

        <x-m.kpi :items="$mabKpis" />

        <div class="m-sec"><b>À faire</b></div>
        @if($mabAFaireAbsence || $mabAFairePaiement)
            <div class="m-list one">
                @canany(['attendances.view_own', 'attendances.view'])
                    @if($mabAFaireAbsence)
                        <x-m.row :href="route('esbtp.mes-absences.index')" icon="clock" :title="$mabAbsTitre" :sub="$mabAbsSous" chip="À justifier" chip-type="bad" />
                    @endif
                @endcanany
                @canany(['profile.view_own', 'notes.view_own'])
                    @if($mabAFairePaiement)
                        <x-m.row :href="route('esbtp.mes-paiements.index')" icon="cash" :title="$mabPayTitre" :sub="$mabPaySous" :chip="$mabPayChip" :chip-type="$mabPayTon" />
                    @endif
                @endcanany
            </div>
        @else
            <x-m.empty class="mab-empty-court" icon="check" title="Rien à faire" text="Aucune absence à justifier, aucun paiement en attente." />
        @endif

        @canany(['notes.view_own', 'notes.view'])
            <div class="m-sec"><b>Dernières notes</b><a href="{{ route('esbtp.mes-notes.index') }}">Tout voir</a></div>
            @if(count($mab['notes']))
                <div class="m-list one">
                    @foreach($mab['notes'] as $mabNote)
                        @php
                            $mabNoteTitre = $mabNote['matiere'] . ($mabNote['titre'] ? ' · ' . $mabNote['titre'] : '');
                            $mabNoteSous = implode(' · ', array_filter([
                                $mabNote['code'] ? ($mab['est_lmd'] ? 'ECUE ' : '') . $mabNote['code'] : null,
                                $mabNote['coefficient'] !== null ? 'coef ' . $mabNombre($mabNote['coefficient']) : null,
                                $mabNote['date'] ? $mabDate($mabNote['date']) : null,
                            ]));
                        @endphp
                        <div class="m-grade">
                            <div>
                                <b>{{ $mabNoteTitre }}</b>
                                @if($mabNoteSous !== '')
                                    <span>{{ $mabNoteSous }}</span>
                                @endif
                            </div>
                            @if($mabNote['absent'])
                                <span class="n mab-abs">Absent</span>
                            @else
                                <span class="n">{{ $mabNombre($mabNote['note']) }}<small>/{{ $mabNombre($mabNote['bareme']) }}</small></span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <x-m.empty class="mab-empty-court" icon="pen" title="Pas encore de note" text="Les notes saisies par vos enseignants apparaîtront ici." />
            @endif
        @endcanany
    </div>
</div>
@endif

<div class="dashboard-acasi{{ $mabShell ? ' m-only-desktop' : '' }}">
    <div class="main-content">
        <!-- Header Étudiant - même style que superadmin -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Bienvenue, {{ $user->name }}</h1>
                <p class="header-subtitle">Votre espace étudiant KLASSCI</p>
            </div>
            <div class="header-actions">
                @if(isset($student))
                    <div class="year-selector">
                        <i class="fas fa-calendar me-1"></i>
                        {{ $anneeEnCours->name ?? 'Année non définie' }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Statistiques Étudiant - style moderne admin-stats -->
        <div class="admin-stats" style="margin-bottom: var(--space-xl);">
            <!-- Matricule -->
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $student->matricule ?? 'N/A' }}</div>
                        <div class="stat-label" style="margin: 0;">Numéro Matricule</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.mon-profil.index') }}" class="btn-acasi primary" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md);">
                        <i class="fas fa-user" style="margin-right: var(--space-xs);"></i>
                        Voir mon profil
                    </a>
                </div>
            </div>

            <!-- Taux de Présence -->
            @if(isset($attendanceStats))
            @php
                // Aucun appel enregistre (ou calcul indisponible) : taux null,
                // affiche « — » sans couleur d'alerte — jamais un faux 0 %.
                $_tauxPresence = $attendanceStats['rate'] ?? null;
                $_tonPresence = $_tauxPresence === null ? '' : ($_tauxPresence >= 75 ? 'success' : ($_tauxPresence >= 50 ? 'warning' : 'danger'));
            @endphp
            <div class="stat-card {{ $_tonPresence }}" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon {{ $_tonPresence }}">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $_tauxPresence === null ? '—' : $_tauxPresence . '%' }}</div>
                        <div class="stat-label" style="margin: 0;">Taux de Présence</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.mes-absences.index') }}" class="btn-acasi primary" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md); white-space: nowrap;">
                        <i class="fas fa-chart-line" style="margin-right: var(--space-xs);"></i>
                        Mes présences
                    </a>
                </div>
            </div>
            @endif

            <!-- Classe -->
            @if(isset($classe))
            <div class="stat-card success" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon success">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $classe->nom }}</div>
                        <div class="stat-label" style="margin: 0;">Ma Classe</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--success);">
                    <i class="fas fa-users"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Formation active</span>
                </div>
            </div>
            @endif

            <!-- Filière -->
            @if(isset($filiere))
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $filiere->nom }}</div>
                        <div class="stat-label" style="margin: 0;">Filière</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--neutral);">
                    <i class="fas fa-certificate"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Spécialisation</span>
                </div>
            </div>
            @endif

            <!-- Notifications -->
            @if(isset($unreadNotifications))
            <div class="stat-card warning" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon warning">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $unreadNotifications }}</div>
                        <div class="stat-label" style="margin: 0;">Notifications</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.mes-notifications.index') }}" class="btn-acasi warning" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md); white-space: nowrap;">
                        <i class="fas fa-envelope" style="margin-right: var(--space-xs);"></i>
                        Voir notifications
                    </a>
                </div>
            </div>
            @endif

            <!-- Niveau -->
            @if(isset($niveau))
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $niveau->nom }}</div>
                        <div class="stat-label" style="margin: 0;">Niveau d'Étude</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--neutral);">
                    <i class="fas fa-stairs"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Progression</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Planning Cours d'Aujourd'hui - même style que main-card -->
        @if(isset($todayClasses) && $todayClasses->count() > 0)
        <div class="main-card">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-day"></i>
                    Cours d'Aujourd'hui
                </div>
                <div class="main-card-subtitle">Planning du {{ date('d/m/Y') }}</div>
            </div>
            <div class="main-card-body">
                <div class="course-list">
                    @foreach($todayClasses as $cours)
                    <div class="course-item">
                        <div class="course-time">
                            <div class="time-display">{{ $cours->heure_debut->format('H:i') }} - {{ $cours->heure_fin->format('H:i') }}</div>
                            <div class="course-day">{{ \Carbon\Carbon::parse($cours->heure_debut)->diffInHours(\Carbon\Carbon::parse($cours->heure_fin)) }}h</div>
                        </div>
                        <div class="course-info">
                            <div class="course-subject">{{ $cours->matiere->nom ?? 'N/A' }}</div>
                            <div class="course-class">{{ $cours->enseignant ?? 'Enseignant non défini' }}</div>
                            <div class="course-type">{{ $cours->salle ?? 'Salle non définie' }}</div>
                        </div>
                        <div class="course-status">
                            <span class="badge success">Programmé</span>
                        </div>
                        <div class="course-actions">
                            <i class="fas fa-chevron-right color-primary"></i>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Examens à venir - même style que main-card urgent -->
        @if(isset($upcomingExams) && $upcomingExams->count() > 0)
        <div class="main-card urgent">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-file-alt"></i>
                    Examens à Venir
                </div>
                <div class="main-card-subtitle">{{ $upcomingExams->count() }} examen(s) programmé(s)</div>
            </div>
            <div class="main-card-body">
                <div class="urgent-list">
                    @foreach($upcomingExams as $examen)
                    <div class="urgent-item">
                        <div class="urgent-info">
                            <div class="urgent-title">{{ $examen->matiere->nom ?? 'N/A' }} - {{ $examen->type }}</div>
                            <div class="urgent-time">
                                <i class="fas fa-calendar-day"></i> {{ $examen->date->format('d/m/Y') }}
                                <i class="fas fa-clock ml-3"></i> {{ $examen->heure }}
                            </div>
                        </div>
                        <div class="urgent-countdown">
                            @php
                                $daysUntil = now()->diffInDays($examen->date, false);
                            @endphp
                            @if($daysUntil > 0)
                                <span class="badge warning">{{ $daysUntil }} jour(s)</span>
                            @elseif($daysUntil == 0)
                                <span class="badge danger">Aujourd'hui</span>
                            @else
                                <span class="badge neutral">Passé</span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Notes récentes - même style que resultats -->
        @if(isset($recentGrades) && $recentGrades->count() > 0)
        <div class="resultats-grid">
            <div class="resultat-card card-moderne">
                <div class="resultat-title">
                    <i class="fas fa-chart-line"></i>
                    Notes Récentes
                </div>
                <div class="resultat-montant color-primary">{{ $recentGrades->count() }} note(s)</div>
                <div class="resultat-details">
                    @foreach($recentGrades as $note)
                    @php
                        // Récupérer le nom de la matière
                        $matiereName = $note->matiere->name ?? 'Matière non définie';
                        
                        // Récupérer la valeur de la note (convertir string en float)
                        $noteValue = floatval($note->note ?? 0);
                        
                        // Gestion des étudiants absents
                        $isAbsent = $note->is_absent ?? false;
                        $statusText = $isAbsent ? 'Absent' : '';
                    @endphp
                    <div class="resultat-detail">
                        <span>{{ $matiereName }}</span>
                        @if($isAbsent)
                            <span class="color-warning">
                                <i class="fas fa-user-times" style="margin-right: 4px;"></i>Absent
                            </span>
                        @else
                            <span class="color-{{ $noteValue >= 10 ? 'success' : 'danger' }}">
                                {{ number_format($noteValue, 2) }}/20
                            </span>
                        @endif
                    </div>
                    @endforeach
                </div>
                <div class="mt-md">
                    <a href="{{ route('esbtp.mes-notes.index') }}" class="btn-acasi primary">
                        <i class="fas fa-chart-bar"></i>
                        Voir toutes mes notes
                    </a>
                </div>
            </div>
        </div>
        @endif

        <!-- Emploi du temps - même style que table-moderne -->
        <div class="table-moderne" style="margin-bottom: var(--space-xl);">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-calendar-week"></i>
                    Emploi du Temps
                </div>
                <div class="main-card-subtitle">
                    @if(isset($classe) && $classe)
                        {{ $classe->nom ?? $classe->name ?? 'Ma Classe' }}
                    @elseif(isset($student) && isset($student->classe))
                        {{ $student->classe->nom ?? $student->classe->name ?? 'Ma Classe' }}
                    @else
                        Classe non définie
                    @endif
                </div>
            </div>
            
            <div class="empty-state" style="padding: var(--space-xl); text-align: center;">
                <i class="fas fa-calendar-times" style="font-size: 3rem; color: var(--text-muted); margin-bottom: var(--space-lg);"></i>
                <h4 style="color: var(--text-secondary); margin-bottom: var(--space-md);">Emploi du temps</h4>
                <p style="color: var(--text-muted);">
                    Consultez votre emploi du temps complet avec toutes les séances programmées.
                </p>
                <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="btn-acasi primary" style="margin-top: var(--space-md);">
                    <i class="fas fa-calendar-plus" style="margin-right: var(--space-xs);"></i>
                    Voir l'emploi du temps complet
                </a>
            </div>
        </div>

        <!-- Statistiques de présence détaillées - style moderne admin-stats -->
        <div class="admin-stats" style="margin-bottom: var(--space-xl);">
            @php
                // Calculer le taux de présence en utilisant les données du contrôleur
                $totalAttendances = isset($presences) && isset($absences) ?
                    $presences->count() + $absences->count() +
                    (isset($retards) ? $retards->count() : 0) +
                    (isset($excuses) ? $excuses->count() : 0) : 0;

                $present = isset($presences) ? $presences->count() : 0;
                $retard = isset($retards) ? $retards->count() : 0;
                $excuse = isset($excuses) ? $excuses->count() : 0;

                $presenceRate = $totalAttendances > 0 ?
                    round((($present + $retard + $excuse) / $totalAttendances) * 100) : 100;
            @endphp

            <div class="stat-card success" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon success">
                        <i class="fas fa-check"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $present }}</div>
                        <div class="stat-label" style="margin: 0;">Présences</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--success);">
                    <i class="fas fa-user-check"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Cours présent</span>
                </div>
            </div>
            
            <div class="stat-card danger" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon danger">
                        <i class="fas fa-times"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ isset($absences) ? $absences->count() : 0 }}</div>
                        <div class="stat-label" style="margin: 0;">Absences</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--danger);">
                    <i class="fas fa-user-times"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Cours manqués</span>
                </div>
            </div>
            
            <div class="stat-card warning" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon warning">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $retard }}</div>
                        <div class="stat-label" style="margin: 0;">Retards</div>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: var(--space-xs); color: var(--warning);">
                    <i class="fas fa-stopwatch"></i>
                    <span style="font-size: var(--text-small); font-weight: 500;">Arrivées tardives</span>
                </div>
            </div>
            
            <div class="stat-card" style="padding: var(--space-xl); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: var(--space-lg); flex: 1;">
                    <div class="stat-icon primary">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div style="flex: 1;">
                        <div class="stat-value" style="margin-bottom: var(--space-xs);">{{ $presenceRate }}%</div>
                        <div class="stat-label" style="margin: 0;">Taux Global</div>
                    </div>
                </div>
                <div>
                    <a href="{{ route('esbtp.mes-absences.index') }}" class="btn-acasi primary" style="font-size: var(--text-small); padding: var(--space-sm) var(--space-md); white-space: nowrap;">
                        <i class="fas fa-chart-line" style="margin-right: var(--space-xs);"></i>
                        Voir détails
                    </a>
                </div>
            </div>
        </div>

        <!-- Actions rapides - même style que quick-actions-section -->
        <div class="quick-actions-section">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-bolt"></i>
                    Actions Rapides
                </div>
            </div>
            <div class="quick-actions-grid">
                <a href="{{ route('esbtp.mes-notes.index') }}" class="quick-action-card">
                    <i class="fas fa-chart-line"></i>
                    <span>Mes Notes</span>
                </a>

                <a href="{{ route('esbtp.mes-absences.index') }}" class="quick-action-card">
                    <i class="fas fa-calendar-check"></i>
                    <span>Mes Présences</span>
                </a>

                <a href="{{ route('esbtp.mon-emploi-temps.index') }}" class="quick-action-card">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Emploi du Temps</span>
                </a>

                <a href="{{ route('esbtp.mes-evaluations.index') }}" class="quick-action-card">
                    <i class="fas fa-tasks"></i>
                    <span>Mes Évaluations</span>
                </a>

                <a href="{{ route('esbtp.mes-notifications.index') }}" class="quick-action-card">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>

                <a href="{{ route('esbtp.mon-profil.index') }}" class="quick-action-card">
                    <i class="fas fa-user-circle"></i>
                    <span>Mon Profil</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
