@extends('layouts.app')

@section('title', 'Résultats de la classe ' . $classe->name . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/student-results.css') }}">
@endsection

@php
    $totalMoyennes = 0; $countMoyennes = 0; $min = 20; $max = 0; $countSucces = 0; $countEchec = 0;
    foreach ($resultats as $r) {
        if ($r['notes_count'] > 0) {
            $avg = $r['moyenne_avec_assiduite'] ?? $r['moyenne'];
            $totalMoyennes += $avg; $countMoyennes++;
            $min = min($min, $avg); $max = max($max, $avg);
            if ($avg >= 10) { $countSucces++; } else { $countEchec++; }
        }
    }
    $moyenneClasse = $countMoyennes > 0 ? $totalMoyennes / $countMoyennes : 0;
    $tauxReussite = $countMoyennes > 0 ? ($countSucces / $countMoyennes) * 100 : 0;
    $periodeLabel = 'Annuel';
    if (isset($periode) && $periode) { $periodeLabel = $periodes[$periode] ?? $periode; }
@endphp

@section('content')
{{-- Loading overlay --}}
<div class="sr-loading-overlay" id="rc-loading">
    <div class="sr-loading-spinner">
        <div class="sr-loading-spinner-circle"></div>
        <div class="sr-loading-spinner-text">Chargement...</div>
    </div>
</div>

<div class="dashboard-acasi">
    <div class="main-content" id="rc-content"
         data-base-url="{{ route('esbtp.resultats.classe', $classe) }}">

        {{-- Hero --}}
        <div class="sr-hero sr-animate">
            <div class="sr-hero-content">
                <div class="sr-hero-left">
                    <div class="sr-hero-avatar"><i class="fas fa-chart-bar"></i></div>
                    <div class="sr-hero-info">
                        <h1>{{ $classe->name }}</h1>
                        <p>
                            @if($classe->filiere) {{ $classe->filiere->name }} @endif
                            @if($classe->niveau) · {{ $classe->niveau->name ?? '' }} @endif
                            · {{ $periodeLabel }}
                        </p>
                        <div class="sr-breadcrumb">
                            <a href="{{ route('esbtp.resultats.index') }}">Résultats</a>
                            <i class="fas fa-chevron-right"></i>
                            <a href="{{ route('esbtp.resultats.classes') }}">Classes</a>
                            <i class="fas fa-chevron-right"></i>
                            <span>{{ $classe->name }}</span>
                        </div>
                    </div>
                </div>
                <div class="sr-hero-actions">
                    <a href="{{ route('esbtp.resultats.classes') }}" class="sr-hero-btn">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                    @can('resultats.edit')
                        <a href="{{ route('esbtp.resultats.classe.edit', $classe->id) }}?annee_universitaire_id={{ $annee_id }}&semestre={{ $semestre }}"
                           class="sr-hero-btn--solid sr-hero-btn">
                            <i class="fas fa-edit"></i>Éditer groupé
                        </a>
                    @endcan
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- KPIs --}}
        <div class="sr-stats sr-animate sr-animate-delay-1" style="margin-bottom: 1.5rem;">
            <div class="sr-stat sr-stat--primary">
                <div class="sr-stat-icon"><i class="fas fa-users"></i></div>
                <div class="sr-stat-value">{{ count($resultats) }}</div>
                <div class="sr-stat-label">Étudiants</div>
            </div>
            <div class="sr-stat sr-stat--info">
                <div class="sr-stat-icon"><i class="fas fa-calculator"></i></div>
                <div class="sr-stat-value">{{ $countMoyennes > 0 ? number_format($moyenneClasse, 2) : '—' }}</div>
                <div class="sr-stat-label">Moy. classe</div>
            </div>
            <div class="sr-stat sr-stat--success">
                <div class="sr-stat-icon"><i class="fas fa-percentage"></i></div>
                <div class="sr-stat-value">{{ number_format($tauxReussite, 0) }}%</div>
                <div class="sr-stat-label">Réussite</div>
            </div>
            <div class="sr-stat sr-stat--warning">
                <div class="sr-stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="sr-stat-value">{{ $countMoyennes > 0 ? number_format($max, 2) : '—' }}</div>
                <div class="sr-stat-label">Meilleure</div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="sr-filter-bar sr-animate sr-animate-delay-2">
            <form id="rc-filter-form" class="filter-form">
                <div class="sr-filter-row">
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Année universitaire</label>
                        <select class="sr-filter-select rc-auto-filter" name="annee_universitaire_id">
                            @foreach($anneesUniversitaires ?? [] as $annee)
                                <option value="{{ $annee->id }}" {{ isset($annee_universitaire_id) && $annee_universitaire_id == $annee->id ? 'selected' : '' }}>
                                    {{ $annee->name ?? ($annee->annee_debut . '-' . $annee->annee_fin) }}{{ $annee->is_current ? ' *' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-filter-group">
                        <label class="sr-filter-label">Période</label>
                        <select class="sr-filter-select rc-auto-filter" name="periode">
                            @foreach($periodes ?? [] as $key => $nom)
                                <option value="{{ $key }}" {{ (isset($periode) ? $periode : '') == $key ? 'selected' : '' }}>
                                    {{ $nom }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sr-filter-group" style="flex: 0 0 auto; min-width: auto;">
                        <label class="sr-filter-label">&nbsp;</label>
                        <button type="submit" class="sr-filter-btn">
                            <i class="fas fa-search"></i>Filtrer
                        </button>
                    </div>
                </div>
                <label class="sr-filter-toggle">
                    <input type="checkbox" name="include_all_statuses" value="1" class="rc-auto-filter"
                           {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
                    <span class="sr-toggle-track"></span>
                    <span>Inclure inscriptions inactives</span>
                </label>
            </form>
        </div>

        {{-- Table résultats --}}
        <div class="sr-table-card sr-animate sr-animate-delay-3">
            <div class="sr-table-header">
                <div class="sr-table-header-left">
                    <i class="fas fa-list-ol"></i>
                    <h3>Classement — {{ $periodeLabel }}</h3>
                </div>
                <span class="sr-table-count">{{ count($resultats) }} étudiants</span>
            </div>

            @if(count($resultats) > 0)
                <div class="sr-table-responsive">
                    <table class="sr-table">
                        <thead>
                            <tr>
                                <th style="width: 6%" class="text-center">Rang</th>
                                <th style="width: 30%">Étudiant</th>
                                <th style="width: 15%" class="text-center">Moyenne</th>
                                @if(isset($afficherNoteAssiduite) && $afficherNoteAssiduite)
                                    <th style="width: 10%" class="text-center">Assiduité</th>
                                @endif
                                <th style="width: 10%" class="text-center">Notes</th>
                                <th style="width: 14%" class="text-center">Statut</th>
                                <th style="width: 15%" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultats as $index => $resultat)
                                @php
                                    $etudiant = $resultat['etudiant'];
                                    $moyenne = $resultat['moyenne_avec_assiduite'] ?? $resultat['moyenne'];
                                    $notesCount = $resultat['notes_count'];
                                    $noteAssid = $resultat['note_assiduite'] ?? 0;
                                @endphp
                                <tr>
                                    <td class="text-center">
                                        @if($index === 0 && $notesCount > 0)
                                            <span style="color: #d97706; font-size: 1.1rem;"><i class="fas fa-trophy"></i></span>
                                        @elseif($index === 1 && $notesCount > 0)
                                            <span style="color: #9ca3af; font-size: 1rem;"><i class="fas fa-medal"></i></span>
                                        @elseif($index === 2 && $notesCount > 0)
                                            <span style="color: #b45309; font-size: 0.95rem;"><i class="fas fa-medal"></i></span>
                                        @else
                                            <span style="color: var(--sr-muted-light); font-weight: 600; font-size: 0.85rem;">{{ $index + 1 }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="sr-subject-cell">
                                            <div class="sr-subject-icon" style="border-radius: 50%; font-size: 0.7rem; width: 34px; height: 34px;">
                                                {{ strtoupper(substr($etudiant->nom ?? 'N', 0, 1)) }}{{ strtoupper(substr($etudiant->prenoms ?? 'A', 0, 1)) }}
                                            </div>
                                            <div class="sr-subject-info">
                                                <div class="sr-subject-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</div>
                                                <span class="sr-subject-code">{{ $etudiant->matricule }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if($notesCount > 0)
                                            <div class="sr-avg-cell">
                                                <span class="sr-avg-badge sr-avg-badge--{{ $moyenne >= 10 ? 'success' : 'danger' }}">
                                                    {{ number_format($moyenne, 2) }}/20
                                                </span>
                                                <div class="sr-avg-progress">
                                                    <div class="sr-avg-progress-fill sr-avg-progress-fill--{{ $moyenne >= 10 ? 'success' : 'danger' }}"
                                                         style="width: {{ min($moyenne * 5, 100) }}%"></div>
                                                </div>
                                            </div>
                                        @else
                                            <span style="color: var(--sr-muted-light);">—</span>
                                        @endif
                                    </td>
                                    @if(isset($afficherNoteAssiduite) && $afficherNoteAssiduite)
                                        <td class="text-center">
                                            @if($noteAssid != 0)
                                                <span class="sr-assiduity-badge {{ $noteAssid > 0 ? 'sr-assiduity-badge--positive' : 'sr-assiduity-badge--negative' }}" style="margin-top: 0;">
                                                    {{ $noteAssid > 0 ? '+' : '' }}{{ number_format($noteAssid, 2) }}
                                                </span>
                                            @else
                                                <span style="color: var(--sr-muted-light); font-size: 0.8rem;">0.00</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td class="text-center">
                                        <span class="sr-eval-count">{{ $notesCount }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if($notesCount > 0)
                                            @if($moyenne >= 10)
                                                <span class="sr-appreciation sr-appreciation--tres-bien">Admis</span>
                                            @elseif($moyenne >= 8)
                                                <span class="sr-appreciation sr-appreciation--passable">Rattrapage</span>
                                            @else
                                                <span class="sr-appreciation sr-appreciation--insuffisant">Ajourné</span>
                                            @endif
                                        @else
                                            <span class="sr-appreciation" style="background: var(--sr-border); color: var(--sr-muted);">Aucune note</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('esbtp.resultats.etudiant', [
                                            'etudiant' => $etudiant->id,
                                            'classe_id' => $classe->id,
                                            'periode' => $periode ?? '',
                                            'annee_universitaire_id' => $annee_universitaire_id ?? ''
                                        ]) }}" class="sr-hero-btn" style="padding: 0.35rem 0.75rem; font-size: 0.75rem; background: var(--sr-primary-gradient); border-color: var(--sr-primary);">
                                            <i class="fas fa-eye"></i>Détails
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="sr-empty">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucun résultat</h3>
                    <p>Aucun étudiant ne correspond aux critères sélectionnés.</p>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    function rcInit() {
        // Auto-filter on change
        document.querySelectorAll('.rc-auto-filter').forEach(function(el) {
            el.addEventListener('change', function() { rcSubmit(); });
        });

        // Form submit via AJAX
        var form = document.getElementById('rc-filter-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                rcSubmit();
            });
        }
    }

    function rcSubmit() {
        var form = document.getElementById('rc-filter-form');
        if (!form) return;

        var params = new URLSearchParams();
        var formData = new FormData(form);
        for (var pair of formData.entries()) {
            if (pair[1]) params.set(pair[0], pair[1]);
        }

        var container = document.getElementById('rc-content');
        var baseUrl = container ? container.dataset.baseUrl : '';
        var url = baseUrl + '?' + params.toString();

        rcFetchAndSwap(url);
    }

    function rcFetchAndSwap(url) {
        var overlay = document.getElementById('rc-loading');
        if (overlay) overlay.classList.add('active');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var fresh = doc.getElementById('rc-content');
            var target = document.getElementById('rc-content');

            if (fresh && target) {
                target.innerHTML = fresh.innerHTML;
                target.dataset.baseUrl = fresh.dataset.baseUrl || target.dataset.baseUrl;
            }

            history.pushState(null, '', url);
            rcInit();
            if (overlay) overlay.classList.remove('active');
        })
        .catch(function(err) {
            console.error('AJAX error:', err);
            if (overlay) overlay.classList.remove('active');
            window.location.href = url;
        });
    }

    window.addEventListener('popstate', function() { rcFetchAndSwap(window.location.href); });
    document.addEventListener('DOMContentLoaded', rcInit);
})();
</script>
@endpush
