@php $primaire = $emailPrimaryColor ?? '#0453cb'; @endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:16px auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="background:{{ $primaire }};color:#ffffff;padding:22px 20px;text-align:center;">
            <div style="font-size:18px;font-weight:bold;">{{ $schoolName }}</div>
            <div style="font-size:13px;margin-top:6px;opacity:.85;">Confirmation de votre adresse e-mail</div>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 20px;color:#1e293b;font-size:14px;line-height:1.6;">
            <p style="margin:0 0 14px;">Bonjour {{ $nom }},</p>
            <p style="margin:0 0 14px;">Confirmez que cette adresse est bien la vôtre : vous serez alors averti par e-mail quand le support KLASSCI répond à vos demandes.</p>
            <p style="margin:22px 0;">
                <a href="{{ $lien }}" style="display:inline-block;background:{{ $primaire }};color:#ffffff;padding:12px 18px;text-decoration:none;font-weight:bold;border-radius:8px;">
                    Confirmer mon adresse
                </a>
            </p>
            <p style="margin:0;font-size:12px;color:#64748b;">Le lien est valable {{ $heures }} heures et demande d'être connecté à KLASSCI. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.</p>
        </td>
    </tr>
</table>
</body>
</html>
