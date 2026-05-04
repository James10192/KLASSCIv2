@extends('layouts.app')

@section('title', 'Modifier un Paiement - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endsection

@section('content')
@php
    $modeOptions = [
        'Espèces' => 'Espèces',
        'Chèque' => 'Chèque',
        'Virement' => 'Virement bancaire',
        'Virement bancaire' => 'Virement bancaire',
        'Mobile Money' => 'Mobile Money',
        'Carte bancaire' => 'Carte bancaire',
    ];

    $trancheOptions = [
        'Première tranche' => 'Première tranche',
        'Deuxième tranche' => 'Deuxième tranche',
        'Troisième tranche' => 'Troisième tranche',
        'Paiement intégral' => 'Paiement intégral',
    ];

    $categoryOptions = $feeCategories
        ->mapWithKeys(fn ($category) => [(string) $category->id => $category->name])
        ->all();

    $statusBadgeClass = match ((string) ($paiement->status ?? '')) {
        'validé' => 'pe-badge--success',
        'rejeté' => 'pe-badge--danger',
        'annulé' => 'pe-badge--danger',
        default => 'pe-badge--warning',
    };
@endphp

<div class="dashboard-acasi">
    <div class="main-content pe-page">
        <div class="dashboard-header">
            <div class="header-left">
                <div class="pe-header-shell">
                    <div class="pe-header-icon"><i class="fas fa-pen-to-square"></i></div>
                    <div>
                        <h1>Modifier le paiement</h1>
                        <p class="header-subtitle">Ajustez les informations du reçu #{{ $paiement->numero_recu }}</p>
                        <div class="pe-header-meta">
                            <span class="pe-header-pill"><i class="fas fa-receipt"></i> Reçu #{{ $paiement->numero_recu }}</span>
                            <span class="pe-header-pill {{ $statusBadgeClass }}">{{ ucfirst((string) ($paiement->status ?? 'en_attente')) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour aux détails
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger pe-alert" role="alert">
                <div class="pe-alert-title"><i class="fas fa-triangle-exclamation me-2"></i>Corrigez les champs en erreur</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('esbtp.paiements.update', $paiement->id) }}" method="POST" id="pe-edit-payment-form">
            @csrf
            @method('PUT')

            <div class="pe-layout">
                <div class="pe-main">
                    <section class="main-card pe-card">
                        <div class="main-card-header pe-card-header">
                            <div class="main-card-title pe-card-title">
                                <span class="pe-card-icon"><i class="fas fa-user-graduate"></i></span>
                                <span>Étudiant & inscription</span>
                            </div>
                            <div class="main-card-subtitle">Informations de contexte non modifiables</div>
                        </div>
                        <div class="main-card-body pe-card-body">
                            <div class="pe-grid pe-grid--two">
                                <div class="pe-field">
                                    <label class="pe-label">Étudiant</label>
                                    <input
                                        type="text"
                                        class="pe-input pe-input--readonly"
                                        value="{{ $paiement->etudiant->matricule ?? 'N/A' }} - {{ $paiement->etudiant->user->name ?? $paiement->etudiant->nom_complet ?? 'N/A' }}"
                                        readonly>
                                </div>
                                <div class="pe-field">
                                    <label class="pe-label">Inscription</label>
                                    <input
                                        type="text"
                                        class="pe-input pe-input--readonly"
                                        value="{{ $paiement->inscription->filiere->name ?? 'N/A' }} - {{ $paiement->inscription->niveauEtude->name ?? 'N/A' }} ({{ $paiement->inscription->anneeUniversitaire->libelle ?? 'N/A' }})"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="main-card pe-card">
                        <div class="main-card-header pe-card-header">
                            <div class="main-card-title pe-card-title">
                                <span class="pe-card-icon"><i class="fas fa-wallet"></i></span>
                                <span>Détails financiers</span>
                            </div>
                            <div class="main-card-subtitle">Montant, date, mode et référence de paiement</div>
                        </div>
                        <div class="main-card-body pe-card-body">
                            <div class="pe-grid pe-grid--two">
                                <div class="pe-field">
                                    <label for="montant" class="pe-label">Montant <span class="text-danger">*</span></label>
                                    <div class="pe-input-group">
                                        <input
                                            type="number"
                                            name="montant"
                                            id="montant"
                                            min="0"
                                            step="1"
                                            class="pe-input @error('montant') pe-input--error @enderror"
                                            value="{{ old('montant', $paiement->montant) }}"
                                            required>
                                        <span class="pe-input-suffix">FCFA</span>
                                    </div>
                                    @error('montant')
                                        <div class="pe-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="pe-field">
                                    <label for="date_paiement" class="pe-label">Date de paiement <span class="text-danger">*</span></label>
                                    <input
                                        type="date"
                                        name="date_paiement"
                                        id="date_paiement"
                                        class="pe-input @error('date_paiement') pe-input--error @enderror"
                                        value="{{ old('date_paiement', optional($paiement->date_paiement)->format('Y-m-d')) }}"
                                        required>
                                    @error('date_paiement')
                                        <div class="pe-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="pe-field">
                                    <label for="mode_paiement" class="pe-label">Mode de paiement <span class="text-danger">*</span></label>
                                    <x-au-select
                                        id="mode_paiement"
                                        name="mode_paiement"
                                        :value="(string) old('mode_paiement', $paiement->mode_paiement ?? '')"
                                        :options="$modeOptions"
                                        placeholder="Sélectionner un mode"
                                        icon="fa-wallet"
                                        required
                                        searchable />
                                    @error('mode_paiement')
                                        <div class="pe-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="pe-field">
                                    <label for="reference_paiement" class="pe-label">Référence du paiement</label>
                                    <input
                                        type="text"
                                        name="reference_paiement"
                                        id="reference_paiement"
                                        class="pe-input @error('reference_paiement') pe-input--error @enderror"
                                        value="{{ old('reference_paiement', $paiement->reference_paiement) }}"
                                        placeholder="N° de chèque, transaction, etc.">
                                    <small class="pe-help">Optionnel, utile pour faciliter l'audit.</small>
                                    @error('reference_paiement')
                                        <div class="pe-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="main-card pe-card">
                        <div class="main-card-header pe-card-header">
                            <div class="main-card-title pe-card-title">
                                <span class="pe-card-icon"><i class="fas fa-tags"></i></span>
                                <span>Classification</span>
                            </div>
                            <div class="main-card-subtitle">Catégorie, tranche et commentaire</div>
                        </div>
                        <div class="main-card-body pe-card-body">
                            <div class="pe-grid pe-grid--two">
                                <div class="pe-field">
                                    <label for="frais_category_id" class="pe-label">Catégorie de frais <span class="text-danger">*</span></label>
                                    <x-au-select
                                        id="frais_category_id"
                                        name="frais_category_id"
                                        :value="(string) old('frais_category_id', (string) $selectedCategoryId)"
                                        :options="$categoryOptions"
                                        placeholder="Sélectionner une catégorie"
                                        icon="fa-layer-group"
                                        required
                                        searchable />
                                    @error('frais_category_id')
                                        <div class="pe-error">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="pe-field">
                                    <label for="tranche" class="pe-label">Tranche</label>
                                    <x-au-select
                                        id="tranche"
                                        name="tranche"
                                        :value="(string) old('tranche', $paiement->tranche ?? '')"
                                        :options="$trancheOptions"
                                        placeholder="Sélectionner une tranche"
                                        icon="fa-list-check" />
                                    @error('tranche')
                                        <div class="pe-error">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="pe-field pe-field--full">
                                <label for="commentaire" class="pe-label">Commentaire</label>
                                <textarea
                                    name="commentaire"
                                    id="commentaire"
                                    rows="4"
                                    class="pe-textarea @error('commentaire') pe-input--error @enderror"
                                    placeholder="Informations complémentaires sur ce paiement...">{{ old('commentaire', $paiement->commentaire) }}</textarea>
                                @error('commentaire')
                                    <div class="pe-error">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="pe-side">
                    <section class="main-card pe-card pe-summary-card">
                        <div class="main-card-header pe-card-header">
                            <div class="main-card-title pe-card-title">
                                <span class="pe-card-icon"><i class="fas fa-receipt"></i></span>
                                <span>Résumé</span>
                            </div>
                            <div class="main-card-subtitle">Contexte de ce paiement</div>
                        </div>
                        <div class="main-card-body pe-card-body">
                            <dl class="pe-summary-list">
                                <div class="pe-summary-item">
                                    <dt>Numéro de reçu</dt>
                                    <dd>{{ $paiement->numero_recu ?? 'N/A' }}</dd>
                                </div>
                                <div class="pe-summary-item">
                                    <dt>Statut actuel</dt>
                                    <dd><span class="pe-badge {{ $statusBadgeClass }}">{{ ucfirst((string) ($paiement->status ?? 'en_attente')) }}</span></dd>
                                </div>
                                <div class="pe-summary-item">
                                    <dt>Date de saisie</dt>
                                    <dd>{{ optional($paiement->created_at)->format('d/m/Y H:i') ?? 'N/A' }}</dd>
                                </div>
                                <div class="pe-summary-item">
                                    <dt>Dernière mise à jour</dt>
                                    <dd>{{ optional($paiement->updated_at)->format('d/m/Y H:i') ?? 'N/A' }}</dd>
                                </div>
                            </dl>

                            <div class="pe-note">
                                <i class="fas fa-shield-check"></i>
                                <span>
                                    Toute modification est tracée dans l'audit comptable.
                                </span>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>

            <div class="pe-actions">
                <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn-acasi secondary">
                    <i class="fas fa-times"></i>Annuler
                </a>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-save"></i>Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.pe-page {
    --pe-primary: #0453cb;
    --pe-primary-d: #033a8e;
    --pe-secondary: #5e91de;
    --pe-dark: #0f172a;
    --pe-text: #1e293b;
    --pe-muted: #64748b;
    --pe-border: #dbe4f0;
    --pe-surface: #f8fafc;
    --pe-success: #10b981;
    --pe-warning: #f59e0b;
    --pe-danger: #dc2626;
}

