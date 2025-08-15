@extends('layouts.app')

@section('title', 'Gestion des Réinscriptions')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Réinscriptions</h1>
                <p class="header-subtitle">Gestion des passages, rattrapages et redoublements</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.reinscription.regles.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-cogs"></i>Règles Académiques
                </a>
                <button class="btn-acasi primary" onclick="exportResults()">
                    <i class="fas fa-download"></i>Exporter
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="card-moderne mb-md" style="border-left: 4px solid var(--danger); background-color: rgba(239, 68, 68, 0.05);">
                <div class="p-lg">
                    <ul style="margin: 0; padding-left: 20px; color: var(--danger);">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="card-moderne mb-md" style="border-left: 4px solid var(--success); background-color: rgba(16, 185, 129, 0.05);">
                <div class="p-lg">
                    <p style="margin: 0; color: var(--success); font-weight: 500;">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Filtre année académique -->
        <div class="card-moderne mb-lg">
            <div class="p-lg">
                <div class="section-title mb-md">
                    <i class="fas fa-filter me-2"></i>Filtres d'analyse
                </div>
                <form method="GET" style="display: flex; gap: var(--space-md); align-items: end;">
                    <div style="flex: 1; max-width: 300px;">
                        <label for="annee_academique" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; font-size: var(--text-small); text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);">Année Académique</label>
                        <select name="annee_academique" id="annee_academique" class="year-selector" style="width: 100%;">
                            <option value="{{ date('Y') . '-' . (date('Y') + 1) }}" 
                                    {{ $anneeAcademique == date('Y') . '-' . (date('Y') + 1) ? 'selected' : '' }}>
                                {{ date('Y') . '-' . (date('Y') + 1) }}
                            </option>
                            <option value="{{ (date('Y') - 1) . '-' . date('Y') }}" 
                                    {{ $anneeAcademique == (date('Y') - 1) . '-' . date('Y') ? 'selected' : '' }}>
                                {{ (date('Y') - 1) . '-' . date('Y') }}
                            </option>
                        </select>
                    </div>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-search"></i>Analyser
                    </button>
                </form>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="kpi-grid mb-xl">
            <div class="card-moderne kpi-card">
                <div class="kpi-title">Passages</div>
                <div class="kpi-value color-success">{{ count($resultats['passages']) }}</div>
                <div class="kpi-trend positive">
                    <i class="fas fa-arrow-up"></i>
                    <span>Admis niveau supérieur</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Rattrapages</div>
                <div class="kpi-value color-warning">{{ count($resultats['rattrapages']) }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Session de rattrapage</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Redoublements</div>
                <div class="kpi-value color-danger">{{ count($resultats['redoublements']) }}</div>
                <div class="kpi-trend negative">
                    <i class="fas fa-redo"></i>
                    <span>Reprise de l'année</span>
                </div>
            </div>

            <div class="card-moderne kpi-card">
                <div class="kpi-title">Non validés</div>
                <div class="kpi-value color-neutral">{{ count($resultats['errors']) }}</div>
                <div class="kpi-trend">
                    <i class="fas fa-user-clock"></i>
                    <span>Inscriptions en cours</span>
                </div>
            </div>
        </div>

        <!-- Onglets pour les différentes catégories -->
        <div class="card-moderne">
            <div class="p-lg" style="border-bottom: 1px solid rgba(0, 0, 0, 0.05);">
                <ul class="nav nav-tabs" id="myTab" role="tablist" style="border: none; display: flex; gap: var(--space-md);">
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link active" id="passages-tab" data-toggle="tab" href="#passages" role="tab" 
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); background-color: rgba(16, 185, 129, 0.1); color: var(--success); font-weight: 600;">
                            <i class="fas fa-arrow-up"></i> Passages ({{ count($resultats['passages']) }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="rattrapages-tab" data-toggle="tab" href="#rattrapages" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-exclamation-triangle"></i> Rattrapages ({{ count($resultats['rattrapages']) }})
                        </a>
                    </li>
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="redoublements-tab" data-toggle="tab" href="#redoublements" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-redo"></i> Redoublements ({{ count($resultats['redoublements']) }})
                        </a>
                    </li>
                    @if(count($resultats['errors']) > 0)
                    <li class="nav-item" style="border: none;">
                        <a class="nav-link" id="errors-tab" data-toggle="tab" href="#errors" role="tab"
                           style="border: none; border-radius: var(--radius-small); padding: var(--space-sm) var(--space-md); color: var(--text-secondary); font-weight: 500;">
                            <i class="fas fa-user-clock"></i> Non validés ({{ count($resultats['errors']) }})
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="p-lg">
                <div class="tab-content" id="myTabContent">
                <!-- Onglet Passages -->
                <div class="tab-pane fade show active" id="passages" role="tabpanel">
                    @include('esbtp.reinscription.partials.liste-etudiants', ['etudiants' => $resultats['passages'], 'type' => 'passage'])
                </div>

                <!-- Onglet Rattrapages -->
                <div class="tab-pane fade" id="rattrapages" role="tabpanel">
                    @include('esbtp.reinscription.partials.liste-etudiants', ['etudiants' => $resultats['rattrapages'], 'type' => 'rattrapage'])
                </div>

                <!-- Onglet Redoublements -->
                <div class="tab-pane fade" id="redoublements" role="tabpanel">
                    @include('esbtp.reinscription.partials.liste-etudiants', ['etudiants' => $resultats['redoublements'], 'type' => 'redoublement'])
                </div>

                <!-- Onglet Erreurs -->
                @if(count($resultats['errors']) > 0)
                <div class="tab-pane fade" id="errors" role="tabpanel">
                    <div class="table-moderne">
                        <table>
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Classe</th>
                                    <th>Erreur</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($resultats['errors'] as $error)
                                <tr>
                                    <td>{{ $error['etudiant']->prenoms }} {{ $error['etudiant']->nom }}</td>
                                    <td>{{ $error['etudiant']->classe->nom ?? 'N/A' }}</td>
                                    <td><span class="table-badge danger">{{ $error['error'] }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Gestion des onglets
$(document).ready(function() {
    // Gérer les clics sur les onglets
    $('a[data-toggle="tab"]').on('click', function (e) {
        e.preventDefault();
        
        // Enlever l'état actif de tous les onglets
        $('a[data-toggle="tab"]').removeClass('active').css({
            'background-color': 'transparent',
            'color': 'var(--text-secondary)'
        });
        
        // Ajouter l'état actif à l'onglet cliqué
        $(this).addClass('active').css({
            'background-color': 'rgba(30, 58, 138, 0.1)',
            'color': 'var(--primary)',
            'font-weight': '600'
        });
        
        // Cacher tous les contenus d'onglets
        $('.tab-pane').removeClass('show active');
        
        // Afficher le contenu de l'onglet cliqué
        const target = $(this).attr('href');
        $(target).addClass('show active');
    });
});

function exportResults() {
    const anneeAcademique = document.getElementById('annee_academique').value;
    
    fetch(`{{ route('esbtp.reinscription.export') }}?annee_academique=${anneeAcademique}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Erreur: ' + data.error);
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'export');
        });
}
</script>
@endsection