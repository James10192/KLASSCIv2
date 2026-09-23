{{-- Convocations, puis la semaine. Rendu aussi seul par index(?fragment=1). --}}
@php
    $_conv = $convocations;
    $_peutPrevenir = auth()->user()?->can('inscriptions.rdv.accueil') ?? false;
    $_familles = app(\App\Services\RendezVous\FamillesAPrevenirRdv::class);
@endphp

{{-- 1. Les convocations : ce qui est parti, ce qui attend, ce qui a echoue --}}
@if(array_sum($_conv) > 0)
    <section class="rdv-card" aria-labelledby="rdv-conv-titre">
        <div class="rdv-section-head">
            <div class="rdv-section-icon"><i class="fas fa-envelope-open-text"></i></div>
            <div>
                <h2 id="rdv-conv-titre">Convocations par e-mail</h2>
                <p>Chaque réservation garde la trace de son courriel : parti, en attente ou refusé, avec la raison.</p>
            </div>
        </div>
        <div class="rdv-conv-grille">
            <div class="rdv-conv rdv-conv--succes"><span>{{ $_conv['envoyee'] }}</span>envoyées</div>
            <div class="rdv-conv rdv-conv--attente"><span>{{ $_conv['en_attente'] }}</span>en attente</div>
            <div class="rdv-conv rdv-conv--echec"><span>{{ $_conv['echec'] }}</span>en échec</div>
            <div class="rdv-conv rdv-conv--neutre"><span>{{ $_conv['sans_email'] }}</span>sans e-mail</div>
            @if($_conv['telephone'] > 0)
                <div class="rdv-conv rdv-conv--succes"><span>{{ $_conv['telephone'] }}</span>prévenues par téléphone</div>
            @endif
            @if($_conv['sans_objet'] > 0)
                <div class="rdv-conv rdv-conv--neutre" title="Le créneau était passé au moment de l'envoi"><span>{{ $_conv['sans_objet'] }}</span>sans objet</div>
            @endif
            @if($_conv['inconnu'] > 0)
                <div class="rdv-conv rdv-conv--inconnu"><span>{{ $_conv['inconnu'] }}</span>non suivies</div>
            @endif
        </div>
        @if($aPrevenir > 0)
            <div class="rdv-a-prevenir">
                <i class="fas fa-phone-volume"></i>
                <p><strong>{{ $aPrevenir }} famille{{ $aPrevenir > 1 ? 's' : '' }} à prévenir par téléphone</strong> — rendez-vous à venir sans convocation reçue par e-mail (pas d'adresse, envoi refusé, ou réservation d'avant le suivi).</p>
                <a class="rdv-btn rdv-btn--ghost rdv-btn--sm" href="{{ route('esbtp.rendez-vous.familles.apercu') }}" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i>Liste d'appel</a>
                <a class="rdv-btn rdv-btn--ghost rdv-btn--sm" href="{{ route('esbtp.rendez-vous.familles.excel') }}"><i class="fas fa-file-excel"></i>Excel</a>
            </div>
        @endif
        @if($peutGerer)
            <div class="rdv-conv-actions">
                @if($_conv['en_attente'] > 0)
                    <button type="button" class="rdv-btn rdv-btn--primary" data-rdv-envoyer>
                        <i class="fas fa-paper-plane"></i>Envoyer les {{ $_conv['en_attente'] }} en attente
                    </button>
                @endif
                @if($_conv['echec'] > 0)
                    <button type="button" class="rdv-btn rdv-btn--ghost" data-rdv-remettre="echecs"
                            data-confirm="Renvoyer la convocation aux {{ $_conv['echec'] }} réservations en échec ?">
                        <i class="fas fa-rotate-right"></i>Relancer les {{ $_conv['echec'] }} échecs
                    </button>
                @endif
                @if($_conv['inconnu'] > 0)
                    <button type="button" class="rdv-btn rdv-btn--ghost" data-rdv-remettre="inconnues"
                            data-confirm="Ces {{ $_conv['inconnu'] }} réservations datent d'avant le suivi des envois : on ne sait pas si leur convocation est partie. Leur envoyer la convocation maintenant ? Une famille déjà convoquée la recevra une seconde fois.">
                        <i class="fas fa-envelope"></i>Convoquer les {{ $_conv['inconnu'] }} non suivies
                    </button>
                @endif
            </div>
            @if($_conv['inconnu'] > 0)
                <p class="rdv-note"><i class="fas fa-circle-info"></i>« Non suivies » : réservations créées avant l'activation du suivi des envois sur votre établissement. Rien ne leur est envoyé sans votre accord.</p>
            @endif
        @endif
    </section>
