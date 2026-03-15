<!DOCTYPE html>
<html>
<head>
    <title>Demande d'approbation de bon de sortie</title>
</head>
<body>
    <h1>Demande d'approbation de bon de sortie</h1>
    <p>Bonjour,</p>
    <p>Une nouvelle demande de bon de sortie vous a été assignée pour approbation.</p>
    <p><strong>Référence :</strong> {{ $bon->reference }}</p>
    <p><strong>Titre :</strong> {{ $bon->titre }}</p>
    <p><strong>Description :</strong> {{ $bon->description }}</p>
    <p>
        <a href="{{ route('esbtp.bons_sortie.show', $bon->id) }}">Voir les détails du bon de sortie</a>
    </p>
    <p>Merci,</p>
    <p>L'équipe {{ \App\Helpers\SettingsHelper::get('school_acronym', config('app.name')) }}</p>
</body>
</html> 