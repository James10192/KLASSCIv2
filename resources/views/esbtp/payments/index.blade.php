@extends('layouts.app')

@section('title', 'Gestion des Paiements')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Paiements</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.payments.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveau Paiement
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Étudiant</th>
                                    <th>Montant</th>
                                    <th>Date</th>
                                    <th>Méthode</th>
                                    <th>Référence</th>
                                    <th>Catégorie</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->student->name }}</td>
                                        <td>{{ number_format($payment->amount, 2, ',', ' ') }} FCFA</td>
                                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                                        <td>
                                            @switch($payment->payment_method)
                                                @case('cash')
                                                    <span class="badge bg-success">Espèces</span>
                                                    @break
                                                @case('bank_transfer')
                                                    <span class="badge bg-info">Virement</span>
                                                    @break
                                                @case('check')
                                                    <span class="badge bg-warning">Chèque</span>
                                                    @break
                                                @case('mobile_money')
                                                    <span class="badge bg-primary">Mobile Money</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                                        <td>{{ $payment->category->name }}</td>
                                        <td>
                                            @switch($payment->status)
                                                @case('completed')
                                                    <span class="badge bg-success">Complété</span>
                                                    @break
                                                @case('pending')
                                                    <span class="badge bg-warning">En attente</span>
                                                    @break
                                                @case('failed')
                                                    <span class="badge bg-danger">Échoué</span>
                                                    @break
                                                @case('refunded')
                                                    <span class="badge bg-info">Remboursé</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            <a href="{{ route('esbtp.payments.show', $payment) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.payments.edit', $payment) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('esbtp.payments.receipt', $payment) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                            <form action="{{ route('esbtp.payments.destroy', $payment) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HEADER PREMIUM -->
<div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
            <i class="fas fa-cash-register fa-2x text-white"></i>
        </div>
        <div>
            <h1 class="h3 fw-bold text-white mb-1">Gestion des Paiements</h1>
            <div class="text-white-50">Suivi et gestion des paiements de l'établissement</div>
        </div>
    </div>
    <a href="{{ route('esbtp.payments.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
        <i class="fas fa-plus"></i> Nouveau Paiement
    </a>
</div>

<div class="container-fluid animate-fade-in-up">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
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
                    <div class="table-responsive">
                        <table class="table table-hover align-middle premium-table mb-0">
                            <thead class="sticky-top bg-gradient-primary text-white rounded-top-4">
                                <tr>
                                    <th>Nom</th>
                                    <th>Code</th>
                                    <th>Description</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->name }}</td>
                                        <td><span class="badge bg-info text-dark px-3 py-2">{{ $payment->code }}</span></td>
                                        <td>{{ $payment->description }}</td>
                                        <td>
                                            @if($payment->is_active)
                                                <span class="badge bg-success px-3 py-2">Actif</span>
                                            @else
                                                <span class="badge bg-danger px-3 py-2">Inactif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('esbtp.payments.edit', $payment) }}" class="btn btn-primary btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('esbtp.payments.destroy', $payment) }}" method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer ce paiement ?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $payments->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
