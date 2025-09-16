@extends('layouts.app')

@section('title', 'Modifier un Paiement - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier le Paiement</h1>
                <p class="header-subtitle">Modification du paiement #{{ $paiement->numero_recu }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux détails
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger border-start border-danger border-4 mb-4">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-exclamation-circle fs-4"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Erreur de validation</h5>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('esbtp.paiements.update', $paiement->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-sections">
                <!-- Section 1: Informations de l'étudiant -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-user"></i>
                            Informations de l'Étudiant
                        </div>
                        <div class="main-card-subtitle">Données de l'étudiant et de l'inscription (non modifiables)</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Étudiant</label>
                                <input type="text" class="form-input" value="{{ $paiement->etudiant->matricule ?? 'N/A' }} - {{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Inscription</label>
                                <input type="text" class="form-input" value="{{ $paiement->inscription->filiere->name }} - {{ $paiement->inscription->niveauEtude->name }} ({{ $paiement->inscription->anneeUniversitaire->libelle }})" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Informations financières -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-money-bill"></i>
                            Informations Financières
                        </div>
                        <div class="main-card-subtitle">Montant et détails du paiement</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="montant" class="form-label">Montant <span class="text-danger">*</span></label>
                                <div class="input-with-suffix">
                                    <input type="number" name="montant" id="montant" class="form-input @error('montant') error @enderror" min="0" step="1" value="{{ old('montant', $paiement->montant) }}" required>
                                    <span class="input-suffix">FCFA</span>
                                </div>
                                @error('montant')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="date_paiement" class="form-label">Date de paiement <span class="text-danger">*</span></label>
                                <input type="date" name="date_paiement" id="date_paiement" class="form-input @error('date_paiement') error @enderror" value="{{ old('date_paiement', $paiement->date_paiement->format('Y-m-d')) }}" required>
                                @error('date_paiement')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="mode_paiement" class="form-label">Mode de paiement <span class="text-danger">*</span></label>
                                <select name="mode_paiement" id="mode_paiement" class="form-select @error('mode_paiement') error @enderror" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="Espèces" {{ old('mode_paiement', $paiement->mode_paiement) == 'Espèces' ? 'selected' : '' }}>Espèces</option>
                                    <option value="Chèque" {{ old('mode_paiement', $paiement->mode_paiement) == 'Chèque' ? 'selected' : '' }}>Chèque</option>
                                    <option value="Virement" {{ old('mode_paiement', $paiement->mode_paiement) == 'Virement' ? 'selected' : '' }}>Virement bancaire</option>
                                    <option value="Mobile Money" {{ old('mode_paiement', $paiement->mode_paiement) == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
                                    <option value="Carte bancaire" {{ old('mode_paiement', $paiement->mode_paiement) == 'Carte bancaire' ? 'selected' : '' }}>Carte bancaire</option>
                                </select>
                                @error('mode_paiement')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="reference_paiement" class="form-label">Référence du paiement</label>
                                <input type="text" name="reference_paiement" id="reference_paiement" class="form-input @error('reference_paiement') error @enderror" value="{{ old('reference_paiement', $paiement->reference_paiement) }}" placeholder="N° de chèque, transaction, etc.">
                                <small class="form-hint">Numéro de chèque, référence de transaction, etc.</small>
                                @error('reference_paiement')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Classification du paiement -->
                <div class="main-card">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-tags"></i>
                            Classification du Paiement
                        </div>
                        <div class="main-card-subtitle">Motif, tranche et commentaires</div>
                    </div>
                    <div class="main-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="motif" class="form-label">Motif du paiement <span class="text-danger">*</span></label>
                                <select name="motif" id="motif" class="form-select @error('motif') error @enderror" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="Frais d'inscription" {{ old('motif', $paiement->motif) == "Frais d'inscription" ? 'selected' : '' }}>Frais d'inscription</option>
                                    <option value="Scolarité" {{ old('motif', $paiement->motif) == 'Scolarité' ? 'selected' : '' }}>Scolarité</option>
                                    <option value="Frais d'examen" {{ old('motif', $paiement->motif) == "Frais d'examen" ? 'selected' : '' }}>Frais d'examen</option>
                                    <option value="Frais de diplôme" {{ old('motif', $paiement->motif) == 'Frais de diplôme' ? 'selected' : '' }}>Frais de diplôme</option>
                                    <option value="Frais divers" {{ old('motif', $paiement->motif) == 'Frais divers' ? 'selected' : '' }}>Frais divers</option>
                                </select>
                                @error('motif')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="tranche" class="form-label">Tranche</label>
                                <select name="tranche" id="tranche" class="form-select @error('tranche') error @enderror">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="Première tranche" {{ old('tranche', $paiement->tranche) == 'Première tranche' ? 'selected' : '' }}>Première tranche</option>
                                    <option value="Deuxième tranche" {{ old('tranche', $paiement->tranche) == 'Deuxième tranche' ? 'selected' : '' }}>Deuxième tranche</option>
                                    <option value="Troisième tranche" {{ old('tranche', $paiement->tranche) == 'Troisième tranche' ? 'selected' : '' }}>Troisième tranche</option>
                                    <option value="Paiement intégral" {{ old('tranche', $paiement->tranche) == 'Paiement intégral' ? 'selected' : '' }}>Paiement intégral</option>
                                </select>
                                @error('tranche')
                                    <div class="form-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="commentaire" class="form-label">Commentaire</label>
                            <textarea name="commentaire" id="commentaire" class="form-textarea @error('commentaire') error @enderror" rows="4" placeholder="Informations complémentaires sur ce paiement...">{{ old('commentaire', $paiement->commentaire) }}</textarea>
                            @error('commentaire')
                                <div class="form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="form-actions">
                    <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary">
                        <i class="fas fa-times"></i>Annuler
                    </a>
                    <button type="submit" class="btn-acasi primary">
                        <i class="fas fa-save"></i>Enregistrer les modifications
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

<style>
/* Formulaire moderne avec dashboard-moderne.css */
.form-sections {
    display: grid;
    gap: var(--space-xl);
    max-width: none;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--space-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-label {
    font-weight: 600;
    color: var(--text);
    margin-bottom: var(--space-sm);
    font-size: var(--text-small);
    line-height: 1.2;
}

.form-input, .form-select, .form-textarea {
    padding: var(--space-md);
    border: 1px solid var(--border);
    border-radius: var(--radius-small);
    background: var(--card-background);
    color: var(--text);
    font-size: var(--text-base);
    transition: all 0.2s ease;
    line-height: 1.5;
}

.form-input:focus, .form-select:focus, .form-textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
    background: white;
}

.form-input.error, .form-select.error, .form-textarea.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(var(--danger-rgb), 0.1);
}

.form-input[readonly] {
    background: #f8f9fa;
    color: #6c757d;
    cursor: not-allowed;
}

.form-error {
    color: var(--danger);
    font-size: var(--text-small);
    margin-top: var(--space-xs);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.form-error::before {
    content: "⚠";
    font-weight: bold;
}

.form-hint {
    color: var(--muted);
    font-size: var(--text-small);
    margin-top: var(--space-xs);
    font-style: italic;
}

.input-with-suffix {
    position: relative;
    display: flex;
    align-items: stretch;
}

.input-with-suffix .form-input {
    border-radius: var(--radius-small) 0 0 var(--radius-small);
    border-right: none;
    flex: 1;
}

.input-suffix {
    background: #e9ecef;
    border: 1px solid var(--border);
    border-left: none;
    border-radius: 0 var(--radius-small) var(--radius-small) 0;
    padding: var(--space-md);
    display: flex;
    align-items: center;
    font-size: var(--text-small);
    font-weight: 600;
    color: var(--muted);
}

.form-actions {
    display: flex;
    gap: var(--space-md);
    justify-content: flex-end;
    padding: var(--space-xl) 0;
    border-top: 1px solid var(--border);
    margin-top: var(--space-lg);
}

/* Amélioration des cards principales */
.main-card {
    background: var(--card-background);
    border-radius: var(--radius-medium);
    box-shadow: var(--shadow-card);
    border: 1px solid rgba(var(--border-rgb), 0.1);
    transition: all 0.2s ease;
}

.main-card:hover {
    box-shadow: var(--shadow-hover);
}

.main-card-header {
    padding: var(--space-lg);
    background: linear-gradient(135deg, rgba(30, 58, 138, 0.03), rgba(30, 64, 175, 0.01));
    border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    border-radius: var(--radius-medium) var(--radius-medium) 0 0;
}

.main-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--primary);
    margin-bottom: var(--space-xs);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.main-card-subtitle {
    font-size: var(--text-small);
    color: var(--muted);
    margin: 0;
}

.main-card-body {
    padding: var(--space-xl);
}

/* Responsive */
@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .form-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .main-card-body {
        padding: var(--space-lg);
    }
}

/* Couleurs personnalisées */
:root {
    --primary: #01632f;
    --primary-rgb: 1, 99, 47;
    --danger: #dc3545;
    --danger-rgb: 220, 53, 69;
    --info: #0dcaf0;
    --info-rgb: 13, 202, 240;
}
</style>