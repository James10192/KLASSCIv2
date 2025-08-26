@extends('layouts.app')

@section('title', 'Détails Réinscription - ' . $analyse['etudiant']->prenom . ' ' . $analyse['etudiant']->nom)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
.progress-container {
    width: 100%;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--space-xs);
    font-weight: 600;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background-color: var(--background);
    border-radius: var(--radius-small);
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.progress-fill {
    height: 100%;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: var(--space-xs);
    color: white;
    font-size: var(--text-small);
    font-weight: 600;
}

.progress-fill.success {
    background: linear-gradient(90deg, var(--success), #34d399);
}

.progress-fill.danger {
    background: linear-gradient(90deg, var(--danger), #f87171);
}

.progress-info {
    margin-top: var(--space-xs);
    text-align: center;
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header moderne -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1>Détails de Réinscription</h1>
                <p class="header-subtitle">{{ $analyse['etudiant']->prenoms }} {{ $analyse['etudiant']->nom }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.reinscription.index') }}?annee_academique={{ $anneeAcademique }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
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

        <div class="reinscription-grid">
            <!-- Informations Étudiant -->
            <div class="card-moderne">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-user"></i>
                        Informations Étudiant
                    </div>
                </div>
                <div class="p-lg" style="text-align: center;">
                    <div style="width: 80px; height: 80px; border-radius: var(--radius-circle); background-color: var(--background); display: inline-flex; align-items: center; justify-content: center; margin-bottom: var(--space-md);">
                        @if($analyse['etudiant']->photo_url)
                            <img src="{{ $analyse['etudiant']->photo_url }}" alt="Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                        @else
                            <i class="fas fa-user fa-2x" style="color: var(--text-muted);"></i>
                        @endif
                    </div>
                    <h3 style="color: var(--primary); margin-bottom: var(--space-lg);">{{ $analyse['etudiant']->prenoms }} {{ $analyse['etudiant']->nom }}</h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-md); text-align: left;">
                        <div>
                            <div style="font-size: var(--text-small); font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs); text-transform: uppercase; letter-spacing: 0.5px;">Matricule</div>
                            <div style="color: var(--text-primary); margin-bottom: var(--space-md);">{{ $analyse['etudiant']->matricule ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="font-size: var(--text-small); font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs); text-transform: uppercase; letter-spacing: 0.5px;">Email</div>
                            <div style="color: var(--text-primary); margin-bottom: var(--space-md);">{{ $analyse['etudiant']->email ?? $analyse['etudiant']->email_personnel ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="font-size: var(--text-small); font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs); text-transform: uppercase; letter-spacing: 0.5px;">Classe</div>
                            <div style="color: var(--text-primary); margin-bottom: var(--space-md);">{{ $analyse['inscription']->classe->name ?? 'N/A' }}</div>
                        </div>
                        <div>
                            <div style="font-size: var(--text-small); font-weight: 600; color: var(--text-secondary); margin-bottom: var(--space-xs); text-transform: uppercase; letter-spacing: 0.5px;">Filière</div>
                            <div style="color: var(--text-primary); margin-bottom: var(--space-md);">{{ $analyse['inscription']->classe->filiere->name ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Situation Financière -->
            <div class="card-moderne">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-wallet"></i>
                        Situation Financière & Réinscription
                    </div>
                </div>
                <div class="p-lg">
                    @php
                        $etudiant = $analyse['etudiant'];
                        $montantAttendu = $etudiant->montant_attendu ?? 0;
                        $montantPaye = $etudiant->montant_paye ?? 0;
                        $soldeRestant = $etudiant->solde_restant ?? 0;
                        $peutReinscrire = $etudiant->peut_reinscrire ?? false;
                        $pourcentsage_paye = $montantAttendu > 0 ? ($montantPaye / $montantAttendu) * 100 : 0;
                    @endphp
                    
                    <!-- KPI Financiers -->
                    <div class="kpi-grid mb-lg">
                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Total Attendu</div>
                            <div class="kpi-value color-primary">{{ number_format($montantAttendu, 0, ',', ' ') }} FCFA</div>
                            <div class="kpi-trend">
                                <i class="fas fa-file-invoice-dollar"></i>
                                <span>Frais totaux</span>
                            </div>
                        </div>

                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Total Payé</div>
                            <div class="kpi-value color-success">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</div>
                            <div class="kpi-trend">
                                <i class="fas fa-check-circle"></i>
                                <span>{{ number_format($pourcentsage_paye, 1) }}% payé</span>
                            </div>
                        </div>

                        @if($soldeRestant > 0)
                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Reste à Payer</div>
                            <div class="kpi-value color-danger">{{ number_format($soldeRestant, 0, ',', ' ') }} FCFA</div>
                            <div class="kpi-trend">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Impayé</span>
                            </div>
                        </div>
                        @elseif($soldeRestant < 0)
                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Trop-Perçu</div>
                            <div class="kpi-value color-warning">{{ number_format(abs($soldeRestant), 0, ',', ' ') }} FCFA</div>
                            <div class="kpi-trend">
                                <i class="fas fa-plus-circle"></i>
                                <span>Excédent</span>
                            </div>
                        </div>
                        @else
                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Statut</div>
                            <div class="kpi-value color-success">Soldé</div>
                            <div class="kpi-trend">
                                <i class="fas fa-check-double"></i>
                                <span>Complet</span>
                            </div>
                        </div>
                        @endif

                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Éligibilité Réinscription</div>
                            <div class="kpi-value {{ $peutReinscrire ? 'color-success' : 'color-danger' }}">
                                {{ $peutReinscrire ? 'Éligible' : 'Non éligible' }}
                            </div>
                            <div class="kpi-trend">
                                <i class="fas {{ $peutReinscrire ? 'fa-thumbs-up' : 'fa-thumbs-down' }}"></i>
                                <span>{{ $peutReinscrire ? 'Peut se réinscrire' : 'Bloqué' }}</span>
                            </div>
                        </div>
                    </div>

                    @if(!$peutReinscrire && $soldeRestant > 0)
                    <div class="card-moderne mb-lg" style="border-left: 4px solid var(--danger); background-color: rgba(239, 68, 68, 0.05);">
                        <div class="p-md">
                            <div style="display: flex; align-items: center; gap: var(--space-md);">
                                <i class="fas fa-exclamation-triangle fa-2x" style="color: var(--danger);"></i>
                                <div>
                                    <div style="font-weight: 600; color: var(--danger); margin-bottom: var(--space-xs);">Réinscription Bloquée</div>
                                    <div style="color: var(--text-primary);">
                                        L'étudiant doit payer au moins 50% de ses frais ({{ number_format($montantAttendu * 0.5, 0, ',', ' ') }} FCFA) pour pouvoir se réinscrire.
                                        <br><strong>Montant minimum requis : {{ number_format($montantAttendu * 0.5 - $montantPaye, 0, ',', ' ') }} FCFA</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @elseif($peutReinscrire)
                    <div class="card-moderne mb-lg" style="border-left: 4px solid var(--success); background-color: rgba(16, 185, 129, 0.05);">
                        <div class="p-md">
                            <div style="display: flex; align-items: center; gap: var(--space-md);">
                                <i class="fas fa-check-circle fa-2x" style="color: var(--success);"></i>
                                <div>
                                    <div style="font-weight: 600; color: var(--success); margin-bottom: var(--space-xs);">Réinscription Autorisée</div>
                                    <div style="color: var(--text-primary);">
                                        L'étudiant a payé suffisamment pour procéder à sa réinscription ({{ number_format($pourcentsage_paye, 1) }}% des frais).
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Barre de progression des paiements -->
                    <div class="progress-container mb-lg">
                        <div class="progress-label">
                            <span>Progression des paiements</span>
                            <span>{{ number_format($pourcentsage_paye, 1) }}%</span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill {{ $pourcentsage_paye >= 50 ? 'success' : 'danger' }}" 
                                 style="width: {{ min($pourcentsage_paye, 100) }}%"></div>
                        </div>
                        <div class="progress-info">
                            <small style="color: var(--text-secondary);">
                                Minimum requis pour réinscription : 50% ({{ number_format($montantAttendu * 0.5, 0, ',', ' ') }} FCFA)
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analyse Académique -->
            <div class="card-moderne">
                <div class="main-card-header">
                    <div class="main-card-title">
                        <i class="fas fa-chart-line"></i>
                        Analyse Académique - {{ $anneeAcademique }}
                    </div>
                </div>
                <div class="p-lg">
                    <div class="kpi-grid mb-lg">
                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Moyenne Générale</div>
                            <div class="kpi-value 
                                @if($analyse['moyenne_generale'] >= 10) color-success
                                @elseif($analyse['moyenne_generale'] >= 8) color-warning
                                @else color-danger
                                @endif">
                                {{ number_format($analyse['moyenne_generale'], 2) }}/20
                            </div>
                            <div class="kpi-trend">
                                <i class="fas fa-percentage"></i>
                                <span>Note globale</span>
                            </div>
                        </div>

                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Matières Échouées</div>
                            <div class="kpi-value color-warning">{{ count($analyse['matieres_echouees']) }}</div>
                            <div class="kpi-trend">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>En difficulté</span>
                            </div>
                        </div>

                        <div class="card-moderne kpi-card">
                            <div class="kpi-title">Décision</div>
                            <div class="kpi-value 
                                @if($analyse['decision'] === 'passage') color-success
                                @elseif($analyse['decision'] === 'rattrapage') color-warning
                                @else color-danger
                                @endif">
                                {{ ucfirst($analyse['decision']) }}
                            </div>
                            <div class="kpi-trend">
                                @switch($analyse['decision'])
                                    @case('passage')
                                        <i class="fas fa-arrow-up"></i>
                                        <span>Admis niveau supérieur</span>
                                        @break
                                    @case('rattrapage')
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>Session de rattrapage</span>
                                        @break
                                    @case('redoublement')
                                        <i class="fas fa-redo"></i>
                                        <span>Reprise de l'année</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                    </div>

                    <!-- Règles appliquées -->
                    <div class="card-moderne mb-lg">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-gavel"></i>
                                Règles Académiques Appliquées
                            </div>
                        </div>
                        <div class="p-lg">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg); margin-bottom: var(--space-lg);">
                                <div>
                                    <div class="info-item mb-md">
                                        <div class="info-label">Moyenne de passage</div>
                                        <div class="info-value">{{ $analyse['regle']->moyenne_passage }}/20</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Moyenne de rattrapage</div>
                                        <div class="info-value">{{ $analyse['regle']->moyenne_rattrapage }}/20</div>
                                    </div>
                                </div>
                                <div>
                                    <div class="info-item mb-md">
                                        <div class="info-label">Max matières rattrapage</div>
                                        <div class="info-value">{{ $analyse['regle']->max_matieres_rattrapage }}</div>
                                    </div>
                                    <div class="info-item">
                                        <div class="info-label">Redoublement autorisé</div>
                                        <div class="info-value">
                                            <span class="status-badge {{ $analyse['regle']->autoriser_redoublement ? 'success' : 'danger' }}">
                                                {{ $analyse['regle']->autoriser_redoublement ? 'Oui' : 'Non' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @if($analyse['regle']->conditions_speciales)
                            <div class="card-moderne" style="border-left: 4px solid var(--info); background-color: rgba(59, 130, 246, 0.05);">
                                <div class="p-md">
                                    <div style="font-weight: 600; color: var(--info); margin-bottom: var(--space-xs);">Conditions spéciales</div>
                                    <div style="color: var(--text-primary);">{{ $analyse['regle']->conditions_speciales }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Matières échouées -->
                    @if(count($analyse['matieres_echouees']) > 0)
                    <div class="card-moderne mb-lg">
                        <div class="main-card-header">
                            <div class="main-card-title" style="color: var(--danger);">
                                <i class="fas fa-exclamation-triangle"></i>
                                Matières Échouées
                            </div>
                        </div>
                        <div class="p-lg">
                            <div class="table-moderne">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Matière</th>
                                            <th class="text-center">Moyenne</th>
                                            <th class="text-center">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($analyse['matieres_echouees'] as $matiere)
                                        <tr>
                                            <td>{{ $matiere['matiere']->name ?? $matiere['matiere']->nom ?? 'N/A' }}</td>
                                            <td style="text-align: center;">
                                                <span class="table-badge danger">
                                                    {{ number_format($matiere['moyenne'], 2) }}/20
                                                </span>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="table-badge danger">Échec</span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Formulaire de réinscription -->
        <div class="card-moderne">
            <div class="main-card-header" style="background-color: rgba(16, 185, 129, 0.1); border-bottom: 1px solid rgba(16, 185, 129, 0.2);">
                <div class="main-card-title" style="color: var(--success);">
                    <i class="fas fa-edit"></i>
                    Finaliser la Réinscription
                </div>
            </div>
            <div class="p-lg">
                <form action="{{ route('esbtp.reinscription.update', $analyse['etudiant']->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg); margin-bottom: var(--space-lg);">
                        <div class="form-group-moderne">
                            <label for="decision" class="form-label-moderne">Décision *</label>
                            <select name="decision" id="decision" class="form-select-moderne" required>
                                <option value="passage" {{ $analyse['decision'] === 'passage' ? 'selected' : '' }}>
                                    Passage au niveau supérieur
                                </option>
                                <option value="rattrapage" {{ $analyse['decision'] === 'rattrapage' ? 'selected' : '' }}>
                                    Rattrapage
                                </option>
                                <option value="redoublement" {{ $analyse['decision'] === 'redoublement' ? 'selected' : '' }}>
                                    Redoublement
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group-moderne">
                            <label for="nouvelle_classe_id" class="form-label-moderne">Nouvelle Classe *</label>
                            <select name="nouvelle_classe_id" id="nouvelle_classe_id" class="form-select-moderne" required>
                                @foreach($classesProposees as $classe)
                                <option value="{{ $classe->id }}" 
                                        {{ $classe->id === $analyse['etudiant']->classe_id ? 'selected' : '' }}>
                                    {{ $classe->name ?? $classe->nom }} - {{ $classe->niveau->name ?? 'N/A' }} {{ $classe->filiere->name ?? 'N/A' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group-moderne mb-lg">
                        <label for="observations" class="form-label-moderne">Observations</label>
                        <textarea name="observations" id="observations" class="form-textarea-moderne" rows="3" 
                                  placeholder="Observations particulières concernant cette réinscription..."></textarea>
                    </div>

                    <div style="display: flex; justify-content: flex-end; gap: var(--space-md);">
                        <button type="button" class="btn-acasi secondary" onclick="history.back()">
                            <i class="fas fa-times"></i>Annuler
                        </button>
                        <button type="submit" class="btn-acasi primary">
                            <i class="fas fa-save"></i>Confirmer la Réinscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection