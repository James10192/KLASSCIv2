@extends('layouts.app')

@section('title', 'Tableau de bord Communication - KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@endpush

@section('content')
<div class="main-content">
    <x-role-hero
        icon="fa-bullhorn"
        title="Communication"
        subtitle="Annonces, messages internes et envois MailPulse."
        :kpis="[
            ['icon' => 'fa-bullhorn', 'value' => $annoncesPubliees, 'label' => 'Annonces publiées'],
            ['icon' => 'fa-envelope', 'value' => $mailpulseOn ? 1 : 0, 'label' => 'MailPulse', 'tone' => $mailpulseOn ? null : 'alert'],
        ]">
        <x-slot:actions>
            @can('annonces.create')
            <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.annonces.create') }}">
                <i class="fas fa-plus"></i>Nouvelle annonce
            </a>
            @endcan
            @can('mailpulse.view')
            <a class="rdx-btn rdx-btn--white" href="{{ route('esbtp.communication.mailpulse') }}">
                <i class="fas fa-paper-plane"></i>MailPulse
            </a>
            @endcan
        </x-slot:actions>
    </x-role-hero>

    <x-role-dashboard>
        <x-slot:focal>
            <x-role-panel icon="fa-bullhorn" title="Dernières annonces" :count="$annonces->count()">
                @forelse($annonces as $annonce)
                    <div class="rdx-row">
                        <div class="rdx-row-icon"><i class="fas fa-bullhorn"></i></div>
                        <div class="rdx-row-main">
                            <div class="rdx-row-title">{{ $annonce->titre }}</div>
                            <div class="rdx-row-meta">{{ optional($annonce->date_publication)->format('d/m/Y') ?? '—' }}</div>
                        </div>
                    </div>
                @empty
                    <x-role-empty icon="fa-bullhorn" title="Aucune annonce" hint="Créez la première depuis le bouton ci-dessus." />
                @endforelse
            </x-role-panel>
        </x-slot:focal>
        <x-slot:rail>
            <x-role-panel icon="fa-link" title="Raccourcis">
                @can('annonces.view')
                <a class="rdx-act rdx-act--primary" href="{{ route('esbtp.annonces.index') }}"><i class="fas fa-list"></i>Annonces</a>
                @endcan
                @can('messages.send')
                <a class="rdx-act rdx-act--primary" href="{{ route('chat.index') }}"><i class="fas fa-comments"></i>Messages</a>
                @endcan
                @can('students.view')
                <a class="rdx-act rdx-act--primary" href="{{ route('esbtp.etudiants.index') }}"><i class="fas fa-user-graduate"></i>Étudiants</a>
                @endcan
            </x-role-panel>
        </x-slot:rail>
    </x-role-dashboard>
</div>
@endsection
