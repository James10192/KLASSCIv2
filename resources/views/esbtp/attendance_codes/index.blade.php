@extends('layouts.app')

@section('content')
<div class="container">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
            <i class="fas fa-key fa-2x text-white"></i>
        </div>
        <div>
            <h1 class="text-white fw-bold mb-1" style="font-size:1.7rem;">Gestion des codes d'assiduité</h1>
            <p class="text-white-50 mb-0">Générez et suivez les codes d'assiduité pour la présence</p>
        </div>
    </div>

    <div class="container-fluid animate-fade-in-up">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 mb-4">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                    <div class="card-body p-0">
                        <h5 class="fw-bold mb-3"><i class="fas fa-key text-primary me-2"></i>Générer un nouveau code</h5>
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
                        <form action="{{ route('esbtp.attendance-codes.generate') }}" method="POST" class="row g-3">
                            @csrf
                            <div class="col-md-6">
                                <label for="date" class="form-label fw-semibold">Date</label>
                                <input type="date" class="form-control" id="date" name="date" required min="{{ date('Y-m-d') }}" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-12 d-flex gap-3 mt-3">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 animate-fade-in">
                                    <i class="fas fa-key"></i> Générer un code
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-10 col-md-12">
                <div class="card border-0 shadow-lg rounded-4 p-4 animate-fade-in-up">
                    <div class="card-body p-0">
                        <h5 class="fw-bold mb-3"><i class="fas fa-list text-primary me-2"></i>Liste des codes générés</h5>
                        <div class="table-responsive premium-table">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="sticky-top bg-white shadow-sm rounded-4">
                                    <tr>
                                        <th>Code</th>
                                        <th>Date</th>
                                        <th>Expire le</th>
                                        <th>Statut</th>
                                        <th>Utilisé par</th>
                                        <th>Tentatives</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($codes as $code)
                                    <tr>
                                        <td class="fw-bold text-primary">{{ $code->code }}</td>
                                        <td>{{ $code->date->format('d/m/Y') }}</td>
                                        <td>{{ $code->expires_at->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($code->is_used)
                                                <span class="badge bg-success">Utilisé</span>
                                            @elseif($code->expires_at->isPast())
                                                <span class="badge bg-danger">Expiré</span>
                                            @else
                                                <span class="badge bg-primary">Valide</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($code->usedByTeacher)
                                                {{ $code->usedByTeacher->nom }} {{ $code->usedByTeacher->prenoms }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($code->attempts >= 3)
                                                <span class="badge bg-danger">{{ $code->attempts }}</span>
                                            @else
                                                {{ $code->attempts }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-4">
                            {{ $codes->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
