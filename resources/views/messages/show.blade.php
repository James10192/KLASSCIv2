@extends('layouts.app')

@section('title', $message->subject)

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
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>{{ $message->subject }}</span>
                    <div>
                        <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        @if($message->sender_id !== Auth::id())
                            <a href="#reply-form" class="btn btn-sm btn-primary ms-2">
                                <i class="fas fa-reply"></i> Répondre
                            </a>
                        @endif
                    </div>
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
                    
                    <div class="message-container mb-4">
                        <div class="message-header d-flex justify-content-between mb-3">
                            <div>
                                <strong>De:</strong> {{ $message->sender->name }}
                                @if($message->isGroupMessage())
                                    <div>
                                        <strong>À:</strong>
                                        @if($message->recipient_type == 'all')
                                            Tous les utilisateurs
                                        @elseif($message->recipient_type == 'students')
                                            Tous les étudiants
                                        @elseif($message->recipient_type == 'teachers')
                                            Tous les enseignants
                                        @elseif($message->recipient_type == 'admins')
                                            Tous les administrateurs
                                        @elseif($message->recipient_type == 'class')
                                            Classe #{{ $message->recipient_group }}
                                        @elseif($message->recipient_type == 'department')
                                            Département #{{ $message->recipient_group }}
                                        @endif
                                    </div>
                                @else
                                    <div>
                                        <strong>À:</strong> {{ $message->recipient->name ?? 'Destinataire inconnu' }}
                                    </div>
                                @endif
                            </div>
                            <div class="text-muted">
                                {{ $message->created_at->format('d/m/Y H:i') }}
                            </div>
                        </div>
                        <div class="message-content p-3 bg-light rounded">
                            {!! nl2br(e($message->content)) !!}
                        </div>
                    </div>
                    
                    @if($replies->count() > 0)
                        <h5 class="mt-4 mb-3">Réponses</h5>
                        @foreach($replies as $reply)
                            <div class="message-container mb-3">
                                <div class="message-header d-flex justify-content-between mb-2">
                                    <div>
                                        <strong>De:</strong> {{ $reply->sender->name }}
                                        <div>
                                            <strong>À:</strong> {{ $reply->recipient->name ?? 'Destinataire inconnu' }}
                                        </div>
                                    </div>
                                    <div class="text-muted">
                                        {{ $reply->created_at->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                                <div class="message-content p-3 bg-light rounded">
                                    {!! nl2br(e($reply->content)) !!}
                                </div>
                            </div>
                        @endforeach
                    @endif
                    
                    @if($message->sender_id !== Auth::id())
                        <div class="mt-4" id="reply-form">
                            <h5 class="mb-3">Répondre</h5>
                            <form action="{{ route('messages.reply', $message) }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label for="content" class="form-label">Votre réponse</label>
                                    <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="5" required>{{ old('content') }}</textarea>
                                    @error('content')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Envoyer la réponse
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 