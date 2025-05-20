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
@endsection
