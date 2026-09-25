{{-- Une ligne du suivi des pieces : rendue par la page et par la suite chargee au defilement. --}}
@php
    $inscription = $ligne['inscription'];
    $synthese = $ligne['synthese'];
    $etudiant = $inscription->etudiant;
@endphp
<tr data-li-cle="{{ $inscription->id }}">
    <td>
        <div class="sp-eleve">
            @if($etudiant?->photo_url)
                <img src="{{ $etudiant->photo_url }}" alt="" class="sp-avatar">
            @else
                <span class="sp-avatar">{{ mb_strtoupper(mb_substr($etudiant->prenoms ?? 'E', 0, 1)) }}{{ mb_strtoupper(mb_substr($etudiant->nom ?? '', 0, 1)) }}</span>
            @endif
            <div>
                <div class="sp-nom">{{ $etudiant->nom ?? '' }} {{ $etudiant->prenoms ?? '' }}</div>
                <div class="sp-matricule">{{ $etudiant->matricule ?? '' }}</div>
            </div>
        </div>
    </td>
    <td>{{ $inscription->classe->name ?? '—' }}</td>
    <td>
        @if($synthese['complet'])
            <span class="sp-etat sp-etat--ok">Complet</span>
        @else
            <span class="sp-etat sp-etat--manque">
                {{ $synthese['manquantes'] }} {{ $synthese['manquantes'] > 1 ? 'pièces' : 'pièce' }}
            </span>
        @endif
        @if($synthese['a_relire'] > 0)
            <span class="sp-etat sp-etat--relire">{{ $synthese['a_relire'] }} à relire</span>
        @endif
    </td>
    <td class="sp-manquantes">
        {{ $synthese['libelles_manquants'] ? implode(', ', $synthese['libelles_manquants']) : '—' }}
    </td>
    <td>
        {{-- Le lien mene la ou le geste se fait : la fiche d'inscription,
             pas un formulaire de plus. --}}
        <a href="{{ route('esbtp.inscriptions.show', $inscription->id) }}#dossier"
           class="sp-lien">Ouvrir le dossier &rarr;</a>
    </td>
</tr>
