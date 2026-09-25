{{-- Une ligne de la corbeille des candidatures. Rendue par la page et par
     chaque tranche du defilement (ESBTPCandidatureController::index). --}}
@php
    // Tout ce que le candidat a declare, rassemble pour le modal.
    $_dossier = [
        'nom' => $c->nomComplet(),
        'naissance' => trim(($c->date_naissance?->format('d/m/Y') ?: '')
            .($c->lieu_naissance ? ' à '.$c->lieu_naissance : '')),
        'sexe' => $c->sexe ? ($c->sexe === 'F' ? 'Féminin' : 'Masculin') : '',
        'nationalite' => (string) $c->nationalite,
        'telephone' => \App\Domain\Notifications\PhoneFormatter::toReadable($c->telephone) ?: (string) $c->telephone,
        'email' => (string) $c->email,
        'residence' => collect([$c->commune, $c->ville])->filter()->join(', '),
        'voeu' => $c->voeu() !== '' ? $c->voeu() : '',
        'annee' => (string) $c->anneeUniversitaire?->name,
        'serie_bac' => (string) $c->serie_bac,
        'etablissement_origine' => (string) $c->etablissement_origine,
        'annee_bac' => (string) $c->annee_bac,
        'affectation' => $c->affectation_status
            ? (\App\Models\ESBTPCandidature::affectationsDeclarables()[$c->affectation_status] ?? $c->affectation_status)
            : '',
        'etablissement_sup_origine' => (string) $c->etablissement_sup_origine,
        'formation_origine' => (string) $c->formation_origine,
        'niveau_atteint_origine' => (string) $c->niveau_atteint_origine,
        'annee_derniere_inscription' => (string) $c->annee_derniere_inscription,
        'motif_transfert' => (string) $c->motif_transfert,
        'tuteur_nom' => (string) $c->tuteur_nom,
        'tuteur_lien' => (string) $c->tuteur_lien,
        'tuteur_telephone' => \App\Domain\Notifications\PhoneFormatter::toReadable($c->tuteur_telephone) ?: (string) $c->tuteur_telephone,
        'tuteur_profession' => (string) $c->tuteur_profession,
        'message' => (string) $c->message,
        'recue_le' => (string) $c->created_at?->format('d/m/Y à H:i'),
        'statut' => (string) $c->statut,
        'motif_rejet' => (string) $c->motif_rejet,
        'traitable' => $c->estTraitable(),
        'url_accepter' => route('esbtp.candidatures.accepter', $c),
        'id' => $c->id,
    ];
@endphp
{{-- La ligne entiere ouvre le dossier : c'est le geste le plus
     rapide, et decider sur une ligne de tableau demandait sinon de
     deviner ce que le candidat avait declare. Le bouton « Voir »
     reste, pour qui cherche une cible explicite. --}}
