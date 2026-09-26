{{-- Le dossier ouvert a droite de la file. Rendu par ESBTPDemandesInscriptionController::dossier().
     Les boutons portent data-dmi-agir / data-dmi-poster : la page les branche par delegation. --}}
@php
    /** @var \App\Domain\Admissions\DemandeDInscription $d */
    $m = $d->modele;
    $u = auth()->user();
    $_nouvelle = $d->estNouvelle();
    $_traiter = $_nouvelle ? $u?->can('inscriptions.candidatures.process') : $u?->can('reinscriptions.demandes.process');
    $_inscrire = $_nouvelle && $_traiter && $u?->can('inscriptions.ouvrir-formulaire');
    $_rdv = $d->rendezVous;
    $_honoree = $d->recueAuGuichet();
    $_rdvDuJour = $_rdv && ! $_honoree && $_rdv->creneau && ($_rdv->creneau->date->isToday() || ($_rdv->creneau->date->isPast() && $d->estOuverte()));
    $_contact = $d->contactAVerifier();
    $_etudiant = $_nouvelle ? null : $m->etudiant;
    $_actions = [
        'cle' => $d->cle(),
        'type' => $d->type,
        'nom' => $d->nom,
        'statut' => $d->statut,
        'peut_traiter' => (bool) $_traiter,
        'ouverte' => $d->estOuverte(),
        'accepter' => $_nouvelle ? route('esbtp.candidatures.accepter', $m) : null,
        'rejeter' => $_nouvelle ? route('esbtp.candidatures.rejeter', $m) : route('esbtp.reinscription-demandes.rejeter', $m),
        'convertir' => $_nouvelle ? null : route('esbtp.reinscription-demandes.convertir', $m),
        'preparer' => $_inscrire ? route('esbtp.demandes.preparer-inscription', $m) : null,
        'formulaire' => $_inscrire ? route('esbtp.inscriptions.create', ['candidature' => $m->id]) : null,
        'classe' => $_nouvelle ? null : $m->classe_souhaitee_id,
        'obstacle' => $d->obstacle,
        'rendez_vous' => $u?->can('inscriptions.rdv.manage') ? route('esbtp.demandes.rendez-vous', [$d->type, $d->id]) : null,
    ];
    $_tel = $d->telephoneLisible();
