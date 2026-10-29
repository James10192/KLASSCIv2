@extends('layouts.app')

@section('title', 'Messages envoyés')

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
                        <a href="{{ route('messages.sent') }}" class="list-group-item list-group-item-action active">
                            <i class="fas fa-paper-plane"></i> Messages envoyés
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Messages envoyés</span>
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
                    
                    @if($messages->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%"></th>
                                        <th width="20%">Destinataire</th>
                                        <th width="50%">Sujet</th>
                                        <th width="15%">Date</th>
                                        <th width="10%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($messages as $message)
                                        <tr>
                                            <td>
                                                @if($message->isGroupMessage())
                                                    <span class="badge bg-info rounded-pill">Groupe</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($message->isGroupMessage())
                                                    <span class="text-muted">
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
                                                    </span>
                                                @else
                                                    {{ $message->recipient->name ?? 'Destinataire inconnu' }}
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('messages.show', $message) }}">
                                                    {{ $message->subject }}
                                                </a>
                                            </td>
                                            <td>{{ $message->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <form action="{{ route('messages.destroy', $message) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce message?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-paper-plane fa-4x text-muted mb-3"></i>
                            <p class="lead">Vous n'avez envoyé aucun message</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 