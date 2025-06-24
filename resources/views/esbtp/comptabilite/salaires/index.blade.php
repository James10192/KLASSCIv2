@extends('layouts.app')

@section('title', 'Gestion des salaires')

@section('content')
<div class="container-fluid">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="fas fa-money-check-alt fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Gestion des salaires</h1>
                <div class="text-white-50">Suivi et gestion des salaires du personnel</div>
            </div>
        </div>
        <a href="{{ route('esbtp.comptabilite.salaires.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
            <i class="fas fa-plus"></i> Nouveau salaire
        </a>
    </div>

    <div class="container-fluid animate-fade-in-up">
        <div class="row justify-content-center">
            <div class="col-lg-11 col-md-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass mb-4">
                    <div class="card-body p-0">
                        @if(session('success'))
                        <div class="alert alert-success d-flex align-items-center glass-alert mb-4">
                            <i class="fas fa-check-circle fa-2x me-3 text-success"></i>
                            <div>{{ session('success') }}</div>
                        </div>
                        @endif
                        @if(session('error'))
                        <div class="alert alert-danger d-flex align-items-center glass-alert mb-4">
                            <i class="fas fa-exclamation-triangle fa-2x me-3 text-danger"></i>
                            <div>{{ session('error') }}</div>
                        </div>
                        @endif
                        <!-- Filtres -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4 premium-glass">
                            <div class="card-header bg-white border-0 rounded-top-4">
                                <i class="fas fa-filter me-1"></i> Filtrer les salaires
                            </div>
                            <div class="card-body">
                                <form action="{{ route('esbtp.comptabilite.salaires') }}" method="GET" class="row align-items-end">
                                    <div class="col-md-3 mb-2">
                                        <label for="search" class="form-label">Recherche par nom</label>
                                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Nom de l'employé">
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="mois" class="form-label">Mois</label>
                                        <select name="mois" id="mois" class="form-select">
                                            <option value="">Tous les mois</option>
                                            <option value="1" {{ request('mois') == '1' ? 'selected' : '' }}>Janvier</option>
                                            <option value="2" {{ request('mois') == '2' ? 'selected' : '' }}>Février</option>
                                            <option value="3" {{ request('mois') == '3' ? 'selected' : '' }}>Mars</option>
                                            <option value="4" {{ request('mois') == '4' ? 'selected' : '' }}>Avril</option>
                                            <option value="5" {{ request('mois') == '5' ? 'selected' : '' }}>Mai</option>
                                            <option value="6" {{ request('mois') == '6' ? 'selected' : '' }}>Juin</option>
                                            <option value="7" {{ request('mois') == '7' ? 'selected' : '' }}>Juillet</option>
                                            <option value="8" {{ request('mois') == '8' ? 'selected' : '' }}>Août</option>
                                            <option value="9" {{ request('mois') == '9' ? 'selected' : '' }}>Septembre</option>
                                            <option value="10" {{ request('mois') == '10' ? 'selected' : '' }}>Octobre</option>
                                            <option value="11" {{ request('mois') == '11' ? 'selected' : '' }}>Novembre</option>
                                            <option value="12" {{ request('mois') == '12' ? 'selected' : '' }}>Décembre</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="annee" class="form-label">Année</label>
                                        <select name="annee" id="annee" class="form-select">
                                            <option value="">Toutes les années</option>
                                            @for($i = date('Y') - 2; $i <= date('Y') + 1; $i++)
                                                <option value="{{ $i }}" {{ request('annee') == $i ? 'selected' : '' }}>{{ $i }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-md-2 mb-2">
                                        <label for="statut" class="form-label">Statut</label>
                                        <select name="statut" id="statut" class="form-select">
                                            <option value="">Tous les statuts</option>
                                            <option value="calculé" {{ request('statut') == 'calculé' ? 'selected' : '' }}>Calculé</option>
                                            <option value="validé" {{ request('statut') == 'validé' ? 'selected' : '' }}>Validé</option>
                                            <option value="payé" {{ request('statut') == 'payé' ? 'selected' : '' }}>Payé</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2 d-flex">
                                        <button type="submit" class="btn btn-primary me-2">
                                            <i class="fas fa-search me-1"></i> Filtrer
                                        </button>
                                        <a href="{{ route('esbtp.comptabilite.salaires') }}" class="btn btn-secondary">
                                            <i class="fas fa-redo me-1"></i> Réinitialiser
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <!-- Tableau des salaires -->
                        <div class="table-responsive">
                            <table class="table table-hover align-middle premium-table mb-0">
                                <thead class="sticky-top bg-gradient-primary text-white rounded-top-4">
                                    <tr>
                                        <th>ID</th>
                                        <th>Employé</th>
                                        <th>Période</th>
                                        <th>Salaire base</th>
                                        <th>Heures supp.</th>
                                        <th>Primes</th>
                                        <th>Retenues</th>
                                        <th>Montant net</th>
                                        <th>Date paiement</th>
                                        <th>Statut</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($salaires as $salaire)
                                    <tr>
                                        <td>{{ $salaire->id }}</td>
                                        <td>{{ $salaire->user->name ?? 'N/A' }}</td>
                                        <td>
                                            @php
                                                $months = [
                                                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars',
                                                    4 => 'Avril', 5 => 'Mai', 6 => 'Juin',
                                                    7 => 'Juillet', 8 => 'Août', 9 => 'Septembre',
                                                    10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
                                                ];
                                            @endphp
                                            {{ $months[$salaire->mois] ?? 'N/A' }} {{ $salaire->annee }}
                                        </td>
                                        <td>{{ number_format($salaire->salaire_base, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ number_format($salaire->heures_supplementaires, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ number_format($salaire->primes, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ number_format($salaire->retenues, 0, ',', ' ') }} FCFA</td>
                                        <td class="fw-bold">{{ number_format($salaire->montant_net, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ $salaire->date_paiement ? date('d/m/Y', strtotime($salaire->date_paiement)) : 'Non payé' }}</td>
                                        <td>
                                            @if($salaire->statut == 'payé')
                                                <span class="badge bg-success px-3 py-2">Payé</span>
                                            @elseif($salaire->statut == 'validé')
                                                <span class="badge bg-info px-3 py-2">Validé</span>
                                            @elseif($salaire->statut == 'calculé')
                                                <span class="badge bg-warning text-dark px-3 py-2">Calculé</span>
                                            @else
                                                <span class="badge bg-secondary px-3 py-2">{{ $salaire->statut }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex">
                                                <a href="{{ route('esbtp.comptabilite.salaires.show', $salaire->id) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.comptabilite.salaires.edit', $salaire->id) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1 me-1" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                @if($salaire->statut == 'calculé')
                                                <form action="{{ route('esbtp.comptabilite.salaires.update-status', ['id' => $salaire->id, 'status' => 'validé']) }}" method="POST" class="d-inline me-1">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Valider">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                @elseif($salaire->statut == 'validé')
                                                <form action="{{ route('esbtp.comptabilite.salaires.update-status', ['id' => $salaire->id, 'status' => 'payé']) }}" method="POST" class="d-inline me-1">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Marquer comme payé">
                                                        <i class="fas fa-money-bill"></i>
                                                    </button>
                                                </form>
                                                @endif
                                                <button type="button" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $salaire->id }}" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <!-- Modal de suppression -->
                                                @includeIf('esbtp.comptabilite.salaires._delete_modal', ['salaire' => $salaire])
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="11" class="text-center">Aucun salaire trouvé</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $salaires->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 