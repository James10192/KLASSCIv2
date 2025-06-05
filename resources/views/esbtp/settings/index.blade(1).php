@extends('layouts.app')

@section('title', 'Configuration du Système')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-cogs"></i> Configuration du Système ESBTP
                    </h3>
                    <div class="btn-group">
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#backupModal">
                            <i class="fas fa-save"></i> Créer une Sauvegarde
                        </button>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#restoreModal">
                            <i class="fas fa-history"></i> Restaurer
                        </button>
                        <button type="button" class="btn btn-warning" onclick="validateAllSettings()">
                            <i class="fas fa-check-circle"></i> Valider Configuration
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Messages de feedback -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i>
                            {{ session('success') }}
                            @if(session('updated_count'))
                                <br><small class="text-muted">{{ session('updated_count') }} paramètre(s) mis à jour</small>
                            @endif
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('warning'))
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i>
                            {{ session('warning') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle"></i>
                            {{ session('info') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Alertes de validation dynamiques -->
                    <div id="validation-alerts"></div>

                    <!-- Onglets de configuration -->
                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="establishment-tab" data-bs-toggle="tab" href="#establishment" role="tab" aria-controls="establishment" aria-selected="true">
                                <i class="fas fa-university"></i> Établissement
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="academic-tab" data-bs-toggle="tab" href="#academic" role="tab" aria-controls="academic" aria-selected="false">
                                <i class="fas fa-graduation-cap"></i> Académique
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pdf-tab" data-bs-toggle="tab" href="#pdf" role="tab" aria-controls="pdf" aria-selected="false">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="attendance-tab" data-bs-toggle="tab" href="#attendance" role="tab" aria-controls="attendance" aria-selected="false">
                                <i class="fas fa-calendar-check"></i> Assiduité
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="bulletin-tab" data-bs-toggle="tab" href="#bulletin" role="tab" aria-controls="bulletin" aria-selected="false">
                                <i class="fas fa-file-alt"></i> Bulletins
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="notifications-tab" data-bs-toggle="tab" href="#notifications" role="tab" aria-controls="notifications" aria-selected="false">
                                <i class="fas fa-bell"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="interface-tab" data-bs-toggle="tab" href="#interface" role="tab" aria-controls="interface" aria-selected="false">
                                <i class="fas fa-palette"></i> Interface
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="general-tab" data-bs-toggle="tab" href="#general" role="tab" aria-controls="general" aria-selected="false">
                                <i class="fas fa-cog"></i> Général
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content" id="settingsTabContent">
                        <!-- Onglet Établissement -->
                        <div class="tab-pane fade show active" id="establishment" role="tabpanel" aria-labelledby="establishment-tab">
                            <form id="establishment-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['establishment'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="setting_{{ $setting->key }}"
                                                       name="setting_{{ $setting->key }}"
                                                       value="1"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Activé
                                                </label>
                                            </div>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>

                        <!-- Onglet Académique -->
                        <div class="tab-pane fade" id="academic" role="tabpanel" aria-labelledby="academic-tab">
                            <form id="academic-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['academic'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="setting_{{ $setting->key }}"
                                                       name="setting_{{ $setting->key }}"
                                                       value="1"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Activé
                                                </label>
                                            </div>
                                        @elseif($setting->key === 'semester_system')
                                            <select class="form-control" id="setting_{{ $setting->key }}" name="setting_{{ $setting->key }}">
                                                <option value="semester" {{ $setting->value === 'semester' ? 'selected' : '' }}>Semestre</option>
                                                <option value="trimester" {{ $setting->value === 'trimester' ? 'selected' : '' }}>Trimestre</option>
                                                <option value="quarter" {{ $setting->value === 'quarter' ? 'selected' : '' }}>Quadrimestre</option>
                                            </select>
                                        @elseif($setting->type === 'integer')
                                            <input type="number" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>

                        <!-- Onglet PDF -->
                        <div class="tab-pane fade" id="pdf" role="tabpanel" aria-labelledby="pdf-tab">
                            <form id="pdf-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['pdf'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="setting_{{ $setting->key }}"
                                                       name="setting_{{ $setting->key }}"
                                                       value="1"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Activé
                                                </label>
                                            </div>
                                        @elseif($setting->key === 'pdf_orientation')
                                            <select class="form-control" id="setting_{{ $setting->key }}" name="setting_{{ $setting->key }}">
                                                <option value="portrait" {{ $setting->value === 'portrait' ? 'selected' : '' }}>Portrait</option>
                                                <option value="landscape" {{ $setting->value === 'landscape' ? 'selected' : '' }}>Paysage</option>
                                            </select>
                                        @elseif($setting->key === 'pdf_format')
                                            <select class="form-control" id="setting_{{ $setting->key }}" name="setting_{{ $setting->key }}">
                                                <option value="A4" {{ $setting->value === 'A4' ? 'selected' : '' }}>A4</option>
                                                <option value="A3" {{ $setting->value === 'A3' ? 'selected' : '' }}>A3</option>
                                                <option value="Letter" {{ $setting->value === 'Letter' ? 'selected' : '' }}>Letter</option>
                                            </select>
                                        @elseif(strpos($setting->key, 'alignment') !== false)
                                            <select class="form-control" id="setting_{{ $setting->key }}" name="setting_{{ $setting->key }}">
                                                <option value="left" {{ $setting->value === 'left' ? 'selected' : '' }}>Gauche</option>
                                                <option value="center" {{ $setting->value === 'center' ? 'selected' : '' }}>Centre</option>
                                                <option value="right" {{ $setting->value === 'right' ? 'selected' : '' }}>Droite</option>
                                            </select>
                                        @elseif($setting->type === 'integer')
                                            <input type="number" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>

                        <!-- Onglet Assiduité -->
                        <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                            <form id="attendance-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['attendance'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="setting_{{ $setting->key }}"
                                                       name="setting_{{ $setting->key }}"
                                                       value="1"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Activé
                                                </label>
                                            </div>
                                        @elseif($setting->type === 'integer')
                                            <input type="number" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>

                        <!-- Onglet Bulletins -->
                        <div class="tab-pane fade" id="bulletin" role="tabpanel" aria-labelledby="bulletin-tab">
                            <form id="bulletin-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['bulletin'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="setting_{{ $setting->key }}"
                                                       name="setting_{{ $setting->key }}"
                                                       value="1"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Activé
                                                </label>
                                            </div>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>

                        <!-- Onglet Notifications -->
                        <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                            <form id="notifications-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['notifications'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="setting_{{ $setting->key }}"
                                                       name="setting_{{ $setting->key }}"
                                                       value="1"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Activé
                                                </label>
                                            </div>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>

                        <!-- Onglet Interface -->
                        <div class="tab-pane fade" id="interface" role="tabpanel" aria-labelledby="interface-tab">
                            <form id="interface-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['interface'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'string' && strpos($setting->key, 'color') !== false)
                                            <input type="color" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>

                        <!-- Onglet Général -->
                        <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                            <form id="general-form" class="mt-3" method="POST" action="{{ route('esbtp.settings.update') }}">
                                @csrf
                                @method('PUT')
                                <div class="row">
                                    @foreach(($settings['general'] ?? collect()) as $setting)
                                    <div class="col-md-6 mb-3">
                                        <label for="setting_{{ $setting->key }}" class="form-label">
                                            {{ $setting->description }}
                                            @if($setting->is_required)
                                                <span class="text-danger">*</span>
                                            @endif
                                        </label>

                                        @if($setting->type === 'boolean')
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input"
                                                       id="setting_{{ $setting->key }}"
                                                       name="setting_{{ $setting->key }}"
                                                       value="1"
                                                       {{ $setting->value ? 'checked' : '' }}>
                                                <label class="form-check-label" for="setting_{{ $setting->key }}">
                                                    Activé
                                                </label>
                                            </div>
                                        @elseif($setting->type === 'string' && strpos($setting->key, 'color') !== false)
                                            <input type="color" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @elseif($setting->key === 'app_locale')
                                            <select class="form-control" id="setting_{{ $setting->key }}" name="setting_{{ $setting->key }}">
                                                <option value="fr" {{ $setting->value === 'fr' ? 'selected' : '' }}>Français</option>
                                                <option value="en" {{ $setting->value === 'en' ? 'selected' : '' }}>English</option>
                                            </select>
                                        @else
                                            <input type="text" class="form-control"
                                                   id="setting_{{ $setting->key }}"
                                                   name="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   {{ $setting->is_required ? 'required' : '' }}>
                                        @endif

                                        @if($setting->description)
                                            <small class="form-text text-muted">{{ $setting->description }}</small>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Sauvegarder les Paramètres
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Sauvegarde -->
<div class="modal fade" id="backupModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Créer une Sauvegarde</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="backup-form">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="backup_name">Nom de la sauvegarde</label>
                        <input type="text" class="form-control" id="backup_name" name="backup_name" required>
                    </div>
                    <div class="form-group">
                        <label for="backup_description">Description</label>
                        <textarea class="form-control" id="backup_description" name="backup_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Créer la Sauvegarde</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Restauration -->
<div class="modal fade" id="restoreModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Restaurer une Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="backups-list">
                            <!-- Chargé via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
/* Correction du z-index pour les modals - valeurs plus élevées */
.modal {
    z-index: 9999 !important;
}

.modal-backdrop {
    z-index: 9998 !important;
}

/* Force l'affichage des modals au premier plan */
.modal.show {
    z-index: 9999 !important;
}

.modal-dialog {
    z-index: 10000 !important;
    position: relative;
}

/* S'assurer que le contenu du modal est cliquable */
.modal-content {
    z-index: 10001 !important;
    position: relative;
}

/* Amélioration de l'apparence des alertes */
.alert {
    border-left: 4px solid;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
}

.alert-success {
    border-left-color: #28a745;
    background-color: #d4edda;
    color: #155724;
}

.alert-danger {
    border-left-color: #dc3545;
    background-color: #f8d7da;
    color: #721c24;
}

.alert-warning {
    border-left-color: #ffc107;
    background-color: #fff3cd;
    color: #856404;
}

.alert-info {
    border-left-color: #17a2b8;
    background-color: #d1ecf1;
    color: #0c5460;
}

/* Animation pour les alertes */
.alert {
    animation: slideInDown 0.3s ease-out;
}

@keyframes slideInDown {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Amélioration des boutons dans les modals */
.modal-footer .btn {
    min-width: 100px;
}

/* Style pour les onglets */
.nav-tabs .nav-link {
    border-radius: 0.375rem 0.375rem 0 0;
    margin-right: 0.25rem;
}

.nav-tabs .nav-link.active {
    background-color: var(--bs-primary);
    color: white;
    border-color: var(--bs-primary);
}

/* Amélioration des formulaires */
.form-label {
    font-weight: 600;
    color: #495057;
}

.form-control:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), 0.25);
}

/* Style pour les boutons de sauvegarde */
.btn-primary {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    transition: all 0.2s ease-in-out;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* Indicateur de chargement pour les boutons */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: #ffffff;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Correction spécifique pour Bootstrap 5 modals */
.modal.fade .modal-dialog {
    transition: transform 0.3s ease-out;
    transform: translate(0, -50px);
}

.modal.show .modal-dialog {
    transform: none;
}

/* Force la visibilité des modals */
.modal.show {
    display: block !important;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialiser les onglets Bootstrap
    $('#settingsTabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var target = $(e.target).attr("href"); // onglet activé
        var relatedTarget = $(e.relatedTarget).attr("href"); // onglet précédent
        console.log('Onglet activé:', target);
    });

    // Gestion des formulaires de configuration
    $('form[id$="-form"]').on('submit', function(e) {
        e.preventDefault();

        const formId = $(this).attr('id');
        const category = formId.replace('-form', '');

        saveSettings(category, $(this));
    });

    // Gestion du formulaire de sauvegarde
    $('#backup-form').on('submit', function(e) {
        e.preventDefault();
        createBackup();
    });

    // Charger les sauvegardes quand le modal s'ouvre
    $('#restoreModal').on('show.bs.modal', function() {
        loadBackups();
    });

    // S'assurer que Bootstrap est chargé et fonctionnel
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap 5 not loaded!');
    } else {
        console.log('Bootstrap 5 tabs initialized successfully');
    }
});

