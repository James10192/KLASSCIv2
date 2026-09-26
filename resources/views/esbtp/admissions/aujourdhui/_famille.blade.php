{{-- Une famille du jour. La case de gauche coche « reçue » en un clic (route de
     l'Accueil du jour, avec le creneau affiche pour refuser une ligne deplacee
     entre-temps) ; un second clic annule la coche. Le bouton de droite est le
     seul geste utile a cet instant : finaliser, appeler, reprogrammer, ouvrir. --}}
@php
    /** @var \App\Domain\Admissions\FamilleDuJour $f */
    $_r = $f->reservation;
    [$_badge, $_ton] = $f->badge();
    $_conv = \App\Domain\Admissions\EtatConvocation::pour($_r);
    $_tel = preg_replace('/[^\d+]/', '', $f->telephone());
    $_action = match (true) {
        $f->estRecue() && $f->lienFinaliser !== null => ['href' => $f->lienFinaliser, 'texte' => $f->estNouvelle() ? 'Finaliser' : 'Réinscrire', 'icone' => 'fa-user-plus', 'primaire' => true],
        $f->etat === \App\Domain\Admissions\FamilleDuJour::EN_RETARD && $_tel !== '' => ['href' => 'tel:'.$_tel, 'texte' => 'Appeler', 'icone' => 'fa-phone', 'primaire' => false],
        $f->etat === \App\Services\RendezVous\AccueilRdv::NON_VENUE => ['href' => route('esbtp.rendez-vous.accueil.index'), 'texte' => 'Reprogrammer', 'icone' => 'fa-calendar-plus', 'primaire' => false],
        $f->lienDossier !== null => ['href' => $f->lienDossier, 'texte' => 'Dossier', 'icone' => 'fa-folder-open', 'primaire' => false],
        default => null,
    };
@endphp
<li class="adj-famille {{ $f->estRecue() ? 'is-recue' : '' }}" data-adj-famille="{{ $_r->id }}" data-adj-recherche="{{ $f->indexRecherche() }}">
    @if($f->peutEtreCochee())
        <button type="button" class="adj-coche" aria-pressed="{{ $f->estRecue() ? 'true' : 'false' }}"
                aria-label="{{ $f->estRecue() ? 'Annuler la coche de' : 'Marquer reçue' }} {{ $f->nom }}"
                title="{{ $f->estRecue() ? 'Reçue : cliquer pour annuler la coche' : 'Marquer reçue au guichet' }}"
                data-adj-cocher="{{ $f->estRecue() ? route('esbtp.rendez-vous.accueil.annuler', $_r) : route('esbtp.rendez-vous.accueil.recu', $_r) }}"
                data-adj-creneau-id="{{ $_r->creneau_id }}">
            <i class="fas fa-check" aria-hidden="true"></i>
        </button>
    @else
        <span class="adj-coche is-fige" aria-hidden="true"><i class="fas fa-check"></i></span>
    @endif
    <div class="adj-qui">
        <strong>{{ $f->nom }}</strong>
        <span>{{ $f->estNouvelle() ? 'Nouvelle inscription' : 'Réinscription' }} · {{ $f->parcours }}</span>
        <small>
            @if($f->telephoneLisible() !== '')<span class="adj-mono">{{ $f->telephoneLisible() }}</span> · @endif
            <span class="adj-conv adj-conv--{{ $_conv['ton'] }}">{{ $_conv['texte'] }}</span>
            @if($f->estRecue() && $_r->accueilliPar) · reçue par {{ $_r->accueilliPar->name }}@endif
        </small>
    </div>
    <span class="adj-badge adj-badge--{{ $_ton }}">{{ $_badge }}</span>
    @if($_action)
        <a class="adj-btn adj-btn--sm {{ $_action['primaire'] ? 'adj-btn--primary' : 'adj-btn--ghost' }}" href="{{ $_action['href'] }}">
            <i class="fas {{ $_action['icone'] }}" aria-hidden="true"></i>{{ $_action['texte'] }}
        </a>
    @endif
</li>