.pe-page .dashboard-header {
    border: 1px solid var(--pe-border);
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.06);
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.05));
}

.pe-page .header-left h1 {
    color: var(--pe-primary);
}

.pe-header-shell {
    display: flex;
    align-items: center;
    gap: 0.9rem;
}

.pe-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--pe-primary), var(--pe-secondary));
    color: #fff;
    font-size: 1rem;
    box-shadow: 0 10px 24px rgba(4, 83, 203, 0.2);
    flex-shrink: 0;
}

.pe-header-meta {
    margin-top: 0.4rem;
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.pe-header-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.28rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #1e3a8a;
    border: 1px solid rgba(4, 83, 203, 0.22);
    background: rgba(255, 255, 255, 0.72);
}

.pe-header-pill.pe-badge--success,
.pe-header-pill.pe-badge--warning,
.pe-header-pill.pe-badge--danger {
    border: none;
}

.pe-alert {
    border: 1px solid rgba(220, 38, 38, 0.2);
    border-left: 4px solid var(--pe-danger);
    border-radius: 12px;
}

.pe-alert-title {
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.pe-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.8fr) minmax(300px, 1fr);
    gap: 1.25rem;
    align-items: start;
}

.pe-main {
    display: grid;
    gap: 1rem;
}

.pe-side {
    position: sticky;
    top: 1rem;
}

