<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;color:#1e293b;line-height:1.5;max-width:560px;margin:0 auto;padding:24px;">
<p>Bonjour {{ $prenom }},</p>
<p>Votre dossier a bien été transmis à <strong>{{ $ecole }}</strong>. Pour éviter l'attente au guichet, prenez rendez-vous en ligne.</p>
<p>Votre référence : <strong style="letter-spacing:.04em;">{{ $reference }}</strong></p>
<p>Vous aurez besoin de votre date de naissance{{ $identifiantAide }}.</p>
<p><a href="{{ $lien }}" style="display:inline-block;background:#0453cb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;">Choisir un créneau</a></p>
<p style="color:#64748b;font-size:13px;">Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.</p>
</body>
</html>
