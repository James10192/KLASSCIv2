<!DOCTYPE html>
<html lang="fr">
<head>
    @include('pdf.partials.theme')
    @php
        $pdfSettings    = \App\Helpers\SettingsHelper::getPdfSettings();
        $sfHeaderBg     = $pdfSettings['header_bg_color']   ?? '#0453cb';
        $sfHeaderText   = $pdfSettings['header_text_color'] ?? '#ffffff';
        $sfPrimary      = $pdfSettings['primary_color']     ?? $sfHeaderBg;
        $sfText         = $pdfSettings['text_color']        ?? '#1f2937';
        $sfMuted        = '#6b7280';
        $sfBorder       = '#e5e7eb';
    @endphp
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Situation Financière - {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: "Helvetica", "Arial", sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
            color: {{ $sfText }};
            line-height: 1.4;
            background: white;
        }

        .container {
            max-width: 100%;
            padding: 12px 15px;
        }

        /* ── En-tête établissement ───────────────────────────── */
        .doc-header {
            background-color: {{ $sfHeaderBg }};
            color: {{ $sfHeaderText }};
            border-radius: 10px;
            padding: 16px 20px;
            text-align: center;
            margin-bottom: 14px;
        }

        .doc-header-logo img {
            max-height: 40px;
            max-width: 90px;
            margin-bottom: 7px;
            filter: brightness(0) invert(1);
        }

        .doc-school-name {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: {{ $sfHeaderText }};
            margin-bottom: 4px;
        }

        .doc-school-meta {
            font-size: 8px;
            opacity: 0.88;
            color: {{ $sfHeaderText }};
            margin-bottom: 10px;
        }

        /* Bandeau titre dans l'en-tête */
        .doc-title-band {
            background-color: rgba(255,255,255,0.18);
            border-radius: 6px;
            padding: 7px 12px;
            margin-top: 8px;
        }

        .doc-title-band-label {
            font-size: 12px;
            font-weight: 700;
            color: {{ $sfHeaderText }};
            letter-spacing: 0.06em;
            margin-bottom: 6px;
        }

        /* Grille étudiant/année dans l'en-tête */
        .hdr-info-grid {
            display: table;
            width: 100%;
            font-size: 9px;
        }
        .hdr-info-row { display: table-row; }
        .hdr-info-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 2px 4px;
            color: {{ $sfHeaderText }};
        }
        .hdr-badge {
            background-color: rgba(255,255,255,0.25);
            border-radius: 8px;
            display: inline-block;
            padding: 2px 8px;
            margin-top: 2px;
            font-weight: 700;
            color: {{ $sfHeaderText }};
        }

        /* ── KPI cards ───────────────────────────────────────── */
        .kpi-section {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 5px;
        }
        .kpi-row { display: table-row; }
        .kpi-card {
            display: table-cell;
            width: 25%;
            padding: 8px 6px;
            text-align: center;
            background-color: white;
            border: 1px solid {{ $sfBorder }};
            border-top: 3px solid {{ $sfPrimary }};
            vertical-align: middle;
        }
        .kpi-title {
            font-size: 7px;
            font-weight: 700;
            color: {{ $sfMuted }};
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
        }
        .kpi-value {
            font-size: 13px;
            font-weight: 800;
            color: {{ $sfPrimary }};
            margin-bottom: 2px;
        }
        .kpi-value.negative { color: #dc3545; }
        .kpi-value.zero     { color: #16a34a; }
        .kpi-desc {
            font-size: 7px;
            color: {{ $sfMuted }};
        }

        /* ── Section title ───────────────────────────────────── */
        .section-title {
            font-size: 10px;
            font-weight: 700;
            color: {{ $sfPrimary }};
            margin: 14px 0 7px;
            padding-bottom: 3px;
            border-bottom: 1px solid {{ $sfBorder }};
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ── Tableau étudiant (photo + infos) ────────────────── */
        .student-info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 9px;
        }
        .student-info-table td {
            padding: 5px 8px;
            border: 1px solid {{ $sfBorder }};
            vertical-align: top;
        }
        .cell-label {
            font-weight: 600;
            color: {{ $sfText }};
            background-color: #f8fafc;
            width: 20%;
        }
        .cell-value { color: {{ $sfText }}; }
        .cell-label-parent {
            font-weight: 600;
            background-color: #fef9ec;
            color: {{ $sfText }};
            width: 20%;
        }

        /* Placeholder photo */
        .photo-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            background-color: #f3f4f6;
            border: 2px solid {{ $sfPrimary }};
            text-align: center;
            vertical-align: middle;
            font-size: 9px;
            color: {{ $sfMuted }};
            line-height: 80px;
        }

        .photo-real {
            width: 80px;
            height: 80px;
            border-radius: 4px;
            object-fit: cover;
            border: 2px solid {{ $sfPrimary }};
        }

        .matricule-label {
            margin-top: 5px;
            font-weight: 700;
            font-size: 8px;
            text-align: center;
            color: {{ $sfText }};
        }

        /* ── Badges statut ───────────────────────────────────── */
        .badge-status {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
            color: white;
        }
        .badge-active   { background-color: #16a34a; }
        .badge-inactive { background-color: {{ $sfMuted }}; }

        /* ── Tableaux données ────────────────────────────────── */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            font-size: 10px;
        }
        .doc-table th {
            background-color: {{ $sfHeaderBg }};
            color: {{ $sfHeaderText }};
            border: 1px solid {{ $sfHeaderBg }};
            padding: 6px 8px;
            font-weight: 700;
            font-size: 9px;
        }
        .doc-table td {
            padding: 5px 8px;
            border: 1px solid {{ $sfBorder }};
            text-align: left;
        }
        .doc-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        .amount { text-align: right; font-weight: 600; }
        .amount-positive { color: #16a34a; }
        .amount-negative { color: #dc3545; }
        .amount-neutral  { color: {{ $sfText }}; }

        /* Badges frais */
        .badge-mandatory { background-color: #dc3545; color: white; padding: 1px 4px; border-radius: 6px; font-size: 7px; }
        .badge-optional  { background-color: #0dcaf0; color: white; padding: 1px 4px; border-radius: 6px; font-size: 7px; }
        .badge-reliquat  { background-color: #f59e0b; color: white; padding: 1px 4px; border-radius: 6px; font-size: 7px; }

        /* Badges paiement */
        .badge-paye    { background-color: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 8px; font-size: 8px; font-weight: 600; }
        .badge-partiel { background-color: #fef9c3; color: #854d0e; padding: 2px 6px; border-radius: 8px; font-size: 8px; font-weight: 600; }
        .badge-impaye  { background-color: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 8px; font-size: 8px; font-weight: 600; }

        .no-data {
            text-align: center;
            color: {{ $sfMuted }};
            font-style: italic;
            padding: 12px;
            background-color: #f8fafc;
            margin: 8px 0;
        }

        /* ── Pied de page ────────────────────────────────────── */
        .doc-footer {
            margin-top: 22px;
            text-align: center;
            color: {{ $sfMuted }};
            font-size: 8px;
            border-top: 1px solid {{ $sfBorder }};
            padding-top: 10px;
        }

        .footer-alert-danger { color: #dc3545; font-weight: 700; }
        .footer-alert-ok     { color: #16a34a; font-weight: 700; }

        /* page-break */
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="container">

    {{-- ── En-tête établissement ── --}}
    <div class="doc-header">
        @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
            <div class="doc-header-logo">
                <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}" alt="Logo">
            </div>
        @endif

        <div class="doc-school-name">{{ $etablissement['nom'] ?? 'KLASSCI' }}</div>

        @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
        <div class="doc-school-meta">
            @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
            @if($etablissement['telephone'] && $etablissement['adresse']) &nbsp;|&nbsp; @endif
            @if($etablissement['telephone'])Tél : {{ $etablissement['telephone'] }}@endif
            @if($etablissement['email'] && ($etablissement['adresse'] || $etablissement['telephone'])) &nbsp;|&nbsp; @endif
            @if($etablissement['email'])Email : {{ $etablissement['email'] }}@endif
        </div>
        @endif

        <div class="doc-title-band">
            <div class="doc-title-band-label">SITUATION FINANCIÈRE</div>
            <div class="hdr-info-grid">
                <div class="hdr-info-row">
                    <div class="hdr-info-cell">
                        <div>Étudiant</div>
                        <span class="hdr-badge">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</span>
                    </div>
                    <div class="hdr-info-cell">
                        <div>Année universitaire</div>
                        <span class="hdr-badge">{{ $inscription->anneeUniversitaire->name }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── KPI cards ── --}}
    <div class="kpi-section">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-title">Total Attendu</div>
                <div class="kpi-value">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }}</div>
                <div class="kpi-desc">FCFA</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Total Payé</div>
                <div class="kpi-value amount-positive">{{ number_format($statistiques['total_paye'], 0, ',', ' ') }}</div>
                <div class="kpi-desc">FCFA</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Solde Restant</div>
                <div class="kpi-value {{ $statistiques['solde_restant'] > 0 ? 'negative' : 'zero' }}">
                    {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }}
                </div>
                <div class="kpi-desc">FCFA</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-title">Progression</div>
                <div class="kpi-value">{{ $statistiques['pourcentage_paye'] }}%</div>
                <div class="kpi-desc">Complété</div>
            </div>
        </div>
    </div>

    {{-- ── Informations de l'étudiant ── --}}
    <div class="section-title">Informations de l'étudiant</div>
    <table class="student-info-table">
        <tr>
            <td rowspan="{{ $inscription->etudiant->parents && $inscription->etudiant->parents->count() > 0 ? '5' : '4' }}"
                style="width:100px; text-align:center; vertical-align:middle; padding:10px; border:1px solid {{ $sfBorder }};">
                @php
                    $sfPhotoPath = null;
                    if ($inscription->etudiant->photo) {
                        foreach ([
                            storage_path('app/public/photos/etudiants/' . $inscription->etudiant->photo),
                            storage_path('app/public/' . $inscription->etudiant->photo),
                        ] as $sfCandidate) {
                            if (file_exists($sfCandidate)) { $sfPhotoPath = $sfCandidate; break; }
                        }
                    }
                @endphp
                @if($sfPhotoPath)
                    @php $sfPhotoSrc = 'data:' . (mime_content_type($sfPhotoPath) ?: 'image/jpeg') . ';base64,' . base64_encode(file_get_contents($sfPhotoPath)); @endphp
                    <img src="{{ $sfPhotoSrc }}" alt="Photo" class="photo-real">
                @else
                    <div class="photo-placeholder">Photo</div>
                @endif
                <div class="matricule-label">{{ $inscription->etudiant->matricule }}</div>
            </td>
            <td class="cell-label">Genre</td>
            <td class="cell-value">{{ $inscription->etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
            <td class="cell-label">Lieu de naissance</td>
            <td class="cell-value">{{ $inscription->etudiant->lieu_naissance ?? 'Non renseigné' }}</td>
        </tr>
        <tr>
            <td class="cell-label">Date de naissance</td>
            <td class="cell-value">{{ $inscription->etudiant->date_naissance ? \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}</td>
            <td class="cell-label">Téléphone</td>
            <td class="cell-value">{{ $inscription->etudiant->telephone ?? 'Non renseigné' }}</td>
        </tr>
        <tr>
            <td class="cell-label">Email</td>
            <td class="cell-value" style="font-size:8px;">{{ $inscription->etudiant->email ?? 'Non renseigné' }}</td>
            <td class="cell-label">Statut</td>
            <td class="cell-value">
                <span class="badge-status {{ $inscription->status == 'active' ? 'badge-active' : 'badge-inactive' }}">
                    {{ ucfirst($inscription->status) }}
                </span>
            </td>
        </tr>
        <tr>
            <td class="cell-label">Adresse</td>
            <td colspan="3" class="cell-value">{{ $inscription->etudiant->adresse ?? 'Non renseigné' }}</td>
        </tr>
        @if($inscription->etudiant->parents && $inscription->etudiant->parents->count() > 0)
        @php $parent = $inscription->etudiant->parents->first(); @endphp
        <tr>
            <td class="cell-label-parent">Contact parent</td>
            <td class="cell-value">{{ $parent->nom ?? 'Non renseigné' }} {{ $parent->prenoms ?? '' }}</td>
            <td class="cell-label-parent">Tél. parent</td>
            <td class="cell-value">{{ $parent->telephone ?? 'Non renseigné' }}</td>
        </tr>
        @endif
    </table>

    {{-- ── Détail des frais souscrits ── --}}
    <div class="section-title">Détail des frais souscrits</div>
    @if($fraisSouscrits->count() > 0)
    <table class="doc-table">
        <thead>
            <tr>
                <th>Catégorie de frais</th>
                <th style="text-align:center;">Type</th>
                <th style="text-align:right;">Montant attendu</th>
                <th style="text-align:right;">Montant payé</th>
                <th style="text-align:right;">Solde</th>
                <th style="text-align:center;">Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fraisSouscrits as $frais)
            @php
                $montantPaye = $inscription->paiements
                    ->where('frais_category_id', $frais->frais_category_id)
                    ->where('status', 'validé')
                    ->where(function($p) { return $p->type_paiement != 'reliquat' || is_null($p->type_paiement); })
                    ->sum('montant');
                $solde = $frais->amount - $montantPaye;
            @endphp
            <tr>
                <td>{{ $frais->fraisCategory->name ?? 'Non renseigné' }}</td>
                <td style="text-align:center;">
                    @if($frais->fraisCategory->is_mandatory)
                        <span class="badge-mandatory">Obligatoire</span>
                    @else
                        <span class="badge-optional">Optionnel</span>
                    @endif
                </td>
                <td class="amount amount-neutral">{{ number_format($frais->amount, 0, ',', ' ') }} FCFA</td>
                <td class="amount amount-positive">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</td>
                <td class="amount {{ $solde > 0 ? 'amount-negative' : 'amount-positive' }}">{{ number_format($solde, 0, ',', ' ') }} FCFA</td>
                <td style="text-align:center;">
                    @if($solde <= 0)
                        <span class="badge-paye">Soldé</span>
                    @elseif($montantPaye > 0)
                        <span class="badge-partiel">Partiel</span>
                    @else
                        <span class="badge-impaye">Impayé</span>
                    @endif
                </td>
            </tr>
            @endforeach

            @if($reliquatsEntrants->count() > 0)
                @foreach($reliquatsEntrants as $reliquat)
                    @if($reliquat->solde_restant > 0)
                    <tr style="background-color:#fef9ec;">
                        <td>
                            {{ $reliquat->fraisSubscription->fraisCategory->name ?? 'Non renseigné' }}<br>
                            <small style="color:{{ $sfMuted }};">Reliquat {{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-reliquat">Reliquat</span>
                        </td>
                        <td class="amount amount-neutral">{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</td>
                        <td class="amount amount-positive">{{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA</td>
                        <td class="amount" style="color:#d97706;">{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</td>
                        <td style="text-align:center;"><span class="badge-partiel">Reliquat</span></td>
                    </tr>
                    @endif
                @endforeach
            @endif
        </tbody>
    </table>
    @else
    <div class="no-data">Aucun frais souscrit pour cette inscription.</div>
    @endif

    {{-- ── Historique des paiements ── --}}
    <div class="section-title">Historique des paiements</div>
    @if($inscription->paiements->count() > 0)
    <table class="doc-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Catégorie</th>
                <th>Mode</th>
                <th style="text-align:right;">Montant</th>
                <th style="text-align:center;">Statut</th>
                <th>Référence</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inscription->paiements as $paiement)
            <tr>
                <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'Non renseigné' }}</td>
                <td>
                    {{ $paiement->fraisCategory->name ?? 'Non renseigné' }}
                    @if($paiement->type_paiement === 'reliquat')
                        <br><span class="badge-reliquat">Reliquat</span>
                    @endif
                </td>
                <td>{{ ucfirst($paiement->mode_paiement ?? 'Non renseigné') }}</td>
                <td class="amount amount-positive">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                <td style="text-align:center;">
                    @if($paiement->status === 'validé')
                        <span class="badge-paye">Validé</span>
                    @elseif($paiement->status === 'en_attente')
                        <span class="badge-partiel">En attente</span>
                    @else
                        <span class="badge-impaye">{{ ucfirst($paiement->status) }}</span>
                    @endif
                </td>
                <td>{{ $paiement->numero_recu ?? 'Non renseigné' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">Aucun paiement enregistré pour cette inscription.</div>
    @endif

    {{-- ── Pied de page ── --}}
    <div class="doc-footer">
        <strong>Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}</strong><br>
        {{ $etablissement['nom'] ?? 'KLASSCI' }} — Système de Gestion des Inscriptions<br>
        @if($statistiques['solde_restant'] > 0)
            <span class="footer-alert-danger">
                ATTENTION : Solde restant à payer : {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA
            </span>
        @else
            <span class="footer-alert-ok">
                Situation financière à jour — Tous les frais sont soldés
            </span>
        @endif
    </div>

</div>
</body>
</html>
