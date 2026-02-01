@extends('layouts.app')

@section('title', 'Prévisualisation Certificat - ' . $etudiant->nom . ' ' . $etudiant->prenoms)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
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
    
    /* Styles pour le certificat - similaires au PDF mais adaptés pour l'affichage HTML */
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
        color: var(--primary);
    }
    
    .certificat-address {
        font-size: 12px;
        color: var(--text-secondary);
        margin-bottom: 5px;
    }
    
    .certificat-divider {
        height: 6px;
        background: repeating-linear-gradient(
            45deg,
            var(--primary),
            var(--primary) 8px,
            #fff 8px,
            #fff 16px
        );
        margin: 20px 0;
    }
    
    .certificat-title {
        font-size: 28px;
        font-weight: bold;
        text-align: center;
        border: 3px double var(--primary);
        border-radius: 10px;
        padding: 15px;
        margin: 30px auto;
        max-width: 90%;
        box-shadow: var(--shadow-card);
        background: linear-gradient(135deg, #ffffff, #f8fafc);
        position: relative;
        text-transform: uppercase;
        color: var(--primary);
    }
    
    .certificat-title::before {
        content: '';
        position: absolute;
        top: -5px;
        left: -5px;
        right: -5px;
        bottom: -5px;
        border: 1px solid var(--primary);
        border-radius: 15px;
        z-index: -1;
        opacity: 0.3;
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
        color: var(--primary);
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
                <div class="certificat-document">
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
