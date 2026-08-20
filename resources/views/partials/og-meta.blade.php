{{--
    Aperçu affiché quand l'adresse de l'établissement est partagée sur WhatsApp,
    LinkedIn, Facebook ou par message.

    Le nom vient des paramètres du tenant : partager l'adresse de l'ISLG doit
    montrer l'ISLG, pas une marque générique. Sans nom configuré, on retombe sur
    KLASSCI plutôt que sur une carte vide.
--}}
@php
    $ogEcole = \App\Helpers\SettingsHelper::getSchoolInfo();
    $ogNom = trim((string) ($ogEcole['name'] ?? '')) ?: 'KLASSCI';
    $ogSigle = trim((string) ($ogEcole['acronym'] ?? ''));
    $ogVille = trim((string) ($ogEcole['city'] ?? ''));

    $ogTitre = $ogNom.' — Espace en ligne';

    // La description dit à quoi sert le lien. « Bienvenue » n'apprend rien à
    // celui qui hésite à cliquer.
    $ogDescription = 'Inscriptions, notes, bulletins et scolarité de '
        .($ogSigle !== '' && $ogSigle !== $ogNom ? $ogSigle : $ogNom)
        .($ogVille !== '' ? ' à '.$ogVille : '')
        .'. Connectez-vous avec les identifiants remis par votre établissement.';

    $ogImage = url('/og-image.png');
    $ogUrl = url('/');
@endphp

<meta name="description" content="{{ $ogDescription }}">

<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $ogNom }}">
<meta property="og:title" content="{{ $ogTitre }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:locale" content="fr_FR">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="{{ \App\Services\OpenGraphImageService::LARGEUR }}">
<meta property="og:image:height" content="{{ \App\Services\OpenGraphImageService::HAUTEUR }}">
<meta property="og:image:alt" content="Logo de {{ $ogNom }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $ogTitre }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">
