@extends('layouts.app')

@section('title', 'Résultats de ' . $etudiant->nom . ' ' . $etudiant->prenoms . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/student-results.css') }}">
@endsection

@php
    $coeffContext = session('coefficient_missing_context');

    // ── Données pour le modal auto-suffisant de coefficients ──
    $coeffFiliere       = null;
    $coeffNiveau        = null;
    $coeffAnneeId       = $annee_id ?? null;
    $coeffMatieresLiees = collect();
    $coeffMatieresEvals = collect();
    $coefficients       = collect();

    if (isset($classe) && $classe && $classe->filiere && $classe->niveau) {
        $coeffFiliere = $classe->filiere;
        $coeffNiveau  = $classe->niveau;

        $coeffMatieresLiees = \App\Models\ESBTPMatiere::where('is_active', true)
            ->whereHas('filieres', fn($q) => $q->where('esbtp_filieres.id', $coeffFiliere->id))
            ->whereHas('niveaux',  fn($q) => $q->where('esbtp_niveau_etudes.id', $coeffNiveau->id))
            ->orderBy('name')
            ->get();

        $idsLiees = $coeffMatieresLiees->pluck('id');

        $coeffMatieresEvals = \App\Models\ESBTPMatiere::where('is_active', true)
            ->whereHas('evaluations', fn($q) => $q->where('classe_id', $classe->id))
            ->whereNotIn('id', $idsLiees)
            ->orderBy('name')
            ->get();

        if ($coeffAnneeId) {
            $coefficients = \App\Models\ESBTPMatiereCoefficient::where('filiere_id', $coeffFiliere->id)
                ->where('niveau_etude_id', $coeffNiveau->id)
                ->where('annee_universitaire_id', $coeffAnneeId)
                ->get()
                ->keyBy('matiere_id');
        }

        if ($coefficients->isEmpty()) {
            $coefficients = \App\Models\ESBTPMatiereCoefficient::where('filiere_id', $coeffFiliere->id)
                ->where('niveau_etude_id', $coeffNiveau->id)
                ->orderByDesc('annee_universitaire_id')
                ->get()
                ->unique('matiere_id')
                ->keyBy('matiere_id');
        }
    }

    // Current period for tabs
    $currentPeriode = $periode ?? 'annuel';
    $currentPeriodeKey = $currentPeriode;
    if ($currentPeriodeKey === '1') $currentPeriodeKey = 'semestre1';
    elseif ($currentPeriodeKey === '2') $currentPeriodeKey = 'semestre2';
    $includeAllStatusesParam = isset($include_all_statuses) && $include_all_statuses ? ['include_all_statuses' => 1] : [];
@endphp

@section('content')
{{-- Loading overlay --}}
<div class="sr-loading-overlay" id="sr-loading">
    <div class="sr-loading-spinner">
        <div class="sr-loading-spinner-circle"></div>
        <div class="sr-loading-spinner-text">Chargement des résultats...</div>
    </div>
</div>

<div class="dashboard-acasi">
    <div class="main-content" id="etudiant-resultats-content"
         data-etudiant-id="{{ $etudiant->id }}"
         data-route-base="{{ route('esbtp.resultats.etudiant', $etudiant) }}">

        {{-- 1. Hero Header --}}
        <div class="sr-hero sr-animate">
            <div class="sr-hero-content">
                <div class="sr-hero-left">
                    <div class="sr-hero-avatar">
                        @if($etudiant->photo_url)
                            <img src="{{ $etudiant->photo_url }}" alt="{{ $etudiant->nom }}">
                        @else
                            <i class="fas fa-user-graduate"></i>
                        @endif
                    </div>
                    <div class="sr-hero-info">
                        <h1>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h1>
                        <p>{{ isset($classe) && $classe ? $classe->name : 'Toutes classes' }} — Détail des notes et moyennes</p>
                        <div class="sr-breadcrumb">
                            <a href="{{ route('esbtp.resultats.index', $includeAllStatusesParam) }}">Résultats</a>
                            <i class="fas fa-chevron-right"></i>
                            @if(isset($classe) && $classe)
                                <a href="{{ route('esbtp.resultats.classe', ['classe' => $classe->id]) }}?periode={{ $periode }}&annee_universitaire_id={{ $annee_id }}@if(!empty($includeAllStatusesParam))&include_all_statuses=1@endif">{{ $classe->name }}</a>
                                <i class="fas fa-chevron-right"></i>
                            @endif
                            <span>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</span>
                        </div>
                    </div>
                </div>
                <div class="sr-hero-actions">
                    @if(isset($classe) && $classe && auth()->user()->can('admin.access'))
                        <a href="{{ route('esbtp.resultats.classe.edit', $classe->id) }}?annee_universitaire_id={{ $annee_id }}&semestre={{ isset($periode) && str_starts_with($periode, 'semestre') ? str_replace('semestre', '', $periode) : '' }}"
                           class="sr-hero-btn">
                            <i class="fas fa-edit"></i>Éditer classe
                        </a>
                    @endif
                    <a href="{{ route('esbtp.resultats.index', $includeAllStatusesParam) }}" class="sr-hero-btn">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                    @if(isset($classe) && $classe)
                        @php $_resBulletinParams = ['bulletin' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $bulletinWorkflowPeriode, 'annee_universitaire_id' => $annee_id]; @endphp
                        <a href="{{ route('esbtp.resultats.etudiant.preview', ['etudiant' => $etudiant->id]) }}?classe_id={{ $classe->id }}&annee_universitaire_id={{ $annee_id }}&periode={{ $bulletinWorkflowPeriode }}"
                           class="sr-hero-btn"
                           data-check-url="{{ route('esbtp.bulletins.check-prerequisites', $_resBulletinParams) }}"
                           onclick="return srCheckBeforePDF(event, this);">
                            <i class="fas fa-window-restore"></i>Vue web{{ $currentPeriodeKey === 'annuel' ? ' ' . $bulletinWorkflowPeriodeLabel : '' }}
                        </a>
                        <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_resBulletinParams) }}"
                           class="sr-hero-btn--solid sr-hero-btn sr-pdf-link"
                           target="_blank"
                           data-check-url="{{ route('esbtp.bulletins.check-prerequisites', $_resBulletinParams) }}"
                           onclick="return srCheckBeforePDF(event, this);">
                            <i class="fas fa-eye"></i>Aperçu PDF{{ $currentPeriodeKey === 'annuel' ? ' ' . $bulletinWorkflowPeriodeLabel : '' }}
                        </a>
                        <a href="{{ route('esbtp.bulletins.pdf-params', $_resBulletinParams) }}"
                           class="sr-hero-btn sr-hero-btn--danger sr-pdf-link"
                           data-check-url="{{ route('esbtp.bulletins.check-prerequisites', $_resBulletinParams) }}"
                           onclick="return srCheckBeforePDF(event, this);">
                            <i class="fas fa-file-pdf"></i>PDF{{ $currentPeriodeKey === 'annuel' ? ' ' . $bulletinWorkflowPeriodeLabel : '' }}
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- 2. Filtres (Classe + Année, sans Période) --}}
        @include('components.student-results.filters-section')

        {{-- 2b. Period Tabs --}}
        <div class="sr-period-tabs sr-animate sr-animate-delay-1">
            <button type="button"
                    class="sr-period-tab {{ $currentPeriodeKey === 'annuel' ? 'active' : '' }}"
                    data-periode="annuel"
                    onclick="srSwitchPeriod('annuel')">
                <i class="fas fa-layer-group"></i>
                Annuel
                @if($moyenneAnnuelle !== null)
                    <span class="sr-tab-avg">{{ number_format($moyenneAnnuelle, 2) }}</span>
                @elseif(($detailUiState['primary_average'] ?? null) !== null)
                    <span class="sr-tab-avg" style="opacity: 0.75;">
                        {{ number_format($detailUiState['primary_average'], 2) }}
                    </span>
                @endif
            </button>
            <button type="button"
                    class="sr-period-tab {{ $currentPeriodeKey === 'semestre1' ? 'active' : '' }}"
                    data-periode="semestre1"
                    onclick="srSwitchPeriod('semestre1')">
                <i class="fas fa-calendar-alt"></i>
                Semestre 1
                @if($moyenneSemestre1 !== null)
                    <span class="sr-tab-avg">{{ number_format($moyenneSemestre1, 2) }}</span>
                @endif
            </button>
            <button type="button"
                    class="sr-period-tab {{ $currentPeriodeKey === 'semestre2' ? 'active' : '' }}"
                    data-periode="semestre2"
                    onclick="srSwitchPeriod('semestre2')">
                <i class="fas fa-calendar-check"></i>
                Semestre 2
                @if($moyenneSemestre2 !== null)
                    <span class="sr-tab-avg">{{ number_format($moyenneSemestre2, 2) }}</span>
                @endif
            </button>
        </div>

        {{-- 3+4. Layout deux colonnes --}}
        <div class="row mb-4">
            <div class="col-lg-4 mb-3 mb-lg-0">
                @include('components.student-results.student-info-card')
            </div>
            <div class="col-lg-8">
                @include('components.student-results.results-overview-card')
            </div>
        </div>

        {{-- 5. Tableau des matières --}}
        @include('components.student-results.subjects-table')

        {{-- 6. Détail des évaluations --}}
        @include('components.student-results.evaluations-detail')

        {{-- 7. Actions et navigation --}}
        @include('components.student-results.action-buttons')

        {{-- Modal coefficients --}}
        @include('esbtp.resultats.partials.student-coefficients-modal')

    </div>
