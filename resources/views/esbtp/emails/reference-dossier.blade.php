@php
    $primaire = $emailPrimaryColor ?? '#0453cb';
@endphp
<!DOCTYPE html>
<html lang="fr">
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:16px auto;background:#fff;">
    <tr>
        <td style="background:{{ $primaire }};color:#ffffff;padding:20px 16px;text-align:center;">
            @if(!empty($schoolLogoUrl))
                <img src="{{ $schoolLogoUrl }}" alt="" width="72" height="72" style="display:block;margin:0 auto 8px;background:#fff;padding:4px;border-radius:6px;">
            @endif
            <div style="font-size:18px;font-weight:bold;">{{ $schoolName }}</div>
            <div style="font-size:13px;margin-top:8px;">Votre référence de dossier</div>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 16px;color:#1f2937;">
            <p>Bonjour,</p>
            <p>Vous avez demandé la référence de votre dossier. La voici :</p>
            @foreach($references as $r)
                <p style="margin:4px 0;">{{ $r['type'] === 'inscription' ? 'Inscription' : 'Réinscription' }} :
                    <strong style="font-size:20px;letter-spacing:2px;color:{{ $primaire }};">{{ $r['reference'] }}</strong></p>
            @endforeach
            <p style="margin:20px 0;">
                <a href="{{ $lien }}" style="display:inline-block;background:{{ $primaire }};color:#fff;padding:12px 18px;text-decoration:none;font-weight:bold;border-radius:6px;">
                    Suivre mon dossier
                </a>
            </p>
            <p style="font-size:13px;color:#64748b;">Avec cette référence et votre date de naissance, vous pouvez suivre votre dossier, confirmer votre adresse e-mail et recevoir votre convocation. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message.</p>
        </td>
    </tr>
</table>
</body>
</html>
