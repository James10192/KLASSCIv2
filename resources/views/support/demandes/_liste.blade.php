<div class="sd-liste" id="sd-liste">
    @if($indisponible)
        <div class="sd-vide">
            <i class="fas fa-plug-circle-xmark"></i>
            <p><strong>Le support est momentanément injoignable.</strong><br>Vos demandes sont en sécurité ; réessayez dans un instant.</p>
        </div>
    @elseif(empty($demandes))
        <div class="sd-vide">
            <i class="fas fa-inbox"></i>
            <p><strong>Aucune demande pour l'instant.</strong><br>Un problème, une question ? Signalez-le depuis n'importe quelle page, menu de votre compte.</p>
        </div>
    @else
        @foreach($demandes as $d)
            <a class="sd-ligne" href="{{ route('support.demandes.show', $d['reference']) }}{{ $portee === 'school' ? '?portee=ecole' : '' }}">
                <div class="sd-ligne-principal">
                    <span class="sd-ref">{{ $d['reference'] }}</span>
                    <span class="sd-titre">{{ $d['titre'] }}</span>
                    <span class="sd-meta">
                        {{ $d['categorie']['libelle'] ?? '' }}
                        · {{ \Carbon\Carbon::parse($d['cree_le'])->translatedFormat('d M Y, H:i') }}
                        @if($portee === 'school' && !empty($d['rapporteur']['nom']))
                            · {{ $d['rapporteur']['nom'] }}
                        @endif
                    </span>
                    @if(!empty($d['derniere_reponse']))
                        <span class="sd-reponse"><i class="fas fa-reply"></i> {{ \Illuminate\Support\Str::limit($d['derniere_reponse']['corps'], 110) }}</span>
                    @endif
                </div>
                @include('support.demandes._statut', ['statut' => $d['statut']])
            </a>
        @endforeach

        @if(($meta['pages'] ?? 1) > 1)
            <nav class="sd-pagination" aria-label="Pages">
                @for($p = 1; $p <= $meta['pages']; $p++)
                    <a href="{{ route('support.demandes.index', array_filter(['page' => $p, 'portee' => $portee === 'school' ? 'ecole' : null])) }}"
                       class="sd-page {{ $p === ($meta['page'] ?? 1) ? 'sd-page--active' : '' }}" data-sd-lien>{{ $p }}</a>
                @endfor
            </nav>
        @endif
    @endif
</div>
