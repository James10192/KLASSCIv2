<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Pieces manquantes</title>
</head>
<body style="margin:0;padding:24px;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#1e293b;">
    <div style="max-width:600px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;">
        <h1 style="margin:0 0 4px;font-size:18px;color:#0453cb;">Dossier d'inscription incomplet</h1>
        @if(!empty($ecoleNom))
            <p style="margin:0 0 16px;font-size:12px;color:#64748b;">{{ $ecoleNom }}</p>
        @endif

        <p style="font-size:14px;line-height:1.6;">
            Bonjour {{ $etudiantNom }},
        </p>

        {{-- Une directive collee a un mot (…inscription@@if) n'est pas compilee par
             Blade : on prepare la phrase en PHP plutot que de l'interpoler. --}}
        @php
            $_periode = !empty($anneeNom) ? " pour l'annee {$anneeNom}" : '';
        @endphp
        <p style="font-size:14px;line-height:1.6;">
            Les pieces suivantes manquent encore a votre dossier d'inscription{{ $_periode }}.
            Merci de les deposer au secretariat.
        </p>

        <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:13px;">
            <thead>
                <tr style="background:#0453cb;color:#fff;">
                    <th style="text-align:left;padding:8px;">Piece</th>
                    <th style="text-align:center;padding:8px;width:110px;">Exemplaires</th>
                </tr>
            </thead>
            <tbody>
                @foreach($manquantes as $manquante)
                    <tr>
                        <td style="padding:8px;border-bottom:1px solid #e2e8f0;">
                            {{ $manquante['libelle'] }}
                            @if(!empty($manquante['obligatoire']))
                                <span style="color:#dc2626;font-weight:bold;">*</span>
                            @endif
                        </td>
                        <td style="padding:8px;border-bottom:1px solid #e2e8f0;text-align:center;">
                            {{ $manquante['exemplaires_manquants'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p style="font-size:12px;color:#64748b;line-height:1.6;">
            Les pieces marquees d'une etoile sont obligatoires.
        </p>
    </div>
</body>
</html>
