@extends('layouts.app')

@section('title', 'Tableau de bord parent')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title" style="color: #01632f;">Bienvenue dans le tableau de bord parent ESBTP</h5>
                    <p class="card-text">Système de gestion universitaire pour l'École Supérieure du Bâtiment et des Travaux Publics</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title">Mes enfants</h5>
                    <p class="card-text display-4">{{ $childrenCount ?? 0 }}</p>
                    <a href="{{ route('children.index') }}" class="btn" style="background-color: #f29400; color: white;">Voir détails</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #f29400;">
                <div class="card-body">
                    <h5 class="card-title">Notifications</h5>
                    <p class="card-text display-4">{{ $unreadNotifications ?? 0 }}</p>
                    <a href="{{ route('notifications.index') }}" class="btn" style="background-color: #01632f; color: white;">Voir toutes</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card text-white" style="background-color: #01632f;">
                <div class="card-body">
                    <h5 class="card-title">Paiements</h5>
                    <p class="card-text display-4">{{ $pendingPayments ?? 0 }}</p>
                    <a href="{{ route('payments.index') }}" class="btn" style="background-color: #f29400; color: white;">Voir détails</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-header" style="background-color: #01632f; color: white;">
                    Mes enfants
                </div>
                <div class="card-body">
                    @if(isset($children) && $children->count() > 0)
                        <div class="row">
                            @foreach($children as $child)
                                <div class="col-md-6 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="{{ $child->profile_photo ?? asset('images/default-avatar.png') }}" 
                                                         alt="{{ $child->name }}" class="rounded-circle" 
                                                         style="width: 60px; height: 60px; object-fit: cover;">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h5 class="mb-1">{{ $child->name }}</h5>
                                                    <p class="mb-0">Classe: {{ $child->class->name ?? 'N/A' }} {{ $child->section->name ?? '' }}</p>
                                                    <p class="mb-0">Moyenne: 
                                                        <span class="badge {{ ($child->average_grade ?? 0) >= 10 ? 'bg-success' : 'bg-danger' }}">
                                                            {{ $child->average_grade ?? 'N/A' }}
                                                        </span>
                                                    </p>
                                                </div>
                                                <div class="ms-auto">
                                                    <a href="{{ route('children.show', $child->id) }}" 
                                                       class="btn" style="background-color: #01632f; color: white;">
                                                        Détails
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">Aucun enfant enregistré</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card" style="border-color: #f29400;">
                <div class="card-header" style="background-color: #f29400; color: white;">
                    Notifications récentes
                </div>
                <div class="card-body">
                    @if(isset($recentNotifications) && $recentNotifications->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($recentNotifications as $notification)
                                <li class="list-group-item">
                                    <strong>{{ $notification->title }}</strong>
                                    <span class="float-end text-muted">{{ $notification->created_at->format('d/m/Y') }}</span>
                                    <p class="mb-0">{{ Str::limit($notification->message, 100) }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">Aucune notification récente</p>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card" style="border-color: #01632f;">
                <div class="card-header" style="background-color: #01632f; color: white;">
                    Paiements à venir
                </div>
                <div class="card-body">
                    @if(isset($upcomingPayments) && $upcomingPayments->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($upcomingPayments as $payment)
                                <li class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $payment->title }}</strong> - 
                                            {{ $payment->student->name }}
                                        </div>
                                        <div>
                                            <span class="badge {{ \Carbon\Carbon::parse($payment->due_date)->isPast() ? 'bg-danger' : 'bg-warning' }}">
                                                {{ number_format($payment->amount, 2) }} € - 
                                                Date limite: {{ \Carbon\Carbon::parse($payment->due_date)->format('d/m/Y') }}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted">Aucun paiement à venir</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 