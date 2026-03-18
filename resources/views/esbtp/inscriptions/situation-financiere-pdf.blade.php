<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Situation Financière - {{ $inscription->etudiant->matricule }}</title>
    @php
        $pdfCfg  = \App\Helpers\SettingsHelper::getPdfSettings();
        $primary = $pdfCfg['primary_color'] ?? '#0453cb';
        $hdrBg   = $pdfCfg['header_bg_color'] ?? $primary;
        $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
    @endphp
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 6px;
            color: #1e293b;
            line-height: 1.3;
            background: white;
        }

        @page {
            margin: 0.7cm;
            size: A4 portrait;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 8px;
            position: relative;
        }

        /* ── Watermark ── */
        .document-watermark {
            position: fixed;
            top: 30%;
            left: 15%;
            width: 70%;
            opacity: 0.14;
            z-index: 0;
            text-align: center;
        }
        .document-watermark img { max-width: 100%; }
        .document-content { position: relative; z-index: 1; }

        /* ── Header Banner ── */
        .header-section {
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        /* ── Card Style ── */
        .card-section {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        /* ── Key-Value Table ── */
        .kv-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kv-table td {
            padding: 7px 12px;
            font-size: 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        .kv-table tr:last-child td {
            border-bottom: none;
        }
        .kv-label {
            width: 28%;
            font-weight: bold;
            color: #64748b;
            background-color: #f8fafc;
            border-right: 1px solid #f1f5f9;
        }
        .kv-value {
            font-weight: 500;
            color: #1e293b;
        }

        /* ── Badge ── */
        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        .badge-reliquat {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        /* ── Data Table ── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .data-table th {
            background-color: {{ $hdrBg }};
            color: {{ $hdrText }};
            padding: 7px 10px;
            font-weight: 700;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: none;
        }
        .data-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }
        .data-table tbody tr:nth-child(even) td {
            background-color: #f8fafc;
        }
        .data-table tfoot td {
            padding: 7px 10px;
            font-weight: 800;
            font-size: 11px;
            border-top: 2px solid {{ $primary }};
            background-color: #f0f4ff;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .green { color: #059669; }
        .red { color: #dc2626; }
        .amber { color: #d97706; }

        /* ── Amount Section ── */
        .amount-section {
            border: 2px solid {{ $primary }};
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        /* ── Footer ── */
        .footer-section {
            margin-top: 16px;
            padding-top: 10px;
            border-top: 2px solid {{ $primary }};
        }
        .footer-warning {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            color: #dc2626;
            margin-bottom: 6px;
        }
        .footer-contact {
            text-align: center;
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }

        /* Reliquat row */
        .row-reliquat td { background-color: #fef9ec !important; }

        /* Photo */
        .photo-box {
            width: 70px;
            height: 70px;
            border-radius: 4px;
            border: 2px solid {{ $primary }};
            object-fit: cover;
            display: block;
            margin: 0 auto;
        }
        .photo-placeholder {
            width: 70px;
            height: 70px;
            border-radius: 4px;
            background-color: #e2e8f0;
            border: 2px solid {{ $primary }};
            text-align: center;
            padding-top: 10px;
            margin: 0 auto;
        }
        .photo-placeholder img {
            width: 44px;
            height: 44px;
            opacity: 0.4;
        }
    </style>
</head>
<body>
    <div class="container">

        @php
            $logoPath   = \App\Helpers\SettingsHelper::get('school_logo');
            $logoBase64Wm = null;
            if ($logoPath) {
                foreach ([
                    storage_path('app/public/' . $logoPath),
                    public_path($logoPath),
                ] as $wmPath) {
                    if (file_exists($wmPath)) {
                        $wmExt       = pathinfo($wmPath, PATHINFO_EXTENSION);
                        $logoBase64Wm = 'data:image/' . $wmExt . ';base64,' . base64_encode(file_get_contents($wmPath));
                        break;
                    }
                }
            }
        @endphp

        @if($logoBase64Wm)
            <div class="document-watermark">
                <img src="{{ $logoBase64Wm }}" alt="">
            </div>
        @endif

        <div class="document-content">

        <!-- ═══ HEADER BANNER ═══ -->
        <div class="header-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="16%" style="background-color: {{ $hdrBg }}; padding: 14px 10px; text-align: center; vertical-align: middle; border-right: 2px solid rgba(255,255,255,0.2);">
                        @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                            <img src="{{ $settings['logo_base64'] }}"
                                 style="max-height: 70px; max-width: 120px;"
                                 alt="Logo">
                        @else
                            <div style="font-size: 32px; font-weight: 900; color: {{ $hdrText }}; opacity: 0.4;">K</div>
                        @endif
                    </td>
                    <td width="84%" style="background-color: {{ $hdrBg }}; padding: 10px 16px; vertical-align: middle;">
                        <div style="font-size: 18px; font-weight: 700; color: {{ $hdrText }}; margin-bottom: 2px;">
                            {{ $etablissement['nom'] ?? 'KLASSCI' }}
                        </div>
                        <div style="font-size: 11px; color: {{ $hdrText }}; opacity: 0.8; margin-bottom: 6px;">
                            @if($etablissement['adresse'] ?? false){{ $etablissement['adresse'] }}@endif
                            @if($etablissement['telephone'] ?? false) &nbsp;|&nbsp; Tél: {{ $etablissement['telephone'] }}@endif
                            @if($etablissement['email'] ?? false) &nbsp;|&nbsp; Email: {{ $etablissement['email'] }}@endif
                        </div>
                        <div style="border-top: 1px solid rgba(255,255,255,0.3); padding-top: 6px;">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td width="55%" style="font-size: 17px; font-weight: 700; color: {{ $hdrText }}; letter-spacing: 0.5px;">
                                        SITUATION FINANCIÈRE
                                    </td>
                                    <td width="45%" style="font-size: 11px; color: {{ $hdrText }}; opacity: 0.75; text-align: right;">
                                        {{ $inscription->anneeUniversitaire->name ?? '' }} &nbsp;|&nbsp; {{ $inscription->classe->name ?? '' }} &nbsp;|&nbsp; {{ now()->format('d/m/Y') }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ KPI SUMMARY ═══ -->
        <div class="amount-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td width="25%" style="text-align: center; padding: 8px 6px; border-right: 1px solid #e2e8f0;">
                        <div style="font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Total Attendu</div>
                        <div style="font-size: 16px; font-weight: 900; color: {{ $primary }};">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }}</div>
                        <div style="font-size: 8px; color: #64748b;">FCFA</div>
                    </td>
                    <td width="25%" style="text-align: center; padding: 8px 6px; border-right: 1px solid #e2e8f0;">
                        <div style="font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Total Payé</div>
                        <div style="font-size: 16px; font-weight: 900; color: #059669;">{{ number_format($statistiques['total_paye'], 0, ',', ' ') }}</div>
                        <div style="font-size: 8px; color: #64748b;">FCFA</div>
                    </td>
                    <td width="25%" style="text-align: center; padding: 8px 6px; border-right: 1px solid #e2e8f0;">
                        <div style="font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Solde Restant</div>
                        <div style="font-size: 16px; font-weight: 900; color: {{ $statistiques['solde_restant'] > 0 ? '#dc2626' : '#059669' }};">{{ number_format($statistiques['solde_restant'], 0, ',', ' ') }}</div>
                        <div style="font-size: 8px; color: #64748b;">FCFA</div>
                    </td>
                    <td width="25%" style="text-align: center; padding: 8px 6px;">
                        <div style="font-size: 8px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 3px;">Progression</div>
                        <div style="font-size: 16px; font-weight: 900; color: {{ $statistiques['pourcentage_paye'] >= 100 ? '#059669' : ($statistiques['pourcentage_paye'] >= 50 ? '#d97706' : '#dc2626') }};">{{ $statistiques['pourcentage_paye'] }}%</div>
                        <div style="font-size: 8px; color: #64748b;">Complété</div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ STUDENT INFO CARD ═══ -->
        <div class="card-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: {{ $primary }}; color: {{ $hdrText }}; padding: 8px 14px; font-size: 14px; font-weight: 700; letter-spacing: 0.3px;">
                        INFORMATIONS DE L'ÉTUDIANT
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0;">
                        <table width="100%" border="0" cellspacing="0" cellpadding="0">
                            <tr>
                                {{-- Photo column --}}
                                <td width="90" style="padding: 10px; text-align: center; vertical-align: top; border-right: 1px solid #f1f5f9;">
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
                                        <img src="{{ $sfPhotoSrc }}" alt="Photo" class="photo-box">
                                    @else
                                        <div class="photo-placeholder">
                                            <img src="data:image/svg+xml;base64,{{ base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#94a3b8"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>') }}" alt="">
                                        </div>
                                    @endif
                                    <div style="margin-top: 4px; font-weight: 700; font-size: 9px; color: #1e293b; font-family: 'Courier New', monospace;">
                                        {{ $inscription->etudiant->matricule }}
                                    </div>
                                </td>
                                {{-- Info columns --}}
                                <td style="padding: 0; vertical-align: top;">
                                    <table class="kv-table">
                                        <tr>
                                            <td class="kv-label">Nom complet</td>
                                            <td class="kv-value" style="font-weight: 700;">{{ strtoupper($inscription->etudiant->nom) }} {{ $inscription->etudiant->prenoms }}</td>
                                        </tr>
                                        <tr>
                                            <td class="kv-label">Date de naissance</td>
                                            <td class="kv-value">{{ $inscription->etudiant->date_naissance ? \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d/m/Y') : 'Non renseigné' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="kv-label">Lieu de naissance</td>
                                            <td class="kv-value">{{ $inscription->etudiant->lieu_naissance ?? 'Non renseigné' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="kv-label">Téléphone</td>
                                            <td class="kv-value">{{ $inscription->etudiant->telephone ?? 'Non renseigné' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="kv-label">Classe</td>
                                            <td class="kv-value" style="font-weight: 700;">{{ $inscription->classe->name ?? 'Non renseigné' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="kv-label">Filière / Niveau</td>
                                            <td class="kv-value">{{ $inscription->classe->filiere->name ?? 'N/A' }} — {{ $inscription->classe->niveau->name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="kv-label">Statut</td>
                                            <td class="kv-value">
                                                <span class="badge badge-{{ $inscription->status == 'active' ? 'success' : 'warning' }}">
                                                    {{ ucfirst($inscription->status) }}
                                                </span>
                                            </td>
                                        </tr>
                                        @if($inscription->etudiant->parents && $inscription->etudiant->parents->count() > 0)
                                        @php $parent = $inscription->etudiant->parents->first(); @endphp
                                        <tr>
                                            <td class="kv-label" style="background-color: #fef9ec;">Contact parent</td>
                                            <td class="kv-value">{{ ($parent->nom ?? '') . ' ' . ($parent->prenoms ?? '') }} — Tél: {{ $parent->telephone ?? 'Non renseigné' }}</td>
                                        </tr>
                                        @endif
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ FEES DETAIL CARD ═══ -->
        <div class="card-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: {{ $primary }}; color: {{ $hdrText }}; padding: 8px 14px; font-size: 14px; font-weight: 700; letter-spacing: 0.3px;">
                        DÉTAIL DES FRAIS — {{ $inscription->anneeUniversitaire->name ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0;">
                        @if($fraisSouscrits->count() > 0)
                        @php
                            $totalAttendu = 0;
                            $totalPaye    = 0;
                        @endphp
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Catégorie de frais</th>
                                    <th>Type</th>
                                    <th style="text-align:right;">Attendu</th>
                                    <th style="text-align:right;">Payé</th>
                                    <th style="text-align:right;">Solde</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fraisSouscrits as $frais)
                                @php
                                    $montantPaye = $inscription->paiements
                                        ->where('frais_category_id', $frais->frais_category_id)
                                        ->where('status', 'validé')
                                        ->filter(fn($p) => $p->type_paiement != 'reliquat' || is_null($p->type_paiement))
                                        ->sum('montant');
                                    $solde = $frais->amount - $montantPaye;
                                    $totalAttendu += $frais->amount;
                                    $totalPaye    += $montantPaye;
                                @endphp
                                <tr>
                                    <td style="font-weight:600;">{{ $frais->fraisCategory->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($frais->fraisCategory->is_mandatory)
                                            <span class="badge badge-danger">Obligatoire</span>
                                        @else
                                            <span class="badge badge-info">Optionnel</span>
                                        @endif
                                    </td>
                                    <td class="text-right" style="font-weight:600;">{{ number_format($frais->amount, 0, ',', ' ') }}</td>
                                    <td class="text-right green" style="font-weight:600;">{{ number_format($montantPaye, 0, ',', ' ') }}</td>
                                    <td class="text-right {{ $solde > 0 ? 'red' : 'green' }}" style="font-weight:700;">{{ number_format($solde, 0, ',', ' ') }}</td>
                                    <td class="text-center">
                                        @if($solde <= 0)
                                            <span class="badge badge-success">Soldé</span>
                                        @elseif($montantPaye > 0)
                                            <span class="badge badge-warning">Partiel</span>
                                        @else
                                            <span class="badge badge-danger">Impayé</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach

                                @if($reliquatsEntrants->count() > 0)
                                    @foreach($reliquatsEntrants as $reliquat)
                                        @if($reliquat->solde_restant > 0)
                                        @php
                                            $totalAttendu += $reliquat->montant_reliquat;
                                            $totalPaye    += $reliquat->montant_regle;
                                        @endphp
                                        <tr class="row-reliquat">
                                            <td>
                                                <span style="font-weight:600;">{{ $reliquat->fraisSubscription->fraisCategory->name ?? '—' }}</span><br>
                                                <small style="color:#64748b;">Reliquat {{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                                            </td>
                                            <td class="text-center"><span class="badge badge-reliquat">Reliquat</span></td>
                                            <td class="text-right" style="font-weight:600;">{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }}</td>
                                            <td class="text-right green" style="font-weight:600;">{{ number_format($reliquat->montant_regle, 0, ',', ' ') }}</td>
                                            <td class="text-right amber" style="font-weight:700;">{{ number_format($reliquat->solde_restant, 0, ',', ' ') }}</td>
                                            <td class="text-center"><span class="badge badge-reliquat">Reliquat</span></td>
                                        </tr>
                                        @endif
                                    @endforeach
                                @endif
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td style="font-weight:800;">TOTAL (FCFA)</td>
                                    <td></td>
                                    <td class="text-right">{{ number_format($totalAttendu, 0, ',', ' ') }}</td>
                                    <td class="text-right green">{{ number_format($totalPaye, 0, ',', ' ') }}</td>
                                    <td class="text-right {{ ($totalAttendu - $totalPaye) > 0 ? 'red' : 'green' }}">{{ number_format($totalAttendu - $totalPaye, 0, ',', ' ') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                        @else
                        <div style="text-align:center; color:#64748b; font-style:italic; padding:14px;">
                            Aucun frais souscrit pour cette inscription.
                        </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ PAYMENT HISTORY CARD ═══ -->
        <div class="card-section">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: {{ $primary }}; color: {{ $hdrText }}; padding: 8px 14px; font-size: 14px; font-weight: 700; letter-spacing: 0.3px;">
                        HISTORIQUE DES PAIEMENTS
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0;">
                        @if($inscription->paiements->count() > 0)
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="text-align:left;">Date</th>
                                    <th style="text-align:left;">Catégorie</th>
                                    <th>Mode</th>
                                    <th style="text-align:right;">Montant</th>
                                    <th>Statut</th>
                                    <th style="text-align:left;">Référence</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($inscription->paiements->sortByDesc('date_paiement') as $paiement)
                                <tr @if($paiement->type_paiement === 'reliquat') class="row-reliquat" @endif>
                                    <td>{{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : '—' }}</td>
                                    <td>
                                        {{ $paiement->fraisCategory->name ?? '—' }}
                                        @if($paiement->type_paiement === 'reliquat')
                                            <span class="badge badge-reliquat">Reliquat</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ ucfirst($paiement->mode_paiement ?? '—') }}</td>
                                    <td class="text-right green" style="font-weight:700;">{{ number_format($paiement->montant, 0, ',', ' ') }}</td>
                                    <td class="text-center">
                                        @if($paiement->status === 'validé')
                                            <span class="badge badge-success">Validé</span>
                                        @elseif($paiement->status === 'en_attente')
                                            <span class="badge badge-warning">En attente</span>
                                        @else
                                            <span class="badge badge-danger">{{ ucfirst($paiement->status) }}</span>
                                        @endif
                                    </td>
                                    <td style="font-size:9px; font-family:'Courier New', monospace;">{{ $paiement->numero_recu ?? '—' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @else
                        <div style="text-align:center; color:#64748b; font-style:italic; padding:14px;">
                            Aucun paiement enregistré pour cette inscription.
                        </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <!-- ═══ STATUS ALERT ═══ -->
        @if($statistiques['solde_restant'] > 0)
        <div style="border: 2px solid #dc2626; border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: #dc2626; color: white; padding: 5px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">
                        Solde Restant à Payer
                    </td>
                </tr>
                <tr>
                    <td style="padding: 10px 14px; text-align: center; background-color: #fef2f2;">
                        <div style="font-size: 24px; font-weight: 900; color: #dc2626; line-height: 1;">
                            {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }}
                        </div>
                        <div style="font-size: 13px; font-weight: 600; color: #dc2626; opacity: 0.7;">FCFA</div>
                    </td>
                </tr>
            </table>
        </div>
        @else
        <div style="border: 2px solid #059669; border-radius: 8px; overflow: hidden; margin-bottom: 10px;">
            <table width="100%" border="0" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="background-color: #059669; color: white; padding: 5px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: center;">
                        Situation Financière
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 14px; text-align: center; background-color: #ecfdf5;">
                        <div style="font-size: 14px; font-weight: 900; color: #059669;">
                            TOUS LES FRAIS SONT SOLDÉS
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        @endif

        <!-- ═══ SIGNATURES ═══ -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom: 10px; margin-top: 28px;">
            <tr>
                <td width="45%" style="text-align: center; vertical-align: top; padding-right: 20px;">
                    <div style="font-size: 12px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 36px;">
                        Date d'émission
                    </div>
                    <div style="border-top: 2px solid {{ $primary }}; padding-top: 8px;">
                        <div style="font-size: 12px; font-weight: 600; color: #1e293b;">
                            {{ $settings['city'] ?? 'Yamoussoukro' }}, le {{ now()->format('d/m/Y') }}
                        </div>
                    </div>
                </td>
                <td width="10%"></td>
                <td width="45%" style="text-align: center; vertical-align: top; padding-left: 20px;">
                    <div style="font-size: 12px; font-weight: 700; color: {{ $primary }}; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 36px;">
                        {{ $settings['director_title'] ?? 'Le Directeur' }}
                    </div>
                    <div style="border-top: 2px solid {{ $primary }}; padding-top: 8px;">
                        @if($settings['director_name'] ?? null)
                        <div style="font-size: 12px; font-weight: 600; color: #1e293b;">
                            {{ $settings['director_name'] }}
                        </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- ═══ FOOTER ═══ -->
        <div class="footer-section">
            <div class="footer-warning">
                Ce document est un relevé officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
            </div>
            <div class="footer-contact">
                {{ $etablissement['nom'] ?? 'KLASSCI' }} — {{ $etablissement['adresse'] ?? '' }}<br>
                Email: {{ $etablissement['email'] ?? '' }} — Tél: {{ $etablissement['telephone'] ?? '' }}
            </div>
        </div>

        </div>{{-- /document-content --}}
    </div>
</body>
</html>
