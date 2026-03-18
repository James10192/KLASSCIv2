@extends('layouts.app')

@section('title', 'Modifier — ' . $etudiant->nom_complet . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   STUDENT EDIT PREMIUM — KLASSCI Design System 2025
   Namespace: se-  (student-edit)
=================================================================== */

:root {
    --k-blue:      #0453cb;
    --k-blue-2:    #5e91de;
    --k-surface:   #f4f7fb;
    --k-card:      #ffffff;
    --k-border:    #e2e8f0;
    --k-text:      #1e293b;
    --k-muted:     #64748b;
    --k-success:   #10b981;
    --k-radius:    12px;
    --k-radius-lg: 20px;
    --k-shadow:    0 1px 3px rgba(0,0,0,.08), 0 4px 16px rgba(0,0,0,.06);
    --k-shadow-lg: 0 8px 32px rgba(4,83,203,.12);
}

/* ── Page shell ──────────────────────────────────────────────── */
.se-page { background: var(--k-surface); min-height: 100vh; }

/* ── HERO ────────────────────────────────────────────────────── */
.se-hero {
    position: relative;
    background: linear-gradient(135deg, var(--k-blue) 0%, var(--k-blue-2) 100%);
    padding: 0;
}
.se-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='12' cy='12' r='1.5' fill='rgba(255,255,255,0.12)'/%3E%3C/svg%3E");
    pointer-events: none;
}
.se-hero::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 48px;
    background: linear-gradient(to top, var(--k-surface) 0%, transparent 100%);
}
.se-hero-inner {
    position: relative; z-index: 2;
    max-width: 1280px; margin: 0 auto;
    padding: 32px 32px 40px;
    display: flex; align-items: center; gap: 20px;
    flex-wrap: wrap;
}

/* Avatar */
.se-hero-avatar {
    width: 72px; height: 72px;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    backdrop-filter: blur(8px);
    border: 3px solid rgba(255,255,255,.4);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: 1.4rem;
    overflow: hidden; flex-shrink: 0;
}
.se-hero-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

/* Info */
.se-hero-info { flex: 1; min-width: 200px; }
.se-hero-title {
    font-size: 1.5rem; font-weight: 800; color: #fff;
    letter-spacing: -.02em; margin: 0 0 6px;
    text-shadow: 0 1px 3px rgba(0,0,0,.15);
}
.se-hero-sub {
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.se-hero-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 50px;
    font-size: .78rem; font-weight: 600;
    background: rgba(255,255,255,.15);
    color: rgba(255,255,255,.95);
    border: 1px solid rgba(255,255,255,.25);
    backdrop-filter: blur(4px);
}

/* Actions */
.se-hero-actions {
    display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
}
.se-hero-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    transition: all .2s; border: none;
}
.se-hero-btn.ghost {
    background: rgba(255,255,255,.12);
    color: #fff;
    border: 1px solid rgba(255,255,255,.3);
}
.se-hero-btn.ghost:hover {
    background: rgba(255,255,255,.25);
}

/* ── Content ─────────────────────────────────────────────────── */
.se-content {
    max-width: 1280px; margin: -20px auto 0;
    padding: 0 24px 40px;
    position: relative; z-index: 3;
}

/* ── Error banner ────────────────────────────────────────────── */
.se-errors {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    border: 1.5px solid #fca5a5;
    border-left: 5px solid #ef4444;
    border-radius: var(--k-radius);
    padding: 16px 20px;
    margin-bottom: 20px;
}
.se-errors ul { margin: 0; padding-left: 18px; }
.se-errors li { color: #991b1b; font-size: .88rem; line-height: 1.6; }

/* ── Section cards ───────────────────────────────────────────── */
.se-section {
    background: var(--k-card);
    border: 1px solid var(--k-border);
    border-radius: var(--k-radius-lg);
    box-shadow: var(--k-shadow);
    margin-bottom: 20px;
    overflow: hidden;
    animation: seFadeUp .5s ease both;
}
.se-section:nth-child(2) { animation-delay: .08s; }
.se-section:nth-child(3) { animation-delay: .16s; }

.se-section-header {
    display: flex; align-items: center; gap: 12px;
    padding: 18px 24px;
    border-bottom: 1px solid var(--k-border);
    background: linear-gradient(135deg, rgba(4,83,203,.02) 0%, rgba(94,145,222,.02) 100%);
}
.se-section-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--k-blue), var(--k-blue-2));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .85rem;
    flex-shrink: 0;
}
.se-section-title { font-size: 1rem; font-weight: 700; color: var(--k-text); margin: 0; }
.se-section-desc { font-size: .8rem; color: var(--k-muted); margin: 2px 0 0; }
.se-section-body { padding: 24px; }

