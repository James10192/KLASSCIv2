{{-- Les etapes du dossier, une puce par etape. Chaque puce filtre la liste sur
     les dossiers de son etape ; un second clic revient a tous les dossiers
     ouverts. Les nombres suivent l'onglet (tous, nouvelles, reinscriptions) :
     rendus au chargement, au changement d'onglet et apres une decision
     (index?fragment=1&compteurs=1). L'etat actif suit les filtres cote Alpine. --}}
@php
    $_etapes = $compteurs['etapes'] ?? [];
@endphp
@foreach(\App\Domain\Admissions\EtapeDuDossier::cases() as $_e)
    <button type="button" class="dmi-kpi dmi-kpi--{{ $_e->ton() }}" data-dmi-etape="{{ $_e->value }}"
            :class="filtres.etape === '{{ $_e->value }}' ? 'is-actif' : ''" :aria-pressed="filtres.etape === '{{ $_e->value }}'"
            title="Afficher les dossiers : {{ mb_strtolower($_e->libelle(), 'UTF-8') }}">
        <span class="dmi-kpi-libelle">{{ $_e->libelle() }}</span>
        <span class="dmi-kpi-valeur">{{ number_format($_etapes[$_e->value] ?? 0, 0, ',', ' ') }}</span>
        <span class="dmi-kpi-repere">{{ $_e->consigne() }}</span>
    </button>
@endforeach