.pe-card {
    border: 1px solid var(--pe-border);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 1px 2px rgba(15, 23, 42, 0.06);
    overflow: visible;
    position: relative;
    z-index: 1;
}

.pe-card:hover {
    box-shadow: 0 8px 30px rgba(4, 83, 203, 0.08), 0 2px 8px rgba(15, 23, 42, 0.04);
}

.pe-card:focus-within {
    z-index: 30;
}

.pe-card-header {
    background: linear-gradient(135deg, rgba(4, 83, 203, 0.05), rgba(94, 145, 222, 0.05));
    border-bottom: 1px solid var(--pe-border);
    padding: 1rem 1.125rem;
}

.pe-card-title {
    margin-bottom: 0.25rem;
    color: var(--pe-dark);
    font-size: 1rem;
}

.pe-card-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--pe-primary), var(--pe-secondary));
    color: #fff;
    font-size: 0.85rem;
}

.pe-card-body {
    padding: 1.125rem;
}

.pe-grid {
    display: grid;
    gap: 1rem;
}

.pe-grid--two {
    grid-template-columns: repeat(2, minmax(0, 1fr));
}

.pe-field {
    display: grid;
    gap: 0.45rem;
    position: relative;
}

.pe-field:focus-within {
    z-index: 25;
}

.pe-field--full {
    margin-top: 1rem;
}

