@extends('layouts.app')
@section('content')
<div class="container">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center justify-content-between gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                <i class="fas fa-credit-card fa-2x text-white"></i>
            </div>
            <div>
                <h1 class="h3 fw-bold text-white mb-1">Catégories de paiements</h1>
                <div class="text-white-50">Gérez les différentes catégories de paiements</div>
            </div>
        </div>
        <a href="{{ route('esbtp.payment-categories.create') }}" class="btn btn-lg btn-warning fw-bold shadow rounded-3 px-4 py-2 d-flex align-items-center gap-2 animate-fade-in-up">
            <i class="fas fa-plus"></i> Nouvelle catégorie
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
                                <tbody id="pca-tbody">
                                    @foreach($categories as $cat)
                                        @include('esbtp.payment-categories._ligne')
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <x-liste-infinie :paginateur="$categories" cible="#pca-tbody" libelle="catégories" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
