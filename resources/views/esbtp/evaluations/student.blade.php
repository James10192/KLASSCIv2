@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Mes évaluations</h5>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Prochaines évaluations</h6>
                                    <h2 class="card-text">
                                        {{ $evaluations->where('date_evaluation', '>', now())->count() }}
                                    </h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title">Notes disponibles</h6>
                                    <h2 class="card-text">
                                        {{ $evaluations->where('notes_published', true)->count() }}
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul class="nav nav-tabs mb-4" id="evaluationTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button" role="tab" aria-controls="upcoming" aria-selected="true">
                                À venir
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button" role="tab" aria-controls="past" aria-selected="false">
                                Passées
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="evaluationTabsContent">
                        <div class="tab-pane fade show active" id="upcoming" role="tabpanel" aria-labelledby="upcoming-tab">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Matière</th>
                                            <th>Type</th>
                                            <th>Titre</th>
                                            <th>Durée</th>
                                            <th>Coefficient</th>
                                            <th>Barème</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($evaluations->where('date_evaluation', '>', now()) as $evaluation)
                                            <tr>
                                                <td>{{ $evaluation->date_evaluation->format('d/m/Y') }}</td>
                                                <td>{{ $evaluation->matiere->nom }}</td>
                                                <td>{{ ucfirst($evaluation->type) }}</td>
                                                <td>{{ $evaluation->titre }}</td>
                                                <td>{{ $evaluation->duree_minutes }} minutes</td>
                                                <td>{{ $evaluation->coefficient }}</td>
                                                <td>{{ $evaluation->bareme }} points</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">Aucune évaluation à venir</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="past" role="tabpanel" aria-labelledby="past-tab">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Matière</th>
                                            <th>Type</th>
                                            <th>Titre</th>
                                            <th>Coefficient</th>
                                            <th>Note</th>
                                            <th>Note finale</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($evaluations->where('date_evaluation', '<', now()) as $evaluation)
                                            <tr>
                                                <td>{{ $evaluation->date_evaluation->format('d/m/Y') }}</td>
                                                <td>{{ $evaluation->matiere->nom }}</td>
                                                <td>{{ ucfirst($evaluation->type) }}</td>
                                                <td>{{ $evaluation->titre }}</td>
                                                <td>{{ $evaluation->coefficient }}</td>
                                                <td>
                                                    @if($evaluation->notes_published && $evaluation->notes->isNotEmpty())
                                                        {{ $evaluation->notes->first()->note }}/{{ $evaluation->bareme }}
                                                    @else
                                                        <span class="badge bg-secondary">Non disponible</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($evaluation->notes_published && $evaluation->notes->isNotEmpty())
                                                        {{ $evaluation->notes->first()->note * $evaluation->coefficient }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">Aucune évaluation passée</td>
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
@endsection
