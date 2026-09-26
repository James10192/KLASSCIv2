{{-- Le dossier en trois cases, en tete du panneau : ou il en est, quand la
     famille est attendue, et si sa convocation lui est bien parvenue. --}}
@php
    /** @var \App\Domain\Admissions\DemandeDInscription $d */
    $_etape = $d->etapeDuDossier();
    [$_rdv, $_rdvDetail, $_rdvTon] = $d->rendezVousResume();
    $_conv = $d->convocation();
@endphp
<div class="dmi-resume">
    <div class="dmi-resume-case">
        <span>Étape</span>
        @if($_etape)
            <strong class="dmi-etape-badge dmi-etape-badge--{{ $_etape->ton() }}">{{ $_etape->libelle() }}</strong>
        @else
            <strong class="dmi-statut dmi-statut--{{ $d->statut }}">{{ $d->libelleStatut() }}</strong>
        @endif
    </div>
    <div class="dmi-resume-case dmi-rdv--{{ $_rdvTon }}">
        <span>Rendez-vous</span>
        <strong>{{ $_rdv }}</strong>
        @if($_rdvDetail !== '' && $_rdvTon !== 'fixe')<small>{{ $_rdvDetail }}</small>@endif
    </div>
    <div class="dmi-resume-case">
        <span>Convocation</span>
        <strong class="dmi-conv dmi-conv--{{ $_conv['ton'] }}">{{ $_conv['texte'] }}</strong>
        @if($_conv['detail'] !== '')<small>{{ $_conv['detail'] }}</small>@endif
    </div>
</div>
