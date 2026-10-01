@extends('esbtp.emails.layout', ['emailTitle' => 'Activez votre espace étudiant'])

@section('preheader', 'Votre préinscription est enregistrée. Choisissez votre mot de passe pour accéder à votre espace.')
@section('subtitle', 'Encore quelques minutes et votre inscription est terminée')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;color:#0f172a;font-weight:600;">Bonjour {{ $nom }},</p>
    <p style="margin:0 0 20px;">Votre dossier d'inscription à <strong>{{ $schoolName }}</strong> a franchi l'étape de préinscription. Activez votre espace étudiant pour la suite.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $emailPrimarySoft }};border-radius:14px;">
        <tr>
            <td style="padding:18px 20px;">
                <div style="font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:{{ $emailPrimaryColor }};margin-bottom:10px;">Ce qui vous attend</div>
                @foreach($etapes as $i => $etape)
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:{{ $i ? '8px' : '0' }} 0 0;">
                        <tr>
                            <td valign="top" width="26" style="width:26px;">
                                <div style="width:22px;height:22px;border-radius:11px;background:{{ $emailPrimaryColor }};color:{{ $emailHeaderTextColor }};font-size:12px;font-weight:700;text-align:center;line-height:22px;">{{ $i + 1 }}</div>
                            </td>
                            <td valign="top" style="padding-left:8px;font-size:14px;color:#1e293b;line-height:22px;">{{ $etape }}</td>
                        </tr>
                    </table>
                @endforeach
            </td>
        </tr>
    </table>

    @include('esbtp.emails.partials.bouton', ['url' => $url, 'libelle' => 'Activer mon espace'])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;border-left:4px solid #f59e0b;background:#fffbeb;border-radius:10px;">
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#92400e;">
                Ce lien est personnel : il expire dans <strong>{{ $heures }} heures</strong> et ne fonctionne qu'une fois. Si vous n'êtes pas à l'origine de cette inscription, ignorez ce message.
            </td>
        </tr>
    </table>
@endsection
