@php $primaire = $emailPrimaryColor ?? '#0453cb'; @endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:16px auto;background:#ffffff;border-radius:12px;overflow:hidden;">
    <tr>
        <td style="background:{{ $primaire }};color:#ffffff;padding:22px 20px;">
            <div style="font-size:13px;opacity:.85;">{{ $schoolName }} · Support KLASSCI</div>
            <div style="font-size:19px;font-weight:bold;margin-top:6px;">
                {{ $a_repondu ? 'Le support a répondu à votre demande' : 'Votre demande est '.mb_strtolower((string) $statut_libelle, 'UTF-8') }}
            </div>
            <div style="font-size:13px;margin-top:6px;opacity:.85;">{{ $reference }}@if(!empty($titre)) · {{ $titre }}@endif</div>
        </td>
    </tr>
    <tr>
        <td style="padding:22px 20px;color:#1e293b;font-size:14px;line-height:1.6;">
            <p style="margin:0 0 14px;">Bonjour {{ $nom }},</p>
            @if($a_repondu && !empty($extrait))
                <p style="margin:0 0 8px;color:#64748b;font-size:13px;">Réponse du support :</p>
                <div style="border-left:3px solid {{ $primaire }};background:#f8fafc;padding:12px 14px;border-radius:6px;white-space:pre-line;">{{ $extrait }}</div>
            @endif
            @if(!empty($statut_libelle))
                <p style="margin:16px 0 0;">Statut actuel : <strong>{{ $statut_libelle }}</strong></p>
            @endif
            <p style="margin:22px 0;">
                <a href="{{ $lien }}" style="display:inline-block;background:{{ $primaire }};color:#ffffff;padding:12px 18px;text-decoration:none;font-weight:bold;border-radius:8px;">
                    Ouvrir la demande
                </a>
            </p>
            <p style="margin:0;font-size:12px;color:#64748b;">Vous recevez ce message parce que vous avez signalé ce problème depuis KLASSCI. Répondez depuis la demande, pas à ce courriel.</p>
        </td>
    </tr>
</table>
</body>
</html>
