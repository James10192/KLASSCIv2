<!doctype html>
<html lang="fr">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f6f4f0;font-family:Arial,Helvetica,sans-serif;color:#172235;padding:30px 12px">
<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;max-width:560px;margin:auto;background:#fff;border-radius:16px">
<tr><td style="padding:36px 32px">
    <p style="font-size:13px;color:#0453cb;font-weight:bold;letter-spacing:.05em">KLASSCI — ESPACE RESPONSABLE</p>
    <h1 style="font-size:25px;line-height:1.3">Votre accès personnel est prêt</h1>
    <p>Bonjour {{ $parent->prenoms }},</p>
    <p>Votre établissement vous invite à activer <strong>votre propre compte</strong>. Vous ne devez pas utiliser les identifiants de l'étudiant.</p>
    <p style="margin:32px 0">
        <a href="{{ $url }}" style="background:#0453cb;color:#fff;text-decoration:none;padding:16px 22px;border-radius:8px;font-weight:bold">Créer mon mot de passe</a>
    </p>
    <p style="font-size:13px;color:#526173">Lien valable 48 heures et utilisable une seule fois. Si vous n'avez rien demandé, contactez l'établissement.</p>
</td></tr></table>
</body></html>
