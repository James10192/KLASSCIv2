<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;color:#1e293b;line-height:1.5;max-width:560px;margin:0 auto;padding:24px;">
<p>Bonjour {{ $prenom }},</p>
<p>{{ $intro }}</p>
<table style="width:100%;border-collapse:collapse;margin:16px 0;">
    <tr><td style="padding:8px 0;color:#64748b;">Établissement</td><td style="padding:8px 0;font-weight:600;">{{ $ecole }}</td></tr>
    <tr><td style="padding:8px 0;color:#64748b;">Date</td><td style="padding:8px 0;font-weight:600;">{{ $date }}</td></tr>
    <tr><td style="padding:8px 0;color:#64748b;">Heure</td><td style="padding:8px 0;font-weight:600;">{{ $heure }}</td></tr>
    <tr><td style="padding:8px 0;color:#64748b;">Référence</td><td style="padding:8px 0;font-weight:600;letter-spacing:.04em;">{{ $reference }}</td></tr>
</table>
<p>Présentez-vous au guichet avec vos pièces. Conservez cette référence pour modifier ou annuler le rendez-vous.</p>
<p><a href="{{ $lien }}" style="display:inline-block;background:#0453cb;color:#fff;text-decoration:none;padding:12px 18px;border-radius:8px;">Gérer mon rendez-vous</a></p>
<p style="color:#64748b;font-size:13px;">{{ $ecole }}</p>
</body>
</html>
