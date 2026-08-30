<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>PV {{ $snapshot['document']['number'] }}</title>
    <style>
        @page { margin: 16mm 14mm 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1e293b; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        .header { border-bottom: 3px solid #0453cb; margin-bottom: 12px; }
        .header td { padding: 0 0 10px; vertical-align: top; }
        .title { font-size: 17px; font-weight: 700; color: #0453cb; text-transform: uppercase; }
        .muted { color: #64748b; }
        .id-table td { padding: 6px 8px; background-color: #0453cb; color: #fff; }
        .id-table .right { text-align: right; }
        .verify td { padding: 5px 8px; border: 1px solid #cbd5e1; font-family: DejaVu Sans Mono, monospace; }
        h2 { margin: 13px 0 6px; padding: 6px 8px; background-color: #eff6ff; border-left: 3px solid #0453cb; color: #0453cb; font-size: 11px; page-break-after: avoid; }
        .info td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .info .label { width: 32%; color: #64748b; }
        .data th { padding: 5px 4px; background-color: #0453cb; color: #fff; text-align: left; font-size: 8px; }
        .data td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; vertical-align: top; font-size: 8px; }
        .data tr:nth-child(even) td { background-color: #f8fafc; }
        .center { text-align: center; }
        .signature td { width: 50%; padding: 10px; border: 1px solid #cbd5e1; vertical-align: top; page-break-inside: avoid; }
        .signature img { max-height: 46px; max-width: 130px; margin-top: 5px; }
        .legal { margin-top: 12px; padding: 8px; border: 1px solid #cbd5e1; background-color: #f8fafc; font-size: 8px; }
        .footer { position: fixed; bottom: -12mm; left: 0; right: 0; border-top: 1px solid #cbd5e1; padding-top: 4px; text-align: center; color: #64748b; font-size: 7.5px; }
    </style>
    @include('pdf.partials.theme')
</head>
<body>
@include('pdf.partials.banner', [
    'title' => 'Procès-verbal de délibération',
    'subtitle' => ($snapshot['jury']['year']['label'] ?? '').' · Semestre '.($snapshot['jury']['semester'] ?? '—'),
])
<div style="height:8px;"></div>

<table class="id-table">
    <tr>
        <td>{{ $snapshot['document']['number'] }} · Version {{ $snapshot['document']['version'] }}</td>
        <td class="right">Émis le {{ \Carbon\Carbon::parse($snapshot['issuance']['issued_at'])->format('d/m/Y à H:i') }}</td>
    </tr>
</table>
<table class="verify">
    <tr><td style="width:30%;">Référence officielle</td><td>{{ $snapshot['document']['reference'] }}</td></tr>
    <tr><td>Code de vérification</td><td>{{ $verificationCode }}</td></tr>
</table>

<h2>1. Identification du jury</h2>
<table class="info">
    <tr><td class="label">Libellé</td><td>{{ $snapshot['jury']['label'] }}</td></tr>
    <tr><td class="label">Date du jury</td><td>{{ $snapshot['jury']['date'] ?? 'Non renseignée' }}</td></tr>
    <tr><td class="label">Session</td><td>{{ $snapshot['jury']['session']['label'] ?? 'Non renseignée' }}</td></tr>
    <tr><td class="label">Parcours</td><td>{{ $snapshot['jury']['parcours']['label'] ?? 'Non renseigné' }}</td></tr>
    <tr><td class="label">Classe</td><td>{{ $snapshot['jury']['class']['label'] ?? 'Non renseignée' }}</td></tr>
    <tr><td class="label">Observations</td><td>{{ $snapshot['jury']['observations'] ?: 'Aucune observation' }}</td></tr>
</table>

<h2>2. Composition et signatures</h2>
<table class="signature">
    @foreach(array_chunk($snapshot['members'], 2) as $memberRow)
        <tr>
            @foreach($memberRow as $member)
                <td>
                    <strong>{{ $member['name'] }}</strong><br>
                    <span class="muted">{{ ucfirst($member['role']) }} · {{ $member['present'] ? 'Présent' : 'Absent' }}</span><br>
                    @php
                        $sig = $member['signature_data'] ?? '';
                        $sigOk = is_string($sig) && str_starts_with($sig, 'data:image/');
                    @endphp
                    @if($sigOk)
                        <img src="{{ $sig }}" alt="Signature"><br>
                        @if(!empty($member['signed_at']))
                            <span class="muted">Signé le {{ \Carbon\Carbon::parse($member['signed_at'])->format('d/m/Y à H:i') }}</span>
                        @endif
                    @elseif(!empty($member['signed_at']))
                        <span class="muted">Présence signée le {{ \Carbon\Carbon::parse($member['signed_at'])->format('d/m/Y à H:i') }}</span>
                    @else
                        <span class="muted">Signature non requise</span>
                    @endif
                </td>
            @endforeach
            @if(count($memberRow) === 1)<td></td>@endif
        </tr>
    @endforeach
</table>

<h2>3. Décisions de la cohorte</h2>
<table class="data">
    <thead><tr><th>Matricule</th><th>Étudiant</th><th class="center">Moyenne</th><th class="center">Crédits</th><th>Décision</th><th>Vote et observation</th></tr></thead>
    <tbody>
        @foreach($snapshot['decisions'] as $decision)
            <tr>
                <td>{{ $decision['matricule'] }}</td>
                <td>{{ trim(($decision['last_name'] ?? '').' '.($decision['first_names'] ?? '')) }}</td>
                <td class="center">{{ $decision['average'] !== null ? number_format((float) $decision['average'], 2, ',', ' ') : 'N/A' }}</td>
                <td class="center">{{ $decision['credits'] }}/{{ $decision['expected_credits'] }}</td>
                <td>{{ strtoupper(str_replace('_', ' ', $decision['decision'])) }}@if($decision['mention'])<br><span class="muted">{{ str_replace('_', ' ', $decision['mention']) }}</span>@endif</td>
                <td>{{ $decision['vote'] ? str_replace('_', ' ', $decision['vote']) : 'Décision automatique' }}@if($decision['override_reason'])<br>{{ $decision['override_reason'] }}@endif</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h2>4. Statistiques et règles appliquées</h2>
<table class="info">
    <tr><td class="label">Effectif délibéré</td><td>{{ $snapshot['statistics']['total'] }}</td></tr>
    <tr><td class="label">Moyenne de la cohorte</td><td>{{ number_format((float) ($snapshot['statistics']['average'] ?? 0), 2, ',', ' ') }}</td></tr>
    <tr><td class="label">Décisions modifiées par le jury</td><td>{{ $snapshot['statistics']['overrides'] }}</td></tr>
    <tr><td class="label">Seuil de validation</td><td>{{ $snapshot['rules']['validation_threshold'] }}/20</td></tr>
    <tr><td class="label">Crédits attendus</td><td>{{ $snapshot['rules']['expected_credits'] }}</td></tr>
    <tr><td class="label">Profil de règles</td><td>{{ $snapshot['rules']['profile_version'] }}</td></tr>
</table>

<div class="legal">
    Ce document est archivé avec son empreinte SHA-256. Toute correction exige une nouvelle version explicitement reliée à la précédente. Les versions révoquées ou remplacées ne peuvent plus être téléchargées ni vérifiées publiquement.
</div>

<div class="footer">
    {{ $snapshot['institution']['name'] ?? config('app.name', 'KLASSCI') }} · {{ $snapshot['document']['reference'] }} · {{ $snapshot['issuance']['template_version'] }}
</div>
</body>
</html>
