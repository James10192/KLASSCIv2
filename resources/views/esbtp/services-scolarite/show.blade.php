@extends('layouts.app')

@section('title', $service->name)

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl" style="background-color: var(--primary); color: white; border-radius: var(--radius-medium);">
        <h1 style="color:white;margin:0;">{{ $service->name }}</h1>
        <p style="color:rgba(255,255,255,.8);margin:6px 0 0;">Service scolarite</p>
    </div>

    <div class="card-moderne" style="padding: var(--space-xl);">
        <p><strong>Email :</strong> {{ $service->email ?: '—' }}</p>
        <p><strong>Téléphone :</strong> {{ $service->telephone ?: '—' }}</p>
        <p><strong>Spécialité :</strong> {{ $service->specialite ?: '—' }}</p>
        <p><strong>Statut :</strong> {{ $service->is_active ? 'Actif' : 'Inactif' }}</p>
        @if($performanceScore)
            <p><strong>Score personnel :</strong> {{ $performanceScore->total_score ?? $performanceScore['total_score'] ?? '—' }}</p>
        @endif
        <a class="btn-acasi" href="{{ route('esbtp.services-scolarite.edit', $service) }}">Modifier</a>
        <a class="btn-acasi secondary" href="{{ route('esbtp.personnel.unified.index') }}">Retour</a>
    </div>
</div>
@endsection
