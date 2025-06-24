@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ isset($evaluation) ? 'Modifier l\'évaluation' : 'Nouvelle évaluation' }}</h5>
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

                    <form action="{{ isset($evaluation) ? route('esbtp.evaluations.update', $evaluation) : route('esbtp.evaluations.store') }}" method="POST">
                        @csrf
                        @if(isset($evaluation))
                            @method('PUT')
                        @endif

                        @yield('content_form')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HEADER PREMIUM -->
<div class="bg-gradient-primary rounded-4 p-5 mb-4 d-flex align-items-center gap-4 animate-fade-in-up" style="background: linear-gradient(135deg, #0453cb 0%, #5e91de 100%); min-height: 120px;">
    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
        <i class="fas fa-file-signature fa-2x text-white"></i>
    </div>
    <div>
        <h1 class="text-white fw-bold mb-1" style="font-size:1.7rem;">{{ isset($evaluation) ? 'Modifier l\'évaluation' : 'Nouvelle évaluation' }}</h1>
        <p class="text-white-50 mb-0">Gestion avancée des évaluations scolaires</p>
    </div>
</div>

<div class="container-fluid animate-fade-in-up">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card border-0 shadow-lg rounded-4 p-4 premium-glass">
                <div class="card-body p-0">
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
                    <form action="{{ isset($evaluation) ? route('esbtp.evaluations.update', $evaluation) : route('esbtp.evaluations.store') }}" method="POST" class="row g-4">
                        @csrf
                        @if(isset($evaluation))
                            @method('PUT')
                        @endif
                        @yield('content_form')
                        <div class="d-flex gap-3 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 animate-fade-in">
                                <i class="fas fa-save"></i> {{ isset($evaluation) ? 'Enregistrer les modifications' : 'Créer l\'évaluation' }}
                            </button>
                            <a href="{{ route('esbtp.evaluations.index') }}" class="btn btn-light btn-lg rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center gap-2 animate-fade-in">
                                <i class="fas fa-arrow-left"></i> Retour
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