@endif

{{-- 2. La semaine --}}
<nav class="rdv-semaine" aria-label="Semaine affichée">
    <button type="button" class="rdv-nav-btn" data-rdv-semaine="{{ $semainePrecedente }}" aria-label="Semaine précédente"><i class="fas fa-chevron-left"></i></button>
    <div class="rdv-semaine-titre">
        <strong>Semaine du {{ $debut->translatedFormat('j F') }} au {{ $fin->translatedFormat('j F Y') }}</strong>
    </div>
    <button type="button" class="rdv-nav-btn" data-rdv-semaine="{{ $semaineSuivante }}" aria-label="Semaine suivante"><i class="fas fa-chevron-right"></i></button>
    <button type="button" class="rdv-btn rdv-btn--ghost rdv-btn--sm" data-rdv-semaine="{{ now()->toDateString() }}">Aujourd'hui</button>
</nav>

@if($semaineVide)
    <div class="rdv-card rdv-vide">
        <div class="rdv-vide-icone"><i class="fas fa-calendar-plus"></i></div>
        @if($prochainJour)
            <h3>Aucun créneau cette semaine</h3>
            <p>Le prochain jour de rendez-vous est le {{ $prochainJour->translatedFormat('l j F Y') }}.</p>
            <button type="button" class="rdv-btn rdv-btn--primary" data-rdv-semaine="{{ $prochainJour->toDateString() }}">
                <i class="fas fa-arrow-right"></i>Aller au {{ $prochainJour->translatedFormat('j F') }}
            </button>
        @elseif($debit)
            <h3>Aucun créneau n'a encore été créé</h3>
            <p>Les réglages sont prêts. Générez les créneaux : les familles pourront réserver dès que la prise de rendez-vous est ouverte.</p>
            @if($peutGerer)
                <button type="button" class="rdv-btn rdv-btn--primary" data-rdv-action="generer"><i class="fas fa-calendar-plus"></i>Générer les créneaux</button>
            @endif
        @else
            <h3>Commencez par les réglages</h3>
            <p>Indiquez les jours, les horaires du guichet et le nombre de familles par créneau. Les créneaux se créent ensuite en un clic.</p>
            @if($peutConfigurer)
                <button type="button" class="rdv-btn rdv-btn--primary" data-rdv-ouvrir-reglages><i class="fas fa-sliders"></i>Ouvrir les réglages</button>
            @endif
        @endif
    </div>
