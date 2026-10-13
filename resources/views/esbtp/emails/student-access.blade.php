@extends('esbtp.emails.layout', ['emailTitle' => 'Votre accès KLASSCI'])

@section('preheader', 'Un lien personnel pour accéder à votre espace, sans mot de passe transmis par e-mail.')
@section('subtitle', 'Votre espace scolaire, en toute sécurité')

@section('content')
    <p style="margin:0 0 16px;font-size:16px;font-weight:700;color:#0f172a;">Bonjour {{ $nom }},</p>

    @if($motif === 'activation')
        <p style="margin:0 0 18px;">Bienvenue sur <strong>KLASSCI</strong> ! Votre compte étudiant est prêt. Pour le sécuriser, définissez votre mot de passe personnel depuis ce lien.</p>
    @elseif($motif === 'reinscription')
        <p style="margin:0 0 18px;">Votre parcours se poursuit sur <strong>KLASSCI</strong>. Vous pouvez retrouver vos informations en vous connectant à votre espace avec votre identifiant habituel.</p>
    @else
        <p style="margin:0 0 18px;">Une demande de réinitialisation a été effectuée pour votre compte <strong>KLASSCI</strong>. Si vous êtes à l'origine de cette demande, choisissez un nouveau mot de passe.</p>
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:{{ $emailPrimarySoft }};border-radius:12px;">
        <tr><td style="padding:18px 20px;">
            <span style="font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#64748b;">Votre identifiant</span>
            <div style="margin-top:6px;font-size:17px;font-weight:700;color:#0f172a;">{{ $identifiant }}</div>
            <div style="margin-top:8px;font-size:13px;color:#64748b;">Votre mot de passe ne vous sera jamais demandé par e-mail.</div>
        </td></tr>
    </table>

    @include('esbtp.emails.partials.bouton', ['url' => $url, 'libelle' => $motif === 'activation' ? 'Activer mon compte' : 'Choisir mon mot de passe'])

    <p style="margin:20px 0 0;font-size:13px;color:#64748b;">Ce lien est personnel, utilisable une seule fois et expire dans {{ $expiration }} minutes. N'envoyez ce lien à personne.</p>
    <p style="margin:10px 0 0;font-size:13px;color:#64748b;">Vous n'avez rien demandé ? Ignorez ce message : aucun changement de mot de passe ne sera appliqué.</p>
    <p style="margin:18px 0 0;font-size:13px;color:#64748b;">À bientôt sur KLASSCI.</p>
@endsection
