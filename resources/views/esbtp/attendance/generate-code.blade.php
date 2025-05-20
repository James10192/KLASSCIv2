@extends('layouts.app')

@section('title', 'Gestion des Codes d\'Émargement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode me-2"></i>
                        Gestion des Codes d'Émargement
                    </h5>
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

                    <!-- Code Actif -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">Code Actif</h6>
                                </div>
                                <div class="card-body">
                                    @if($activeCode)
                                        <div class="text-center">
                                            <h2 class="display-4 mb-3">{{ $activeCode->code }}</h2>
                                            <p class="text-muted">
                                                @if($activeCode->valid_until)
                                                    Expire le : {{ $activeCode->valid_until->format('d/m/Y H:i') }}
                                                @else
                                                    Date d'expiration inconnue
                                                @endif
                                                (dans {{ $activeCode->getRemainingValidityInMinutes() }} minutes)
                                            </p>
                                            <form action="{{ route('esbtp.attendance-codes.invalidate', $activeCode->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-danger">
                                                    <i class="fas fa-ban me-2"></i>Invalider
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="text-center">
                                            <p class="text-muted mb-3">Aucun code actif</p>
                                            <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fas fa-plus-circle me-2"></i>Générer un nouveau code
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Statistiques -->
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">Statistiques du Jour</h6>
                                </div>
                                <div class="card-body">
                                    @if($activeCode)
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <h4>{{ $activeCode->total_attempts }}</h4>
                                                <small class="text-muted">Total tentatives</small>
                                            </div>
                                            <div class="col-4">
                                                <h4>{{ $activeCode->successful_attempts }}</h4>
                                                <small class="text-muted">Réussies</small>
                                            </div>
                                            <div class="col-4">
                                                <h4>{{ $activeCode->failed_attempts }}</h4>
                                                <small class="text-muted">Échouées</small>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-center text-muted">Aucune statistique disponible</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique des Codes -->
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">Historique des Codes</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Généré le</th>
                                            <th>Par</th>
                                            <th>Statut</th>
                                            <th>Tentatives</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentCodes as $code)
                                            <tr>
                                                <td>{{ $code->code }}</td>
                                                <td>{{ $code->created_at->format('d/m/Y H:i') }}</td>
                                                <td>{{ $code->generator->name }}</td>
                                                <td>
                                                    @if($code->status === 'active')
                                                        <span class="badge bg-success">Actif</span>
                                                    @elseif($code->status === 'expired')
                                                        <span class="badge bg-warning">Expiré</span>
                                                    @else
                                                        <span class="badge bg-danger">Annulé</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $code->successful_attempts }}/{{ $code->total_attempts }}
                                                </td>
                                                <td>
                                                    @if($code->status === 'active')
                                                        <form action="{{ route('esbtp.attendance-codes.invalidate', $code->id) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="fas fa-ban"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Aucun code généré</td>
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

@push('scripts')
<script>
    // Rafraîchissement automatique de la page toutes les 5 minutes
    setTimeout(function() {
        window.location.reload();
    }, 300000);

    // Animation du compte à rebours
    @if($activeCode)
        const expirationTime = new Date("{{ $activeCode->valid_until }}").getTime();

        const countdown = setInterval(function() {
            const now = new Date().getTime();
            const distance = expirationTime - now;

            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            if (distance < 0) {
                clearInterval(countdown);
                window.location.reload();
            }
        }, 1000);
    @endif
</script>
@endpush
@endsection
