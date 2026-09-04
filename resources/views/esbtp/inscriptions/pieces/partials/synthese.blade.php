{{-- Synthèse par pièce : la réponse directe à « il manque 34 extraits de naissance ».
     Chaque carte est un filtre : un clic isole les étudiants concernés par cette pièce. --}}
@foreach($parPiece as $piece)
    <button type="button"
            class="pm-piece {{ $codeActif === $piece['code'] ? 'is-active' : '' }}"
            data-piece-code="{{ $piece['code'] }}">
        <span class="pm-piece-count">{{ $piece['etudiants'] }}</span>
        <span>
            <span class="pm-piece-libelle">{{ $piece['libelle'] }}</span>
            <span class="pm-piece-meta">
                @if($piece['obligatoire'])<span class="pm-piece-req">obligatoire</span> · @endif
                {{ $piece['exemplaires'] }} exemplaire(s)
            </span>
        </span>
    </button>
@endforeach
