@extends('layouts.app')

@section('title', 'Historique de mes émargements')

@php
    $moisOptions = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];
    $anneeCourante = (int) now()->year;
    $anneeOptions = [];
    foreach (range($anneeCourante, $anneeCourante - 4) as $a) {
        $anneeOptions[$a] = (string) $a;
    }
@endphp

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="card-title mb-0">Historique de mes émargements</h4>
        </div>
        <div class="card-body">
            <!-- Filtres -->
            <form method="GET" class="mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Mois</label>
                        <x-au-select name="month" :value="$month" :options="$moisOptions" icon="fa-calendar" placeholder="Mois" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Année</label>
                        <x-au-select name="year" :value="$year" :options="$anneeOptions" icon="fa-calendar-days" placeholder="Année" />
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-1"></i>Filtrer
                        </button>
                    </div>
                </div>
            </form>

            <!-- Compteurs de la période -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3">
                        <div class="text-muted small">Total</div>
                        <div class="fs-4 fw-bold">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3">
                        <div class="text-muted small">Présent</div>
                        <div class="fs-4 fw-bold text-success">{{ $stats['present'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3">
                        <div class="text-muted small">En retard</div>
                        <div class="fs-4 fw-bold text-warning">{{ $stats['late'] ?? 0 }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="border rounded p-3">
                        <div class="text-muted small">Non émargé</div>
                        <div class="fs-4 fw-bold text-danger">{{ $stats['not_signed'] ?? 0 }}</div>
                    </div>
                </div>
            </div>

            <!-- Tableau des émargements -->
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Séance</th>
                            <th>Classe</th>
                            <th>Moment</th>
                            <th>Heure de validation</th>
                            <th>Statut</th>
                            <th>Code</th>
                        </tr>
                    </thead>
                    <tbody id="tah-tbody">
                        @forelse($attendances as $attendance)
                            @include('esbtp.teacher.attendance._ligne')
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Aucun émargement enregistré pour cette période.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-liste-infinie :paginateur="$attendances" cible="#tah-tbody" libelle="émargements" />
        </div>
    </div>
</div>
@endsection
