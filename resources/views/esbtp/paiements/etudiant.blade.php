@extends('layouts.app')

@section('title', 'Paiements de l\'étudiant')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Paiements de l'étudiant: {{ $etudiant->user->name ?? $etudiant->nom_complet ?? 'N/A' }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.etudiants.show', $etudiant->id) }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Profil de l'étudiant
                        </a>
                        @can('create-paiements')
                        <a href="{{ route('esbtp.paiements.create', ['etudiant_id' => $etudiant->id]) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Nouveau paiement
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <!-- Informations de l'étudiant -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info">
                                    <h3 class="card-title">Informations de l'étudiant</h3>
                                </div>
                                <div class="card-body">
                                    <table class="table table-bordered">
                                        <tr>
                                            <th style="width: 40%">Matricule</th>
                                            <td>{{ $etudiant->matricule }}</td>
                                        </tr>
                                        <tr>
                                            <th>Nom complet</th>
                                            <td>{{ $etudiant->user->name ?? $etudiant->nom_complet ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th>Email</th>
                                            <td>{{ $etudiant->user->email ?? 'N/A' }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success">
                                    <h3 class="card-title">Résumé des paiements</h3>
                                </div>
                                <div class="card-body">
                                    <div class="info-box bg-success">
                                        <span class="info-box-icon"><i class="fas fa-money-bill"></i></span>
                                        <div class="info-box-content">
                                            <span class="info-box-text">Total des paiements validés</span>
                                            <span class="info-box-number">{{ number_format($totalValide, 0, ',', ' ') }} FCFA</span>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h5>Inscriptions actives</h5>
                                        <ul class="list-group">
                                            @forelse($etudiant->inscriptions->where('status', 'active') as $inscription)
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    {{ $inscription->filiere->name ?? 'N/A' }} - {{ $inscription->niveauEtude->name ?? 'N/A' }}
                                                    <span class="badge badge-primary">{{ $inscription->anneeUniversitaire->libelle ?? 'N/A' }}</span>
                                                </li>
                                            @empty
                                                <li class="list-group-item">Aucune inscription active</li>
                                            @endforelse
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liste des paiements -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Historique des paiements</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>N° Reçu</th>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Motif</th>
                                            <th>Année Univ.</th>
                                            <th>Mode</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($paiements as $paiement)
                                            <tr>
                                                <td>{{ $paiement->numero_recu }}</td>
                                                <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
                                                <td class="text-right">{{ number_format($paiement->montant, 0, ',', ' ') }} FCFA</td>
                                                <td>
                                                    {{ $paiement->motif }}
                                                    @if($paiement->tranche)
                                                        <br><small class="text-muted">{{ $paiement->tranche }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ $paiement->inscription->anneeUniversitaire->libelle }}</td>
                                                <td>{{ $paiement->mode_paiement }}</td>
                                                <td>
                                                    <span class="badge badge-{{ $paiement->status_class }}">
                                                        {{ $paiement->status_formatte }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="{{ route('esbtp.paiements.show', $paiement->id) }}" class="btn btn-sm btn-info" title="Détails">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        
                                                        @if($paiement->status == 'validé')
                                                            <a href="{{ route('esbtp.paiements.recu', $paiement->id) }}" class="btn btn-sm btn-primary" title="Télécharger le reçu">
                                                                <i class="fas fa-file-pdf"></i>
                                                            </a>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center">Aucun paiement trouvé pour cet étudiant</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 