.pe-label {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    color: var(--pe-muted);
    text-transform: uppercase;
    margin: 0;
}

.pe-input,
.pe-textarea {
    width: 100%;
    border: 1px solid var(--pe-border);
    border-radius: 10px;
    padding: 0.65rem 0.8rem;
    font-size: 0.9rem;
    color: var(--pe-text);
    background: #fff;
    transition: all 0.2s ease;
}

.pe-input:focus,
.pe-textarea:focus {
    outline: none;
    border-color: var(--pe-primary);
    box-shadow: 0 0 0 3px rgba(4, 83, 203, 0.12);
}

.pe-input--readonly {
    background: var(--pe-surface);
    color: #475569;
}

.pe-input--error {
    border-color: var(--pe-danger) !important;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1) !important;
}

.pe-input-group {
    display: flex;
    align-items: stretch;
}

.pe-input-group .pe-input {
    border-radius: 10px 0 0 10px;
    border-right: none;
}

.pe-input-suffix {
    border: 1px solid var(--pe-border);
    border-left: none;
    border-radius: 0 10px 10px 0;
    padding: 0.65rem 0.8rem;
    background: var(--pe-surface);
    color: var(--pe-muted);
    font-size: 0.8rem;
    font-weight: 700;
}

.pe-help {
    font-size: 0.78rem;
    color: var(--pe-muted);
}

.pe-error {
    color: var(--pe-danger);
    font-size: 0.78rem;
    font-weight: 600;
}

.pe-summary-list {
    display: grid;
    gap: 0.75rem;
    margin: 0;
}

.pe-summary-item {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    align-items: center;
    border-bottom: 1px dashed #e2e8f0;
    padding-bottom: 0.6rem;
}

.pe-summary-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.pe-summary-item dt {
    margin: 0;
    color: var(--pe-muted);
    font-size: 0.78rem;
    font-weight: 600;
}

.pe-summary-item dd {
    margin: 0;
    color: var(--pe-dark);
    font-size: 0.82rem;
    font-weight: 700;
    text-align: right;
}

.pe-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    padding: 0.18rem 0.55rem;
    font-size: 0.72rem;
    font-weight: 700;
}

.pe-badge--success {
    color: #065f46;
    background: rgba(16, 185, 129, 0.14);
}

.pe-badge--warning {
    color: #92400e;
    background: rgba(245, 158, 11, 0.16);
}

.pe-badge--danger {
    color: #991b1b;
    background: rgba(220, 38, 38, 0.15);
}

.pe-note {
    margin-top: 1rem;
    border: 1px solid rgba(4, 83, 203, 0.15);
    background: rgba(4, 83, 203, 0.05);
    color: #1e3a8a;
    border-radius: 10px;
    padding: 0.7rem 0.75rem;
    font-size: 0.78rem;
    font-weight: 600;
    display: flex;
    gap: 0.5rem;
    align-items: flex-start;
}

.pe-actions {
    border-top: 1px solid var(--pe-border);
    margin-top: 1.1rem;
    padding-top: 1rem;
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}

@media (max-width: 992px) {
    .pe-layout {
        grid-template-columns: 1fr;
    }

    .pe-side {
        position: static;
    }
}

@media (max-width: 768px) {
    .pe-header-shell {
        align-items: flex-start;
    }

    .pe-grid--two {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .pe-actions {
        flex-direction: column-reverse;
    }

    .pe-actions .btn-acasi {
        width: 100%;
        justify-content: center;
    }
}
</style>
@endpush
