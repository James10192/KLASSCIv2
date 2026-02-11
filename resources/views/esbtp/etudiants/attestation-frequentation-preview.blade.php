@extends('layouts.app')

@section('title', 'Prévisualisation Attestation de Fréquentation - ' . $etudiant->nom . ' ' . $etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
@php
    $pdfSettings = \App\Helpers\SettingsHelper::getPdfSettings();
    $pdfHeaderBg = $pdfSettings['header_bg_color'] ?? '#0453cb';
    $pdfHeaderText = $pdfSettings['header_text_color'] ?? '#ffffff';
    $pdfText = $pdfSettings['text_color'] ?? '#1f2937';
@endphp
<style>
    .preview-toolbar h4,
    .preview-toolbar h4 i {
        color: #1e293b !important;
    }

    .preview-toolbar small,
    .preview-toolbar .text-muted {
        color: #64748b !important;
    }

    .preview-toolbar {
        background: var(--surface) !important;
        border-color: var(--border) !important;
    }
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

    .preview-content .document-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        opacity: 0.08;
        width: 60%;
        z-index: 0;
        text-align: center;
    }

    .preview-content .document-watermark img {
        max-width: 100%;
    }

    .preview-content .document-content {
        position: relative;
        z-index: 1;
    }

    .certificat-document {
        color: {{ $pdfText }};
    }

    .certificat-header,
    .certificat-school-name,
    .certificat-address {
        color: {{ $pdfHeaderText }} !important;
    }

    .certificat-divider {
        background-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-title {
        background-color: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-highlight,
    .signature-title,
    .certificat-signature {
        color: {{ $pdfHeaderText }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .detail-label {
        color: {{ $pdfHeaderText }} !important;
    }

    .detail-value {
        color: {{ $pdfText }} !important;
    }

    .student-details {
        background-color: {{ $pdfHeaderBg }} !important;
        border-left-color: {{ $pdfHeaderText }} !important;
    }

    .signature-name {
        color: {{ $pdfHeaderText }} !important;
    }

    /* Modern administrative look (preview) */
    .certificat-header {
        background: {{ $pdfHeaderBg }};
        color: {{ $pdfHeaderText }};
        border-radius: 12px;
        padding: 18px 20px;
        border-bottom: none;
    }

    .certificat-school-name,
    .certificat-address {
        color: {{ $pdfHeaderText }} !important;
    }

    .certificat-title {
        background: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
        border-color: {{ $pdfHeaderText }} !important;
        border-radius: 12px;
        font-size: 22px;
        letter-spacing: 0.5px;
    }

    .certificat-divider {
        height: 2px;
        background: {{ $pdfHeaderText }};
    }

    /* Final overrides to match PDF theme */
    .certificat-document,
    .certificat-header,
    .certificat-school-name,
    .certificat-address,
    .certificat-title,
    .certificat-highlight,
    .signature-title,
    .signature-name,
    .detail-label {
        color: {{ $pdfHeaderText }} !important;
    }

    .certificat-title {
        background-color: {{ $pdfHeaderBg }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-divider {
        background-color: {{ $pdfHeaderText }} !important;
    }
    
    /* Styles pour l'attestation - similaires au certificat */
    .certificat-document {
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.6;
        color: #333;
        padding: 30px;
        max-width: 750px;
        margin: 0 auto;
    }
    
    .certificat-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 3px solid var(--primary);
        padding-bottom: 20px;
    }
    
    .certificat-logo {
        max-width: 100px;
        margin-bottom: 15px;
    }
    
    .certificat-school-name {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 8px;
        text-transform: uppercase;
        color: {{ $pdfHeaderText }};
    }
    
    .certificat-address {
        font-size: 12px;
        color: {{ $pdfHeaderText }};
        margin-bottom: 5px;
    }
    
    .certificat-divider {
        height: 6px;
        background: {{ $pdfHeaderText }};
        margin: 20px 0;
    }
    
    .certificat-title {
        font-size: 28px;
        font-weight: bold;
        text-align: center;
        border: 3px double {{ $pdfHeaderText }};
        border-radius: 10px;
        padding: 15px;
        margin: 30px auto;
        max-width: 90%;
        background: {{ $pdfHeaderBg }};
        position: relative;
        text-transform: uppercase;
        color: {{ $pdfHeaderText }};
    }
    
    .certificat-title::before {
        border: 1px solid {{ $pdfHeaderText }};
    }
    
    .certificat-content {
        margin: 30px 0;
        line-height: 1.8;
        font-size: 16px;
        text-align: justify;
    }
    
    .certificat-content p {
        margin-bottom: 15px;
    }
    
    .certificat-highlight {
        font-weight: bold;
        color: {{ $pdfHeaderText }};
        text-decoration: underline;
    }

    .student-info {
        margin: 20px 0;
        line-height: 1.8;
    }

    .student-details {
        margin: 15px 0;
        padding: 15px;
        background-color: {{ $pdfHeaderBg }};
        border-left: 4px solid {{ $pdfHeaderText }};
        border-radius: var(--radius-small);
    }

    .detail-row {
        margin-bottom: 10px;
        display: flex;
        align-items: flex-start;
    }

    .detail-label {
        font-weight: bold;
        min-width: 200px;
        color: {{ $pdfHeaderText }};
    }

    .detail-value {
        flex: 1;
        color: {{ $pdfText }};
    }

    .status-options {
        margin: 15px 0;
        font-style: italic;
        font-size: 14px;
        background-color: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: var(--radius-small);
        padding: 10px;
        text-align: center;
    }
    
    .certificat-footer {
        margin-top: 50px;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
    }
    
    .certificat-date {
        flex: 1;
        text-align: left;
        font-style: italic;
        color: var(--text-secondary);
    }
    
    .certificat-signature {
        flex: 1;
        text-align: right;
        border-top: 2px solid var(--primary);
        padding-top: 15px;
        min-height: 80px;
    }
    
    .signature-title {
        font-weight: bold;
        margin-bottom: 10px;
        color: var(--primary);
    }
    
    .signature-name {
        color: var(--text-secondary);
        font-style: italic;
        margin-top: 20px;
    }

    .signature-note {
        text-align: center;
        font-size: 12px;
        font-style: italic;
        color: var(--text-secondary);
        margin: 15px 0;
    }
    
    .certificat-note {
        margin-top: 40px;
        text-align: center;
        font-size: 11px;
        font-style: italic;
        color: var(--text-secondary);
        border-top: 1px solid #ddd;
        padding-top: 15px;
    }
    
    @media print {
        .preview-toolbar {
            display: none;
        }
        
        .preview-content {
            border: none;
            box-shadow: none;
        }
        
        .certificat-document {
            padding: 0;
        }
    }

    /* Overrides PDF theme for document area */
    .certificat-document {
        color: {{ $pdfText }} !important;
        --primary: {{ $pdfHeaderText }};
        --text-secondary: {{ $pdfText }};
        --text: {{ $pdfText }};
    }

    .certificat-header,
    .certificat-school-name,
    .certificat-address {
        color: {{ $pdfHeaderText }} !important;
    }

    .certificat-divider {
        background-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-title {
        background-color: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-highlight,
    .signature-title,
    .certificat-signature,
    .signature-name,
    .detail-label {
        color: {{ $pdfHeaderText }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .detail-value {
        color: {{ $pdfText }} !important;
    }

    .student-details {
        background-color: {{ $pdfHeaderBg }} !important;
        border-left-color: {{ $pdfHeaderText }} !important;
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
                        Prévisualisation Attestation de Fréquentation
                    </h4>
                    <small class="text-muted">{{ $etudiant->nom }} {{ $etudiant->prenoms }} - {{ $etudiant->matricule }}</small>
                </div>
                
                <div class="preview-actions">
                    <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    
                    <a href="{{ route('esbtp.etudiants.attestation-frequentation', $etudiant->id) }}" class="btn-acasi success">
                        <i class="fas fa-file-pdf me-1"></i>Générer PDF
                    </a>
                    
                    <button onclick="window.print()" class="btn-acasi info">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>

            <!-- Contenu de l'attestation -->
            <div class="preview-content">
                <div class="certificat-document" style="position: relative;">
                    @if(!empty($settings['logo_base64']))
                        <div class="document-watermark">
                            <img src="{{ $settings['logo_base64'] }}" alt="Filigrane logo">
                        </div>
                    @endif

                    <div class="document-content">
                    <!-- En-tête -->
                    <div class="certificat-header">
                        @if(!empty($settings['show_logo']) && !empty($settings['logo_base64']))
                            <img src="{{ $settings['logo_base64'] }}" alt="Logo École" class="certificat-logo">
                        @endif
                        
                        <div class="certificat-school-name">{{ $settings['name'] ?? '' }}</div>
                        
                        @if(!empty($settings['address']))
                            <div class="certificat-address">{{ $settings['address'] }}</div>
                        @endif
                        @if(!empty($settings['phone']) || !empty($settings['email']))
                            <div class="certificat-address">
                                @if(!empty($settings['phone']))Tél: {{ $settings['phone'] }}@endif
                                @if(!empty($settings['phone']) && !empty($settings['email'])) - @endif
                                @if(!empty($settings['email']))Email: {{ $settings['email'] }}@endif
                            </div>
                        @endif
                    </div>

                    <!-- Séparateur décoratif -->
                    <div class="certificat-divider"></div>

                    <!-- Titre -->
                    <div class="certificat-title">
                        Attestation de Fréquentation
                    </div>

                    {{-- Alerte workflow --}}
                    @if(!empty($alerteWorkflow))
                    <div style="background:#fff3cd;border-left:4px solid #f59e0b;padding:10px 14px;margin:10px 0;border-radius:6px;font-size:13px;">
                        <strong>⚠️ Attention :</strong> Aucune inscription active et finalisée trouvée pour cet étudiant.
                        Veuillez <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}">valider et finaliser l'inscription</a> (étape "Étudiant créé") avant de générer l'attestation.
                    </div>
                    @endif

                    <!-- Contenu principal -->
                    <div class="certificat-content">
                        <p>
                        Je soussigné(e), {{ $settings['director_title'] ?? '' }} de {{ $settings['name'] ?? '' }}, atteste que :
                    </p>

                        <div class="student-info">
                            <p>
                                {{ $etudiant->sexe === 'F' ? 'Mme, M., Mlle' : 'M.' }} <span class="certificat-highlight">{{ strtoupper($etudiant->nom) }} {{ strtoupper($etudiant->prenoms) }}</span>
                            </p>

                            @if($etudiant->date_naissance)
                            <p>
                                Né(e) le <span class="certificat-highlight">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                                @if($etudiant->lieu_naissance) 
                                    à <span class="certificat-highlight">{{ strtoupper($etudiant->lieu_naissance) }}</span>
                                @endif
                            </p>
                            @endif
                        </div>

                        <p>
                            Est régulièrement inscrit(e) au titre de l'année scolaire <span class="certificat-highlight">
                            @php
                                $anneeText = $inscription->anneeUniversitaire->name ?? $inscription->anneeUniversitaire->nom ?? $inscription->anneeUniversitaire->libelle ?? '';
                                if (preg_match('/(\d{4}-\d{4})/', $anneeText, $matches)) {
                                    echo $matches[1];
                                } else {
                                    echo $anneeText;
                                }
                            @endphp
                            </span>
                        </p>

                        <div class="student-details">
                            <div class="detail-row">
                                <div class="detail-label">En classe de :</div>
                                <div class="detail-value">{{ $inscription->classe->name ?? ($inscription->niveauEtude->name ?? 'Non renseigné') }}</div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">Filière :</div>
                                <div class="detail-value">{{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}</div>
                            </div>

                            <div class="detail-row">
                                <div class="detail-label">Sous le numéro Matricule :</div>
                                <div class="detail-value">{{ $etudiant->numero_etudiant ?? $etudiant->matricule }}</div>
                            </div>
                        </div>

                        <div class="status-options">
                            <p><strong>Statut* :</strong> Affecté / Non affecté &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <strong>Boursier* :</strong> Oui / Non</p>
                        </div>

                        <p>
                            En foi de quoi, la présente attestation lui est délivrée pour servir et valoir ce que de droit.
                        </p>
                    </div>

                    <!-- Footer avec signature -->
                    <div class="certificat-footer">
                        <div class="certificat-date">
                        <p>Fait à {{ $settings['city'] ?? '' }}, le {{ now()->format('d/m/Y') }}</p>
                        </div>

                        <div class="certificat-signature">
                        <div class="signature-title">{{ $settings['director_title'] ?? '' }}</div>
                        @if(!empty($settings['director_name']))
                            <div class="signature-name">{{ $settings['director_name'] }}</div>
                        @endif
                        </div>
                    </div>

                    <div class="signature-note">
                        *Rayer la mention inutile
                    </div>

                    <!-- Note de bas de page -->
                    <div class="certificat-note">
                        Ce document est un certificat officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
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
