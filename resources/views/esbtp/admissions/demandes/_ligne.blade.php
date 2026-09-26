{{-- Une ligne de la file. Rendue par la page et par chaque tranche du
     defilement (ESBTPDemandesInscriptionController::index). La ligne entiere
     ouvre le dossier au clic ; au clavier, c'est le nom, un vrai bouton (la ligne
     n'est pas un role=button : elle contient elle-meme un bouton). Le bouton de
     droite lance directement l'etape suivante. --}}
@php
    /** @var \App\Domain\Admissions\DemandeDInscription $d */
    [$_rdv, $_rdvDetail, $_rdvTon] = $d->rendezVousResume();
    $_peutInscrire = $d->estNouvelle()
        ? (auth()->user()?->can('inscriptions.candidatures.process') && auth()->user()?->can('inscriptions.ouvrir-formulaire'))
        : auth()->user()?->can('reinscriptions.demandes.process');
    [$_libelle, $_agir, $_primaire] = match (true) {
        $d->etape === \App\Domain\Admissions\DemandeDInscription::ETAPE_INSCRIRE && $_peutInscrire => ['Inscrire', 'inscrire', true],
        $d->etape === \App\Domain\Admissions\DemandeDInscription::ETAPE_REINSCRIRE && $_peutInscrire => ['Réinscrire', 'reinscrire', $d->recueAuGuichet() || $_rdvTon === 'aucun'],
        $d->etape === \App\Domain\Admissions\DemandeDInscription::ETAPE_VOIR => ['Voir', '', false],
        default => ['Examiner', '', false],
    };
    $_contact = $d->estOuverte() ? $d->contactAVerifier() : null;
@endphp
<div class="dmi-ligne {{ $d->recueAuGuichet() && $d->estOuverte() ? 'is-recue' : '' }}"
     data-li-cle="{{ $d->cle() }}" data-dmi-cle="{{ $d->cle() }}">
    <div class="dmi-qui">
        <span class="dmi-av" aria-hidden="true">{{ $d->initiales() }}</span>
        <div style="min-width:0">
            <button type="button" class="dmi-nom" aria-label="Ouvrir le dossier de {{ $d->nom }}">{{ $d->nom }}</button>
            <span class="dmi-sous">
                <span class="dmi-type dmi-type--{{ $d->type }}">{{ $d->estNouvelle() ? 'Nouvelle' : 'Réinscription' }}</span>
                @if($d->sousTitre !== '')<span class="{{ $d->estNouvelle() ? '' : 'dmi-mono' }}">{{ $d->sousTitre }}</span>@endif
                @if(! $d->estOuverte())<span class="dmi-statut dmi-statut--{{ $d->statut }}">{{ $d->libelleStatut() }}</span>@endif
                @if($_contact)<span class="dmi-contact" title="{{ $_contact }}">Contact à vérifier</span>@endif
                @if($d->obstacle)<span class="dmi-contact" title="{{ $d->obstacle }}">Conversion impossible</span>@endif
            </span>
        </div>
    </div>
    <div class="dmi-col">
        <strong title="{{ $d->parcours }}">{{ $d->parcours }}</strong>
        @if($d->parcoursDetail !== '')<small>{{ $d->parcoursDetail }}</small>@endif
    </div>
    <div class="dmi-col dmi-rdv--{{ $_rdvTon }}">
        <strong>{{ $_rdv }}</strong>
        @if($_rdvDetail !== '')<small>{{ $_rdvDetail }}</small>@endif
    </div>
    <div>
        <button type="button" class="dmi-btn dmi-btn--sm {{ $_primaire ? 'dmi-btn--primary' : 'dmi-btn--ghost' }}"
                @if($_agir !== '') data-dmi-agir="{{ $_agir }}" @endif>{{ $_libelle }}</button>
    </div>
</div>
