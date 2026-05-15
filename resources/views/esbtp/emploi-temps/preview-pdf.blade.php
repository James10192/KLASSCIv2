@extends('layouts.app')

@section('title', 'Prévisualisation Emploi du temps - ' . ($emploiTemps->classe->name ?? 'Classe'))

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
@include('pdf.partials.theme')
<style>
    .kpi-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 18px;
        box-shadow: 0 8px 24px rgba(4, 83, 203, 0.08);
        padding: 1.5rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    .kpi-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(135deg, rgba(4, 83, 203, 0.12), rgba(94, 145, 222, 0.08));
        pointer-events: none;
    }
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(30, 64, 175, 0.12);
    }
    .kpi-value {
        font-size: 2.5rem;
        font-weight: 700;
        color: #0453cb;
        margin-bottom: 0.25rem;
    }
    .kpi-title {
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-size: 0.75rem;
        color: #6b7280;
        font-weight: 600;
    }
    .kpi-description {
        font-size: 0.9rem;
        color: #9ca3af;
    }

    .school-card {
        background: linear-gradient(135deg, #0453cb, #5e91de);
        color: white;
        border-radius: 20px;
        padding: 2rem;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px rgba(4, 83, 203, 0.28);
    }
    .school-card::after {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 45%);
        pointer-events: none;
    }
    .school-card h2 {
        font-size: 1.75rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        margin-bottom: 0.5rem;
    }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.2rem;
        margin-top: 1.5rem;
    }
    .info-badge {
        background: rgba(255, 255, 255, 0.12);
        border-radius: 12px;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.95rem;
    }
    .info-badge i {
        background: rgba(255,255,255,0.2);
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .info-badge span {
        display: block;
        font-weight: 600;
        color: white;
    }
    .info-badge small {
        display: block;
        font-size: 0.75rem;
        opacity: 0.75;
    }

    .timetable-wrapper {
        overflow-x: auto;
        border-radius: 18px;
        border: 1px solid #e5e7eb;
        background: white;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
    }
    .timetable-grid {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 960px;
    }
    .timetable-grid th,
    .timetable-grid td {
        border: 1px solid #e5e7eb;
        padding: 0.75rem;
        text-align: center;
        vertical-align: middle;
        position: relative;
    }
    .timetable-grid thead th {
        background: linear-gradient(135deg, #0453cb, #5e91de);
        color: white;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        font-weight: 600;
        border-bottom: none;
    }
    .timetable-time-header {
        width: 110px;
    }
    .timetable-time-cell {
        background: #f8fafc;
        font-weight: 600;
        font-size: 0.85rem;
        color: #1f2937;
        position: sticky;
        left: 0;
        z-index: 5;
    }
    .tt-session {
        border-radius: 14px;
        padding: 0.75rem;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.2);
    }
    .tt-session-subject {
        font-size: 0.95rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .tt-session-teacher,
    .tt-session-room,
    .tt-session-time {
        font-size: 0.8rem;
        opacity: 0.95;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        justify-content: center;
    }
    .tt-session-teacher i,
    .tt-session-room i,
    .tt-session-time i {
        font-size: 0.75rem;
    }
    .tt-session-notes {
        font-size: 0.72rem;
        opacity: 0.85;
    }
    .timetable-empty-cell {
        background: #f9fafb;
    }

    .session-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .session-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.85rem;
        background: #f3f4f6;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
    }
    .session-legend-color {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        -webkit-print-color-adjust: exact;
        color-adjust: exact;
    }

    .stat-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }
    .stat-list-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.85rem 1rem;
        border-radius: 14px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
    }
    .stat-list-item strong {
        font-size: 0.95rem;
        color: #1f2937;
    }
    .stat-list-item span {
        font-size: 0.85rem;
        color: #6b7280;
    }
    .stat-list-item .badge {
        font-size: 0.8rem;
        background: #0453cb;
        color: white;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
    }

    @media (max-width: 992px) {
        .kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .timetable-grid {
            min-width: 720px;
        }
    }
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <div class="dashboard-header">
            <div class="header-left">
                <h1>
                    <i class="fas fa-calendar-week me-2"></i>
                    Emploi du temps - {{ $emploiTemps->classe->name ?? 'Classe' }}
                </h1>
                <p class="header-subtitle">
                    @if($periodeAffichage)
                        {{ $periodeAffichage }}
                    @elseif($emploiTemps->annee && $emploiTemps->annee->name)
                        {{ $emploiTemps->annee->name }}
                    @else
                        Période non définie
                    @endif
                </p>
            </div>
            <div class="header-actions">
                <x-pdf-actions
                    :preview-url="route('esbtp.emploi-temps.preview-pdf', ['emploi_temp' => $emploiTemps->id])"
                    :download-url="route('esbtp.emploi-temps.export-pdf', ['emploi_temp' => $emploiTemps->id])"
                    label="Emploi du temps"
                    buttonClass="btn-acasi primary"
                    downloadClass="btn-acasi danger"
                    downloadLabel="Exporter en PDF" />
                <a href="{{ route('esbtp.emploi-temps.preview-pdf', ['emploi_temp' => $emploiTemps->id]) }}" target="_blank" rel="noopener" class="btn-acasi primary">
                    <i class="fas fa-print"></i>
                    Imprimer
                </a>
                <a href="{{ route('esbtp.emploi-temps.show', ['emploi_temp' => $emploiTemps->id]) }}" class="btn-acasi secondary">
                    <i class="fas fa-arrow-left"></i>
                    Retour au détail
                </a>
            </div>
        </div>

        <div class="kpi-grid">
            @foreach($summaryStats as $stat)
                <div class="kpi-card">
                    <div class="kpi-title">{{ $stat['label'] }}</div>
                    <div class="kpi-value">
                        @if(is_numeric($stat['value']))
                            {{ number_format($stat['value'], 0, ',', ' ') }}
                        @else
                            {{ $stat['value'] }}
                        @endif
                    </div>
                    <div class="kpi-description">
                        <i class="fas {{ $stat['icon'] }} me-1"></i>
                        {{ $stat['description'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="school-card mt-4">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-4">
                <div>
                    <h2>{{ $etablissement['nom'] }}</h2>
                    <p style="margin: 0; font-size: 0.95rem; opacity: 0.9;">
                        {{ $etablissement['type'] ?? 'Établissement' }}
                    </p>
                </div>
                @if($logoBase64)
                    <div class="text-center">
                        {{-- Pas de filter:brightness/invert pour preserver les couleurs originales du logo
                             (qui peut etre colore avec une marque cohérente). Background card semi-transparent
                             pour assurer la visibilite sur n'importe quel logo (clair OU sombre) sur fond bleu KLASSCI. --}}
                        <div style="background: rgba(255,255,255,.92); border-radius: 10px; padding: 6px 10px; display: inline-block;">
                            <img src="{{ $logoBase64 }}" alt="Logo établissement" style="max-height: 60px; max-width: 150px; object-fit: contain; display: block;">
                        </div>
                    </div>
                @endif
            </div>

            <div class="info-grid">
                <div class="info-badge">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <span>{{ $etablissement['ville'] ?? '' }} {{ $etablissement['pays'] ? ' - ' . $etablissement['pays'] : '' }}</span>
                        <small>{{ $etablissement['adresse'] ?: 'Adresse non renseignée' }}</small>
                    </div>
                </div>
                <div class="info-badge">
                    <i class="fas fa-phone"></i>
                    <div>
                        <span>{{ $etablissement['telephone'] ?: '---' }}</span>
                        <small>Standard administratif</small>
                    </div>
                </div>
                <div class="info-badge">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <span>{{ $etablissement['email'] ?: '---' }}</span>
                        <small>Contact officiel</small>
                    </div>
                </div>
                <div class="info-badge">
                    <i class="fas fa-layer-group"></i>
                    <div>
                        <span>{{ $emploiTemps->classe->filiere->name ?? 'Filière non renseignée' }}</span>
                        <small>{{ $emploiTemps->classe->niveau->name ?? 'Niveau non renseigné' }}</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mt-4">
            <div class="col-lg-6">
                <div class="main-card h-100">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-chart-pie"></i>
                            Répartition par type de séance
                        </div>
                        <div class="main-card-subtitle">Vue d’ensemble des formats pédagogiques</div>
                    </div>
                    <div class="main-card-body">
                        <ul class="stat-list">
                            @foreach($sessionTypeLabels as $type => $label)
                                @php
                                    $count = $sessionTypeStats[$type] ?? 0;
                                    $style = $sessionTypeColors[$type] ?? $sessionTypeColors['default'];
                                @endphp
                                <li class="stat-list-item">
                                    <div>
                                        <strong>{{ $label }}</strong>
                                        <span>{{ $count }} séance{{ $count > 1 ? 's' : '' }}</span>
                                    </div>
                    <div class="badge" style="background: {{ $style['bg'] }}; color: {{ $style['text'] }};">
                                        {{ number_format($count / max(1, $totalSeances) * 100, 0) }}%
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="main-card h-100">
                    <div class="main-card-header">
                        <div class="main-card-title">
                            <i class="fas fa-book"></i>
                            Matières les plus présentes
                        </div>
                        <div class="main-card-subtitle">Classement par volume de séances</div>
                    </div>
                    <div class="main-card-body">
                        <ul class="stat-list">
                            @forelse($matiereStats->take(6) as $matiere => $count)
                                <li class="stat-list-item">
                                    <div>
                                        <strong>{{ $matiere }}</strong>
                                        <span>{{ $count }} séance{{ $count > 1 ? 's' : '' }}</span>
                                    </div>
                                    <div class="badge">
                                        {{ number_format($count / max(1, $totalSeances) * 100, 0) }}%
                                    </div>
                                </li>
                            @empty
                                <li class="stat-list-item">
                                    <div>
                                        <strong>Aucune donnée</strong>
                                        <span>Pas de séance enregistrée</span>
                                    </div>
                                </li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-card mt-4">
            <div class="main-card-header">
                <div class="main-card-title">
                    <i class="fas fa-table"></i>
                    Grille hebdomadaire
                </div>
                <div class="main-card-subtitle">
                    {{ $totalSeances }} séances réparties sur {{ $daysCovered }} jour{{ $daysCovered > 1 ? 's' : '' }} • {{ $totalHoursFormatted }} cumulées
                </div>
            </div>
            <div class="main-card-body">
                @include('esbtp.emploi-temps.partials.timetable-grid', [
                    'seances' => $seances,
                    'timeSlots' => $timeSlots,
                    'days' => $days,
                    'dayLabels' => $joursNoms,
                    'sessionStyles' => $sessionTypeColors,
                    'sessionLabels' => $sessionTypeLabels,
                    'variant' => 'pdf',
                    'emploiTemps' => $emploiTemps ?? null,
                    'interactive' => false,
                ])

                <div class="session-legend">
                    @foreach($sessionTypeLabels as $type => $label)
                        @php
                            $style = $sessionTypeSwatches[$type] ?? ($sessionTypeColors[$type] ?? $sessionTypeColors['default']);
                        @endphp
                        <div class="session-legend-item">
                            <span class="session-legend-color" style="background: {{ $style['bg'] }};"></span>
                            {{ $label }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
