@extends('layouts.app')

@section('title', 'Générer un rapport de présence')

@section('content')
<div class="content-wrapper">
    <!-- HEADER PREMIUM -->
    <div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
        <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
            <i class="mdi mdi-file-chart fa-2x text-white"></i>
        </div>
        <div>
            <h1 class="text-white fw-bold mb-1" style="font-size:1.7rem;">Rapport de présence</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-white-50">Tableau de bord</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('esbtp.attendances.index') }}" class="text-white-50">Présences</a></li>
                    <li class="breadcrumb-item active text-white" aria-current="page">Rapport</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="container-fluid animate-fade-in-up">
        <div class="row">
            <div class="col-lg-8 col-md-7 mb-4">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                    <div class="card-body p-0">
                        <h4 class="fw-bold mb-3"><i class="mdi mdi-file-chart text-primary me-2"></i>Générer un rapport de présence</h4>
                        <p class="text-muted mb-4">Sélectionnez une classe et une période pour générer un rapport de présence.</p>
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
                        <form action="{{ route('esbtp.attendances.rapport') }}" method="POST" class="row g-4">
                            @csrf
                            <div class="col-12">
                                <label for="classe_id" class="form-label fw-semibold">Classe</label>
                                <select name="classe_id" id="classe_id" class="form-select" required>
                                    <option value="">Sélectionner une classe</option>
                                    @foreach($classes as $classe)
                                        <option value="{{ $classe->id }}">{{ $classe->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label fw-semibold">Date de début</label>
                                <input type="date" name="date_debut" id="date_debut" class="form-control" required value="{{ date('Y-m-01') }}">
                            </div>
                            <div class="col-md-6">
                                <label for="date_fin" class="form-label fw-semibold">Date de fin</label>
                                <input type="date" name="date_fin" id="date_fin" class="form-control" required value="{{ date('Y-m-t') }}">
                            </div>
                            <div class="col-12 d-flex gap-3 mt-4">
                                <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 animate-fade-in">
                                    <i class="mdi mdi-file-chart"></i> Générer le rapport
                                </button>
                                <a href="{{ route('esbtp.attendances.index') }}" class="btn btn-light btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 animate-fade-in">
                                    <i class="fas fa-arrow-left"></i> Annuler
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-5 mb-4">
                <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass animate-fade-in-up">
                    <div class="card-body p-0">
                        <h4 class="fw-bold mb-3"><i class="fas fa-info-circle text-info me-2"></i>Informations</h4>
                        <ul class="list-unstyled mb-3">
                            <li class="mb-2 d-flex align-items-center"><span class="badge bg-primary me-2"><i class="fas fa-user-check"></i></span> Taux de présence de chaque étudiant</li>
                            <li class="mb-2 d-flex align-items-center"><span class="badge bg-success me-2"><i class="fas fa-user-times"></i></span> Nombre de présences, absences, retards, absences excusées</li>
                            <li class="mb-2 d-flex align-items-center"><span class="badge bg-warning me-2"><i class="fas fa-chart-bar"></i></span> Statistiques globales par classe</li>
                        </ul>
                        <div class="mt-4">
                            <p class="fw-semibold mb-2">Vous pourrez également :</p>
                            <ul class="list-unstyled">
                                <li class="mb-2 d-flex align-items-center"><span class="badge bg-info me-2"><i class="fas fa-file-pdf"></i></span> Exporter le rapport PDF</li>
                                <li class="mb-2 d-flex align-items-center"><span class="badge bg-secondary me-2"><i class="fas fa-envelope"></i></span> Envoyer le rapport par e-mail</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier que la date de fin est postérieure à la date de début
        document.getElementById('date_fin').addEventListener('change', function() {
            const dateDebut = new Date(document.getElementById('date_debut').value);
            const dateFin = new Date(this.value);
            
            if (dateFin < dateDebut) {
                alert('La date de fin doit être postérieure à la date de début.');
                this.value = document.getElementById('date_debut').value;
            }
        });
        
        document.getElementById('date_debut').addEventListener('change', function() {
            const dateDebut = new Date(this.value);
            const dateFin = new Date(document.getElementById('date_fin').value);
            
            if (dateFin < dateDebut) {
                document.getElementById('date_fin').value = this.value;
            }
        });
    });
</script>
@endsection 