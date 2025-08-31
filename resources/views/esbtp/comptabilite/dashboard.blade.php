@extends('layouts.app')

@section('title', 'Dashboard Comptabilité')

@section('content')
<div class="container-fluid py-4">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
            <i class="fas fa-coins fa-2x text-white"></i>
        </div>
        <div>
            <h1 class="text-white fw-bold mb-1" style="font-size:1.7rem;">Dashboard Comptabilité</h1>
            <p class="text-white-50 mb-0">Suivi premium des finances de l'établissement</p>
        </div>
    </div>

    <!-- Filtres dynamiques premium -->
    <div class="container-fluid animate-fade-in-up">
        <form method="GET" class="row g-3 mb-4 align-items-end premium-glass p-4 rounded-4 shadow-lg">
            <div class="col-md-3">
                <label for="annee" class="form-label fw-semibold">Année universitaire</label>
                <select name="annee" id="annee" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($annees as $annee)
                        <option value="{{ $annee->id }}" {{ request('annee') == $annee->id ? 'selected' : '' }}>{{ $annee->name ?? $annee->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filiere" class="form-label fw-semibold">Filière</label>
                <select name="filiere" id="filiere" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($filieres as $filiere)
                        <option value="{{ $filiere->id }}" {{ request('filiere') == $filiere->id ? 'selected' : '' }}>{{ $filiere->name ?? $filiere->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="classe" class="form-label fw-semibold">Classe</label>
                <select name="classe" id="classe" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($classes as $classe)
                        <option value="{{ $classe->id }}" {{ request('classe') == $classe->id ? 'selected' : '' }}>{{ $classe->name ?? $classe->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 animate-fade-in w-100">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </div>
        </form>

        <!-- Cards stats premium -->
        <div class="row g-4 mb-4 animate-fade-in-up">
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center bg-primary bg-gradient text-white rounded-circle" style="width:48px;height:48px;font-size:1.5rem;">
                            <i class="fas fa-wallet"></i>
                        </span>
                    </div>
                    <div class="display-6 fw-bold mb-1 text-primary">{{ number_format($totalDue, 0, ',', ' ') }} FCFA</div>
                    <div class="text-muted mb-2">Frais dus</div>
                    <span class="badge bg-primary bg-gradient rounded-pill px-3 py-2">À payer</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center bg-success bg-gradient text-white rounded-circle" style="width:48px;height:48px;font-size:1.5rem;">
                            <i class="fas fa-coins"></i>
                        </span>
                    </div>
                    <div class="display-6 fw-bold mb-1 text-success">{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</div>
                    <div class="text-muted mb-2">Total encaissé</div>
                    <span class="badge bg-success bg-gradient rounded-pill px-3 py-2">Encaissé</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center bg-danger bg-gradient text-white rounded-circle" style="width:48px;height:48px;font-size:1.5rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                    </div>
                    <div class="display-6 fw-bold mb-1 text-danger">{{ number_format($totalOverdue, 0, ',', ' ') }} FCFA</div>
                    <div class="text-muted mb-2">Échéances en retard</div>
                    <span class="badge bg-danger bg-gradient rounded-pill px-3 py-2">Retard</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="d-flex justify-content-center mb-2">
                        <span class="d-inline-flex align-items-center justify-content-center bg-info bg-gradient text-white rounded-circle" style="width:48px;height:48px;font-size:1.5rem;">
                            <i class="fas fa-check-circle"></i>
                        </span>
                    </div>
                    <div class="display-6 fw-bold mb-1 text-info">{{ $countPaid }}</div>
                    <div class="text-muted mb-2">Échéances soldées</div>
                    <span class="badge bg-info bg-gradient rounded-pill px-3 py-2">Soldées</span>
                </div>
            </div>
        </div>
        <!-- Cards états d'échéances et dépenses premium -->
        <div class="row g-4 mb-4 animate-fade-in-up">
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="text-warning mb-2"><i class="fas fa-hourglass-half fa-2x"></i></div>
                    <div class="display-6 fw-bold mb-1 text-warning">{{ $countPartiallyPaid }}</div>
                    <div class="text-muted mb-2">Partiellement payées</div>
                    <span class="badge bg-warning bg-gradient rounded-pill px-3 py-2">Partiel</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="text-danger mb-2"><i class="fas fa-calendar-times fa-2x"></i></div>
                    <div class="display-6 fw-bold mb-1 text-danger">{{ $countOverdue }}</div>
                    <div class="text-muted mb-2">En retard</div>
                    <span class="badge bg-danger bg-gradient rounded-pill px-3 py-2">Retard</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="text-primary mb-2"><i class="fas fa-calendar-plus fa-2x"></i></div>
                    <div class="display-6 fw-bold mb-1 text-primary">{{ $countDue }}</div>
                    <div class="text-muted mb-2">À venir</div>
                    <span class="badge bg-primary bg-gradient rounded-pill px-3 py-2">À venir</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="text-danger mb-2"><i class="fas fa-money-bill-wave fa-2x"></i></div>
                    <div class="display-6 fw-bold mb-1 text-danger">{{ number_format($statsDepenses['total'], 0, ',', ' ') }} FCFA</div>
                    <div class="text-muted mb-2">Dépenses totales</div>
                    <span class="badge bg-danger bg-gradient rounded-pill px-3 py-2">Dépenses</span>
                </div>
            </div>
            <div class="col-md-3 mt-3">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass text-center hover-lift">
                    <div class="text-warning mb-2"><i class="fas fa-calendar-alt fa-2x"></i></div>
                    <div class="display-6 fw-bold mb-1 text-warning">{{ number_format($statsDepenses['mensuel'], 0, ',', ' ') }} FCFA</div>
                    <div class="text-muted mb-2">Dépenses mensuelles</div>
                    <span class="badge bg-warning bg-gradient rounded-pill px-3 py-2">Mensuel</span>
                </div>
            </div>
        </div>
        <!-- Graph encaissements premium -->
        <div class="row mt-5 animate-fade-in-up">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                    <div class="card-body p-0">
                        <h5 class="fw-bold mb-3"><i class="fas fa-chart-line text-primary me-2"></i>Évolution des encaissements</h5>
                        <canvas id="encaissementsChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Graph dépenses premium -->
        <div class="row mt-4 animate-fade-in-up">
            <div class="col-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                    <div class="card-body p-0">
                        <h5 class="fw-bold mb-3"><i class="fas fa-chart-area text-danger me-2"></i>Évolution des dépenses</h5>
                        <canvas id="depensesChart" height="80"></canvas>
                    </div>
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
            borderColor: '#0453cb',
            backgroundColor: 'rgba(4,83,203,0.1)',
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
