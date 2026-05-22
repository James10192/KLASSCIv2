<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>PV {{ $numero }}</title>
    <style>
        @@page { margin: 1.8cm 1.5cm 2cm 1.5cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; margin: 0; padding: 0; line-height: 1.45; }

        /* En-tête institution */
        .pv-header { border-bottom: 3px solid #0453cb; padding-bottom: 0.6rem; margin-bottom: 1rem; }
        .pv-header-title { font-size: 18px; font-weight: 700; color: #0453cb; text-transform: uppercase; letter-spacing: 1px; }
        .pv-header-sub { font-size: 9.5px; color: #64748b; margin-top: 3px; }

        /* PV badge identifiant */
        .pv-id-bar { background: #0453cb; color: #fff; padding: 0.5rem 1rem; border-radius: 6px; margin-bottom: 1.25rem; display: table; width: 100%; }
        .pv-id-bar-num { font-family: 'Courier New', monospace; font-size: 13px; font-weight: 700; }
        .pv-id-bar-date { font-size: 10px; color: rgba(255,255,255,.85); float: right; }

        /* Sections */
        .pv-section { margin-bottom: 1.1rem; page-break-inside: avoid; }
        .pv-section-title { font-size: 11px; font-weight: 700; color: #0453cb; text-transform: uppercase; letter-spacing: 0.5px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; margin-bottom: 0.5rem; }
        .pv-section-content { padding-left: 0.5rem; }

        /* Tableau infos */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 4px 6px; font-size: 10.5px; vertical-align: top; }
        .info-table td.label { color: #64748b; width: 30%; font-weight: 600; }
        .info-table td.value { color: #1e293b; font-weight: 600; }

        /* Tableau étudiants × décisions */
        .decisions-table { width: 100%; border-collapse: collapse; font-size: 9.5px; margin-top: 0.5rem; }
        .decisions-table th { background: #0453cb; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.3px; }
        .decisions-table td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; color: #1e293b; }
        .decisions-table tr:nth-child(even) td { background: #f8fafc; }
        .decisions-table .dec-cell { font-weight: 700; }
        .decisions-table .dec-admis { color: #047857; }
        .decisions-table .dec-rattrapage { color: #b45309; }
        .decisions-table .dec-ajourne { color: #b91c1c; }
        .decisions-table .dec-exclu { color: #7f1d1d; font-weight: 800; }
        .decisions-table .dec-sous-condition { color: #92400e; }
        .decisions-table .dec-defere { color: #475569; }
        .override-flag { display: inline-block; padding: 1px 4px; border-radius: 3px; background: rgba(245,158,11,.18); color: #92400e; font-size: 8px; font-weight: 700; margin-left: 2px; }

        /* Stats grid */
        .stats-grid { display: table; width: 100%; border-collapse: separate; border-spacing: 4px; }
        .stat-cell { display: table-cell; background: #f8fafc; border: 1px solid #e2e8f0; padding: 8px; text-align: center; width: 16.6%; border-radius: 5px; }
        .stat-value { font-size: 16px; font-weight: 700; color: #0453cb; line-height: 1; }
        .stat-label { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; margin-top: 3px; }

        /* Signatures */
        .sig-grid { display: table; width: 100%; margin-top: 1rem; border-collapse: separate; border-spacing: 10px; }
        .sig-cell { display: table-cell; width: 33%; vertical-align: top; }
        .sig-box { border: 1px solid #cbd5e1; border-radius: 5px; padding: 8px; height: 90px; }
        .sig-role { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; margin-bottom: 4px; }
        .sig-name { font-size: 11px; font-weight: 700; color: #1e293b; }
        .sig-img { max-height: 50px; max-width: 100%; margin-top: 4px; }
        .sig-empty { color: #cbd5e1; font-size: 9px; font-style: italic; }
        .sig-meta { font-size: 8px; color: #94a3b8; margin-top: 4px; }

        /* Footer */
        .footer { position: fixed; bottom: 0.8cm; left: 1.5cm; right: 1.5cm; text-align: center; font-size: 8px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 4px; }
        .footer strong { color: #475569; }

        /* Legal seal */
        .legal-seal { background: #fef3c7; border-left: 3px solid #f59e0b; padding: 8px 10px; margin: 0.5rem 0 1rem; font-size: 9.5px; color: #92400e; }
        .legal-seal strong { color: #78350f; }
    </style>
</head>
<body>

{{-- 1. En-tête institution --}}
<div class="pv-header">
    <div class="pv-header-title">Procès-Verbal de Délibération</div>
    <div class="pv-header-sub">
        @php
            $tenantName = function_exists('config') ? config('app.name', 'KLASSCI') : 'KLASSCI';
        @endphp
        {{ $tenantName }} · Système LMD UEMOA · Conforme directive 03/2007/CM/UEMOA
    </div>
</div>

{{-- 2. ID + date --}}
<div class="pv-id-bar">
    <span class="pv-id-bar-num"><i class="fas fa-stamp"></i> N° {{ $numero }}</span>
    <span class="pv-id-bar-date">Généré le {{ $generated_at->format('d/m/Y à H:i') }}</span>
</div>

{{-- 3. Informations jury --}}
<div class="pv-section">
    <div class="pv-section-title">1. Identification du jury</div>
    <div class="pv-section-content">
        <table class="info-table">
            <tr><td class="label">Libellé</td><td class="value">{{ $jury->libelle }}</td></tr>
            <tr><td class="label">Année universitaire</td><td class="value">{{ $jury->anneeUniversitaire?->libelle ?? '—' }}</td></tr>
            <tr><td class="label">Date du jury</td><td class="value">{{ $jury->date_jury?->format('l d F Y') ?? '—' }}</td></tr>
            <tr><td class="label">Parcours</td><td class="value">{{ $jury->parcours?->nom ?? 'Tous parcours' }}</td></tr>
            <tr><td class="label">Classe</td><td class="value">{{ $jury->classe?->name ?? '—' }}</td></tr>
            <tr><td class="label">Semestre</td><td class="value">{{ $jury->semestre ? 'S' . $jury->semestre : '—' }}</td></tr>
            <tr><td class="label">Session liée</td><td class="value">{{ $jury->session?->libelle ?? '—' }}</td></tr>
        </table>
    </div>
</div>

{{-- 4. Composition du jury --}}
<div class="pv-section">
    <div class="pv-section-title">2. Composition du jury</div>
    <div class="pv-section-content">
        @if($jury->membres->isEmpty())
        <p style="color:#94a3b8;font-style:italic;">Aucun membre enregistré.</p>
        @else
        <table class="info-table" style="border:1px solid #e2e8f0;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="padding:5px 8px;text-align:left;font-size:9px;text-transform:uppercase;color:#475569;">Nom</th>
                    <th style="padding:5px 8px;text-align:left;font-size:9px;text-transform:uppercase;color:#475569;">Rôle</th>
                    <th style="padding:5px 8px;text-align:left;font-size:9px;text-transform:uppercase;color:#475569;">Présent</th>
                    <th style="padding:5px 8px;text-align:left;font-size:9px;text-transform:uppercase;color:#475569;">Signé</th>
                </tr>
            </thead>
            @foreach($jury->membres as $m)
            <tr>
                <td style="padding:4px 8px;">{{ $m->user?->name ?? '—' }}</td>
                <td style="padding:4px 8px;text-transform:capitalize;">{{ $m->role }}</td>
                <td style="padding:4px 8px;">{{ $m->present ? 'Oui' : 'Non' }}</td>
                <td style="padding:4px 8px;">{{ $m->signature_at ? '✓ ' . $m->signature_at->format('d/m H:i') : '—' }}</td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
</div>

{{-- 5. Statistiques --}}
<div class="pv-section">
    <div class="pv-section-title">3. Synthèse statistique</div>
    <div class="pv-section-content">
        <div class="stats-grid">
            <div class="stat-cell"><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Étudiants</div></div>
            <div class="stat-cell"><div class="stat-value">{{ $stats['admis'] }}</div><div class="stat-label">Admis</div></div>
            <div class="stat-cell"><div class="stat-value">{{ $stats['admission_rattrapage'] }}</div><div class="stat-label">Rattrapage</div></div>
            <div class="stat-cell"><div class="stat-value">{{ $stats['ajourne'] }}</div><div class="stat-label">Ajournés</div></div>
            <div class="stat-cell"><div class="stat-value">{{ $stats['admis_sous_condition'] }}</div><div class="stat-label">Sous cond.</div></div>
            <div class="stat-cell"><div class="stat-value">{{ $stats['exclu'] + $stats['defere'] }}</div><div class="stat-label">Exclus/Déférés</div></div>
        </div>
        @if($stats['moyenne_promo'])
        <p style="margin-top:8px;font-size:10px;color:#475569;"><strong>Moyenne promo :</strong> {{ number_format((float) $stats['moyenne_promo'], 2) }} / 20 · <strong>Overrides jury :</strong> {{ $stats['overrides'] }}</p>
        @endif
    </div>
</div>

{{-- 6. Tableau décisions individuelles --}}
<div class="pv-section">
    <div class="pv-section-title">4. Décisions individuelles</div>
    <div class="pv-section-content">
        @if($jury->decisions->isEmpty())
        <p style="color:#94a3b8;font-style:italic;">Aucune décision enregistrée.</p>
        @else
        <table class="decisions-table">
            <thead>
                <tr><th>#</th><th>Étudiant</th><th>Moy.</th><th>Crédits</th><th>Décision</th><th>Mention</th><th>Vote / Motif</th></tr>
            </thead>
            @foreach($jury->decisions as $i => $d)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ trim(($d->etudiant?->nom ?? '') . ' ' . ($d->etudiant?->prenom ?? '')) ?: '#' . $d->etudiant_id }}</td>
                <td>{{ $d->moyenne_generale !== null ? number_format((float) $d->moyenne_generale, 2) : '—' }}</td>
                <td>{{ $d->credits_obtenus }}/{{ $d->credits_attendus }}</td>
                <td class="dec-cell dec-{{ str_replace('_', '-', $d->decision) }}">
                    {{ str_replace('_', ' ', $d->decision) }}
                    @if($d->override_par_jury)<span class="override-flag">OV</span>@endif
                </td>
                <td>{{ $d->mention ? str_replace('_', ' ', $d->mention) : '—' }}</td>
                <td style="font-size:8.5px;">
                    @if($d->vote_resultat){{ str_replace('_', ' ', $d->vote_resultat) }} @endif
                    @if($d->motif_override)<br><em style="color:#92400e;">{{ \Illuminate\Support\Str::limit($d->motif_override, 80) }}</em>@endif
                </td>
            </tr>
            @endforeach
        </table>
        @endif
    </div>
</div>

{{-- 7. Observations --}}
@if($jury->observations)
<div class="pv-section">
    <div class="pv-section-title">5. Observations du jury</div>
    <div class="pv-section-content" style="background:#f8fafc;padding:10px;border-radius:5px;font-style:italic;">
        {{ $jury->observations }}
    </div>
</div>
@endif

{{-- 8. Cachet légal --}}
<div class="legal-seal">
    <strong>Cachet légal :</strong> Le présent procès-verbal est conservé selon les exigences légales (minimum 5 ans, Côte d'Ivoire MENA).
    Les décisions deviennent officielles après publication par l'autorité académique compétente.
    Toute modification post-génération est interdite (anti-tampering).
</div>

{{-- 9. Signatures --}}
<div class="pv-section" style="page-break-before:auto;">
    <div class="pv-section-title">6. Signatures des membres du jury</div>
    <div class="pv-section-content">
        @if($jury->membres->isEmpty())
        <p style="color:#94a3b8;font-style:italic;">Aucun membre à faire signer.</p>
        @else
        <div class="sig-grid">
        @foreach($jury->membres as $m)
            <div class="sig-cell">
                <div class="sig-box">
                    <div class="sig-role">{{ $m->role }}</div>
                    <div class="sig-name">{{ $m->user?->name ?? '—' }}</div>
                    @if($m->signature_data && str_starts_with((string) $m->signature_data, 'data:image'))
                        <img src="{{ $m->signature_data }}" class="sig-img" alt="Signature">
                    @elseif($m->signature_at)
                        <p style="font-size:9px;color:#047857;margin-top:6px;">✓ Signé électroniquement le {{ $m->signature_at->format('d/m/Y H:i') }}</p>
                    @else
                        <p class="sig-empty">— Non signé —</p>
                    @endif
                    @if($m->signature_at)
                    <div class="sig-meta">
                        {{ $m->signature_at->format('d/m/Y H:i') }}
                        @if($m->signature_ip) · IP {{ $m->signature_ip }}@endif
                    </div>
                    @endif
                </div>
            </div>
            @if(($loop->iteration % 3) === 0 && !$loop->last)
        </div>
        <div class="sig-grid">
            @endif
        @endforeach
        </div>
        @endif
    </div>
</div>

<div class="footer">
    <strong>{{ $tenantName }}</strong> · PV {{ $numero }} · Page <span class="pageNumber"></span>
</div>

</body>
</html>
