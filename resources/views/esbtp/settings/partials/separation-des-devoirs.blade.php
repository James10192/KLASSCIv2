{{-- Separation des devoirs : trois regles independantes, lues depuis
     config/sod.php. Aucune cle n'est ecrite ici : en ajouter une au fichier
     de configuration l'affiche. --}}
@php $reglesSod = \App\Services\Security\SeparationOfDutiesService::reglesExposables(); @endphp
@if (count($reglesSod) > 0)
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
            $modesSod = \App\Enums\ModeSeparationDesDevoirs::cases();
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
            <select class="form-select" id="sod_{{ $loop->index }}" name="{{ $regleSod['cle'] }}">
                @foreach ($modesSod as $modeOption)
                    <option value="{{ $modeOption->value }}" @selected($modeOption === $modeSod)>
                        {{ $modeOption->label() }} — {{ $modeOption->hint() }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    @endforeach
</div>
@endif
