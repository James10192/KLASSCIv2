{{-- Une ligne de la liste des dossiers. Rendue par la page et par chaque tranche du
     defilement (ESBTPDemandesInscriptionController::index). La ligne entiere
     ouvre le dossier au clic ; au clavier, c'est le nom, un vrai bouton (la ligne
     n'est pas un role=button : elle contient elle-meme un bouton). Le bouton de
     droite lance directement l'etape suivante. --}}
@php
    /** @var \App\Domain\Admissions\DemandeDInscription $d */
    [$_rdv, $_rdvDetail, $_rdvTon] = $d->rendezVousResume();
    $_etape = $d->etapeDuDossier();
    $_conv = $d->rendezVous ? $d->convocation() : null;
    $_peutInscrire = $d->estNouvelle()
        ? (auth()->user()?->can('inscriptions.candidatures.process') && auth()->user()?->can('inscriptions.ouvrir-formulaire'))
        : auth()->user()?->can('reinscriptions.demandes.process');
    [$_libelle, $_agir, $_primaire] = match (true) {
        $d->etape === \App\Domain\Admissions\DemandeDInscription::ETAPE_INSCRIRE && $_peutInscrire => ['Inscrire', 'inscrire', true],
        $d->etape === \App\Domain\Admissions\DemandeDInscription::ETAPE_REINSCRIRE && $_peutInscrire => ['Réinscrire', 'reinscrire', $d->recueAuGuichet() || $_rdvTon === 'aucun'],
        $d->etape === \App\Domain\Admissions\DemandeDInscription::ETAPE_VOIR => ['Voir', '', false],
        default => ['Examiner', '', false],
    };
@endphp
<div class="dmi-ligne {{ $d->recueAuGuichet() && $d->estOuverte() ? 'is-recue' : '' }}"
     data-li-cle="{{ $d->cle() }}" data-dmi-cle="{{ $d->cle() }}">
    <div class="dmi-qui">
        <span class="dmi-av" aria-hidden="true">{{ $d->initiales() }}</span>
        <div style="min-width:0">
            <button type="button" class="dmi-nom" aria-label="Ouvrir le dossier de {{ $d->nom }}">{{ $d->nom }}</button>
            <span class="dmi-sous">
                <span class="dmi-type dmi-type--{{ $d->type }}">{{ $d->estNouvelle() ? 'Nouvelle' : 'Réinscription' }}</span>
                <span class="dmi-parcours" title="{{ $d->parcours }}">{{ $d->parcours }}</span>
                {{-- Un ancien etudiant se reconnait a son matricule ; un nouveau, a la reference donnee a sa famille. --}}
                @if(! $d->estNouvelle() && $d->sousTitre !== '')<span class="dmi-mono" title="Matricule">{{ $d->sousTitre }}</span>
                @elseif($d->reference() !== '')<span class="dmi-mono" title="Référence donnée à la famille">{{ $d->reference() }}</span>
                @elseif($d->sousTitre !== '')<span>{{ $d->sousTitre }}</span>@endif
                @if($d->obstacle)<span class="dmi-contact" title="{{ $d->obstacle }}">Conversion impossible</span>@endif
            </span>
        </div>
    </div>
    <div class="dmi-col">
        @if($_etape)
            <span class="dmi-etape-badge dmi-etape-badge--{{ $_etape->ton() }}">{{ $_etape->libelle() }}</span>
        @else
            <span class="dmi-statut dmi-statut--{{ $d->statut }}">{{ $d->libelleStatut() }}</span>
        @endif
    </div>
    <div class="dmi-col dmi-rdv--{{ $_rdvTon }}">
        <strong>{{ $_rdv }}</strong>
        @if($_rdvDetail !== '' && $_rdvTon !== 'fixe')<small>{{ $_rdvDetail }}</small>@endif
    </div>
    <div class="dmi-col">
        @if($_conv)
            <strong class="dmi-conv dmi-conv--{{ $_conv['ton'] }}">{{ $_conv['texte'] }}</strong>
        @elseif($d->estOuverte() && $d->contactAVerifier())
            <strong class="dmi-conv dmi-conv--alerte">{{ $d->contactAVerifier() }}</strong>
        @else
            <small>{{ $d->estOuverte() ? 'Pas encore convoquée' : '—' }}</small>
        @endif
    </div>
    <div>
        <button type="button" class="dmi-btn dmi-btn--sm {{ $_primaire ? 'dmi-btn--primary' : 'dmi-btn--ghost' }}"
                @if($_agir !== '') data-dmi-agir="{{ $_agir }}" @endif>{{ $_libelle }}</button>
    </div>
</div>
