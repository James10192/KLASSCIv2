@extends('layouts.app')

@section('title', 'Statistiques d\'Émargement')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>
                        Statistiques d'Émargement
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Résumé des Statistiques -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Total des Codes</h6>
                                    <h2 class="mb-0">{{ $stats['total_codes'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Codes Actifs</h6>
                                    <h2 class="mb-0">{{ $stats['active_codes'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Codes Expirés</h6>
                                    <h2 class="mb-0">{{ $stats['expired_codes'] }}</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Codes Annulés</h6>
                                    <h2 class="mb-0">{{ $stats['cancelled_codes'] }}</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques des Tentatives -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Total des Tentatives</h6>
                                </div>
                                <div class="card-body">
                                    <h3 class="text-center">{{ $stats['total_attempts'] }}</h3>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: {{ $stats['successful_attempts'] / max($stats['total_attempts'], 1) * 100 }}%">
                                            Réussies
                                        </div>
                                        <div class="progress-bar bg-danger" style="width: {{ $stats['failed_attempts'] / max($stats['total_attempts'], 1) * 100 }}%">
                                            Échouées
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Tentatives Réussies</h6>
                                </div>
                                <div class="card-body text-center">
                                    <h3 class="text-success">{{ $stats['successful_attempts'] }}</h3>
                                    <p class="mb-0">
                                        ({{ $stats['total_attempts'] > 0 ? round(($stats['successful_attempts'] / $stats['total_attempts']) * 100, 1) : 0 }}%)
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Tentatives Échouées</h6>
                                </div>
                                <div class="card-body text-center">
                                    <h3 class="text-danger">{{ $stats['failed_attempts'] }}</h3>
                                    <p class="mb-0">
                                        ({{ $stats['total_attempts'] > 0 ? round(($stats['failed_attempts'] / $stats['total_attempts']) * 100, 1) : 0 }}%)
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activité Récente -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Activité Récente</h6>
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
                                            <th>Utilisations</th>
                                            <th>Taux de Réussite</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recentActivity as $code)
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
                                                <td>{{ $code->attendances->count() }}</td>
                                                <td>
                                                    @if($code->total_attempts > 0)
                                                        {{ round(($code->successful_attempts / $code->total_attempts) * 100, 1) }}%
                                                    @else
                                                        0%
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center">Aucune activité récente</td>
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
</script>
@endpush
@endsection
