@extends('layouts.app')

@section('title', 'Détail Bon de Sortie')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">
                    <i class="fas fa-file-export text-primary"></i>
                    Bon de Sortie {{ $bon->numero_bon }}
                </h1>
                <div>
                    <a href="{{ route('esbtp.comptabilite.bons-sortie.index') }}"
                       class="btn btn-secondary me-2">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>

                    @if(in_array($bon->statut_workflow, ['approuve', 'paye']))
                    <a href="{{ route('esbtp.comptabilite.bons-sortie.pdf', $bon->id) }}"
                       class="btn btn-primary"
                       target="_blank">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Informations principales -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i> Informations du Bon
                    </h5>

                    @php
                        $statutClass = match($bon->statut_workflow) {
                            'brouillon' => 'bg-secondary',
                            'en_attente' => 'bg-warning',
                            'approuve' => 'bg-success',
                            'paye' => 'bg-primary',
                            'rejete' => 'bg-danger',
                            default => 'bg-secondary'
                        };
                        $statutText = match($bon->statut_workflow) {
                            'brouillon' => 'Brouillon',
                            'en_attente' => 'En Attente',
                            'approuve' => 'Approuvé',
                            'paye' => 'Payé',
                            'rejete' => 'Rejeté',
                            default => 'Inconnu'
                        };
                    @endphp

                    <span class="badge {{ $statutClass }} fs-6">
                        {{ $statutText }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Numéro de bon:</td>
                                    <td class="text-primary">{{ $bon->numero_bon }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Libellé:</td>
                                    <td>{{ $bon->libelle }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Montant:</td>
                                    <td class="text-success fw-bold">
                                        {{ number_format($bon->montant, 0, ',', ' ') }} FCFA
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Date de dépense:</td>
                                    <td>{{ \Carbon\Carbon::parse($bon->date_depense)->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Mode de paiement:</td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($bon->mode_paiement) }}</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold">Catégorie:</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $bon->categorie->nom ?? 'N/A' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Fournisseur:</td>
                                    <td>{{ $bon->fournisseur->nom ?? 'Non spécifié' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Créé par:</td>
                                    <td>{{ $bon->createur->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Date de création:</td>
                                    <td>{{ $bon->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @if($bon->approved_by && $bon->date_approbation)
                                <tr>
                                    <td class="fw-bold">Approuvé par:</td>
                                    <td>{{ $bon->approbateur->name ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Date approbation:</td>
                                    <td>{{ \Carbon\Carbon::parse($bon->date_approbation)->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </div>

                    @if($bon->description)
                    <div class="mt-3">
                        <h6 class="fw-bold">Description:</h6>
                        <div class="bg-light p-3 rounded">
                            {{ $bon->description }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            @if($bon->statut_workflow !== 'paye')
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs"></i> Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        @if($bon->statut_workflow === 'brouillon')
                            @can('comptabilite.bons.edit')
                            <a href="{{ route('esbtp.comptabilite.bons-sortie.edit', $bon->id) }}"
                               class="btn btn-warning">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            @endcan

                            @can('comptabilite.bons.submit')
                            <button type="button"
                                    class="btn btn-info"
                                    onclick="soumettreApprobation({{ $bon->id }})">
                                <i class="fas fa-paper-plane"></i> Soumettre pour approbation
                            </button>
                            @endcan
                        @endif

                        @if($bon->statut_workflow === 'en_attente')
                            @can('comptabilite.bons.approve')
                            <button type="button"
                                    class="btn btn-success"
                                    onclick="approuverBon({{ $bon->id }})">
                                <i class="fas fa-check"></i> Approuver
                            </button>
                            <button type="button"
                                    class="btn btn-danger"
                                    onclick="rejeterBon({{ $bon->id }})">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                            @endcan
                        @endif

                        @if($bon->statut_workflow === 'approuve')
                            @can('comptabilite.bons.pay')
                            <button type="button"
                                    class="btn btn-primary"
                                    onclick="marquerPaye({{ $bon->id }})">
                                <i class="fas fa-money-bill"></i> Marquer comme payé
                            </button>
                            @endcan
                        @endif

                        @if($bon->statut_workflow === 'rejete')
                            @can('comptabilite.bons.resubmit')
                            <button type="button"
                                    class="btn btn-info"
                                    onclick="resoumettreApprobation({{ $bon->id }})">
                                <i class="fas fa-redo"></i> Resoumettre
                            </button>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Historique et informations complémentaires -->
        <div class="col-md-4">
            <!-- Historique du workflow -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> Historique
                    </h5>
                </div>
                <div class="card-body">
                    @if($bon->workflow_data)
                        @php $workflow = json_decode($bon->workflow_data, true) ?? []; @endphp
                        @if(is_array($workflow) && count($workflow) > 0)
                        <div class="timeline">
                            @foreach($workflow as $index => $etape)
                            <div class="timeline-item mb-3">
                                <div class="d-flex">
                                    <div class="timeline-marker me-3">
                                        @php
                                            $iconClass = match($etape['action'] ?? '') {
                                                'creation' => 'fas fa-plus text-info',
                                                'approbation' => 'fas fa-check text-success',
                                                'rejet' => 'fas fa-times text-danger',
                                                'soumission' => 'fas fa-paper-plane text-warning',
                                                'paiement' => 'fas fa-money-bill text-primary',
                                                default => 'fas fa-circle text-secondary'
                                            };
                                        @endphp
                                        <i class="{{ $iconClass }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold">
                                            {{ ucfirst($etape['action'] ?? 'Action') }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $etape['utilisateur_nom'] ?? 'Utilisateur' }} -
                                            {{ \Carbon\Carbon::parse($etape['date'] ?? now())->format('d/m/Y H:i') }}
                                        </small>
                                        @if(isset($etape['commentaire']) && $etape['commentaire'])
                                        <div class="mt-1">
                                            <small class="text-dark">{{ $etape['commentaire'] }}</small>
                                        </div>
                                        @endif
                                        @if(isset($etape['motif']) && $etape['motif'])
                                        <div class="mt-1">
                                            <small class="text-danger">Motif: {{ $etape['motif'] }}</small>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted">Aucun historique disponible</p>
                        @endif
                    @else
                    <p class="text-muted">Aucun historique disponible</p>
                    @endif
                </div>
            </div>

            <!-- Workflow guide -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-route"></i> Workflow
                    </h6>
                </div>
                <div class="card-body">
                    <div class="workflow-steps">
                        <div class="step {{ $bon->statut_workflow === 'brouillon' ? 'active' : 'completed' }}">
                            <i class="fas fa-edit"></i> Brouillon
                        </div>
                        <div class="step {{ $bon->statut_workflow === 'en_attente' ? 'active' : ($bon->statut_workflow === 'brouillon' ? 'pending' : 'completed') }}">
                            <i class="fas fa-clock"></i> En Attente
                        </div>
                        <div class="step {{ $bon->statut_workflow === 'approuve' ? 'active' : (in_array($bon->statut_workflow, ['brouillon', 'en_attente']) ? 'pending' : 'completed') }}">
                            <i class="fas fa-check"></i> Approuvé
                        </div>
                        <div class="step {{ $bon->statut_workflow === 'paye' ? 'active' : 'pending' }}">
                            <i class="fas fa-money-bill"></i> Payé
                        </div>
                        @if($bon->statut_workflow === 'rejete')
                        <div class="step rejected">
                            <i class="fas fa-times"></i> Rejeté
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals pour les actions -->
@include('esbtp.comptabilite.bons-sortie.partials.modals')
@endsection

@push('styles')
<style>
.timeline-marker {
    width: 30px;
    text-align: center;
}

.workflow-steps .step {
    padding: 8px 12px;
    margin: 5px 0;
    border-radius: 5px;
    border-left: 4px solid #ddd;
}

.workflow-steps .step.active {
    background-color: #e3f2fd;
    border-left-color: #2196f3;
    color: #1976d2;
}

.workflow-steps .step.completed {
    background-color: #e8f5e8;
    border-left-color: #4caf50;
    color: #388e3c;
}

.workflow-steps .step.pending {
    background-color: #f5f5f5;
    border-left-color: #ddd;
    color: #666;
}

.workflow-steps .step.rejected {
    background-color: #ffebee;
    border-left-color: #f44336;
    color: #d32f2f;
}
</style>
@endpush

@push('scripts')
<script>
function approuverBon(bonId) {
    const modal = new bootstrap.Modal(document.getElementById('approuverModal'));
    const form = document.getElementById('approuverForm');
    form.action = `/esbtp/comptabilite/bons-sortie/${bonId}/approuver`;
    modal.show();
}

function rejeterBon(bonId) {
    const modal = new bootstrap.Modal(document.getElementById('rejeterModal'));
    const form = document.getElementById('rejeterForm');
    form.action = `/esbtp/comptabilite/bons-sortie/${bonId}/rejeter`;
    modal.show();
}

function soumettreApprobation(bonId) {
    if (confirm('Êtes-vous sûr de vouloir soumettre ce bon pour approbation ?')) {
        fetch(`/esbtp/comptabilite/bons-sortie/${bonId}/soumettre`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Une erreur est survenue');
        });
    }
}

function marquerPaye(bonId) {
    const modal = new bootstrap.Modal(document.getElementById('payeModal'));
    const form = document.getElementById('payeForm');
    form.action = `/esbtp/comptabilite/bons-sortie/${bonId}/payer`;
    modal.show();
}

function resoumettreApprobation(bonId) {
    if (confirm('Êtes-vous sûr de vouloir resoumettre ce bon pour approbation ?')) {
        soumettreApprobation(bonId);
    }
}
</script>
@endpush
