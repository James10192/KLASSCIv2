{{-- « Redoublant ? » dans les fenêtres Accepter et inscrire (ctx = ins) et
     Réinscrire (ctx = reins). La logique vit dans _script (redo*). --}}
<div class="dmi-champ" style="margin-top:.75rem" x-show="cfg.redoublant && redoPropose('{{ $ctx }}') !== null" x-cloak
     :class="{{ $ctx }}.erreurs.redoublant_motif ? 'is-erreur' : ''">Redoublant ?
    <div class="dmi-choix" role="radiogroup" aria-label="Redoublant">
        <button type="button" role="radio" :aria-checked="redoValeur('{{ $ctx }}') === '1'" :class="redoValeur('{{ $ctx }}') === '1' ? 'is-actif' : ''" x-on:click="redoChoisir('{{ $ctx }}', '1')">Oui, il redouble</button>
        <button type="button" role="radio" :aria-checked="redoValeur('{{ $ctx }}') === '0'" :class="redoValeur('{{ $ctx }}') === '0' ? 'is-actif' : ''" x-on:click="redoChoisir('{{ $ctx }}', '0')">Non</button>
    </div>
    <span class="dmi-champ-aide" x-text="redoAide('{{ $ctx }}')"></span>
    <span class="dmi-champ-aide dmi-champ-aide--alerte" x-show="redoContradiction('{{ $ctx }}')" x-cloak><i class="fas fa-triangle-exclamation"></i> <span x-text="redoContradiction('{{ $ctx }}')"></span></span>
    <label class="dmi-champ" style="margin-top:.5rem" x-show="redoMotifRequis('{{ $ctx }}') || {{ $ctx }}.erreurs.redoublant_motif" x-cloak>Pourquoi ? <span class="dmi-champ-aide">Obligatoire quand vous changez la réponse proposée (10 caractères au moins).</span>
        <textarea x-model="{{ $ctx }}.redo.motif" maxlength="500" placeholder="Exemple : redouble sa 1re année, venu d'un autre établissement"></textarea>
    </label>
    <span class="dmi-champ-erreur" x-text="{{ $ctx }}.erreurs.redoublant_motif" x-show="{{ $ctx }}.erreurs.redoublant_motif"></span>
</div>
