{{-- La liste du jour, par creneau. Rendu aussi seul par index(?fragment=1). --}}
@php
    $_contacts = app(\App\Services\RendezVous\ContactsFamilleRdv::class);
    $_aVenir = $jour->isFuture() && ! $jour->isToday();
    $_Tel = \App\Enums\StatutConvocationRdv::Telephone;
    $_familles = app(\App\Services\RendezVous\FamillesAPrevenirRdv::class);
    $_voitCandidatures = auth()->user()?->can('inscriptions.candidatures.view') ?? false;
    $_voitDemandes = auth()->user()?->can('reinscriptions.demandes.view') ?? false;
    $_nonVenuesMasse = $aReprogrammer;
    $_recidives = $_nonVenuesMasse->where('absences', '>', 0)->count();
@endphp

@if($enSouffrance->isNotEmpty())
    <div class="rac-alerte" role="status">
        <i class="fas fa-triangle-exclamation"></i>
        <div>
            <strong>Des familles non venues attendent encore d'être reprogrammées</strong>
            <div class="rac-alerte-jours">
                @foreach($enSouffrance->take(6) as $_j)
                    <a href="{{ route('esbtp.rendez-vous.accueil.index', ['jour' => $_j->jour]) }}" data-rac-jour="{{ $_j->jour }}">
                        {{ ucfirst(\Carbon\Carbon::parse($_j->jour)->translatedFormat('l j F')) }} · {{ $_j->n }}
                    </a>
                @endforeach
                @if($enSouffrance->count() > 6)<span>et {{ $enSouffrance->count() - 6 }} autre(s) jour(s)</span>@endif
            </div>
        </div>
    </div>
@endif

@if($creneaux->isEmpty() && $reprogrammees->isEmpty())
    <div class="rdv-card rdv-vide">
        <div class="rdv-vide-icone"><i class="fas fa-mug-hot"></i></div>
        <h3>Aucun rendez-vous ce jour</h3>
        <p>Personne n'est attendu au guichet le {{ $jour->translatedFormat('l j F') }}. Changez de jour, ou consultez le planning pour voir les prochains rendez-vous.</p>
        <a class="rdv-btn rdv-btn--primary" href="{{ route('esbtp.rendez-vous.index', ['debut' => $jour->toDateString()]) }}"><i class="fas fa-calendar-week"></i>Ouvrir le planning</a>
    </div>
