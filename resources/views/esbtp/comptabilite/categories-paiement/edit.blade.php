@extends('layouts.app')

@section('title', 'Modifier la catégorie : ' . $categorie->nom)

@section('styles')
<style>
    .color-picker-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }

    .color-option {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        border: 2px solid transparent;
        transition: all 0.2s ease;
    }

    .color-option:hover {
        transform: scale(1.1);
    }

    .color-option.selected {
        border-color: #333;
        transform: scale(1.1);
    }

    .icon-picker-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(40px, 1fr));
        gap: 10px;
        max-height: 200px;
        overflow-y: auto;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        margin-top: 10px;
    }

    .icon-option {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 40px;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.2s ease;
        background-color: #f8f9fa;
    }

    .icon-option:hover {
        background-color: #e9ecef;
    }

    .icon-option.selected {
        background-color: #007bff;
        color: white;
    }

    .form-preview {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .preview-header {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 15px;
        color: white;
        display: flex;
        align-items: center;
    }

    .preview-icon {
        font-size: 2rem;
        margin-right: 15px;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Modifier la catégorie : {{ $categorie->nom }}</h5>
                    <div>
                        <a href="{{ route('esbtp.comptabilite.categories-paiement.show', $categorie->id) }}" class="btn btn-info">
                            <i class="fas fa-eye me-1"></i> Voir les détails
                        </a>
                        <a href="{{ route('esbtp.comptabilite.categories-paiement.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('esbtp.comptabilite.categories-paiement.update', $categorie->id) }}" method="POST" id="categoryForm">
                        @csrf
                        @method('PUT')

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nom" class="form-label">Nom de la catégorie <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('nom') is-invalid @enderror" id="nom" name="nom" value="{{ old('nom', $categorie->nom) }}" required>
                                @error('nom')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code" value="{{ old('code', $categorie->code) }}" required>
                                <small class="form-text text-muted">Code unique pour cette catégorie (ex: SCOLAR, INSCR)</small>
                                @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $categorie->description) }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="parent_id" class="form-label">Catégorie parente</label>
                                <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id" name="parent_id">
                                    <option value="">-- Aucune (catégorie principale) --</option>
                                    @foreach($categoriesParentes as $cat)
                                        @if($cat->id != $categorie->id)
                                        <option value="{{ $cat->id }}" {{ old('parent_id', $categorie->parent_id) == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->nom }}
                                        </option>
                                        @endif
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Laissez vide pour une catégorie principale</small>
                                @error('parent_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="ordre" class="form-label">Ordre d'affichage</label>
                                <input type="number" class="form-control @error('ordre') is-invalid @enderror" id="ordre" name="ordre" value="{{ old('ordre', $categorie->ordre) }}" min="1">
                                <small class="form-text text-muted">Ordre d'affichage dans les listes</small>
                                @error('ordre')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="icone" class="form-label">Icône</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i id="selectedIconPreview" class="{{ $categorie->icone }}"></i></span>
                                    <input type="text" class="form-control @error('icone') is-invalid @enderror" id="icone" name="icone" value="{{ old('icone', $categorie->icone) }}">
                                    <button class="btn btn-outline-secondary" type="button" id="iconPickerToggle">
                                        <i class="fas fa-icons"></i> Choisir
                                    </button>
                                </div>
                                <div class="icon-picker-container mt-2 d-none" id="iconPicker">
                                    <div class="icon-option" data-icon="fas fa-money-bill"><i class="fas fa-money-bill"></i></div>
                                    <div class="icon-option" data-icon="fas fa-graduation-cap"><i class="fas fa-graduation-cap"></i></div>
                                    <div class="icon-option" data-icon="fas fa-book"><i class="fas fa-book"></i></div>
                                    <div class="icon-option" data-icon="fas fa-university"><i class="fas fa-university"></i></div>
                                    <div class="icon-option" data-icon="fas fa-id-card"><i class="fas fa-id-card"></i></div>
                                    <div class="icon-option" data-icon="fas fa-clipboard"><i class="fas fa-clipboard"></i></div>
                                    <div class="icon-option" data-icon="fas fa-file-invoice"><i class="fas fa-file-invoice"></i></div>
                                    <div class="icon-option" data-icon="fas fa-file-alt"><i class="fas fa-file-alt"></i></div>
                                    <div class="icon-option" data-icon="fas fa-certificate"><i class="fas fa-certificate"></i></div>
                                    <div class="icon-option" data-icon="fas fa-award"><i class="fas fa-award"></i></div>
                                    <div class="icon-option" data-icon="fas fa-credit-card"><i class="fas fa-credit-card"></i></div>
                                    <div class="icon-option" data-icon="fas fa-wallet"><i class="fas fa-wallet"></i></div>
                                    <div class="icon-option" data-icon="fas fa-cash-register"><i class="fas fa-cash-register"></i></div>
                                    <div class="icon-option" data-icon="fas fa-money-check-alt"><i class="fas fa-money-check-alt"></i></div>
                                    <div class="icon-option" data-icon="fas fa-receipt"><i class="fas fa-receipt"></i></div>
                                    <div class="icon-option" data-icon="fas fa-donate"><i class="fas fa-donate"></i></div>
                                    <div class="icon-option" data-icon="fas fa-coins"><i class="fas fa-coins"></i></div>
                                    <div class="icon-option" data-icon="fas fa-file-invoice-dollar"><i class="fas fa-file-invoice-dollar"></i></div>
                                    <div class="icon-option" data-icon="fas fa-landmark"><i class="fas fa-landmark"></i></div>
                                    <div class="icon-option" data-icon="fas fa-percentage"><i class="fas fa-percentage"></i></div>
                                </div>
                                @error('icone')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="couleur" class="form-label">Couleur</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" id="couleurPicker" value="{{ old('couleur', $categorie->couleur) }}">
                                    <input type="text" class="form-control @error('couleur') is-invalid @enderror" id="couleur" name="couleur" value="{{ old('couleur', $categorie->couleur) }}">
                                </div>
                                <div class="color-picker-container">
                                    <div class="color-option" style="background-color: #3498db;" data-color="#3498db"></div>
                                    <div class="color-option" style="background-color: #2ecc71;" data-color="#2ecc71"></div>
                                    <div class="color-option" style="background-color: #e74c3c;" data-color="#e74c3c"></div>
                                    <div class="color-option" style="background-color: #f39c12;" data-color="#f39c12"></div>
                                    <div class="color-option" style="background-color: #9b59b6;" data-color="#9b59b6"></div>
                                    <div class="color-option" style="background-color: #1abc9c;" data-color="#1abc9c"></div>
                                    <div class="color-option" style="background-color: #34495e;" data-color="#34495e"></div>
                                    <div class="color-option" style="background-color: #e67e22;" data-color="#e67e22"></div>
                                </div>
                                @error('couleur')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="est_obligatoire" name="est_obligatoire" value="1" {{ old('est_obligatoire', $categorie->est_obligatoire) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="est_obligatoire">Catégorie obligatoire</label>
                                </div>
                                <small class="form-text text-muted">Les frais obligatoires sont exigés pour tous les étudiants</small>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="est_actif" name="est_actif" value="1" {{ old('est_actif', $categorie->est_actif) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="est_actif">Catégorie active</label>
                                </div>
                                <small class="form-text text-muted">Les catégories inactives ne peuvent pas être utilisées</small>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="{{ route('esbtp.comptabilite.categories-paiement.show', $categorie->id) }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            @if($categorie->enfants->count() > 0)
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Sous-catégories associées</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Code</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($categorie->enfants as $enfant)
                                <tr>
                                    <td>
                                        <i class="{{ $enfant->icone }}" style="{{ $enfant->getStyleIconeAttribute() }}"></i>
                                        {{ $enfant->nom }}
                                    </td>
                                    <td>{{ $enfant->code }}</td>
                                    <td>
                                        @if($enfant->est_actif)
                                        <span class="badge bg-success">Actif</span>
                                        @else
                                        <span class="badge bg-danger">Inactif</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('esbtp.comptabilite.categories-paiement.edit', $enfant->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Aperçu</h5>
                </div>
                <div class="card-body">
                    <div class="form-preview">
                        <div class="preview-header" id="previewHeader" style="background-color: {{ $categorie->couleur }};">
                            <i id="previewIcon" class="{{ $categorie->icone }} preview-icon"></i>
                            <h5 id="previewTitle" class="mb-0">{{ $categorie->nom }}</h5>
                        </div>
                        <div class="mt-3">
                            <p><strong>Code:</strong> <span id="previewCode">{{ $categorie->code }}</span></p>
                            <p><strong>Description:</strong> <span id="previewDescription">{{ $categorie->description ?: 'Aucune description' }}</span></p>
                            <p>
                                <span id="previewObligatoire" class="badge bg-danger" style="{{ $categorie->est_obligatoire ? '' : 'display: none;' }}">Obligatoire</span>
                                <span id="previewActif" class="badge bg-success" style="{{ $categorie->est_actif ? '' : 'display: none;' }}">Actif</span>
                            </p>
                        </div>
                    </div>

                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-info-circle me-2"></i>Informations</h6>
                        <p class="mb-2"><strong>Créée le:</strong> {{ $categorie->created_at->format('d/m/Y H:i') }}</p>
                        <p class="mb-2"><strong>Dernière modification:</strong> {{ $categorie->updated_at->format('d/m/Y H:i') }}</p>

                        @if($categorie->parent)
                        <p class="mb-0"><strong>Catégorie parente:</strong> {{ $categorie->parent->nom }}</p>
                        @endif
                    </div>

                    @if($categorie->paiements->count() > 0)
                    <div class="alert alert-warning mt-4">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Attention</h6>
                        <p class="mb-0">Cette catégorie est utilisée par {{ $categorie->paiements->count() }} paiement(s). Les modifications affecteront ces paiements.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial selected icon
        const currentIcon = '{{ $categorie->icone }}';
        document.querySelectorAll('.icon-option').forEach(option => {
            if (option.dataset.icon === currentIcon) {
                option.classList.add('selected');
            }
        });

        // Set initial selected color
        const currentColor = '{{ $categorie->couleur }}';
        document.querySelectorAll('.color-option').forEach(option => {
            if (option.dataset.color === currentColor) {
                option.classList.add('selected');
            }
        });

        // Icon picker
        const iconPicker = document.getElementById('iconPicker');
        const iconPickerToggle = document.getElementById('iconPickerToggle');
        const iconInput = document.getElementById('icone');
        const selectedIconPreview = document.getElementById('selectedIconPreview');
        const previewIcon = document.getElementById('previewIcon');

        iconPickerToggle.addEventListener('click', function() {
            iconPicker.classList.toggle('d-none');
        });

        document.querySelectorAll('.icon-option').forEach(option => {
            option.addEventListener('click', function() {
                const icon = this.dataset.icon;
                iconInput.value = icon;
                selectedIconPreview.className = icon;
                previewIcon.className = `${icon} preview-icon`;

                document.querySelectorAll('.icon-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');

                iconPicker.classList.add('d-none');
            });
        });

        // Color picker
        const colorOptions = document.querySelectorAll('.color-option');
        const colorInput = document.getElementById('couleur');
        const colorPicker = document.getElementById('couleurPicker');
        const previewHeader = document.getElementById('previewHeader');

        colorOptions.forEach(option => {
            option.addEventListener('click', function() {
                const color = this.dataset.color;
                colorInput.value = color;
                colorPicker.value = color;
                previewHeader.style.backgroundColor = color;

                colorOptions.forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
            });
        });

        colorPicker.addEventListener('input', function() {
            const color = this.value;
            colorInput.value = color;
            previewHeader.style.backgroundColor = color;

            colorOptions.forEach(opt => {
                opt.classList.remove('selected');
                if(opt.dataset.color === color) {
                    opt.classList.add('selected');
                }
            });
        });

        colorInput.addEventListener('input', function() {
            const color = this.value;
            colorPicker.value = color;
            previewHeader.style.backgroundColor = color;

            colorOptions.forEach(opt => {
                opt.classList.remove('selected');
                if(opt.dataset.color === color) {
                    opt.classList.add('selected');
                }
            });
        });

        // Live preview
        const nomInput = document.getElementById('nom');
        const codeInput = document.getElementById('code');
        const descriptionInput = document.getElementById('description');
        const obligatoireInput = document.getElementById('est_obligatoire');
        const actifInput = document.getElementById('est_actif');

        const previewTitle = document.getElementById('previewTitle');
        const previewCode = document.getElementById('previewCode');
        const previewDescription = document.getElementById('previewDescription');
        const previewObligatoire = document.getElementById('previewObligatoire');
        const previewActif = document.getElementById('previewActif');

        nomInput.addEventListener('input', function() {
            previewTitle.textContent = this.value || 'Nom de la catégorie';
        });

        codeInput.addEventListener('input', function() {
            previewCode.textContent = this.value || 'CODE';
        });

        descriptionInput.addEventListener('input', function() {
            previewDescription.textContent = this.value || 'Aucune description';
        });

        obligatoireInput.addEventListener('change', function() {
            previewObligatoire.style.display = this.checked ? 'inline-block' : 'none';
        });

        actifInput.addEventListener('change', function() {
            previewActif.style.display = this.checked ? 'inline-block' : 'none';
        });

        // Form validation
        document.getElementById('categoryForm').addEventListener('submit', function(event) {
            const nom = nomInput.value.trim();
            const code = codeInput.value.trim();

            let isValid = true;

            if (nom === '') {
                nomInput.classList.add('is-invalid');
                isValid = false;
            } else {
                nomInput.classList.remove('is-invalid');
            }

            if (code === '') {
                codeInput.classList.add('is-invalid');
                isValid = false;
            } else {
                codeInput.classList.remove('is-invalid');
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    });
</script>
@endsection