<tr class="cd-ligne" data-li-cle="{{ $c->id }}" data-dossier='@json($_dossier)'>
    <td>
        <strong>{{ $c->nomComplet() }}</strong>
        <div class="cd-contact">
            {{ $c->date_naissance?->format('d/m/Y') }}@if($c->lieu_naissance) à {{ $c->lieu_naissance }}@endif
            @if($c->sexe) &middot; {{ $c->sexe === 'F' ? 'Féminin' : 'Masculin' }} @endif
            @if($c->nationalite) &middot; {{ $c->nationalite }} @endif
        </div>
    </td>
    <td>
        {{-- Stocké en E.164 (« +2250707121234 »), parce que c'est la clé
             d'unicité du canal public. C'est un agent qui va le composer :
             on le lui rend lisible. --}}
        <div>{{ \App\Domain\Notifications\PhoneFormatter::toReadable($c->telephone) ?: $c->telephone }}</div>
        @if($c->email)
            <div class="cd-contact">{{ $c->email }}</div>
        @endif
        @if($c->ville || $c->commune)
            <div class="cd-contact">{{ collect([$c->commune, $c->ville])->filter()->join(', ') }}</div>
        @endif
        @if($c->tuteur_nom || $c->tuteur_telephone)
            <div class="cd-tuteur">
                <i class="fas fa-user-shield"></i>
                {{ $c->tuteur_nom ?: 'Tuteur' }}@if($c->tuteur_lien) ({{ $c->tuteur_lien }})@endif
                @if($c->tuteur_telephone) &middot; {{ $c->tuteur_telephone }} @endif
            </div>
        @endif
    </td>
    <td>
        {{ $c->voeu() !== '' ? $c->voeu() : '—' }}
        <div class="cd-contact">{{ $c->anneeUniversitaire?->name }}</div>
    </td>
    <td class="cd-contact">
        @if($c->serie_bac) Série {{ $c->serie_bac }}<br> @endif
        @if($c->etablissement_origine) {{ $c->etablissement_origine }}<br> @endif
        @if($c->annee_bac) Bac {{ $c->annee_bac }}<br> @endif
        @if($c->affectation_status)
            <span class="cd-affect">{{-- Le libellé canonique, pas une reconstruction : `affectationsDeclarables()`
     existe pour que valeur et libellé voyagent ensemble. Écrit à la main, cette
     ligne rendait « Affecté » là où le portail public affiche « Affecté par
     l'État » — deux mots différents pour la même donnée, sur les deux écrans
     que la scolarité compare. --}}
{{ \App\Models\ESBTPCandidature::affectationsDeclarables()[$c->affectation_status] ?? $c->affectation_status }} <em>(déclaré)</em></span>
        @endif
        {{-- Le transfert se voit AVANT d'ouvrir le dossier : c'est ce qui
             change l'instruction du dossier, et l'agent trie sur cette
             colonne. Le detail complet reste dans la fiche. --}}
        @if($c->est_transfert)
            <span class="cd-transfert"><i class="fas fa-right-left"></i> Transfert</span>
            @if($c->etablissement_sup_origine)
                <div class="cd-transfert-de">{{ $c->etablissement_sup_origine }}</div>
            @endif
        @endif
        @if($c->parcoursEstVide()) — @endif
    </td>
    <td class="cd-contact">{{ $c->created_at?->format('d/m/Y H:i') }}</td>
    <td>
        @php
            $_libelles = [
                'en_attente' => ['attente', 'En attente'],
                'acceptee' => ['acceptee', 'Acceptée'],
                'rejetee' => ['rejetee', 'Rejetée'],
                'convertie' => ['convertie', 'Inscrite'],
            ];
            $_b = $_libelles[$c->statut] ?? ['attente', $c->statut];
        @endphp
        <span class="cd-badge cd-badge--{{ $_b[0] }}">{{ $_b[1] }}</span>
        <x-demande-contact-badge :demande="$c" route="esbtp.candidatures.confirmer-contact" permission="inscriptions.candidatures.process" />
        @if($c->motif_rejet)
            <div class="cd-contact" style="margin-top:.25rem;">{{ $c->motif_rejet }}</div>
        @endif
    </td>
    <td>
        @can('inscriptions.candidatures.process')
            @if($c->estTraitable())
                <div class="cd-actions">
                    <button type="button" class="btn-acasi secondary btn-sm cd-voir">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                    <form method="POST" action="{{ route('esbtp.candidatures.accepter', $c) }}">
                        @csrf
                        <button type="submit" class="btn-acasi primary btn-sm">
                            <i class="fas fa-check"></i> Accepter
                        </button>
                    </form>
                    <button type="button" class="btn-acasi secondary btn-sm"
                            data-rejet-id="{{ $c->id }}"
                            data-rejet-nom="{{ $c->nomComplet() }}">
                        <i class="fas fa-times"></i> Rejeter
                    </button>
                </div>
            @else
                <div class="cd-actions">
                    <button type="button" class="btn-acasi secondary btn-sm cd-voir">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                    {{-- La suite du parcours. Sans ce lien, la scolarité
                         retaperait à la main ce que le candidat a déjà
                         saisi. Ne pas compter les champs ici : le compte
                         a déjà vieilli une fois, et
                         PreRemplissageCandidature::valeurs() fait foi. --}}
                    @if($c->statut === \App\Models\ESBTPCandidature::STATUT_ACCEPTEE)
                        @can('inscriptions.ouvrir-formulaire')
                            <a href="{{ route('esbtp.inscriptions.create', ['candidature' => $c->id]) }}"
                               class="btn-acasi primary btn-sm">
                                <i class="fas fa-user-plus"></i> Créer l'inscription
                            </a>
                        @endcan
                    @endif
                    <div class="cd-contact" style="text-align:right;">
                        {{ $c->traitePar?->name ? 'par '.$c->traitePar->name : '' }}
                        {{ $c->traite_at?->format('d/m/Y') }}
                    </div>
                </div>
            @endif
        @endcan
    </td>
</tr>