@endphp
<div class="dmi-dossier" data-dmi-dossier='@json($_actions)'>
    <div class="dmi-p-tete">
        <div style="min-width:0">
            <span class="dmi-p-type">{{ $_nouvelle ? 'Nouvelle inscription' : 'Réinscription' }}</span>
            <h2 class="dmi-p-nom">{{ $d->nom }}</h2>
            <div class="dmi-p-sous">
                @if($_nouvelle && $m->date_naissance)Né{{ $m->sexe === 'F' ? 'e' : '' }} le {{ $m->date_naissance->format('d/m/Y') }}@if($m->lieu_naissance) à {{ $m->lieu_naissance }}@endif · @endif
                @if(! $_nouvelle && $_etudiant?->matricule)<span class="dmi-mono">{{ $_etudiant->matricule }}</span> · @endif
                @if($d->reference() !== '')<span class="dmi-mono" title="Référence donnée à la famille">{{ $d->reference() }}</span>@endif
            </div>
        </div>
        <button type="button" class="dmi-fermer" data-dmi-fermer aria-label="Fermer le dossier"><i class="fas fa-xmark"></i></button>
    </div>

    @include('esbtp.admissions.demandes._panneau-resume', ['d' => $d])

    <div class="dmi-infos">
        @if($_nouvelle)
            <div class="dmi-info"><span>Vœu</span><strong>{{ $d->parcours }}</strong></div>
            <div class="dmi-info"><span>{{ $m->est_transfert ? 'Transfert' : 'Baccalauréat' }}</span><strong>{{ $m->est_transfert ? ($m->etablissement_sup_origine ?: 'Établissement non précisé') : ($d->parcoursDetail !== '' ? $d->parcoursDetail : '—') }}</strong></div>
        @else
            <div class="dmi-info"><span>Classe souhaitée</span><strong>{{ $m->classeSouhaitee?->name ?? 'À choisir' }}</strong></div>
            <div class="dmi-info"><span>Année visée</span><strong>{{ $m->anneeUniversitaire?->name ?? '—' }}</strong></div>
        @endif
        <div class="dmi-info">
            <span>Contact</span>
            <strong>
                @if($_tel !== '')<a href="tel:{{ preg_replace('/[^\d+]/', '', $d->telephone) }}">{{ $_tel }}</a>@else — @endif
                @if(($m->verification_contact ?? null) === \App\Enums\StatutVerificationContact::Verifie->value)<span class="dmi-ok"> · vérifié</span>@endif
            </strong>
        </div>
        @if($_nouvelle)
            <div class="dmi-info"><span>Parent ou tuteur</span><strong>{{ $m->tuteur_nom ?: '—' }}@if($m->tuteur_lien) · {{ $m->tuteur_lien }}@endif</strong></div>
        @else
            <div class="dmi-info"><span>E-mail</span><strong>{{ $_etudiant?->email_personnel ?: ($_etudiant?->email ?: '—') }}</strong></div>
        @endif
        @if($_nouvelle && ($m->email || $m->tuteur_telephone))
            <div class="dmi-info dmi-info--large"><span>Autres contacts</span><strong>{{ collect([$m->email, $m->tuteur_telephone ? 'tuteur '.(\App\Domain\Notifications\PhoneFormatter::toReadable($m->tuteur_telephone) ?: $m->tuteur_telephone) : null])->filter()->join(' · ') }}</strong></div>
        @endif
    </div>

    @if($_nouvelle)
        @php
            // Tout ce que la famille a declare au depot, pour ne rien avoir a
            // chercher ailleurs. Seules les lignes renseignees s'affichent.
            $_depot = array_filter([
                'Sexe' => $m->sexe ? ($m->sexe === 'F' ? 'Féminin' : 'Masculin') : null,
                'Nationalité' => $m->nationalite,
                'Résidence' => collect([$m->commune, $m->ville])->filter()->join(', '),
                'Baccalauréat' => collect([$m->serie_bac ? 'série '.$m->serie_bac : null, $m->annee_bac, $m->etablissement_origine])->filter()->join(' · '),
                'Affectation déclarée' => $m->affectation_status ? (\App\Models\ESBTPCandidature::affectationsDeclarables()[$m->affectation_status] ?? $m->affectation_status) : null,
                'Formation d\'origine' => $m->est_transfert ? collect([$m->formation_origine, $m->niveau_atteint_origine])->filter()->join(' · ') : null,
                'Dernière inscription' => $m->est_transfert ? $m->annee_derniere_inscription : null,
                'Motif du transfert' => $m->est_transfert ? $m->motif_transfert : null,
                'Profession du tuteur' => $m->tuteur_profession,
            ], fn ($v) => filled($v));
        @endphp
        @if($_depot !== [])
            <details class="dmi-depot">
                <summary>Tout le dossier déposé</summary>
                <dl>
                    @foreach($_depot as $_libelle => $_valeur)
                        <dt>{{ $_libelle }}</dt><dd>{{ $_valeur }}</dd>
                    @endforeach
                </dl>
            </details>
        @endif
    @endif

    @if($_contact)
        <div class="dmi-encart dmi-encart--alerte">
            <i class="fas fa-user-clock" aria-hidden="true"></i>
            <div style="flex:1">
                <strong>{{ $_contact }}</strong>
                <div class="dmi-p-note">Pas de convocation automatique tant que la famille n'a pas confirmé son contact.</div>
                @if($_traiter)
                    <button type="button" class="dmi-btn dmi-btn--ghost dmi-btn--sm" style="margin-top:.5rem"
                            data-dmi-poster="{{ route($_nouvelle ? 'esbtp.candidatures.confirmer-contact' : 'esbtp.reinscription-demandes.confirmer-contact', $m) }}"
                            data-dmi-corps='@json(['empreinte' => $m->empreinteContact()])'>
                        <i class="fas fa-user-check"></i>J'ai joint la famille : confirmer le contact
                    </button>
                @endif
            </div>
        </div>
    @endif

    @if($d->obstacle)
        <div class="dmi-encart dmi-encart--alerte" role="status">
            <i class="fas fa-triangle-exclamation" aria-hidden="true"></i>
            <div><strong>Réinscription impossible</strong><div class="dmi-p-note">{{ $d->obstacle }}</div></div>
        </div>
    @endif

    @if($_nouvelle && $m->message)
        <div class="dmi-section-titre">Message de la famille</div>
        <div class="dmi-message">{{ $m->message }}</div>
    @endif

    <div class="dmi-section-titre">Parcours du dossier</div>
    <ol class="dmi-etapes">
        @foreach(\App\Domain\Admissions\ParcoursDuDossier::pour($d) as $_e)
            <li class="dmi-etape {{ $_e['fait'] ? 'is-fait' : '' }} {{ $_e['prochaine'] ? 'is-prochaine' : '' }} {{ $_e['ton'] === 'echec' ? 'is-echec' : '' }}">
                <span class="dmi-etape-rail"><span class="dmi-etape-point"></span><span class="dmi-etape-trait"></span></span>
                <span class="dmi-etape-corps"><strong>{{ $_e['titre'] }}</strong>@if($_e['detail'] !== '')<small>{{ $_e['detail'] }}</small>@endif</span>
            </li>
        @endforeach
    </ol>

    @if($d->estOuverte())
        @if($_rdvDuJour && $u?->can('inscriptions.rdv.accueil'))
            <div class="dmi-encart">
                <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                <div style="flex:1">
                    <strong>Rendez-vous {{ $_rdv->creneau->date->isToday() ? "aujourd'hui" : 'du '.$_rdv->creneau->date->format('d/m') }} à {{ $_rdv->creneau->heureDebutHi() }}</strong>
                    <div class="dmi-p-note">La famille est au guichet ? Cochez-la reçue avant de décider.</div>
                    <button type="button" class="dmi-btn dmi-btn--ghost dmi-btn--sm" style="margin-top:.5rem"
                            data-dmi-poster="{{ route('esbtp.rendez-vous.accueil.recu', $_rdv) }}" data-dmi-corps='@json(['creneau_id' => $_rdv->creneau_id])'>
                        <i class="fas fa-check"></i>Marquer reçue
                    </button>
                </div>
            </div>
        @elseif(! $_rdv && $_actions['rendez_vous'])
            <div class="dmi-encart">
                <i class="fas fa-calendar-plus" aria-hidden="true"></i>
                <div style="flex:1">
                    <strong>Aucun rendez-vous</strong>
                    <div class="dmi-p-note">Proposez un créneau : la convocation part à la famille.</div>
                    <button type="button" class="dmi-btn dmi-btn--ghost dmi-btn--sm" style="margin-top:.5rem" data-dmi-agir="rendez-vous">
                        <i class="fas fa-calendar-plus"></i>Proposer un créneau
                    </button>
                </div>
            </div>
        @endif
    @endif

    <div class="dmi-p-actions">
        @if($d->estOuverte() && $_traiter)
            <div class="dmi-p-actions-rangee">
                @if($_nouvelle && $_inscrire)
                    <button type="button" class="dmi-btn dmi-btn--primary" data-dmi-agir="inscrire"><i class="fas fa-user-plus"></i>{{ $d->estAcceptee() ? 'Inscrire' : 'Accepter et inscrire' }}</button>
                @elseif($_nouvelle && ! $d->estAcceptee())
                    <button type="button" class="dmi-btn dmi-btn--primary" data-dmi-agir="accepter"><i class="fas fa-check"></i>Accepter</button>
                @elseif(! $_nouvelle && ! $d->obstacle)
                    <button type="button" class="dmi-btn dmi-btn--primary" data-dmi-agir="reinscrire"><i class="fas fa-user-check"></i>Réinscrire</button>
                @endif
                <button type="button" class="dmi-btn dmi-btn--danger" data-dmi-agir="rejeter">Rejeter{{ $d->obstacle ? ' la demande' : '' }}</button>
            </div>
            <span class="dmi-p-note">Rejeter libère aussi son créneau de rendez-vous, s'il en a un.</span>
            @if(! $_inscrire && $d->estAcceptee())
                <span class="dmi-p-note">Acceptée : le service des inscriptions la reprend. Vous n'avez pas le droit d'inscrire vous-même.</span>
            @endif
        @elseif($d->estOuverte())
            <span class="dmi-p-note">Vous pouvez consulter ce dossier, mais pas le traiter.</span>
        @elseif($d->estInscrite() && ($m->inscription_id ?? null) && $u?->can('inscriptions.view'))
            <a class="dmi-btn dmi-btn--ghost" href="{{ route('esbtp.inscriptions.show', $m->inscription_id) }}"><i class="fas fa-id-card"></i>Ouvrir l'inscription</a>
        @elseif($d->estRejetee())
            <span class="dmi-p-note"><strong>Motif du rejet :</strong> {{ $m->motif_rejet }}</span>
        @endif
        @if($_etudiant && $u?->can('students.view'))
            <a class="dmi-lien" href="{{ route('esbtp.etudiants.show', $_etudiant) }}">Voir la fiche de l'étudiant</a>
        @endif
    </div>
</div>