</div>

{{-- Modal warning pré-requis bulletin (HORS du container AJAX pour éviter les problèmes de swap) --}}
<div class="modal fade" id="srBulletinWarningModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 60px rgba(0,0,0,0.15);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); border: none; padding: 1.25rem 1.5rem;">
                <h5 class="modal-title" style="color: white; font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span id="srWarningModalTitle">Attention</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem;">
                <div id="srWarningModalBody"></div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid #f3f4f6; padding: 1rem 1.5rem; gap: 0.5rem;">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="border-radius: 8px;">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" id="srWarningProceedBtn" class="btn btn-warning" style="border-radius: 8px; font-weight: 600; color: white;"
                        onclick="var url = this.dataset.pdfUrl; if(url) { try { var m = bootstrap.Modal.getInstance(document.getElementById('srBulletinWarningModal')); if(m) m.hide(); } catch(e) {} window.location.href = url; }">
                    <i class="fas fa-check me-1"></i><span id="srWarningProceedText">Continuer quand même</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // ═══ Init function — called on page load AND after AJAX swap ═══
    function initStudentResults() {
        // Tooltips
        var tooltipList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipList.forEach(function(el) { new bootstrap.Tooltip(el); });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                var target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        // Accordion toggle
        document.querySelectorAll('.sr-accordion-trigger').forEach(function(trigger) {
            trigger.addEventListener('click', function() {
                this.closest('.sr-accordion-item').classList.toggle('open');
            });
        });

        // Gauge animation
        document.querySelectorAll('.sr-gauge-fill').forEach(function(circle) {
            var pct = parseFloat(circle.dataset.percent) || 0;
            var r = parseFloat(circle.getAttribute('r'));
            var circumference = 2 * Math.PI * r;
            circle.style.strokeDasharray = circumference;
            circle.style.strokeDashoffset = circumference;
            setTimeout(function() {
                circle.style.strokeDashoffset = circumference - (pct / 100) * circumference;
            }, 100);
        });

        // Auto-filter on select change (AJAX)
        document.querySelectorAll('.sr-auto-filter').forEach(function(el) {
            el.addEventListener('change', function() {
                srSubmitFilter();
            });
        });

        // Filter form submit via AJAX
        var form = document.getElementById('sr-filter-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                srSubmitFilter();
            });
        }

        // Coefficient modal auto-open
        var coeffContext = {{ $coeffContext ? 'true' : 'false' }};
        var urlCoeff = new URLSearchParams(window.location.search).get('open_coeff_modal') === '1';
        if (coeffContext || urlCoeff) {
            var modalEl = document.getElementById('studentCoeffModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                new bootstrap.Modal(modalEl, { backdrop: 'static', keyboard: false }).show();
            }
        }
    }

    // ═══ AJAX content swap ═══
    function srFetchAndSwap(url) {
        var overlay = document.getElementById('sr-loading');
        if (overlay) overlay.classList.add('active');

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        })
        .then(function(html) {
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var fresh = doc.getElementById('etudiant-resultats-content');
            var target = document.getElementById('etudiant-resultats-content');

            if (fresh && target) {
                target.innerHTML = fresh.innerHTML;
                // Copy data attributes
                target.dataset.etudiantId = fresh.dataset.etudiantId || target.dataset.etudiantId;
                target.dataset.routeBase = fresh.dataset.routeBase || target.dataset.routeBase;
            }

            // Update URL without reload
            history.pushState(null, '', url);

            // Re-initialize everything
            initStudentResults();

            // Hide overlay
            if (overlay) overlay.classList.remove('active');
        })
        .catch(function(err) {
            console.error('AJAX error:', err);
            if (overlay) overlay.classList.remove('active');
            // Fallback: navigate normally
            window.location.href = url;
        });
    }

    // ═══ Submit filter form via AJAX ═══
    function srSubmitFilter() {
        var form = document.getElementById('sr-filter-form');
        if (!form) return;

        var formData = new FormData(form);
        var params = new URLSearchParams();

        for (var pair of formData.entries()) {
            if (pair[1]) params.set(pair[0], pair[1]);
        }

        var container = document.getElementById('etudiant-resultats-content');
        var baseUrl = container ? container.dataset.routeBase : form.action;
        var url = baseUrl + '?' + params.toString();

        srFetchAndSwap(url);
    }

    // ═══ Switch period tab ═══
    window.srSwitchPeriod = function(periode) {
        // Update hidden input
        var input = document.getElementById('sr-periode-input');
        if (input) input.value = periode;

        // Submit via AJAX
        srSubmitFilter();
    };

    // ═══ Check prerequisites before PDF generation ═══
    window.srCheckBeforePDF = function(event, link) {
        event.preventDefault();

        var checkUrl = link.dataset.checkUrl;
        var pdfUrl = link.href;

        if (!checkUrl) {
            window.location.href = pdfUrl;
            return false;
        }

        fetch(checkUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.warnings && data.warnings.length > 0) {
                // Show warning modal
                var title = document.getElementById('srWarningModalTitle');
                var body = document.getElementById('srWarningModalBody');
                var proceedBtn = document.getElementById('srWarningProceedBtn');

                if (title) title.textContent = data.warnings[0].title || 'Attention';

                if (body) {
                    body.innerHTML = '';
                    data.warnings.forEach(function(w) {
                        var row = document.createElement('div');
                        row.style.cssText = 'display:flex;gap:0.75rem;align-items:flex-start;padding:0.75rem;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;margin-bottom:0.5rem;';
                        var icon = document.createElement('i');
                        icon.className = 'fas fa-' + (w.type === 'warning' ? 'exclamation-triangle' : 'info-circle');
                        icon.style.cssText = 'color:' + (w.type === 'warning' ? '#d97706' : '#0453cb') + ';margin-top:0.15rem;flex-shrink:0;';
                        var p = document.createElement('p');
                        p.style.cssText = 'margin:0;font-size:0.875rem;color:#1e293b;line-height:1.5;';
                        p.textContent = w.message;
                        row.appendChild(icon);
                        row.appendChild(p);
                        body.appendChild(row);
                    });
                }

                if (proceedBtn) proceedBtn.dataset.pdfUrl = pdfUrl;

                var modal = new bootstrap.Modal(document.getElementById('srBulletinWarningModal'));
                modal.show();
            } else {
                // No warnings, proceed directly
                window.location.href = pdfUrl;
            }
        })
        .catch(function() {
            // On error, proceed anyway
            window.location.href = pdfUrl;
        });

        return false;
    };

    // ═══ Handle browser back/forward ═══
    window.addEventListener('popstate', function() {
        srFetchAndSwap(window.location.href);
    });

    // ═══ Initial setup ═══
    document.addEventListener('DOMContentLoaded', initStudentResults);
})();
</script>
@endpush
