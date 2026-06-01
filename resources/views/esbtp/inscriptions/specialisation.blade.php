@extends('layouts.app')

@section('title', 'Spécialisation — ' . $inscription->etudiant->nom_complet)

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .spec-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .spec-card:hover { border-color: var(--primary, #0453cb); background: #f8fafc; }
    .spec-card.selected { border-color: var(--primary, #0453cb); background: #eff6ff; box-shadow: 0 0 0 3px rgba(4,83,203,0.1); }
    .spec-card .spec-name { font-weight: 600; font-size: 1.05rem; color: #1e293b; }
    .spec-card .spec-code { color: #64748b; font-size: 0.85rem; }
    .recap-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid #f1f5f9; }
    .recap-item:last-child { border-bottom: none; }
    .recap-label { color: #64748b; }
    .recap-value { font-weight: 600; color: #1e293b; }
    .classe-option { border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 0.5rem; cursor: pointer; }
    .classe-option:hover { border-color: var(--primary, #0453cb); }
    .classe-option.selected { border-color: var(--primary, #0453cb); background: #eff6ff; }
    .places-badge { font-size: 0.8rem; padding: 2px 8px; border-radius: 20px; }
    .places-ok { background: #dcfce7; color: #166534; }
    .places-warning { background: #fef9c3; color: #854d0e; }
    .places-danger { background: #fee2e2; color: #991b1b; }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-code-branch me-2"></i>Spécialisation</h1>
                <p class="header-subtitle">Changement de filière pour {{ $inscription->etudiant->nom_complet }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.inscriptions.show', $inscription) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à l'inscription
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('esbtp.inscriptions.specialisation.store', $inscription) }}" method="POST" id="specialisationForm">
            @csrf

            <div class="row">
                <!-- Colonne gauche : Récapitulatif -->
                <div class="col-lg-4 mb-4">
                    <div class="main-card">
                        <div class="main-card-header">
                            <div class="main-card-title"><i class="fas fa-user-graduate"></i> Inscription actuelle</div>
                        </div>
                        <div class="main-card-body">
                            <div class="recap-item">
                                <span class="recap-label">Étudiant</span>
                                <span class="recap-value">{{ $inscription->etudiant->nom_complet }}</span>
                            </div>
                            <div class="recap-item">
                                <span class="recap-label">Matricule</span>
                                <span class="recap-value">{{ $inscription->etudiant->matricule }}</span>
                            </div>
                            <div class="recap-item">
                                <span class="recap-label">Filière TC</span>
                                <span class="recap-value">{{ $inscription->filiere->name }}</span>
                            </div>
                            <div class="recap-item">
                                <span class="recap-label">Classe</span>
                                <span class="recap-value">{{ $inscription->classe->name ?? '-' }}</span>
                            </div>
                            <div class="recap-item">
                                <span class="recap-label">Niveau</span>
                                <span class="recap-value">{{ $inscription->niveau->name ?? '-' }}</span>
                            </div>
                            <div class="recap-item">
                                <span class="recap-label">Année</span>
                                <span class="recap-value">{{ $inscription->anneeUniversitaire->name ?? '-' }}</span>
                            </div>

                            @if($totalPaye > 0)
                            <hr>
                            <div class="recap-item">
                                <span class="recap-label">Total payé</span>
                                <span class="recap-value text-success">{{ number_format($totalPaye, 0, ',', ' ') }} FCFA</span>
                            </div>
                            <div class="recap-item">
                                <span class="recap-label">Report paiements</span>
                                <span class="recap-value">
                                    @if(\App\Helpers\SettingsHelper::get('tronc_commun_report_paiements', true))
                                        <span class="badge bg-success">Activé</span>
                                    @else
                                        <span class="badge bg-secondary">Désactivé</span>
                                    @endif
                                </span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Colonne droite : Choix -->
                <div class="col-lg-8">
                    <!-- Étape 1 : Choix de la spécialisation -->
                    <div class="main-card mb-4">
                        <div class="main-card-header">
                            <div class="main-card-title"><i class="fas fa-graduation-cap"></i> 1. Choisir la spécialisation</div>
                        </div>
                        <div class="main-card-body">
                            <div class="row">
                                @forelse($specialisations as $spec)
                                    <div class="col-md-6 mb-3">
                                        <div class="spec-card" data-filiere-id="{{ $spec->id }}" onclick="selectSpecialisation(this, {{ $spec->id }})">
                                            <div class="spec-name">{{ $spec->name }}</div>
                                            <div class="spec-code">{{ $spec->code }}</div>
                                            @if($spec->description)
                                                <small class="text-muted d-block mt-1">{{ Str::limit($spec->description, 80) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <div class="alert alert-warning mb-0">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Aucune spécialisation disponible pour cette filière. Créez d'abord les filières enfants.
                                        </div>
                                    </div>
                                @endforelse
                            </div>
                            <input type="hidden" name="filiere_id" id="filiere_id" value="{{ old('filiere_id') }}">
                        </div>
                    </div>

                    <!-- Étape 2 : Choix de la classe -->
                    <div class="main-card mb-4" id="classes-container" style="display: none;">
                        <div class="main-card-header">
                            <div class="main-card-title"><i class="fas fa-chalkboard"></i> 2. Choisir la classe</div>
                        </div>
                        <div class="main-card-body" id="classes-list">
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-arrow-up me-2"></i>Sélectionnez d'abord une spécialisation
                            </div>
                        </div>
                        <input type="hidden" name="classe_id" id="classe_id" value="{{ old('classe_id') }}">
                    </div>

                    <!-- Bouton de confirmation -->
                    <div id="submit-container" style="display: none;">
                        <button type="submit" class="btn btn-lg btn-primary fw-bold shadow rounded-3 px-4 py-2 w-100">
                            <i class="fas fa-check-circle me-2"></i>Confirmer la spécialisation
                        </button>
                        <small class="text-muted d-block text-center mt-2">
                            <i class="fas fa-info-circle me-1"></i>
                            L'inscription actuelle sera conservée et mise à jour avec une phase de spécialisation active.
                        </small>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const classesUrl = '{{ route("esbtp.inscriptions.specialisation.classes", $inscription) }}';

    function selectSpecialisation(el, filiereId) {
        document.querySelectorAll('.spec-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('filiere_id').value = filiereId;
        document.getElementById('classe_id').value = '';
        document.getElementById('submit-container').style.display = 'none';

        // Charger les classes via AJAX
        const container = document.getElementById('classes-container');
        const list = document.getElementById('classes-list');
        container.style.display = 'block';
        list.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Chargement...</div>';

        fetch(classesUrl + '?filiere_id=' + filiereId)
            .then(r => r.json())
            .then(data => {
                if (data.classes.length === 0) {
                    list.innerHTML = `<div class="alert alert-warning mb-0"><i class="fas fa-exclamation-triangle me-2"></i>${data.message || "Aucune classe cible n'est configurée pour cette spécialisation."}</div>`;
                    return;
                }
                let html = '';
                data.classes.forEach(c => {
                    const pct = c.places_totales > 0 ? Math.round((c.nombre_etudiants / c.places_totales) * 100) : 0;
                    let badgeClass = 'places-ok';
                    if (pct >= 90) badgeClass = 'places-danger';
                    else if (pct >= 70) badgeClass = 'places-warning';

                    html += `<div class="classe-option" onclick="selectClasse(this, ${c.id})">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${c.name}</strong>
                                <span class="text-muted ms-2">${c.code}</span>
                            </div>
                            <span class="places-badge ${badgeClass}">
                                ${c.places_disponibles} places dispo / ${c.places_totales}
                            </span>
                        </div>
                    </div>`;
                });
                list.innerHTML = html;
            })
            .catch(() => {
                list.innerHTML = '<div class="alert alert-danger mb-0">Erreur lors du chargement des classes.</div>';
            });
    }

    function selectClasse(el, classeId) {
        document.querySelectorAll('.classe-option').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('classe_id').value = classeId;
        document.getElementById('submit-container').style.display = 'block';
    }

    document.getElementById('specialisationForm').addEventListener('submit', function(e) {
        if (!document.getElementById('filiere_id').value || !document.getElementById('classe_id').value) {
            e.preventDefault();
            alert('Veuillez sélectionner une spécialisation et une classe.');
        }
    });
</script>
@endpush
