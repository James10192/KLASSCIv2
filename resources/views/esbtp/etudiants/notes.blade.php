@extends('layouts.app')

@section('title', 'Mes Notes')

@section('content')
<!-- HEADER PREMIUM -->
<div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
        <i class="fas fa-graduation-cap fa-2x text-white"></i>
    </div>
    <div>
        <h1 class="h3 fw-bold text-white mb-1">Mes Notes</h1>
        <div class="text-white-50">Consultez vos résultats et votre moyenne générale</div>
    </div>
</div>

<div class="container-fluid animate-fade-in-up">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                <div class="card-body p-0">
                    @if($notes->isEmpty())
                        <div class="alert alert-info d-flex align-items-center glass-alert mb-4">
                            <i class="fas fa-info-circle fa-2x me-3 text-primary"></i>
                            <div>Aucune note disponible pour le moment.</div>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle premium-table mb-0">
                                <thead class="sticky-top bg-gradient-primary text-white rounded-top-4">
                                    <tr>
                                        <th>Matière</th>
                                        <th>Type d'évaluation</th>
                                        <th>Date</th>
                                        <th>Note</th>
                                        <th>Coefficient</th>
                                        <th>Note pondérée</th>
                                        <th>Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($notes as $note)
                                        <tr>
                                            <td>{{ $note->evaluation->matiere->name }}</td>
                                            <td>{{ $note->evaluation->type }}</td>
                                            <td>{{ $note->evaluation->date_evaluation->format('d/m/Y') }}</td>
                                            <td>
                                                @if($note->is_absent)
                                                    <span class="badge bg-danger px-3 py-2 fs-6 shadow">Absent</span>
                                                @else
                                                    <span class="fw-bold text-primary">{{ $note->note }}</span>/<span class="text-muted">{{ $note->evaluation->bareme }}</span>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-info text-dark px-3 py-2">{{ $note->evaluation->coefficient }}</span></td>
                                            <td>
                                                @if(!$note->is_absent)
                                                    <span class="fw-bold text-success">{{ number_format(($note->note * $note->evaluation->coefficient), 2) }}</span>
                                                @else
                                                    <span class="text-muted">0</span>
                                                @endif
                                            </td>
                                            <td>{{ $note->commentaire }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light">
                                        <td colspan="5" class="text-end fw-bold">Moyenne générale :</td>
                                        <td colspan="2" class="fw-bold text-primary fs-5">
                                            @php
                                                $totalPoints = 0;
                                                $totalCoeff = 0;
                                                foreach($notes as $note) {
                                                    if(!$note->is_absent) {
                                                        $totalPoints += ($note->note * $note->evaluation->coefficient);
                                                        $totalCoeff += $note->evaluation->coefficient;
                                                    }
                                                }
                                                $moyenne = $totalCoeff > 0 ? $totalPoints / $totalCoeff : 0;
                                            @endphp
                                            {{ number_format($moyenne, 2) }}/20
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
