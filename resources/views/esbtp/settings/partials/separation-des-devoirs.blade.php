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
        @endphp
        <div class="ls-toggle-hint" style="margin-bottom:.5rem;">
            Norme OHADA : personne ne signe les deux bouts d'une même chaîne.
            La permission « {{ $libellePermissionSod }} » lève ces contrôles ;
            chaque levée est journalisée.
        </div>
    </div>
    @foreach ($reglesSod as $regleSod)
    <div class="col-md-4">
        <label class="ls-toggle" for="sod_{{ $loop->index }}">
            <div class="ls-toggle-text">
                <div class="ls-toggle-label">{{ $regleSod['label'] }}</div>
                <div class="ls-toggle-hint">{{ $regleSod['hint'] }}</div>
            </div>
            <div class="form-check form-switch" style="margin:0; padding-left:2.5em;">
                <input class="form-check-input" type="checkbox" id="sod_{{ $loop->index }}"
                       name="{{ $regleSod['cle'] }}" value="1"
                       {{-- filter_var et non « == '1' » : le réglage rend un vrai booléen
                            quand il existe en base (type boolean), une chaîne sinon. La
                            comparaison lâche marchait par accident ; passer à === l'aurait cassée. --}}
                       {{ filter_var(\App\Helpers\SettingsHelper::get($regleSod['cle'], $regleSod['defaut']), FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
            </div>
        </label>
    </div>
    @endforeach
</div>
@endif
