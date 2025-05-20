@extends('layouts.app')

@section('title', 'Gestion des Frais de Scolarité')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestion des Frais de Scolarité</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.fees.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nouveaux Frais
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
                                    <th>Classe</th>
                                    <th>Année Académique</th>
                                    <th>Montant</th>
                                    <th>Échéance</th>
                                    <th>Mode de Paiement</th>
                                    <th>Versements</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fees as $fee)
                                    <tr>
                                        <td>{{ $fee->class->name }}</td>
                                        <td>{{ $fee->academicYear->name }}</td>
                                        <td>{{ number_format($fee->amount, 0, ',', ' ') }} FCFA</td>
                                        <td>{{ $fee->due_date->format('d/m/Y') }}</td>
                                        <td>
                                            @switch($fee->payment_schedule)
                                                @case('one_time')
                                                    <span class="badge bg-primary">Paiement unique</span>
                                                    @break
                                                @case('monthly')
                                                    <span class="badge bg-info">Mensuel</span>
                                                    @break
                                                @case('termly')
                                                    <span class="badge bg-warning">Trimestriel</span>
                                                    @break
                                                @case('yearly')
                                                    <span class="badge bg-success">Annuel</span>
                                                    @break
                                            @endswitch
                                        </td>
                                        <td>
                                            @if($fee->installments_allowed)
                                                <span class="badge bg-success">Autorisés</span>
                                                <br>
                                                <small>Min: {{ number_format($fee->min_installment_amount, 0, ',', ' ') }} FCFA</small>
                                            @else
                                                <span class="badge bg-danger">Non autorisés</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($fee->status === 'active')
                                                <span class="badge bg-success">Actif</span>
                                            @else
                                                <span class="badge bg-danger">Inactif</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('esbtp.fees.show', $fee) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('esbtp.fees.edit', $fee) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('esbtp.fees.destroy', $fee) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ces frais ?')">
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
