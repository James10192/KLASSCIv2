@extends('layouts.app')

@section('title', 'Réinscription - ' . $analyse['etudiant']->nom . ' ' . $analyse['etudiant']->prenoms)

@php
    use App\Services\Reinscription\EligibiliteReinscription as Eligibilite;

    $etudiant = $analyse['etudiant'];
    $inscription = $eligibilite['inscription'];
    $existante = $eligibilite['inscription_annee_cible'];
    $etat = $eligibilite['etat'];
    $anneeQuittee = $inscription->anneeUniversitaire->name ?? '—';
    $anneeCible = $eligibilite['annee_cible']->name ?? null;
    $regle = $analyse['regle'];
    $moyenne = (float) $analyse['moyenne_generale'];
    $decision = $analyse['decision'];
    $decisionFiable = $notesComptees > 0;

    $fcfa = fn ($m) => number_format((float) $m, 0, ',', ' ') . ' FCFA';
    $du = $eligibilite['du'];
    $paye = $eligibilite['paye'];
    $solde = $eligibilite['solde'];
    $tolerance = $eligibilite['tolerance'];
    // Rien de dû : la barre est pleine. Elle n'affiche plus « 0 % payé » en rouge
    // sur un dossier soldé.
    $pourcentPaye = $du > 0 ? min(100, round($paye / $du * 100, 1)) : 100;

    $decisions = [
        'passage' => ['Passage', 'Admis au niveau supérieur', 'fa-arrow-up', 'ok'],
        'rattrapage' => ['Rattrapage', 'Session de rattrapage', 'fa-rotate', 'warn'],
        'redoublement' => ['Redoublement', "Reprise de l'année", 'fa-redo', 'ko'],
    ];
    [$decisionLabel, $decisionAide, $decisionIcone, $decisionTon] = $decisions[$decision] ?? [ucfirst((string) $decision), '', 'fa-circle-question', 'neutre'];
    $tonMoyenne = $moyenne >= (float) $regle->moyenne_passage ? 'ok' : ($moyenne >= (float) $regle->moyenne_rattrapage ? 'warn' : 'ko');

    // Le lien porte l'année visée : la finalisation calcule pour CETTE année.
    $lienVers = fn ($annee) => route('esbtp.reinscription.create', array_filter([
        'etudiant' => $etudiant->id,
        'annee_academique' => $anneeAcademique,
        'annee_cible_id' => $annee?->id,
    ]));
    $lienFinalisation = $lienVers($eligibilite['annee_cible']);
    $initiales = mb_strtoupper(mb_substr((string) $etudiant->nom, 0, 1, 'UTF-8') . mb_substr((string) $etudiant->prenoms, 0, 1, 'UTF-8'), 'UTF-8');
@endphp

