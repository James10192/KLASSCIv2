<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTitle }}</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8fafc; margin: 0; padding: 24px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 12px rgba(15,23,42,.08);">
        <div style="background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 50%, #3b7ddb 100%); padding: 28px 32px; color: #fff;">
            <h1 style="margin: 0; font-size: 22px; font-weight: 700;">{{ $reportTitle }}</h1>
            @if($reportSubtitle)
                <p style="margin: 8px 0 0; color: rgba(255,255,255,.85); font-size: 14px;">{{ $reportSubtitle }}</p>
            @endif
        </div>
        <div style="padding: 28px 32px;">
            <p style="font-size: 15px; color: #1e293b; margin: 0 0 14px;">Bonjour,</p>
            <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 14px;">
                Vous trouverez en pièce jointe le rapport <strong>{{ $reportTitle }}</strong>
                @if($senderName) demandé par <strong>{{ $senderName }}</strong>@endif.
            </p>
            <p style="font-size: 14px; color: #475569; line-height: 1.6; margin: 0 0 18px;">
                Document généré automatiquement par le moteur Analytics KLASSCI.
            </p>
            <div style="background: #f1f5f9; padding: 14px 18px; border-radius: 8px; border-left: 3px solid #0453cb; font-size: 13px; color: #1e293b;">
                <strong>📎 Pièce jointe :</strong> rapport au format PDF
            </div>
        </div>
        <div style="background: #fafbfc; padding: 14px 32px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; text-align: center;">
            KLASSCI · {{ now()->locale('fr')->translatedFormat('d F Y') }}
        </div>
    </div>
</body>
</html>
