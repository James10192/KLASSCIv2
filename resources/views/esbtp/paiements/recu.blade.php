<!DOCTYPE html>
<html>
<head>
    @include('pdf.partials.theme')
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reçu de Paiement - {{ $paiement->numero_recu }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: white;
        }
        
        .container {
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            padding: 15px;
        }
        
        /* En-tête moderne */
        .receipt-header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #1e40af;
            padding-bottom: 10px;
        }
        
        .receipt-logo {
            max-width: 80px;
            margin-bottom: 8px;
        }
        
        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
            text-transform: uppercase;
            color: #1e40af;
        }
        
        .receipt-subtitle {
            font-size: 14px;
            margin-bottom: 4px;
            color: #64748b;
        }
        
        /* Numéro de reçu avec style moderne */
        .receipt-number {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
            border: 2px solid #1e40af;
            padding: 8px;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 6px;
            color: #1e40af;
        }
        
        /* Sections d'informations */
        .info-section {
            margin-bottom: 15px;
        }
        
        .info-title {
            font-weight: bold;
            margin-bottom: 6px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            color: #1e40af;
            font-size: 12px;
        }
        
        /* Tables modernisées */
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .receipt-table th,
        .receipt-table td {
            padding: 6px 8px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            font-size: 10px;
        }
        
        .receipt-table th {
            background: #f8fafc;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .receipt-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Détails de paiement avec style moderne */
        .payment-details {
            margin: 15px 0;
            border: 2px solid #1e40af;
            padding: 12px;
            border-radius: 8px;
            background: linear-gradient(135deg, #fefefe, #f8fafc);
        }
        
        .payment-title {
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
            color: #1e40af;
            padding: 6px;
            background: rgba(30, 64, 175, 0.1);
            border-radius: 4px;
        }
        
        /* Montant avec mise en valeur */
        .amount-display {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 12px 0;
            color: #059669;
            padding: 12px;
            background: rgba(5, 150, 105, 0.1);
            border-radius: 6px;
            border: 2px solid #10b981;
        }
        
        .amount-words {
            text-align: center;
            font-style: italic;
            margin-top: 8px;
            color: #64748b;
            padding: 8px;
            background: #f8fafc;
            border-radius: 4px;
            font-size: 10px;
        }
        
        /* Section signatures modernisée */
        .signature-section {
            margin-top: 20px;
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        
        .signature-box {
            display: table-cell;
            width: 50%;
            border-top: 2px solid #1e40af;
            padding-top: 8px;
            text-align: center;
            min-height: 40px;
            vertical-align: top;
        }
        
        .signature-box:first-child {
            padding-right: 15px;
        }
        
        .signature-box:last-child {
            padding-left: 15px;
        }
        
        .signature-label {
            font-weight: bold;
            margin-bottom: 6px;
            color: #1e40af;
            font-size: 11px;
        }
        
        .signature-value {
            color: #64748b;
            font-size: 10px;
        }
        
        /* Pied de page modernisé */
        .receipt-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
        }
        
        .footer-warning {
            margin-bottom: 8px;
            font-weight: bold;
            color: #dc2626;
            font-size: 9px;
        }
        
        .footer-contact {
            color: #64748b;
            line-height: 1.4;
        }
        
        /* Badge de statut si nécessaire */
        .status-badge {
            display: inline-block;
            padding: 3px 6px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .status-badge.success {
            background: rgba(5, 150, 105, 0.1);
            color: #059669;
            border: 1px solid #10b981;
        }
        
        .status-badge.warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
            border: 1px solid #fbbf24;
        }
        
        .status-badge.danger {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 1px solid #ef4444;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête moderne -->
        <div class="receipt-header">
            @if(isset($settings['show_logo']) && $settings['show_logo'] && isset($settings['logo_base64']))
                <img src="{{ $settings['logo_base64'] }}" alt="Logo École" class="receipt-logo">
            @endif
            <div class="receipt-title">{{ $settings['school_name'] ?? 'Ecole Spéciale du Bâtiment et des Travaux Publics' }}</div>
            <div class="receipt-subtitle">Reçu de Paiement</div>
        </div>

        <!-- Numéro de reçu -->
        <div class="receipt-number">
            REÇU N° {{ $paiement->numero_recu }}
        </div>

        <!-- Informations étudiant -->
        <div class="info-section">
            <div class="info-title">Informations de l'Étudiant</div>
            <table class="receipt-table">
                <tr>
                    <th width="40%">Matricule</th>
                    <td>{{ $paiement->etudiant->matricule }}</td>
                </tr>
                <tr>
                    <th>Nom et Prénoms</th>
                    <td>{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Filière</th>
                    <td>{{ $paiement->inscription->filiere->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Niveau</th>
                    <td>{{ $paiement->inscription->niveauEtude->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Année Universitaire</th>
                    <td>{{ $paiement->inscription->anneeUniversitaire->libelle ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <!-- Détails du paiement -->
        <div class="payment-details">
            <div class="payment-title">Détails du Paiement</div>
            <table class="receipt-table">
                <tr>
                    <th width="40%">Date de paiement</th>
                    <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Motif</th>
                    <td>{{ $paiement->motif }}</td>
                </tr>
                @php
                    $categoryInfo = null;
                    $categoryColors = [
                        'academic' => 'success',
                        'service' => 'warning', 
                        'administrative' => 'info'
                    ];
                    $categoryIcons = [
                        'academic' => 'graduation-cap',
                        'service' => 'cogs',
                        'administrative' => 'file-alt'
                    ];
                    
                    // D'abord essayer avec le nouveau système
                    if ($paiement->fraisCategory) {
                        $categoryInfo = [
                            'name' => $paiement->fraisCategory->name,
                            'type' => $paiement->fraisCategory->category_type ?? 'academic',
                        ];
                    }
                    // Fallback sur l'ancien système
                    elseif ($paiement->categorie) {
                        $categoryInfo = [
                            'name' => $paiement->categorie->nom ?? 'Catégorie ancienne',
                            'type' => $paiement->categorie->nom && str_contains(strtolower($paiement->categorie->nom), 'cantine') ? 'service' : 'academic',
                        ];
                    }
                    // Fallback sur le motif
                    elseif ($paiement->motif) {
                        $motifLower = strtolower($paiement->motif);
                        $type = 'academic';
                        if (str_contains($motifLower, 'cantine') || str_contains($motifLower, 'transport')) {
                            $type = 'service';
                        } elseif (str_contains($motifLower, 'documentation') || str_contains($motifLower, 'examen')) {
                            $type = 'administrative';
                        }
                        $categoryInfo = [
                            'name' => $paiement->motif,
                            'type' => $type,
                        ];
                    }
                    
                    $color = $categoryColors[$categoryInfo['type'] ?? 'academic'] ?? 'secondary';
                    $typeLabel = [
                        'academic' => 'Académique',
                        'service' => 'Service',
                        'administrative' => 'Administratif'
                    ][$categoryInfo['type'] ?? 'academic'] ?? 'Académique';
                @endphp
                @if($categoryInfo)
                <tr>
                    <th>Catégorie</th>
                    <td>
                        {{ $categoryInfo['name'] }}
                        <span class="status-badge {{ $color }}" style="margin-left: 8px;">{{ $typeLabel }}</span>
                    </td>
                </tr>
                @endif
                @if($paiement->tranche)
                <tr>
                    <th>Tranche</th>
                    <td>{{ $paiement->tranche }}</td>
                </tr>
                @endif
                <tr>
                    <th>Mode de paiement</th>
                    <td>{{ $paiement->mode_paiement }}</td>
                </tr>
                @if($paiement->reference_paiement)
                <tr>
                    <th>Référence</th>
                    <td>{{ $paiement->reference_paiement }}</td>
                </tr>
                @endif
                <tr>
                    <th>Statut</th>
                    <td>
                        <span class="status-badge {{ $paiement->status === 'validé' ? 'success' : ($paiement->status === 'en_attente' ? 'warning' : 'danger') }}">
                            {{ $paiement->status_formatte }}
                        </span>
                    </td>
                </tr>
            </table>

            <!-- Montant avec style moderne -->
            <div class="amount-display">
                Montant: {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
            </div>

            <div class="amount-words">
                {{ ucfirst(\App\Services\NumberToWords::convert($paiement->montant)) }} Francs CFA
            </div>
        </div>

        <!-- Signatures modernisées -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Date d'émission</div>
                <div class="signature-value">
                    {{ $paiement->date_validation ? $paiement->date_validation->format('d/m/Y') : date('d/m/Y') }}
                </div>
            </div>

            <div class="signature-box">
                <div class="signature-label">Signature et Cachet</div>
                <div class="signature-value">
                    {{ $paiement->validatedBy ? $paiement->validatedBy->name : 'Le Comptable' }}
                </div>
            </div>
        </div>

        <!-- Pied de page modernisé -->
        <div class="receipt-footer">
            <div class="footer-warning">
                Ce reçu est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
            </div>
            <div class="footer-contact">
                {{ $settings['school_name'] ?? 'ESBTP' }} - {{ $settings['school_address'] ?? 'BP 2541 Yamoussoukro' }}<br>
                Email: {{ $settings['school_email'] ?? 'esbtp@aviso.ci' }} - Tél: {{ $settings['school_phone'] ?? '30 64 39 93' }}
            </div>
        </div>
    </div>
</body>
</html>
