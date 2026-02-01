@extends('layouts.app')

@section('title', 'Prévisualisation Certificat - ' . $etudiant->nom . ' ' . $etudiant->prenoms)

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

    .certificat-content table thead th {
        background-color: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-content table td {
        color: {{ $pdfText }} !important;
        background: transparent !important;
        border-color: {{ $pdfHeaderText }} !important;
    }
    
    /* Styles pour le certificat - similaires au PDF mais adaptés pour l'affichage HTML */
    .certificat-document {
        font-family: Arial, sans-serif;
        font-size: 14px;
        line-height: 1.6;
        color: {{ $pdfText }} !important;
        padding: 30px;
        max-width: 750px;
        margin: 0 auto;
        box-sizing: border-box;
    }
    
    .certificat-header {
        text-align: center;
        margin-bottom: 30px;
        border-bottom: 3px solid {{ $pdfHeaderText }};
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
        color: {{ $pdfHeaderText }} !important;
    }
    
    .certificat-address {
        font-size: 12px;
        color: {{ $pdfHeaderText }} !important;
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
        color: {{ $pdfText }};
    }
    
    .certificat-signature {
        flex: 1;
        text-align: right;
        border-top: 2px solid {{ $pdfHeaderText }};
        padding-top: 15px;
        min-height: 80px;
    }
    
    .signature-title {
        font-weight: bold;
        margin-bottom: 10px;
        color: {{ $pdfHeaderText }};
    }
    
    .signature-name {
        color: {{ $pdfHeaderText }};
        font-style: italic;
    }
    
    .certificat-note {
        margin-top: 40px;
        text-align: center;
        font-size: 11px;
        font-style: italic;
        color: {{ $pdfText }};
        border-top: 1px solid {{ $pdfHeaderText }};
        padding-top: 15px;
    }

    .certificat-content table {
        width: 100%;
        table-layout: fixed;
    }

    .certificat-content table th,
    .certificat-content table td {
        word-wrap: break-word;
        overflow-wrap: break-word;
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
    .signature-name {
        color: {{ $pdfHeaderText }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-content table {
        width: 100% !important;
        table-layout: fixed;
    }

    .certificat-content table th,
    .certificat-content table td {
        word-wrap: break-word;
        overflow-wrap: break-word;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-content table thead th {
        background-color: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
    }

    .certificat-content table tbody td {
        color: {{ $pdfText }} !important;
        background: transparent !important;
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

    .certificat-content table th {
        background: {{ $pdfHeaderBg }} !important;
        color: {{ $pdfHeaderText }} !important;
    }

    /* Final overrides to match PDF theme */
    .certificat-document,
    .certificat-header,
    .certificat-school-name,
    .certificat-address,
    .certificat-title,
    .certificat-highlight,
    .signature-title,
    .signature-name {
        color: {{ $pdfHeaderText }} !important;
    }

    .certificat-title {
        background-color: {{ $pdfHeaderBg }} !important;
        border-color: {{ $pdfHeaderText }} !important;
    }

    .certificat-divider {
        background-color: {{ $pdfHeaderText }} !important;
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
                        Prévisualisation Certificat de Scolarité
                    </h4>
                    <small class="text-muted">{{ $etudiant->nom }} {{ $etudiant->prenoms }} - {{ $etudiant->matricule }}</small>
                </div>
                
                <div class="preview-actions">
                    <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    
                    <a href="{{ route('esbtp.etudiants.certificat', $etudiant->id) }}" class="btn-acasi success">
                        <i class="fas fa-file-pdf me-1"></i>Générer PDF
                    </a>
                    
                    <button onclick="window.print()" class="btn-acasi info">
                        <i class="fas fa-print me-1"></i>Imprimer
                    </button>
                </div>
            </div>

            <!-- Contenu du certificat -->
            <div class="preview-content">
                <div class="certificat-document" style="position: relative;">
                    @php
                        use App\Helpers\SettingsHelper;
                        $schoolName = SettingsHelper::get('school_name', 'École Spéciale du Bâtiment et des Travaux Publics');
                        $schoolAddress = SettingsHelper::get('school_address', 'BP 2541 Yamoussoukro');
                        $schoolPhone = SettingsHelper::get('school_phone', '30 64 39 93');
                        $schoolEmail = SettingsHelper::get('school_email', 'esbtp@aviso.ci');
                        $schoolCity = SettingsHelper::get('school_city', 'Yamoussoukro');
                        $directorName = SettingsHelper::get('director_name', '');
                        $directorTitle = SettingsHelper::get('director_title', 'Le Directeur');
                        $showLogo = SettingsHelper::get('certificat_show_logo', '1') === '1';
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
                                    $logoBase64 = 'data:image/' . $extension . ';base64,' . base64_encode($imageData);
                                    break;
                                }
                            }
                        }
                    @endphp

                    @if($logoBase64)
                        <div class="document-watermark">
                            <img src="{{ $logoBase64 }}" alt="Filigrane logo">
                        </div>
                    @endif

                    <div class="document-content">
                    <!-- En-tête -->
                    <div class="certificat-header">
                        @if($showLogo && $logoBase64)
                            <img src="{{ $logoBase64 }}" alt="Logo École" class="certificat-logo">
                        @endif
                        
                        <div class="certificat-school-name">{{ $schoolName }}</div>
                        
                        @if($schoolAddress)
                            <div class="certificat-address">{{ $schoolAddress }}</div>
                        @endif
                        @if($schoolPhone || $schoolEmail)
                            <div class="certificat-address">
                                @if($schoolPhone)Tél: {{ $schoolPhone }}@endif
                                @if($schoolPhone && $schoolEmail) - @endif
                                @if($schoolEmail)Email: {{ $schoolEmail }}@endif
                            </div>
                        @endif
                    </div>

                    <!-- Séparateur décoratif -->
                    <div class="certificat-divider"></div>

                    <!-- Titre du certificat -->
                    <div class="certificat-title">
                        Certificat de Scolarité
                    </div>

                    <!-- Contenu principal -->
                    <div class="certificat-content">
                        <p>
                            Je soussigné(e), {{ $directorTitle }} de {{ $schoolName }}, certifie que :
                        </p>

                        <p>
                            L'étudiant(e) <span class="certificat-highlight">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span>
                        </p>

                        @if($etudiant->date_naissance)
                        <p>
                            Né(e) le <span class="certificat-highlight">{{ $etudiant->date_naissance->format('d/m/Y') }}</span>
                            @if($etudiant->lieu_naissance) 
                                à <span class="certificat-highlight">{{ $etudiant->lieu_naissance }}</span>
                            @endif
                        </p>
                        @endif

                        <p>
                            Matricule : <span class="certificat-highlight">{{ $etudiant->matricule }}</span>
                        </p>

                        <p>
                            Est régulièrement inscrit(e) sur le registre des effectifs de l'année académique :
                        </p>

                        <!-- Tableau des inscriptions -->
                        <div style="margin: 20px 0;">
                            <table style="width: 100%; border-collapse: collapse; border: 2px solid var(--primary); font-size: 14px;">
                                <thead>
                                    <tr style="background-color: #f8fafc;">
                                        <th style="border: 1px solid var(--primary); padding: 8px; text-align: center; font-weight: bold;">Année scolaire</th>
                                        <th style="border: 1px solid var(--primary); padding: 8px; text-align: center; font-weight: bold;">Classe suivie</th>
                                        <th style="border: 1px solid var(--primary); padding: 8px; text-align: center; font-weight: bold;">Niveau d'étude</th>
                                        <th style="border: 1px solid var(--primary); padding: 8px; text-align: center; font-weight: bold;">Filière</th>
                                        <th style="border: 1px solid var(--primary); padding: 8px; text-align: center; font-weight: bold;">Moyenne/20</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($inscriptions as $inscription)
                                    <tr>
                                        <td style="border: 1px solid var(--primary); padding: 8px; text-align: center;">
                                        @php
                                            $rawAcademicYear = $inscription->anneeUniversitaire?->libelle
                                                ?? $inscription->anneeUniversitaire?->name
                                                ?? null;
                                            $displayAcademicYear = $rawAcademicYear
                                                ? (preg_match('/(\d{4}-\d{4})/', $rawAcademicYear, $matches) ? $matches[1] : $rawAcademicYear)
                                                : 'Non renseigné';
                                        @endphp
                                        {{ $displayAcademicYear }}
                                        </td>
                                        <td style="border: 1px solid var(--primary); padding: 8px; text-align: center;">
                                            {{ $inscription->classe->name ?? 'Non renseigné' }}
                                        </td>
                                        <td style="border: 1px solid var(--primary); padding: 8px; text-align: center;">
                                            {{ $inscription->niveauEtude->name ?? 'Non renseigné' }}
                                        </td>
                                        <td style="border: 1px solid var(--primary); padding: 8px; text-align: center;">
                                            {{ strtoupper($inscription->filiere->name ?? 'Non renseigné') }}
                                        </td>
                                        <td style="border: 1px solid var(--primary); padding: 8px; text-align: center;">
                                            @if($inscription->moyenne_generale)
                                                {{ number_format($inscription->moyenne_generale, 2) }}
                                            @else
                                                <!-- Moyenne vide pour l'année en cours -->
                                            @endif
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" style="border: 1px solid var(--primary); padding: 8px; text-align: center;">Aucune inscription trouvée</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <p style="font-style: italic; margin: 15px 0;">
                            Suivant l'horaire du programme complet.
                        </p>

                        <p>
                            Ce certificat est délivré à l'intéressé(e) pour servir et valoir ce que de droit.
                        </p>
                    </div>

                    <!-- Footer avec signature -->
                    <div class="certificat-footer">
                        <div class="certificat-date">
                            <p>Fait à {{ $schoolCity }}, le 13/09/2025</p>
                        </div>

                        <div class="certificat-signature">
                            <div class="signature-title">{{ $directorTitle }}</div>
                            @if($directorName)
                                <div class="signature-name">{{ $directorName }}</div>
                            @endif
                        </div>
                    </div>

                    <!-- Note de bas de page -->
                    <div class="certificat-note">
                        Ce certificat est un document officiel. Toute falsification constitue un délit passible de poursuites judiciaires.
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
