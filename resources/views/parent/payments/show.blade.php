@extends('layouts.app')

@section('title', 'Détail du paiement #' . $paiement->reference)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Détail du paiement #{{ $paiement->reference }}</h5>
                    <div>
                        <a href="{{ route('parent.payments.index') }}" class="btn btn-secondary btn-sm me-2">
                            <i class="fas fa-arrow-left me-1"></i>Retour aux paiements
                        </a>
                        @if($paiement->receipt_path)
                            <a href="{{ route('parent.payments.receipt', $paiement->id) }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-download me-1"></i>Télécharger le reçu
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-4 mb-md-0">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations du paiement</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3 d-flex justify-content-between align-items-center">
                                        <h5>Statut :</h5>
                                        @if($paiement->status == 'confirme' || $paiement->status == 'Confirmé')
                                            <span class="badge bg-success fs-6 px-3 py-2">Confirmé</span>
                                        @elseif($paiement->status == 'en_attente')
                                            <span class="badge bg-warning fs-6 px-3 py-2">En attente</span>
                                        @elseif($paiement->status == 'annule')
                                            <span class="badge bg-danger fs-6 px-3 py-2">Annulé</span>
                                        @else
                                            <span class="badge bg-secondary fs-6 px-3 py-2">{{ ucfirst($paiement->status) }}</span>
                                        @endif
                                    </div>
                                    <table class="table table-borderless">
                                        <tbody>
                                            <tr>
                                                <th style="width: 40%;">Référence :</th>
                                                <td>{{ $paiement->reference }}</td>
                                            </tr>
                                            <tr>
                                                <th>Montant :</th>
                                                <td class="fw-bold text-primary">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                            </tr>
                                            <tr>
                                                <th>Date :</th>
                                                <td>{{ \Carbon\Carbon::parse($paiement->created_at)->format('d/m/Y à H:i') }}</td>
                                            </tr>
                                            <tr>
                                                <th>Mode de paiement :</th>
                                                <td>
                                                    @if($paiement->mode_paiement == 'mobile_money')
                                                        <span class="badge bg-success">Mobile Money</span>
                                                    @elseif($paiement->mode_paiement == 'carte_credit')
                                                        <span class="badge bg-primary">Carte de crédit</span>
                                                    @elseif($paiement->mode_paiement == 'virement')
                                                        <span class="badge bg-info">Virement bancaire</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ ucfirst($paiement->mode_paiement) }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Description :</th>
                                                <td>{{ $paiement->description }}</td>
                                            </tr>
                                            @if($paiement->commentaire)
                                            <tr>
                                                <th>Commentaire :</th>
                                                <td>{{ $paiement->commentaire }}</td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                    
                                    @if($paiement->status == 'en_attente')
                                        <div class="alert alert-warning mt-3">
                                            <i class="fas fa-clock me-2"></i>Ce paiement est en attente de confirmation. Veuillez patienter pendant que notre équipe traite votre paiement.
                                        </div>
                                    @elseif($paiement->status == 'confirme' || $paiement->status == 'Confirmé')
                                        <div class="alert alert-success mt-3">
                                            <i class="fas fa-check-circle me-2"></i>Ce paiement a été confirmé et traité avec succès.
                                        </div>
                                    @elseif($paiement->status == 'annule')
                                        <div class="alert alert-danger mt-3">
                                            <i class="fas fa-times-circle me-2"></i>Ce paiement a été annulé. Veuillez contacter l'administration pour plus d'informations.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Informations de l'étudiant</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                            <i class="fas fa-user fa-lg"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0">{{ $paiement->etudiant->prenoms }} {{ $paiement->etudiant->nom }}</h5>
                                            <p class="text-muted mb-0">Matricule: {{ $paiement->etudiant->matricule }}</p>
                                        </div>
                                    </div>
                                    
                                    @if($paiement->etudiant->inscriptions && $paiement->etudiant->inscriptions->isNotEmpty())
                                        @php 
                                            $inscription = $paiement->etudiant->inscriptions->sortByDesc('date_inscription')->first(); 
                                        @endphp
                                        <p><strong>Classe:</strong> {{ $inscription->classe->name ?? 'Non définie' }}</p>
                                        <p><strong>Filière:</strong> {{ $inscription->filiere->name ?? 'Non définie' }}</p>
                                        <p><strong>Année universitaire:</strong> {{ $inscription->anneeUniversitaire->name ?? 'Non définie' }}</p>
                                    @endif
                                    
                                    <div class="mt-3">
                                        <a href="{{ route('parent.student.details', $paiement->etudiant->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Voir le profil de l'étudiant
                                        </a>
                                        <a href="{{ route('parent.payments.student-history', $paiement->etudiant->id) }}" class="btn btn-outline-info btn-sm ms-2">
                                            <i class="fas fa-history me-1"></i>Historique des paiements
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Besoin d'assistance ?</h6>
                                </div>
                                <div class="card-body">
                                    <p>Si vous avez des questions concernant ce paiement, n'hésitez pas à contacter notre service financier :</p>
                                    <ul class="list-unstyled">
                                        <li class="mb-2">
                                            <i class="fas fa-phone-alt me-2 text-primary"></i>+225 XX XX XX XX
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-envelope me-2 text-primary"></i>finance@esbtp-yakro.ci
                                        </li>
                                        <li>
                                            <i class="fas fa-map-marker-alt me-2 text-primary"></i>Bureau des finances, Campus KLASSCI
                                        </li>
                                    </ul>
                                    <div class="mt-3">
                                        <a href="{{ route('parent.messages.create') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-envelope me-1"></i>Envoyer un message
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 