{{--
    Indicateur d'établissement, à la manière du sélecteur d'organisation des
    applications SaaS. La marque KLASSCI reste dans la barre latérale : ce badge
    répond à une autre question, « chez qui suis-je ? », qui compte dès qu'on
    administre plusieurs écoles.

    Statique et non cliquable : une instance ne dessert qu'un établissement, un
    menu déroulant n'aurait rien à proposer. La navbar porte par ailleurs un
    backdrop-filter, qui casserait le positionnement d'un menu.
--}}
@php
    $etb = \App\Helpers\SettingsHelper::getSchoolInfo();

    $etbNom = trim((string) ($etb['name'] ?? ''));
    $etbSigle = trim((string) ($etb['acronym'] ?? ''));
    $etbVille = trim((string) ($etb['city'] ?? ''));
    $etbLogo = trim((string) ($etb['logo'] ?? ''));

    // On affiche le NOM, pas le sigle : deux campus d'un même groupe
    // partagent le sigle et deviendraient indiscernables. Un nom trop long
    // est coupé par le CSS, l'infobulle porte la version entière.
    $etbCourt = $etbNom !== '' ? $etbNom : $etbSigle;

    // is_file et non file_exists : un chemin de dossier passerait le test et
    // afficherait une image cassée.
    $etbLogoUrl = null;
    if ($etbLogo !== '' && is_file(storage_path('app/public/' . $etbLogo))) {
        $etbLogoUrl = asset('storage/' . $etbLogo);
    }

    // Sans logo configuré, deux lettres valent mieux qu'un cadre vide.
    $etbInitiales = $etbCourt !== ''
        ? mb_strtoupper(mb_substr($etbCourt, 0, 2, 'UTF-8'), 'UTF-8')
        : '';

    $etbInfobulle = $etbNom !== '' ? $etbNom : $etbCourt;
    if ($etbVille !== '') {
        $etbInfobulle .= ' — ' . $etbVille;
    }
@endphp

@if($etbCourt !== '')
<div class="etb-badge" title="{{ $etbInfobulle }}">
    <span class="etb-media">
        @if($etbLogoUrl)
            <img src="{{ $etbLogoUrl }}" alt="{{ $etbCourt }}" loading="lazy">
        @else
            <span class="etb-initiales" aria-hidden="true">{{ $etbInitiales }}</span>
        @endif
    </span>
    <span class="etb-texte">
        <span class="etb-nom">{{ $etbCourt }}</span>
        @if($etbVille !== '')
            <span class="etb-ville">{{ $etbVille }}</span>
        @endif
    </span>
</div>
@endif
