{{-- Bouton d'action lisible partout (table + bgcolor : Outlook ignore les
     styles d'un lien seul), suivi du lien en clair pour qui ne peut pas cliquer.
     `background-image` et les enveloppes `gm-*` : garde contre le mode sombre
     des applications Gmail, expliquée dans `esbtp.emails.layout`. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0 10px;">
    <tr>
        <td bgcolor="{{ $emailPrimaryColor }}" style="background:{{ $emailPrimaryColor }};background-image:linear-gradient({{ $emailPrimaryColor }},{{ $emailPrimaryColor }});border-radius:12px;box-shadow:0 6px 16px rgba(15,23,42,.14);">
            @if($emailGardeGmailSombre)<div class="gm-ecran" style="border-radius:12px;"><div class="gm-diff" style="border-radius:12px;">@endif
            <a href="{{ $url }}" target="_blank" rel="noopener" style="display:inline-block;padding:15px 30px;font-size:15px;font-weight:700;color:{{ $emailHeaderTextColor }};text-decoration:none;border-radius:12px;">{{ $libelle }}&nbsp;&rarr;</a>
            @if($emailGardeGmailSombre)</div></div>@endif
        </td>
    </tr>
</table>
@if($afficherLien ?? true)
    <p style="margin:0 0 4px;font-size:12px;color:#94a3b8;">Le bouton ne s'ouvre pas ? Copiez ce lien dans votre navigateur :</p>
    <p style="margin:0;font-size:12px;word-break:break-all;"><a href="{{ $url }}" style="color:{{ $emailPrimaryColor }};">{{ $url }}</a></p>
@endif
