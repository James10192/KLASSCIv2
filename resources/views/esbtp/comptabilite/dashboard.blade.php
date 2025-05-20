@extends('layouts.app')

@section('title', 'Dashboard Comptabilité')

@section('content')
<div class="container-fluid py-4">
    <!-- Filtres dynamiques -->
    <form method="GET" class="row g-3 mb-4 align-items-end">
        <div class="col-md-3">
            <label for="annee" class="form-label">Année universitaire</label>
            <select name="annee" id="annee" class="form-select">
                <option value="">Toutes</option>
                @foreach($annees as $annee)
                    <option value="{{ $annee->id }}" {{ request('annee') == $annee->id ? 'selected' : '' }}>{{ $annee->name ?? $annee->libelle }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="filiere" class="form-label">Filière</label>
            <select name="filiere" id="filiere" class="form-select">
                <option value="">Toutes</option>
                @foreach($filieres as $filiere)
                    <option value="{{ $filiere->id }}" {{ request('filiere') == $filiere->id ? 'selected' : '' }}>{{ $filiere->name ?? $filiere->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label for="classe" class="form-label">Classe</label>
            <select name="classe" id="classe" class="form-select">
                <option value="">Toutes</option>
                @foreach($classes as $classe)
                    <option value="{{ $classe->id }}" {{ request('classe') == $classe->id ? 'selected' : '' }}>{{ $classe->name ?? $classe->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Filtrer</button>
        </div>
    </form>
    <!-- Fin filtres -->
    <!-- Ligne 1 : Cards recettes -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="me-2"><i class="fas fa-wallet text-primary fa-2x"></i></span>
                        <div>
                            <h6 class="mb-0">Frais dus</h6>
                            <h4 class="fw-bold text-primary">{{ number_format($totalDue, 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="me-2"><i class="fas fa-coins text-success fa-2x"></i></span>
                        <div>
                            <h6 class="mb-0">Total encaissé</h6>
                            <h4 class="fw-bold text-success">{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="me-2"><i class="fas fa-exclamation-triangle text-danger fa-2x"></i></span>
                        <div>
                            <h6 class="mb-0">Échéances en retard</h6>
                            <h4 class="fw-bold text-danger">{{ number_format($totalOverdue, 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="me-2"><i class="fas fa-check-circle text-info fa-2x"></i></span>
                        <div>
                            <h6 class="mb-0">Échéances soldées</h6>
                            <h4 class="fw-bold text-info">{{ $countPaid }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Ligne 2 : Cards états d'échéances et dépenses -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0">
                <div class="card-body text-center">
                    <h6 class="mb-1">Partiellement payées</h6>
                    <span class="display-6 text-warning">{{ $countPartiallyPaid }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0">
                <div class="card-body text-center">
                    <h6 class="mb-1">En retard</h6>
                    <span class="display-6 text-danger">{{ $countOverdue }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0">
                <div class="card-body text-center">
                    <h6 class="mb-1">À venir</h6>
                    <span class="display-6 text-primary">{{ $countDue }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="me-2"><i class="fas fa-money-bill-wave text-danger fa-2x"></i></span>
                        <div>
                            <h6 class="mb-0">Dépenses totales</h6>
                            <h4 class="fw-bold text-danger">{{ number_format($statsDepenses['total'], 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mt-3">
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <span class="me-2"><i class="fas fa-calendar-alt text-warning fa-2x"></i></span>
                        <div>
                            <h6 class="mb-0">Dépenses mensuelles</h6>
                            <h4 class="fw-bold text-warning">{{ number_format($statsDepenses['mensuel'], 0, ',', ' ') }} FCFA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Graph encaissements -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Évolution des encaissements</h5>
                    <canvas id="encaissementsChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
    <!-- Graph dépenses -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Évolution des dépenses (à intégrer avec Chart.js)</h5>
                    <canvas id="depensesChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('encaissementsChart').getContext('2d');
const encaissementsChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: @json($labelsMois),
        datasets: [{
            label: 'Encaissements',
            data: @json($dataEncaissements),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Graphique dynamique des dépenses
const ctxDep = document.getElementById('depensesChart').getContext('2d');
const depensesChart = new Chart(ctxDep, {
    type: 'line',
    data: {
        labels: @json($labelsMoisDepenses),
        datasets: [{
            label: 'Dépenses',
            data: @json($dataDepensesMensuelles),
            borderColor: '#e11d48',
            backgroundColor: 'rgba(225,29,72,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
@endpush
