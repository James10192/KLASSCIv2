@extends('layouts.app')

@section('title', 'Tableau de bord Super Admin')

@section('content')
@php
    $saShellMobile = ($mobileShellEnabled ?? false) && ($mobileProfile ?? null);
@endphp
@if($saShellMobile)
<div class="m-only-mobile m-screen">
    <x-m.appbar title="Tableau de bord" :sub="$anneeEnCours->name ?? 'Année non définie'" />
    <div class="m-body" data-m-ptr="reload">
        {{-- Mosaïque (maquette M3) : le chiffre clé en grande tuile bleue, ce qui
             attend en orange, le reste en petites tuiles. Chaque tuile ouvre sa liste. --}}
        @php
            $samInscrits = (int) ($totalStudents ?? 0);
            $samEnAttente = (int) ($pendingInscriptionsCount ?? 0);
            $samTuiles = [
                ['v' => $totalStudentsBase ?? $totalStudents, 'l' => 'Étudiants en base', 'href' => route('esbtp.etudiants.index')],
                ['v' => $totalClasses ?? 0, 'l' => 'Classes', 'href' => route('esbtp.classes.index')],
                ['v' => $totalFilieres ?? 0, 'l' => 'Filières', 'href' => route('esbtp.filieres.index')],
                ['v' => $totalMatieres ?? 0, 'l' => 'Matières', 'href' => route('esbtp.matieres.index')],
                ['v' => $totalTeachers ?? 0, 'l' => 'Enseignants', 'href' => route('esbtp.enseignants.index')],
            ];
        @endphp
        <div class="sam-bento">
            <a href="{{ route('esbtp.etudiants.index') }}" class="sam-tuile sam-tuile--cle">
                <span class="sam-k">Inscrits {{ $anneeLabel ?? '' }}</span>
                <span class="sam-v">{{ number_format($samInscrits, 0, ',', ' ') }}</span>
                <span class="sam-k">{{ $samEnAttente > 0 ? $samEnAttente.' encore à valider' : 'toutes validées' }}</span>
            </a>
            <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="sam-tuile {{ $samEnAttente > 0 ? 'sam-tuile--att' : '' }}">
                <span class="sam-v">{{ $samEnAttente }}</span>
                <span class="sam-l">Inscription{{ $samEnAttente > 1 ? 's' : '' }} en attente</span>
                @if($samEnAttente > 0)<span class="sam-go">Consulter →</span>@endif
            </a>
            @foreach($samTuiles as $t)
                <a href="{{ $t['href'] }}" class="sam-tuile">
                    <span class="sam-v">{{ number_format((int) $t['v'], 0, ',', ' ') }}</span>
                    <span class="sam-l">{{ $t['l'] }}</span>
                </a>
            @endforeach
        </div>

        @php
            // Barres en SVG calculées ici : pas de Chart.js sur téléphone.
            // monthlyStats porte 12 mois, du plus ancien au courant.
            $samMois = collect($monthlyStats ?? [])->values();
            $samMax = max(1, (int) $samMois->max('inscriptions'));
            $samPas = $samMois->count() > 0 ? 300 / $samMois->count() : 300;
            $samTotal = (int) $samMois->sum('inscriptions');
            $samValides = (int) $samMois->sum('students');
            $samAttente = (int) ($samMois->last()['pending_payments'] ?? 0);
            $samFilieres = collect($filiereStats ?? [])->filter(fn ($f) => ($f['students'] ?? 0) > 0)->sortByDesc('students')->values();
            $samFilTotal = max(1, (int) $samFilieres->sum('students'));
        @endphp

        <div class="m-sec"><b>Inscriptions sur 12 mois</b></div>
        <div class="m-chart">
            @if($samTotal === 0)
                <p class="sam-vide">Aucune inscription enregistrée sur les douze derniers mois.</p>
            @else
                <svg viewBox="0 0 300 120" role="img" aria-label="{{ $samTotal }} inscriptions sur 12 mois, dont {{ $samValides }} validées">
                    <g stroke="#e9edf5"><line x1="0" y1="20" x2="300" y2="20"/><line x1="0" y1="55" x2="300" y2="55"/><line x1="0" y1="90" x2="300" y2="90"/></g>
                    @foreach($samMois as $i => $m)
                        @php
                            $samH = round(((int) $m['inscriptions']) / $samMax * 88, 1);
                            $samHv = round(min((int) $m['students'], (int) $m['inscriptions']) / $samMax * 88, 1);
                            $samX = round($i * $samPas + $samPas * .2, 1);
                            $samW = round($samPas * .6, 1);
                        @endphp
                        <rect x="{{ $samX }}" y="{{ 102 - $samH }}" width="{{ $samW }}" height="{{ $samH }}" rx="2" fill="#c7d7f3"/>
                        <rect x="{{ $samX }}" y="{{ 102 - $samHv }}" width="{{ $samW }}" height="{{ $samHv }}" rx="2" fill="#0453cb"/>
                        @if($i % 2 === 1 || $i === $samMois->count() - 1)
                            <text x="{{ round($samX + $samW / 2, 1) }}" y="116" font-size="9" fill="#64748b" text-anchor="middle">{{ now()->subMonths($samMois->count() - 1 - $i)->locale('fr')->isoFormat('MMM') }}</text>
                        @endif
                    @endforeach
                    <text x="2" y="16" font-size="9" fill="#64748b">{{ $samMax }}</text>
                </svg>
                <div class="sam-legende">
                    <span><i class="sam-pastille"></i>Validées : <b>{{ $samValides }}</b></span>
                    <span><i class="sam-pastille clair"></i>Créées : <b>{{ $samTotal }}</b></span>
                </div>
                @if($samAttente > 0)
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="sam-attente">{{ $samAttente }} inscription(s) sans paiement validé à ce jour</a>
                @endif
            @endif
        </div>

        <div class="m-sec"><b>Répartition par filière</b><a href="{{ route('esbtp.filieres.index') }}">Filières</a></div>
        @if($samFilieres->isEmpty())
            <x-m.empty icon="book" title="Aucune inscription par filière" />
        @else
            <div class="m-chart sam-filieres">
                @foreach($samFilieres->take(6) as $f)
                    @php $samPct = round($f['students'] / $samFilTotal * 100); @endphp
                    <div class="sam-fil">
                        <div class="sam-fil-tete"><span>{{ $f['name'] }}</span><b>{{ $f['students'] }} · {{ $samPct }} %</b></div>
                        <div class="m-bar"><i style="width: {{ $samPct }}%"></i></div>
                    </div>
                @endforeach
                @if($samFilieres->count() > 6)
                    <p class="sam-vide">+ {{ $samFilieres->count() - 6 }} autre(s) filière(s)</p>
                @endif
            </div>
        @endif

        <div class="m-sec"><b>Actions rapides</b></div>
        <div class="m-list">
            <x-m.row :href="route('esbtp.evaluations.create')" icon="pen" title="Créer une évaluation" sub="Examen, devoir, contrôle" />
            <x-m.row :href="route('esbtp.annonces.create')" icon="msg" title="Publier une annonce" sub="Étudiants, enseignants, personnel" />
            <x-m.row :href="route('esbtp.resultats.index')" icon="print" title="Générer les bulletins" sub="Résultats et bulletins par classe" />
        </div>

        <div class="m-sec"><b>Inscriptions récentes</b><a href="{{ route('esbtp.inscriptions.index') }}">Tout voir</a></div>
        @if(($recentInscriptions ?? collect())->isEmpty())
            <x-m.empty icon="inbox" title="Aucune inscription récente" />
        @else
            <div class="m-list">
                @foreach($recentInscriptions->take(5) as $inscription)
                    @php
                        $saNom = trim(($inscription->etudiant->prenoms ?? '') . ' ' . ($inscription->etudiant->nom ?? ''));
                        $saNom = $saNom !== '' ? $saNom : 'N/A';
                        $saIni = mb_strtoupper(mb_substr($inscription->etudiant->prenoms ?? 'N', 0, 1) . mb_substr($inscription->etudiant->nom ?? 'A', 0, 1));
                        $saSous = collect([
                            $inscription->classe->filiere->name ?? null,
                            $inscription->classe->name ?? null,
                            optional($inscription->created_at)->format('d/m'),
                        ])->filter()->implode(' · ');
                    @endphp
                    <x-m.row :href="$inscription->etudiant ? route('esbtp.etudiants.show', $inscription->etudiant->id) : null"
                             :av="$saIni"
                             :title="$saNom"
                             :sub="$saSous"
                             :chip="$inscription->status === 'active' ? 'Active' : 'En attente'"
                             :chip-type="$inscription->status === 'active' ? 'ok' : 'warn'" />
                @endforeach
            </div>
        @endif
    </div>
    <x-m.actionbar>
        <a href="{{ route('esbtp.inscriptions.create') }}" class="m-btn p"><x-m.icon name="plus" />Nouvel étudiant</a>
    </x-m.actionbar>
