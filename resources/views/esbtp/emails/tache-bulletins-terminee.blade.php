@php
    $primaire = $emailPrimaryColor ?? '#0453cb';
@endphp
<!DOCTYPE html>
<html lang="fr">
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:16px auto;background:#fff;">
    <tr>
        <td style="background:{{ $primaire }};color:#ffffff;padding:20px 16px;text-align:center;">
            <div style="font-size:18px;font-weight:bold;">{{ $schoolName }}</div>
            <div style="font-size:13px;margin-top:8px;">{{ $libelle }}</div>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 16px;color:#1f2937;">
            <p>Bonjour{{ $prenom !== '' ? ' '.$prenom : '' }},</p>
            @if($reussie)
                <p>Le travail que vous avez lancé est terminé.</p>
            @else
                <p>Le travail que vous avez lancé n'a pas abouti.</p>
            @endif
            <p style="padding:12px 14px;background:#f8fafc;border-left:3px solid {{ $reussie ? $primaire : '#dc2626' }};">{{ $message }}</p>
            @if($lien)
                <p style="margin:20px 0;">
                    <a href="{{ $lien }}" style="display:inline-block;background:{{ $primaire }};color:#fff;padding:12px 18px;text-decoration:none;font-weight:bold;border-radius:6px;">
                        {{ $libelleLien }}
                    </a>
                </p>
            @endif
            @if($reussie && $estExport)
                <p style="font-size:13px;color:#64748b;">Le document reste disponible {{ $conservationHeures }} heures. Il faut être connecté à l'application pour l'ouvrir.</p>
            @endif
            <p style="font-size:13px;color:#64748b;">Ce message est envoyé automatiquement parce que vous avez lancé ce travail.</p>
        </td>
    </tr>
</table>
</body>
</html>
