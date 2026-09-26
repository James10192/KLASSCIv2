{{-- Au guichet : les familles cochees reçues aujourd'hui dont l'inscription reste
     a finaliser, la plus recente en tete. Rendu par la page et par
     index(?fragment=1). Une famille inscrite en sort d'elle-meme. --}}
<div class="adj-g-tete">
    <span class="adj-g-sur">Au guichet</span>
    <strong>{{ count($auGuichet) }} famille{{ count($auGuichet) > 1 ? 's' : '' }} à finaliser</strong>
</div>
@if($auGuichet === [])
    <div class="adj-g-vide">
        <i class="fas fa-user-check" aria-hidden="true"></i>
        <p>Personne n'attend son inscription. Cochez une famille à son arrivée : elle apparaît ici jusqu'à ce que son inscription soit faite.</p>
    </div>
@else
    <ol class="adj-g-liste">
        @foreach($auGuichet as $f)
            @php
                $_r = $f->reservation;
                $_porteur = $_r->candidature ?? $_r->demande;
                $_verification = $_porteur?->verification_contact ?? null;
                $_contact = \App\Enums\StatutVerificationContact::badge($_verification);
                $_contactTexte = $_contact ?? ($_verification === \App\Enums\StatutVerificationContact::Verifie->value ? 'Confirmé' : 'Sans vérification');
            @endphp
            <li class="adj-g-famille">
                <strong>{{ $f->nom }}</strong>
                <span>{{ $f->estNouvelle() ? 'Nouvelle inscription' : 'Réinscription' }} · {{ $f->parcours }}</span>
                <dl>
                    <dt>Reçue</dt><dd>{{ $_r->accueilli_at?->format('H:i') ?? 'Heure non notée' }}@if($_r->accueilliPar) · {{ $_r->accueilliPar->name }}@endif</dd>
                    <dt>Rendez-vous</dt><dd>{{ $_r->creneau->heureDebutHi() }}</dd>
                    <dt>Contact</dt><dd class="{{ $_contact ? 'adj-g-alerte' : '' }}">{{ $_contactTexte }}</dd>
                </dl>
                @if($f->lienFinaliser)
                    <a class="adj-btn adj-btn--primary adj-btn--bloc" href="{{ $f->lienFinaliser }}">
                        <i class="fas fa-user-plus" aria-hidden="true"></i>{{ $f->estNouvelle() ? "Finaliser l'inscription" : 'Finaliser la réinscription' }}
                    </a>
                    <span class="adj-g-note">Le formulaire s'ouvre pré-rempli avec la demande en ligne : aucune ressaisie.</span>
                @elseif($f->lienDossier)
                    <a class="adj-btn adj-btn--ghost adj-btn--bloc" href="{{ $f->lienDossier }}"><i class="fas fa-folder-open" aria-hidden="true"></i>Ouvrir le dossier</a>
                    <span class="adj-g-note">Le service des inscriptions finalise ce dossier.</span>
                @endif
            </li>
        @endforeach
    </ol>
@endif