@push('styles')
<style>
.rsd { display: flex; flex-direction: column; gap: 1.25rem; }
.rsd-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px; padding: 1.75rem 2rem 1.5rem; color: #fff;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.rsd-hero-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
.rsd-hero-left { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.rsd-avatar {
    width: 56px; min-width: 56px; height: 56px; border-radius: 16px; flex-shrink: 0; overflow: hidden;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.15rem;
}
.rsd-avatar img { width: 100%; height: 100%; object-fit: cover; }
.rsd-hero h1 { font-size: 1.45rem; font-weight: 700; color: #fff; margin: 0; line-height: 1.25; }
.rsd-hero p { color: rgba(255,255,255,.75); font-size: .88rem; margin: .25rem 0 0; }
.rsd-pills { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .6rem; }
.rsd-pill {
    display: inline-flex; align-items: center; gap: .4rem; border-radius: 999px;
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18);
    padding: .28rem .8rem; font-size: .78rem; color: rgba(255,255,255,.95); font-weight: 500;
}
.rsd-btn {
    display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border-radius: 10px;
    padding: .6rem 1.1rem; font-size: .85rem; font-weight: 600; text-decoration: none; border: 1px solid transparent;
    min-height: 44px; transition: background .2s ease, color .2s ease, border-color .2s ease; cursor: pointer;
}
.rsd-btn--glass { background: rgba(255,255,255,.15); color: #fff; border-color: rgba(255,255,255,.22); }
.rsd-btn--glass:hover { background: rgba(255,255,255,.24); color: #fff; }
.rsd-btn--primary { background: #0453cb; color: #fff; }
.rsd-btn--primary:hover { background: #033a8e; color: #fff; }
.rsd-btn--outline { background: #fff; color: #0453cb; border-color: #c7d4e5; }
.rsd-btn--outline:hover { border-color: #0453cb; color: #033a8e; }
.rsd-btn--warn { background: #b45309; color: #fff; }
.rsd-btn--warn:hover { background: #92400e; color: #fff; }
.rsd-btn[disabled] { opacity: .55; cursor: not-allowed; }

.rsd-kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: .75rem; margin-top: 1.4rem; }
.rsd-kpi { background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.15); border-radius: 12px; padding: .85rem 1rem; }
.rsd-kpi-label { font-size: .72rem; color: rgba(255,255,255,.7); text-transform: uppercase; letter-spacing: .4px; }
.rsd-kpi-value { font-size: clamp(1.05rem, 2.2vw, 1.35rem); font-weight: 700; color: #fff; white-space: nowrap; margin-top: .2rem; }
.rsd-kpi-hint { font-size: .74rem; color: rgba(255,255,255,.7); margin-top: .1rem; }

.rsd-verdict {
    display: flex; gap: 1.1rem; align-items: flex-start; border-radius: 14px; padding: 1.35rem 1.5rem;
    background: #fff; border: 1px solid #e2e8f0; border-left-width: 5px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.rsd-verdict--ok { border-left-color: #10b981; }
.rsd-verdict--warn { border-left-color: #f59e0b; }
.rsd-verdict--ko { border-left-color: #dc2626; }
.rsd-verdict--info { border-left-color: #0453cb; }
.rsd-verdict-icon {
    width: 48px; height: 48px; border-radius: 12px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: #fff;
}
.rsd-verdict--ok .rsd-verdict-icon { background: #10b981; }
.rsd-verdict--warn .rsd-verdict-icon { background: #f59e0b; }
.rsd-verdict--ko .rsd-verdict-icon { background: #dc2626; }
.rsd-verdict--info .rsd-verdict-icon { background: #0453cb; }
.rsd-verdict-body { flex: 1; min-width: 0; }
.rsd-verdict h2 { font-size: 1.15rem; font-weight: 700; color: #0f172a; margin: 0 0 .3rem; }
.rsd-verdict p { color: #475569; margin: 0 0 .75rem; font-size: .92rem; }
.rsd-verdict ul { margin: 0 0 1rem; padding-left: 1.1rem; color: #334155; font-size: .88rem; }
.rsd-verdict li + li { margin-top: .2rem; }
.rsd-actions { display: flex; flex-wrap: wrap; gap: .6rem; }
.rsd-astuce {
    margin-top: .9rem; padding: .65rem .85rem; border-radius: 10px; background: #f8fafc; border: 1px dashed #c7d4e5;
    font-size: .82rem; color: #475569;
}
.rsd-nanan { margin-top: .6rem; }
.rsd-nanan[hidden], .rsd-astuce-question[hidden] { display: none; }
.rsd-nanan { display: flex; }
.rsd-astuce code { background: rgba(4,83,203,.08); color: #0453cb; padding: .05rem .35rem; border-radius: 5px; }

.rsd-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
.rsd-card {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 1.35rem 1.5rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
}
.rsd-card--full { grid-column: 1 / -1; }
.rsd-section-header { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
.rsd-section-icon {
    width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
    background: linear-gradient(135deg, #0453cb, #3b7ddb); color: #fff;
    display: flex; align-items: center; justify-content: center; font-size: .95rem;
}
.rsd-section-header h3 { font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
.rsd-section-header small { display: block; color: #64748b; font-size: .78rem; }

.rsd-lignes { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem 1rem; }
.rsd-ligne-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; color: #64748b; font-weight: 600; }
.rsd-ligne-valeur { font-size: .95rem; color: #1e293b; font-weight: 600; margin-top: .15rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.rsd-ok { color: #047857; }
.rsd-warn { color: #b45309; }
.rsd-ko { color: #b91c1c; }
.rsd-neutre { color: #1e293b; }

.rsd-jauge { margin-top: 1.1rem; }
.rsd-jauge-top { display: flex; justify-content: space-between; font-size: .82rem; color: #475569; font-weight: 600; margin-bottom: .35rem; }
.rsd-jauge-fond { height: 10px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
.rsd-jauge-rempli { height: 100%; border-radius: 999px; background: #0453cb; transition: width .3s ease; }
.rsd-jauge-rempli--ok { background: #10b981; }
.rsd-note { margin-top: .5rem; font-size: .8rem; color: #64748b; }

.rsd-badge {
    display: inline-flex; align-items: center; gap: .35rem; padding: .22rem .6rem; border-radius: 999px;
    font-size: .74rem; font-weight: 600;
}
.rsd-badge--ok { background: rgba(16,185,129,.12); color: #047857; }
.rsd-badge--warn { background: rgba(245,158,11,.14); color: #b45309; }
.rsd-badge--ko { background: rgba(220,38,38,.1); color: #b91c1c; }
.rsd-badge--neutre { background: rgba(4,83,203,.08); color: #0453cb; }

.rsd-regles { margin-top: 1.1rem; padding-top: 1rem; border-top: 1px solid #eef2f7; }
.rsd-regles-titre { font-size: .8rem; font-weight: 700; color: #334155; margin-bottom: .6rem; }
.rsd-regles-liste { display: flex; flex-wrap: wrap; gap: .45rem; }
.rsd-repli { margin-top: .7rem; font-size: .8rem; color: #475569; background: #f8fafc; border-radius: 10px; padding: .6rem .8rem; }
.rsd-repli a { color: #0453cb; font-weight: 600; }

.rsd-table { width: 100%; border-collapse: collapse; margin-top: .4rem; font-size: .88rem; }
.rsd-table th { text-align: left; font-size: .72rem; text-transform: uppercase; letter-spacing: .4px; color: #64748b; padding: .5rem .4rem; border-bottom: 1px solid #e2e8f0; }
.rsd-table td { padding: .55rem .4rem; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.rsd-table td:last-child, .rsd-table th:last-child { text-align: right; }

.rsd-erreurs { background: #fff; border: 1px solid #fecaca; border-left: 5px solid #dc2626; border-radius: 12px; padding: .9rem 1.1rem; color: #b91c1c; }
.rsd-erreurs ul { margin: 0; padding-left: 1.1rem; }
.rsd-succes { background: #fff; border: 1px solid #a7f3d0; border-left: 5px solid #10b981; border-radius: 12px; padding: .9rem 1.1rem; color: #047857; font-weight: 500; }

@media (max-width: 992px) {
    .rsd-grid { grid-template-columns: 1fr; }
}
@media (max-width: 576px) {
    .rsd-hero { padding: 1.25rem 1rem; border-radius: 14px; }
    .rsd-hero h1 { font-size: 1.2rem; }
    .rsd-hero-left { align-items: flex-start; }
    .rsd-avatar { width: 44px; min-width: 44px; height: 44px; border-radius: 12px; font-size: .95rem; }
    .rsd-hero-top > .rsd-btn { width: 100%; }
    .rsd-verdict { flex-direction: column; padding: 1.1rem 1rem; }
    .rsd-card { padding: 1.1rem 1rem; }
    .rsd-lignes { grid-template-columns: 1fr; }
    .rsd-actions .rsd-btn { width: 100%; }
}
</style>
@endpush

@section('content')
<div class="rsd">

    <header class="rsd-hero">
        <div class="rsd-hero-top">
            <div class="rsd-hero-left">
                <div class="rsd-avatar rsd-hero-icon">
                    @if($etudiant->photo_url)
                        <img src="{{ $etudiant->photo_url }}" alt="" data-initiales="{{ $initiales }}" onerror="this.parentNode.textContent = this.dataset.initiales">
                    @else
                        <span>{{ $initiales }}</span>
                    @endif
                </div>
                <div style="min-width:0">
                    <h1>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h1>
                    <p>Réinscription {{ $anneeCible ? 'pour ' . $anneeCible : '' }}</p>
                    <div class="rsd-pills">
                        <span class="rsd-pill"><i class="fas fa-id-card"></i>{{ $etudiant->matricule ?? 'Sans matricule' }}</span>
                        <span class="rsd-pill"><i class="fas fa-chalkboard"></i>{{ $inscription->classe->name ?? '—' }}</span>
                        <span class="rsd-pill"><i class="fas fa-calendar"></i>{{ $anneeQuittee }}@if($anneeCible) &rarr; {{ $anneeCible }}@endif</span>
                    </div>
                </div>
            </div>
            <a href="{{ route('esbtp.reinscription.index') }}?annee_academique={{ urlencode((string) $anneeAcademique) }}" class="rsd-btn rsd-btn--glass">
                <i class="fas fa-arrow-left"></i>Retour à la liste
            </a>
        </div>

        <div class="rsd-kpis">
            @if($voirFinances)
                <div class="rsd-kpi">
                    <div class="rsd-kpi-label">Dû sur {{ $anneeQuittee }}</div>
                    <div class="rsd-kpi-value">{{ $fcfa($du) }}</div>
                    <div class="rsd-kpi-hint">{{ $du > 0 ? 'frais souscrits' : 'aucun frais souscrit' }}</div>
                </div>
                <div class="rsd-kpi">
                    <div class="rsd-kpi-label">Payé</div>
                    <div class="rsd-kpi-value">{{ $fcfa($paye) }}</div>
                    <div class="rsd-kpi-hint">{{ number_format($pourcentPaye, 0) }} % du dû</div>
                </div>
                <div class="rsd-kpi">
                    <div class="rsd-kpi-label">{{ $solde < 0 ? 'Trop-perçu' : 'Reste à payer' }}</div>
                    <div class="rsd-kpi-value">{{ $fcfa(abs($solde)) }}</div>
                    <div class="rsd-kpi-hint">{{ $solde > 0 ? 'impayé' : ($solde < 0 ? 'à rembourser ou reporter' : 'soldé') }}</div>
                </div>
            @endif
            @php
                $libelleEtat = match (true) {
                    $etat === Eligibilite::DEJA_INSCRIT => 'Déjà inscrit',
                    $eligibilite['autorisee'] => 'Autorisée',
                    $eligibilite['peut_deroger'] => 'Par dérogation',
                    default => 'Bloquée',
                };
            @endphp
            <div class="rsd-kpi">
                <div class="rsd-kpi-label">Réinscription</div>
                <div class="rsd-kpi-value">{{ $libelleEtat }}</div>
                <div class="rsd-kpi-hint">{{ $anneeCible ? 'pour ' . $anneeCible : 'aucune année courante' }}</div>
            </div>
            <div class="rsd-kpi">
                <div class="rsd-kpi-label">Moyenne {{ $anneeQuittee }}</div>
                <div class="rsd-kpi-value">{{ $decisionFiable ? number_format($moyenne, 2, ',', ' ') . ' / 20' : '—' }}</div>
                <div class="rsd-kpi-hint">{{ $decisionFiable ? $decisionLabel : 'aucune note saisie' }}</div>
            </div>
        </div>
    </header>

    @if ($errors->any())
        <div class="rsd-erreurs" role="alert">
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    @if (session('success'))
        <div class="rsd-succes" role="status">{{ session('success') }}</div>
    @endif

    {{-- Le verdict d'abord : c'est la question que la personne se pose. --}}
    @if($etat === Eligibilite::DEJA_INSCRIT && $existante)
        @php
            $valide = $existante->reinscription_status === 'validated';
            $statutLibelle = match ($existante->reinscription_status) {
                'validated' => 'Validée',
                'pending' => 'En attente de validation',
                'draft' => 'Brouillon',
                default => $existante->status === 'active' && $existante->workflow_step === 'etudiant_cree' ? 'Inscription finalisée' : 'Dossier en cours',
            };
            $dateValidation = $valide && $existante->reinscription_validated_at
                ? \Illuminate\Support\Carbon::parse($existante->reinscription_validated_at)->format('d/m/Y à H:i') : null;
        @endphp
        <section class="rsd-verdict {{ $valide ? 'rsd-verdict--ok' : 'rsd-verdict--info' }}">
            <div class="rsd-verdict-icon"><i class="fas {{ $valide ? 'fa-check' : 'fa-circle-info' }}"></i></div>
            <div class="rsd-verdict-body">
                <h2>Déjà inscrit pour {{ $existante->anneeUniversitaire->name ?? $anneeCible }}</h2>
                <p>
                    {{ $statutLibelle }}{{ $dateValidation ? ', le ' . $dateValidation : '' }}{{ $valide && $existante->reinscriptionValidatedBy ? ' par ' . $existante->reinscriptionValidatedBy->name : '' }}.
                    Classe : <strong>{{ $existante->classe->name ?? 'non renseignée' }}</strong>
                    @if($existante->classe)({{ $existante->classe->filiere->name ?? '' }}{{ $existante->classe->niveau ? ' · ' . $existante->classe->niveau->name : '' }})@endif.
                </p>
                <ul>
                    <li>Statut d'affectation : {{ $existante->affectation_status ? ucfirst(str_replace(['_', '-'], ' ', $existante->affectation_status)) : 'non renseigné' }}</li>
                    @if($reliquatAnneeCible > 0)
                        <li class="rsd-warn">Reliquat reporté à régulariser{{ $voirFinances ? ' : ' . $fcfa($reliquatAnneeCible) : '' }}</li>
                    @endif
                    @if($existante->reinscription_observations)
                        <li>Observations : {{ $existante->reinscription_observations }}</li>
                    @endif
                </ul>
                <div class="rsd-actions">
                    <a href="{{ route('esbtp.inscriptions.show', $existante->id) }}" class="rsd-btn rsd-btn--primary">
                        <i class="fas fa-up-right-from-square"></i>Ouvrir l'inscription
                    </a>
                    <a href="{{ route('esbtp.inscriptions.index') }}" class="rsd-btn rsd-btn--outline">
                        <i class="fas fa-list"></i>Liste des inscriptions
                    </a>
                    @if($eligibilite['annee_suivante'])
                        <a href="{{ $lienVers($eligibilite['annee_suivante']) }}" class="rsd-btn rsd-btn--outline">
                            <i class="fas fa-forward"></i>Préparer {{ $eligibilite['annee_suivante']->name }}
                        </a>
                    @elseif($eligibilite['peut_rejouer'])
                        <a href="{{ $lienFinalisation }}" class="rsd-btn rsd-btn--outline" title="Refaire la réinscription : l'inscription actuelle sera terminée et remplacée">
                            <i class="fas fa-pen"></i>Corriger la réinscription
                        </a>
                    @endif
                </div>
            </div>
        </section>
    @elseif($eligibilite['autorisee'])
        <section class="rsd-verdict rsd-verdict--ok">
            <div class="rsd-verdict-icon"><i class="fas fa-check"></i></div>
            <div class="rsd-verdict-body">
                <h2>Réinscription autorisée</h2>
                <p>
                    @if($etat === Eligibilite::DANS_TOLERANCE)
                        Le reste dû ({{ $voirFinances ? $fcfa($solde) : 'faible' }}) est sous le seuil toléré par l'école{{ $voirFinances ? ' (' . $fcfa($tolerance) . ')' : '' }} : il sera reporté en reliquat sur {{ $anneeCible ?? "l'année suivante" }}.
                    @else
                        {{ $du > 0 ? 'Les frais de ' . $anneeQuittee . ' sont soldés.' : 'Rien n\'est dû sur ' . $anneeQuittee . '.' }} {{ $anneeCible ? 'Le dossier peut passer en ' . $anneeCible . '.' : '' }}
                    @endif
                </p>
                <ul>
                    <li>Décision académique : passage, rattrapage ou redoublement</li>
                    <li>Nouvelle classe et statut d'affectation</li>
                    <li>Frais de la nouvelle année</li>
                </ul>
                <div class="rsd-actions">
                    <a href="{{ $lienFinalisation }}" class="rsd-btn rsd-btn--primary">
                        <i class="fas fa-arrow-right"></i>Procéder à la finalisation
                    </a>
                </div>
            </div>
        </section>
    @elseif($eligibilite['peut_deroger'])
        <section class="rsd-verdict rsd-verdict--warn">
            <div class="rsd-verdict-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="rsd-verdict-body">
                <h2>Impayé : réinscription possible par dérogation</h2>
                <p>
                    Il reste {{ $voirFinances ? $fcfa($solde) : 'un montant' }} à payer sur {{ $anneeQuittee }}. Votre compte peut autoriser la réinscription :
                    ce reste sera reporté en reliquat sur {{ $anneeCible ?? "l'année suivante" }} et restera dû.
                </p>
                <div class="rsd-actions">
                    <a href="{{ $lienFinalisation }}" class="rsd-btn rsd-btn--warn">
                        <i class="fas fa-arrow-right"></i>Réinscrire avec reliquat
                    </a>
                    @can('paiements.create')
                        <a href="{{ route('esbtp.paiements.create', ['inscription_id' => $inscription->id, 'etudiant_id' => $etudiant->id]) }}" class="rsd-btn rsd-btn--outline">
                            <i class="fas fa-cash-register"></i>Encaisser un paiement
                        </a>
                    @endcan
                    <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="rsd-btn rsd-btn--outline">
                        <i class="fas fa-file-invoice"></i>Voir les frais
                    </a>
                </div>
            </div>
        </section>
    @else
        <section class="rsd-verdict rsd-verdict--ko">
            <div class="rsd-verdict-icon"><i class="fas fa-lock"></i></div>
            <div class="rsd-verdict-body">
                <h2>Réinscription bloquée</h2>
                <p>
                    Il reste {{ $voirFinances ? $fcfa($solde) : 'un montant' }} à payer sur {{ $anneeQuittee }}.
                    @if($tolerance > 0 && $voirFinances)
                        L'école tolère jusqu'à {{ $fcfa($tolerance) }} de reste dû.
                    @endif
                </p>
                <ul>
                    <li>Encaisser le reste dû, puis revenir sur cette page.</li>
                    <li>Ou demander à un superadministrateur d'autoriser le report en reliquat.</li>
                    <li>Si ce montant ne devrait pas exister (exonération, dette reprise à tort), le frais se corrige sur l'inscription.</li>
                </ul>
                <div class="rsd-actions">
                    @can('paiements.create')
                        <a href="{{ route('esbtp.paiements.create', ['inscription_id' => $inscription->id, 'etudiant_id' => $etudiant->id]) }}" class="rsd-btn rsd-btn--primary">
                            <i class="fas fa-cash-register"></i>Encaisser un paiement
                        </a>
                    @endcan
                    <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}" class="rsd-btn rsd-btn--outline">
                        <i class="fas fa-file-invoice"></i>Voir les frais de {{ $anneeQuittee }}
                    </a>
                </div>
                @php $questionNanan = 'Pourquoi la réinscription de ' . ($etudiant->matricule ?? ($etudiant->nom . ' ' . $etudiant->prenoms)) . ' est bloquée ?'; @endphp
                <div class="rsd-astuce">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    Nanan peut expliquer et corriger ce blocage.
                    <span class="rsd-astuce-question">Demandez-lui : <code>{{ $questionNanan }}</code></span>
                    <button type="button" class="rsd-btn rsd-btn--outline rsd-nanan" data-question="{{ $questionNanan }}" hidden>
                        <i class="fas fa-wand-magic-sparkles"></i>Demander à Nanan
                    </button>
                </div>
            </div>
        </section>
    @endif

    <div class="rsd-grid">
        @if($voirFinances)
            <section class="rsd-card">
                <div class="rsd-section-header">
                    <span class="rsd-section-icon"><i class="fas fa-wallet"></i></span>
                    <div><h3>Situation financière</h3><small>Inscription {{ $anneeQuittee }}</small></div>
                </div>
                <div class="rsd-lignes">
                    <div><div class="rsd-ligne-label">Total dû</div><div class="rsd-ligne-valeur">{{ $fcfa($du) }}</div></div>
                    <div><div class="rsd-ligne-label">Total payé</div><div class="rsd-ligne-valeur rsd-ok">{{ $fcfa($paye) }}</div></div>
                    <div>
                        <div class="rsd-ligne-label">{{ $solde < 0 ? 'Trop-perçu' : 'Reste à payer' }}</div>
                        <div class="rsd-ligne-valeur {{ $solde > $tolerance ? 'rsd-ko' : ($solde > 0 ? 'rsd-warn' : 'rsd-ok') }}">{{ $fcfa(abs($solde)) }}</div>
                    </div>
                    <div>
                        <div class="rsd-ligne-label">Seuil toléré par l'école</div>
                        <div class="rsd-ligne-valeur">{{ $tolerance > 0 ? $fcfa($tolerance) : 'Aucun (solde complet exigé)' }}</div>
                    </div>
                    @if($reliquatEntrant > 0)
                        <div>
                            <div class="rsd-ligne-label">Reliquat d'avant {{ $anneeQuittee }}</div>
                            <div class="rsd-ligne-valeur rsd-warn">{{ $fcfa($reliquatEntrant) }}</div>
                        </div>
                    @endif
                </div>
                <div class="rsd-jauge">
                    <div class="rsd-jauge-top"><span>Paiements</span><span>{{ number_format($pourcentPaye, 0) }} %</span></div>
                    <div class="rsd-jauge-fond" role="progressbar" aria-valuenow="{{ (int) $pourcentPaye }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="rsd-jauge-rempli {{ $solde <= $tolerance ? 'rsd-jauge-rempli--ok' : '' }}" style="width: {{ $pourcentPaye }}%"></div>
                    </div>
                    <div class="rsd-note">
                        @if($du <= 0)
                            Aucun frais souscrit sur cette inscription : rien n'est dû.
                        @elseif($solde <= 0)
                            Entièrement soldé{{ $solde < 0 ? ', avec un trop-perçu de ' . $fcfa(abs($solde)) : '' }}.
                        @else
                            Requis pour se réinscrire : {{ $tolerance > 0 ? 'un reste dû de ' . $fcfa($tolerance) . ' au plus' : 'le solde complet' }}.
                        @endif
                    </div>
                </div>
            </section>
        @endif

        <section class="rsd-card {{ $voirFinances ? '' : 'rsd-card--full' }}">
            <div class="rsd-section-header">
                <span class="rsd-section-icon"><i class="fas fa-chart-line"></i></span>
                <div><h3>Résultats {{ $anneeQuittee }}</h3><small>{{ $inscription->classe->name ?? '' }}{{ $inscription->classe?->filiere ? ' · ' . $inscription->classe->filiere->name : '' }}</small></div>
            </div>
            <div class="rsd-lignes">
                <div>
                    <div class="rsd-ligne-label">Moyenne générale</div>
                    <div class="rsd-ligne-valeur {{ $decisionFiable ? 'rsd-' . $tonMoyenne : '' }}">{{ $decisionFiable ? number_format($moyenne, 2, ',', ' ') . ' / 20' : 'Aucune note' }}</div>
                </div>
                <div>
                    <div class="rsd-ligne-label">Matières sous la moyenne de passage</div>
                    <div class="rsd-ligne-valeur">{{ count($analyse['matieres_echouees']) }}</div>
                </div>
                <div>
                    <div class="rsd-ligne-label">Décision proposée</div>
                    <div class="rsd-ligne-valeur">
                        <span class="rsd-badge rsd-badge--{{ $decisionFiable ? $decisionTon : 'neutre' }}"><i class="fas {{ $decisionIcone }}"></i>{{ $decisionLabel }}</span>
                    </div>
                </div>
                <div>
                    <div class="rsd-ligne-label">Fiabilité</div>
                    <div class="rsd-ligne-valeur {{ $decisionFiable ? 'rsd-ok' : 'rsd-warn' }}">
                        {{ $decisionFiable ? $notesComptees . ' note(s) prises en compte' : 'Indicative : aucune note' }}
                    </div>
                </div>
            </div>
            @unless($decisionFiable)
                <div class="rsd-repli">
                    Aucune note n'est saisie pour {{ $anneeQuittee }} : la décision affichée n'en tient pas compte. Elle se choisit à la finalisation.
                </div>
            @endunless

            <div class="rsd-regles">
                <div class="rsd-regles-titre">Règle appliquée</div>
                <div class="rsd-regles-liste">
                    <span class="rsd-badge rsd-badge--neutre">Passage à {{ rtrim(rtrim(number_format((float) $regle->moyenne_passage, 2, ',', ''), '0'), ',') }}</span>
                    <span class="rsd-badge rsd-badge--neutre">Rattrapage dès {{ rtrim(rtrim(number_format((float) $regle->moyenne_rattrapage, 2, ',', ''), '0'), ',') }}</span>
                    <span class="rsd-badge rsd-badge--neutre">{{ (int) $regle->max_matieres_rattrapage }} matière(s) en rattrapage au plus</span>
                    <span class="rsd-badge rsd-badge--neutre">Redoublement {{ $regle->autoriser_redoublement ? 'autorisé' : 'non autorisé' }}</span>
                </div>
                @if(! $regle->exists)
                    <div class="rsd-repli">
                        <i class="fas fa-circle-info"></i>
                        Aucune règle n'est configurée pour ce niveau et cette filière : ces seuils sont les valeurs par défaut.
                        @if(Route::has('esbtp.reinscription.regles.index'))
                            <a href="{{ route('esbtp.reinscription.regles.index') }}">Configurer les règles</a>
                        @endif
                    </div>
                @elseif($regle->conditions_speciales)
                    <div class="rsd-repli">{{ $regle->conditions_speciales }}</div>
                @endif
            </div>

            @if(count($analyse['matieres_echouees']) > 0)
                <table class="rsd-table" aria-label="Matières sous la moyenne de passage">
                    <thead><tr><th>Matière sous la moyenne de passage</th><th>Moyenne</th></tr></thead>
                    <tbody>
                        @foreach($analyse['matieres_echouees'] as $matiere)
                            <tr>
                                <td>{{ $matiere['matiere']->name ?? $matiere['matiere']->nom ?? '—' }}</td>
                                <td><span class="rsd-badge rsd-badge--ko">{{ number_format((float) $matiere['moyenne'], 2, ',', ' ') }} / 20</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</div>

<script>
// Le bouton n'apparaît que si Nanan est présent pour ce compte.
document.addEventListener('DOMContentLoaded', function () {
    if (!document.querySelector('.ast-root')) { return; }
    document.querySelectorAll('.rsd-nanan').forEach(function (bouton) {
        bouton.hidden = false;
        var question = bouton.parentNode.querySelector('.rsd-astuce-question');
        if (question) { question.hidden = true; }
        bouton.addEventListener('click', function () {
            window.dispatchEvent(new CustomEvent('nanan:demander', { detail: { question: bouton.dataset.question } }));
        });
    });
});
</script>
@endsection
