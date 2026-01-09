@extends('layouts.app')

@section('title', 'Attacher des matières aux classes - KLASSCI')

@section('styles')
<style>
    .filter-card {
        transition: all 0.3s ease;
    }
    .filter-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .selection-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .selection-item {
        transition: background-color 0.2s ease;
    }
    .selection-item:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.1);
    }
    .selection-item.selected {
        background-color: rgba(var(--bs-primary-rgb), 0.2);
    }
</style>
@endsection

@section('content')
<div class="container-fluid" x-data="attachMatieres()">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Attacher des matières aux classes</h5>
                    <a href="{{ route('esbtp.matieres.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour à la liste
                    </a>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.matieres.process-attach-to-classes') }}" method="POST">
                        @csrf

                        <div class="row mb-4">
                            <!-- Filtres -->
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card filter-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Filtre par filière</h6>
                                        <select class="form-select" x-model="selectedFiliere" @change="filterItems()">
                                            <option value="">Toutes les filières</option>
                                            @foreach($filieres as $filiere)
                                                <option value="{{ $filiere->id }}">{{ $filiere->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card filter-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Filtre par niveau</h6>
                                        <select class="form-select" x-model="selectedNiveau" @change="filterItems()">
                                            <option value="">Tous les niveaux</option>
                                            @foreach($niveaux as $niveau)
                                                <option value="{{ $niveau->id }}">{{ $niveau->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card filter-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Coefficient</h6>
                                        <input type="number" name="coefficient" class="form-control" min="0" step="0.5" required
                                               placeholder="Coefficient par défaut">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card filter-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title">Total des heures</h6>
                                        <input type="number" name="total_heures" class="form-control" min="0" required
                                               placeholder="Nombre total d'heures">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Liste des matières -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Matières disponibles</h6>
                                        <div class="input-group" style="width: 250px;">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" placeholder="Rechercher une matière..."
                                                   x-model="searchMatiere" @input="filterItems()">
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="selection-list">
                                            <template x-for="matiere in filteredMatieres" :key="matiere.id">
                                                <div class="selection-item p-3 border-bottom d-flex justify-content-between align-items-center"
                                                     :class="{'selected': selectedMatieres.includes(matiere.id)}"
                                                     @click="toggleMatiere(matiere.id)">
                                                    <div>
                                                        <h6 class="mb-1" x-text="matiere.name"></h6>
                                                        <small class="text-muted" x-text="matiere.code"></small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input"
                                                               :checked="selectedMatieres.includes(matiere.id)"
                                                               @click.stop>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Liste des classes -->
                            <div class="col-md-6 mb-4">
                                <div class="card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Classes disponibles</h6>
                                        <div class="input-group" style="width: 250px;">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" class="form-control" placeholder="Rechercher une classe..."
                                                   x-model="searchClasse" @input="filterItems()">
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="selection-list">
                                            <template x-for="classe in filteredClasses" :key="classe.id">
                                                <div class="selection-item p-3 border-bottom d-flex justify-content-between align-items-center"
                                                     :class="{'selected': selectedClasses.includes(classe.id)}"
                                                     @click="toggleClasse(classe.id)">
                                                    <div>
                                                        <h6 class="mb-1" x-text="classe.name"></h6>
                                                        <small class="text-muted">
                                                            <span x-text="classe.filiere.name"></span> -
                                                            <span x-text="classe.niveau.name"></span>
                                                        </small>
                                                    </div>
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input"
                                                               :checked="selectedClasses.includes(classe.id)"
                                                               @click.stop>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Champs cachés pour les sélections -->
                        <template x-for="matiereId in selectedMatieres" :key="matiereId">
                            <input type="hidden" name="matiere_ids[]" :value="matiereId">
                        </template>
                        <template x-for="classeId in selectedClasses" :key="classeId">
                            <input type="hidden" name="classe_ids[]" :value="classeId">
                        </template>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="me-3">
                                    <strong x-text="selectedMatieres.length"></strong> matière(s) sélectionnée(s)
                                </span>
                                <span>
                                    <strong x-text="selectedClasses.length"></strong> classe(s) sélectionnée(s)
                                </span>
                            </div>
                            <button type="submit" class="btn btn-primary"
                                    :disabled="selectedMatieres.length === 0 || selectedClasses.length === 0">
                                <i class="fas fa-link me-1"></i>Attacher les matières aux classes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('attachToClasses', () => ({
            selectedMatieres: @json($selectedMatieres->pluck('id') ?? []),
            selectedClasses: [],
            searchMatiere: '',
            searchClasse: '',
            matieres: @json($matieres),
            classes: @json($classes),
            selectedFiliere: '',
            selectedNiveau: '',
            filteredMatieres() {
                return this.matieres.filter(matiere => {
                    const matchesSearch = matiere.name.toLowerCase().includes(this.searchMatiere.toLowerCase()) ||
                                        matiere.code.toLowerCase().includes(this.searchMatiere.toLowerCase());

                    const matchesFiliere = !this.selectedFiliere ||
                                         matiere.filieres.some(f => f.id === parseInt(this.selectedFiliere));

                    const matchesNiveau = !this.selectedNiveau ||
                                        matiere.niveaux.some(n => n.id === parseInt(this.selectedNiveau));

                    return matchesSearch && matchesFiliere && matchesNiveau;
                });
            },
            filteredClasses() {
                return this.classes.filter(classe => {
                    const matchesSearch = classe.name.toLowerCase().includes(this.searchClasse.toLowerCase());

                    const matchesFiliere = !this.selectedFiliere ||
                                         classe.filiere.id === parseInt(this.selectedFiliere);

                    const matchesNiveau = !this.selectedNiveau ||
                                        classe.niveau.id === parseInt(this.selectedNiveau);

                    return matchesSearch && matchesFiliere && matchesNiveau;
                });
            },
            toggleMatiere(id) {
                const index = this.selectedMatieres.indexOf(id);
                if (index === -1) {
                    this.selectedMatieres.push(id);
                } else {
                    this.selectedMatieres.splice(index, 1);
                }
            },
            toggleClasse(id) {
                const index = this.selectedClasses.indexOf(id);
                if (index === -1) {
                    this.selectedClasses.push(id);
                } else {
                    this.selectedClasses.splice(index, 1);
                }
            },
            filterItems() {
                // La fonction est appelée automatiquement grâce aux computed properties
            }
        }))
    })
</script>
@endsection
