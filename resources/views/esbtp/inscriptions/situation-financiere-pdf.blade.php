<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situation Financiere - {{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
            color: #333;
            line-height: 1.3;
            background: white;
        }

        .container {
            max-width: 100%;
            background: white;
            padding: 15px;
        }

        /* Header principal */
        .header-section {
            background: #007bff;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 15px;
            -webkit-print-color-adjust: exact;
            color-adjust: exact;
        }

        .header-logo {
            max-height: 40px;
            max-width: 100px;
            margin-bottom: 8px;
            filter: brightness(0) invert(1);
        }

        .school-name {
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .school-info {
            font-size: 8px;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .document-title-section {
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 6px;
            margin-top: 8px;
        }

        .document-title {
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .student-info-grid {
            display: table;
            width: 100%;
            font-size: 9px;
        }

        .student-info-row {
            display: table-row;
        }

        .student-info-cell {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 3px;
        }

        .info-badge {
            background: rgba(255,255,255,0.3);
            padding: 2px 4px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 2px;
        }

        /* KPI Section */
        .kpi-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .kpi-row {
            display: table-row;
        }

        .kpi-card {
            display: table-cell;
            width: 25%;
            padding: 6px;
            text-align: center;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            vertical-align: top;
            font-size: 8px;
        }

        .kpi-card:first-child {
            border-radius: 6px 0 0 6px;
        }

        .kpi-card:last-child {
            border-radius: 0 6px 6px 0;
        }

        .kpi-title {
            font-size: 7px;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
        }

        .kpi-value {
            font-size: 11px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 2px;
        }

        .kpi-desc {
            font-size: 6px;
            color: #9ca3af;
        }

        .student-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 9px;
        }

        .student-info-grid {
            display: table;
            width: 100%;
        }

        .student-info-row {
            display: table-row;
        }

        .student-info-cell {
            display: table-cell;
            padding: 2px 10px 2px 0;
            vertical-align: top;
            width: 50%;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            margin-right: 8px;
        }

        .info-value {
            color: #212529;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #007bff;
            margin: 15px 0 8px 0;
            padding-bottom: 3px;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-box {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .summary-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .summary-label {
            display: table-cell;
            width: 70%;
        }

        .summary-value {
            display: table-cell;
            text-align: right;
            font-weight: 600;
        }

        .summary-total {
            border-top: 1px solid rgba(255,255,255,0.3);
            margin-top: 10px;
            padding-top: 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .progress-bar {
            width: 100%;
            height: 15px;
            background: rgba(255,255,255,0.3);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: rgba(255,255,255,0.9);
            border-radius: 8px;
            text-align: center;
            line-height: 15px;
            font-size: 10px;
            color: #007bff;
            font-weight: bold;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 11px;
        }

        .table th,
        .table td {
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #dee2e6;
        }

        .table th {
            background-color: #007bff;
            color: white;
            font-weight: 600;
            font-size: 10px;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .amount {
            text-align: right;
            font-weight: 600;
        }

        .amount.positive {
            color: #28a745;
        }

        .amount.negative {
            color: #dc3545;
        }

        .status-badge {
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.paye {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.partiel {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge.impaye {
            background: #f8d7da;
            color: #721c24;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 10px 0;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #dee2e6;
            padding-top: 15px;
        }

        .page-break {
            page-break-before: always;
        }

        /* Informations de génération */
        .generation-info {
            text-align: center;
            font-size: 7px;
            color: #6b7280;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e5e7eb;
        }

        .no-data {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 10px 0;
        }

        /* Eviter les coupures de page dans les elements importants */
        .summary-box, .student-info {
            page-break-inside: avoid;
        }

        .section-title {
            page-break-after: avoid;
        }

        /* Print optimizations */
        @media print {
            body {
                background: white;
                padding: 5px;
            }

            .container {
                padding: 10px;
            }

            .header-section {
                margin-bottom: 10px;
            }

            .kpi-section {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header-section">
            @if($etablissement['logo'] && file_exists(storage_path('app/public/' . $etablissement['logo'])))
                <img src="data:image/{{ pathinfo($etablissement['logo'], PATHINFO_EXTENSION) }};base64,{{ base64_encode(file_get_contents(storage_path('app/public/' . $etablissement['logo']))) }}" class="header-logo" alt="Logo">
            @endif

            <div class="school-name">{{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }}</div>

            @if($etablissement['adresse'] || $etablissement['telephone'] || $etablissement['email'])
            <div class="school-info">
                @if($etablissement['adresse']){{ $etablissement['adresse'] }}@endif
                @if($etablissement['telephone'] && $etablissement['adresse']) | @endif
                @if($etablissement['telephone'])Tel: {{ $etablissement['telephone'] }}@endif
                @if($etablissement['email'] && ($etablissement['adresse'] || $etablissement['telephone'])) | @endif
                @if($etablissement['email'])Email: {{ $etablissement['email'] }}@endif
            </div>
            @endif

            <div class="document-title-section">
                <div class="document-title">SITUATION FINANCIERE</div>
                <div class="student-info-grid">
                    <div class="student-info-row">
                        <div class="student-info-cell">
                            <strong>Etudiant:</strong><br>
                            <span class="info-badge">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</span>
                        </div>
                        <div class="student-info-cell">
                            <strong>Annee:</strong><br>
                            <span class="info-badge">{{ $inscription->anneeUniversitaire->name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Section -->
        <div class="kpi-section">
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-title">Total Attendu</div>
                    <div class="kpi-value">{{ number_format($statistiques['total_attendu'], 0, ',', ' ') }}</div>
                    <div class="kpi-desc">FCFA</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Total Paye</div>
                    <div class="kpi-value">{{ number_format($statistiques['total_paye'], 0, ',', ' ') }}</div>
                    <div class="kpi-desc">FCFA</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Solde Restant</div>
                    <div class="kpi-value" style="color: {{ $statistiques['solde_restant'] > 0 ? '#dc3545' : '#28a745' }};">{{ number_format($statistiques['solde_restant'], 0, ',', ' ') }}</div>
                    <div class="kpi-desc">FCFA</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-title">Progression</div>
                    <div class="kpi-value">{{ $statistiques['pourcentage_paye'] }}%</div>
                    <div class="kpi-desc">Complete</div>
                </div>
            </div>
        </div>


    <!-- Informations detaillees de l'etudiant -->
    <div class="section-title">INFORMATIONS DE L'ETUDIANT</div>
    <table class="student-info-table" style="width: 100%; margin-bottom: 15px; border-collapse: collapse;">
        <tr>
            <td rowspan="{{ $inscription->etudiant->parents && $inscription->etudiant->parents->count() > 0 ? '5' : '4' }}" style="width: 120px; text-align: center; vertical-align: top; padding: 10px; border: 1px solid #e5e7eb;">
                @if($inscription->etudiant->photo_url)
                    <img src="{{ $inscription->etudiant->photo_url }}" alt="Photo" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #007bff;">
                @else
                    <div style="width: 100px; height: 100px; border-radius: 50%; background: #f3f4f6; display: flex; align-items: center; justify-content: center; border: 2px solid #007bff;">
                        <i class="fas fa-user" style="font-size: 40px; color: #6b7280;"></i>
                    </div>
                @endif
                <div style="margin-top: 8px; font-weight: bold; font-size: 9px;">{{ $inscription->etudiant->matricule }}</div>
            </td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #f8f9fa;">Genre:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb;">{{ $inscription->etudiant->genre == 'M' ? 'Masculin' : 'Feminin' }}</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #f8f9fa;">Lieu naissance:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb;">{{ $inscription->etudiant->lieu_naissance ?? 'Non renseigne' }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #f8f9fa;">Date naissance:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb;">{{ $inscription->etudiant->date_naissance ? \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d/m/Y') : 'Non renseigne' }}</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #f8f9fa;">Telephone:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb;">{{ $inscription->etudiant->telephone ?? 'Non renseigne' }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #f8f9fa;">Email:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-size: 8px;">{{ $inscription->etudiant->email ?? 'Non renseigne' }}</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #f8f9fa;">Statut:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb;">
                <span style="background: {{ $inscription->status == 'active' ? '#28a745' : '#6c757d' }}; color: white; padding: 2px 6px; border-radius: 4px; font-size: 8px;">
                    {{ ucfirst($inscription->status) }}
                </span>
            </td>
        </tr>
        <tr>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #f8f9fa;">Adresse:</td>
            <td colspan="3" style="padding: 5px 10px; border: 1px solid #e5e7eb; font-size: 9px;">{{ $inscription->etudiant->adresse ?? 'Non renseigne' }}</td>
        </tr>
        @if($inscription->etudiant->parents && $inscription->etudiant->parents->count() > 0)
        @php $parent = $inscription->etudiant->parents->first(); @endphp
        <tr>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #fef3c7;">Contact parent:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb;">{{ $parent->nom ?? 'Non renseigne' }} {{ $parent->prenoms ?? '' }}</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb; font-weight: bold; background: #fef3c7;">Tel. parent:</td>
            <td style="padding: 5px 10px; border: 1px solid #e5e7eb;">{{ $parent->telephone ?? 'Non renseigne' }}</td>
        </tr>
        @endif
    </table>

    <!-- Detail des frais souscrits -->
    <div class="section-title">DETAIL DES FRAIS SOUSCRITS</div>
    @if($fraisSouscrits->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Categorie de Frais</th>
                <th>Type</th>
                <th>Montant Attendu</th>
                <th>Montant Paye</th>
                <th>Solde</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fraisSouscrits as $frais)
            @php
                $montantPaye = $inscription->paiements
                    ->where('frais_category_id', $frais->frais_category_id)
                    ->where('status', 'validé')
                    ->where(function($paiement) {
                        return $paiement->type_paiement != 'reliquat' || is_null($paiement->type_paiement);
                    })
                    ->sum('montant');
                $solde = $frais->amount - $montantPaye;
            @endphp
            <tr>
                <td>{{ $frais->fraisCategory->name ?? 'Non renseigne' }}</td>
                <td style="text-align: center; font-size: 8px;">
                    @if($frais->fraisCategory->is_mandatory)
                        <span style="background: #dc3545; color: white; padding: 2px 4px; border-radius: 6px; font-size: 7px;">Obligatoire</span>
                    @else
                        <span style="background: #0dcaf0; color: white; padding: 2px 4px; border-radius: 6px; font-size: 7px;">Optionnel</span>
                    @endif
                </td>
                <td class="amount">{{ number_format($frais->amount, 0, ',', ' ') }} FCFA</td>
                <td class="amount positive">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</td>
                <td class="amount {{ $solde > 0 ? 'negative' : 'positive' }}">
                    {{ number_format($solde, 0, ',', ' ') }} FCFA
                </td>
                <td>
                    @if($solde <= 0)
                        <span class="status-badge paye">Solde</span>
                    @elseif($montantPaye > 0)
                        <span class="status-badge partiel">Partiel</span>
                    @else
                        <span class="status-badge impaye">Impaye</span>
                    @endif
                </td>
            </tr>
            @endforeach

            {{-- Intégrer les reliquats comme des lignes de frais --}}
            @if($reliquatsEntrants->count() > 0)
                @foreach($reliquatsEntrants as $reliquat)
                    @if($reliquat->solde_restant > 0)
                        <tr style="background-color: #fef3c7;">
                            <td>
                                {{ $reliquat->fraisSubscription->fraisCategory->name ?? 'Non renseigne' }}<br>
                                <small style="color: #6b7280;">Reliquat {{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                            </td>
                            <td style="text-align: center; font-size: 8px;">
                                @if($reliquat->fraisSubscription->fraisCategory->is_mandatory)
                                    <span style="background: #f59e0b; color: white; padding: 2px 4px; border-radius: 6px; font-size: 7px;">Obligatoire</span>
                                @else
                                    <span style="background: #6b7280; color: white; padding: 2px 4px; border-radius: 6px; font-size: 7px;">Optionnel</span>
                                @endif
                            </td>
                            <td class="amount">{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</td>
                            <td class="amount positive">{{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA</td>
                            <td class="amount" style="color: #d97706;">{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</td>
                            <td><span class="status-badge" style="background: #fef3c7; color: #d97706;">Reliquat</span></td>
                        </tr>
                    @endif
                @endforeach
            @endif
        </tbody>
    </table>
    @else
    <div class="no-data">Aucun frais souscrit pour cette inscription.</div>
    @endif


    <!-- Historique des paiements -->
    <div class="section-title">HISTORIQUE DES PAIEMENTS</div>
    @if($inscription->paiements->count() > 0)
    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Categorie</th>
                <th>Mode</th>
                <th>Montant</th>
                <th>Statut</th>
                <th>Reference</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inscription->paiements as $paiement)
            <tr>
                <td>{{ $paiement->date_paiement ? $paiement->date_paiement->format('d/m/Y') : 'Non renseigne' }}</td>
                <td>
                    {{ $paiement->fraisCategory->name ?? 'Non renseigne' }}
                    @if($paiement->type_paiement === 'reliquat')
                        <br><span style="background: #f59e0b; color: white; padding: 1px 3px; border-radius: 4px; font-size: 7px;">Reliquat</span>
                    @endif
                </td>
                <td>{{ ucfirst($paiement->mode_paiement ?? 'Non renseigne') }}</td>
                <td class="amount positive">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                <td>
                    @if($paiement->status === 'validé')
                        <span class="status-badge paye">Validé</span>
                    @elseif($paiement->status === 'en_attente')
                        <span class="status-badge partiel">En attente</span>
                    @else
                        <span class="status-badge impaye">{{ ucfirst($paiement->status) }}</span>
                    @endif
                </td>
                <td>{{ $paiement->numero_recu ?? 'Non renseigne' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="no-data">Aucun paiement enregistre pour cette inscription.</div>
    @endif

        <!-- Informations de génération -->
        <div class="generation-info">
            <strong>Document genere automatiquement le {{ now()->format('d/m/Y a H:i') }}</strong><br>
            {{ $etablissement['nom'] ?? 'ESBTP-yAKRO' }} - Systeme de Gestion des Inscriptions<br>
            @if($statistiques['solde_restant'] > 0)
                <span style="color: #dc3545; font-weight: bold;">
                    ATTENTION: Solde restant a payer: {{ number_format($statistiques['solde_restant'], 0, ',', ' ') }} FCFA
                </span>
            @else
                <span style="color: #28a745; font-weight: bold;">
                    SITUATION FINANCIERE A JOUR - Tous les frais sont soldes
                </span>
            @endif
        </div>
    </div>
</body>
</html>