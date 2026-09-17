@extends('layouts.app')

@section('title', 'Séances de cours - KLASSCI')

@push('styles')
<style>
/* Namespace `sdc-` (séances de cours). Cette page était en Bootstrap brut et
   n'apparaissait dans aucun menu ; elle porte pourtant la seule vue
   transversale (tous emplois du temps confondus) et le seul relevé des
   conflits DÉJÀ en base — le garde de saisie, lui, empêche seulement d'en
   créer de nouveaux. */
.sdc-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.sdc-hero-top {
    display: flex; align-items: flex-start; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem;
}
.sdc-hero-left { display: flex; align-items: center; gap: 1rem; }
.sdc-hero-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.15);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; flex-shrink: 0; color: #fff;
}
.sdc-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; }
.sdc-hero p { color: rgba(255,255,255,.7); font-size: .88rem; margin: 0; }

.sdc-btn {
    display: inline-flex; align-items: center; gap: .45rem;
    border-radius: 10px; padding: .5rem 1rem;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; border: 1px solid transparent; cursor: pointer;
    transition: background .15s, color .15s, border-color .15s;
}
.sdc-btn--white { background: #fff; color: #0453cb; }
.sdc-btn--white:hover { background: #eef4ff; color: #033a8e; }
.sdc-btn--glass {
    background: rgba(255,255,255,.15); color: #fff;
    border-color: rgba(255,255,255,.2);
}
.sdc-btn--glass:hover { background: rgba(255,255,255,.25); color: #fff; }

.sdc-kpis { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
.sdc-kpi {
    flex: 1; min-width: 150px;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.15);
    border-radius: 12px; padding: .9rem 1rem;
    display: flex; align-items: center; gap: .75rem;
}
.sdc-kpi-icon {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    background: rgba(255,255,255,.14);
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; color: #fff;
}
.sdc-kpi-value { font-size: 1.35rem; font-weight: 700; color: #fff; line-height: 1; }
.sdc-kpi-label { font-size: .72rem; color: rgba(255,255,255,.65); margin-top: .2rem; }

.sdc-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    margin-bottom: 1.25rem;
}
.sdc-card-head {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem; border-bottom: 1px solid #eef2f7;
}
.sdc-card-icon {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0453cb, #3b7ddb);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .95rem;
}
.sdc-card-title { font-size: .95rem; font-weight: 700; color: #1e293b; margin: 0; }
.sdc-card-sub { font-size: .76rem; color: #64748b; margin: 0; }
.sdc-card-body { padding: 1.25rem; }

.sdc-filters { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; }
.sdc-field { display: flex; flex-direction: column; gap: .3rem; flex: 1 1 200px; min-width: 0; }
/* Le libellé d'une option de sélecteur premium est coupé à l'ellipse, et le menu
   fait la largeur de son déclencheur. Sur une colonne de filtre étroite, on
   lisait « Semestre 3 — 2026-2027 (… » : le nom de la classe, c'est-à-dire la
   seule chose qui distingue deux emplois du temps, tombait.
   L'écran de séparation des devoirs a résolu le même piège en RACCOURCISSANT
   ses libellés ; ici c'est impossible, ce sont des données. Le libellé passe
   donc à la ligne au lieu d'être coupé — l'option grandit en hauteur, ce qui ne
   coûte rien dans un menu déroulant.

   N'ESSAYEZ PAS D'ÉLARGIR LE MENU PAR UNE FEUILLE DE STYLE. Ce fichier a porté
   dix lignes le faisant (`width: max-content`, `max-width`, `min-width`), plus
   un commentaire certifiant les avoir mesurées actives. Elles étaient mortes :
   `ouvrir()` appelle `positionMenu(true)` SANS condition, et cette méthode écrit
   toujours `width` / `min-width` / `max-width` en ligne via `:style="menuStyle"`
   — un style en ligne bat une feuille sans `!important`. La « mesure » de 396,7 px
   invoquée pour les défendre était en réalité la largeur du déclencheur, donc la
   preuve qu'elles ne portaient pas. Élargir le menu se règle dans `positionMenu()`,
   pas ici. */
.sdc-field .au-select-option-label { white-space: normal; overflow: visible; text-overflow: clip; }
.sdc-field-label {
    font-size: .72rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .5px;
}
.sdc-btn--primary { background: #0453cb; color: #fff; }
.sdc-btn--primary:hover { background: #033a8e; color: #fff; }
.sdc-btn--ghost { background: #fff; color: #475569; border-color: #e2e8f0; }
.sdc-btn--ghost:hover { border-color: #cbd5e1; color: #0453cb; }

.sdc-table-wrap { overflow-x: auto; }
.sdc-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
.sdc-table th {
    text-align: left; padding: .7rem .8rem;
    font-size: .7rem; font-weight: 700; color: #64748b;
    text-transform: uppercase; letter-spacing: .5px;
    border-bottom: 1px solid #e2e8f0; white-space: nowrap;
}
.sdc-table td { padding: .7rem .8rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
.sdc-table tbody tr:last-child td { border-bottom: none; }
/* Pas de `transform` au survol : cette ligne porte un menu d'actions, et un
   `transform` sur un parent rompt le positionnement fixe des menus déroulants
   (cf. universal-dropdowns.md). */
.sdc-table tbody tr:hover { background: #f8fafc; }
.sdc-row--off { opacity: .55; }
.sdc-horaire { font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
.sdc-muted { color: #94a3b8; }

.sdc-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    padding: .25rem .6rem; border-radius: 999px;
    font-size: .72rem; font-weight: 600; white-space: nowrap;
}
.sdc-statut { font-size: .72rem; font-weight: 700; padding: .22rem .55rem; border-radius: 6px; }
.sdc-statut--on { background: rgba(16,185,129,.1); color: #047857; border: 1px solid rgba(16,185,129,.25); }
.sdc-statut--off { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }

.sdc-actions { display: flex; gap: .3rem; }
.sdc-act {
    width: 30px; height: 30px; border-radius: 8px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .76rem; border: 1px solid #e2e8f0; background: #fff;
    color: #475569; text-decoration: none; cursor: pointer;
    transition: border-color .15s, color .15s, background .15s;
}
.sdc-act:hover { border-color: #0453cb; color: #0453cb; background: #f8fafc; }
.sdc-act--danger:hover { border-color: #dc2626; color: #dc2626; background: #fef2f2; }

.sdc-empty { text-align: center; padding: 2.5rem 1rem; color: #64748b; }
.sdc-empty i { font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: .6rem; }

.sdc-alert {
    display: flex; gap: .7rem; align-items: flex-start;
    border-radius: 12px; padding: .9rem 1.1rem; margin-bottom: 1.25rem;
    font-size: .85rem; border: 1px solid transparent;
}
.sdc-alert--ok { background: rgba(16,185,129,.07); border-color: rgba(16,185,129,.22); color: #065f46; }
.sdc-alert--bad { background: rgba(220,38,38,.06); border-color: rgba(220,38,38,.2); color: #991b1b; }
.sdc-alert--warn { background: rgba(245,158,11,.08); border-color: rgba(245,158,11,.25); color: #92400e; }

.sdc-conflits { max-height: 320px; overflow-y: auto; display: flex; flex-direction: column; gap: .5rem; }
.sdc-conflit {
    border: 1px solid rgba(245,158,11,.25); background: rgba(245,158,11,.05);
    border-radius: 10px; padding: .7rem .85rem; font-size: .82rem;
}
.sdc-conflit-when { font-weight: 700; color: #1e293b; }
.sdc-conflit-what { color: #92400e; margin-top: .15rem; }

.sdc-stat-list { display: flex; flex-direction: column; gap: .35rem; }
.sdc-stat-row {
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
    padding: .5rem .7rem; border-radius: 8px; background: #f8fafc; font-size: .82rem;
}
.sdc-stat-row span:first-child { display: inline-flex; align-items: center; gap: .45rem; color: #1e293b; }
.sdc-stat-count {
    font-variant-numeric: tabular-nums; font-weight: 700; font-size: .78rem;
    padding: .15rem .55rem; border-radius: 999px;
    background: rgba(4,83,203,.1); color: #0453cb;
}
.sdc-grid2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }

@media (max-width: 992px) {
    .sdc-grid2 { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .sdc-hero { padding: 1.5rem 1.25rem 1.25rem; }
    .sdc-hero h1 { font-size: 1.2rem; }
    .sdc-kpi { min-width: 100%; }
    .sdc-field { flex: 1 1 100%; }
}
</style>
@endpush

@section('content')
{{-- `.dashboard-acasi` est la coquille FLEX qui porte la barre latérale ; le
     contenu de page vit dans `.main-content`, sinon il devient un élément flex
     nu qui ne remplit pas sa colonne. Même emboîtement que la page sœur
     emploi-temps/show. --}}
<div class="dashboard-acasi">
<div class="main-content">

    <div class="sdc-hero">
        <div class="sdc-hero-top">
            <div class="sdc-hero-left">
                <div class="sdc-hero-icon"><i class="fas fa-calendar-day"></i></div>
                <div>
                    <h1>Séances de cours</h1>
                    <p>Toutes les séances, tous emplois du temps confondus — et les conflits d'horaire déjà enregistrés.</p>
                </div>
            </div>
            <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                <a href="{{ route('esbtp.emploi-temps.index') }}" class="sdc-btn sdc-btn--glass">
                    <i class="fas fa-table"></i>Emplois du temps
                </a>
                <a href="{{ route('esbtp.seances-cours.create') }}" class="sdc-btn sdc-btn--white">
                    <i class="fas fa-plus"></i>Ajouter une séance
                </a>
            </div>
        </div>

        <div class="sdc-kpis">
            <div class="sdc-kpi">
                <div class="sdc-kpi-icon"><i class="fas fa-list"></i></div>
                <div>
                    <div class="sdc-kpi-value">{{ $seancesCours->total() }}</div>
                    <div class="sdc-kpi-label">Séances listées</div>
                </div>
            </div>
            <div class="sdc-kpi">
                <div class="sdc-kpi-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <div class="sdc-kpi-value">{{ count($conflits) }}</div>
                    <div class="sdc-kpi-label">Conflits d'horaire</div>
                </div>
            </div>
            <div class="sdc-kpi">
                <div class="sdc-kpi-icon"><i class="fas fa-table"></i></div>
                <div>
                    <div class="sdc-kpi-value">{{ $emploisTemps->count() }}</div>
                    <div class="sdc-kpi-label">Emplois du temps</div>
                </div>
            </div>
            <div class="sdc-kpi">
                <div class="sdc-kpi-icon"><i class="fas fa-chalkboard-user"></i></div>
                <div>
                    <div class="sdc-kpi-value">{{ $enseignants->count() }}</div>
                    <div class="sdc-kpi-label">Enseignants au planning</div>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="sdc-alert sdc-alert--ok">
            <i class="fas fa-circle-check" style="margin-top:.15rem;"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    @if (session('error'))
        <div class="sdc-alert sdc-alert--bad">
            <i class="fas fa-circle-exclamation" style="margin-top:.15rem;"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="sdc-card">
        <div class="sdc-card-head">
            <div class="sdc-card-icon"><i class="fas fa-filter"></i></div>
            <div>
                <p class="sdc-card-title">Filtrer les séances</p>
                <p class="sdc-card-sub">Les quatre filtres se combinent.</p>
            </div>
        </div>
        <div class="sdc-card-body">
            @php
                // Les options des sélecteurs premium : ['valeur' => 'libellé'].
                // Le composant garde un <select> caché, donc le formulaire GET
                // part exactement comme avant.
                $sdcOptionsEmploiTemps = [];
                foreach ($emploisTemps as $sdcEmploiTemps) {
                    $sdcOptionsEmploiTemps[(string) $sdcEmploiTemps->id] =
                        $sdcEmploiTemps->titre.' ('.optional($sdcEmploiTemps->classe)->name.')';
                }

                $sdcOptionsJours = [];
                foreach (\App\Domain\EmploiTemps\JourDeLaSemaine::libelles() as $sdcJourValeur => $sdcJourLibelle) {
                    $sdcOptionsJours[(string) $sdcJourValeur] = $sdcJourLibelle;
                }

                $sdcOptionsEnseignants = [];
                foreach ($enseignants as $sdcEnseignant) {
                    $sdcOptionsEnseignants[(string) $sdcEnseignant->id] = $sdcEnseignant->name;
                }
            @endphp
            <form action="{{ route('esbtp.seances-cours.index') }}" method="GET" class="sdc-filters">
                <div class="sdc-field">
                    <label class="sdc-field-label" for="emploi_temps_id">Emploi du temps</label>
                    <x-au-select
                        name="emploi_temps_id"
                        :value="request('emploi_temps_id')"
                        :options="$sdcOptionsEmploiTemps"
                        placeholder="Tous les emplois du temps"
                        icon="fa-table"
                        :searchable="true" />
                </div>
                <div class="sdc-field">
                    <label class="sdc-field-label" for="jour_semaine">Jour</label>
                    <x-au-select
                        name="jour_semaine"
                        :value="request('jour_semaine')"
                        :options="$sdcOptionsJours"
                        placeholder="Tous les jours"
                        icon="fa-calendar-day" />
                </div>
                <div class="sdc-field">
                    <label class="sdc-field-label" for="type_seance">Type de séance</label>
                    <x-au-select
                        name="type_seance"
                        :value="request('type_seance')"
                        :options="\App\Enums\TypeSeance::selectOptions()"
                        placeholder="Tous les types"
                        icon="fa-shapes" />
                </div>
                <div class="sdc-field">
                    <label class="sdc-field-label" for="enseignant">Enseignant</label>
                    <x-au-select
                        name="enseignant"
                        :value="request('enseignant')"
                        :options="$sdcOptionsEnseignants"
                        placeholder="Tous les enseignants"
                        icon="fa-chalkboard-user"
                        :searchable="true" />
                </div>
                <div style="display:flex;gap:.5rem;">
                    <button type="submit" class="sdc-btn sdc-btn--primary">
                        <i class="fas fa-search"></i>Filtrer
                    </button>
                    <a href="{{ route('esbtp.seances-cours.index') }}" class="sdc-btn sdc-btn--ghost">
                        <i class="fas fa-rotate-left"></i>Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="sdc-card">
        <div class="sdc-card-head">
            <div class="sdc-card-icon"><i class="fas fa-list"></i></div>
            <div>
                <p class="sdc-card-title">Les séances</p>
                <p class="sdc-card-sub">{{ $seancesCours->total() }} séance(s) — page {{ $seancesCours->currentPage() }} sur {{ max($seancesCours->lastPage(), 1) }}</p>
            </div>
        </div>
        <div class="sdc-table-wrap">
            <table class="sdc-table">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Horaire</th>
                        <th>Classe</th>
                        <th>Matière</th>
                        <th>Enseignant</th>
                        <th>Salle</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- La table de correspondance des jours a été retirée : elle ne
                         connaissait que les entiers, et la colonne `jour` porte aussi
                         des libellés. `JourDeLaSemaine` comprend les deux. --}}
                    @forelse($seancesCours as $seance)
                        <tr @class(['sdc-row--off' => ! $seance->is_active])>
                            <td>{{ \App\Domain\EmploiTemps\JourDeLaSemaine::libelle($seance->jour) ?? 'Inconnu' }}</td>
                            {{-- `format('H:i')` et non l'attribut brut : le modèle déclare un
                                 accesseur qui fait `Carbon::parse()`, donc la lecture en contexte
                                 chaîne rendrait « 2026-09-15 08:00:00 ». C'est le piège #14 de
                                 klassci-debugging-discipline, et cette colonne en était le site
                                 le plus visible. --}}
                            <td class="sdc-horaire">
                                {{ $seance->heure_debut?->format('H:i') ?? '--:--' }}
                                <span class="sdc-muted">→</span>
                                {{ $seance->heure_fin?->format('H:i') ?? '--:--' }}
                            </td>
                            <td>{{ optional(optional($seance->emploiTemps)->classe)->name ?? '—' }}</td>
                            <td>{{ optional($seance->matiere)->name ?? '—' }}</td>
                            {{-- Par la relation, pas par `$seance->enseignant` : le modèle porte
                                 une colonne morte de ce nom ET une relation homonyme, et Eloquent
                                 sert l'attribut avant la relation. La colonne s'affichait donc
                                 vide sur toutes les lignes. `teacher.user` est chargé par
                                 `listeFiltree()`, sans quoi ce serait un N+1 sur la page.

                                 Le compte est testé AVANT le nom, et pas seulement l'affectation :
                                 `ESBTPTeacher::getNameAttribute()` ne rend jamais `null` — sans
                                 compte lié, il rend la chaîne « N/A ». Un `?? '—'` écrit ici
                                 serait donc du code mort, et afficherait ce sigle technique dans
                                 une colonne en français. --}}
                            <td>{{ $seance->teacher?->user ? $seance->teacher->name : '—' }}</td>
                            <td>{{ $seance->salle ?: '—' }}</td>
                            <td>
                                @php
                                    $ts = $seance->type_seance instanceof \App\Enums\TypeSeance
                                        ? $seance->type_seance
                                        : \App\Enums\TypeSeance::fromLegacy($seance->type_seance ?? null);
                                @endphp
                                <span class="sdc-badge" style="{{ $ts->badgeInlineStyle() }}">
                                    <i class="fas {{ $ts->badgeIcon() }}"></i>{{ $ts->label() }}
                                </span>
                            </td>
                            <td>
                                @if($seance->is_active)
                                    <span class="sdc-statut sdc-statut--on">Actif</span>
                                @else
                                    <span class="sdc-statut sdc-statut--off">Inactif</span>
                                @endif
                            </td>
                            <td>
                                <div class="sdc-actions" style="justify-content:flex-end;">
                                    <a href="{{ route('esbtp.emploi-temps.show', $seance->emploi_temps_id) }}" class="sdc-act" title="Voir l'emploi du temps">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('esbtp.seances-cours.edit', $seance->id) }}" class="sdc-act" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <button type="button" class="sdc-act sdc-act--danger" data-bs-toggle="modal" data-bs-target="#sdcDelete{{ $seance->id }}" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <div class="modal fade" id="sdcDelete{{ $seance->id }}" tabindex="-1" aria-labelledby="sdcDeleteLabel{{ $seance->id }}" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content" style="border-radius:14px;border:none;">
                                            <div class="modal-header" style="border-bottom:1px solid #eef2f7;">
                                                <h5 class="modal-title" style="font-size:1rem;font-weight:700;" id="sdcDeleteLabel{{ $seance->id }}">Supprimer cette séance ?</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                                            </div>
                                            <div class="modal-body" style="font-size:.88rem;">
                                                <p style="margin-bottom:.5rem;">
                                                    <strong>{{ \App\Domain\EmploiTemps\JourDeLaSemaine::libelle($seance->jour) ?? 'Jour inconnu' }}</strong>
                                                    de <strong>{{ $seance->heure_debut?->format('H:i') ?? '--:--' }}</strong>
                                                    à <strong>{{ $seance->heure_fin?->format('H:i') ?? '--:--' }}</strong>
                                                    — {{ optional($seance->matiere)->name ?? 'matière inconnue' }}
                                                </p>
                                                <p style="margin-bottom:.75rem;color:#64748b;">
                                                    Classe : {{ optional(optional($seance->emploiTemps)->classe)->name ?? '—' }}
                                                </p>
                                                <div class="sdc-alert sdc-alert--warn" style="margin-bottom:0;">
                                                    <i class="fas fa-triangle-exclamation" style="margin-top:.15rem;"></i>
                                                    <div>Cette action est irréversible.</div>
                                                </div>
                                            </div>
                                            <div class="modal-footer" style="border-top:1px solid #eef2f7;">
                                                <button type="button" class="sdc-btn sdc-btn--ghost" data-bs-dismiss="modal">Annuler</button>
                                                <form action="{{ route('esbtp.seances-cours.destroy', $seance->id) }}" method="POST" style="display:inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="sdc-btn" style="background:#dc2626;color:#fff;">
                                                        <i class="fas fa-trash"></i>Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">
                                <div class="sdc-empty">
                                    <i class="fas fa-calendar-xmark"></i>
                                    Aucune séance ne correspond à ces filtres.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($seancesCours->hasPages())
            <div style="padding:1rem 1.25rem;border-top:1px solid #eef2f7;">
                {{ $seancesCours->withQueryString()->links() }}
            </div>
        @endif
    </div>

    <div class="sdc-grid2">
        <div class="sdc-card">
            <div class="sdc-card-head">
                <div class="sdc-card-icon"><i class="fas fa-triangle-exclamation"></i></div>
                <div>
                    <p class="sdc-card-title">Conflits d'horaire</p>
                    <p class="sdc-card-sub">Ceux déjà enregistrés — la saisie, elle, refuse d'en créer de nouveaux.</p>
                </div>
            </div>
            <div class="sdc-card-body">
                @if(count($conflits) > 0)
                    <div class="sdc-alert sdc-alert--warn">
                        <i class="fas fa-triangle-exclamation" style="margin-top:.15rem;"></i>
                        <div><strong>{{ count($conflits) }}</strong> conflit(s) détecté(s) sur les séances enregistrées.</div>
                    </div>
                    <div class="sdc-conflits">
                        @foreach($conflits as $conflit)
                            <div class="sdc-conflit">
                                <div class="sdc-conflit-when">
                                    {{ \App\Domain\EmploiTemps\JourDeLaSemaine::libelle($conflit['jour']) ?? 'Jour inconnu' }}
                                    — {{ $conflit['heure_debut'] }} à {{ $conflit['heure_fin'] }}
                                </div>
                                <div class="sdc-conflit-what">
                                    @if($conflit['type'] === 'Enseignant')
                                        {{ $conflit['nom'] }} a plusieurs cours en même temps.
                                    @elseif($conflit['type'] === 'Salle')
                                        La salle {{ $conflit['nom'] }} est réservée plusieurs fois.
                                    @else
                                        La classe {{ $conflit['nom'] }} a plusieurs cours en même temps.
                                    @endif
                                </div>
                                <div style="margin-top:.5rem;">
                                    <a href="{{ route('esbtp.seances-cours.edit', $conflit['seance_id']) }}" class="sdc-btn sdc-btn--ghost" style="padding:.3rem .7rem;font-size:.76rem;">
                                        <i class="fas fa-pen"></i>Corriger
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sdc-alert sdc-alert--ok" style="margin-bottom:.85rem;">
                        <i class="fas fa-circle-check" style="margin-top:.15rem;"></i>
                        <div>Aucun conflit d'horaire sur les séances enregistrées.</div>
                    </div>
                    <p style="font-size:.8rem;color:#64748b;margin-bottom:.4rem;">Trois règles sont vérifiées :</p>
                    <ul style="font-size:.8rem;color:#64748b;padding-left:1.1rem;margin:0;">
                        <li>un enseignant ne donne pas deux cours en même temps ;</li>
                        <li>une salle n'accueille pas deux cours en même temps ;</li>
                        <li>une classe n'a pas deux cours en même temps.</li>
                    </ul>
                @endif
            </div>
        </div>

        <div class="sdc-card">
            <div class="sdc-card-head">
                <div class="sdc-card-icon"><i class="fas fa-chart-simple"></i></div>
                <div>
                    <p class="sdc-card-title">Répartition</p>
                    <p class="sdc-card-sub">Sur l'ensemble des séances, filtres non appliqués.</p>
                </div>
            </div>
            <div class="sdc-card-body">
                <p class="sdc-field-label" style="margin-bottom:.5rem;">Par type</p>
                <div class="sdc-stat-list" style="margin-bottom:1.1rem;">
                    @foreach(\App\Enums\TypeSeance::cases() as $ts)
                        <div class="sdc-stat-row">
                            <span>
                                <i class="fas {{ $ts->badgeIcon() }}" style="color:{{ $ts->badgeStyle()['color'] }};"></i>{{ $ts->label() }}
                            </span>
                            <span class="sdc-stat-count">{{ $statsCours[$ts->value] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>

                <p class="sdc-field-label" style="margin-bottom:.5rem;">Par jour</p>
                <div class="sdc-stat-list">
                    {{-- Les six jours viennent de `JourDeLaSemaine`, et non d'une liste
                         recopiée ici : c'est en la recopiant que ce panneau s'est
                         retrouvé à lire six clés entières sur une colonne qui porte
                         aussi des libellés, et à compter pour zéro toutes les séances
                         saisies depuis l'emploi du temps. --}}
                    @foreach(\App\Domain\EmploiTemps\JourDeLaSemaine::libelles() as $jourValeur => $jourLibelle)
                        <div class="sdc-stat-row">
                            <span>{{ $jourLibelle }}</span>
                            <span class="sdc-stat-count">{{ $statsJours[$jourValeur] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
</div>
@endsection
