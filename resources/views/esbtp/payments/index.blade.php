@extends('layouts.app')

@section('title', 'Gestion des Paiements')

@section('content')
<div class="container-fluid">
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

    <div class="row justify-content-center animate-fade-in-up">
        <div class="col-lg-12 col-md-12">
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
                                @forelse($payments as $payment)
                                    <tr>
                                        <td>
                                            @if($payment->student)
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <strong>{{ $payment->student->nom }} {{ $payment->student->prenom }}</strong>
                                                        @if($payment->student->matricule)
                                                            <br><small class="text-muted">{{ $payment->student->matricule }}</small>
                                                        @endif
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-muted">Étudiant non défini</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold text-primary">{{ $payment->formatted_amount }}</span>
                                        </td>
                                        <td>
                                            @if($payment->payment_date)
                                                {{ $payment->payment_date->format('d/m/Y') }}
                                            @else
                                                <span class="text-muted">Non définie</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $payment->payment_method === 'cash' ? 'success' : ($payment->payment_method === 'bank_transfer' ? 'info' : ($payment->payment_method === 'check' ? 'warning' : 'primary')) }} px-3 py-2">
                                                {{ $payment->payment_method_label }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($payment->reference_number)
                                                <code>{{ $payment->reference_number }}</code>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($payment->category)
                                                <span class="badge bg-info text-dark px-3 py-2">{{ $payment->category->name }}</span>
                                            @else
                                                <span class="text-muted">Non définie</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $payment->status_color }} px-3 py-2">
                                                {{ $payment->status_label }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('esbtp.payments.show', $payment) }}"
                                                   class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"
                                                   title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('esbtp.payments.edit', $payment) }}"
                                                   class="btn btn-warning btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"
                                                   title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('esbtp.payments.receipt', $payment) }}"
                                                   class="btn btn-success btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"
                                                   title="Reçu">
                                                    <i class="fas fa-file-invoice"></i>
                                                </a>
                                                <form action="{{ route('esbtp.payments.destroy', $payment) }}" method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce paiement ?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                            class="btn btn-danger btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1"
                                                            title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h5 class="text-muted">Aucun paiement enregistré</h5>
                                                <p class="text-muted">Commencez par créer votre premier paiement</p>
                                                <a href="{{ route('esbtp.payments.create') }}" class="btn btn-primary">
                                                    <i class="fas fa-plus"></i> Nouveau Paiement
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($payments->hasPages())
                        <div class="mt-4 d-flex justify-content-center">
                            {{ $payments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
