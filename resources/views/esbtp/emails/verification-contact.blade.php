@extends('esbtp.emails.layout', ['emailTitle' => 'Confirmez votre adresse e-mail'])

@section('preheader', 'Votre code : '.$code.'. Valable '.$minutes.' minutes.')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;color:#0f172a;font-weight:600;">Bonjour,</p>
    <p style="margin:0 0 20px;">Votre demande a bien été reçue. Confirmez que cette adresse est la vôtre : l'établissement pourra ainsi vous convoquer par e-mail.</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $emailPrimarySoft }};border-radius:14px;">
        <tr>
            <td align="center" style="padding:22px 16px;">
                <div style="font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:{{ $emailPrimaryColor }};">Votre code</div>
                <div style="font-size:34px;font-weight:800;letter-spacing:10px;color:#0f172a;margin-top:6px;font-family:'Courier New',monospace;">{{ $code }}</div>
                <div style="font-size:12px;color:#64748b;margin-top:6px;">Valable {{ $minutes }} minutes</div>
            </td>
        </tr>
    </table>

    @include('esbtp.emails.partials.bouton', ['url' => $lien, 'libelle' => 'Confirmer mon adresse', 'afficherLien' => false])

    <p style="margin:20px 0 0;font-size:13px;color:#64748b;">Le lien reste valable {{ $heures }} heures. Sans confirmation, l'établissement recevra quand même votre demande, avec la mention « contact non confirmé », et vous appellera avant de vous convoquer. Si vous n'êtes pas à l'origine de cette demande, ignorez ce message ou signalez-le à l'établissement.</p>
@endsection
