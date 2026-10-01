{{-- Gabarit commun de tous les courriels KLASSCI : logo, couleurs et coordonnées
     de l'école, posés par le composeur `CouleursDesCourrielsParents` (enregistré
     sur `esbtp.emails.*`). Ne recalculez rien ici : Blade évalue les `@section`
     de l'enfant AVANT ce gabarit.

     Tout ce qui structure la page est en style en ligne : plusieurs messageries
     ignorent le bloc <style>. Le bloc ne sert qu'aux classes historiques des
     avis aux parents, et à la garde contre le mode sombre de Gmail ci-dessous.

     Mode sombre des applications Gmail (iOS, Android) : elles ignorent
     `color-scheme` et recolorent d'office. Un fond porté par `background-image`
     garde sa couleur, pas le texte : le blanc de l'en-tête et du bouton devenait
     gris foncé sur le bleu. Technique de Rémi Parmentier (« Fixing Gmail's dark
     mode issues with CSS Blend Modes », 2021) : deux enveloppes à fond noir,
     `gm-ecran` (fondu `screen`) puis `gm-diff` (fondu `difference`). Gmail
     inverse ce fond noir en blanc et le texte blanc en noir ; la différence
     remet le texte en blanc sur noir, l'écran rend ce noir transparent sur la
     couleur de l'école. Sans inversion, les deux fondus ne changent rien. Le
     sélecteur `u + .body` ne vise que Gmail, qui remplace le doctype par un
     `<u></u>` : Apple Mail (qui respecte `color-scheme`), Outlook et
     Outlook.com ne le voient pas. Le logo reste HORS des enveloppes : le fondu
     inverserait ses couleurs. Garde posée seulement si ce texte est blanc
     (`emailGardeGmailSombre`, composeur `IdentiteDesCourriels`).
     Prouvée par son auteur sur Gmail iOS ; sur Gmail Android, qu'il dit
     différent, NON vérifiée ici. Tout bouton passe par `partials/bouton`, qui
     seul porte cette garde : n'écrivez pas de `<a>` stylé en bouton. --}}
@php
    $logoSrc = null;
    // Un courriel envoyé par SMTP porte le logo en pièce intégrée, que les
    // messageries affichent sans demander. Par MailPulse (mailer comme API),
    // seule l'URL existe : une pièce intégrée y serait retirée.
    if (! empty($schoolLogoPath) && isset($message) && $message instanceof \Illuminate\Mail\Message && is_file($schoolLogoPath)
        && ! \App\Mail\Transport\MailPulseTransport::actif()) {
        $logoSrc = $message->embed($schoolLogoPath);
    } elseif (! empty($schoolLogoUrl)) {
        $logoSrc = $schoolLogoUrl;
    }
    $initiale = mb_strtoupper(mb_substr(trim((string) ($schoolName ?? 'K')), 0, 1, 'UTF-8'), 'UTF-8');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>{{ $emailTitle ?? $title ?? $schoolName }}</title>
    <style>
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; border: 1px solid #e5eaf2; border-radius: 12px; overflow: hidden; }
        .info-table th, .info-table td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #eef2f7; font-size: 14px; }
        .info-table th { background: {{ $emailPrimarySoft }}; color: {{ $emailPrimaryColor }}; font-size: 12px; text-transform: uppercase; letter-spacing: .4px; }
        .info-table td { color: #334155; }
        .info-table tr:last-child td { border-bottom: none; }
        .kpi-section { display: table; width: 100%; margin: 20px 0; border-collapse: separate; border-spacing: 8px 0; }
        .kpi-row { display: table-row; }
        .kpi-card { display: table-cell; padding: 16px; background: {{ $emailPrimarySoft }}; border-radius: 12px; text-align: center; vertical-align: top; }
        .kpi-label { font-size: 12px; color: #64748b; margin-top: 4px; }
        .divider { height: 1px; background: #e5eaf2; margin: 20px 0; }
        .message { margin: 0 0 14px; }
        .kpi-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
        .kpi-value { font-size: 24px; font-weight: 700; color: {{ $emailPrimaryColor }}; }
        .kpi-desc { font-size: 12px; color: #94a3b8; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 700; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: {{ $emailPrimarySoft }}; color: {{ $emailPrimaryColor }}; }
        .alert { padding: 14px 16px; margin: 18px 0; border-radius: 10px; border-left: 4px solid; font-size: 14px; }
        .alert-success { background: #f0fdf4; border-color: #16a34a; color: #166534; }
        .alert-warning { background: #fffbeb; border-color: #f59e0b; color: #92400e; }
        .alert-danger { background: #fef2f2; border-color: {{ $emailDangerColor }}; color: #991b1b; }
        .alert-info { background: {{ $emailPrimarySoft }}; border-color: {{ $emailPrimaryColor }}; color: #1e293b; }
        .message-intro { background: {{ $emailPrimarySoft }}; border-left: 4px solid {{ $emailPrimaryColor }}; padding: 14px 16px; margin: 20px 0; border-radius: 10px; }
        .message-intro p { margin: 0; color: #1e293b; font-weight: 600; }
        .instruction-box { background: #f8fafc; border: 1px solid #e5eaf2; border-radius: 12px; padding: 18px 20px; margin: 20px 0; }
        .instruction-box h3 { color: {{ $emailPrimaryColor }}; margin: 0 0 12px; font-size: 15px; }
        .instruction-box li { margin-bottom: 6px; color: #475569; }
        .greeting { font-size: 16px; color: #0f172a; font-weight: 600; margin: 0 0 16px; }
        u + .body .gm-ecran { background: #000; mix-blend-mode: screen; }
        u + .body .gm-diff { background: #000; mix-blend-mode: difference; }
        @media only screen and (max-width: 620px) {
            .kl-wrap { padding: 0 !important; }
            .kl-card { border-radius: 0 !important; }
            .kl-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .kpi-card { display: block !important; margin-bottom: 8px; }
        }
    </style>
</head>
<body class="body" style="margin:0;padding:0;background:#eef2f7;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1e293b;-webkit-text-size-adjust:100%;">
    {{-- Aperçu affiché par la messagerie à côté du sujet. --}}
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">@yield('preheader')</div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#eef2f7;">
        <tr>
            <td class="kl-wrap" align="center" style="padding:28px 12px;">
                <table role="presentation" class="kl-card" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,.08);">

                    {{-- En-tête : couleurs de l'école, comme sur ses PDF. --}}
                    <tr>
                        <td class="kl-pad" bgcolor="{{ $emailHeaderBgColor }}" style="background:{{ $emailHeaderBgColor }};background-image:linear-gradient(135deg,{{ $emailPrimaryDark }} 0%,{{ $emailHeaderBgColor }} 55%,{{ $emailPrimaryColor }} 100%);padding:32px 36px 28px;color:{{ $emailHeaderTextColor }};">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td width="64" valign="middle" style="width:64px;">
                                        <div style="width:64px;height:64px;background:#ffffff;background-image:linear-gradient(#ffffff,#ffffff);border-radius:16px;text-align:center;line-height:64px;box-shadow:0 4px 12px rgba(0,0,0,.12);">
                                            @if($logoSrc)
                                                <img src="{{ $logoSrc }}" alt="{{ $schoolName }}" width="52" height="52" style="width:52px;height:52px;object-fit:contain;vertical-align:middle;border:0;">
                                            @else
                                                <span style="font-size:28px;font-weight:800;color:{{ $emailPrimaryColor }};">{{ $initiale }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td valign="middle" style="padding-left:16px;">
                                        @if($emailGardeGmailSombre)<div class="gm-ecran"><div class="gm-diff">@endif
                                        <div style="font-size:19px;font-weight:700;line-height:1.3;color:{{ $emailHeaderTextColor }};">{{ $schoolName }}</div>
                                        @if(! empty($schoolAddress))
                                            <div style="font-size:13px;opacity:.82;margin-top:3px;color:{{ $emailHeaderTextColor }};">{{ $schoolAddress }}</div>
                                        @endif
                                        @if($emailGardeGmailSombre)</div></div>@endif
                                    </td>
                                </tr>
                            </table>
                            @if($emailGardeGmailSombre)<div class="gm-ecran"><div class="gm-diff">@endif
                            @if(! empty($emailTitle))
                                <div style="margin-top:24px;font-size:24px;font-weight:700;line-height:1.3;color:{{ $emailHeaderTextColor }};">{{ $emailTitle }}</div>
                            @endif
                            @hasSection('subtitle')
                                <div style="margin-top:6px;font-size:14px;opacity:.85;color:{{ $emailHeaderTextColor }};">@yield('subtitle')</div>
                            @endif
                            @if($emailGardeGmailSombre)</div></div>@endif
                        </td>
                    </tr>

                    {{-- Corps --}}
                    <tr>
                        <td class="kl-pad" style="padding:32px 36px 12px;font-size:15px;line-height:1.65;color:#334155;">
                            @if(! empty($parentName))
                                <p class="greeting" style="font-size:16px;color:#0f172a;font-weight:600;margin:0 0 16px;">Bonjour {{ $parentName }},</p>
                            @endif
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Pied : coordonnées de l'école --}}
                    <tr>
                        <td class="kl-pad" style="padding:24px 36px 28px;">
                            <div style="height:1px;background:#e5eaf2;margin-bottom:20px;"></div>
                            <div style="font-size:14px;font-weight:700;color:{{ $emailPrimaryColor }};">{{ $schoolName }}</div>
                            @if(! empty($schoolPhone) || ! empty($schoolEmail))
                                <div style="font-size:13px;color:#64748b;margin-top:4px;">
                                    @if(! empty($schoolPhone))Tél. {{ $schoolPhone }}@endif
                                    @if(! empty($schoolPhone) && ! empty($schoolEmail)) · @endif
                                    @if(! empty($schoolEmail))<a href="mailto:{{ $schoolEmail }}" style="color:{{ $emailPrimaryColor }};text-decoration:none;">{{ $schoolEmail }}</a>@endif
                                </div>
                            @endif
                            <div style="font-size:12px;color:#94a3b8;margin-top:14px;line-height:1.5;">
                                @hasSection('mention')
                                    @yield('mention')
                                @else
                                    Message automatique, merci de ne pas y répondre. Pour toute question, contactez l'établissement.
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
                <div style="font-size:11px;color:#94a3b8;margin-top:14px;">Envoyé avec KLASSCI</div>
            </td>
        </tr>
    </table>
</body>
</html>
