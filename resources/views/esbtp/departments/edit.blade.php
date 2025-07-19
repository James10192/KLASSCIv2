@extends('layouts.app')

@section('title', 'Modifier un Département')
@section('page_title', 'Modification du Département')

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl">
        <div class="header-content">
            <h1 class="header-title">Modifier le Département</h1>
            <p class="header-subtitle">Modification du département {{ $department->name }} ({{ $department->code }})</p>
        </div>
        <div class="header-actions">
            <a href="{{ route('esbtp.departments.index') }}" class="btn-acasi secondary">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-lg" style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-medium); padding: var(--space-md);">
            <div style="color: var(--danger); font-weight: 600; margin-bottom: var(--space-sm);">
                <i class="fas fa-exclamation-triangle"></i> Erreurs de validation
            </div>
            <ul style="margin: 0; padding-left: var(--space-lg); color: var(--danger);">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card-moderne">
        <div class="p-lg">
            <div class="section-title mb-lg">Formulaire de modification</div>
            
            <form action="{{ route('esbtp.departments.update', $department) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Informations de base -->
                    <div class="col-md-6">
                        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: var(--space-lg); font-size: var(--title-section); text-transform: uppercase; letter-spacing: 0.5px;">Informations de base</h4>
                        
                        <div class="mb-lg">
                            <label for="name" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">
                                Nom du département <span style="color: var(--danger);">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $department->name) }}" 
                                   required
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('name')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="code" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">
                                Code du département <span style="color: var(--danger);">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('code') is-invalid @enderror" 
                                   id="code" 
                                   name="code" 
                                   value="{{ old('code', $department->code) }}" 
                                   required
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            <small style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs); display: block;">Le code doit être unique et court (ex: INFO, MECA, etc.)</small>
                            @error('code')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="description" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease; resize: vertical;">{{ old('description', $department->description) }}</textarea>
                            @error('description')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <div style="display: flex; align-items: center; gap: var(--space-sm);">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="is_active" 
                                       name="is_active" 
                                       value="1" 
                                       {{ old('is_active', $department->is_active) ? 'checked' : '' }}
                                       style="width: 20px; height: 20px; accent-color: var(--primary);">
                                <label for="is_active" style="font-weight: 600; color: var(--text-primary); cursor: pointer;">Département actif</label>
                            </div>
                            <small style="color: var(--text-secondary); font-size: var(--text-small); margin-top: var(--space-xs); display: block;">Un département inactif ne sera pas visible dans les listes de sélection</small>
                        </div>
                    </div>

                    <!-- Informations du responsable -->
                    <div class="col-md-6">
                        <h4 style="color: var(--primary); font-weight: 600; margin-bottom: var(--space-lg); font-size: var(--title-section); text-transform: uppercase; letter-spacing: 0.5px;">Informations du responsable</h4>
                        
                        <div class="mb-lg">
                            <label for="head_name" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Nom du chef de département</label>
                            <input type="text" 
                                   class="form-control @error('head_name') is-invalid @enderror" 
                                   id="head_name" 
                                   name="head_name" 
                                   value="{{ old('head_name', $department->head_name) }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('head_name')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="head_title" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Titre du chef de département</label>
                            <input type="text" 
                                   class="form-control @error('head_title') is-invalid @enderror" 
                                   id="head_title" 
                                   name="head_title" 
                                   value="{{ old('head_title', $department->head_title) }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('head_title')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="email" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Email du département</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $department->email) }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('email')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="phone" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Téléphone du département</label>
                            <input type="text" 
                                   class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" 
                                   name="phone" 
                                   value="{{ old('phone', $department->phone) }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('phone')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-lg">
                            <label for="office_location" style="display: block; margin-bottom: var(--space-sm); font-weight: 600; color: var(--text-primary);">Localisation du bureau</label>
                            <input type="text" 
                                   class="form-control @error('office_location') is-invalid @enderror" 
                                   id="office_location" 
                                   name="office_location" 
                                   value="{{ old('office_location', $department->office_location) }}"
                                   style="padding: var(--space-md); border: 1px solid #e5e7eb; border-radius: var(--radius-small); font-size: var(--text-normal); transition: all 0.2s ease;">
                            @error('office_location')
                                <div style="color: var(--danger); font-size: var(--text-small); margin-top: var(--space-xs);">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div style="margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid #e5e7eb; display: flex; gap: var(--space-md);">
                    <button type="submit" class="btn-acasi primary" style="padding: var(--space-md) var(--space-xl);">
                        <i class="fas fa-save"></i> Enregistrer les modifications
                    </button>
                    <a href="{{ route('esbtp.departments.index') }}" class="btn-acasi secondary" style="padding: var(--space-md) var(--space-xl);">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
