@extends('layouts.app')

@section('title', 'Détails des Frais de Scolarité')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Détails des Frais de Scolarité</h3>
                    <div class="card-tools">
                        <a href="{{ route('esbtp.fees.index') }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <a href="{{ route('esbtp.fees.edit', $fee) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Modifier
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fas fa-graduation-cap"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Classe</span>
                                    <span class="info-box-number">{{ $fee->class->name }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-calendar"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Année Académique</span>
                                    <span class="info-box-number">{{ $fee->academicYear->name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-money-bill"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Montant</span>
                                    <span class="info-box-number">{{ number_format($fee->amount, 0, ',', ' ') }} FCFA</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Date d'échéance</span>
                                    <span class="info-box-number">{{ $fee->due_date->format('d/m/Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Mode de Paiement</h3>
                                </div>
                                <div class="card-body">
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
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Versements</h3>
                                </div>
                                <div class="card-body">
                                    @if($fee->installments_allowed)
                                        <span class="badge bg-success">Autorisés</span>
                                        <p class="mt-2">Montant minimum par versement: {{ number_format($fee->min_installment_amount, 0, ',', ' ') }} FCFA</p>
                                    @else
                                        <span class="badge bg-danger">Non autorisés</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($fee->late_fee)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Pénalité de Retard</h3>
                                </div>
                                <div class="card-body">
                                    <p>{{ number_format($fee->late_fee, 0, ',', ' ') }} FCFA</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($fee->description)
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Description</h3>
                                </div>
                                <div class="card-body">
                                    <p>{{ $fee->description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Statut</h3>
                                </div>
                                <div class="card-body">
                                    @if($fee->status === 'active')
                                        <span class="badge bg-success">Actif</span>
                                    @else
                                        <span class="badge bg-danger">Inactif</span>
                                    @endif
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
