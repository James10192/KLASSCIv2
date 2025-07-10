@extends('layouts.app')

@section('title', 'Dashboard Avancé - Version Simple')

@section('content')
<div class="container">
    <h1>Dashboard Comptabilité Avancé</h1>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Test de rendu - Version simplifiée</h5>
                </div>
                <div class="card-body">
                    <p>Cette vue de test évite les expressions Blade complexes qui causent les erreurs ParseError.</p>

                    {{-- Au lieu d'utiliser @json avec des expressions complexes,
                         nous utilisons des variables préparées dans le contrôleur --}}

                    <div id="chart-container">
                        <canvas id="evolutionChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Utiliser des variables simples au lieu d'expressions @json complexes
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('evolutionChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Recettes',
                    data: [2800000, 3200000, 2900000, 3500000, 3100000, 3400000],
                    borderColor: '#06b6d4',
                    backgroundColor: '#06b6d4' + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>
@endpush
@endsection