/* ── Form elements ───────────────────────────────────────────── */
.se-section .form-label {
    font-size: .82rem; font-weight: 600; color: var(--k-text);
    margin-bottom: 6px;
}
.se-section .form-control,
.se-section .form-select {
    border: 1.5px solid var(--k-border);
    border-radius: 8px;
    padding: 9px 14px;
    font-size: .88rem;
    transition: border-color .2s, box-shadow .2s;
}
.se-section .form-control:focus,
.se-section .form-select:focus {
    border-color: var(--k-blue);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.se-section .form-control[readonly] {
    background: #f8fafc;
    color: var(--k-muted);
}

/* ── Submit button ───────────────────────────────────────────── */
.se-submit-wrap {
    display: flex; justify-content: flex-end; gap: 12px;
    padding-top: 8px;
}
.se-submit-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px;
    border-radius: 10px;
    font-size: .9rem; font-weight: 700;
    background: linear-gradient(135deg, var(--k-blue), var(--k-blue-2));
    color: #fff; border: none; cursor: pointer;
    box-shadow: 0 4px 16px rgba(4,83,203,.3);
    transition: all .2s;
}
.se-submit-btn:hover {
    box-shadow: 0 6px 24px rgba(4,83,203,.4);
    transform: translateY(-1px);
}

/* ── Animation ───────────────────────────────────────────────── */
/* ── Modal parent — responsive ───────────────────────────────── */
@media (max-width: 995px) {
    .se-parent-modal { max-width: 95%; }
    .se-parent-modal .modal-body { padding: 1rem !important; font-size: .85rem; }
    .se-parent-modal .modal-header { padding: 1rem 1.25rem !important; }
    .se-parent-modal .modal-title { font-size: 1rem; }
    .se-parent-modal th, .se-parent-modal td { padding: .45rem .5rem; font-size: .82rem; }
}

/* ── Animation ───────────────────────────────────────────────── */
@keyframes seFadeUp {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* ── Responsive ──────────────────────────────────────────────── */
@media (max-width: 768px) {
    .se-hero-inner { padding: 20px 16px 28px; gap: 14px; }
    .se-hero-title { font-size: 1.15rem; }
    .se-hero-avatar { width: 52px; height: 52px; font-size: 1rem; }
    .se-hero-actions { width: 100%; justify-content: flex-end; }
    .se-content { padding: 0 12px 32px; }
    .se-section-body { padding: 16px; }
    .se-section-header { padding: 14px 16px; flex-wrap: wrap; gap: 8px; }
    .se-submit-wrap { justify-content: stretch; }
    .se-submit-btn { width: 100%; justify-content: center; }
}
</style>
@endsection

@section('content')
<div class="se-page">

{{-- HERO --}}
<div class="se-hero">
    <div class="se-hero-inner">
        <div class="se-hero-avatar">
            @if($etudiant->photo)
                <img src="{{ asset('storage/photos/etudiants/' . $etudiant->photo) }}" alt="{{ $etudiant->nom_complet }}"
                     onerror="this.parentElement.innerHTML='<i class=\'fas fa-user-graduate\'></i>'">
            @else
                {{ strtoupper(substr($etudiant->prenoms ?? 'E', 0, 1)) }}{{ strtoupper(substr($etudiant->nom, 0, 1)) }}
            @endif
        </div>
        <div class="se-hero-info">
            <h1 class="se-hero-title"><i class="fas fa-edit" style="font-size:1.1rem; opacity:.7;"></i> Modifier — {{ $etudiant->nom_complet }}</h1>
            <div class="se-hero-sub">
                @if($etudiant->matricule)
                <span class="se-hero-pill"><i class="fas fa-id-badge"></i> {{ $etudiant->matricule }}</span>
                @endif
                <span class="se-hero-pill">
                    <i class="fas fa-circle" style="font-size:.4rem; color:{{ $etudiant->statut === 'actif' ? '#34d399' : '#f87171' }}"></i>
                    {{ ucfirst($etudiant->statut) }}
                </span>
            </div>
        </div>
        <div class="se-hero-actions">
            <a href="{{ route('esbtp.etudiants.show', $etudiant) }}" class="se-hero-btn ghost">
                <i class="fas fa-eye"></i> Voir la fiche
            </a>
            <a href="{{ route('esbtp.etudiants.index') }}" class="se-hero-btn ghost">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>
</div>

{{-- CONTENT --}}
<div class="se-content">
    @if ($errors->any())
    <div class="se-errors">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @include('esbtp.etudiants.partials.edit-form', ['etudiant' => $etudiant, 'isEmbedded' => false])
</div>

</div>
@endsection

@push('scripts')
@include('esbtp.etudiants.partials.edit-form-scripts')
<script>
// Move modal to <body> to escape stacking context from .se-section animation
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('searchParentModal');
    if (modal) document.body.appendChild(modal);
});
</script>
@endpush
