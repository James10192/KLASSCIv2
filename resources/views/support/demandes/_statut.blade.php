@php
    // La couleur porte un sens : l'orange attend l'ecole, le vert est regle, le gris est clos.
    $_sdTon = \App\Domain\Support\TonDuStatut::pour($statut['code'] ?? null);
@endphp
<span class="sd-statut sd-statut--{{ $_sdTon }}">{{ $statut['libelle'] ?? '—' }}</span>
