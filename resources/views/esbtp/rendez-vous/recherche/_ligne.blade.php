{{-- Un rendez-vous retrouve. Rendu par la page et par chaque tranche du
     defilement (ESBTPRendezVousRechercheController). --}}
@php
    $_creneau = $resa->creneau;
    $_porteur = $resa->porteur();
    $_ref = (string) ($_porteur?->referencePubliqueAffichee() ?? '');
    $_estCandidature = $resa->candidature_id !== null;
    $_matricule = $resa->demande?->etudiant?->matricule;
    $_tel = \App\Domain\Notifications\PhoneFormatter::toReadable($resa->telephone) ?? $resa->telephone;
    $_occupe = $resa->statut?->occupeLeCreneau() ?? false;
    $_etat = ($_occupe && $_creneau) ? $accueil->etat($resa) : null;
    [$_badge, $_libelle] = match (true) {
        $_etat === \App\Services\RendezVous\AccueilRdv::RECUE => ['succes', 'Reçue'.($resa->accueilli_at ? ' le '.$resa->accueilli_at->format('d/m à H:i') : '')],
        $_etat === \App\Services\RendezVous\AccueilRdv::NON_VENUE => ['echec', 'Non venue'],
        $_etat === \App\Services\RendezVous\AccueilRdv::TRAITEE => ['neutre', 'Dossier traité'],
        $_etat === \App\Services\RendezVous\AccueilRdv::ATTENDUE => ['inconnu', 'À recevoir'],
        default => ['neutre', $resa->statut?->label() ?? '—'],
    };
    $_jour = $_creneau?->date;
    $_voitAccueil = auth()->user()?->can('inscriptions.rdv.accueil') ?? false;
    $_voitPlanning = auth()->user()?->can('inscriptions.rdv.view') ?? false;
    $_voitDossier = $_ref !== '' && (auth()->user()?->can($_estCandidature ? 'inscriptions.candidatures.view' : 'reinscriptions.demandes.view') ?? false);
@endphp
<li class="rdr-ligne {{ $_occupe ? '' : 'rdr-ligne--hors' }}" data-li-cle="{{ $resa->id }}">
    <div class="rdr-quand" @if($_jour) title="{{ ucfirst($_jour->translatedFormat('l j F Y')) }}" @endif>
        @if($_jour)
            <span class="rdr-quand-jour">{{ $_jour->format('j') }}</span>
            <span class="rdr-quand-mois">{{ $_jour->translatedFormat('M') }}</span>
            <span class="rdr-quand-heure">{{ $_creneau->heureDebutHi() }}</span>
        @else
            <span class="rdr-quand-mois">—</span>
        @endif
    </div>

    <div class="rdr-qui">
        <div class="rdr-nom">
            <strong>{{ $resa->nomComplet() }}</strong>
            <span class="rdr-type">{{ $_estCandidature ? 'Nouvelle inscription' : 'Réinscription' }}</span>
        </div>
        <div class="rdr-details">
            @if($_jour)<span><i class="far fa-calendar"></i>{{ ucfirst($_jour->translatedFormat('l j F Y')) }} · {{ $_creneau->heureDebutHi() }} – {{ $_creneau->heureFinHi() }}</span>@endif
            @if($_ref !== '')<span class="rdr-ref">{{ $_ref }}</span>@endif
            @if($_matricule)<span><i class="fas fa-id-card"></i>{{ $_matricule }}</span>@endif
            @if($resa->telephone)<a href="tel:{{ preg_replace('/[^\d+]/', '', (string) $resa->telephone) }}"><i class="fas fa-phone"></i>{{ $_tel }}</a>@endif
        </div>
    </div>

    <div class="rdr-statut">
        <span class="rdv-badge rdv-badge--{{ $_badge }}">{{ $_libelle }}</span>
        @if($resa->absences > 0)<small>{{ $resa->absences }} rendez-vous manqué{{ $resa->absences > 1 ? 's' : '' }}</small>@endif
    </div>

    <div class="rdr-actions">
        @if($_voitDossier)
            <a class="rdv-btn rdv-btn--ghost rdv-btn--sm"
               href="{{ $_estCandidature ? route('esbtp.candidatures.index', ['reference' => $_ref]) : route('esbtp.reinscription-demandes.index', ['reference' => $_ref]) }}"><i class="fas fa-folder-open"></i>Dossier</a>
        @endif
        @if($_jour && $_voitAccueil)
            <a class="rdv-btn rdv-btn--primary rdv-btn--sm" href="{{ route('esbtp.rendez-vous.accueil.index', ['jour' => $_jour->toDateString()]) }}"><i class="fas fa-clipboard-check"></i>Voir le jour</a>
        @elseif($_jour && $_voitPlanning)
            <a class="rdv-btn rdv-btn--ghost rdv-btn--sm" href="{{ route('esbtp.rendez-vous.index', ['debut' => $_jour->toDateString()]) }}"><i class="fas fa-calendar-week"></i>Planning</a>
        @endif
    </div>
</li>
