@extends('layouts.app')

@section('title', 'Paramètres du système')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-cogs me-2"></i>
                        Paramètres de l'École
                    </h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('esbtp.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Section Informations de l'École -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-university me-2"></i>
                                    Informations de l'Établissement
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="setting_school_name" class="form-label">Nom de l'établissement <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('setting_school_name') is-invalid @enderror"
                                       id="setting_school_name" name="setting_school_name"
                                       value="{{ old('setting_school_name', \App\Helpers\SettingsHelper::get('school_name', '')) }}"
                                       placeholder="Ex: École Spéciale du Bâtiment et des Travaux Publics">
                                @error('setting_school_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_school_acronym" class="form-label">Sigle/Acronyme</label>
                                <input type="text" class="form-control @error('setting_school_acronym') is-invalid @enderror"
                                       id="setting_school_acronym" name="setting_school_acronym"
                                       value="{{ old('setting_school_acronym', \App\Helpers\SettingsHelper::get('school_acronym', '')) }}"
                                       placeholder="Ex: ESBTP">
                                @error('setting_school_acronym')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_school_address" class="form-label">Adresse <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('setting_school_address') is-invalid @enderror"
                                       id="setting_school_address" name="setting_school_address"
                                       value="{{ old('setting_school_address', \App\Helpers\SettingsHelper::get('school_address', '')) }}"
                                       placeholder="Ex: BP 2541 Yamoussoukro">
                                @error('setting_school_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_school_city" class="form-label">Ville</label>
                                <input type="text" class="form-control @error('setting_school_city') is-invalid @enderror"
                                       id="setting_school_city" name="setting_school_city"
                                       value="{{ old('setting_school_city', \App\Helpers\SettingsHelper::get('school_city', '')) }}"
                                       placeholder="Ex: Yamoussoukro">
                                @error('setting_school_city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_school_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('setting_school_email') is-invalid @enderror"
                                       id="setting_school_email" name="setting_school_email"
                                       value="{{ old('setting_school_email', \App\Helpers\SettingsHelper::get('school_email', '')) }}"
                                       placeholder="Ex: esbtpabidjan@esbtp-ci.net">
                                @error('setting_school_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_school_phone" class="form-label">Téléphone</label>
                                <input type="text" class="form-control @error('setting_school_phone') is-invalid @enderror"
                                       id="setting_school_phone" name="setting_school_phone"
                                       value="{{ old('setting_school_phone', \App\Helpers\SettingsHelper::get('school_phone', '')) }}"
                                       placeholder="Ex: 30 64 39 93">
                                @error('setting_school_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_school_mobile" class="form-label">Téléphone mobile/cellulaire</label>
                                <input type="text" class="form-control @error('setting_school_mobile') is-invalid @enderror"
                                       id="setting_school_mobile" name="setting_school_mobile"
                                       value="{{ old('setting_school_mobile', \App\Helpers\SettingsHelper::get('school_mobile', '')) }}"
                                       placeholder="Ex: 07 07 79 84 85">
                                @error('setting_school_mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_school_logo" class="form-label">Logo de l'établissement</label>
                                <input type="file" class="form-control @error('setting_school_logo') is-invalid @enderror"
                                       id="setting_school_logo" name="setting_school_logo"
                                       accept="image/*">
                                @if(\App\Helpers\SettingsHelper::get('school_logo'))
                                    <small class="text-muted">Fichier actuel: {{ basename(\App\Helpers\SettingsHelper::get('school_logo')) }}</small>
                                @endif
                                @error('setting_school_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Section Direction -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-user-tie me-2"></i>
                                    Direction
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="setting_director_name" class="form-label">Nom du directeur</label>
                                <input type="text" class="form-control @error('setting_director_name') is-invalid @enderror"
                                       id="setting_director_name" name="setting_director_name"
                                       value="{{ old('setting_director_name', \App\Helpers\SettingsHelper::get('director_name', '')) }}"
                                       placeholder="Ex: Marc BIC">
                                @error('setting_director_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_director_title" class="form-label">Titre du directeur</label>
                                <input type="text" class="form-control @error('setting_director_title') is-invalid @enderror"
                                       id="setting_director_title" name="setting_director_title"
                                       value="{{ old('setting_director_title', \App\Helpers\SettingsHelper::get('director_title', 'Directeur Général')) }}"
                                       placeholder="Ex: Directeur Général">
                                @error('setting_director_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Section PDF -->
                        <div class="row mb-4 mt-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-file-pdf me-2"></i>
                                    Configuration PDF des Bulletins
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="setting_pdf_show_logo" class="form-label">Afficher le logo</label>
                                <select class="form-control @error('setting_pdf_show_logo') is-invalid @enderror"
                                        id="setting_pdf_show_logo" name="setting_pdf_show_logo">
                                    <option value="1" {{ \App\Helpers\SettingsHelper::get('pdf_show_logo', '1') == '1' ? 'selected' : '' }}>Oui</option>
                                    <option value="0" {{ \App\Helpers\SettingsHelper::get('pdf_show_logo', '1') == '0' ? 'selected' : '' }}>Non</option>
                                </select>
                                @error('setting_pdf_show_logo')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="setting_pdf_font_size" class="form-label">Taille de police (pt)</label>
                                <input type="number" class="form-control @error('setting_pdf_font_size') is-invalid @enderror"
                                       id="setting_pdf_font_size" name="setting_pdf_font_size"
                                       value="{{ old('setting_pdf_font_size', \App\Helpers\SettingsHelper::get('pdf_font_size', '12')) }}"
                                       min="8" max="20" step="1">
                                @error('setting_pdf_font_size')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="setting_pdf_footer_text" class="form-label">Texte de pied de page</label>
                                <input type="text" class="form-control @error('setting_pdf_footer_text') is-invalid @enderror"
                                       id="setting_pdf_footer_text" name="setting_pdf_footer_text"
                                       value="{{ old('setting_pdf_footer_text', \App\Helpers\SettingsHelper::get('pdf_footer_text', '')) }}"
                                       placeholder="Ex: Bulletin informatisé, aucun duplicata n'est délivré">
                                @error('setting_pdf_footer_text')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    Enregistrer les modifications
                        </button>
                                <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-lg ms-2">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Retour au tableau de bord
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-bottom: none;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.text-primary {
    color: #667eea !important;
}

.border-bottom {
    border-color: #dee2e6 !important;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    transform: translateY(-1px);
}

.alert {
    border: none;
    border-radius: 10px;
}
</style>
@endsection