function saveSettings(category, form) {
    const formData = new FormData(form[0]);
    const submitBtn = form.find('button[type="submit"]');
    const originalText = submitBtn.html();

    // Ajouter l'indicateur de chargement
    submitBtn.addClass('btn-loading').prop('disabled', true);
    submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Sauvegarde en cours...');

    $.ajax({
        url: '{{ route("esbtp.settings.update") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            // Restaurer le bouton
            submitBtn.removeClass('btn-loading').prop('disabled', false);
            submitBtn.html('<i class="fas fa-check"></i> Sauvegardé !');

            // Message de succès avec animation
            showAlert('success', '<i class="fas fa-check-circle"></i> Configuration sauvegardée avec succès!');

            // Restaurer le texte original après 2 secondes
            setTimeout(() => {
                submitBtn.html(originalText);
            }, 2000);

            // Faire défiler vers le haut pour voir le message
            $('html, body').animate({
                scrollTop: 0
            }, 500);
        },
        error: function(xhr) {
            // Restaurer le bouton
            submitBtn.removeClass('btn-loading').prop('disabled', false);
            submitBtn.html('<i class="fas fa-exclamation-triangle"></i> Erreur');

            let errorMessage = 'Erreur lors de la sauvegarde';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                const errors = Object.values(xhr.responseJSON.errors).flat();
                errorMessage = errors.join('<br>');
            }

            showAlert('danger', '<i class="fas fa-exclamation-triangle"></i> ' + errorMessage);

            // Restaurer le texte original après 3 secondes
            setTimeout(() => {
                submitBtn.html(originalText);
            }, 3000);

            // Faire défiler vers le haut pour voir le message
            $('html, body').animate({
                scrollTop: 0
            }, 500);
        }
    });
}

