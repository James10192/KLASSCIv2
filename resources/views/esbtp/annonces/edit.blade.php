@extends('layouts.app')

@section('title', 'Modifier l\'annonce : ' . $annonce->titre . ' - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .current-file {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
    }

    .file-info {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    .file-info a {
        text-decoration: none;
        font-weight: 500;
    }

    .file-info a:hover {
        text-decoration: underline;
    }

    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .form-check-input {
        margin: 0;
    }

    .form-check-label {
        margin: 0;
        font-size: 14px;
        color: #6b7280;
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Header Section -->
        <div class="dashboard-header">
            <div class="header-left">
                <h1><i class="fas fa-edit me-2"></i>Modifier l'annonce</h1>
                <p class="header-subtitle">{{ $annonce->titre }}</p>
            </div>
            <div class="header-actions">
                <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-acasi secondary">
                    <i class="fas fa-eye"></i>Voir l'annonce
                </a>
                <a href="{{ route('esbtp.annonces.index') }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>Retour à la liste
                </a>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert-modern error">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h4>Erreur de validation</h4>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form action="{{ route('esbtp.annonces.update', $annonce) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-lg-8">
                    <!-- Informations générales -->
                    <div class="main-card mb-4">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-info-circle"></i>
                                Informations générales
                            </div>
                        </div>
                        <div class="main-card-body">
                            <div class="form-group">
                                <label for="titre" class="form-label">Titre de l'annonce <span class="required">*</span></label>
                                <input type="text" id="titre" name="titre" class="form-input @error('titre') error @enderror" 
                                       value="{{ old('titre', $annonce->titre) }}" placeholder="Titre clair et concis" required>
                                @error('titre')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="contenu" class="form-label">Contenu <span class="required">*</span></label>
                                <textarea id="contenu" name="contenu" class="form-textarea @error('contenu') error @enderror" 
                                          rows="6" placeholder="Contenu détaillé de l'annonce..." required>{{ old('contenu', $annonce->contenu) }}</textarea>
                                @error('contenu')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="piece_jointe" class="form-label">Pièce jointe (optionnel)</label>
                                @if($annonce->piece_jointe)
                                    <div class="current-file mb-3">
                                        <div class="file-info">
                                            <i class="fas fa-paperclip text-primary me-2"></i>
                                            <strong>Fichier actuel:</strong>
                                            <a href="{{ asset('storage/' . $annonce->piece_jointe) }}" target="_blank" class="text-primary ms-2">
                                                {{ basename($annonce->piece_jointe) }}
                                            </a>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="supprimer_piece_jointe" id="supprimer_piece_jointe" value="1">
                                            <label class="form-check-label" for="supprimer_piece_jointe">
                                                Supprimer le fichier actuel
                                            </label>
                                        </div>
                                    </div>
                                @endif
                                <input type="file" id="piece_jointe" name="piece_jointe" class="form-file @error('piece_jointe') error @enderror">
                                @error('piece_jointe')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                <div class="form-help">Formats acceptés: PDF, Word, Excel, Images (max 5MB)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Options de publication -->
                    <div class="main-card mb-4">
                        <div class="main-card-header">
                            <div class="main-card-title">
                                <i class="fas fa-cog"></i>
                                Options de publication
                            </div>
                        </div>
                        <div class="main-card-body">
                            <div class="form-group">
                                <label for="status" class="form-label">Statut de publication <span class="required">*</span></label>
                                <select name="is_published" id="status" class="form-select-single @error('is_published') error @enderror" required>
                                    <option value="0" {{ old('is_published', $annonce->is_published ? '1' : '0') == '0' ? 'selected' : '' }}>Brouillon</option>
                                    <option value="1" {{ old('is_published', $annonce->is_published ? '1' : '0') == '1' ? 'selected' : '' }}>Publiée</option>
                                </select>
                                @error('is_published')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                <div class="form-help">Les annonces en brouillon ne sont pas visibles par les destinataires.</div>
                            </div>

                            <div class="form-group">
                                <label for="date_expiration" class="form-label">Date d'expiration</label>
                                <input type="datetime-local" id="date_expiration" name="date_expiration" 
                                       class="form-input @error('date_expiration') error @enderror"
                                       value="{{ old('date_expiration', $annonce->date_expiration ? $annonce->date_expiration->format('Y-m-d\TH:i') : '') }}">
                                @error('date_expiration')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                                <div class="form-help">Laissez vide pour ne pas définir de date d'expiration.</div>
                            </div>

                            <div class="form-group">
                                <label for="priorite" class="form-label">Niveau d'urgence</label>
                                <select id="priorite" name="priorite" class="form-select-single @error('priorite') error @enderror">
                                    <option value="0" {{ old('priorite', $annonce->priorite) == 0 ? 'selected' : '' }}>Normale</option>
                                    <option value="1" {{ old('priorite', $annonce->priorite) == 1 ? 'selected' : '' }}>Importante</option>
                                    <option value="2" {{ old('priorite', $annonce->priorite) == 2 ? 'selected' : '' }}>Urgente</option>
                                </select>
                                @error('priorite')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="main-card mb-4">
                        <div class="main-card-body">
                            <div class="form-actions">
                                <button type="submit" class="btn-acasi primary">
                                    <i class="fas fa-save"></i>Enregistrer les modifications
                                </button>
                                <a href="{{ route('esbtp.annonces.show', $annonce) }}" class="btn-acasi secondary">
                                    <i class="fas fa-times"></i>Annuler
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection