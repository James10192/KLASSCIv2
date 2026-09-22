@php
    // La couleur porte un sens : l'orange attend l'ecole, le vert est regle, le gris est clos.
    $_sdTon = match ($statut['code'] ?? '') {
        'ACTION_REQUISE' => 'attention',
        'RESOLU' => 'succes',
        'FERME' => 'neutre',
        default => 'info',
    };
@endphp
<span class="sd-statut sd-statut--{{ $_sdTon }}">{{ $statut['libelle'] ?? '—' }}</span>
