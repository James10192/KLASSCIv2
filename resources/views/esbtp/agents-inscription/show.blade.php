@extends('layouts.app')

@section('title', $agent->name)

@section('content')
<div class="main-content">
    <div class="dashboard-header mb-xl" style="background-color: var(--primary); color: white; border-radius: var(--radius-medium);">
        <h1 style="color:white;margin:0;">{{ $agent->name }}</h1>
        <p style="color:rgba(255,255,255,.8);margin:6px 0 0;">Agent d inscription</p>
    </div>

    <div class="card-moderne" style="padding: var(--space-xl);">
        <p><strong>Email :</strong> {{ $agent->email ?: '—' }}</p>
        <p><strong>Téléphone :</strong> {{ $agent->telephone ?: '—' }}</p>
        <p><strong>Spécialité :</strong> {{ $agent->specialite ?: '—' }}</p>
        <p><strong>Statut :</strong> {{ $agent->is_active ? 'Actif' : 'Inactif' }}</p>
        @if($performanceScore)
            <p><strong>Score personnel :</strong> {{ $performanceScore->total_score ?? $performanceScore['total_score'] ?? '—' }}</p>
        @endif
        <a class="btn-acasi" href="{{ route('esbtp.agents-inscription.edit', $agent) }}">Modifier</a>
        <a class="btn-acasi secondary" href="{{ route('esbtp.personnel.unified.index') }}">Retour</a>
    </div>
</div>
@endsection
