@extends('layouts.app')

@section('title', 'Prévisualisation Reçu - ' . $paiement->numero_recu)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .preview-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
    }
    
    .preview-toolbar {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-medium);
        padding: var(--space-md);
        margin-bottom: var(--space-lg);
        display: flex;
        justify-content: between;
        align-items: center;
        gap: var(--space-md);
    }
    
    .preview-actions {
        display: flex;
        gap: var(--space-sm);
        margin-left: auto;
    }
    
    .preview-content {
        border: 1px solid #ddd;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-card);
        padding: 0;
        background: white;
        min-height: 800px;
    }
    
    /* Styles pour le reçu - similaires au PDF mais adaptés pour l'affichage HTML */
    .receipt-document {
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.5;
        color: #333;
        padding: 30px;
    }
    
    .receipt-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 2px solid #333;
        padding-bottom: 15px;
    }
    
    .receipt-logo {
        max-width: 120px;
        margin-bottom: 15px;
    }
    
    .receipt-title {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 8px;
        text-transform: uppercase;
        color: var(--primary);
    }
    
    .receipt-subtitle {
        font-size: 18px;
        margin-bottom: 8px;
        color: var(--text-secondary);
    }
    
    .receipt-number {
        font-size: 20px;
        font-weight: bold;
        margin: 25px 0;
        text-align: center;
        border: 2px solid var(--primary);
        padding: 10px;
        background: linear-gradient(135deg, #f8fafc, #e2e8f0);
        border-radius: var(--radius-medium);
        color: var(--primary);
    }
    
    .info-section {
        margin-bottom: 25px;
    }
    
    .info-title {
        font-weight: bold;
        margin-bottom: 10px;
        border-bottom: 1px solid #ccc;
        padding-bottom: 5px;
        color: var(--primary);
        font-size: 16px;
    }
    
    .receipt-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    
    .receipt-table th,
    .receipt-table td {
        padding: 10px;
        text-align: left;
        border: 1px solid #333;
    }
    
    .receipt-table th {
        background-color: #f8fafc;
        font-weight: bold;
        color: var(--primary);
    }
    
    .payment-details {
        margin: 25px 0;
        border: 2px solid var(--primary);
        padding: 20px;
        border-radius: var(--radius-medium);
        background: linear-gradient(135deg, #fefefe, #f8fafc);
    }
    
    .payment-title {
        font-size: 18px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 15px;
        color: var(--primary);
        padding: 8px;
        background: rgba(var(--primary-rgb), 0.1);
        border-radius: var(--radius-small);
    }
    
    .amount-display {
        font-size: 22px;
        font-weight: bold;
        text-align: center;
        margin: 20px 0;
        color: var(--success);
        padding: 15px;
        background: rgba(var(--success-rgb), 0.1);
        border-radius: var(--radius-medium);
    }
    
    .amount-words {
        text-align: center;
        font-style: italic;
        margin-top: 15px;
        color: var(--text-secondary);
        padding: 10px;
        background: #f8fafc;
        border-radius: var(--radius-small);
    }
    
    .signature-section {
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
        gap: 30px;
    }
    
    .signature-box {
        flex: 1;
        border-top: 1px solid #333;
        padding-top: 10px;
        text-align: center;
        min-height: 80px;
    }
    
    .signature-label {
        font-weight: bold;
        margin-bottom: 10px;
        color: var(--primary);
    }
    
    .signature-value {
        color: var(--text-secondary);
    }
    
    .receipt-footer {
        margin-top: 40px;
        text-align: center;
        font-size: 11px;
        color: #666;
        border-top: 1px solid #ddd;
        padding-top: 15px;
    }
    
    .footer-warning {
        margin-bottom: 10px;
        font-weight: bold;
        color: var(--danger);
    }
    
    .footer-contact {
        color: var(--text-secondary);
    }
    
    @media print {
        .preview-toolbar {
            display: none;
        }
        
        .preview-content {
            border: none;
            box-shadow: none;
        }
        
        .receipt-document {
            padding: 0;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="preview-container">
            <!-- Barre d'outils de prévisualisation -->
            <div class="preview-toolbar">
                <div class="toolbar-info">
                    <h4 class="mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Prévisualisation du Reçu
                    </h4>
                    <small class="text-muted">{{ $paiement->numero_recu }} - {{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</small>
                </div>
                
                <div class="preview-actions">
                    <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    
                    @if($paiement->status == 'validé')
                        <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}" class="btn-acasi success">
                            <i class="fas fa-file-pdf me-1"></i>Générer PDF
                        </a>
                    @else
                        <span class="badge bg-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            En attente de validation
                        </span>
                    @endif
                    
                    <button onclick="window.print()" class="btn-acasi info">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>

            <!-- Contenu du reçu -->
            <div class="preview-content">
                <div class="receipt-document">
                    <!-- En-tête -->
                    <div class="receipt-header">
                        @php
                            use App\Helpers\SettingsHelper;
                            $schoolName = SettingsHelper::get('school_name', 'Ecole Spéciale du Bâtiment et des Travaux Publics');
                            $showLogo = SettingsHelper::get('receipt_show_logo', '1') === '1';
                            $logoPath = SettingsHelper::get('school_logo');
                            
                            $logoBase64 = null;
                            if ($showLogo && $logoPath) {
                                $paths = [
                                    storage_path('app/public/' . $logoPath),
                                    public_path($logoPath),
                                    public_path('images/LOGO-KLASSCI-PNG.png'),
                                ];
                                
                                foreach ($paths as $path) {
                                    if (file_exists($path)) {
                                        $imageData = file_get_contents($path);
                                        $extension = pathinfo($path, PATHINFO_EXTENSION);
                                        $logoBase64 = 'data:image/' . $extension . ';base64,' . base64encode($imageData);
                                        break;
                                    }
                                }
                            }
                        @endphp
                        
                        @if($showLogo && $logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Logo École" class="receipt-logo">
                        @endif
                        
                        <div class="receipt-title">{{ $schoolName }}</div>
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
                                <td>{{ $paiement->inscription->anneeUniversitaire->libelle }}</td>
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
                                    <span class="badge bg-{{ $color }} ms-2">{{ $typeLabel }}</span>
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
                                    <span class="badge bg-{{ $paiement->status === 'validé' ? 'success' : ($paiement->status === 'en_attente' ? 'warning' : 'danger') }}">
                                        {{ $paiement->status_formatte }}
                                    </span>
                                </td>
                            </tr>
                        </table>

                        <!-- Montant -->
                        <div class="amount-display">
                            Montant: {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA
                        </div>

                        <div class="amount-words">
                            {{ ucfirst(\App\Services\NumberToWords::convert($paiement->montant)) }} Francs CFA
                        </div>
                    </div>

                    <!-- Signatures -->
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

                    <!-- Pied de page -->
                    <div class="receipt-footer">
                        <div class="footer-warning">
                            Ce reçu est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
                        </div>
                        <div class="footer-contact">
                            @php
                                $schoolAddress = SettingsHelper::get('school_address', 'BP 2541 Yamoussoukro');
                                $schoolEmail = SettingsHelper::get('school_email', 'esbtp@aviso.ci');
                                $schoolPhone = SettingsHelper::get('school_phone', '30 64 39 93');
                            @endphp
                            {{ $schoolName }} - {{ $schoolAddress }} 
                            - Email: {{ $schoolEmail }} - Tél: {{ $schoolPhone }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Gérer l'impression
window.addEventListener('beforeprint', function() {
    document.body.classList.add('printing');
});

window.addEventListener('afterprint', function() {
    document.body.classList.remove('printing');
});
</script>
@endpush