@else
    <div class="rdv-jours">
        @foreach($jours as $jour)
            @if($jour['creneaux']->isEmpty())
                <div class="rdv-jour-vide"><span>{{ $jour['libelle'] }}</span>Aucun créneau</div>
                @continue
            @endif
            @php
                $_taux = $jour['places'] > 0 ? (int) round(100 * $jour['prises'] / $jour['places']) : 0;
            @endphp
            <section class="rdv-card rdv-jour {{ $jour['aujourdhui'] ? 'rdv-jour--auj' : '' }}">
                <header class="rdv-jour-tete">
                    <h3>{{ $jour['libelle'] }} @if($jour['aujourdhui'])<span class="rdv-puce">Aujourd'hui</span>@endif</h3>
                    <span class="rdv-jour-resume">{{ $jour['creneaux']->count() }} créneaux · <strong>{{ $jour['prises'] }}</strong> / {{ $jour['places'] }} places prises ({{ $_taux }} %)</span>
                </header>
                <div class="rdv-slots">
                    @foreach($jour['creneaux'] as $creneau)
                        @php
                            $_resas = $creneau->reservations;
                            $_prises = $_resas->count();
                            $_pct = $creneau->capacite > 0 ? min(100, (int) round(100 * $_prises / $creneau->capacite)) : 0;
                            $_complet = $_prises >= $creneau->capacite;
                            $_etat = ! $creneau->ouvert ? 'ferme' : ($_complet ? 'complet' : 'ouvert');
                            $_libelles = ['ferme' => 'Fermé', 'complet' => 'Complet', 'ouvert' => 'Ouvert'];
                        @endphp
                        <div class="rdv-slot rdv-slot--{{ $_etat }}">
                            <div class="rdv-slot-ligne">
                                <span class="rdv-heure">{{ $creneau->heureDebutHi() }} – {{ $creneau->heureFinHi() }}</span>
                                <span class="rdv-jauge" role="meter" aria-valuemin="0" aria-valuemax="{{ $creneau->capacite }}" aria-valuenow="{{ $_prises }}" aria-label="Places prises">
                                    <span style="width: {{ $_pct }}%"></span>
                                </span>
                                <span class="rdv-slot-compte"><strong>{{ $_prises }}</strong> / {{ $creneau->capacite }}</span>
                                <span class="rdv-etat rdv-etat--{{ $_etat }}">{{ $_libelles[$_etat] }}</span>
                                @if($peutGerer && ! $jour['passe'])
                                    <button type="button" class="rdv-switch" role="switch"
                                            aria-checked="{{ $creneau->ouvert ? 'true' : 'false' }}"
                                            aria-label="{{ $creneau->ouvert ? 'Fermer' : 'Ouvrir' }} le créneau de {{ $creneau->heureDebutHi() }}"
                                            title="{{ $creneau->ouvert ? 'Visible des familles — cliquer pour fermer' : 'Invisible des familles — cliquer pour ouvrir' }}"
                                            data-rdv-basculer="{{ $creneau->ouvert ? route('esbtp.rendez-vous.fermer', $creneau) : route('esbtp.rendez-vous.ouvrir', $creneau) }}">
                                        <span class="rdv-switch-rond"></span>
                                    </button>
                                @endif
                            </div>
                            @if($_prises > 0)
                                <details class="rdv-resas">
                                    <summary><i class="fas fa-chevron-right"></i>{{ $_prises }} {{ $_prises > 1 ? 'familles attendues' : 'famille attendue' }}</summary>
                                    <ul>
                                        @foreach($_resas as $resa)
                                            @php $_c = $resa->convocation_statut; @endphp
                                            <li class="rdv-resa">
                                                <div class="rdv-resa-qui">
                                                    <strong>{{ $resa->nomComplet() }}</strong>
                                                    <span>{{ $resa->telephone }}@if($resa->email) · {{ $resa->email }}@endif</span>
                                                    @include('esbtp.partials._badge-verification-contact', ['statutVerification' => $resa->porteur()?->verification_contact])
                                                </div>
                                                <div class="rdv-resa-conv">
                                                    @if($_c)
                                                        <span class="rdv-badge rdv-badge--{{ $_c->ton() }}">{{ $_c->label() }}</span>
                                                        @if($_c === \App\Enums\StatutConvocationRdv::Envoyee && $resa->convocation_delivree_at)
                                                            <small>délivrée le {{ $resa->convocation_delivree_at->translatedFormat('j M à H:i') }}</small>
                                                        @elseif($_c === \App\Enums\StatutConvocationRdv::Envoyee && $resa->convocation_envoyee_at)
                                                            <small title="MailPulse a accepté le courriel ; sa remise dans la boîte de la famille n'est pas encore confirmée.">acceptée le {{ $resa->convocation_envoyee_at->translatedFormat('j M à H:i') }}, remise non confirmée</small>
                                                        @elseif($_c === \App\Enums\StatutConvocationRdv::Telephone)
                                                            <small>{{ $resa->prevenuePar ? 'par '.$resa->prevenuePar->name.' ' : '' }}{{ $resa->convocation_envoyee_at ? 'le '.$resa->convocation_envoyee_at->translatedFormat('j M à H:i') : '' }}</small>
                                                            @if($_peutPrevenir && $_familles->annulable($resa))
                                                                <button type="button" class="rdv-lien" data-rdv-basculer="{{ route('esbtp.rendez-vous.accueil.prevenue.annuler', $resa) }}">Annuler</button>
                                                            @endif
                                                        @elseif($resa->convocation_erreur)
                                                            <small class="rdv-resa-erreur">{{ $resa->convocation_erreur }}</small>
                                                        @endif
                                                    @else
                                                        <span class="rdv-badge rdv-badge--inconnu" title="Réservation antérieure au suivi des envois">Non suivie</span>
                                                    @endif
                                                    @if($_peutPrevenir && $_familles->concerne($resa))
                                                        <button type="button" class="rdv-btn rdv-btn--ghost rdv-btn--sm" data-rdv-basculer="{{ route('esbtp.rendez-vous.accueil.prevenue', $resa) }}" title="Vous l'avez appelée : elle sort de la liste des familles à prévenir"><i class="fas fa-phone-volume"></i>Prévenue</button>
                                                    @endif
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endif
