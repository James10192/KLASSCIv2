@php
    $fond = $emailHeaderBgColor ?? '#0453cb';
    $texteBandeau = $emailHeaderTextColor ?? '#ffffff';
    $primaire = $emailPrimaryColor ?? '#0453cb';
@endphp
<!DOCTYPE html>
<html lang="fr">
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:16px auto;background:#fff;">
    <tr>
        <td style="background:{{ $fond }};color:{{ $texteBandeau }};padding:20px 16px;text-align:center;">
            @if(!empty($schoolLogoUrl))
                <img src="{{ $schoolLogoUrl }}" alt="" width="72" height="72" style="display:block;margin:0 auto 8px;background:#fff;padding:4px;border-radius:6px;">
            @endif
            <div style="font-size:18px;font-weight:bold;">{{ $schoolName }}</div>
            <div style="font-size:13px;margin-top:8px;">Convocation au guichet</div>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 16px;color:#1f2937;">
            <p>Bonjour {{ $nom }},</p>
            <p>{{ $intro }}</p>
            <p style="font-size:18px;font-weight:bold;color:{{ $primaire }};">{{ $date }}<br>{{ $heure }}</p>
            @if($reference)
                <p>Référence : <strong>{{ $reference }}</strong></p>
            @endif
            <p>Présentez-vous à l'heure indiquée avec vos pièces. Imprimez la convocation PDF et apportez-la au guichet.</p>
            @if(!empty($lienPdf))
                <p style="margin:20px 0;">
                    <a href="{{ $lienPdf }}" style="display:inline-block;background:{{ $primaire }};color:#fff;padding:12px 18px;text-decoration:none;font-weight:bold;border-radius:6px;">
                        Télécharger / imprimer la convocation PDF
                    </a>
                </p>
            @endif
            @if($lien)
                <p><a href="{{ $lien }}" style="color:{{ $primaire }};">Voir ou modifier le rendez-vous</a></p>
            @endif
        </td>
    </tr>
</table>
</body>
</html>