</div>
@endif
@push('styles')
<style>
    /* Tableau de bord superAdmin mobile — namespace sam- */
    .sam-vide { margin: 0; font-size: 12.5px; color: #64748b; }
    .sam-bento { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
    .sam-tuile { background: #fff; border-radius: 20px; padding: 14px; display: flex; flex-direction: column; gap: 2px; text-decoration: none; color: #0f172a; min-width: 0; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 6px 18px rgba(15,23,42,.05); }
    .sam-tuile:active { transform: scale(.97); }
    .sam-v { font-size: 23px; font-weight: 800; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .sam-l { font-size: 12px; color: #64748b; }
    .sam-k { font-size: 12px; font-weight: 600; color: rgba(255,255,255,.8); }
    .sam-tuile--cle { grid-column: span 2; background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 55%, #3b7ddb 100%); color: #fff; box-shadow: 0 12px 30px rgba(4,83,203,.25); }
    .sam-tuile--cle .sam-v { font-size: 34px; }
    .sam-tuile--att { background: #fff7ed; }
    .sam-tuile--att .sam-v { color: #9a3412; }
    .sam-go { margin-top: 4px; font-size: 12px; font-weight: 700; color: #9a3412; }
    .sam-legende { display: flex; gap: 14px; flex-wrap: wrap; font-size: 12px; color: #475569; }
    .sam-legende b { color: #0f172a; }
    .sam-pastille { display: inline-block; width: 10px; height: 10px; border-radius: 3px; background: #0453cb; margin-right: 6px; vertical-align: -1px; }
    .sam-pastille.clair { background: #c7d7f3; }
    .sam-attente { font-size: 12.5px; font-weight: 600; color: #8a5200; text-decoration: none; }
    .sam-filieres { gap: 12px; }
    .sam-fil { display: grid; grid-template-columns: minmax(0, 1fr); gap: 6px; min-width: 0; }
    .sam-fil-tete { min-width: 0; }
    .sam-fil-tete { display: flex; justify-content: space-between; gap: 10px; font-size: 13px; color: #1e293b; }
    .sam-fil-tete span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; }
    .sam-fil-tete b { flex-shrink: 0; font-variant-numeric: tabular-nums; }
</style>
@endpush
<div class="main-content {{ $saShellMobile ? 'm-only-desktop' : '' }}">
    <!-- Header -->
    <div class="dashboard-header mb-xl" style="background-color: var(--primary); color: white; border-radius: var(--radius-medium); display: block;">
        <div class="row align-items-center">
            <div class="col-12 col-lg-7">
                <div style="display: flex; align-items: center; gap: var(--space-lg);">
                    <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25); backdrop-filter: blur(8px); color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 700; box-shadow: var(--shadow-elevated);">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <h1 style="color: white; margin: 0; font-size: var(--title-main); font-weight: 700;">Tableau de bord Super Admin</h1>
                        <p style="color: rgba(255,255,255,0.8); margin: var(--space-xs) 0 0 0;">Gestion administrative KLASSCI</p>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-5 d-flex">
                <div class="header-actions" style="display: flex; justify-content: flex-end; align-items: center; gap: var(--space-sm); width: 100%;">
                    <div>
                        <span class="badge rounded-pill" style="background: rgba(255,255,255,0.15); color: #fff; border: 1px solid rgba(255,255,255,0.35); box-shadow: 0 6px 16px rgba(0,0,0,0.18); padding: 0.55rem 0.9rem; font-weight: 600; letter-spacing: 0.2px;">
                            <i class="fas fa-calendar me-1"></i>
                            {{ $anneeEnCours->name ?? 'Année non définie' }}
                        </span>
                    </div>
                    <button class="btn-acasi secondary" style="margin-right: var(--space-md);" onclick="location.reload()" title="Actualiser les données">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <div class="dropdown d-inline-block">
                        <button class="btn-acasi" style="background-color: var(--primary); color: white;" type="button" id="quickActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-bolt"></i> Actions rapides
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('esbtp.inscriptions.create') }}"><i class="fas fa-user-plus" style="color: var(--primary);"></i> Nouvel étudiant</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.evaluations.create') }}"><i class="fas fa-file-alt" style="color: var(--primary);"></i> Créer examen</a></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.annonces.create') }}"><i class="fas fa-bullhorn" style="color: var(--primary);"></i> Publier annonce</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('esbtp.resultats.index') }}"><i class="fas fa-print" style="color: var(--primary);"></i> Générer bulletins</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- $pendingInscriptionsCount est calculé dans DashboardController avec la logique workflow_step --}}

    <!-- Alert for pending inscriptions -->
    @if($pendingInscriptionsCount > 0)
    <div class="alert alert-warning alert-dismissible fade show mb-lg" style="background-color: rgba(245, 158, 11, 0.1); border: 1px solid var(--warning); border-radius: var(--radius-medium); padding: var(--space-lg);">
        <div style="display: flex; align-items: center; gap: var(--space-md);">
            <div style="width: 64px; height: 64px; background-color: var(--warning); color: white; border-radius: var(--radius-medium); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-card);">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
            </div>
            <div style="flex: 1;">
                <h5 style="color: var(--warning); font-weight: 600; margin: 0 0 var(--space-sm) 0;">
                    <i class="fas fa-bell"></i>
                    Attention! Inscriptions en attente
                </h5>
                <p style="margin: 0 0 var(--space-md) 0; color: #1e293b;">
                    Il y a <strong>{{ $pendingInscriptionsCount }}</strong> inscription(s) en attente de validation.<br>
                    Ces inscriptions nécessitent votre vérification pour finaliser le processus d'admission des étudiants.
                </p>
                <a href="{{ route('esbtp.inscriptions.index', ['status' => 'non_validee']) }}" class="btn-acasi" style="background-color: var(--warning); color: white;">
                    <i class="fas fa-check-circle"></i> Consulter et valider
                </a>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="color: #d97706; opacity: 0.8;"></button>
    </div>
    @endif

    <!-- KPI Cards -->
    <div class="kpi-grid mb-xl">
        <div class="kpi-card card-moderne" style="background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 100%); color: white; text-align: center;"
             title="Étudiants distincts avec inscription active validée sur l'année {{ $anneeLabel ?? 'courante' }}">
            <i class="fas fa-user-check fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Inscrits {{ $anneeLabel ?? 'année courante' }}</div>
            <div class="kpi-value" style="color: white;">{{ $totalStudents }}</div>
        </div>

        @if(isset($totalStudentsBase))
        <div class="kpi-card card-moderne" style="background: linear-gradient(135deg, #3b7ddb 0%, #5e91de 100%); color: white; text-align: center;"
             title="Toutes les fiches étudiants dans la base (toutes années / statuts confondus, y compris diplômés et non-réinscrits)">
            <i class="fas fa-users fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Étudiants en base</div>
            <div class="kpi-value" style="color: white;">{{ $totalStudentsBase }}</div>
        </div>
        @endif

        <div class="kpi-card card-moderne" style="background: linear-gradient(135deg, #0453cb 0%, #1b64d4 100%); color: white; text-align: center;">
            <i class="fas fa-graduation-cap fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Filières</div>
            <div class="kpi-value" style="color: white;">{{ $totalFilieres }}</div>
        </div>

        <div class="kpi-card card-moderne" style="background: linear-gradient(135deg, #1b64d4 0%, #3b7ddb 100%); color: white; text-align: center;">
            <i class="fas fa-chalkboard-teacher fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Classes</div>
            <div class="kpi-value" style="color: white;">{{ $totalClasses }}</div>
        </div>

        <div class="kpi-card card-moderne" style="background: linear-gradient(135deg, #3b7ddb 0%, #5e91de 100%); color: white; text-align: center;">
            <i class="fas fa-book-open fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Matières</div>
            <div class="kpi-value" style="color: white;">{{ $totalMatieres }}</div>
        </div>

        <div class="kpi-card card-moderne" style="background: linear-gradient(135deg, #0a3d8f 0%, #3b7ddb 100%); color: white; text-align: center;">
            <i class="fas fa-user-tie fa-2x mb-md"></i>
            <div class="kpi-title" style="color: white;">Enseignants</div>
            <div class="kpi-value" style="color: white;">{{ $totalTeachers ?? 0 }}</div>
        </div>
    </div>

    <!-- Recent Inscriptions Table -->
    <div class="card-moderne mb-xl">
        <div class="p-lg">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
                <div>
                    <div class="section-title" style="color: var(--primary);">
                        <i class="fas fa-user-plus"></i>
                        Inscriptions récentes
                    </div>
                    <p style="color: var(--text-secondary); margin: 0;">Dernières demandes d'inscription</p>
                </div>
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi primary">
                    <i class="fas fa-eye"></i> Voir tout
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover" style="border-collapse: separate; border-spacing: 0; border-radius: var(--radius-medium); overflow: hidden; box-shadow: var(--shadow-card);">
                    <thead style="background-color: var(--primary); color: white;">
                        <tr>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-user"></i> Étudiant
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-graduation-cap"></i> Filière
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-school"></i> Classe
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-calendar"></i> Date
                            </th>
                            <th style="padding: var(--space-md); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; border: none;">
                                <i class="fas fa-flag"></i> Statut
                            </th>
                        </tr>
                    </thead>
                    <tbody style="background-color: var(--surface);">
                        @foreach($recentInscriptions as $inscription)
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: var(--space-md);">
                                <div style="display: flex; align-items: center;">
                                    @if($inscription->etudiant && $inscription->etudiant->photo_url)
                                        <img src="{{ $inscription->etudiant->photo_url }}"
                                             alt="{{ $inscription->etudiant->prenoms }} {{ $inscription->etudiant->nom }}"
                                             style="width: 44px; height: 44px; border-radius: var(--radius-circle); object-fit: cover; margin-right: var(--space-md);">
                                    @else
                                        <div style="width: 44px; height: 44px; border-radius: var(--radius-circle); background-color: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: var(--space-md);">
                                            {{ strtoupper(substr($inscription->etudiant->prenoms ?? 'N', 0, 1) . substr($inscription->etudiant->nom ?? 'A', 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        @if($inscription->etudiant)
                                            <a href="{{ route('esbtp.etudiants.show', $inscription->etudiant->id) }}"
                                               style="text-decoration: none;">
                                                <div style="font-weight: 600; color: var(--primary); cursor: pointer;">
                                                    {{ $inscription->etudiant->prenoms ?? 'N/A' }} {{ $inscription->etudiant->nom ?? 'N/A' }}
                                                </div>
                                            </a>
                                        @else
                                            <div style="font-weight: 600; color: var(--text-primary);">N/A</div>
                                        @endif
                                        <small style="color: var(--text-secondary);">{{ $inscription->etudiant->email_personnel ?? $inscription->etudiant->email ?? 'Email non disponible' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: var(--space-md);">
                                @if($inscription->classe && $inscription->classe->filiere)
                                    <span class="badge success">
                                        <i class="fas fa-graduation-cap"></i>
                                        {{ $inscription->classe->filiere->name }}
                                    </span>
                                @else
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-question"></i> Non définie
                                    </span>
                                @endif
                            </td>
                            <td style="padding: var(--space-md);">
                                @if($inscription->classe)
                                    <span class="badge" style="background-color: rgba(6, 182, 212, 0.1); color: var(--accent-blue);">
                                        <i class="fas fa-users"></i>
                                        {{ $inscription->classe->name }}
                                    </span>
                                @else
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-question"></i> Non assignée
                                    </span>
                                @endif
                            </td>
                            <td style="padding: var(--space-md);">
                                <div style="font-weight: 600; color: var(--text-primary);">{{ $inscription->created_at->format('d/m/Y') }}</div>
                                <small style="color: var(--text-secondary);">{{ $inscription->created_at->diffForHumans() }}</small>
                            </td>
                            <td style="padding: var(--space-md);">
                                @if($inscription->status === 'active' || $inscription->status === 'actif')
                                    <span class="badge success">
                                        <i class="fas fa-check-circle"></i> Actif
                                    </span>
                                @elseif($inscription->status === 'validated' || $inscription->status === 'validé' || $inscription->status === 'approved')
                                    <span class="badge success">
                                        <i class="fas fa-check-circle"></i> Validé
                                    </span>
                                @elseif($inscription->status === 'pending' || $inscription->status === 'en_attente' || $inscription->status === 'waiting')
                                    <span class="badge warning">
                                        <i class="fas fa-clock"></i> En attente
                                    </span>
                                @elseif($inscription->status === 'rejected' || $inscription->status === 'refusé' || $inscription->status === 'refused')
                                    <span class="badge danger">
                                        <i class="fas fa-times-circle"></i> Refusé
                                    </span>
                                @elseif($inscription->status === 'inactive' || $inscription->status === 'inactif' || $inscription->status === 'disabled')
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-pause-circle"></i> Inactif
                                    </span>
                                @else
                                    <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: var(--neutral);">
                                        <i class="fas fa-info-circle"></i> {{ ucfirst($inscription->status ?? 'Inconnu') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="text-center mt-lg">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-right"></i> Voir toutes les inscriptions
                </a>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-xl">
        <div class="col-xl-8 mb-lg">
            <div class="card-moderne p-lg">
                <div class="section-title mb-lg" style="color: var(--primary); border-bottom: 2px solid var(--primary); padding-bottom: var(--space-sm);">
                    <i class="fas fa-chart-area"></i>
                    Évolution des inscriptions et paiements
                </div>
                <p style="color: var(--text-secondary); margin-bottom: var(--space-lg);">
                    <span style="color: #0453cb;">■</span> Inscriptions créées
                    <span style="margin-left: 1rem; color: #10b981;">■</span> Inscriptions validées
                    <span style="margin-left: 1rem; color: #f59e0b;">■</span> Inscriptions en attente de paiement
                </p>
                
                <div class="chart-container">
                    <canvas id="inscriptionsChart"></canvas>
                </div>

                <!-- Note explicative -->
                <div class="mt-md" style="padding: var(--space-md); background-color: rgba(245, 158, 11, 0.05); border-left: 4px solid var(--warning); border-radius: var(--radius-small);">
                    <div style="display: flex; align-items: start; gap: var(--space-sm);">
                        <i class="fas fa-info-circle" style="color: var(--warning); margin-top: 2px;"></i>
                        <div style="flex: 1;">
                            <strong style="color: var(--warning);">Note sur la courbe orange :</strong>
                            <p style="margin: var(--space-xs) 0 0 0; color: var(--text-secondary); font-size: var(--text-small);">
                                Cette courbe affiche le <strong>stock cumulatif</strong> d'inscriptions en attente de paiement à la fin de chaque mois
                                (toutes les inscriptions créées jusqu'à cette date qui n'ont pas encore de paiement validé).
                                Elle montre le <strong>travail restant</strong> et permet de détecter si la situation s'améliore ou empire dans le temps.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-lg">
            <div class="card-moderne p-lg">
                <div class="section-title mb-lg" style="color: var(--success); border-bottom: 2px solid var(--success); padding-bottom: var(--space-sm);">
                    <i class="fas fa-chart-pie"></i>
                    Répartition par filière
                </div>
                <p style="color: var(--text-secondary); margin-bottom: var(--space-lg);">Distribution des étudiants</p>
                
                <div style="position: relative; height: 250px; margin-bottom: var(--space-lg);">
                    <canvas id="filieresChart"></canvas>
                </div>
                
                <div>
                    @foreach($filiereStats as $filiere)
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--space-sm); margin-bottom: var(--space-sm); background: var(--background); border-radius: var(--radius-small); border-left: 4px solid {{ $filiere['color'] }};">
                        <div style="display: flex; align-items: center;">
                            <div style="width: 14px; height: 14px; background: {{ $filiere['color'] }}; border-radius: 50%; margin-right: var(--space-sm);"></div>
                            <span style="font-weight: 500; color: var(--text-primary);">{{ $filiere['name'] }}</span>
                        </div>
                        <span class="badge primary">{{ $filiere['students'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-xl">
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.inscriptions.create') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--primary); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-user-plus fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Nouvel étudiant</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.evaluations.create') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--success); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-file-alt fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Créer examen</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.annonces.create') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--warning); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-bullhorn fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Publier annonce</span>
            </a>
        </div>
        <div class="col-xl-3 col-md-6 mb-lg">
            <a href="{{ route('esbtp.resultats.index') }}" class="card-moderne" style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: var(--space-xl); text-decoration: none; color: inherit; transition: all 0.3s ease;">
                <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--accent-blue); color: white; display: flex; align-items: center; justify-content: center; margin-bottom: var(--space-md); box-shadow: var(--shadow-card);">
                    <i class="fas fa-print fa-2x"></i>
                </div>
                <span style="font-weight: 600; color: var(--text-primary);">Générer bulletins</span>
            </a>
        </div>
    </div>
</div>

<!-- Chart.js Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Données pour le graphique des inscriptions
    const monthlyData = @json($monthlyStats);

    // Debug: afficher les données dans la console
    debugLog('📊 Données mensuelles pour le graphique:', monthlyData);
    debugLog('📊 Vérification des étudiants créés (courbe verte):', monthlyData.map(item => ({
        month: item.month + ' ' + item.year,
        students: item.students,
        inscriptions: item.inscriptions,
        pending_payments: item.pending_payments
    })));

    // Graphique des inscriptions
    const inscriptionsCtx = document.getElementById('inscriptionsChart');
    if (inscriptionsCtx) {
        new Chart(inscriptionsCtx, {
            type: 'line',
            data: {
                labels: monthlyData.map(item => item.month + ' ' + item.year),
                datasets: [{
                        label: 'Toutes inscriptions',
                    data: monthlyData.map(item => item.inscriptions),
                        borderColor: '#0453cb',
                        backgroundColor: 'rgba(4, 83, 203, 0.1)',
                        tension: 0.4,
                        fill: true
                }, {
                    label: 'Inscriptions validées',
                    data: monthlyData.map(item => item.students),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Inscriptions avec paiement en attente',
                    data: monthlyData.map(item => item.pending_payments),
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            footer: function(tooltipItems) {
                                const index = tooltipItems[0].dataIndex;
                                const data = monthlyData[index];
                                return 'Total: ' + data.inscriptions + ' | Validés: ' + data.students + ' | En attente: ' + data.pending_payments;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Graphique des filières
    const filieresCtx = document.getElementById('filieresChart');
    if (filieresCtx) {
        const filieresData = @json($filiereStats);

        new Chart(filieresCtx, {
            type: 'doughnut',
            data: {
                labels: filieresData.map(item => item.name),
                datasets: [{
                    data: filieresData.map(item => item.students),
                    backgroundColor: filieresData.map(item => item.color),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                            display: false
                    }
                }
            }
        });
    }
    });
</script>
@endsection
