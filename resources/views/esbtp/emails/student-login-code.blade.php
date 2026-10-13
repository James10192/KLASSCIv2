@extends('esbtp.emails.layout', ['emailTitle' => 'Votre code de connexion KLASSCI'])

@section('preheader', 'Votre code de connexion personnel, valable cinq minutes.')
@section('subtitle', 'Une connexion simple, sans mot de passe')

@section('content')
<p style="margin:0 0 18px;font-size:16px;font-weight:700;color:#0f172a;">Bonjour {{ $nom }},</p>
<p style="margin:0 0 20px;">Voici votre code pour vous connecter à votre compte KLASSCI partagé avec votre famille.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius:14px;background:{{ $emailPrimarySoft }};">
    <tr><td style="text-align:center;padding:27px 12px;">
        <div style="font-size:34px;font-weight:800;letter-spacing:9px;color:{{ $emailPrimaryColor }};">{{ $code }}</div>
        <p style="font-size:13px;color:#64748b;margin:12px 0 0;">Code valable {{ $minutes }} minutes</p>
    </td></tr>
</table>
<p style="font-size:13px;line-height:1.7;color:#64748b;margin:20px 0 0;">Ne transmettez ce code à personne en dehors du parent ou de l'étudiant qui partage déjà ce compte. Si vous n'avez pas demandé de connexion, ignorez ce message.</p>
@endsection
