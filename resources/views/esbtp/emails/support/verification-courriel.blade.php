@extends('esbtp.emails.layout', ['emailTitle' => 'Confirmez votre adresse e‑mail'])

@section('subtitle', 'Support KLASSCI · suivi de vos demandes')

@section('preheader', 'Un clic pour être averti par e-mail des réponses du support. Lien valable '.$heures.' heures.')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;color:#0f172a;font-weight:600;">Bonjour {{ $nom }},</p>
    <p style="margin:0 0 6px;">Confirmez que cette adresse est bien la vôtre&nbsp;: vous serez alors averti par e-mail dès que le support KLASSCI répond à l'une de vos demandes.</p>

    @include('esbtp.emails.partials.bouton', ['url' => $lien, 'libelle' => 'Confirmer mon adresse'])

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 8px;background:{{ $emailPrimarySoft }};border-radius:14px;">
        <tr>
            <td style="padding:16px 20px;font-size:13px;line-height:1.6;color:#475569;">
                <div style="font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:{{ $emailPrimaryColor }};margin-bottom:6px;">Bon à savoir</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:13px;line-height:1.6;color:#475569;">
                    <tr>
                        <td width="16" valign="top" style="width:16px;padding:2px 0;color:{{ $emailPrimaryColor }};">&bull;</td>
                        <td valign="top" style="padding:2px 0;">Le lien est valable <strong style="color:#0f172a;">{{ $heures }} heures</strong>.</td>
                    </tr>
                    <tr>
                        <td width="16" valign="top" style="width:16px;padding:2px 0;color:{{ $emailPrimaryColor }};">&bull;</td>
                        <td valign="top" style="padding:2px 0;">Il s'ouvre dans KLASSCI&nbsp;: connectez-vous d'abord si on vous le demande.</td>
                    </tr>
                    <tr>
                        <td width="16" valign="top" style="width:16px;padding:2px 0;color:{{ $emailPrimaryColor }};">&bull;</td>
                        <td valign="top" style="padding:2px 0;">Vous n'êtes pas à l'origine de cette demande&nbsp;? Ignorez simplement ce message.</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@endsection

@section('mention', 'Message automatique du support KLASSCI, merci de ne pas y répondre.')
