{{--
    Les quatre cellules chiffrees d'une ligne de matiere : moyenne, coefficient,
    moyenne ponderee, rang.

    Une matiere dispensee ou non notee n'a ni moyenne ni rang : elle porte le
    symbole de trou, valide pour le bulletin. Son coefficient reste affiche —
    c'est celui de la maquette, il renseigne le lecteur — mais il n'entre dans
    aucun total, les moyennes etant calculees en amont par le service.

    Partage par les deux gabarits (standard et Abidjan) pour que les deux disent
    la meme chose : c'est precisement leur divergence qui a produit des
    bulletins differents pour une meme classe.

    @param object $resultat            ligne vivante ou modele ESBTPResultatMatiere
    @param bool   $showSubjectAverage
    @param bool   $showCoefficient
    @param bool   $showWeightedAverage
    @param bool   $showRankPerSubject
--}}
@php $_notee = \App\Models\ESBTPResultatMatiere::ligneNotee($resultat); @endphp
@if($showSubjectAverage)<td class="center">{{ $_notee ? number_format((float) $resultat->moyenne, 2) : \App\Models\ESBTPResultatMatiere::SYMBOLE_TROU }}</td>@endif
@if($showCoefficient)<td class="center">{{ $resultat->coefficient }}</td>@endif
@if($showWeightedAverage)<td class="center">{{ $_notee ? number_format((float) $resultat->moyenne * $resultat->coefficient, 2) : \App\Models\ESBTPResultatMatiere::SYMBOLE_TROU }}</td>@endif
@if($showRankPerSubject)<td class="center">{{ $_notee ? ($resultat->rang ?: '-') : \App\Models\ESBTPResultatMatiere::SYMBOLE_TROU }}</td>@endif
