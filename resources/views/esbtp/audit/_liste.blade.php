{{-- La liste du journal : les phrases groupees par jour, puis ce que le masquage cache.
     Rendue par la page et seule par index(?fragment=1). --}}
<div id="jda-lignes">
    @forelse($lignes as $_i => $l)
        @include('esbtp.audit._phrase', ['l' => $l, 'afficherJour' => $_i === 0 || $lignes[$_i - 1]->quand->format('Y-m-d') !== $l->quand->format('Y-m-d')])
    @empty
        <div class="jda-vide">
            <strong>Aucune action sur cette vue.</strong>
            @if($filtres->periode !== 'tout')
                Élargissez la période, ou
                <button type="button" class="jda-lien" data-jda-filtre='@json(['periode' => 'tout'])'>cherchez depuis le début</button>.
            @endif
        </div>
    @endforelse
</div>

@if($automatiques && $automatiques['nombre'] > 0)
    <div class="jda-auto">
        @php $_n = $automatiques['nombre']; @endphp
        <span><strong>{{ number_format($_n, 0, ',', ' ') }} consultation{{ $_n > 1 ? 's' : '' }} ou tâche{{ $_n > 1 ? 's' : '' }} automatique{{ $_n > 1 ? 's' : '' }}</strong>
            masquée{{ $_n > 1 ? 's' : '' }} sur cette vue{{ $automatiques['surtout'] ? ", surtout sur les ".$automatiques['surtout'] : "" }}.</span>
        <button type="button" class="jda-lien" data-jda-filtre='@json(['auto' => true])'>Les afficher</button>
    </div>
@endif

<x-liste-infinie :paginateur="$tranche" cible="#jda-lignes" libelle="actions" :url="route('esbtp.audit.index')" />
