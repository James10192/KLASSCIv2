<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bon de Sortie {{ $numero_bon }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #007bff;
            margin: 0;
            font-weight: bold;
        }

        .header .subtitle {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .header .numero-bon {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-top: 10px;
            background: #f8f9fa;
            padding: 8px 16px;
            border-radius: 5px;
            display: inline-block;
        }

        .info-section {
            margin-bottom: 25px;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 8px 12px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }

        .info-label {
            background-color: #f8f9fa;
            font-weight: bold;
            width: 30%;
            color: #495057;
        }

        .info-value {
            background-color: #fff;
            width: 70%;
        }

        .montant-highlight {
            font-size: 16px;
            font-weight: bold;
            color: #28a745;
        }

        .statut-badge {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .statut-approuve {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .statut-paye {
            background-color: #cce7ff;
            color: #004085;
            border: 1px solid #99d3ff;
        }

        .statut-en-attente {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .statut-rejete {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .description-section {
            margin-top: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            border-radius: 5px;
        }

        .description-section h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #007bff;
        }

        .signatures-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 20px 10px;
            vertical-align: top;
        }

        .signature-box h4 {
            font-size: 12px;
            margin: 0 0 40px 0;
            color: #495057;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10px;
            color: #666;
        }

        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }

        .qr-code {
            float: right;
            margin: 0 0 20px 20px;
            text-align: center;
        }

        .qr-placeholder {
            width: 80px;
            height: 80px;
            border: 2px dashed #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #666;
            margin-bottom: 5px;
        }

        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 60px;
            color: rgba(0, 123, 255, 0.1);
            font-weight: bold;
            z-index: -1;
            pointer-events: none;
        }

        @media print {
            body { margin: 0; padding: 15px; }
            .footer { position: fixed; bottom: 0; }
        }
    </style>
</head>
<body>
    <!-- Watermark selon le statut -->
    @if($bon->statut_workflow === 'approuve')
        <div class="watermark">APPROUVÉ</div>
    @elseif($bon->statut_workflow === 'paye')
        <div class="watermark">PAYÉ</div>
    @elseif($bon->statut_workflow === 'rejete')
        <div class="watermark">REJETÉ</div>
    @endif

    <!-- Header -->
    <div class="header">
        <h1>BON DE SORTIE</h1>
        <div class="subtitle">École Supérieure de Bâtiment et Travaux Publics</div>
        <div class="numero-bon">{{ $numero_bon }}</div>
    </div>

    <!-- QR Code (placeholder) -->
    <div class="qr-code">
        <div class="qr-placeholder">QR CODE</div>
        <div style="font-size: 8px;">{{ $numero_bon }}</div>
    </div>

    <!-- Informations principales -->
    <div class="info-section">
        <div class="info-grid">
            <div class="info-row">
                <div class="info-cell info-label">Libellé</div>
                <div class="info-cell info-value">{{ $bon->libelle }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Montant</div>
                <div class="info-cell info-value">
                    <span class="montant-highlight">{{ number_format($bon->montant, 0, ',', ' ') }} FCFA</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Date de dépense</div>
                <div class="info-cell info-value">{{ \Carbon\Carbon::parse($bon->date_depense)->format('d/m/Y') }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Catégorie</div>
                <div class="info-cell info-value">{{ $bon->categorie->nom ?? 'Non spécifiée' }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Fournisseur</div>
                <div class="info-cell info-value">{{ $bon->fournisseur->nom ?? 'Non spécifié' }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Mode de paiement</div>
                <div class="info-cell info-value">{{ ucfirst($bon->mode_paiement) }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Statut</div>
                <div class="info-cell info-value">
                    @php
                        $statutClass = match($bon->statut_workflow) {
                            'en_attente' => 'statut-en-attente',
                            'approuve' => 'statut-approuve',
                            'paye' => 'statut-paye',
                            'rejete' => 'statut-rejete',
                            default => 'statut-en-attente'
                        };
                        $statutText = match($bon->statut_workflow) {
                            'en_attente' => 'En Attente',
                            'approuve' => 'Approuvé',
                            'paye' => 'Payé',
                            'rejete' => 'Rejeté',
                            default => 'En Attente'
                        };
                    @endphp
                    <span class="statut-badge {{ $statutClass }}">{{ $statutText }}</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Créé par</div>
                <div class="info-cell info-value">{{ $bon->createur->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Date de création</div>
                <div class="info-cell info-value">{{ $bon->created_at->format('d/m/Y H:i') }}</div>
            </div>
            @if($bon->approved_by && $bon->date_approbation)
            <div class="info-row">
                <div class="info-cell info-label">Approuvé par</div>
                <div class="info-cell info-value">{{ $bon->approbateur->name ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-cell info-label">Date d'approbation</div>
                <div class="info-cell info-value">{{ \Carbon\Carbon::parse($bon->date_approbation)->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Description -->
    @if($bon->description)
    <div class="description-section">
        <h3>Description</h3>
        <div>{{ $bon->description }}</div>
    </div>
    @endif

    <!-- Section signatures -->
    <div class="signatures-section">
        <div class="signature-box">
            <h4>Demandeur</h4>
            <div class="signature-line">
                {{ $bon->createur->name ?? 'N/A' }}<br>
                Le {{ $bon->created_at->format('d/m/Y') }}
            </div>
        </div>

        <div class="signature-box">
            <h4>Approbateur</h4>
            <div class="signature-line">
                @if($bon->approved_by)
                    {{ $bon->approbateur->name ?? 'N/A' }}<br>
                    Le {{ \Carbon\Carbon::parse($bon->date_approbation)->format('d/m/Y') }}
                @else
                    En attente d'approbation
                @endif
            </div>
        </div>

        <div class="signature-box">
            <h4>Comptabilité</h4>
            <div class="signature-line">
                @if($bon->statut_workflow === 'paye')
                    Payé<br>
                    Le {{ $bon->updated_at->format('d/m/Y') }}
                @else
                    En attente de paiement
                @endif
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>
            Document généré le {{ $date_generation }} |
            ESBTP - Système de Gestion Comptable |
            Bon N° {{ $numero_bon }}
        </div>
    </div>
</body>
</html>
