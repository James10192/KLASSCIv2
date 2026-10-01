@extends('esbtp.emails.layout', ['emailTitle' => 'Convocation au guichet'])

@section('preheader', $intro.' '.$date.', '.$heure.'.')
@section('subtitle', $intro)

@section('content')
    <p style="margin:0 0 16px;font-size:16px;color:#0f172a;font-weight:600;">Bonjour {{ $nom }},</p>
    <p style="margin:0 0 20px;">{{ $intro }} Voici le récapitulatif de votre passage au guichet.</p>

    {{-- Le rendez-vous, en grand : c'est ce que l'on cherche en ouvrant le message. --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:{{ $emailPrimarySoft }};border-radius:14px;">
        <tr>
            <td style="padding:20px 22px;">
                <div style="font-size:12px;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:{{ $emailPrimaryColor }};">Date et heure</div>
                <div style="font-size:21px;font-weight:700;color:#0f172a;margin-top:4px;text-transform:capitalize;">{{ $date }}</div>
                <div style="font-size:17px;font-weight:600;color:{{ $emailPrimaryColor }};margin-top:2px;">{{ $heure }}</div>
                @if(! empty($lieu) || ! empty($reference))
                    <div style="height:1px;background:#d8e2f0;margin:16px 0;"></div>
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            @if(! empty($lieu))
                                <td valign="top" style="padding-right:12px;">
                                    <div style="font-size:12px;color:#64748b;">Lieu</div>
                                    <div style="font-size:14px;font-weight:600;color:#0f172a;">{{ $lieu }}</div>
                                </td>
                            @endif
                            @if(! empty($reference))
                                <td valign="top" align="right">
                                    <div style="font-size:12px;color:#64748b;">Référence</div>
                                    <div style="font-size:15px;font-weight:700;color:#0f172a;letter-spacing:1px;font-family:'Courier New',monospace;">{{ $reference }}</div>
                                </td>
                            @endif
                        </tr>
                    </table>
                @endif
            </td>
        </tr>
    </table>

    @if(! empty($lienPdf))
        <p style="margin:22px 0 0;">Présentez-vous à l'heure avec vos pièces et la convocation imprimée.</p>
        @include('esbtp.emails.partials.bouton', ['url' => $lienPdf, 'libelle' => 'Télécharger ma convocation (PDF)', 'afficherLien' => false])
    @endif

    @if(! empty($lien))
        <p style="margin:18px 0 0;font-size:14px;">Un empêchement ? <a href="{{ $lien }}" style="color:{{ $emailPrimaryColor }};font-weight:600;">Voir, déplacer ou annuler le rendez-vous</a></p>
    @endif
@endsection
