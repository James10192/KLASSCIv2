@php
    // Même formulation que le sujet et la cloche, sans le titre de la demande,
    // qui figure juste en dessous dans sa carte.
    $_rsTitre = \App\Domain\Support\Services\AvertirDuRetourDuSupport::formuler(
        null, ['libelle' => $statut_libelle ?? null, 'code' => $statut_code ?? null], (bool) $a_repondu
    );
    // La couleur de la pastille porte un sens, comme sur l'écran des demandes :
    // vert = réglé, gris = clos, couleur pleine = l'école doit agir.
    [$_rsFond, $_rsTexte] = match ($statut_code ?? '') {
        'RESOLU' => ['#dcfce7', '#166534'],
        'FERME' => ['#f1f5f9', '#475569'],
        'ACTION_REQUISE' => [$emailPrimaryColor, $emailHeaderTextColor],
        default => [$emailPrimarySoft, $emailPrimaryColor],
    };
@endphp
@extends('esbtp.emails.layout', ['emailTitle' => $_rsTitre])

@section('subtitle', 'Support KLASSCI · suivi de vos demandes')

@section('preheader', $a_repondu && ! empty($extrait) ? mb_strimwidth((string) $extrait, 0, 110, '…') : $_rsTitre.' ('.$reference.')')

@section('content')
    <p style="margin:0 0 18px;font-size:16px;color:#0f172a;font-weight:600;">Bonjour {{ $nom }},</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #e5eaf2;border-radius:14px;">
        <tr>
            <td style="padding:16px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td valign="middle" style="font-size:12px;font-weight:700;letter-spacing:.5px;color:{{ $emailPrimaryColor }};font-family:'Courier New',monospace;">{{ $reference }}</td>
                        @if(! empty($statut_libelle))
                            <td valign="middle" align="right">
                                <span style="display:inline-block;padding:4px 12px;border-radius:999px;background:{{ $_rsFond }};color:{{ $_rsTexte }};font-size:12px;font-weight:700;white-space:nowrap;">{{ $statut_libelle }}</span>
                            </td>
                        @endif
                    </tr>
                </table>
                <div style="margin-top:8px;font-size:16px;font-weight:700;line-height:1.4;color:#0f172a;">{{ ! empty($titre) ? $titre : 'Votre demande au support' }}</div>
            </td>
        </tr>
    </table>

    @if($a_repondu && ! empty($extrait))
        <div style="margin:24px 0 8px;font-size:11px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:#64748b;">Réponse du support</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border-radius:14px;">
            <tr>
                <td width="4" bgcolor="{{ $emailPrimaryColor }}" style="width:4px;background:{{ $emailPrimaryColor }};border-radius:14px 0 0 14px;font-size:0;line-height:0;">&nbsp;</td>
                <td style="padding:16px 20px;font-size:15px;line-height:1.65;color:#1e293b;white-space:pre-line;">{{ $extrait }}</td>
            </tr>
        </table>
    @elseif(! $a_repondu)
        <p style="margin:22px 0 0;">Le statut de votre demande vient de changer. Ouvrez-la pour voir son historique complet.</p>
    @endif

    @include('esbtp.emails.partials.bouton', ['url' => $lien, 'libelle' => 'Ouvrir la demande'])

    <p style="margin:22px 0 0;font-size:13px;color:#64748b;">Pour répondre au support, écrivez depuis la demande dans KLASSCI&nbsp;: une réponse à ce courriel ne lui parviendrait pas.</p>
@endsection

@section('mention', 'Vous recevez ce message parce que vous avez signalé un problème depuis KLASSCI.')