function createBackup() {
    const formData = new FormData($('#backup-form')[0]);

    $.ajax({
        url: '{{ route("esbtp.settings.backup") }}',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            const backupModal = bootstrap.Modal.getInstance(document.getElementById('backupModal'));
            backupModal.hide();
            showAlert('success', 'Sauvegarde créée avec succès!');
            $('#backup-form')[0].reset();
        },
        error: function(xhr) {
            showAlert('danger', 'Erreur lors de la création de la sauvegarde: ' + xhr.responseJSON.message);
        }
    });
}

function loadBackups() {
    $.ajax({
        url: '{{ route("esbtp.settings.backups") }}',
        method: 'GET',
        success: function(response) {
            let html = '';
            response.backups.forEach(function(backup) {
                html += `
                    <tr>
                        <td>${backup.backup_name}</td>
                        <td>${backup.description || '-'}</td>
                        <td>${new Date(backup.created_at).toLocaleString()}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="restoreBackup(${backup.id})">
                                <i class="fas fa-undo"></i> Restaurer
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteBackup(${backup.id})">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#backups-list').html(html);
        }
    });
}

function restoreBackup(backupId) {
    if (confirm('Êtes-vous sûr de vouloir restaurer cette configuration? Les paramètres actuels seront remplacés.')) {
        $.ajax({
            url: '{{ route("esbtp.settings.restore", ":id") }}'.replace(':id', backupId),
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                const restoreModal = bootstrap.Modal.getInstance(document.getElementById('restoreModal'));
                restoreModal.hide();
                showAlert('success', 'Configuration restaurée avec succès!');
                setTimeout(() => location.reload(), 2000);
            },
            error: function(xhr) {
                showAlert('danger', 'Erreur lors de la restauration: ' + xhr.responseJSON.message);
            }
        });
    }
}

function deleteBackup(backupId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette sauvegarde?')) {
        $.ajax({
            url: '{{ route("esbtp.settings.backup.delete", ":id") }}'.replace(':id', backupId),
            method: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                showAlert('success', 'Sauvegarde supprimée avec succès!');
                loadBackups();
            },
            error: function(xhr) {
                showAlert('danger', 'Erreur lors de la suppression: ' + xhr.responseJSON.message);
            }
        });
    }
}

function validateAllSettings() {
    $.ajax({
        url: '{{ route("esbtp.settings.validate") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.valid) {
                showAlert('success', 'Toutes les configurations sont valides!');
            } else {
                let errorHtml = '<ul>';
                response.errors.forEach(function(error) {
                    errorHtml += `<li>${error}</li>`;
                });
                errorHtml += '</ul>';
                showAlert('warning', 'Configurations manquantes ou invalides:' + errorHtml);
            }
        }
    });
}

function showAlert(type, message) {
    // Supprimer les alertes existantes
    $('#validation-alerts').empty();
    $('.alert').not('.alert-permanent').fadeOut(300, function() {
        $(this).remove();
    });

    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;

    // Ajouter la nouvelle alerte
    $('#validation-alerts').html(alertHtml);

    // Auto-dismiss après 5 secondes pour les succès, 8 secondes pour les erreurs
    const timeout = type === 'success' ? 5000 : 8000;
    setTimeout(() => {
        $('#validation-alerts .alert').fadeOut(300, function() {
            $(this).remove();
        });
    }, timeout);
}
</script>
@endsection
