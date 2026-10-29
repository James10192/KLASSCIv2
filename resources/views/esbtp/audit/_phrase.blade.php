{{-- Une action du journal, dite comme une phrase, precedee de son jour quand il change.
     Rendue par le journal, sa suite au defilement et l'activite des personnes.
     Attend $l (App\Domain\Audit\LigneDuJournal) et $afficherJour. Une ligne que le
     lecteur ne peut pas ouvrir n'est pas un lien : elle ne mene jamais a un refus. Le jour porte sa propre
     cle : une tranche qui s'ouvre sur un jour deja affiche ne le repete pas. --}}
@if($afficherJour)
    @php
        $_jour = mb_strtoupper($l->quand->translatedFormat($l->quand->isCurrentYear() ? 'l j F' : 'l j F Y'), 'UTF-8');
        $_prefixe = $l->quand->isToday() ? "AUJOURD'HUI · " : ($l->quand->isYesterday() ? 'HIER · ' : '');
    @endphp
    <div class="jda-jour" data-li-cle="jour-{{ $l->quand->format('Y-m-d') }}">{{ $_prefixe }}{{ $_jour }}</div>
@endif
@php $_balise = $l->peutOuvrir ? 'a' : 'div'; @endphp
<{{ $_balise }} @if($l->peutOuvrir) href="{{ route('esbtp.audit.show', $l->id) }}" @endif class="jda-ligne {{ $l->motifs !== [] ? 'is-alerte' : '' }} {{ $l->peutOuvrir ? '' : 'is-fermee' }}" data-li-cle="{{ $l->id }}">
    <span class="jda-av {{ $l->automatique ? 'jda-av--auto' : '' }}" aria-hidden="true">{!! $l->automatique ? '<i class="fas fa-gear"></i>' : e($l->initiales()) !!}</span>
    <span class="jda-corps">
        <span class="jda-phrase">
            <strong>{{ $l->acteur }}</strong>@if($l->role) <span class="jda-role">({{ $l->role }})</span>@endif
            {{ $l->verbe }} {{ $l->objet->designation }} <strong class="jda-cible">{{ $l->objet->nom }}</strong>@if($l->objet->supprime) <span class="jda-sup">supprimé</span>@endif
        </span>
        @if($l->objet->reperes !== [] || $l->changement)
            <span class="jda-puces">
                @foreach($l->objet->reperes as $_repere)<span class="jda-puce">{{ $_repere }}</span>@endforeach
                @if($l->changement)<span class="jda-puce jda-puce--change">{{ $l->changement }}</span>@endif
            </span>
        @endif
    </span>
    <span class="jda-droite">
        <time datetime="{{ $l->quand->toIso8601String() }}">{{ $l->quand->format('H:i') }}</time>
        @foreach($l->motifs as $_motif)<span class="jda-motif">{{ $_motif }}</span>@endforeach
    </span>
</{{ $_balise }}>