@else
    @if($_aVenir)
        <p class="rac-info"><i class="fas fa-circle-info"></i>Journée à venir : la liste se coche le jour même. Vous pouvez déjà prévenir par téléphone les familles sans convocation, ou reprogrammer un rendez-vous.</p>
    @endif
    @if($_nonVenuesMasse->isNotEmpty())
        <section class="rdv-card rac-nonvenues">
            <div class="rdv-section-head" style="margin:0">
                <span class="rdv-section-icon"><i class="fas fa-user-clock"></i></span>
                <div>
                    <h2>{{ $_nonVenuesMasse->count() }} famille{{ $_nonVenuesMasse->count() > 1 ? 's' : '' }} non venue{{ $_nonVenuesMasse->count() > 1 ? 's' : '' }}</h2>
                    <p>Leur créneau est terminé sans qu'elles aient été reçues. Une famille arrivée en retard se coche encore.</p>
                </div>
            </div>
            <button type="button" class="rdv-btn rdv-btn--primary" data-rac-non-venues
                    data-confirm="Les {{ $_nonVenuesMasse->count() }} familles non venues seront placées sur les prochains créneaux libres, et leur nouvelle convocation partira par e-mail. Celles sans adresse rejoindront la liste des familles à prévenir.{{ $_recidives > 0 ? ' Attention : '.$_recidives.' d\'entre elles ont déjà manqué un rendez-vous — pensez à les appeler, ou à clore leur dossier.' : '' }}">
                <i class="fas fa-calendar-plus"></i>Reprogrammer les non-venues
            </button>
        </section>
    @endif
    @foreach($creneaux as $creneau)
        @php
            $_termine = $creneau->estTermine();
            $_commence = $creneau->aCommence();
            $_etatCreneau = $_termine ? 'termine' : ($_commence ? 'en-cours' : 'a-venir');
            $_resas = $creneau->reservations;
            $_recus = $_resas->where('statut', \App\Enums\StatutReservationRdv::Honoree)->count();
        @endphp
        <section class="rdv-card rac-creneau rac-creneau--{{ $_etatCreneau }}" data-rac-creneau>
            <header class="rac-creneau-tete">
                <h3><span class="rdv-heure">{{ $creneau->heureDebutHi() }} – {{ $creneau->heureFinHi() }}</span>
                    <span class="rac-puce rac-puce--{{ $_etatCreneau }}">{{ ['termine' => 'Terminé', 'en-cours' => 'En cours', 'a-venir' => 'À venir'][$_etatCreneau] }}</span>
                </h3>
                <span class="rac-creneau-compte"><strong>{{ $_recus }}</strong> / {{ $_resas->count() }} reçue{{ $_resas->count() > 1 ? 's' : '' }}</span>
            </header>
            <ul class="rac-lignes">
                @foreach($_resas as $resa)
                    @php
                        $_etat = $accueil->etat($resa);
                        $_ref = $_contacts->reference($resa);
                        $_second = $_contacts->second($resa);
                        $_tel = \App\Domain\Notifications\PhoneFormatter::toReadable($resa->telephone) ?? $resa->telephone;
                        $_retard = $accueil->enRetard($resa);
                        $_cherche = mb_strtolower($resa->nomComplet().' '.$resa->telephone.' '.str_replace('-', '', $_ref).' '.$_ref.' '.($_second['telephone'] ?? ''), 'UTF-8');
                        $_url = fn (string $action) => route('esbtp.rendez-vous.accueil.'.$action, $resa);
                        $_sansNouvelle = $_familles->concerne($resa);
                    @endphp
                    <li class="rac-ligne rac-ligne--{{ $_etat }}" data-statut="{{ $_etat }}" data-cherche="{{ $_cherche }}" data-creneau="{{ $creneau->id }}">
                        @if($_etat === 'recu')
                            <button type="button" class="rac-coche is-cochee" data-rac-action="{{ $_url('annuler') }}" aria-label="Annuler : {{ $resa->nomComplet() }} n'est pas encore reçue" title="Reçue — cliquer pour annuler"><i class="fas fa-check"></i></button>
                        @elseif(! $_aVenir)
                            <button type="button" class="rac-coche {{ $_etat === 'non_venue' ? 'rac-coche--non-venue' : '' }}" data-rac-action="{{ $_url('recu') }}" aria-label="Marquer {{ $resa->nomComplet() }} reçue" title="Marquer reçue"><i class="fas fa-check"></i></button>
                        @else
                            <span class="rac-coche rac-coche--attendu" aria-hidden="true"><i class="fas fa-clock"></i></span>
                        @endif

                        <div class="rac-qui">
                            <div class="rac-nom">
                                <strong>{{ $resa->nomComplet() }}</strong>
                                @if($_retard)<span class="rac-puce rac-puce--retard">En retard</span>@endif
                                @if($resa->absences > 0)
                                    <span class="rac-puce rac-puce--reprog">Reprogrammée · {{ $resa->absences }} absence{{ $resa->absences > 1 ? 's' : '' }}</span>
                                @endif
                            </div>
                            <x-demande-contact-badge :demande="$resa->porteur()" />
                            <div class="rac-contacts">
                                @if($_ref !== '')<span class="rac-ref">{{ $_ref }}</span>@endif
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) $resa->telephone) }}"><i class="fas fa-phone"></i>{{ $_tel }}</a>
                                @if($_second)
                                    <a href="tel:{{ preg_replace('/[^\d+]/', '', $_second['telephone']) }}" title="{{ $_second['nom'] }}"><i class="fas fa-user-shield"></i>{{ \App\Domain\Notifications\PhoneFormatter::toReadable($_second['telephone']) ?? $_second['telephone'] }}</a>
                                @endif
                            </div>
                        </div>

                        <div class="rac-statut">
                            @if($_etat === 'recu')
                                <span class="rdv-badge rdv-badge--succes">Reçue{{ $resa->accueilli_at ? ' à '.$resa->accueilli_at->format('H:i') : '' }}</span>
                                @if($resa->accueilliPar)<small>par {{ $resa->accueilliPar->name }}</small>@endif
                            @elseif($_etat === 'non_venue')
                                <span class="rdv-badge rdv-badge--echec">Non venue</span>
                            @elseif($_etat === 'traite')
                                <span class="rdv-badge rdv-badge--neutre" title="Jamais cochée, mais son dossier a avancé">Dossier traité</span>
                            @else
                                <span class="rdv-badge {{ $_retard ? 'rdv-badge--attente' : 'rdv-badge--inconnu' }}">À recevoir</span>
                                @if($resa->convocation_statut === $_Tel)
                                    <small>prévenue par tél.{{ $resa->prevenuePar ? ' ('.$resa->prevenuePar->name.')' : '' }}</small>
                                @elseif($_sansNouvelle)
                                    <small class="rac-sans-nouvelle">aucune convocation reçue</small>
                                @endif
                            @endif
                        </div>

                        <div class="rac-actions">
                            @if($_sansNouvelle)
                                <button type="button" class="rdv-btn rdv-btn--ghost rdv-btn--sm" data-rac-action="{{ $_url('prevenue') }}" title="Vous l'avez appelée : elle sort de la liste des familles à prévenir"><i class="fas fa-phone-volume"></i>Prévenue</button>
                            @endif
                            {{-- Sur chaque ligne, pas seulement sur les non-venues : l'agent qui
                                 vient de recevoir une famille a besoin de son dossier pour la
                                 suite (accepter, inscrire, reinscrire), pas seulement pour clore. --}}
                            @if($_ref !== '' && ($resa->candidature ? $_voitCandidatures : $_voitDemandes))
                                <a class="rdv-btn rdv-btn--ghost rdv-btn--sm" title="Ouvrir le dossier de cette famille"
                                   href="{{ $resa->candidature ? route('esbtp.candidatures.index', ['reference' => $_ref]) : route('esbtp.reinscription-demandes.index', ['reference' => $_ref]) }}"><i class="fas fa-folder-open"></i>Dossier</a>
                            @endif
                            @if(in_array($_etat, ['attendu', 'non_venue'], true))
                                <button type="button" class="rdv-btn {{ $_etat === 'non_venue' ? 'rdv-btn--primary' : 'rdv-btn--ghost' }} rdv-btn--sm"
                                        data-rac-reprogrammer="{{ $_url('reprogrammer') }}" data-rac-nom="{{ $resa->nomComplet() }}"><i class="fas fa-calendar-plus"></i>Reprogrammer</button>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach

    @if($reprogrammees->isNotEmpty())
        <section class="rdv-card rac-creneau rac-reprogrammees" data-rac-creneau>
            <header class="rac-creneau-tete">
                <h3><i class="fas fa-calendar-plus"></i>Non venues ce jour, déjà reprogrammées</h3>
                <span class="rac-creneau-compte"><strong>{{ $reprogrammees->count() }}</strong> famille{{ $reprogrammees->count() > 1 ? 's' : '' }}</span>
            </header>
            <ul class="rac-lignes">
                @foreach($reprogrammees as $resa)
                    @php
                        $_ref = $_contacts->reference($resa);
                        $_tel = \App\Domain\Notifications\PhoneFormatter::toReadable($resa->telephone) ?? $resa->telephone;
                        $_cherche = mb_strtolower($resa->nomComplet().' '.$resa->telephone.' '.str_replace('-', '', $_ref).' '.$_ref, 'UTF-8');
                    @endphp
                    <li class="rac-ligne rac-ligne--reprog" data-statut="non_venue" data-cherche="{{ $_cherche }}">
                        <span class="rac-coche rac-coche--non-venue" aria-hidden="true"><i class="fas fa-share"></i></span>
                        <div class="rac-qui">
                            <div class="rac-nom"><strong>{{ $resa->nomComplet() }}</strong></div>
                            <div class="rac-contacts">
                                @if($_ref !== '')<span class="rac-ref">{{ $_ref }}</span>@endif
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', (string) $resa->telephone) }}"><i class="fas fa-phone"></i>{{ $_tel }}</a>
                            </div>
                        </div>
                        <div class="rac-statut">
                            <span class="rdv-badge rdv-badge--inconnu">Nouveau rendez-vous</span>
                            <small>{{ ucfirst($resa->creneau->date->translatedFormat('l j F')) }} · {{ $resa->creneau->heureDebutHi() }}</small>
                        </div>
                        <div class="rac-actions">
                            <a class="rdv-btn rdv-btn--ghost rdv-btn--sm" href="{{ route('esbtp.rendez-vous.accueil.index', ['jour' => $resa->creneau->date->toDateString()]) }}" data-rac-jour="{{ $resa->creneau->date->toDateString() }}"><i class="fas fa-arrow-right"></i>Voir ce jour</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
    <p class="rac-aucun" hidden><i class="fas fa-magnifying-glass"></i>Aucune famille ne correspond à la recherche.</p>
@endif
