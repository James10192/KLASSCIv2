@extends('layouts.app')

@section('title', 'Nouveau message')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    Messagerie
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('messages.create') }}" class="btn btn-primary mb-3">Nouveau message</a>
                    </div>
                    <div class="list-group">
                        <a href="{{ route('messages.inbox') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-inbox"></i> Boîte de réception
                        </a>
                        <a href="{{ route('messages.sent') }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-paper-plane"></i> Messages envoyés
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    Nouveau message
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs mb-4" id="messageTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual" type="button" role="tab" aria-controls="individual" aria-selected="true">
                                Message individuel
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="group-tab" data-bs-toggle="tab" data-bs-target="#group" type="button" role="tab" aria-controls="group" aria-selected="false">
                                Message de groupe
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="messageTabContent">
                        <!-- Formulaire pour message individuel -->
                        <div class="tab-pane fade show active" id="individual" role="tabpanel" aria-labelledby="individual-tab">
                            <form action="{{ route('messages.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="recipient_id" class="form-label">Destinataire</label>
                                    <select class="form-select @error('recipient_id') is-invalid @enderror" id="recipient_id" name="recipient_id" required>
                                        <option value="">Sélectionner un destinataire</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('recipient_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }} ({{ ucfirst($user->role) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('recipient_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Sujet</label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" required>
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content" class="form-label">Message</label>
                                    <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="5" required>{{ old('content') }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Envoyer</button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Formulaire pour message de groupe -->
                        <div class="tab-pane fade" id="group" role="tabpanel" aria-labelledby="group-tab">
                            <form action="{{ route('messages.send-to-group') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="recipient_type" class="form-label">Type de destinataire</label>
                                    <select class="form-select @error('recipient_type') is-invalid @enderror" id="recipient_type" name="recipient_type" required>
                                        <option value="">Sélectionner un type</option>
                                        <option value="all" {{ old('recipient_type') == 'all' ? 'selected' : '' }}>Tous les utilisateurs</option>
                                        <option value="students" {{ old('recipient_type') == 'students' ? 'selected' : '' }}>Tous les étudiants</option>
                                        <option value="teachers" {{ old('recipient_type') == 'teachers' ? 'selected' : '' }}>Tous les enseignants</option>
                                        <option value="admins" {{ old('recipient_type') == 'admins' ? 'selected' : '' }}>Tous les administrateurs</option>
                                        <option value="class" {{ old('recipient_type') == 'class' ? 'selected' : '' }}>Une classe spécifique</option>
                                        <option value="department" {{ old('recipient_type') == 'department' ? 'selected' : '' }}>Un département spécifique</option>
                                    </select>
                                    @error('recipient_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3" id="recipient_group_container" style="display: none;">
                                    <label for="recipient_group" class="form-label">Identifiant du groupe</label>
                                    <input type="text" class="form-control @error('recipient_group') is-invalid @enderror" id="recipient_group" name="recipient_group" value="{{ old('recipient_group') }}">
                                    @error('recipient_group')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="subject_group" class="form-label">Sujet</label>
                                    <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject_group" name="subject" value="{{ old('subject') }}" required>
                                    @error('subject')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="content_group" class="form-label">Message</label>
                                    <textarea class="form-control @error('content') is-invalid @enderror" id="content_group" name="content" rows="5" required>{{ old('content') }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">Envoyer au groupe</button>
                                </div>
                            </form>
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
        const recipientTypeSelect = document.getElementById('recipient_type');
        const recipientGroupContainer = document.getElementById('recipient_group_container');
        
        // Afficher/masquer le champ de groupe en fonction du type de destinataire
        recipientTypeSelect.addEventListener('change', function() {
            if (this.value === 'class' || this.value === 'department') {
                recipientGroupContainer.style.display = 'block';
            } else {
                recipientGroupContainer.style.display = 'none';
            }
        });
        
        // Initialiser l'état au chargement de la page
        if (recipientTypeSelect.value === 'class' || recipientTypeSelect.value === 'department') {
            recipientGroupContainer.style.display = 'block';
        }
    });
</script>
@endsection 