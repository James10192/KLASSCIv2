@php $_sdPieces = $demande['pieces_jointes'] ?? []; @endphp
@if(count($_sdPieces) === 0)
    <p class="sd-vide">Aucune pièce jointe.</p>
@else
    <ul class="sd-pieces">
        @foreach($_sdPieces as $_sdPiece)
            <li>
                <i class="fas {{ str_starts_with($_sdPiece['type'] ?? '', 'image/') ? 'fa-image' : 'fa-file-pdf' }}" aria-hidden="true"></i>
                <a href="{{ route('support.demandes.pieces.show', [$demande['reference'], $_sdPiece['id']]) }}" @if(str_starts_with($_sdPiece['type'] ?? '', 'image/')) target="_blank" rel="noopener" @endif>{{ $_sdPiece['nom'] }}</a>
                <span class="sd-pieces-meta">
                    {{ number_format(($_sdPiece['taille'] ?? 0) / 1024, 0, ',', ' ') }} Ko
                    · {{ ($_sdPiece['auteur'] ?? '') === 'SUPPORT' ? 'Support KLASSCI' : 'Votre établissement' }}
                    · {{ \App\Domain\Support\Services\DateDuMaster::afficher($_sdPiece['le'] ?? null, 'd M Y à H:i') }}
                </span>
            </li>
        @endforeach
    </ul>
@endif
