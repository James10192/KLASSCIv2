@extends('layouts.app')

@section('title', 'Prévisualisation Reçu - ' . $paiement->numero_recu)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
<style>
    :root {
        --rc-primary: #0453cb;
        --rc-primary-dark: #0343ab;
        --rc-primary-light: #5e91de;
        --rc-success: #059669;
        --rc-success-bg: #ecfdf5;
        --rc-danger: #dc2626;
        --rc-text: #1e293b;
        --rc-text-secondary: #64748b;
        --rc-border: #e2e8f0;
        --rc-surface: #f8fafc;
    }

    .preview-container {
        max-width: 860px;
        margin: 0 auto;
    }

    .preview-toolbar {
        background: white;
        border: 1px solid var(--rc-border);
        border-radius: 12px;
        padding: 14px 20px;
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }

    .toolbar-info h4 {
        font-size: 15px;
        font-weight: 700;
        color: var(--rc-text);
        margin: 0;
    }

    .toolbar-info small {
        font-size: 12px;
        color: var(--rc-text-secondary);
    }

    .preview-actions {
        display: flex;
        gap: 8px;
        margin-left: auto;
    }

    .preview-content {
        border-radius: 12px;
        box-shadow: 0 4px 24px rgba(0,0,0,0.08);
        background: white;
        overflow: hidden;
    }

    /* ── Receipt Document ── */
    .rc-document {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        color: var(--rc-text);
        line-height: 1.45;
        position: relative;
    }

    /* ── Watermark ── */
    .rc-watermark {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.10;
        width: 60%;
        z-index: 0;
        pointer-events: none;
        text-align: center;
    }
    .rc-watermark img { max-width: 100%; }

    /* ── Header Banner ── */
    .rc-header {
        background: linear-gradient(135deg, var(--rc-primary) 0%, var(--rc-primary-light) 100%);
        padding: 24px 32px;
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }

    .rc-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }

    .rc-header::after {
        content: '';
        position: absolute;
        bottom: -60%;
        left: 20%;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.04);
        border-radius: 50%;
    }

    .rc-header-logo {
        width: 90px;
        height: 90px;
        background: rgba(255,255,255,0.15);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        border: 1px solid rgba(255,255,255,0.2);
        position: relative;
        z-index: 1;
    }

    .rc-header-logo img {
        max-height: 65px;
        max-width: 65px;
    }

    .rc-header-logo .fallback-letter {
        font-size: 40px;
        font-weight: 900;
        color: white;
        opacity: 0.6;
    }

    .rc-header-info {
        flex: 1;
        position: relative;
        z-index: 1;
    }

    .rc-header-school {
        font-size: 22px;
        font-weight: 700;
        color: white;
        margin-bottom: 2px;
    }

    .rc-header-contact {
        font-size: 14px;
        color: rgba(255,255,255,0.8);
        margin-bottom: 8px;
    }

    .rc-header-divider {
        border-top: 1px solid rgba(255,255,255,0.25);
        padding-top: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .rc-header-doc-title {
        font-size: 18px;
        font-weight: 700;
        color: white;
        letter-spacing: 0.5px;
    }

    .rc-header-doc-date {
        font-size: 12px;
        color: rgba(255,255,255,0.7);
        margin-left: auto;
    }

    /* ── Receipt Number Banner ── */
    .rc-number-banner {
        background: var(--rc-surface);
        border-bottom: 1px solid var(--rc-border);
        padding: 14px 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .rc-number-label {
        font-size: 15px;
        font-weight: 600;
        color: var(--rc-text-secondary);
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .rc-number-value {
        font-size: 26px;
        font-weight: 800;
        color: var(--rc-primary);
        background: white;
        padding: 6px 20px;
        border-radius: 8px;
        border: 2px solid var(--rc-primary);
        letter-spacing: 1px;
    }

    /* ── Body Content ── */
    .rc-body {
        padding: 24px 32px;
        position: relative;
        z-index: 1;
    }

    /* ── Info Cards ── */
    .rc-card {
        border: 1px solid var(--rc-border);
        border-radius: 10px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .rc-card-header {
        background: var(--rc-primary);
        color: white;
        padding: 10px 18px;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .rc-card-header i {
        font-size: 14px;
        opacity: 0.8;
    }

    .rc-card-body {
        padding: 0;
    }

    /* ── Key-Value Rows ── */
    .rc-kv-row {
        display: flex;
        border-bottom: 1px solid #f1f5f9;
    }

    .rc-kv-row:last-child {
        border-bottom: none;
    }

    .rc-kv-label {
        width: 200px;
        flex-shrink: 0;
        padding: 10px 18px;
        font-size: 16px;
        font-weight: 600;
        color: var(--rc-text-secondary);
        background: var(--rc-surface);
        border-right: 1px solid #f1f5f9;
    }

    .rc-kv-value {
        flex: 1;
        padding: 10px 18px;
        font-size: 16px;
        font-weight: 500;
        color: var(--rc-text);
    }

    /* ── Amount Section ── */
    .rc-amount-section {
        margin-bottom: 20px;
        border: 2px solid var(--rc-success);
        border-radius: 12px;
        overflow: hidden;
    }

    .rc-amount-header {
        background: var(--rc-success);
        color: white;
        padding: 8px 18px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: center;
    }

    .rc-amount-body {
        padding: 18px;
        text-align: center;
        background: var(--rc-success-bg);
    }

    .rc-amount-value {
        font-size: 42px;
        font-weight: 900;
        color: var(--rc-success);
        line-height: 1;
        margin-bottom: 4px;
    }

    .rc-amount-currency {
        font-size: 20px;
        font-weight: 600;
        color: var(--rc-success);
        opacity: 0.7;
    }

    .rc-amount-words {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed rgba(5, 150, 105, 0.25);
        font-size: 16px;
        font-style: italic;
        color: var(--rc-text-secondary);
    }

    /* ── Signature Section ── */
    .rc-signatures {
        display: flex;
        gap: 40px;
        margin-bottom: 20px;
        padding-top: 6px;
    }

    .rc-signature-box {
        flex: 1;
        text-align: center;
    }

    .rc-signature-label {
        font-size: 15px;
        font-weight: 700;
        color: var(--rc-primary);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 36px;
    }

    .rc-signature-line {
        border-top: 2px solid var(--rc-primary);
        padding-top: 8px;
    }

    .rc-signature-name {
        font-size: 15px;
        font-weight: 600;
        color: var(--rc-text);
    }

    .rc-signature-date {
        font-size: 14px;
        color: var(--rc-text-secondary);
        margin-top: 2px;
    }

    /* ── Footer ── */
    .rc-footer {
        border-top: 2px solid var(--rc-primary);
        padding: 14px 32px;
        background: var(--rc-surface);
        position: relative;
        z-index: 1;
    }

    .rc-footer-warning {
        text-align: center;
        font-size: 14px;
        font-weight: 700;
        color: var(--rc-danger);
        margin-bottom: 5px;
    }

    .rc-footer-contact {
        text-align: center;
        font-size: 14px;
        color: var(--rc-text-secondary);
    }

    /* ── Badge ── */
    .rc-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .rc-badge-success {
        background: #dcfce7;
        color: #166534;
        border: 1px solid #86efac;
    }

    .rc-badge-warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .rc-badge-danger {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #fca5a5;
    }

    .rc-badge-info {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }

    /* ── Print ── */
    @media print {
        .preview-toolbar { display: none !important; }
        .preview-content { box-shadow: none; border-radius: 0; }
        .rc-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rc-card-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rc-amount-section { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .rc-footer { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { background: white !important; }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="preview-container">
            <!-- Toolbar -->
            <div class="preview-toolbar">
                <div class="toolbar-info">
                    <h4><i class="fas fa-file-invoice me-2"></i>Prévisualisation du Reçu</h4>
                    <small>{{ $paiement->numero_recu }} — {{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</small>
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
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-exclamation-triangle me-1"></i>En attente de validation
                        </span>
                    @endif
                    <button onclick="window.print()" class="btn-acasi info">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>

            <!-- Receipt Document -->
            <div class="preview-content">
                <div class="rc-document" style="position: relative;">
                    @php
                        use App\Helpers\SettingsHelper;
                        $pdfCfg = SettingsHelper::getPdfSettings();
                        $hdrBg = $pdfCfg['header_bg_color'] ?? $pdfCfg['primary_color'] ?? '#0453cb';
                        $hdrText = $pdfCfg['header_text_color'] ?? '#ffffff';
                        $primary = $pdfCfg['primary_color'] ?? '#0453cb';

                        $schoolName = SettingsHelper::get('school_name', 'Ecole Spéciale du Bâtiment et des Travaux Publics');
                        $schoolAddress = SettingsHelper::get('school_address', 'BP 2541 Yamoussoukro');
                        $schoolEmail = SettingsHelper::get('school_email', 'esbtp@aviso.ci');
                        $schoolPhone = SettingsHelper::get('school_phone', '30 64 39 93');
                        $showLogo = SettingsHelper::get('receipt_show_logo', '1') === '1';
                        $logoPath = SettingsHelper::get('school_logo');

                        $logoUrl = null;
                        if ($showLogo && $logoPath) {
                            $fullPath = storage_path('app/public/' . $logoPath);
                            if (file_exists($fullPath)) {
                                $imageData = file_get_contents($fullPath);
                                $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
                                $logoUrl = 'data:image/' . $extension . ';base64,' . base64_encode($imageData);
                            }
                        }
                        if (!$logoUrl) {
                            $fallbackPath = public_path('images/LOGO-KLASSCI-PNG.png');
                            if (file_exists($fallbackPath)) {
                                $logoUrl = 'data:image/png;base64,' . base64_encode(file_get_contents($fallbackPath));
                            }
                        }
                    @endphp

                    <!-- Watermark -->
                    @if($logoUrl)
                    <div class="rc-watermark"><img src="{{ $logoUrl }}" alt=""></div>
                    @endif

                    <!-- Header Banner -->
                    <div class="rc-header" style="background: {{ $hdrBg }};">
                        <div class="rc-header-logo">
                            @if($showLogo && $logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo">
                            @else
                                <span class="fallback-letter">K</span>
                            @endif
                        </div>
                        <div class="rc-header-info">
                            <div class="rc-header-school" style="color: {{ $hdrText }};">{{ $schoolName }}</div>
                            <div class="rc-header-contact" style="color: {{ $hdrText }}; opacity: 0.85;">
                                @if($schoolAddress){{ $schoolAddress }}@endif
                                @if($schoolPhone) &nbsp;|&nbsp; Tél: {{ $schoolPhone }}@endif
                                @if($schoolEmail) &nbsp;|&nbsp; {{ $schoolEmail }}@endif
                            </div>
                            <div class="rc-header-divider">
                                <div class="rc-header-doc-title" style="color: {{ $hdrText }};">REÇU DE PAIEMENT</div>
                                <div class="rc-header-doc-date" style="color: {{ $hdrText }}; opacity: 0.7;">
                                    {{ $paiement->inscription->anneeUniversitaire->name ?? '' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Number -->
                    <div class="rc-number-banner">
                        <span class="rc-number-label">Reçu N°</span>
                        <span class="rc-number-value" style="color: {{ $primary }}; border-color: {{ $primary }};">{{ $paiement->numero_recu }}</span>
                    </div>

                    <div class="rc-body">
                        <!-- Student Info Card -->
                        <div class="rc-card">
                            <div class="rc-card-header" style="background: {{ $primary }};">
                                <i class="fas fa-user-graduate"></i>
                                Informations de l'Étudiant
                            </div>
                            <div class="rc-card-body">
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Matricule</div>
                                    <div class="rc-kv-value" style="font-family: 'Courier New', monospace; font-weight: 700;">{{ $paiement->etudiant->matricule }}</div>
                                </div>
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Nom et Prénoms</div>
                                    <div class="rc-kv-value" style="font-weight: 700;">{{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}</div>
                                </div>
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Filière</div>
                                    <div class="rc-kv-value">{{ $paiement->inscription->filiere->name ?? 'N/A' }}</div>
                                </div>
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Niveau</div>
                                    <div class="rc-kv-value">{{ $paiement->inscription->niveauEtude->name ?? 'N/A' }}</div>
                                </div>
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Année Universitaire</div>
                                    <div class="rc-kv-value">{{ $paiement->inscription->anneeUniversitaire->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Details Card -->
                        <div class="rc-card">
                            <div class="rc-card-header" style="background: {{ $primary }};">
                                <i class="fas fa-receipt"></i>
                                Détails du Paiement
                            </div>
                            <div class="rc-card-body">
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Date de paiement</div>
                                    <div class="rc-kv-value">{{ $paiement->date_paiement->format('d/m/Y') }}</div>
                                </div>
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Motif</div>
                                    <div class="rc-kv-value">{{ $paiement->motif }}</div>
                                </div>
                                @php
                                    $categoryName = null;
                                    if ($paiement->fraisCategory) {
                                        $categoryName = $paiement->fraisCategory->name;
                                    } elseif ($paiement->categorie) {
                                        $categoryName = $paiement->categorie->nom ?? null;
                                    }
                                @endphp
                                @if($categoryName)
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Catégorie de frais</div>
                                    <div class="rc-kv-value">{{ $categoryName }}</div>
                                </div>
                                @endif
                                @if($paiement->tranche)
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Tranche</div>
                                    <div class="rc-kv-value">{{ $paiement->tranche }}</div>
                                </div>
                                @endif
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Mode de paiement</div>
                                    <div class="rc-kv-value">{{ $paiement->mode_paiement }}</div>
                                </div>
                                @if($paiement->reference_paiement)
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Référence</div>
                                    <div class="rc-kv-value" style="font-family: 'Courier New', monospace;">{{ $paiement->reference_paiement }}</div>
                                </div>
                                @endif
                                <div class="rc-kv-row">
                                    <div class="rc-kv-label">Statut</div>
                                    <div class="rc-kv-value">
                                        <span class="rc-badge rc-badge-{{ $paiement->status === 'validé' ? 'success' : ($paiement->status === 'en_attente' ? 'warning' : 'danger') }}">
                                            {{ $paiement->status_formatte }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Amount Section -->
                        <div class="rc-amount-section">
                            <div class="rc-amount-header">
                                <i class="fas fa-coins me-1"></i> Montant du paiement
                            </div>
                            <div class="rc-amount-body">
                                <div class="rc-amount-value">{{ number_format($paiement->montant, 0, ',', ' ') }}</div>
                                <div class="rc-amount-currency">FCFA</div>
                                <div class="rc-amount-words">
                                    {{ ucfirst(\App\Services\NumberToWords::convert($paiement->montant)) }} Francs CFA
                                </div>
                            </div>
                        </div>

                        {{-- Lot 13 — Encaissé par (créateur du paiement) --}}
                        @if($paiement->creator)
                        <div style="margin-top: 12px; margin-bottom: 8px; border: 1px solid {{ $primary }}; border-radius: 6px; overflow: hidden;">
                            <div style="padding: 10px 16px; background-color: #f8fafc; border-left: 4px solid {{ $primary }}; display: flex; align-items: center; gap: 12px;">
                                <span style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; min-width: 130px;">
                                    <i class="fas fa-user-circle me-1"></i>Encaissé par
                                </span>
                                <span style="font-size: 16px; font-weight: 700; color: {{ $primary }};">
                                    {{ $paiement->creator->name }}
                                </span>
                            </div>
                        </div>
                        @endif

                        <!-- Signatures -->
                        <div class="rc-signatures">
                            <div class="rc-signature-box">
                                <div class="rc-signature-label" style="color: {{ $primary }};">Date d'émission</div>
                                <div class="rc-signature-line" style="border-color: {{ $primary }};">
                                    <div class="rc-signature-name">
                                        {{ $paiement->date_validation ? $paiement->date_validation->format('d/m/Y') : date('d/m/Y') }}
                                    </div>
                                </div>
                            </div>
                            <div class="rc-signature-box">
                                <div class="rc-signature-label" style="color: {{ $primary }};">Signature et Cachet</div>
                                <div class="rc-signature-line" style="border-color: {{ $primary }};">
                                    <div class="rc-signature-name">
                                        {{ $paiement->validatedBy ? $paiement->validatedBy->name : 'Le Comptable' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="rc-footer" style="border-color: {{ $primary }};">
                        <div class="rc-footer-warning">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Ce reçu est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
                        </div>
                        <div class="rc-footer-contact">
                            {{ $schoolName }} — {{ $schoolAddress }}
                            — Email: {{ $schoolEmail }} — Tél: {{ $schoolPhone }}
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
window.addEventListener('beforeprint', function() { document.body.classList.add('printing'); });
window.addEventListener('afterprint', function() { document.body.classList.remove('printing'); });
</script>
@endpush
