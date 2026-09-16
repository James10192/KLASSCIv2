{{-- Separation des devoirs : trois regles independantes, lues depuis
     config/sod.php. Aucune cle n'est ecrite ici : en ajouter une au fichier
     de configuration l'affiche. --}}
@php $reglesSod = \App\Services\Security\SeparationOfDutiesService::reglesExposables(); @endphp
@if (count($reglesSod) > 0)
@once
<style>
/* `.au-select` est en `inline-flex; flex:1 1 0%`, et `flex` ne s'applique que
   sous un parent flex ou grid. `.ls-field` n'est ni l'un ni l'autre : sans
   cette règle, le champ rétrécit à la largeur de son texte et le menu, posé
   sur ses bords, tronque les libellés — le piège décrit dans premium-selects. */
.sod-select-full { display: flex !important; width: 100%; }
.sod-select-full .au-select-trigger { width: 100%; }
</style>
@endonce
<div class="row g-3" style="margin-top:.25rem;">
    <div class="col-12">
        <div class="ls-toggle-label" style="margin-bottom:.35rem;">
            <i class="fas fa-user-shield" style="margin-right:.35rem;"></i>Séparation des devoirs
        </div>
        @php
            // Le libellé français du registre, pas la clé technique : cet
            // écran est lu par une secrétaire, pas par un administrateur système.
            // Via PermissionRegistry et jamais config('permissions.…') :
            // la clé « sod.bypass » contient un point, et un chemin pointé
            // ferait chercher à Laravel un tableau imbriqué qui n'existe pas.
            $clePermissionSod = \App\Services\Security\SeparationOfDutiesService::permissionDeContournement();
            $metaPermissionSod = app(\App\Services\PermissionRegistry::class)->permissionMeta($clePermissionSod);
            $libellePermissionSod = $metaPermissionSod['label'] ?? $clePermissionSod;
        @endphp
        <div class="ls-toggle-hint" style="margin-bottom:.5rem;">
            Norme OHADA : personne ne signe les deux bouts d'une même chaîne.
            Chaque règle se règle séparément — <strong>Bloquer</strong> refuse le
            second geste, <strong>Observer sans bloquer</strong> le laisse passer
            en le consignant au journal, <strong>Ne rien contrôler</strong> ne
            garde aucune trace. Une école dont la même personne tient les deux
            bouts commence par observer, lit son journal, puis durcit.
            La permission « {{ $libellePermissionSod }} » lève ces contrôles
            nominativement ; chaque levée est journalisée.
        </div>
    </div>
    @foreach ($reglesSod as $regleSod)
    <div class="col-md-4">
        <div class="ls-field">
            <label class="ls-toggle-label" for="sod_{{ $loop->index }}">{{ $regleSod['label'] }}</label>
            <div class="ls-toggle-hint" style="margin-bottom:.4rem;">{{ $regleSod['hint'] }}</div>
            @php
                // Le mode posé par l'école, ou le défaut livré. Passe par l'enum
                // plutôt que par une comparaison de chaînes : un réglage écrit
                // avant l'existence du troisième état porte encore un booléen.
                $modeSod = \App\Enums\ModeSeparationDesDevoirs::depuisReglage(
                    \App\Helpers\SettingsHelper::get($regleSod['cle'], $regleSod['defaut'])
                ) ?? \App\Enums\ModeSeparationDesDevoirs::from($regleSod['defaut']);
            @endphp
            {{-- Le composant premium attend ['valeur' => 'libellé'], ce que rend
                 `options()` — la liste vit dans l'enum, pas ici, sinon un
                 quatrième mode n'apparaîtrait qu'à un seul des deux endroits.
                 Le composant garde un <select> caché, donc la boucle de
                 `ESBTPSettingsController::update()` reçoit ce champ comme avant. --}}
            <x-au-select
                class="sod-select-full"
                :name="$regleSod['cle']"
                :value="$modeSod->value"
                :options="\App\Enums\ModeSeparationDesDevoirs::options()"
                :placeholder-is-first-option="false"
                icon="fa-user-shield" />
        </div>
    </div>
    @endforeach
</div>
@endif
