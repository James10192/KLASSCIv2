@extends('layouts.app')

@section('title', 'Détails de l\'inscription - ' . $inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms . ' - KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/modal-force-fix.css') }}">
<style>
/* === CORRECTION SPÉCIFIQUE MODALS INSCRIPTIONS SHOW === */

/* Forcer tous les modals de cette page au premier plan */
#paymentModal.modal,
#validationModal.modal,
#subscriptionModal.modal,
#transferModal.modal,
#reliquatPaymentModal.modal,
#affectationClasseModal.modal {
    z-index: 9999 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#paymentModal .modal-dialog,
#validationModal .modal-dialog,
#subscriptionModal .modal-dialog,
#transferModal .modal-dialog,
#reliquatPaymentModal .modal-dialog,
#affectationClasseModal .modal-dialog {
    z-index: 10000 !important;
    /* position: relative retiré pour permettre le centrage avec modal-dialog-centered */
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

#paymentModal .modal-content,
#validationModal .modal-content,
#subscriptionModal .modal-content,
#transferModal .modal-content,
#reliquatPaymentModal .modal-content,
#affectationClasseModal .modal-content {
    z-index: 10001 !important;
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
}

/* Désactiver animations sur modals show mais permettre le centrage Bootstrap */
#paymentModal.modal.fade .modal-dialog,
#validationModal.modal.fade .modal-dialog,
#subscriptionModal.modal.fade .modal-dialog,
#transferModal.modal.fade .modal-dialog,
#reliquatPaymentModal.modal.fade .modal-dialog,
#affectationClasseModal.modal.fade .modal-dialog {
    transition: none !important;
    /* transform: none retiré pour permettre modal-dialog-centered de fonctionner */
}

/* États d'affichage forcés */
#paymentModal.modal.show,
#validationModal.modal.show,
#subscriptionModal.modal.show,
#transferModal.modal.show,
#reliquatPaymentModal.modal.show,
#affectationClasseModal.modal.show {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
}

/* Anti-curseur erratique quand modals ouverts */
body.modal-open * {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
}

/* Empêcher mouvements de curseur */
body.modal-open .btn,
body.modal-open .card,
body.modal-open .form-control {
    animation: none !important;
    transition: none !important;
}

body.modal-open .btn:hover,
body.modal-open .card:hover {
    transform: none !important;
}

/* Désactiver TOUT blur/filter sur les modals ET leurs backdrops */
#paymentModal, #paymentModal *,
#validationModal, #validationModal *,
#subscriptionModal, #subscriptionModal *,
#transferModal, #transferModal *,
#reliquatPaymentModal, #reliquatPaymentModal *,
#editSubscriptionModal, #editSubscriptionModal *,
#affectationClasseModal, #affectationClasseModal *,
#paymentModal:hover, #paymentModal *:hover,
#validationModal:hover, #validationModal *:hover,
#subscriptionModal:hover, #subscriptionModal *:hover,
#transferModal:hover, #transferModal *:hover,
#reliquatPaymentModal:hover, #reliquatPaymentModal *:hover,
#editSubscriptionModal:hover, #editSubscriptionModal *:hover,
#affectationClasseModal:hover, #affectationClasseModal *:hover {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    filter: none !important;
}

/* Désactiver le blur sur TOUS les modal-backdrop quand ces modals sont ouverts */
body:has(#paymentModal.show) .modal-backdrop,
body:has(#validationModal.show) .modal-backdrop,
body:has(#subscriptionModal.show) .modal-backdrop,
body:has(#transferModal.show) .modal-backdrop,
body:has(#reliquatPaymentModal.show) .modal-backdrop,
body:has(#editSubscriptionModal.show) .modal-backdrop,
body:has(#affectationClasseModal.show) .modal-backdrop {
    backdrop-filter: none !important;
    -webkit-backdrop-filter: none !important;
    filter: none !important;
}

/* === AMÉLIORATION STYLE INPUTS/SELECTS MODALS === */

/* Centrage vertical parfait des icônes - approche correcte */
#paymentModal .form-control,
#paymentModal .form-select,
#validationModal .form-control,
#validationModal .form-select,
#subscriptionModal .form-control,
#subscriptionModal .form-select,
#transferModal .form-control,
#transferModal .form-select,
#reliquatPaymentModal .form-control,
#reliquatPaymentModal .form-select {
    height: 44px !important;
    display: flex !important;
    align-items: center !important;
}

/* Wrapper de l'icône - centrage absolu correct */
#paymentModal div[style*="position: relative"],
#validationModal div[style*="position: relative"],
#subscriptionModal div[style*="position: relative"],
#transferModal div[style*="position: relative"],
#reliquatPaymentModal div[style*="position: relative"] {
    position: relative !important;
}

/* Icône positionnée absolument et centrée verticalement */
#paymentModal i[style*="position: absolute"],
#validationModal i[style*="position: absolute"],
#subscriptionModal i[style*="position: absolute"],
#transferModal i[style*="position: absolute"],
#reliquatPaymentModal i[style*="position: absolute"] {
    position: absolute !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    left: 15px !important;
    z-index: 10 !important;
}

/* Style dropdown moderne Filament-like avec coins arrondis */
#paymentModal .form-select,
#validationModal .form-select,
#subscriptionModal .form-select,
#transferModal .form-select,
#reliquatPaymentModal .form-select {
    border-radius: 8px !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}

/* Amélioration visuelle du dropdown au focus - style Filament */
#paymentModal .form-select:focus,
#validationModal .form-select:focus,
#subscriptionModal .form-select:focus,
#transferModal .form-select:focus,
#reliquatPaymentModal .form-select:focus {
    box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15) !important;
    border-color: #0d6efd !important;
    outline: none !important;
}

/* Style Filament pour le menu déroulant du select - NOTE: Navigateurs limitent le style des options */
/* On stylise le select lui-même pour un effet moderne */
#paymentModal .form-select,
#validationModal .form-select,
#subscriptionModal .form-select,
#transferModal .form-select,
#reliquatPaymentModal .form-select {
    /* Style Filament wrapper-like */
    background-color: white !important;
    border: 1px solid rgb(209 213 219) !important; /* gray-300 */
    border-radius: 8px !important; /* rounded-lg */
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important; /* shadow-sm */
    padding: 0.5rem 2.5rem 0.5rem 2.75rem !important;
    font-size: 0.875rem !important; /* text-sm */
    transition: all 75ms ease-in-out !important;
}

#paymentModal .form-select:focus,
#validationModal .form-select:focus,
#subscriptionModal .form-select:focus,
#transferModal .form-select:focus,
#reliquatPaymentModal .form-select:focus {
    border-color: rgb(59 130 246) !important; /* primary-500 */
    box-shadow: 0 0 0 3px rgb(59 130 246 / 0.15), 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
    outline: none !important;
}

/* Style des inputs text aussi - cohérence Filament */
#paymentModal .form-control,
#validationModal .form-control,
#subscriptionModal .form-control,
#transferModal .form-control,
#reliquatPaymentModal .form-control {
    border: 1px solid rgb(209 213 219) !important;
    border-radius: 8px !important;
    box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
    transition: all 75ms ease-in-out !important;
}

#paymentModal .form-control:focus,
#validationModal .form-control:focus,
#subscriptionModal .form-control:focus,
#transferModal .form-control:focus,
#reliquatPaymentModal .form-control:focus {
    border-color: rgb(59 130 246) !important;
    box-shadow: 0 0 0 3px rgb(59 130 246 / 0.15), 0 1px 2px 0 rgb(0 0 0 / 0.05) !important;
    outline: none !important;
}

/* === OPTIONAL FEE CARDS === */
.optional-fee-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    background: #fff;
    border-left: 4px solid #0453cb;
    transition: box-shadow 0.2s ease, transform 0.2s ease;
}
.optional-fee-card:hover {
    box-shadow: 0 4px 16px rgba(4,83,203,0.1);
    transform: translateY(-1px);
}
.optional-fee-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(4,83,203,0.08);
    color: #0453cb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}
.optional-fee-body {
    flex: 1;
    min-width: 0;
}
.optional-fee-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
}
.optional-fee-desc {
    font-size: 0.78rem;
    color: #64748b;
    margin-top: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.optional-fee-price {
    text-align: right;
    flex-shrink: 0;
}
.optional-fee-price strong {
    display: block;
    color: #0453cb;
    font-size: 1rem;
    font-weight: 700;
}
.optional-fee-price strong small {
    font-size: 0.7em;
    font-weight: 500;
    color: #64748b;
}
.badge-optionnel {
    display: inline-block;
    font-size: 0.68rem;
    padding: 2px 8px;
    border-radius: 20px;
    background: rgba(6,182,212,0.08);
    color: #06b6d4;
    border: 1px solid rgba(6,182,212,0.25);
    margin-bottom: 4px;
    font-weight: 500;
}

/* === ALERTE CLASSE NON AFFECTÉE === */
.alert-no-classe {
    position: relative;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #f59e0b;
    border-left: 5px solid #d97706;
    border-radius: 12px;
    padding: 16px 20px;
    margin-bottom: 20px;
    overflow: hidden;
}
.alert-no-classe::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 120px; height: 120px;
    background: radial-gradient(circle, rgba(217,119,6,0.08) 0%, transparent 70%);
    border-radius: 50%;
    transform: translate(30px, -30px);
}
.alert-no-classe-icon {
    width: 42px; height: 42px;
    border-radius: 10px;
    background: rgba(217,119,6,0.15);
    color: #92400e;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}
.alert-no-classe-title {
    font-weight: 700;
    color: #92400e;
    font-size: 0.95rem;
    margin-bottom: 2px;
}
.alert-no-classe-text {
    font-size: 0.82rem;
    color: #78350f;
    line-height: 1.4;
}
.btn-affecter-classe {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 100%);
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 8px rgba(4,83,203,0.25);
}
.btn-affecter-classe:hover {
    background: linear-gradient(135deg, #1b64d4 0%, #5e91de 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(4,83,203,0.35);
    color: #fff;
}
.classe-manquante-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}
.classe-manquante-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    background: rgba(239,68,68,0.08);
    color: #dc2626;
    border: 1px solid rgba(239,68,68,0.2);
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.classe-manquante-badge i { font-size: 0.7rem; }

/* === MODAL AFFECTATION CLASSE (z-index géré par les sélecteurs partagés en haut) === */
#affectationClasseModal .modal-content {
    border-radius: 16px !important;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25) !important;
    overflow: hidden;
}
#affectationClasseModal .modal-header {
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 50%, #5e91de 100%);
    border: none;
    padding: 20px 24px;
    position: relative;
}
#affectationClasseModal .modal-header::after {
    content: '';
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #10b981, #06b6d4, #0453cb);
}
#affectationClasseModal .modal-title {
    color: #fff;
    font-weight: 700;
    font-size: 1.05rem;
    display: flex;
    align-items: center;
    gap: 10px;
}
#affectationClasseModal .modal-title i {
    width: 32px; height: 32px;
    background: rgba(255,255,255,0.2);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem;
}
#affectationClasseModal .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
}
#affectationClasseModal .btn-close:hover { opacity: 1; }
#affectationClasseModal .modal-body { padding: 24px; }
#affectationClasseModal .modal-footer {
    border-top: 1px solid #f1f5f9;
    padding: 16px 24px;
    background: #f8fafc;
}
.classe-option-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
    margin-bottom: 8px;
    background: #fff;
}
.classe-option-card:hover {
    border-color: #93c5fd;
    background: #f0f7ff;
}
.classe-option-card.selected {
    border-color: #0453cb;
    background: rgba(4,83,203,0.04);
    box-shadow: 0 0 0 3px rgba(4,83,203,0.1);
}
.classe-option-card.disabled {
    opacity: 0.5;
    cursor: not-allowed;
    background: #f9fafb;
}
.classe-option-radio {
    width: 20px; height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.classe-option-card.selected .classe-option-radio {
    border-color: #0453cb;
    background: #0453cb;
}
.classe-option-card.selected .classe-option-radio::after {
    content: '';
    width: 8px; height: 8px;
    background: #fff;
    border-radius: 50%;
}
.classe-option-info { flex: 1; min-width: 0; }
.classe-option-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.92rem;
}
.classe-option-places {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.places-indicator {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
}
.places-indicator.green { background: rgba(16,185,129,0.1); color: #059669; }
.places-indicator.yellow { background: rgba(245,158,11,0.1); color: #d97706; }
.places-indicator.red { background: rgba(239,68,68,0.1); color: #dc2626; }
.affectation-status-group { display: flex; gap: 8px; margin-top: 4px; }
.affectation-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.85rem;
    font-weight: 500;
    color: #64748b;
    background: #fff;
    transition: all 0.2s ease;
}
.affectation-status-chip:hover { border-color: #93c5fd; color: #1e293b; }
.affectation-status-chip.active {
    border-color: #0453cb;
    background: rgba(4,83,203,0.06);
    color: #0453cb;
    font-weight: 600;
}
.affectation-status-chip i { font-size: 0.8rem; }
.btn-affecter-submit .spinner-border { width: 16px; height: 16px; border-width: 2px; }

/* ═══════════════════════════════════════════════════════════
   INSCRIPTION SHOW — PREMIUM REDESIGN (is-* namespace)
   ═══════════════════════════════════════════════════════════ */

/* --- Hero Section --- */
.is-hero {
    background: linear-gradient(135deg, #0453cb 0%, #1b64d4 50%, #5e91de 100%);
    padding: 28px 32px 24px;
    margin: -24px -24px 0;
    position: relative;
    overflow: hidden;
}
.is-hero::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background-image: radial-gradient(circle at 20% 80%, rgba(255,255,255,0.06) 0%, transparent 50%),
                       radial-gradient(circle at 80% 20%, rgba(255,255,255,0.04) 0%, transparent 50%);
}
.is-hero-inner {
    position: relative;
    display: flex;
    align-items: center;
    gap: 20px;
    max-width: 1280px;
    flex-wrap: wrap;
}
.is-hero-avatar {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.5);
    object-fit: cover;
    flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
}
.is-hero-avatar-placeholder {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    border: 3px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.7);
    font-size: 2rem;
    flex-shrink: 0;
}
.is-hero-text { flex: 1; min-width: 200px; }
.is-hero-name {
    font-size: 1.5rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: -0.02em;
    margin-bottom: 4px;
}
.is-hero-sub {
    font-size: 0.88rem;
    color: rgba(255,255,255,0.8);
    margin-bottom: 10px;
}
.is-hero-pills { display: flex; flex-wrap: wrap; gap: 8px; }
.is-hero-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    background: rgba(255,255,255,0.15);
    color: #fff;
    border: 1px solid rgba(255,255,255,0.2);
}
.is-hero-pill.success { background: rgba(16,185,129,0.25); border-color: rgba(16,185,129,0.4); }
.is-hero-pill.warning { background: rgba(245,158,11,0.25); border-color: rgba(245,158,11,0.4); }
.is-hero-pill.danger { background: rgba(239,68,68,0.25); border-color: rgba(239,68,68,0.4); }
.is-hero-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-left: auto;
}
.is-hero-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.12);
    color: #fff;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}
.is-hero-btn:hover {
    background: rgba(255,255,255,0.22);
    color: #fff;
    transform: translateY(-1px);
}
.is-hero-btn.primary {
    background: rgba(255,255,255,0.95);
    color: #0453cb;
    border-color: transparent;
    font-weight: 700;
}
.is-hero-btn.primary:hover {
    background: #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    color: #0453cb;
}
.is-hero-btn.success-btn {
    background: rgba(16,185,129,0.9);
    border-color: rgba(16,185,129,0.5);
}
.is-hero-btn.success-btn:hover { background: #10b981; }

/* --- Cards --- */
.is-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 16px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06), 0 4px 16px rgba(0,0,0,0.04);
    margin-bottom: 16px;
    overflow: hidden;
    transition: box-shadow 0.25s ease;
}
.is-card:hover { box-shadow: 0 2px 6px rgba(0,0,0,0.08), 0 8px 24px rgba(0,0,0,0.06); }
.is-card-body { padding: 20px 24px; }

/* --- Section Headers --- */
.is-section-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
}
.is-section-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.is-section-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.01em;
}

/* --- Info Grid (replaces table-bordered) --- */
.is-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 0;
}
.is-info-row {
    display: flex;
    flex-direction: column;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
}
.is-info-row:last-child { border-bottom: none; }
.is-info-lbl {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #94a3b8;
    margin-bottom: 2px;
}
.is-info-val {
    font-size: 0.88rem;
    font-weight: 600;
    color: #1e293b;
}
.is-info-val.muted { color: #94a3b8; font-weight: 400; font-style: italic; }

/* --- Badges --- */
.is-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.is-badge.success { background: rgba(16,185,129,0.1); color: #059669; border: 1px solid rgba(16,185,129,0.2); }
.is-badge.warning { background: rgba(245,158,11,0.1); color: #d97706; border: 1px solid rgba(245,158,11,0.2); }
.is-badge.danger { background: rgba(239,68,68,0.1); color: #dc2626; border: 1px solid rgba(239,68,68,0.2); }
.is-badge.info { background: rgba(59,130,246,0.1); color: #2563eb; border: 1px solid rgba(59,130,246,0.2); }
.is-badge.primary { background: rgba(4,83,203,0.1); color: #0453cb; border: 1px solid rgba(4,83,203,0.2); }
.is-badge.secondary { background: rgba(100,116,139,0.1); color: #475569; border: 1px solid rgba(100,116,139,0.2); }

/* --- Workflow Stepper Premium --- */
.is-stepper { display: flex; align-items: flex-start; gap: 0; padding: 8px 0; }
.is-step {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    text-align: center;
}
.is-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 18px;
    left: calc(50% + 20px);
    width: calc(100% - 40px);
    height: 2px;
    background: #e2e8f0;
}
.is-step.completed:not(:last-child)::after { background: #10b981; }
.is-step-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    position: relative;
    z-index: 1;
    transition: all 0.3s ease;
}
.is-step.completed .is-step-circle {
    background: #10b981;
    color: #fff;
    box-shadow: 0 2px 8px rgba(16,185,129,0.3);
}
.is-step.current .is-step-circle {
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    box-shadow: 0 2px 8px rgba(4,83,203,0.3);
    animation: is-pulse 2s ease infinite;
}
.is-step.pending .is-step-circle {
    background: #f1f5f9;
    color: #94a3b8;
    border: 2px solid #e2e8f0;
}
@keyframes is-pulse {
    0%, 100% { box-shadow: 0 2px 8px rgba(4,83,203,0.3); }
    50% { box-shadow: 0 2px 16px rgba(4,83,203,0.5); }
}
.is-step-label {
    margin-top: 8px;
    font-size: 0.7rem;
    font-weight: 600;
    max-width: 80px;
    line-height: 1.3;
}
.is-step.completed .is-step-label { color: #059669; }
.is-step.current .is-step-label { color: #0453cb; }
.is-step.pending .is-step-label { color: #94a3b8; }

/* --- Progress Bar Premium --- */
.is-progress-wrap {
    background: #f1f5f9;
    border-radius: 6px;
    height: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}
.is-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #0453cb, #5e91de);
    border-radius: 6px;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}
.is-progress-bar::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: is-shimmer 2s ease infinite;
}
@keyframes is-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* --- Status Row --- */
.is-status-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}
.is-status-row:last-child { border-bottom: none; }
.is-status-lbl { font-size: 0.85rem; color: #64748b; }

/* --- Validation Info --- */
.is-validation-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 10px;
    margin-top: 12px;
}
.is-validation-box.success { background: rgba(16,185,129,0.06); border: 1px solid rgba(16,185,129,0.15); }
.is-validation-box.warning { background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.15); }
.is-validation-box-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.8rem; flex-shrink: 0;
}
.is-validation-box.success .is-validation-box-icon { background: rgba(16,185,129,0.15); color: #059669; }
.is-validation-box.warning .is-validation-box-icon { background: rgba(245,158,11,0.15); color: #d97706; }
.is-validation-box-text { font-size: 0.82rem; color: #334155; line-height: 1.4; }

/* --- Mobile Stepper --- */
.is-stepper-mobile { display: none; }

/* --- Responsive --- */
/* --- Finance Summary Premium --- */
.is-finance-summary {
    background: linear-gradient(135deg, #f0f7ff 0%, #e8f0fe 100%);
    border: 1px solid rgba(4,83,203,0.12);
    border-radius: 14px;
    padding: 16px 20px;
    margin-top: 20px;
    margin-bottom: 16px;
}
.is-finance-summary.paid {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-color: rgba(16,185,129,0.2);
}
.is-finance-grid {
    display: flex;
    align-items: center;
    gap: 0;
    flex-wrap: wrap;
}
.is-finance-kpi {
    flex: 1;
    min-width: 120px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 2px 0;
}
.is-finance-kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(4,83,203,0.1);
    color: #0453cb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    flex-shrink: 0;
}
.is-finance-kpi-icon.success { background: rgba(16,185,129,0.1); color: #059669; }
.is-finance-kpi-icon.danger { background: rgba(239,68,68,0.1); color: #dc2626; }
.is-finance-kpi-label {
    font-size: 0.72rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: #64748b;
    margin-bottom: 1px;
}
.is-finance-kpi-val {
    font-size: 1.25rem;
    font-weight: 800;
    color: #1e293b;
    letter-spacing: -0.02em;
    line-height: 1.2;
}
.is-finance-kpi-val small { font-size: 0.65em; font-weight: 600; color: #94a3b8; }
.is-finance-sep {
    width: 1px;
    height: 36px;
    background: rgba(4,83,203,0.12);
    margin: 0 12px;
    flex-shrink: 0;
}
.is-finance-summary.paid .is-finance-sep { background: rgba(16,185,129,0.2); }

/* --- Empty States Premium --- */
.is-empty-state {
    text-align: center;
    padding: 32px 20px;
}
.is-empty-icon {
    width: 56px;
    height: 56px;
    border-radius: 16px;
    background: rgba(4,83,203,0.06);
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    margin: 0 auto 12px;
}
.is-empty-text {
    font-size: 0.92rem;
    font-weight: 700;
    color: #475569;
    margin-bottom: 4px;
}
.is-empty-sub {
    font-size: 0.8rem;
    color: #94a3b8;
    max-width: 340px;
    margin: 0 auto;
    line-height: 1.5;
}

/* --- Alerts inside is-card premium --- */
.is-card .alert {
    border-radius: 10px;
    border: none;
    font-size: 0.85rem;
}
.is-card .alert-info {
    background: rgba(59,130,246,0.06);
    color: #1e40af;
    border: 1px solid rgba(59,130,246,0.12);
}
.is-card .alert-success {
    background: rgba(16,185,129,0.06);
    color: #065f46;
    border: 1px solid rgba(16,185,129,0.12);
}
.is-card .alert-warning {
    background: rgba(245,158,11,0.06);
    color: #92400e;
    border: 1px solid rgba(245,158,11,0.12);
}
.is-card .alert-danger {
    background: rgba(239,68,68,0.06);
    color: #991b1b;
    border: 1px solid rgba(239,68,68,0.12);
}

/* --- Tables inside is-card (payments, financial) — PREMIUM --- */
.is-card .table {
    margin-bottom: 0;
    border-collapse: separate;
    border-spacing: 0;
}
.is-card .table.table-bordered { border: none; }
.is-card .table.table-bordered > :not(caption) > * > * { border: none; }
.is-card .table.table-striped > tbody > tr:nth-of-type(odd) > * { background: transparent; }
.is-card .table thead th {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border: none !important;
    border-bottom: 2px solid #e2e8f0 !important;
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
    padding: 12px 14px;
    white-space: nowrap;
}
.is-card .table tbody td {
    padding: 14px;
    font-size: 0.85rem;
    color: #334155;
    border: none !important;
    border-bottom: 1px solid #f1f5f9 !important;
    vertical-align: middle;
}
.is-card .table tbody tr:last-child td { border-bottom: none !important; }
.is-card .table tbody tr:hover { background: rgba(4,83,203,0.02); }
.is-card .table tbody tr.reliquat-row { background: rgba(245,158,11,0.03); }
.is-card .table tbody tr.reliquat-row:hover { background: rgba(245,158,11,0.06); }
.is-card .table tfoot tr { border-top: 2px solid #e2e8f0; }
.is-card .table tfoot th {
    background: #f8fafc !important;
    border: none !important;
    font-size: 0.82rem;
    font-weight: 700;
    color: #0453cb;
    padding: 12px 14px;
}
.is-card .table.table-success, .is-card .table tfoot tr.table-success > * {
    background: rgba(16,185,129,0.04) !important;
    color: #059669;
}
.is-card .table-sm td, .is-card .table-sm th { padding: 10px 12px; }

/* Payment section titles premium */
.is-card h6.text-success, .is-card h6.text-warning, .is-card h6.text-danger {
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    padding: 10px 14px;
    border-radius: 8px;
    margin-bottom: 12px !important;
}
.is-card h6.text-success { background: rgba(16,185,129,0.06); }
.is-card h6.text-warning { background: rgba(245,158,11,0.06); }
.is-card h6.text-danger { background: rgba(239,68,68,0.06); }

/* btn-group inside tables */
.is-card .btn-group .btn-sm {
    border-radius: 6px !important;
    padding: 5px 10px;
    font-size: 0.78rem;
}
.is-card .btn-group { gap: 4px; }

/* --- Other Inscriptions Cards --- */
.is-other-inscription-card {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    text-decoration: none;
    min-width: 160px;
    transition: all 0.2s ease;
}
.is-other-inscription-card:hover {
    border-color: #0453cb;
    box-shadow: 0 2px 8px rgba(4,83,203,0.12);
    transform: translateY(-1px);
}
.is-other-inscription-year {
    font-size: 0.78rem;
    font-weight: 700;
    color: #0453cb;
}
.is-other-inscription-class {
    font-size: 0.75rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}

/* --- Finance summary responsive --- */
@media (max-width: 640px) {
    .is-finance-grid { flex-direction: column; gap: 12px; }
    .is-finance-sep { width: 100%; height: 1px; margin: 0; }
    .is-finance-kpi-val { font-size: 1.1rem; }
}

/* --- Premium table styling inside is-card --- */
.is-card .table-bordered {
    border: none;
    margin-bottom: 0;
}
.is-card .table-bordered th {
    background: #f8fafc;
    border: none;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 10px 14px;
    vertical-align: middle;
}
.is-card .table-bordered td {
    border: none;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.88rem;
    font-weight: 500;
    color: #1e293b;
    padding: 10px 14px;
    vertical-align: middle;
}
.is-card .table-bordered tr:last-child th,
.is-card .table-bordered tr:last-child td { border-bottom: none; }

/* --- Premium badges inside is-card --- */
.is-card .badge.bg-success { background: rgba(16,185,129,0.12) !important; color: #059669; font-weight: 600; }
.is-card .badge.bg-warning { background: rgba(245,158,11,0.12) !important; color: #d97706; font-weight: 600; }
.is-card .badge.bg-danger { background: rgba(239,68,68,0.12) !important; color: #dc2626; font-weight: 600; }
.is-card .badge.bg-info { background: rgba(59,130,246,0.12) !important; color: #2563eb; font-weight: 600; }
.is-card .badge.bg-primary { background: rgba(4,83,203,0.12) !important; color: #0453cb; font-weight: 600; }
.is-card .badge.bg-secondary { background: rgba(100,116,139,0.12) !important; color: #475569; font-weight: 600; }

/* --- Accordion inside is-card --- */
.is-card .accordion-item { border: 1px solid #e2e8f0; border-radius: 10px !important; margin-bottom: 8px; overflow: hidden; }
.is-card .accordion-button { font-weight: 600; font-size: 0.88rem; color: #1e293b; padding: 12px 16px; }
.is-card .accordion-button:not(.collapsed) { background: rgba(4,83,203,0.04); color: #0453cb; }
.is-card .accordion-button:focus { box-shadow: 0 0 0 2px rgba(4,83,203,0.15); }

@media (max-width: 768px) {
    .is-hero { padding: 20px 16px; margin: -16px -16px 0; }
    .is-hero-inner { gap: 14px; }
    .is-hero-avatar, .is-hero-avatar-placeholder { width: 64px; height: 64px; font-size: 1.5rem; }
    .is-hero-name { font-size: 1.15rem; }
    .is-hero-actions { width: 100%; margin-left: 0; margin-top: 8px; }
    .is-card-body { padding: 16px; }
    .is-info-grid { grid-template-columns: 1fr; }
    .is-stepper { display: none; }
    .is-stepper-mobile { display: block; }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">
        <!-- Hero Premium -->
        <div class="is-hero">
            <div class="is-hero-inner">
                @if($inscription->etudiant->photo_url)
                    <img src="{{ $inscription->etudiant->photo_url }}" alt="Photo" class="is-hero-avatar">
                @else
                    <div class="is-hero-avatar-placeholder">
                        <i class="fas fa-user"></i>
                    </div>
                @endif
                <div class="is-hero-text">
                    <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.6);margin-bottom:2px;"><i class="fas fa-file-alt me-1"></i>Fiche Inscription</div>
                    <div class="is-hero-name">{{ $inscription->etudiant->nom }} {{ $inscription->etudiant->prenoms }}</div>
                    <div class="is-hero-sub">Matricule : {{ $inscription->etudiant->matricule }} · {{ $inscription->filiere->name ?? '' }} · {{ $inscription->anneeUniversitaire->name ?? '' }}</div>
                    <div class="is-hero-pills">
                        <span class="is-hero-pill {{ $inscription->status === 'active' ? 'success' : ($inscription->status === 'en_attente' ? 'warning' : 'danger') }}">
                            <i class="fas fa-{{ $inscription->status === 'active' ? 'check-circle' : ($inscription->status === 'en_attente' ? 'clock' : 'times-circle') }}"></i>
                            {{ ucfirst($inscription->status) }}
                        </span>
                        @if($inscription->workflow_step)
                        <span class="is-hero-pill">
                            <i class="fas fa-tasks"></i> {{ $inscription->workflow_step_label ?? ucfirst(str_replace('_', ' ', $inscription->workflow_step)) }}
                        </span>
                        @endif
                        @if($inscription->is_sous_reserve)
                        <span class="is-hero-pill warning">
                            <i class="fas fa-exclamation-triangle"></i> Sous réserve
                        </span>
                        @endif
                    </div>
                </div>
                <div class="is-hero-actions">
                    @can('inscriptions.validate')
                        <a href="{{ route('esbtp.inscriptions.administration') }}" class="is-hero-btn">
                            <i class="fas fa-cog"></i>
                            <span class="d-none d-md-inline">Administration</span>
                        </a>
                        @if(auth()->user()->can('inscriptions.validate') && $inscription->status === 'en_attente' && !$inscription->paiement_validation_id)
                            <button class="is-hero-btn primary" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="preparePaymentModal({{ $inscription->id }})">
                                <i class="fas fa-credit-card"></i>
                                <span class="d-none d-lg-inline">Valider avec paiement</span>
                            </button>
                        @endif
                        @if(!($inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree'))
                            <button class="is-hero-btn success-btn" data-bs-toggle="modal" data-bs-target="#validationModal" onclick="openValidationModal({{ $inscription->id }})">
                                <i class="fas fa-check"></i>
                                <span class="d-none d-lg-inline">Valider</span>
                            </button>
                        @endif
                    @endcan
                    @can('inscriptions.edit')
                        <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="is-hero-btn">
                            <i class="fas fa-edit"></i>
                            <span class="d-none d-sm-inline">Modifier</span>
                        </a>
                    @endcan
                    <a href="{{ route('esbtp.etudiants.show', $inscription->etudiant) }}" class="is-hero-btn primary">
                        <i class="fas fa-user"></i>
                        <span class="d-none d-md-inline">Fiche étudiant</span>
                    </a>
                    @can('messages.send')
                        <x-share-to-chat kind="inscription" :id="$inscription->id" label="Envoyer" class="is-hero-btn" />
                    @endcan
                    <a href="{{ route('esbtp.etudiants.index') }}" class="is-hero-btn">
                        <i class="fas fa-arrow-left"></i>
                        <span class="d-none d-sm-inline">Retour</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="p-lg">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('account_info'))
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <h6 class="alert-heading"><i class="fas fa-user-check me-2"></i>Informations de connexion générées</h6>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Nom d'utilisateur:</strong> {{ session('account_info')['username'] }}</p>
                            <p class="mb-1"><strong>Rôle:</strong> {{ session('account_info')['role'] }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Mot de passe temporaire:</strong> <span class="badge bg-light text-dark p-2 font-monospace">{{ session('account_info')['password'] }}</span></p>
                            <p class="mb-0 text-muted"><small>Veuillez communiquer ces informations à l'étudiant. Le mot de passe devra être changé à la première connexion.</small></p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            {{-- Bannière inscription année future non marquée sous réserve --}}
            @php
                $isFutureNonReserve = ($anneeCourante ?? null)
                    && !$inscription->is_sous_reserve
                    && optional($inscription->anneeUniversitaire)->start_date
                    && optional($anneeCourante)->start_date
                    && $inscription->anneeUniversitaire->start_date > $anneeCourante->start_date;
            @endphp
            @if($isFutureNonReserve && Route::has('esbtp.inscriptions.marquer-sous-reserve'))
            <div style="background:linear-gradient(135deg,#fef3c7,#fde68a); border:1.5px solid #f59e0b; border-left:5px solid #d97706; border-radius:10px; padding:16px 20px; margin-bottom:16px; display:flex; align-items:flex-start; gap:14px;">
                <div style="flex-shrink:0; width:36px; height:36px; background:#d97706; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-exclamation-triangle" style="color:#fff; font-size:.9rem;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; color:#92400e; font-size:.95rem; margin-bottom:4px;">
                        Inscription pour une année future
                    </div>
                    <div style="color:#78350f; font-size:.85rem; line-height:1.5; margin-bottom:8px;">
                        Cette inscription concerne l'année <strong>{{ $inscription->anneeUniversitaire->name }}</strong> qui n'est pas encore l'année courante.
                        Souhaitez-vous la marquer sous réserve (ex: en attente du Baccalauréat) ?
                    </div>
                    @can('inscriptions.edit')
                    <form method="POST" action="{{ route('esbtp.inscriptions.marquer-sous-reserve', $inscription) }}" style="display:inline-flex; gap:8px; align-items:center;">
                        @csrf
                        <input type="text" name="condition_reserve" value="BACCALAURÉAT" class="form-control form-control-sm" style="width:180px; font-size:.82rem;" placeholder="Condition...">
                        <button type="submit" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:linear-gradient(135deg, #d97706, #f59e0b); color:#fff; border:none; border-radius:8px; font-size:.84rem; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(217,119,6,.3);">
                            <i class="fas fa-clipboard-check"></i> Marquer sous réserve
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            @endif

            {{-- Bannière inscription sous réserve (info) --}}
            @if($inscription->is_sous_reserve)
            <div style="background:linear-gradient(135deg,#dbeafe,#bfdbfe); border:1.5px solid #3b82f6; border-left:5px solid #0453cb; border-radius:10px; padding:16px 20px; margin-bottom:16px; display:flex; align-items:flex-start; gap:14px;">
                <div style="flex-shrink:0; width:36px; height:36px; background:#0453cb; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-info-circle" style="color:#fff; font-size:.9rem;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; color:#1e3a5f; font-size:.95rem; margin-bottom:4px;">
                        Inscription sous réserve
                    </div>
                    <div style="color:#1e40af; font-size:.85rem; line-height:1.5; margin-bottom:8px;">
                        Cette inscription pour <strong>{{ $inscription->anneeUniversitaire->name ?? '' }}</strong> est sous réserve
                        de son <strong>{{ $inscription->condition_reserve ?? 'diplôme' }}</strong>.
                        @if(Route::has('esbtp.inscriptions.sous-reserve'))
                        La réserve sera levée depuis la <a href="{{ route('esbtp.inscriptions.sous-reserve') }}" style="color:#0453cb; font-weight:600;">page de gestion des réserves</a>.
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- Bannière pré-inscription caissier --}}
            @if($inscription->etudiant && $inscription->etudiant->matricule && str_starts_with($inscription->etudiant->matricule, 'PRE-'))
            @php
                $piMissing = [];
                if (!$inscription->etudiant->date_naissance) $piMissing[] = 'Date de naissance';
                if (!$inscription->etudiant->sexe) $piMissing[] = 'Sexe';
                if (!$inscription->etudiant->lieu_naissance) $piMissing[] = 'Lieu de naissance';
                if (!$inscription->etudiant->adresse) $piMissing[] = 'Adresse';
                $piCreator = $inscription->createdBy;
            @endphp
            @if(count($piMissing) > 0)
            <div style="background:linear-gradient(135deg,#dbeafe,#bfdbfe); border:1.5px solid #3b82f6; border-left:5px solid #0453cb; border-radius:10px; padding:16px 20px; margin-bottom:16px; display:flex; align-items:flex-start; gap:14px;">
                <div style="flex-shrink:0; width:36px; height:36px; background:#0453cb; border-radius:50%; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-clipboard-list" style="color:#fff; font-size:.9rem;"></i>
                </div>
                <div style="flex:1;">
                    <div style="font-weight:700; color:#1e3a5f; font-size:.95rem; margin-bottom:4px;">Pré-inscription — Informations à compléter</div>
                    <div style="color:#1e40af; font-size:.85rem; margin-bottom:8px;">
                        Créée par @if($piCreator) <strong>{{ $piCreator->name }}</strong> @endif
                        le {{ $inscription->created_at->format('d/m/Y à H:i') }}.
                        Complétez les informations avant de valider.
                    </div>
                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:10px;">
                        @foreach($piMissing as $field)
                        <span style="display:inline-flex; align-items:center; gap:4px; padding:3px 10px; background:rgba(4,83,203,.1); border:1px solid rgba(4,83,203,.2); border-radius:6px; font-size:.75rem; color:#0453cb; font-weight:600;">
                            <i class="fas fa-times-circle" style="font-size:.6rem;"></i> {{ $field }}
                        </span>
                        @endforeach
                    </div>
                    <a href="{{ route('esbtp.etudiants.edit', $inscription->etudiant->id) }}" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:linear-gradient(135deg,#0453cb,#5e91de); color:#fff; border:none; border-radius:8px; font-size:.84rem; font-weight:600; text-decoration:none; box-shadow:0 2px 8px rgba(4,83,203,.25);">
                        <i class="fas fa-edit"></i> Compléter les informations
                    </a>
                </div>
            </div>
            @endif
            @endif

            <div class="row">
                <div class="col-12 col-md-5 col-lg-4">
                    <!-- Informations étudiant -->
                    <div class="is-card">
                        <div class="is-card-body">
                            <div class="is-section-header">
                                <div class="is-section-icon"><i class="fas fa-user"></i></div>
                                <div class="is-section-title">Informations de l'étudiant</div>
                                <span class="ms-auto is-badge {{ $inscription->etudiant->statut == 'actif' ? 'success' : 'danger' }}">
                                    <i class="fas fa-{{ $inscription->etudiant->statut == 'actif' ? 'check-circle' : 'times-circle' }}"></i>
                                    {{ ucfirst($inscription->etudiant->statut ?? 'inconnu') }}
                                </span>
                            </div>
                            <div class="is-info-grid">
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Genre</span>
                                    <span class="is-info-val">{{ $inscription->etudiant->sexe == 'M' ? 'Masculin' : 'Féminin' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Date de naissance</span>
                                    <span class="is-info-val">{{ \Carbon\Carbon::parse($inscription->etudiant->date_naissance)->format('d/m/Y') }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Lieu de naissance</span>
                                    <span class="is-info-val {{ !$inscription->etudiant->lieu_naissance ? 'muted' : '' }}">{{ $inscription->etudiant->lieu_naissance ?: 'Non renseigné' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Nationalité</span>
                                    <span class="is-info-val {{ !$inscription->etudiant->nationalite ? 'muted' : '' }}">{{ $inscription->etudiant->nationalite ?: 'Non renseignée' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Téléphone</span>
                                    <span class="is-info-val">{{ $inscription->etudiant->telephone ?: 'Non renseigné' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Email</span>
                                    <span class="is-info-val {{ !$inscription->etudiant->email_personnel ? 'muted' : '' }}" style="word-break:break-all;">{{ $inscription->etudiant->email_personnel ?: 'Non renseigné' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Ville</span>
                                    <span class="is-info-val {{ !$inscription->etudiant->ville ? 'muted' : '' }}">{{ $inscription->etudiant->ville ?: 'Non renseignée' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Commune</span>
                                    <span class="is-info-val {{ !$inscription->etudiant->commune ? 'muted' : '' }}">{{ $inscription->etudiant->commune ?: 'Non renseignée' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Adresse</span>
                                    <span class="is-info-val {{ !$inscription->etudiant->adresse ? 'muted' : '' }}">{{ $inscription->etudiant->adresse ?: 'Non renseignée' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statut de l'inscription -->
                    <div class="is-card">
                        <div class="is-card-body">
                            <div class="is-section-header">
                                <div class="is-section-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="is-section-title">Statut de l'inscription</div>
                            </div>

                            <div class="is-status-row">
                                <span class="is-status-lbl">Statut</span>
                                <span class="is-badge {{ $inscription->status === 'active' ? 'success' : ($inscription->status === 'en_attente' ? 'warning' : 'danger') }}">
                                    <i class="fas fa-{{ $inscription->status === 'active' ? 'check-circle' : ($inscription->status === 'en_attente' ? 'clock' : 'times-circle') }}"></i>
                                    {{ ucfirst($inscription->status) }}
                                </span>
                            </div>

                            @php
                                $steps = [
                                    'prospect' => ['label' => 'Prospect', 'icon' => 'fas fa-user-plus'],
                                    'documents_complets' => ['label' => 'Documents', 'icon' => 'fas fa-file-check'],
                                    'en_validation' => ['label' => 'Validation', 'icon' => 'fas fa-hourglass-half'],
                                    'valide' => ['label' => 'Validé', 'icon' => 'fas fa-check'],
                                    'etudiant_cree' => ['label' => 'Créé', 'icon' => 'fas fa-graduation-cap']
                                ];
                                $stepKeys = array_keys($steps);
                                $currentStepIndex = array_search($inscription->workflow_step, $stepKeys);
                                $progress = $currentStepIndex !== false ? (($currentStepIndex + 1) / count($stepKeys)) * 100 : 0;
                            @endphp

                            <div class="is-progress-wrap" title="{{ round($progress) }}%">
                                <div class="is-progress-bar" style="width: {{ $progress }}%"></div>
                            </div>

                            <!-- Desktop : Stepper horizontal premium -->
                            <div class="is-stepper d-none d-md-flex">
                                @foreach($steps as $stepKey => $stepInfo)
                                    @php
                                        $stepIndex = array_search($stepKey, $stepKeys);
                                        $isCompleted = $stepIndex < $currentStepIndex;
                                        $isCurrent = $stepKey === $inscription->workflow_step;
                                    @endphp
                                    <div class="is-step {{ $isCompleted ? 'completed' : ($isCurrent ? 'current' : 'pending') }}">
                                        <div class="is-step-circle">
                                            @if($isCompleted)
                                                <i class="fas fa-check"></i>
                                            @else
                                                <i class="{{ $stepInfo['icon'] }}"></i>
                                            @endif
                                        </div>
                                        <div class="is-step-label">{{ $stepInfo['label'] }}</div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Mobile : Stepper vertical -->
                            <div class="is-stepper-mobile d-md-none">
                                @foreach($steps as $stepKey => $stepInfo)
                                    @php
                                        $stepIndex = array_search($stepKey, $stepKeys);
                                        $isCompleted = $stepIndex < $currentStepIndex;
                                        $isCurrent = $stepKey === $inscription->workflow_step;
                                    @endphp
                                    <div class="d-flex align-items-center mb-3 pb-3 {{ !$loop->last ? 'border-bottom' : '' }}">
                                        <div class="me-3 flex-shrink-0">
                                            <div class="is-step-circle" style="width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:0.85rem;{{ $isCompleted ? 'background:#10b981;color:#fff;' : ($isCurrent ? 'background:linear-gradient(135deg,#0453cb,#5e91de);color:#fff;' : 'background:#f1f5f9;color:#94a3b8;border:2px solid #e2e8f0;') }}">
                                                @if($isCompleted)
                                                    <i class="fas fa-check"></i>
                                                @else
                                                    <i class="{{ $stepInfo['icon'] }}"></i>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold" style="color:{{ $isCompleted ? '#059669' : ($isCurrent ? '#0453cb' : '#94a3b8') }};font-size:0.88rem;">
                                                {{ $stepInfo['label'] }}
                                            </div>
                                            <small style="color:#94a3b8;font-size:0.75rem;">
                                                @if($isCompleted) <i class="fas fa-check-circle me-1"></i>Complété
                                                @elseif($isCurrent) <i class="fas fa-arrow-right me-1"></i>En cours
                                                @else <i class="fas fa-clock me-1"></i>En attente
                                                @endif
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($inscription->paiement_validation_id)
                                <div class="is-validation-box success">
                                    <div class="is-validation-box-icon"><i class="fas fa-check-circle"></i></div>
                                    <div class="is-validation-box-text">Paiement associé à cette inscription</div>
                                </div>
                            @else
                                <div class="is-validation-box warning">
                                    <div class="is-validation-box-icon"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div class="is-validation-box-text">Aucun paiement associé</div>
                                </div>
                            @endif

                            @if($inscription->date_validation)
                                <div class="is-info-row" style="margin-top:8px;">
                                    <span class="is-info-lbl">Date de validation</span>
                                    <span class="is-info-val">{{ \Carbon\Carbon::parse($inscription->date_validation)->format('d/m/Y à H:i') }}</span>
                                </div>
                            @endif

                            @if($inscription->validated_by)
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Validé par</span>
                                    <span class="is-info-val">{{ \App\Models\User::find($inscription->validated_by)->name ?? 'Utilisateur inconnu' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-7 col-lg-8">
                    {{-- Alerte classe manquante --}}
                    @if(!$inscription->classe_id)
                    <div class="alert-no-classe">
                        <div class="d-flex align-items-start gap-3">
                            <div class="alert-no-classe-icon">
                                <i class="fas fa-user-slash"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="alert-no-classe-title">Cet étudiant n'est affecté à aucune classe</div>
                                <div class="alert-no-classe-text mb-2">
                                    L'étudiant a été retiré de sa classe ou n'a jamais été affecté. Choisissez une classe pour réactiver ses frais de scolarité.
                                </div>
                                @can('inscriptions.edit')
                                <button type="button" class="btn-affecter-classe" data-bs-toggle="modal" data-bs-target="#affectationClasseModal">
                                    <i class="fas fa-user-plus"></i>
                                    Affecter à une classe
                                </button>
                                @endcan
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Informations de l'inscription -->
                    <div class="is-card">
                        <div class="is-card-body">
                            <div class="is-section-header">
                                <div class="is-section-icon"><i class="fas fa-graduation-cap"></i></div>
                                <div class="is-section-title">Informations académiques</div>
                            </div>
                            @php
                                $insIsLmd = ($inscription->classe?->systeme_academique ?? '') === 'LMD';
                                $insLmdParcours = $insIsLmd && $inscription->classe?->parcours
                                    && $inscription->classe->parcours->mention
                                    && $inscription->classe->parcours->mention->domaine
                                    ? $inscription->classe->parcours
                                    : null;
                            @endphp
                            @if($insLmdParcours)
                                {{-- LMD avec parcours : tree premium hiérarchique
                                     Domaine → Mention → Parcours → Classe (cf rule premium-redesign tree IDE-style) --}}
                                <div style="margin-bottom:1rem;">
                                    <x-lmd-hierarchy-tree :parcours="$insLmdParcours" :classe="$inscription->classe" />
                                </div>
                            @endif
                            <div class="is-info-grid" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                                @if(!$insLmdParcours)
                                    <div class="is-info-row">
                                        <span class="is-info-lbl">Filière</span>
                                        <span class="is-info-val">{{ $inscription->filiere->name ?? '—' }}</span>
                                    </div>
                                @endif
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Niveau</span>
                                    <span class="is-info-val">{{ $inscription->niveau->name ?? '—' }}</span>
                                </div>
                                @if(!$insLmdParcours)
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Classe</span>
                                    <span class="is-info-val" id="classe-name-desktop">
                                        @if($inscription->classe)
                                            {{ $inscription->classe->name }}
                                        @else
                                            <div class="classe-manquante-cell">
                                                <span class="classe-manquante-badge"><i class="fas fa-exclamation-circle"></i> Non affecté</span>
                                                @can('inscriptions.edit')
                                                <button type="button" class="btn btn-sm btn-affecter-classe" data-bs-toggle="modal" data-bs-target="#affectationClasseModal" style="padding:4px 12px;font-size:0.78rem;"><i class="fas fa-user-plus"></i> Affecter</button>
                                                @endcan
                                            </div>
                                        @endif
                                    </span>
                                </div>
                                @endif
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Année universitaire</span>
                                    <span class="is-info-val">{{ $inscription->anneeUniversitaire->name }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Statut d'affectation</span>
                                    <span class="is-info-val" id="affectation-badge-desktop">
                                        @if($inscription->affectation_status)
                                            @switch($inscription->affectation_status)
                                                @case('affecté')
                                                    <span class="is-badge success"><i class="fas fa-check-circle"></i> Affecté</span>
                                                    @break
                                                @case('réaffecté')
                                                    <span class="is-badge warning"><i class="fas fa-exchange-alt"></i> Réaffecté</span>
                                                    @break
                                                @case('non_affecté')
                                                    <span class="is-badge danger"><i class="fas fa-times-circle"></i> Non affecté</span>
                                                    @break
                                                @default
                                                    <span class="is-badge secondary">{{ $inscription->affectation_status }}</span>
                                            @endswitch
                                        @else
                                            <span class="is-info-val muted">Non renseigné</span>
                                        @endif
                                    </span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Date d'inscription</span>
                                    <span class="is-info-val">{{ $inscription->date_inscription ? \Carbon\Carbon::parse($inscription->date_inscription)->format('d/m/Y') : '—' }}</span>
                                </div>
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Type d'inscription</span>
                                    <span class="is-info-val">
                                        <span class="is-badge {{ in_array($inscription->type_inscription, ['reinscription', 'réinscription']) ? 'info' : 'primary' }}">
                                            {{ in_array($inscription->type_inscription, ['reinscription', 'réinscription']) ? 'Réinscription' : ucfirst($inscription->type_inscription) }}
                                        </span>
                                        @if($inscription->est_transfert)
                                            <span class="is-badge warning ms-1"><i class="fas fa-exchange-alt"></i> Transfert</span>
                                        @endif
                                    </span>
                                </div>
                                @if($inscription->est_transfert && $inscription->etablissement_origine)
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Établissement d'origine</span>
                                    <span class="is-info-val"><i class="fas fa-school me-1" style="color:#94a3b8;"></i>{{ $inscription->etablissement_origine }}</span>
                                </div>
                                @endif
                                <div class="is-info-row">
                                    <span class="is-info-lbl">Observations</span>
                                    <span class="is-info-val {{ !$inscription->observations && !(in_array($inscription->type_inscription, ['reinscription', 'réinscription']) && $reinscriptionData) ? 'muted' : '' }}">
                                        @if(in_array($inscription->type_inscription, ['reinscription', 'réinscription']) && $reinscriptionData)
                                            @if(isset($reinscriptionData['decision']))
                                                <span class="is-badge {{ strtolower($reinscriptionData['decision']) === 'passage' ? 'success' : (strtolower($reinscriptionData['decision']) === 'redoublement' ? 'danger' : 'warning') }}">{{ $reinscriptionData['decision_label'] ?? $reinscriptionData['decision'] }}</span>
                                            @endif
                                            @if(!$reinscriptionData['reliquat_gere'])
                                                <span class="is-badge warning ms-1"><i class="fas fa-exclamation-triangle"></i> {{ number_format($reinscriptionData['reliquat_montant'], 0, ',', ' ') }} F</span>
                                            @endif
                                        @else
                                            {{ $inscription->observations ?: 'Aucune' }}
                                        @endif
                                    </span>
                                </div>
                            </div>

                            {{-- Autres inscriptions de cet étudiant --}}
                            @if(isset($otherInscriptions) && $otherInscriptions->count() > 0)
                            <div style="margin-bottom:16px;">
                                <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:#94a3b8;margin-bottom:8px;"><i class="fas fa-layer-group me-1"></i>Autres inscriptions</div>
                                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                    @foreach($otherInscriptions as $otherInsc)
                                    <a href="{{ route('esbtp.inscriptions.show', $otherInsc) }}" class="is-other-inscription-card">
                                        <div class="is-other-inscription-year">{{ $otherInsc->anneeUniversitaire->name ?? '—' }}</div>
                                        <div class="is-other-inscription-class">{{ $otherInsc->classe->name ?? $otherInsc->filiere->name ?? '—' }}</div>
                                        <span class="is-badge {{ $otherInsc->status === 'active' ? 'success' : ($otherInsc->status === 'en_attente' ? 'warning' : 'danger') }}" style="font-size:0.65rem;padding:2px 8px;">{{ ucfirst($otherInsc->status) }}</span>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            @if($canViewFinancials ?? true)
                            @php
                                $totalAttendu = collect($feeCategoriesWithRules)->where('is_configured', true)->sum('montant_attendu');
                                $totalPaye = collect($feeCategoriesWithRules)->sum('total_paye');
                                $soldeGlobal = $totalAttendu - $totalPaye;
                                $obligatoiresConfigures = collect($feeCategoriesWithRules)->where('is_mandatory', true)->where('is_configured', true)->count();
                                $obligatoiresTotal = collect($feeCategoriesWithRules)->where('is_mandatory', true)->count();
                                $progressPct = $totalAttendu > 0 ? min(100, round(($totalPaye / $totalAttendu) * 100)) : 0;
                            @endphp
                            <div class="is-finance-summary {{ $soldeGlobal <= 0 ? 'paid' : '' }}">
                                <div class="is-finance-grid">
                                    <div class="is-finance-kpi">
                                        <div class="is-finance-kpi-icon"><i class="fas fa-receipt"></i></div>
                                        <div>
                                            <div class="is-finance-kpi-label">Total attendu</div>
                                            <div class="is-finance-kpi-val">{{ number_format($totalAttendu, 0, ',', ' ') }} <small>F</small></div>
                                        </div>
                                    </div>
                                    <div class="is-finance-sep"></div>
                                    <div class="is-finance-kpi">
                                        <div class="is-finance-kpi-icon success"><i class="fas fa-check-circle"></i></div>
                                        <div>
                                            <div class="is-finance-kpi-label">Total payé</div>
                                            <div class="is-finance-kpi-val" style="color:#059669;">{{ number_format($totalPaye, 0, ',', ' ') }} <small>F</small></div>
                                        </div>
                                    </div>
                                    <div class="is-finance-sep"></div>
                                    <div class="is-finance-kpi">
                                        <div class="is-finance-kpi-icon {{ $soldeGlobal <= 0 ? 'success' : 'danger' }}"><i class="fas fa-{{ $soldeGlobal <= 0 ? 'check-double' : 'exclamation-circle' }}"></i></div>
                                        <div>
                                            <div class="is-finance-kpi-label">Solde restant</div>
                                            <div class="is-finance-kpi-val" style="color:{{ $soldeGlobal <= 0 ? '#059669' : '#dc2626' }};">{{ number_format($soldeGlobal, 0, ',', ' ') }} <small>F</small></div>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:14px;">
                                    <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
                                        <span style="font-size:0.75rem;font-weight:700;color:#0453cb;">{{ $progressPct }}% payé</span>
                                        <span style="font-size:0.72rem;font-weight:600;color:#94a3b8;">{{ $obligatoiresConfigures }}/{{ $obligatoiresTotal }} frais obligatoires configurés</span>
                                    </div>
                                    <div class="is-progress-wrap" style="margin-bottom:0;height:10px;border-radius:8px;">
                                        <div class="is-progress-bar" style="width:{{ $progressPct }}%;border-radius:8px;{{ $soldeGlobal <= 0 ? 'background:linear-gradient(90deg,#10b981,#059669);' : '' }}"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @endif {{-- canViewFinancials: Résumé Financier --}}

                    <!-- Parents / Tuteurs -->
                    <div class="is-card">
                        <div class="is-card-body">
                            <div class="is-section-header">
                                <div class="is-section-icon"><i class="fas fa-users"></i></div>
                                <div class="is-section-title">Parents / Tuteurs</div>
                            </div>
                            @if($inscription->etudiant->parents->count() > 0)
                                <div class="accordion" id="accordionParents">
                                    @foreach($inscription->etudiant->parents as $index => $parent)
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading{{ $index }}">
                                                <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $index }}" aria-expanded="{{ $index == 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $index }}">
                                                    {{ $parent->nom }} {{ $parent->prenoms }} - {{ $parent->pivot->relation }}
                                                    @if($parent->pivot->is_tuteur)
                                                        <span class="badge bg-warning ms-2">Tuteur principal</span>
                                                    @endif
                                                </button>
                                            </h2>
                                            <div id="collapse{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" aria-labelledby="heading{{ $index }}" data-bs-parent="#accordionParents">
                                                <div class="accordion-body" style="padding:16px;">
                                                    <div class="is-info-grid" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                                                        <div class="is-info-row">
                                                            <span class="is-info-lbl">Nom complet</span>
                                                            <span class="is-info-val">{{ $parent->nom }} {{ $parent->prenoms }}</span>
                                                        </div>
                                                        <div class="is-info-row">
                                                            <span class="is-info-lbl">Relation</span>
                                                            <span class="is-info-val">{{ $parent->pivot->relation }}</span>
                                                        </div>
                                                        <div class="is-info-row">
                                                            <span class="is-info-lbl">Téléphone</span>
                                                            <span class="is-info-val {{ !$parent->telephone ? 'muted' : '' }}">{{ $parent->telephone ?: 'Non renseigné' }}</span>
                                                        </div>
                                                        <div class="is-info-row">
                                                            <span class="is-info-lbl">Email</span>
                                                            <span class="is-info-val {{ !$parent->email ? 'muted' : '' }}" style="word-break:break-all;">{{ $parent->email ?: 'Non renseigné' }}</span>
                                                        </div>
                                                        <div class="is-info-row">
                                                            <span class="is-info-lbl">Profession</span>
                                                            <span class="is-info-val {{ !$parent->profession ? 'muted' : '' }}">{{ $parent->profession ?: 'Non renseignée' }}</span>
                                                        </div>
                                                        <div class="is-info-row">
                                                            <span class="is-info-lbl">Adresse</span>
                                                            <span class="is-info-val {{ !$parent->adresse ? 'muted' : '' }}">{{ $parent->adresse ?: 'Non renseignée' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="is-empty-state">
                                    <div class="is-empty-icon"><i class="fas fa-users"></i></div>
                                    <div class="is-empty-text">Aucun parent ou tuteur associé</div>
                                    <div class="is-empty-sub">Les informations de contact parental n'ont pas encore été renseignées pour cet étudiant.</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($canViewFinancials ?? true)
                    <!-- Situation financière détaillée -->
                    <div class="is-card">
                        <div class="is-card-body">
                            <div class="is-section-header">
                                <div class="is-section-icon"><i class="fas fa-chart-line"></i></div>
                                <div class="is-section-title">Situation Financière Détaillée</div>
                            </div>

                            <style>
                                /* Breakpoint personnalisé à 1600px pour TOUT le responsive */
                                @media (max-width: 1600px) {
                                    /* Cards financières */
                                    .financial-table-desktop { display: none !important; }
                                    .financial-cards-responsive { display: block !important; }

                                    /* Tables → Listes */
                                    .table-desktop-1600 { display: none !important; }
                                    .list-mobile-1600 { display: block !important; }

                                    /* FORCER TOUTES les colonnes à être empilées (1 colonne pleine largeur) */
                                    .row > div[class*="col-"],
                                    .row > .col-md-6,
                                    .row > .col-lg-6,
                                    .row > .col-xl-6,
                                    .row > .col-1600-6,
                                    div[class*="col-md-6"],
                                    div[class*="col-lg-6"],
                                    div[class*="col-xl-6"] {
                                        flex: 0 0 100% !important;
                                        max-width: 100% !important;
                                        width: 100% !important;
                                    }

                                    /* Augmenter les tailles de police pour meilleure lisibilité */
                                    body {
                                        font-size: 16px !important;
                                    }

                                    .section-title {
                                        font-size: 20px !important;
                                    }

                                    .text-muted,
                                    .small {
                                        font-size: 15px !important;
                                    }

                                    .fw-bold,
                                    strong {
                                        font-size: 16px !important;
                                    }

                                    .badge {
                                        font-size: 14px !important;
                                        padding: 6px 12px !important;
                                    }

                                    .financial-stat-label {
                                        font-size: 14px !important;
                                    }

                                    .financial-stat-value {
                                        font-size: 22px !important;
                                    }

                                    .financial-stat-extra {
                                        font-size: 14px !important;
                                    }

                                    .financial-title h5 {
                                        font-size: 18px !important;
                                    }

                                    .financial-title p {
                                        font-size: 15px !important;
                                    }

                                    table th,
                                    table td {
                                        font-size: 15px !important;
                                    }

                                    .btn {
                                        font-size: 15px !important;
                                        padding: 10px 16px !important;
                                    }

                                    /* Workflow étapes : passer en mode vertical (mobile) */
                                    .workflow-steps {
                                        display: none !important;
                                    }

                                    .workflow-steps-mobile {
                                        display: block !important;
                                    }

                                    /* Ajouter espace entre les cards empilées */
                                    .card-moderne {
                                        margin-bottom: 24px !important;
                                    }
                                }
                                @media (min-width: 1601px) {
                                    /* Tables financières */
                                    .financial-table-desktop { display: block !important; }
                                    .financial-cards-responsive { display: none !important; }

                                    /* Tables autres sections */
                                    .table-desktop-1600 { display: table !important; }
                                    .list-mobile-1600 { display: none !important; }

                                    /* 2 colonnes côte à côte (50% de largeur chacune) */
                                    .col-1600-6 {
                                        flex: 0 0 50% !important;
                                        max-width: 50% !important;
                                    }

                                    /* Workflow étapes : mode horizontal (desktop) */
                                    .workflow-steps {
                                        display: block !important;
                                    }

                                    .workflow-steps-mobile {
                                        display: none !important;
                                    }
                                }

                                /* Style ACASI pour les cards financières */
                                .financial-card-acasi {
                                    background: var(--surface, #ffffff);
                                    border-radius: var(--radius-medium, 12px);
                                    box-shadow: var(--shadow-card, 0 1px 3px rgba(0, 0, 0, 0.1));
                                    transition: all 0.3s ease;
                                    overflow: hidden;
                                    margin-bottom: var(--space-md, 16px);
                                }

                                .financial-card-acasi:hover {
                                    box-shadow: var(--shadow-hover, 0 4px 12px rgba(0, 0, 0, 0.15));
                                    transform: translateY(-2px);
                                }

                                .financial-card-header {
                                    display: flex;
                                    align-items: center;
                                    padding: var(--space-md, 16px);
                                    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                                    border-bottom: 1px solid #e5e7eb;
                                }

                                .financial-icon-wrapper {
                                    width: 48px;
                                    height: 48px;
                                    border-radius: var(--radius-medium, 12px);
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 24px;
                                    flex-shrink: 0;
                                    margin-right: 24px;
                                }

                                .financial-icon-wrapper.status-paid { background: #dcfce7; color: #10b981; }
                                .financial-icon-wrapper.status-partial { background: #fef3c7; color: #f59e0b; }
                                .financial-icon-wrapper.status-unpaid { background: #fee2e2; color: #ef4444; }

                                .financial-title {
                                    flex: 1;
                                }

                                .financial-title h5 {
                                    margin: 0;
                                    font-size: 16px;
                                    font-weight: 700;
                                    color: var(--text-primary, #111827);
                                }

                                .financial-title p {
                                    margin: 4px 0 0 0;
                                    font-size: var(--text-small, 12px);
                                    color: var(--text-secondary, #6b7280);
                                }

                                .financial-stats {
                                    display: grid;
                                    grid-template-columns: repeat(2, 1fr);
                                    gap: var(--space-md, 16px);
                                    padding: var(--space-md, 16px);
                                }

                                .financial-stat-card {
                                    background: #f9fafb;
                                    border-radius: var(--radius-small, 6px);
                                    padding: var(--space-sm, 8px) var(--space-md, 16px);
                                }

                                .financial-stat-label {
                                    font-size: var(--text-small, 12px);
                                    color: var(--text-secondary, #6b7280);
                                    margin-bottom: 4px;
                                    display: flex;
                                    align-items: center;
                                }

                                .financial-stat-label i {
                                    margin-right: 4px;
                                    opacity: 0.7;
                                }

                                .financial-stat-value {
                                    font-size: 18px;
                                    font-weight: 700;
                                    color: var(--text-primary, #111827);
                                }

                                .financial-stat-value.text-success { color: #10b981 !important; }
                                .financial-stat-value.text-danger { color: #ef4444 !important; }
                                .financial-stat-value.text-warning { color: #f59e0b !important; }

                                .financial-stat-extra {
                                    font-size: var(--text-small, 12px);
                                    margin-top: 4px;
                                }

                                .financial-actions {
                                    padding: 0 var(--space-md, 16px) var(--space-md, 16px);
                                    display: flex;
                                    gap: var(--space-sm, 8px);
                                    flex-wrap: wrap;
                                }

                                .financial-actions .btn {
                                    flex: 1 1 auto;
                                    min-width: 120px;
                                }

                                @media (max-width: 576px) {
                                    .financial-stats {
                                        grid-template-columns: 1fr;
                                    }

                                    .financial-actions .btn {
                                        flex: 1 1 100%;
                                    }
                                }
                            </style>

                            <!-- Version Desktop : Table -->
                            <div class="table-responsive financial-table-desktop">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Catégorie</th>
                                            <th>Type</th>
                                            <th>Montant Attendu</th>
                                            <th>Montant Payé</th>
                                            <th>Solde</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($feeCategoriesWithRules as $item)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if($item['category']->icon)
                                                            <i class="{{ $item['category']->icon }} me-2"></i>
                                                        @endif
                                                        <div>
                                                            <strong>{{ $item['category']->name }}</strong>
                                                            @if($item['category']->description)
                                                                <br><small class="text-muted">{{ $item['category']->description }}</small>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if($item['is_mandatory'])
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                                                        </span>
                                                    @else
                                                        <span class="badge bg-info">
                                                            <i class="fas fa-star me-1"></i>Optionnel
                                                        </span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['is_configured'])
                                                        <strong>{{ number_format($item['montant_attendu'], 0, ',', ' ') }} FCFA</strong>
                                                    @else
                                                        <span class="badge bg-warning">Non configuré</span>
                                                        <br><small class="text-muted">Défaut: {{ number_format($item['category']->default_amount, 0, ',', ' ') }} FCFA</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @php
                                                        // Paiements en attente pour cette catégorie (exclure les paiements de reliquats)
                                                        $paiementsEnAttente = $inscription->paiements()
                                                            ->where('frais_category_id', $item['category']->id)
                                                            ->where('status', 'en_attente')
                                                            ->where(function($query) {
                                                                $query->where('type_paiement', '!=', 'reliquat')
                                                                      ->orWhereNull('type_paiement');
                                                            })
                                                            ->get();
                                                        $montantEnAttente = $paiementsEnAttente->sum('montant');
                                                    @endphp

                                                    @if($item['total_paye'] > 0)
                                                        <strong class="text-success">{{ number_format($item['total_paye'], 0, ',', ' ') }} FCFA</strong>
                                                        <br><small class="text-success"><i class="fas fa-check-circle me-1"></i>Validé</small>
                                                    @else
                                                        <span class="text-muted">0 FCFA validé</span>
                                                    @endif

                                                    @if($montantEnAttente > 0)
                                                        <br>
                                                        <span class="text-warning">{{ number_format($montantEnAttente, 0, ',', ' ') }} FCFA</span>
                                                        <br><small class="text-warning">
                                                            <i class="fas fa-hourglass-half me-1"></i>En attente
                                                            @can('paiements.validate')
                                                                - <a href="{{ route('esbtp.paiements.index') }}?search={{ urlencode($inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms) }}" class="text-warning">
                                                                    <i class="fas fa-external-link-alt"></i>Valider
                                                                </a>
                                                            @endcan
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($item['solde'] > 0)
                                                        <strong class="text-danger">{{ number_format($item['solde'], 0, ',', ' ') }} FCFA</strong>
                                                    @elseif($item['solde'] < 0)
                                                        <strong class="text-success">{{ number_format(abs($item['solde']), 0, ',', ' ') }} FCFA</strong>
                                                        <br><small class="text-success">
                                                            <i class="fas fa-arrow-up me-1"></i>Trop-perçu
                                                            @can('paiements.edit')
                                                                <button class="btn btn-sm btn-outline-primary ms-1" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#transferModal" 
                                                                        onclick="prepareTransferModal({{ $inscription->id }}, {{ $item['category']->id }}, {{ abs($item['solde']) }}, {{ json_encode($item['category']->name) }})"
                                                                        title="Transférer vers un autre frais">
                                                                    <i class="fas fa-exchange-alt"></i>
                                                                </button>
                                                            @endcan
                                                        </small>
                                                    @else
                                                        <span class="badge bg-success">Soldé</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @switch($item['status'])
                                                        @case('paid')
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-check me-1"></i>Payé
                                                            </span>
                                                            @break
                                                        @case('partial')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-clock me-1"></i>Partiel
                                                            </span>
                                                            @break
                                                        @case('unpaid')
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-times me-1"></i>Impayé
                                                            </span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary">{{ $item['status'] }}</span>
                                                    @endswitch
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        @if(auth()->user()->can('paiements.create') && $item['is_configured'] && $item['solde'] > 0)
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="preparePaymentModalForCategory({{ $inscription->id }}, {{ $item['category']->id }})" title="Effectuer un paiement">
                                                                <i class="fas fa-credit-card"></i>
                                                            </button>
                                                        @endif
                                                        @if(auth()->user()->can('inscriptions.edit') && $item['subscription'])
                                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editSubscriptionModal" onclick="prepareEditSubscriptionModal({{ $item['subscription']->id }}, {{ json_encode($item['category']->name) }}, {{ $item['subscription']->amount }})" title="Modifier le montant de la souscription">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        @endif
                                                        @if(!$item['is_mandatory'] && $item['subscription'])
                                                            @can('inscriptions.edit')
                                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#unsubscribeModal" onclick="prepareUnsubscribeModal({{ $item['category']->id }}, {{ json_encode($item['category']->name) }}, {{ $item['subscription']->amount }}, {{ $item['total_paye'] }})" title="Désabonner l'étudiant de ce frais optionnel">
                                                                    <i class="fas fa-user-minus"></i>
                                                                </button>
                                                            @endcan
                                                        @endif
                                                        @if(!$item['is_configured'])
                                                            @can('frais.configure')
                                                                <a href="{{ route('esbtp.frais.configure') }}?filiere_id={{ $inscription->filiere_id }}&niveau_id={{ $inscription->niveau_id }}" class="btn btn-sm btn-warning" title="Configurer ce frais">
                                                                    <i class="fas fa-cogs"></i>
                                                                </a>
                                                            @endcan
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="is-empty-state" style="padding:20px 12px;">
                                                        <div class="is-empty-icon" style="width:40px;height:40px;font-size:1rem;border-radius:10px;"><i class="fas fa-receipt"></i></div>
                                                        <div class="is-empty-text" style="font-size:0.82rem;">Aucune catégorie de frais configurée</div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse

                                        {{-- Intégrer les reliquats comme des lignes de paiement --}}
                                        @if($reliquatsEntrants->count() > 0)
                                            @foreach($reliquatsEntrants as $reliquat)
                                                @if($reliquat->solde_restant > 0)
                                                    <tr class="reliquat-row">
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <i class="fas fa-history me-2 text-warning"></i>
                                                                <div>
                                                                    <strong>{{ $reliquat->fraisSubscription->fraisConfiguration->name ?? $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A' }}</strong>
                                                                    <br><small class="text-muted">Reliquat {{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-warning">Reliquat</span>
                                                        </td>
                                                        <td>
                                                            <strong>{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</strong>
                                                        </td>
                                                        <td>
                                                            @php
                                                                // Paiements en attente pour ce reliquat
                                                                $paiementsReliquatEnAttente = \App\Models\ESBTPPaiement::where('type_paiement', 'reliquat')
                                                                    ->where('reliquat_detail_id', $reliquat->id)
                                                                    ->where('status', 'en_attente')
                                                                    ->sum('montant');
                                                            @endphp

                                                            @if($reliquat->montant_regle > 0)
                                                                <span class="text-success">{{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA</span>
                                                                <br><small class="text-success"><i class="fas fa-check-circle me-1"></i>Validé</small>
                                                            @else
                                                                <span class="text-muted">0 FCFA validé</span>
                                                            @endif

                                                            @if($paiementsReliquatEnAttente > 0)
                                                                <br>
                                                                <span class="text-warning">{{ number_format($paiementsReliquatEnAttente, 0, ',', ' ') }} FCFA</span>
                                                                <br><small class="text-warning">
                                                                    <i class="fas fa-hourglass-half me-1"></i>En attente
                                                                    @can('paiements.validate')
                                                                        - <a href="{{ route('esbtp.paiements.index') }}?search={{ $reliquat->id }}" class="text-warning">
                                                                            <i class="fas fa-external-link-alt"></i>Valider
                                                                        </a>
                                                                    @endcan
                                                                </small>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <strong class="text-danger">{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</strong>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-{{ $reliquat->statut == 'actif' ? 'warning' : ($reliquat->statut == 'soldé' ? 'success' : 'info') }}">
                                                                {{ ucfirst($reliquat->statut) }}
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                @can('paiements.create')
                                                                    <button class="btn btn-sm btn-success"
                                                                            data-bs-toggle="modal"
                                                                            data-bs-target="#reliquatPaymentModal"
                                                                            onclick="prepareReliquatPaymentModal({{ $reliquat->id }}, {{ $reliquat->solde_restant }}, {{ json_encode($reliquat->fraisSubscription->fraisConfiguration->name ?? $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A') }})"
                                                                            title="Payer ce reliquat">
                                                                        <i class="fas fa-credit-card"></i>
                                                                    </button>
                                                                @endcan
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endif
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Version Responsive : Cards ACASI Modernes -->
                            <div class="financial-cards-responsive" style="display: none;">
                                @forelse($feeCategoriesWithRules as $item)
                                    <div class="financial-card-acasi">
                                        <!-- Header avec icône et titre -->
                                        <div class="financial-card-header">
                                            <div class="financial-icon-wrapper status-{{ $item['status'] }}">
                                                <i class="{{ $item['category']->icon ?? 'fas fa-file-invoice-dollar' }}"></i>
                                            </div>
                                            <div class="financial-title">
                                                <h5>{{ $item['category']->name }}</h5>
                                                <p>{{ $item['category']->description ?? '' }}</p>
                                            </div>
                                            @if($item['is_mandatory'])
                                                <span class="badge bg-danger" style="height: fit-content;">
                                                    <i class="fas fa-exclamation-circle me-1"></i>Obligatoire
                                                </span>
                                            @else
                                                <span class="badge bg-info" style="height: fit-content;">
                                                    <i class="fas fa-star me-1"></i>Optionnel
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Stats financières -->
                                        <div class="financial-stats">
                                            @php
                                                $paiementsEnAttente = $inscription->paiements()
                                                    ->where('frais_category_id', $item['category']->id)
                                                    ->where('status', 'en_attente')
                                                    ->where(function($query) {
                                                        $query->where('type_paiement', '!=', 'reliquat')
                                                              ->orWhereNull('type_paiement');
                                                    })
                                                    ->get();
                                                $montantEnAttente = $paiementsEnAttente->sum('montant');
                                            @endphp

                                            <!-- Montant Attendu -->
                                            <div class="financial-stat-card">
                                                <div class="financial-stat-label">
                                                    <i class="fas fa-file-invoice"></i>
                                                    Montant Attendu
                                                </div>
                                                @if($item['is_configured'])
                                                    <div class="financial-stat-value">
                                                        {{ number_format($item['montant_attendu'], 0, ',', ' ') }} FCFA
                                                    </div>
                                                @else
                                                    <div class="financial-stat-value text-warning">
                                                        {{ number_format($item['category']->default_amount, 0, ',', ' ') }} FCFA
                                                    </div>
                                                    <div class="financial-stat-extra text-warning">
                                                        <i class="fas fa-exclamation-triangle"></i> Non configuré (défaut)
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Montant Payé -->
                                            <div class="financial-stat-card">
                                                <div class="financial-stat-label">
                                                    <i class="fas fa-check-circle"></i>
                                                    Montant Payé
                                                </div>
                                                @if($item['total_paye'] > 0)
                                                    <div class="financial-stat-value text-success">
                                                        {{ number_format($item['total_paye'], 0, ',', ' ') }} FCFA
                                                    </div>
                                                    <div class="financial-stat-extra text-success">
                                                        <i class="fas fa-check"></i> Validé
                                                    </div>
                                                @else
                                                    <div class="financial-stat-value">
                                                        0 FCFA
                                                    </div>
                                                @endif
                                                @if($montantEnAttente > 0)
                                                    <div class="financial-stat-extra text-warning">
                                                        <i class="fas fa-hourglass-half"></i> {{ number_format($montantEnAttente, 0, ',', ' ') }} FCFA en attente
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Solde -->
                                            <div class="financial-stat-card">
                                                <div class="financial-stat-label">
                                                    <i class="fas fa-balance-scale"></i>
                                                    Solde
                                                </div>
                                                @if($item['solde'] > 0)
                                                    <div class="financial-stat-value text-danger">
                                                        {{ number_format($item['solde'], 0, ',', ' ') }} FCFA
                                                    </div>
                                                    <div class="financial-stat-extra text-danger">
                                                        <i class="fas fa-arrow-down"></i> À payer
                                                    </div>
                                                @elseif($item['solde'] < 0)
                                                    <div class="financial-stat-value text-success">
                                                        {{ number_format(abs($item['solde']), 0, ',', ' ') }} FCFA
                                                    </div>
                                                    <div class="financial-stat-extra text-success">
                                                        <i class="fas fa-arrow-up"></i> Trop-perçu (transférable)
                                                    </div>
                                                @else
                                                    <div class="financial-stat-value text-success">
                                                        0 FCFA
                                                    </div>
                                                    <div class="financial-stat-extra text-success">
                                                        <i class="fas fa-check-double"></i> Soldé
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Statut -->
                                            <div class="financial-stat-card">
                                                <div class="financial-stat-label">
                                                    <i class="fas fa-info-circle"></i>
                                                    Statut
                                                </div>
                                                <div class="financial-stat-value">
                                                    @switch($item['status'])
                                                        @case('paid')
                                                            <span class="badge bg-success" style="font-size: 14px;">
                                                                <i class="fas fa-check me-1"></i>Payé
                                                            </span>
                                                            @break
                                                        @case('partial')
                                                            <span class="badge bg-warning" style="font-size: 14px;">
                                                                <i class="fas fa-clock me-1"></i>Partiel
                                                            </span>
                                                            @break
                                                        @case('unpaid')
                                                            <span class="badge bg-danger" style="font-size: 14px;">
                                                                <i class="fas fa-times me-1"></i>Impayé
                                                            </span>
                                                            @break
                                                        @default
                                                            <span class="badge bg-secondary" style="font-size: 14px;">{{ $item['status'] }}</span>
                                                    @endswitch
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="financial-actions">
                                                @if(auth()->user()->can('paiements.create') && $item['is_configured'] && $item['solde'] > 0)
                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal" onclick="preparePaymentModalForCategory({{ $inscription->id }}, {{ $item['category']->id }})" title="Effectuer un paiement">
                                                        <i class="fas fa-credit-card me-1"></i>Payer
                                                    </button>
                                                @endif
                                                @if(auth()->user()->can('inscriptions.edit') && $item['subscription'])
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editSubscriptionModal" onclick="prepareEditSubscriptionModal({{ $item['subscription']->id }}, {{ json_encode($item['category']->name) }}, {{ $item['subscription']->amount }})" title="Modifier le montant">
                                                        <i class="fas fa-edit me-1"></i>Modifier
                                                    </button>
                                                @endif
                                                @if(!$item['is_mandatory'] && $item['subscription'])
                                                    @can('inscriptions.edit')
                                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#unsubscribeModal" onclick="prepareUnsubscribeModal({{ $item['category']->id }}, {{ json_encode($item['category']->name) }}, {{ $item['subscription']->amount }}, {{ $item['total_paye'] }})" title="Désabonner">
                                                            <i class="fas fa-user-minus me-1"></i>Désabonner
                                                        </button>
                                                    @endcan
                                                @endif
                                                @if($item['solde'] < 0)
                                                    @can('paiements.edit')
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transferModal" onclick="prepareTransferModal({{ $inscription->id }}, {{ $item['category']->id }}, {{ abs($item['solde']) }}, {{ json_encode($item['category']->name) }})" title="Transférer">
                                                            <i class="fas fa-exchange-alt me-1"></i>Transférer
                                                        </button>
                                                    @endcan
                                                @endif
                                                @if(!$item['is_configured'])
                                                    @can('frais.configure')
                                                        <a href="{{ route('esbtp.frais.configure') }}?filiere_id={{ $inscription->filiere_id }}&niveau_id={{ $inscription->niveau_id }}" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-cogs me-1"></i>Configurer
                                                        </a>
                                                    @endcan
                                                @endif
                                            @if($montantEnAttente > 0)
                                                @can('paiements.validate')
                                                    <a href="{{ route('esbtp.paiements.index') }}?search={{ urlencode($inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms) }}" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-external-link-alt me-1"></i>Valider paiement
                                                    </a>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Cards pour les reliquats --}}
                                    @if($loop->last && isset($reliquatsEntrants) && $reliquatsEntrants->count() > 0)
                                        @foreach($reliquatsEntrants as $reliquat)
                                            @if($reliquat->solde_restant > 0)
                                                <div class="financial-card-acasi">
                                                    <!-- Header reliquat -->
                                                    <div class="financial-card-header">
                                                        <div class="financial-icon-wrapper" style="background: #fef3c7; color: #f59e0b;">
                                                            <i class="fas fa-history"></i>
                                                        </div>
                                                        <div class="financial-title">
                                                            <h5>{{ $reliquat->fraisSubscription->fraisConfiguration->name ?? $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A' }}</h5>
                                                            <p>Reliquat {{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</p>
                                                        </div>
                                                        <span class="badge bg-warning" style="height: fit-content;">
                                                            <i class="fas fa-history me-1"></i>Reliquat
                                                        </span>
                                                    </div>

                                                    <!-- Stats reliquat -->
                                                    <div class="financial-stats">
                                                        @php
                                                            $paiementsReliquatEnAttente = \App\Models\ESBTPPaiement::where('type_paiement', 'reliquat')
                                                                ->where('reliquat_detail_id', $reliquat->id)
                                                                ->where('status', 'en_attente')
                                                                ->sum('montant');
                                                        @endphp

                                                        <!-- Montant Total -->
                                                        <div class="financial-stat-card">
                                                            <div class="financial-stat-label">
                                                                <i class="fas fa-file-invoice"></i>
                                                                Montant Total
                                                            </div>
                                                            <div class="financial-stat-value">
                                                                {{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA
                                                            </div>
                                                        </div>

                                                        <!-- Montant Payé -->
                                                        <div class="financial-stat-card">
                                                            <div class="financial-stat-label">
                                                                <i class="fas fa-check-circle"></i>
                                                                Montant Payé
                                                            </div>
                                                            @if($reliquat->montant_regle > 0)
                                                                <div class="financial-stat-value text-success">
                                                                    {{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA
                                                                </div>
                                                            @else
                                                                <div class="financial-stat-value">
                                                                    0 FCFA
                                                                </div>
                                                            @endif
                                                            @if($paiementsReliquatEnAttente > 0)
                                                                <div class="financial-stat-extra text-warning">
                                                                    <i class="fas fa-hourglass-half"></i> {{ number_format($paiementsReliquatEnAttente, 0, ',', ' ') }} FCFA en attente
                                                                </div>
                                                            @endif
                                                        </div>

                                                        <!-- Solde Restant -->
                                                        <div class="financial-stat-card">
                                                            <div class="financial-stat-label">
                                                                <i class="fas fa-balance-scale"></i>
                                                                Solde Restant
                                                            </div>
                                                            <div class="financial-stat-value text-danger">
                                                                {{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA
                                                            </div>
                                                            <div class="financial-stat-extra text-danger">
                                                                <i class="fas fa-arrow-down"></i> À payer
                                                            </div>
                                                        </div>

                                                        <!-- Statut -->
                                                        <div class="financial-stat-card">
                                                            <div class="financial-stat-label">
                                                                <i class="fas fa-info-circle"></i>
                                                                Statut
                                                            </div>
                                                            <div class="financial-stat-value">
                                                                <span class="badge bg-{{ $reliquat->statut == 'actif' ? 'warning' : ($reliquat->statut == 'soldé' ? 'success' : 'info') }}" style="font-size: 14px;">
                                                                    {{ ucfirst($reliquat->statut) }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Actions reliquat -->
                                                    <div class="financial-actions">
                                                        @can('paiements.create')
                                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#reliquatPaymentModal" onclick="prepareReliquatPaymentModal({{ $reliquat->id }}, {{ $reliquat->solde_restant }}, {{ json_encode($reliquat->fraisSubscription->fraisConfiguration->name ?? $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A') }})" title="Payer ce reliquat">
                                                                <i class="fas fa-credit-card me-1"></i>Payer reliquat
                                                            </button>
                                                        @endcan
                                                        @if($paiementsReliquatEnAttente > 0)
                                                            @can('paiements.validate')
                                                                <a href="{{ route('esbtp.paiements.index') }}?search={{ urlencode($inscription->etudiant->nom . ' ' . $inscription->etudiant->prenoms) }}" class="btn btn-sm btn-outline-warning">
                                                                    <i class="fas fa-external-link-alt me-1"></i>Valider paiement
                                                                </a>
                                                            @endcan
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @endif
                                @empty
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Aucune catégorie de frais configurée pour cette inscription.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    @endif {{-- canViewFinancials: Situation Financière Détaillée --}}

                    <!-- Frais optionnels disponibles -->
                    @if(($canViewFinancials ?? true) && isset($availableOptionalCategories) && $availableOptionalCategories->count() > 0)
                    <div class="is-card">
                        <div class="is-card-body">
                            <div class="is-section-header">
                                <div class="is-section-icon"><i class="fas fa-plus-circle"></i></div>
                                <div class="is-section-title">Frais Optionnels Disponibles</div>
                                @can('inscriptions.edit')
                                <button type="button" class="btn btn-sm btn-primary ms-auto" style="border-radius:8px;font-size:0.8rem;" data-bs-toggle="modal" data-bs-target="#subscriptionModal">
                                    <i class="fas fa-plus me-1"></i>Souscrire
                                </button>
                                @endcan
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-3 px-3 py-2 rounded-3" style="background: rgba(6,182,212,0.07); border: 1px solid rgba(6,182,212,0.2);">
                                <i class="fas fa-info-circle text-info" style="font-size:1.1rem;"></i>
                                <span class="small"><strong>{{ $availableOptionalCategories->count() }} frais optionnel{{ $availableOptionalCategories->count() > 1 ? 's' : '' }}</strong> disponible{{ $availableOptionalCategories->count() > 1 ? 's' : '' }} pour cette filière/niveau. L'administration peut souscrire l'étudiant aux services souhaités.</span>
                            </div>
                            <div class="row g-3">
                                @foreach($availableOptionalCategories as $category)
                                    <div class="col-md-6">
                                        <div class="optional-fee-card" style="flex-direction:column;align-items:stretch;gap:0.75rem;">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="optional-fee-icon" style="flex-shrink:0;">
                                                    <i class="{{ $category->icon ?? 'fas fa-concierge-bell' }}"></i>
                                                </div>
                                                <div class="optional-fee-body" style="flex:1;min-width:0;">
                                                    <div class="optional-fee-name">{{ $category->name }}</div>
                                                    @if($category->description)
                                                        <div class="optional-fee-desc" title="{{ $category->description }}">{{ $category->description }}</div>
                                                    @endif
                                                    @if($category->options->count() > 0)
                                                        <div class="mt-1">
                                                            @foreach($category->options->take(3) as $opt)
                                                                <span class="badge bg-light text-dark border me-1" style="font-size:10px;">{{ $opt->name }}</span>
                                                            @endforeach
                                                            @if($category->options->count() > 3)
                                                                <span class="text-muted" style="font-size:10px;">+{{ $category->options->count() - 3 }} options</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="optional-fee-price" style="flex-shrink:0;text-align:right;">
                                                    <span class="badge-optionnel">Optionnel</span>
                                                    <strong>{{ number_format($category->default_amount, 0, ',', ' ') }}<small> FCFA</small></strong>
                                                </div>
                                            </div>
                                            @can('inscriptions.edit')
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary w-100"
                                                    onclick="openSubscribeModal({{ $category->id }}, '{{ addslashes($category->name) }}', {{ $category->default_amount }}, {{ $category->options->map(fn($o) => ['id' => $o->id, 'name' => $o->name, 'additional_amount' => (float)$o->additional_amount, 'description' => $o->description]) ->values()->toJson() }})">
                                                <i class="fas fa-plus-circle me-1"></i>Souscrire l'étudiant
                                            </button>
                                            @endcan
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    </div>
                    @endif

                    @if($canViewFinancials ?? true)
                    <!-- Paiements liés à l'inscription -->
                    <div class="is-card">
                        <div class="is-card-body">
                            <div class="is-section-header" style="flex-wrap:wrap;">
                                <div class="is-section-icon"><i class="fas fa-money-bill-wave"></i></div>
                                <div class="is-section-title">Paiements liés à cette inscription</div>
                                <div class="ms-auto">
                                <div class="dropdown pdf-dropdown">
                                    <button class="btn btn-outline-success dropdown-toggle" type="button"
                                            id="situationFinanciereDropdown" data-bs-toggle="dropdown"
                                            aria-expanded="false" title="Situation Financière">
                                        <i class="fas fa-file-invoice-dollar"></i> Situation Financière
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="situationFinanciereDropdown">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('esbtp.inscriptions.situation-financiere.preview', $inscription) }}">
                                                <i class="fas fa-window-restore me-1"></i>Vue web
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('esbtp.inscriptions.situation-financiere.pdf-preview', $inscription) }}" target="_blank">
                                                <i class="fas fa-eye me-1"></i>Aperçu PDF
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('esbtp.inscriptions.situation-financiere.pdf', $inscription) }}">
                                                <i class="fas fa-download me-1"></i>Télécharger PDF
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                </div>
                            </div>
                            @php
                                $allPayments = collect();
                                if($inscription->paiements && $inscription->paiements->count()) {
                                    $allPayments = $allPayments->merge($inscription->paiements);
                                }
                                if($inscription->payments && $inscription->payments->count()) {
                                    $allPayments = $allPayments->merge($inscription->payments);
                                }

                                // Séparer les paiements par statut
                                $validatedPayments = $allPayments->filter(function($payment) {
                                    if (isset($payment->status)) {
                                        return in_array($payment->status, ['validated', 'validé']);
                                    }
                                    // Pour les anciens paiements sans status, vérifier le statut explicite
                                    return !isset($payment->status) || $payment->status === 'validé';
                                });

                                $rejectedPayments = $allPayments->filter(function($payment) {
                                    if (isset($payment->status)) {
                                        return in_array($payment->status, ['rejected', 'rejeté']);
                                    }
                                    return false;
                                });

                                $pendingPayments = $allPayments->filter(function($payment) {
                                    if (isset($payment->status)) {
                                        return in_array($payment->status, ['pending', 'en_attente']);
                                    }
                                    return false;
                                });
                            @endphp

                            @if($allPayments->count() > 0)
                                <!-- Paiements Validés -->
                                @if($validatedPayments->count() > 0)
                                    <div class="mb-4">
                                        <h6 class="text-success mb-3">
                                            <i class="fas fa-check-circle me-2"></i>Paiements Validés ({{ $validatedPayments->count() }})
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Montant</th>
                                                        <th>Mode</th>
                                                        <th>Référence</th>
                                                        <th>Statut</th>
                                                        <th>Commentaire</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($validatedPayments as $payment)
                                                        <tr>
                                                            <td>
                                                                @if(isset($payment->date_paiement))
                                                                    {{ \Carbon\Carbon::parse($payment->date_paiement)->format('d/m/Y') }}
                                                                @elseif(isset($payment->payment_date))
                                                                    {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '' }}
                                                                @else
                                                                    {{ $payment->date ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <strong>
                                                                    @if(isset($payment->montant))
                                                                        {{ number_format($payment->montant, 0, ',', ' ') }} FCFA
                                                                    @elseif(isset($payment->amount))
                                                                        {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
                                                                    @endif
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->mode_paiement))
                                                                    {{ ucfirst($payment->mode_paiement) }}
                                                                @elseif(isset($payment->payment_method))
                                                                    {{ ucfirst($payment->payment_method) }}
                                                                @else
                                                                    {{ $payment->methode ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->reference_paiement))
                                                                    {{ $payment->reference_paiement ?? '-' }}
                                                                @elseif(isset($payment->reference_number))
                                                                    {{ $payment->reference_number ?? '-' }}
                                                                @else
                                                                    {{ $payment->reference ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-success">
                                                                    @if(isset($payment->status))
                                                                        {{ $payment->status === 'validated' ? 'Validé' : ucfirst($payment->status) }}
                                                                    @else
                                                                        Validé
                                                                    @endif
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->observations))
                                                                    {{ $payment->observations }}
                                                                @else
                                                                    {{ $payment->commentaire ?? '-' }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-success">
                                                        <th>Total Validé</th>
                                                        <th>
                                                            @php
                                                                $totalValidated = 0;
                                                                foreach($validatedPayments as $payment) {
                                                                    $totalValidated += $payment->montant ?? $payment->amount ?? 0;
                                                                }
                                                            @endphp
                                                            <strong>{{ number_format($totalValidated, 0, ',', ' ') }} FCFA</strong>
                                                        </th>
                                                        <th colspan="4"></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                <!-- Paiements en Attente -->
                                @if($pendingPayments->count() > 0)
                                    <div class="mb-4">
                                        <h6 class="text-warning mb-3">
                                            <i class="fas fa-hourglass-half me-2"></i>Paiements en Attente ({{ $pendingPayments->count() }})
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Montant</th>
                                                        <th>Mode</th>
                                                        <th>Référence</th>
                                                        <th>Statut</th>
                                                        <th>Commentaire</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($pendingPayments as $payment)
                                                        <tr>
                                                            <td>
                                                                @if(isset($payment->date_paiement))
                                                                    {{ \Carbon\Carbon::parse($payment->date_paiement)->format('d/m/Y') }}
                                                                @elseif(isset($payment->payment_date))
                                                                    {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '' }}
                                                                @else
                                                                    {{ $payment->date ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <strong>
                                                                    @if(isset($payment->montant))
                                                                        {{ number_format($payment->montant, 0, ',', ' ') }} FCFA
                                                                    @elseif(isset($payment->amount))
                                                                        {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
                                                                    @endif
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->mode_paiement))
                                                                    {{ ucfirst($payment->mode_paiement) }}
                                                                @elseif(isset($payment->payment_method))
                                                                    {{ ucfirst($payment->payment_method) }}
                                                                @else
                                                                    {{ $payment->methode ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->reference_paiement))
                                                                    {{ $payment->reference_paiement ?? '-' }}
                                                                @elseif(isset($payment->reference_number))
                                                                    {{ $payment->reference_number ?? '-' }}
                                                                @else
                                                                    {{ $payment->reference ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-warning">
                                                                    {{ $payment->status === 'pending' ? 'En attente' : ucfirst($payment->status) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->observations))
                                                                    {{ $payment->observations }}
                                                                @else
                                                                    {{ $payment->commentaire ?? '-' }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                <!-- Paiements Rejetés -->
                                @if($rejectedPayments->count() > 0)
                                    <div class="mb-4">
                                        <h6 class="text-danger mb-3">
                                            <i class="fas fa-times-circle me-2"></i>Paiements Rejetés ({{ $rejectedPayments->count() }})
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Montant</th>
                                                        <th>Mode</th>
                                                        <th>Référence</th>
                                                        <th>Statut</th>
                                                        <th>Commentaire</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($rejectedPayments as $payment)
                                                        <tr>
                                                            <td>
                                                                @if(isset($payment->date_paiement))
                                                                    {{ \Carbon\Carbon::parse($payment->date_paiement)->format('d/m/Y') }}
                                                                @elseif(isset($payment->payment_date))
                                                                    {{ $payment->payment_date ? $payment->payment_date->format('d/m/Y') : '' }}
                                                                @else
                                                                    {{ $payment->date ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <strong class="text-muted">
                                                                    @if(isset($payment->montant))
                                                                        {{ number_format($payment->montant, 0, ',', ' ') }} FCFA
                                                                    @elseif(isset($payment->amount))
                                                                        {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
                                                                    @endif
                                                                </strong>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->mode_paiement))
                                                                    {{ ucfirst($payment->mode_paiement) }}
                                                                @elseif(isset($payment->payment_method))
                                                                    {{ ucfirst($payment->payment_method) }}
                                                                @else
                                                                    {{ $payment->methode ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->reference_paiement))
                                                                    {{ $payment->reference_paiement ?? '-' }}
                                                                @elseif(isset($payment->reference_number))
                                                                    {{ $payment->reference_number ?? '-' }}
                                                                @else
                                                                    {{ $payment->reference ?? '-' }}
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-danger">
                                                                    {{ $payment->status === 'rejected' ? 'Rejeté' : ucfirst($payment->status) }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if(isset($payment->observations))
                                                                    {{ $payment->observations }}
                                                                @else
                                                                    {{ $payment->commentaire ?? '-' }}
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="is-empty-state">
                                    <div class="is-empty-icon"><i class="fas fa-money-bill-wave"></i></div>
                                    <div class="is-empty-text">Aucun paiement enregistré</div>
                                    <div class="is-empty-sub">Les paiements apparaîtront ici une fois effectués.</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @endif {{-- canViewFinancials: Paiements liés --}}

                    {{-- Section Reliquats --}}
                    @if(($canViewFinancials ?? true) && (isset($reliquatsEntrants) && $reliquatsEntrants->count() > 0 || isset($reliquatsSortants) && $reliquatsSortants->count() > 0))
                        <div class="is-card" style="margin-top:16px;">
                            <div class="is-card-body">
                                <div class="is-section-header">
                                    <div class="is-section-icon"><i class="fas fa-exchange-alt"></i></div>
                                    <div class="is-section-title">Reliquats liés à cette inscription</div>
                                </div>

                                @if(isset($reliquatsEntrants) && $reliquatsEntrants->count() > 0)
                                    <div class="alert alert-warning mb-3">
                                        <h6><i class="fas fa-arrow-right me-2"></i>Reliquats entrants (provenant d'inscriptions précédentes)</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Année source</th>
                                                        <th>Frais</th>
                                                        <th>Montant reliquat</th>
                                                        <th>Montant réglé</th>
                                                        <th>Solde restant</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($reliquatsEntrants as $reliquat)
                                                        <tr>
                                                            <td>{{ $reliquat->inscriptionSource->anneeUniversitaire->name ?? 'N/A' }}</td>
                                                            <td>{{ $reliquat->fraisSubscription->fraisConfiguration->name ?? $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A' }}</td>
                                                            <td>{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</td>
                                                            <td>{{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA</td>
                                                            <td><strong>{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</strong></td>
                                                            <td>
                                                                <span class="badge bg-{{ $reliquat->statut == 'actif' ? 'warning' : ($reliquat->statut == 'soldé' ? 'success' : 'info') }}">
                                                                    {{ ucfirst($reliquat->statut) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-warning">
                                                        <th colspan="4">Total reliquats entrants</th>
                                                        <th>{{ number_format($statistiquesReliquats['total_reliquats_entrants'] ?? 0, 0, ',', ' ') }} FCFA</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @endif

                                @if(isset($reliquatsSortants) && $reliquatsSortants->count() > 0)
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-arrow-left me-2"></i>Reliquats sortants (transférés vers des inscriptions futures)</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Année destination</th>
                                                        <th>Frais</th>
                                                        <th>Montant reliquat</th>
                                                        <th>Montant réglé</th>
                                                        <th>Solde restant</th>
                                                        <th>Statut</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($reliquatsSortants as $reliquat)
                                                        <tr>
                                                            <td>{{ $reliquat->inscriptionDestination->anneeUniversitaire->name ?? 'N/A' }}</td>
                                                            <td>{{ $reliquat->fraisSubscription->fraisConfiguration->name ?? $reliquat->fraisSubscription->fraisCategory->name ?? 'N/A' }}</td>
                                                            <td>{{ number_format($reliquat->montant_reliquat, 0, ',', ' ') }} FCFA</td>
                                                            <td>{{ number_format($reliquat->montant_regle, 0, ',', ' ') }} FCFA</td>
                                                            <td><strong>{{ number_format($reliquat->solde_restant, 0, ',', ' ') }} FCFA</strong></td>
                                                            <td>
                                                                <span class="badge bg-{{ $reliquat->statut == 'actif' ? 'warning' : ($reliquat->statut == 'soldé' ? 'success' : 'info') }}">
                                                                    {{ ucfirst($reliquat->statut) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="table-info">
                                                        <th colspan="4">Total reliquats sortants</th>
                                                        <th>{{ number_format($statistiquesReliquats['total_reliquats_sortants'] ?? 0, 0, ',', ' ') }} FCFA</th>
                                                        <th></th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour associer un paiement - Design moderne -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="paymentModalLabel">
                    <i class="fas fa-money-bill-wave me-2"></i>Associer un paiement à l'inscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                <input type="hidden" name="_action" value="valider-avec-paiement">
                <div class="modal-body" style="padding: 2rem;">
                    <!-- Alert moderne -->
                    <div style="
                        background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%);
                        border-left: 4px solid #0d6efd;
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #0d6efd, #0a58ca);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: #084298; font-weight: 500; margin-bottom: 0.25rem;">Information importante</div>
                                <div style="color: #052c65; font-size: 0.9rem;">
                                    Cette action associera un paiement à l'inscription et la fera passer en validation.
                                    Vous pourrez encore modifier la <strong>filière</strong>, le <strong>niveau</strong> et la <strong>classe</strong> jusqu'à la validation définitive.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 1: Montant et Catégorie -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="montant" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-coins me-1" style="color: #0d6efd;"></i>
                                Montant payé <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 2px solid #dee2e6; border-right: none;">
                                    <i class="fas fa-dollar-sign" style="color: #0d6efd;"></i>
                                </span>
                                <input type="number" class="form-control" id="montant" name="montant" min="0" step="0.01" required
                                       style="border: 2px solid #dee2e6; border-left: none; border-right: none; font-weight: 600;">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 2px solid #dee2e6; border-left: none; font-weight: 600;">
                                    FCFA
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="fee_category_id" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-tags me-1" style="color: #0d6efd;"></i>
                                Catégorie de frais <span class="text-danger">*</span>
                            </label>
                            <div style="position: relative;">
                                <i class="fas fa-folder-open" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #0d6efd; z-index: 10;"></i>
                                <select class="form-select" id="fee_category_id" name="fee_category_id" required
                                        style="padding-left: 2.75rem; border: 2px solid #dee2e6; border-radius: 8px; font-weight: 500;">
                                    <option value="">Sélectionnez une catégorie</option>
                                    @if(isset($categoriesfrais))
                                        @foreach($categoriesfrais as $categorie)
                                            <option value="{{ $categorie->id }}" data-default-amount="{{ $categorie->default_amount }}">
                                                {{ $categorie->name }}
                                                @if($categorie->is_mandatory)
                                                    (Obligatoire)
                                                @else
                                                    (Optionnel)
                                                @endif
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 2: Mode paiement et Référence -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="mode_paiement" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-credit-card me-1" style="color: #0d6efd;"></i>
                                Mode de paiement <span class="text-danger">*</span>
                            </label>
                            <div style="position: relative;">
                                <i class="fas fa-wallet" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #0d6efd; z-index: 10;"></i>
                                <select class="form-select" id="mode_paiement" name="mode_paiement" required
                                        style="padding-left: 2.75rem; border: 2px solid #dee2e6; border-radius: 8px; font-weight: 500;">
                                    <option value="">Sélectionnez un mode</option>
                                    <option value="especes">Espèces</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="reference_paiement" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-hashtag me-1" style="color: #6c757d;"></i>
                                Référence du paiement
                            </label>
                            <div style="position: relative;">
                                <i class="fas fa-barcode" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #6c757d; z-index: 10;"></i>
                                <input type="text" class="form-control" id="reference_paiement" name="reference_paiement"
                                       placeholder="Numéro de chèque, référence virement..."
                                       style="padding-left: 2.75rem; border: 2px solid #dee2e6; border-radius: 8px;">
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 3: Date et Observations -->
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="date_paiement" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-calendar-alt me-1" style="color: #0d6efd;"></i>
                                Date du paiement <span class="text-danger">*</span>
                            </label>
                            <div style="position: relative;">
                                <i class="fas fa-calendar-day" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #0d6efd; z-index: 10;"></i>
                                <input type="date" class="form-control" id="date_paiement" name="date_paiement" value="{{ date('Y-m-d') }}" required
                                       style="padding-left: 2.75rem; border: 2px solid #dee2e6; border-radius: 8px; font-weight: 500;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="observations" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-comment-dots me-1" style="color: #6c757d;"></i>
                                Observations
                            </label>
                            <textarea class="form-control" id="observations" name="observations" rows="3"
                                      placeholder="Commentaires sur le paiement..."
                                      style="border: 2px solid #dee2e6; border-radius: 8px; resize: none;"></textarea>
                        </div>
                    </div>

                    {{-- Checkbox : Valider directement le paiement (réservé aux users avec self_override : créateur = validateur) --}}
                    @can('paiements.validate.self_override')
                    <div style="margin-top: 1.5rem; border: 2px solid #e2e8f0; border-radius: 10px; padding: 1rem; background: #f8fafc;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="validate_payment" id="payment_validate_immediately" value="1"
                                   style="width:18px; height:18px; margin-top:.15rem;">
                            <label class="form-check-label fw-semibold" for="payment_validate_immediately" style="margin-left:.4rem; color:#2d3748;">
                                <i class="fas fa-bolt me-1" style="color:#0d6efd;"></i>
                                Valider directement le paiement
                            </label>
                            <div class="text-muted" style="margin-left:1.75rem; font-size:.8rem; margin-top:.2rem;">
                                Si coché, le paiement sera marqué comme validé dès sa création (sans attente de validation ultérieure).
                            </div>
                        </div>
                    </div>
                    @endcan
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 15px 15px; padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        border: 2px solid #6c757d;
                    ">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        background: linear-gradient(135deg, #0d6efd, #0a58ca);
                        border: none;
                        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
                    ">
                        <i class="fas fa-check-circle me-2"></i>Associer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour validation définitive - Structure Bootstrap simple -->
<div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="validationModalLabel">
                    <i class="fas fa-check-circle me-2"></i>Validation définitive de l'inscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="validationForm" method="POST">
                @csrf
                <input type="hidden" name="_action" value="valider-definitivement">
                <div class="modal-body" style="padding: 2rem;">
                    <!-- Alert info moderne -->
                    <div style="
                        background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
                        border-left: 4px solid #3b82f6;
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #3b82f6, #2563eb);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div style="flex-grow: 1; padding-top: 0.5rem;">
                                <div style="color: #1e40af; font-size: 0.9rem;">
                                    Cette action va convertir le prospect en étudiant et activer son compte utilisateur.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alert warning moderne -->
                    <div style="
                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                        border-left: 4px solid #f59e0b;
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #f59e0b, #d97706);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: #92400e; font-weight: 600; margin-bottom: 0.5rem;">Important : Éléments qui ne pourront plus être modifiés</div>
                                <div style="color: #78350f; font-size: 0.9rem; margin-bottom: 0.75rem;">
                                    Une fois l'inscription validée définitivement (statut 'active'), les éléments suivants ne pourront plus être modifiés :
                                </div>
                                <ul style="color: #78350f; font-size: 0.9rem; margin-bottom: 0.75rem;">
                                    <li><strong>Filière</strong> : {{ $inscription->filiere->name ?? 'Non définie' }}</li>
                                    <li><strong>Niveau d'études</strong> : {{ $inscription->niveau->name ?? 'Non défini' }}</li>
                                    <li><strong>Classe</strong> : {{ $inscription->classe?->name ?? 'Non définie' }}</li>
                                </ul>
                                <small style="color: #92400e;">Assurez-vous que ces informations sont correctes avant de procéder à la validation.</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="validation_observations" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                            <i class="fas fa-comment-dots me-1" style="color: #10b981;"></i>
                            Observations
                        </label>
                        <textarea class="form-control" id="validation_observations" name="observations" rows="3"
                                  placeholder="Commentaires sur la validation..."
                                  style="border: 2px solid #dee2e6; border-radius: 8px; resize: none;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 15px 15px; padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 500;
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-success" style="
                        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                        border: none;
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-check-double me-1"></i>Valider définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour paiement de reliquat -->
<div class="modal fade" id="reliquatPaymentModal" tabindex="-1" aria-labelledby="reliquatPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #198754 0%, #157347 100%); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="reliquatPaymentModalLabel">
                    <i class="fas fa-history me-2"></i>Paiement de Reliquat
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reliquatPaymentForm" method="POST" action="{{ route('esbtp.reliquats.pay') }}">
                @csrf
                <input type="hidden" id="reliquat_id" name="reliquat_id">
                <div class="modal-body" style="padding: 2rem;">
                    <!-- Alert moderne -->
                    <div style="
                        background: linear-gradient(135deg, #d1f4e0 0%, #e8f8f0 100%);
                        border-left: 4px solid #198754;
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #198754, #157347);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: #0f5132; font-weight: 500; margin-bottom: 0.25rem;">Paiement d'arrières</div>
                                <div style="color: #0a3622; font-size: 0.9rem;">
                                    Vous êtes sur le point de payer un reliquat de l'année précédente.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carte détail du reliquat -->
                    <div style="
                        background: linear-gradient(135deg, #fff8dc 0%, #fffaed 100%);
                        border: 2px solid #ffc107;
                        border-radius: 12px;
                        padding: 1.5rem;
                        margin-bottom: 1.5rem;
                        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.2);
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 50px;
                                height: 50px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #ffc107, #ffb300);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                font-size: 1.25rem;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <h6 style="color: #664d03; font-weight: 700; margin-bottom: 0.75rem; font-size: 1rem;">
                                    Détail du Reliquat
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-2">
                                        <div style="font-size: 0.85rem; color: #997404; margin-bottom: 0.25rem;">Frais concerné</div>
                                        <div style="font-weight: 600; color: #664d03;" id="reliquat_frais_name">-</div>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <div style="font-size: 0.85rem; color: #997404; margin-bottom: 0.25rem;">Montant à payer</div>
                                        <div style="
                                            background: rgba(220, 53, 69, 0.1);
                                            border: 2px solid #dc3545;
                                            border-radius: 8px;
                                            padding: 0.5rem 0.75rem;
                                            display: inline-block;
                                        ">
                                            <span id="reliquat_amount" style="font-weight: 700; color: #dc3545; font-size: 1.25rem;">0</span>
                                            <span style="font-weight: 600; color: #dc3545; margin-left: 0.25rem;">FCFA</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="reliquat_montant" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-coins me-1" style="color: #198754;"></i>
                                Montant à payer <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #d1f4e0, #e8f8f0); border: 2px solid #198754; border-right: none;">
                                    <i class="fas fa-dollar-sign" style="color: #198754;"></i>
                                </span>
                                <input type="number" class="form-control" id="reliquat_montant" name="montant" min="1" step="1" required
                                       style="border: 2px solid #198754; border-left: none; border-right: none; font-weight: 600; color: #198754;">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #d1f4e0, #e8f8f0); border: 2px solid #198754; border-left: none; font-weight: 600; color: #198754;">
                                    FCFA
                                </span>
                            </div>
                            <div class="form-text" style="font-size: 0.8rem; color: #6c757d;">
                                <i class="fas fa-info-circle me-1"></i>Montant minimum: 1 FCFA
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="reliquat_mode_paiement" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-credit-card me-1" style="color: #198754;"></i>
                                Mode de paiement <span class="text-danger">*</span>
                            </label>
                            <div style="position: relative;">
                                <i class="fas fa-wallet" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #198754; z-index: 10;"></i>
                                <select class="form-select" id="reliquat_mode_paiement" name="mode_paiement" required
                                        style="padding-left: 2.75rem; border: 2px solid #198754; border-radius: 8px; font-weight: 500; color: #198754;">
                                    <option value="">Sélectionnez un mode</option>
                                    <option value="especes">Espèces</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement bancaire</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <label for="reliquat_notes" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-comment-dots me-1" style="color: #6c757d;"></i>
                                Notes (optionnel)
                            </label>
                            <textarea class="form-control" id="reliquat_notes" name="notes" rows="3"
                                      placeholder="Notes complémentaires sur ce paiement..."
                                      style="border: 2px solid #dee2e6; border-radius: 8px; resize: none;"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 15px 15px; padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        border: 2px solid #6c757d;
                    ">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-success" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        background: linear-gradient(135deg, #198754, #157347);
                        border: none;
                        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3);
                    ">
                        <i class="fas fa-credit-card me-2"></i>Confirmer le paiement
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour souscription à un frais optionnel -->
<div class="modal fade" id="subscriptionModal" tabindex="-1" aria-labelledby="subscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="subscriptionModalLabel">
                    <i class="fas fa-plus-circle me-2"></i>Souscrire à un frais optionnel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="subscriptionForm" method="POST" action="{{ route('esbtp.inscriptions.subscribe-optional-fee', $inscription->id) }}">
                @csrf
                <div class="modal-body" style="padding: 2rem;">
                    <!-- Alert information -->
                    <div style="background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%); border-left: 4px solid #0d6efd; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.5rem;">
                        <div class="d-flex align-items-start gap-3">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #0d6efd, #0a58ca); display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                                <i class="fas fa-info-circle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: #084298; font-weight: 500; margin-bottom: 0.25rem;">Souscription à un frais optionnel</div>
                                <div style="color: #052c65; font-size: 0.9rem;">
                                    Sélectionnez un frais optionnel pour y souscrire cet étudiant. Vous pouvez également effectuer et valider le paiement immédiatement.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Ligne 1 : Frais + Montant -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="optional_category_id" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-tags me-1" style="color: #0d6efd;"></i>
                                Frais optionnel <span class="text-danger">*</span>
                            </label>
                            <div style="position: relative;">
                                <i class="fas fa-folder-open" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #0d6efd; z-index: 10;"></i>
                                <select class="form-select" id="optional_category_id" name="frais_category_id" required
                                        style="padding-left: 2.75rem; border: 2px solid #dee2e6; border-radius: 8px; font-weight: 500;">
                                    <option value="">Sélectionnez un frais</option>
                                    @if(isset($availableOptionalCategories))
                                        @foreach($availableOptionalCategories as $category)
                                            <option value="{{ $category->id }}"
                                                    data-default-amount="{{ $category->default_amount }}"
                                                    data-options="{{ json_encode($category->options->map(fn($o) => ['id' => $o->id, 'name' => $o->name, 'additional_amount' => (float)$o->additional_amount, 'description' => $o->description])->values()) }}">
                                                {{ $category->name }} — {{ number_format($category->default_amount, 0, ',', ' ') }} FCFA
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="subscription_amount" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                                <i class="fas fa-coins me-1" style="color: #0d6efd;"></i>
                                Montant <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 2px solid #dee2e6; border-right: none;">
                                    <i class="fas fa-dollar-sign" style="color: #0d6efd;"></i>
                                </span>
                                <input type="number" class="form-control" id="subscription_amount" name="amount" min="0" step="1" required
                                       style="border: 2px solid #dee2e6; border-left: none; border-right: none; font-weight: 600;">
                                <span class="input-group-text" style="background: linear-gradient(135deg, #f8f9fa, #e9ecef); border: 2px solid #dee2e6; border-left: none; font-weight: 600;">FCFA</span>
                            </div>
                            <small class="text-muted" id="subscription_amount_hint"></small>
                        </div>
                    </div>

                    <!-- Zone options dynamique -->
                    <div id="subscription_options_zone" class="mb-3" style="display:none;">
                        <label class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                            <i class="fas fa-list me-1" style="color: #0d6efd;"></i>
                            Option <span class="text-danger">*</span>
                        </label>
                        <div id="subscription_options_list"></div>
                        <small class="text-muted">Le montant se met à jour automatiquement selon l'option choisie.</small>
                    </div>

                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="subscription_notes" class="form-label fw-semibold" style="color: #2d3748; font-size: 0.9rem;">
                            <i class="fas fa-comment-dots me-1" style="color: #6c757d;"></i>
                            Notes
                        </label>
                        <textarea class="form-control" id="subscription_notes" name="notes" rows="2"
                                  placeholder="Commentaires sur la souscription..."
                                  style="border: 2px solid #dee2e6; border-radius: 8px; resize: none;"></textarea>
                    </div>

                    <!-- Option : effectuer le paiement immédiatement -->
                    <div style="border: 2px solid #e2e8f0; border-radius: 10px; padding: 1rem; background: #f8fafc;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="create_and_pay" id="subscription_create_and_pay" value="1"
                                   style="width:18px; height:18px; margin-top:.15rem;">
                            <label class="form-check-label fw-semibold" for="subscription_create_and_pay" style="margin-left:.4rem; color:#2d3748;">
                                <i class="fas fa-bolt me-1" style="color:#0d6efd;"></i>
                                Effectuer et valider le paiement immédiatement
                            </label>
                            <div class="text-muted" style="margin-left:1.75rem; font-size:.8rem; margin-top:.2rem;">
                                Crée un paiement du montant ci-dessus et le marque comme validé sur cette souscription.
                            </div>
                        </div>
                        <div id="subscription_payment_fields" class="row g-3 mt-2" style="display:none;">
                            <div class="col-md-6">
                                <label for="subscription_mode_paiement" class="form-label fw-semibold" style="color:#2d3748; font-size:.85rem;">
                                    <i class="fas fa-credit-card me-1" style="color:#0d6efd;"></i>Mode de paiement <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="mode_paiement" id="subscription_mode_paiement"
                                        style="border: 2px solid #dee2e6; border-radius: 8px;">
                                    <option value="">Sélectionnez</option>
                                    <option value="especes">Espèces</option>
                                    <option value="cheque">Chèque</option>
                                    <option value="virement">Virement</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="subscription_reference" class="form-label fw-semibold" style="color:#2d3748; font-size:.85rem;">
                                    <i class="fas fa-hashtag me-1" style="color:#6c757d;"></i>Référence du paiement
                                </label>
                                <input type="text" class="form-control" name="reference_paiement" id="subscription_reference"
                                       placeholder="Numéro de chèque, référence virement..."
                                       style="border: 2px solid #dee2e6; border-radius: 8px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 15px 15px; padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                            style="padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 600; border: 2px solid #6c757d;">
                        <i class="fas fa-times me-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary"
                            style="padding: 0.65rem 1.5rem; border-radius: 8px; font-weight: 600; background: linear-gradient(135deg, #0d6efd, #0a58ca); border: none; box-shadow: 0 4px 12px rgba(13,110,253,0.3);">
                        <i class="fas fa-plus me-2"></i>Souscrire l'étudiant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal pour transfert de trop-perçu - Design moderne avec support multi-destinations -->
<div class="modal fade modal-moderne" id="transferModal" tabindex="-1" aria-labelledby="transferModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transferModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>Gestion des Trop-perçus
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="transferForm" method="POST" action="{{ route('esbtp.inscriptions.transfer-overpayment', $inscription->id) }}">
                @csrf
                <input type="hidden" id="transfer_source_category" name="source_category_id">
                <input type="hidden" id="transfer_amount_hidden" name="amount">
                
                <div class="modal-body">
                    <!-- Alerte d'information avec design moderne -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Vous allez transférer un trop-perçu de <strong id="transfer_source_name">-</strong> vers un ou plusieurs frais.
                    </div>
                    
                    <!-- Section Source avec design moderne -->
                    <div class="section-card mb-4">
                        <div class="section-card-header" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05)); border-left: 4px solid var(--success);">
                            <h6 class="section-card-title mb-0">
                                <i class="fas fa-arrow-up me-2 text-success"></i>Source (Trop-perçu)
                            </h6>
                        </div>
                        <div class="section-card-body">
                            <div class="form-grid-2">
                                <div class="form-group-moderne">
                                    <label class="form-label-moderne">Frais source</label>
                                    <div class="form-control-moderne" style="background: var(--background); border: none; padding: 12px 16px;">
                                        <strong id="transfer_source_display">-</strong>
                                    </div>
                                </div>
                                <div class="form-group-moderne">
                                    <label class="form-label-moderne">Montant disponible</label>
                                    <div class="form-control-moderne" style="background: var(--background); border: none; padding: 12px 16px; color: var(--success); font-weight: 600;">
                                        <span id="transfer_amount_display">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Destinations multiples -->
                    <div class="section-card mb-4">
                        <div class="section-card-header" style="background: linear-gradient(135deg, rgba(30, 58, 138, 0.1), rgba(30, 64, 175, 0.05)); border-left: 4px solid var(--primary);">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="section-card-title mb-0">
                                    <i class="fas fa-arrow-down me-2 text-primary"></i>Destinations
                                </h6>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add_destination_btn">
                                    <i class="fas fa-plus me-1"></i>Ajouter une destination
                                </button>
                            </div>
                        </div>
                        <div class="section-card-body">
                            <div id="destinations_container">
                                <!-- Les destinations seront ajoutées ici dynamiquement -->
                            </div>
                            
                            <!-- Résumé des transferts -->
                            <div id="transfer_summary" class="alert alert-secondary d-none mt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Total à transférer :</strong>
                                        <span id="total_transfer_amount" class="text-primary">0 FCFA</span>
                                    </div>
                                    <div>
                                        <strong>Restant disponible :</strong>
                                        <span id="remaining_amount" class="text-success">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Commentaire -->
                    <div class="section-card">
                        <div class="section-card-header">
                            <h6 class="section-card-title mb-0">
                                <i class="fas fa-comment me-2"></i>Commentaire (optionnel)
                            </h6>
                        </div>
                        <div class="section-card-body">
                            <div class="form-group-moderne">
                                <textarea class="form-control-moderne" 
                                          id="transfer_comment" 
                                          name="comment" 
                                          rows="3" 
                                          placeholder="Motif du transfert..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" id="transfer_submit_btn" disabled>
                        <i class="fas fa-exchange-alt me-1"></i>Effectuer le transfert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── Modal : Désabonner d'un frais optionnel ──────────────────────────── -->
<div class="modal fade" id="unsubscribeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
        <div class="modal-content" style="border-radius:16px;border:none;box-shadow:0 20px 50px rgba(0,0,0,0.18);overflow:hidden;">

            {{-- Header --}}
            <div class="modal-header border-0 pb-0" style="padding:24px 24px 16px;">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:44px;height:44px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;color:#dc2626;font-size:20px;flex-shrink:0;">
                        <i class="fas fa-user-minus"></i>
                    </div>
                    <div>
                        <h5 class="mb-0" style="font-size:16px;font-weight:700;color:#1e293b;">Désabonner l'étudiant</h5>
                        <p class="mb-0 mt-1" style="font-size:12px;color:#64748b;" id="unsubscribeModalSubtitle">Frais optionnel</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="{{ route('esbtp.inscriptions.unsubscribe-optional-fee', $inscription->id) }}">
                @csrf
                <input type="hidden" name="frais_category_id" id="unsubscribeCategoryId">

                <div class="modal-body" style="padding:16px 24px 8px;">

                    {{-- Résumé frais --}}
                    <div style="background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:10px;padding:14px 16px;margin-bottom:16px;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Frais concerné</div>
                                <div style="font-size:14px;font-weight:700;color:#1e293b;" id="unsubscribeCategoryName">—</div>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;">Montant souscrit</div>
                                <div style="font-size:16px;font-weight:800;color:#0453cb;" id="unsubscribeMontant">—</div>
                            </div>
                        </div>
                    </div>

                    {{-- Alerte contextuelle (injectée par JS) --}}
                    <div id="unsubscribeAlert"></div>

                    <p style="font-size:13px;color:#475569;line-height:1.5;margin-bottom:0;">
                        Voulez-vous retirer cet étudiant de ce frais optionnel ?
                        L'étudiant n'apparaîtra plus dans la liste des souscripteurs.
                    </p>
                </div>

                <div class="modal-footer border-0" style="padding:16px 24px 24px;gap:10px;">
                    <button type="button" class="btn btn-light fw-600" data-bs-dismiss="modal"
                            style="border-radius:10px;padding:9px 20px;font-weight:600;font-size:13px;">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-danger fw-600"
                            style="border-radius:10px;padding:9px 20px;font-weight:700;font-size:13px;">
                        <i class="fas fa-user-minus me-1"></i>Désabonner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal d'édition de souscription (SuperAdmin) -->
<div class="modal fade" id="editSubscriptionModal" tabindex="-1" aria-labelledby="editSubscriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border-radius: 15px 15px 0 0; padding: 1.5rem; border: none;">
                <h5 class="modal-title fw-bold" id="editSubscriptionModalLabel">
                    <i class="fas fa-edit me-2"></i>Modifier le montant de la souscription
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editSubscriptionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body" style="padding: 2rem;">
                    <!-- Alert moderne -->
                    <div style="
                        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
                        border-left: 4px solid #f59e0b;
                        border-radius: 10px;
                        padding: 1rem 1.25rem;
                        margin-bottom: 1.5rem;
                    ">
                        <div class="d-flex align-items-start gap-3">
                            <div style="
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #f59e0b, #d97706);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                color: white;
                                flex-shrink: 0;
                            ">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div style="flex-grow: 1;">
                                <div style="color: #92400e; font-weight: 500; margin-bottom: 0.25rem;">Attention</div>
                                <div style="color: #78350f; font-size: 0.9rem;">
                                    Cette fonctionnalité est réservée aux super-administrateurs.
                                    La modification du montant de souscription affectera les calculs de paiement pour cet étudiant.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group mb-3">
                                <label for="edit_subscription_category_name" class="form-label fw-bold">Catégorie de frais</label>
                                <input type="text" class="form-control" id="edit_subscription_category_name" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_subscription_current_amount" class="form-label">Montant actuel</label>
                                <input type="text" class="form-control bg-light" id="edit_subscription_current_amount" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="edit_subscription_new_amount" class="form-label fw-bold text-primary">
                                    Nouveau montant <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="edit_subscription_new_amount"
                                       name="amount" min="0" step="1" required>
                                <div class="form-text">Le montant doit être en FCFA (nombre entier)</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="edit_subscription_reason" class="form-label">Motif de la modification <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_subscription_reason" name="reason" rows="3"
                                  placeholder="Indiquez le motif de cette modification (ex: bourse partielle, réduction, correction d'erreur...)" required></textarea>
                    </div>

                    <input type="hidden" id="edit_subscription_id" name="subscription_id">
                </div>
                <div class="modal-footer" style="background: #f8f9fa; border-radius: 0 0 15px 15px; padding: 1.25rem 2rem; border: none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 500;
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-times me-1"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-primary" style="
                        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
                        border: none;
                        padding: 0.65rem 1.5rem;
                        border-radius: 8px;
                        font-weight: 600;
                        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
                        transition: all 0.2s;
                    ">
                        <i class="fas fa-save me-1"></i>Sauvegarder les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal affectation classe (si classe manquante) --}}
@if(!$inscription->classe_id)
    @include('esbtp.inscriptions.partials.modal-affecter-classe')
@endif

@push('scripts')
<script>
    // ========================================
    // AFFECTATION CLASSE RAPIDE - Modal AJAX
    // ========================================
    let selectedClasseId = null;
    let selectedAffectationStatus = 'affecté';

    function selectClasse(el, classeId) {
        document.querySelectorAll('.classe-option-card').forEach(c => c.classList.remove('selected'));
        el.classList.add('selected');
        selectedClasseId = classeId;
        document.getElementById('btn-confirmer-affectation').disabled = false;
    }

    function selectAffectationStatus(el, status) {
        document.querySelectorAll('.affectation-status-chip').forEach(c => c.classList.remove('active'));
        el.classList.add('active');
        selectedAffectationStatus = status;
    }

    function confirmerAffectation() {
        if (!selectedClasseId) return;

        const btn = document.getElementById('btn-confirmer-affectation');
        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Affectation...';

        fetch("{{ route('esbtp.inscriptions.changer-classe-rapide', $inscription->id) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                nouvelle_classe_id: selectedClasseId,
                affectation_status: selectedAffectationStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Fermer le modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('affectationClasseModal'));
                if (modal) modal.hide();

                // Toast de succès puis rechargement pour rafraîchir frais + DOM complet
                showToast(data.message, 'success');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showToast(data.message || 'Erreur lors de l\'affectation.', 'error');
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Erreur affectation:', error);
            showToast('Une erreur est survenue. Veuillez réessayer.', 'error');
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        });
    }

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:99999;padding:14px 22px;border-radius:10px;font-size:0.88rem;font-weight:600;color:#fff;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,0.2);animation:slideInRight 0.3s ease;max-width:400px;';
        toast.style.background = type === 'success'
            ? 'linear-gradient(135deg, #059669 0%, #10b981 100%)'
            : 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        toast.innerHTML = '<i class="fas ' + icon + '"></i><span>' + message + '</span>';

        // Ajouter l'animation CSS
        if (!document.getElementById('toast-animation-style')) {
            const style = document.createElement('style');
            style.id = 'toast-animation-style';
            style.textContent = '@keyframes slideInRight{from{transform:translateX(100%);opacity:0}to{transform:translateX(0);opacity:1}}';
            document.head.appendChild(style);
        }

        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'all 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
</script>
<script>
    // ========================================
    // FALLBACK DEBUGLOG - Au cas où debug-helper.js n'est pas chargé
    // ========================================
    if (typeof debugLog === 'undefined') {
        window.debugLog = function(...args) {
            console.log('[DEBUG]', ...args);
        };
        window.debugWarn = function(...args) {
            console.warn('[WARN]', ...args);
        };
        window.debugError = function(...args) {
            console.error('[ERROR]', ...args);
        };
        console.warn('⚠️ debug-helper.js non chargé, utilisation du fallback debugLog');
    }

    debugLog('🚀 SCRIPT CHARGÉ - Fonctions modales en cours de définition...');

    // Logs simples pour debug - Style class-selector
    function logModal(modalId, message) {
        debugLog(`📝 Modal ${modalId}: ${message}`);
    }

    // Fonction simple pour préparer les modals - comme class-selector
    function setupModalBasic(modalId) {
        debugLog(`📝 Configuration basique pour modal ${modalId}`);
        // Laisser Bootstrap gérer tout le reste
    }

    // Fonctions globales simples pour les boutons onclick - Style class-selector
    function preparePaymentModal(inscriptionId) {
        debugLog('🎯 preparePaymentModal appelé avec ID:', inscriptionId);
        
        const form = document.getElementById('paymentForm');
        const correctAction = `/esbtp/inscriptions/${inscriptionId}/valider-avec-paiement`;
        form.action = correctAction;
        
        // Reset le formulaire
        form.reset();
        
        // Remettre la date du jour
        const dateInput = document.getElementById('date_paiement');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        
        debugLog('✅ Formulaire de paiement préparé, action:', form.action);
    }

    function openValidationModal(inscriptionId) {
        debugLog('🎯 openValidationModal appelé avec ID:', inscriptionId);

        const form = document.getElementById('validationForm');
        const correctAction = `/esbtp/inscriptions/${inscriptionId}/valider-definitivement`;
        form.action = correctAction;
        form.reset();

        debugLog('✅ Formulaire de validation préparé, action:', form.action);
    }

    // ========================================
    // VALIDATION MONTANT PAIEMENT - Fonction Globale
    // DOIT ÊTRE DÉFINIE IMMÉDIATEMENT (pas dans DOMContentLoaded)
    // pour être accessible par preparePaymentModalForCategory() qui est appelée via onclick
    // ========================================
    @if(isset($inscription) && $inscription)
    window.updateMontantRestant = function() {
        console.log('🔍 DEBUG updateMontantRestant() appelée (version globale)');

        const inscriptionId = {{ $inscription->id ?? 'null' }};
        const feeCategorySelect = document.getElementById('fee_category_id');
        const montantInput = document.getElementById('montant');
        const categoryId = feeCategorySelect ? feeCategorySelect.value : null;

        console.log('  - categoryId:', categoryId);
        console.log('  - montantInput exists:', !!montantInput);
        console.log('  - window.isSubscribedToCategory (avant):', window.isSubscribedToCategory);

        // ✅ Réinitialiser si aucune catégorie sélectionnée
        if (!categoryId || !montantInput) {
            console.log('  ❌ Pas de categoryId ou pas de montantInput - RÉINITIALISATION');
            window.isSubscribedToCategory = false;

            if (montantInput) {
                montantInput.setAttribute('disabled', 'disabled');
                montantInput.value = '';
                console.log('  - montantInput DÉSACTIVÉ');
            }

            const messageDiv = document.getElementById('montant-validation-message');
            if (messageDiv) {
                messageDiv.style.display = 'none';
            }
            return;
        }

        console.log('  ✅ Appel AJAX pour catégorie:', categoryId);

        // Appel AJAX pour récupérer le montant restant
        fetch(`/esbtp/inscriptions/${inscriptionId}/frais/${categoryId}/montant-restant`)
            .then(response => response.json())
            .then(data => {
                console.log('  📡 Réponse API:', data);
                const messageDiv = document.getElementById('montant-validation-message');

                if (data.success && data.is_subscribed) {
                    console.log('  ✅ ÉTUDIANT SOUSCRIT - Activation validation');
                    window.isSubscribedToCategory = true;

                    // ✅ Réactiver l'input montant si précédemment désactivé
                    montantInput.removeAttribute('disabled');
                    console.log('  - montantInput RÉACTIVÉ');

                    // ✅ Pré-remplir automatiquement avec le montant restant
                    montantInput.value = data.montant_restant;
                    console.log('  - montantInput.value pré-rempli avec:', data.montant_restant, 'FCFA');

                    // Mettre à jour l'attribut max de l'input montant
                    montantInput.setAttribute('max', data.montant_restant);

                    // Afficher le message informatif
                    if (messageDiv) {
                        messageDiv.style.display = 'block';
                        messageDiv.innerHTML = `
                            <div style="
                                background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%);
                                border-left: 4px solid #0d6efd;
                                border-radius: 8px;
                                padding: 0.75rem 1rem;
                            ">
                                <div style="display: flex; align-items: start; gap: 0.5rem;">
                                    <i class="fas fa-info-circle" style="color: #0d6efd; margin-top: 2px;"></i>
                                    <div style="flex: 1;">
                                        <strong style="color: #084298;">Montant maximum autorisé :</strong>
                                        <span style="color: #052c65; font-weight: 600;">${data.montant_restant.toLocaleString('fr-FR')} FCFA</span>
                                        <br>
                                        <small style="color: #6c757d;">
                                            Total: ${data.montant_total.toLocaleString('fr-FR')} FCFA •
                                            Déjà payé: ${data.montant_paye.toLocaleString('fr-FR')} FCFA
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    // Validation en temps réel sur l'input montant
                    montantInput.addEventListener('input', function() {
                        const montantSaisi = parseFloat(this.value) || 0;

                        if (montantSaisi > data.montant_restant) {
                            this.setCustomValidity(`Le montant ne peut pas dépasser ${data.montant_restant.toLocaleString('fr-FR')} FCFA`);
                            this.reportValidity();
                        } else {
                            this.setCustomValidity('');
                        }
                    });
                } else {
                    console.log('  ❌ ÉTUDIANT NON SOUSCRIT - Blocage paiement');
                    window.isSubscribedToCategory = false;
                    console.log('  - window.isSubscribedToCategory mis à FALSE');

                    if (messageDiv) {
                        messageDiv.style.display = 'block';
                        messageDiv.innerHTML = `
                            <div style="
                                background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%);
                                border-left: 4px solid #dc3545;
                                border-radius: 8px;
                                padding: 0.75rem 1rem;
                            ">
                                <div style="display: flex; align-items: start; gap: 0.5rem;">
                                    <i class="fas fa-times-circle" style="color: #dc3545; margin-top: 2px;"></i>
                                    <div style="flex: 1; color: #842029;">
                                        <strong>Impossible d'associer ce paiement :</strong><br>
                                        ${data.message || 'L\'étudiant n\'est pas souscrit à cette catégorie de frais.'}
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    // Désactiver l'input montant pour empêcher la saisie
                    montantInput.setAttribute('disabled', 'disabled');
                    montantInput.value = '';
                    montantInput.removeAttribute('max');
                    console.log('  - montantInput DÉSACTIVÉ (disabled=true)');
                    console.log('  - montantInput.disabled:', montantInput.disabled);
                }
            })
            .catch(error => {
                console.error('Erreur AJAX getMontantRestant:', error);

                // ✅ FIX: Marquer comme NON souscrit en cas d'erreur réseau
                window.isSubscribedToCategory = false;

                const messageDiv = document.getElementById('montant-validation-message');
                if (messageDiv) {
                    messageDiv.style.display = 'block';
                    messageDiv.innerHTML = `
                        <div style="
                            background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%);
                            border-left: 4px solid #dc3545;
                            border-radius: 8px;
                            padding: 0.75rem 1rem;
                        ">
                            <div style="display: flex; align-items: start; gap: 0.5rem;">
                                <i class="fas fa-times-circle" style="color: #dc3545; margin-top: 2px;"></i>
                                <div style="flex: 1; color: #842029;">
                                    <strong>Erreur de connexion :</strong><br>
                                    Impossible de vérifier le montant. Veuillez réessayer.
                                </div>
                            </div>
                        </div>
                    `;
                }

                // Désactiver l'input montant pour empêcher la saisie
                montantInput.setAttribute('disabled', 'disabled');
                montantInput.value = '';
            });
    };

    // Initialiser la variable globale
    window.isSubscribedToCategory = false;

    // 🔍 DEBUG: Vérifier que la fonction est bien définie
    console.log('✅ window.updateMontantRestant définie:', typeof window.updateMontantRestant);
    console.log('✅ window.updateMontantRestant === function:', typeof window.updateMontantRestant === 'function');
    @else
    // ⚠️ $inscription n'est pas définie - fonction updateMontantRestant non créée
    console.error('⚠️ ERREUR BLADE: $inscription non définie - window.updateMontantRestant ne sera pas créée');
    window.updateMontantRestant = function() {
        console.error('❌ updateMontantRestant appelée mais $inscription était undefined lors du rendu Blade');
        alert('Erreur: Impossible de charger les données de l\'inscription.');
    };
    window.isSubscribedToCategory = false;
    console.log('⚠️ Mode fallback: window.updateMontantRestant définie avec message d\'erreur');
    @endif

    function preparePaymentModalForCategory(inscriptionId, categoryId) {
        console.log('🎯 preparePaymentModalForCategory appelé');
        console.log('  - inscriptionId:', inscriptionId);
        console.log('  - categoryId:', categoryId);
        debugLog('🎯 preparePaymentModalForCategory appelé avec ID:', inscriptionId, 'Category:', categoryId);

        preparePaymentModal(inscriptionId);

        // Attendre que le modal soit prêt
        setTimeout(() => {
            console.log('  ⏱️ Timeout 100ms écoulé - Pré-sélection catégorie');
            const categorySelect = document.getElementById('fee_category_id');
            if (categorySelect && categoryId) {
                console.log('  - Avant: categorySelect.value =', categorySelect.value);
                categorySelect.value = categoryId;
                console.log('  - Après: categorySelect.value =', categorySelect.value);

                // ✅ FIX: Appeler updateMontantRestant() DIRECTEMENT au lieu de dispatcher un event
                console.log('  - Appel DIRECT de window.updateMontantRestant()');
                if (typeof window.updateMontantRestant === 'function') {
                    window.updateMontantRestant();
                } else {
                    console.error('  ❌ window.updateMontantRestant n\'est pas définie!');
                }

                debugLog('✅ Catégorie pré-sélectionnée:', categoryId);
            } else {
                console.log('  ⚠️ categorySelect ou categoryId manquant!');
            }
        }, 100);
    }

    // Variables globales pour la gestion multi-destinations
    let destinationCounter = 0;
    let availableFees = @json(collect($feeCategoriesWithRules)->filter(function($item) { return $item['is_configured'] && $item['solde'] != 0; })->values());
    
    // Fonction pour préparer le modal de transfert de trop-perçu - Améliorée
    // Variables globales pour stocker les données de transfert
    let transferData = {
        inscriptionId: null,
        sourceCategoryId: null,
        availableAmount: null,
        sourceCategoryName: null
    };

    function prepareTransferModal(inscriptionId, sourceCategoryId, availableAmount, sourceCategoryName) {
        debugLog('🔄 prepareTransferModal appelé (multi-destinations):', {
            inscriptionId, sourceCategoryId, availableAmount, sourceCategoryName
        });
        
        // Debug : vérifier les types de données
        debugLog('📊 Types des données reçues:', {
            inscriptionId: typeof inscriptionId,
            sourceCategoryId: typeof sourceCategoryId,
            availableAmount: typeof availableAmount,
            sourceCategoryName: typeof sourceCategoryName
        });
        
        // Stocker les données dans les variables globales
        transferData = {
            inscriptionId,
            sourceCategoryId,
            availableAmount,
            sourceCategoryName
        };
        
        debugLog('📦 Données stockées dans transferData:', transferData);
        
        // Forcer l'application des données immédiatement
        applyTransferData();
        
        // Et aussi avec un délai pour être sûr
        setTimeout(applyTransferData, 50);
        setTimeout(applyTransferData, 200);
        setTimeout(applyTransferData, 500);
    }
    
    function applyTransferData() {
        debugLog('🔧 applyTransferData appelé avec:', transferData);
        
        if (!transferData.sourceCategoryId) {
            debugWarn('⚠️ Pas de données de transfert disponibles');
            return;
        }
        
        // Rechercher tous les éléments
        const elements = {
            sourceCategoryField: document.getElementById('transfer_source_category'),
            amountHiddenField: document.getElementById('transfer_amount_hidden'),
            sourceNameEl: document.getElementById('transfer_source_name'),
            sourceDisplayEl: document.getElementById('transfer_source_display'),
            amountDisplayEl: document.getElementById('transfer_amount_display'),
            container: document.getElementById('destinations_container'),
            commentField: document.getElementById('transfer_comment'),
            summaryDiv: document.getElementById('transfer_summary'),
            submitBtn: document.getElementById('transfer_submit_btn')
        };
        
        debugLog('🔍 Éléments trouvés:', Object.keys(elements).filter(key => elements[key] !== null));
        debugLog('❌ Éléments manquants:', Object.keys(elements).filter(key => elements[key] === null));
        
        // Appliquer les données aux champs cachés
        if (elements.sourceCategoryField) {
            elements.sourceCategoryField.value = transferData.sourceCategoryId;
            debugLog('✅ Source category définie:', transferData.sourceCategoryId);
        }
        
        if (elements.amountHiddenField) {
            elements.amountHiddenField.value = transferData.availableAmount;
            debugLog('✅ Amount défini:', transferData.availableAmount);
        }
        
        // Appliquer les données d'affichage
        if (elements.sourceNameEl) {
            elements.sourceNameEl.textContent = transferData.sourceCategoryName || 'Catégorie inconnue';
            debugLog('✅ Source name affiché:', transferData.sourceCategoryName);
        }
        
        if (elements.sourceDisplayEl) {
            elements.sourceDisplayEl.textContent = transferData.sourceCategoryName || 'Catégorie inconnue';
            debugLog('✅ Source display affiché:', transferData.sourceCategoryName);
        }
        
        if (elements.amountDisplayEl) {
            const formattedAmount = new Intl.NumberFormat('fr-FR').format(transferData.availableAmount || 0) + ' FCFA';
            elements.amountDisplayEl.textContent = formattedAmount;
            debugLog('✅ Amount display affiché:', formattedAmount);
        }
        
        // Réinitialiser et configurer le conteneur des destinations
        if (elements.container) {
            elements.container.innerHTML = '';
            destinationCounter = 0;
            debugLog('✅ Container des destinations réinitialisé');
            
            // Ajouter la première destination
            addDestinationRow(transferData.sourceCategoryId, transferData.availableAmount);
        }
        
        // Réinitialiser les autres champs
        if (elements.commentField) {
            elements.commentField.value = '';
        }
        
        if (elements.summaryDiv) {
            elements.summaryDiv.classList.add('d-none');
        }
        
        if (elements.submitBtn) {
            elements.submitBtn.disabled = true;
        }
        
        debugLog('✅ Données de transfert appliquées avec succès');
    }
    
    // Fonction pour ajouter une ligne de destination
    function addDestinationRow(sourceCategoryId, totalAvailable) {
        destinationCounter++;
        const container = document.getElementById('destinations_container');
        
        const destinationHtml = `
            <div class="destination-row" data-destination-id="${destinationCounter}">
                <div class="card mb-3" style="border-left: 3px solid var(--primary);">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <h6 class="mb-0 text-primary">
                            <i class="fas fa-bullseye me-2"></i>Destination ${destinationCounter}
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-destination-btn" 
                                onclick="removeDestination(${destinationCounter})" 
                                ${destinationCounter === 1 ? 'style="display: none;"' : ''}>
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="form-grid-2">
                            <div class="form-group-moderne">
                                <label class="form-label-moderne">Frais de destination</label>
                                <select class="form-select-moderne destination-select" 
                                        name="destinations[${destinationCounter}][category_id]" 
                                        data-destination-id="${destinationCounter}" required>
                                    <option value="">Sélectionner un frais...</option>
                                </select>
                            </div>
                            <div class="form-group-moderne">
                                <label class="form-label-moderne">Montant à transférer (FCFA)</label>
                                <input type="number" 
                                       class="form-control-moderne destination-amount" 
                                       name="destinations[${destinationCounter}][amount]" 
                                       data-destination-id="${destinationCounter}"
                                       step="1" 
                                       min="1"
                                       placeholder="Entrez le montant..." required>
                            </div>
                        </div>
                        
                        <div class="destination-info mt-3 d-none" id="destination_info_${destinationCounter}">
                            <div class="alert alert-secondary">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Solde actuel :</strong>
                                        <span class="destination-current-balance">-</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Après transfert :</strong>
                                        <span class="destination-after-transfer text-primary">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', destinationHtml);
        
        // Remplir les options pour cette destination
        populateDestinationOptions(destinationCounter, sourceCategoryId);
        
        // Attacher les événements
        attachDestinationEvents(destinationCounter);
        
        // Mettre à jour les boutons de suppression
        updateRemoveButtons();
    }
    
    // Fonction pour remplir les options de destination
    function populateDestinationOptions(destinationId, sourceCategoryId) {
        const select = document.querySelector(`select[data-destination-id="${destinationId}"]`);
        if (!select) return;
        
        // Vider les options existantes (sauf la première)
        select.innerHTML = '<option value="">Sélectionner un frais...</option>';
        
        // Obtenir les catégories déjà sélectionnées
        const selectedCategories = getSelectedCategories();
        
        // Ajouter les options disponibles
        availableFees.forEach(item => {
            // Exclure la catégorie source et les catégories déjà sélectionnées
            if (item.category.id != sourceCategoryId && !selectedCategories.includes(item.category.id.toString())) {
                const option = document.createElement('option');
                option.value = item.category.id;
                option.textContent = `${item.category.name}`;
                if (item.solde > 0) {
                    option.textContent += ` (Solde à payer: ${new Intl.NumberFormat('fr-FR').format(item.solde)} FCFA)`;
                } else if (item.solde < 0) {
                    option.textContent += ` (Trop-perçu: ${new Intl.NumberFormat('fr-FR').format(Math.abs(item.solde))} FCFA)`;
                }
                option.dataset.solde = item.solde;
                option.dataset.name = item.category.name;
                select.appendChild(option);
            }
        });
    }
    
    // Fonction pour obtenir les catégories déjà sélectionnées
    function getSelectedCategories() {
        const selected = [];
        document.querySelectorAll('.destination-select').forEach(select => {
            if (select.value) {
                selected.push(select.value);
            }
        });
        return selected;
    }
    
    // Fonction pour attacher les événements à une destination
    function attachDestinationEvents(destinationId) {
        const select = document.querySelector(`select[data-destination-id="${destinationId}"]`);
        const amountInput = document.querySelector(`input[data-destination-id="${destinationId}"]`);
        
        if (select) {
            select.addEventListener('change', function() {
                updateDestinationInfo(destinationId);
                updateAllDestinationOptions();
                updateTransferSummary();
            });
        }
        
        if (amountInput) {
            amountInput.addEventListener('input', function() {
                updateDestinationInfo(destinationId);
                updateTransferSummary();
            });
        }
    }
    
    // Fonction pour mettre à jour les informations d'une destination
    function updateDestinationInfo(destinationId) {
        const select = document.querySelector(`select[data-destination-id="${destinationId}"]`);
        const amountInput = document.querySelector(`input[data-destination-id="${destinationId}"]`);
        const infoDiv = document.getElementById(`destination_info_${destinationId}`);
        
        if (!select || !amountInput || !infoDiv) return;
        
        if (select.value) {
            const selectedOption = select.options[select.selectedIndex];
            const currentSolde = parseFloat(selectedOption.dataset.solde);
            const transferAmount = parseFloat(amountInput.value) || 0;
            
            // Afficher les informations
            const currentBalanceSpan = infoDiv.querySelector('.destination-current-balance');
            const afterTransferSpan = infoDiv.querySelector('.destination-after-transfer');
            
            if (currentSolde > 0) {
                currentBalanceSpan.textContent = `À payer: ${new Intl.NumberFormat('fr-FR').format(currentSolde)} FCFA`;
                currentBalanceSpan.className = 'destination-current-balance text-warning';
            } else if (currentSolde < 0) {
                currentBalanceSpan.textContent = `Trop-perçu: ${new Intl.NumberFormat('fr-FR').format(Math.abs(currentSolde))} FCFA`;
                currentBalanceSpan.className = 'destination-current-balance text-success';
            } else {
                currentBalanceSpan.textContent = 'Soldé';
                currentBalanceSpan.className = 'destination-current-balance text-muted';
            }
            
            if (transferAmount > 0) {
                const newSolde = currentSolde - transferAmount;
                if (newSolde > 0) {
                    afterTransferSpan.textContent = `Restera à payer: ${new Intl.NumberFormat('fr-FR').format(newSolde)} FCFA`;
                    afterTransferSpan.className = 'destination-after-transfer text-warning';
                } else if (newSolde < 0) {
                    afterTransferSpan.textContent = `Nouveau trop-perçu: ${new Intl.NumberFormat('fr-FR').format(Math.abs(newSolde))} FCFA`;
                    afterTransferSpan.className = 'destination-after-transfer text-success';
                } else {
                    afterTransferSpan.textContent = 'Soldé parfaitement';
                    afterTransferSpan.className = 'destination-after-transfer text-success fw-bold';
                }
            } else {
                afterTransferSpan.textContent = '-';
                afterTransferSpan.className = 'destination-after-transfer text-muted';
            }
            
            infoDiv.classList.remove('d-none');
        } else {
            infoDiv.classList.add('d-none');
        }
    }
    
    // Fonction pour mettre à jour toutes les options de destination
    function updateAllDestinationOptions() {
        const sourceCategoryId = document.getElementById('transfer_source_category').value;
        document.querySelectorAll('.destination-select').forEach(select => {
            const destinationId = select.dataset.destinationId;
            const currentValue = select.value;
            populateDestinationOptions(destinationId, sourceCategoryId);
            // Restaurer la valeur sélectionnée si elle est toujours disponible
            if (currentValue) {
                select.value = currentValue;
            }
        });
    }
    
    // Fonction pour mettre à jour le résumé des transferts
    function updateTransferSummary() {
        const totalAvailable = parseFloat(document.getElementById('transfer_amount_hidden').value) || 0;
        let totalToTransfer = 0;
        let hasValidDestinations = false;
        
        document.querySelectorAll('.destination-amount').forEach(input => {
            const amount = parseFloat(input.value) || 0;
            if (amount > 0) {
                totalToTransfer += amount;
                hasValidDestinations = true;
            }
        });
        
        const remaining = totalAvailable - totalToTransfer;
        
        // Mettre à jour l'affichage
        document.getElementById('total_transfer_amount').textContent = 
            new Intl.NumberFormat('fr-FR').format(totalToTransfer) + ' FCFA';
        document.getElementById('remaining_amount').textContent = 
            new Intl.NumberFormat('fr-FR').format(remaining) + ' FCFA';
        
        // Gérer la couleur du montant restant
        const remainingSpan = document.getElementById('remaining_amount');
        if (remaining < 0) {
            remainingSpan.className = 'text-danger fw-bold';
        } else if (remaining === 0) {
            remainingSpan.className = 'text-success fw-bold';
        } else {
            remainingSpan.className = 'text-success';
        }
        
        // Afficher/masquer le résumé
        const summaryDiv = document.getElementById('transfer_summary');
        if (hasValidDestinations) {
            summaryDiv.classList.remove('d-none');
        } else {
            summaryDiv.classList.add('d-none');
        }
        
        // Activer/désactiver le bouton de soumission
        const submitBtn = document.getElementById('transfer_submit_btn');
        submitBtn.disabled = !hasValidDestinations || remaining < 0 || totalToTransfer === 0;
        
        // Vérifier que toutes les destinations ont une catégorie sélectionnée
        const allSelects = document.querySelectorAll('.destination-select');
        let allSelected = true;
        allSelects.forEach(select => {
            if (!select.value) {
                allSelected = false;
            }
        });
        
        if (!allSelected) {
            submitBtn.disabled = true;
        }
    }
    
    // Fonction pour supprimer une destination
    function removeDestination(destinationId) {
        const row = document.querySelector(`div[data-destination-id="${destinationId}"]`);
        if (row) {
            row.remove();
            updateAllDestinationOptions();
            updateTransferSummary();
            updateRemoveButtons();
        }
    }
    
    // Fonction pour mettre à jour la visibilité des boutons de suppression
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-destination-btn');
        const destinationRows = document.querySelectorAll('.destination-row');
        
        removeButtons.forEach(btn => {
            if (destinationRows.length > 1) {
                btn.style.display = 'block';
            } else {
                btn.style.display = 'none';
            }
        });
    }
    
    debugLog('✅ FONCTIONS MODALES DÉFINIES:');
    debugLog('  - preparePaymentModal:', typeof preparePaymentModal);
    debugLog('  - openValidationModal:', typeof openValidationModal);
    debugLog('  - preparePaymentModalForCategory:', typeof preparePaymentModalForCategory);
    debugLog('  - prepareTransferModal:', typeof prepareTransferModal);
    debugLog('  - prepareEditSubscriptionModal:', typeof window.prepareEditSubscriptionModal);

    // Debug des éléments du modal d'édition
    const editModal = document.getElementById('editSubscriptionModal');
    debugLog('🔍 Modal d\'édition présent:', !!editModal);

    if (editModal) {
        const requiredElements = [
            'edit_subscription_id',
            'edit_subscription_category_name',
            'edit_subscription_current_amount',
            'edit_subscription_new_amount',
            'edit_subscription_reason',
            'editSubscriptionForm'
        ];

        requiredElements.forEach(id => {
            const element = document.getElementById(id);
            debugLog(`  - ${id}:`, !!element);
        });
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        debugLog('🚀 Initialisation du diagnostic des modals');
        
        // Surveillance simple des événements de modals - Style class-selector
        const modals = ['paymentModal', 'validationModal', 'subscriptionModal', 'transferModal', 'reliquatPaymentModal'];
        
        // Événement spécial pour le modal de transfert - réinitialisation complète
        const transferModal = document.getElementById('transferModal');
        if (transferModal) {
            // À l'ouverture : appliquer les données de transfert
            transferModal.addEventListener('shown.bs.modal', function() {
                debugLog('🎯 Modal de transfert ouvert - Application des données');
                
                // Forcer l'application des données stockées
                if (transferData.sourceCategoryId) {
                    applyTransferData();
                    debugLog('🔄 Données de transfert réappliquées');
                } else {
                    debugWarn('⚠️ Aucune donnée de transfert stockée');
                }
            });
            
            // À la fermeture : nettoyer
            transferModal.addEventListener('hidden.bs.modal', function() {
                // Réinitialiser complètement le modal quand il se ferme
                const container = document.getElementById('destinations_container');
                if (container) {
                    container.innerHTML = '';
                }
                destinationCounter = 0;
                
                // Nettoyer les données stockées
                transferData = {
                    inscriptionId: null,
                    sourceCategoryId: null,
                    availableAmount: null,
                    sourceCategoryName: null
                };
                
                debugLog('📝 Modal de transfert réinitialisé et données nettoyées');
            });
        }
        
        // Gérer le bouton d'ajout de destination
        const addDestinationBtn = document.getElementById('add_destination_btn');
        if (addDestinationBtn) {
            addDestinationBtn.addEventListener('click', function() {
                const sourceCategoryId = document.getElementById('transfer_source_category').value;
                const totalAvailable = parseFloat(document.getElementById('transfer_amount_hidden').value);
                
                // Vérifier qu'il reste des catégories disponibles
                const selectedCategories = getSelectedCategories();
                const availableCategories = availableFees.filter(item => 
                    item.category.id != sourceCategoryId && 
                    !selectedCategories.includes(item.category.id.toString())
                );
                
                if (availableCategories.length > 0) {
                    addDestinationRow(sourceCategoryId, totalAvailable);
                } else {
                    alert('Toutes les catégories de frais disponibles ont déjà été sélectionnées.');
                }
            });
        }
        
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Événement pour forcer z-index correct à l'ouverture
                modal.addEventListener('show.bs.modal', function(e) {
                    debugLog(`🔧 Préparation modal ${modalId}`);
                    
                    // Désactiver toutes les animations pendant l'ouverture
                    document.body.style.setProperty('overflow', 'hidden', 'important');
                    
                    // Ajouter style anti-cursor
                    const antiCursorStyle = document.createElement('style');
                    antiCursorStyle.id = `anti-cursor-${modalId}`;
                    antiCursorStyle.textContent = `
                        * { animation: none !important; transition: none !important; }
                        *:hover { transform: none !important; }
                    `;
                    document.head.appendChild(antiCursorStyle);
                });
                
                modal.addEventListener('shown.bs.modal', function(e) {
                    debugLog(`✅ Modal ${modalId} ouvert - Application des corrections`);
                    
                    // Forcer z-index très élevé
                    modal.style.setProperty('z-index', '9999', 'important');
                    modal.style.setProperty('backdrop-filter', 'none', 'important');
                    modal.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    
                    const modalDialog = modal.querySelector('.modal-dialog');
                    const modalContent = modal.querySelector('.modal-content');
                    
                    if (modalDialog) {
                        modalDialog.style.setProperty('z-index', '10000', 'important');
                        modalDialog.style.setProperty('backdrop-filter', 'none', 'important');
                        modalDialog.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    }
                    
                    if (modalContent) {
                        modalContent.style.setProperty('z-index', '10001', 'important');
                        modalContent.style.setProperty('backdrop-filter', 'none', 'important');
                        modalContent.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                        modalContent.style.setProperty('background', 'white', 'important');
                    }
                    
                    // Forcer backdrop en arrière
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.style.setProperty('z-index', '1040', 'important');
                        backdrop.style.setProperty('backdrop-filter', 'none', 'important');
                        backdrop.style.setProperty('-webkit-backdrop-filter', 'none', 'important');
                    }
                });
                
                // Nettoyer à la fermeture
                modal.addEventListener('hidden.bs.modal', function(e) {
                    debugLog(`🧹 Nettoyage modal ${modalId}`);
                    
                    // Supprimer style anti-cursor
                    const antiCursorStyle = document.getElementById(`anti-cursor-${modalId}`);
                    if (antiCursorStyle) {
                        antiCursorStyle.remove();
                    }
                    
                    // Rétablir overflow
                    document.body.style.overflow = '';
                });
            }
        });
        
        // Auto-remplir le montant selon la catégorie sélectionnée - Style class-selector
        const feeCategorySelect = document.getElementById('fee_category_id');
        const montantInput = document.getElementById('montant');
        
        if (feeCategorySelect && montantInput) {
            feeCategorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const defaultAmount = selectedOption.getAttribute('data-default-amount');
                if (defaultAmount) {
                    montantInput.value = defaultAmount;
                    debugLog('💰 Montant auto-rempli:', defaultAmount);
                }
            });
        }

        // ── Souscription frais optionnel : gestion modal ──────────────────────
        const optionalCategorySelect  = document.getElementById('optional_category_id');
        const subscriptionAmountInput = document.getElementById('subscription_amount');
        const subscriptionOptionsZone = document.getElementById('subscription_options_zone');
        const subscriptionOptionsList = document.getElementById('subscription_options_list');
        const subscriptionAmountHint  = document.getElementById('subscription_amount_hint');

        function renderSubscriptionOptions(options, defaultAmount) {
            if (!subscriptionOptionsList || !subscriptionOptionsZone) return;

            if (!options || options.length === 0) {
                subscriptionOptionsZone.style.display = 'none';
                if (subscriptionAmountInput) {
                    subscriptionAmountInput.value = defaultAmount;
                    subscriptionAmountInput.readOnly = false;
                }
                if (subscriptionAmountHint) subscriptionAmountHint.textContent = 'Montant par défaut pré-rempli. Vous pouvez l\'ajuster.';
                return;
            }

            subscriptionOptionsZone.style.display = 'block';
            if (subscriptionAmountHint) subscriptionAmountHint.textContent = '';

            let html = '';
            // Default amount option
            html += `<div class="form-check mb-2">
                <input class="form-check-input subscription-option-radio" type="radio"
                       name="subscription_option" value="default"
                       id="subs_opt_default" data-amount="${defaultAmount}" checked>
                <label class="form-check-label" for="subs_opt_default">
                    Montant de base — <strong>${parseFloat(defaultAmount).toLocaleString('fr-FR')} FCFA</strong>
                </label>
            </div>`;
            options.forEach((opt, idx) => {
                const total = parseFloat(defaultAmount) + parseFloat(opt.additional_amount || 0);
                html += `<div class="form-check mb-2">
                    <input class="form-check-input subscription-option-radio" type="radio"
                           name="subscription_option" value="${opt.id}"
                           id="subs_opt_${opt.id}" data-amount="${total}">
                    <label class="form-check-label" for="subs_opt_${opt.id}">
                        ${opt.name} — <strong>${total.toLocaleString('fr-FR')} FCFA</strong>
                        ${opt.description ? `<small class="text-muted d-block">${opt.description}</small>` : ''}
                    </label>
                </div>`;
            });
            subscriptionOptionsList.innerHTML = html;

            // Set initial amount from checked option
            if (subscriptionAmountInput) subscriptionAmountInput.value = defaultAmount;
            subscriptionAmountInput.readOnly = true;

            // On option change → update amount
            subscriptionOptionsList.querySelectorAll('.subscription-option-radio').forEach(radio => {
                radio.addEventListener('change', function() {
                    if (subscriptionAmountInput) subscriptionAmountInput.value = this.dataset.amount;
                });
            });
        }

        if (optionalCategorySelect) {
            optionalCategorySelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const defaultAmount  = parseFloat(selectedOption.getAttribute('data-default-amount')) || 0;
                let   options        = [];
                try { options = JSON.parse(selectedOption.getAttribute('data-options') || '[]'); } catch(e) {}
                renderSubscriptionOptions(options, defaultAmount);
            });
        }

        // Ouvrir le modal depuis un bouton de carte — pré-sélectionne la catégorie
        window.openSubscribeModal = function(categoryId, categoryName, defaultAmount, options) {
            if (!optionalCategorySelect) return;
            // Sélectionner la catégorie correspondante
            for (let i = 0; i < optionalCategorySelect.options.length; i++) {
                if (parseInt(optionalCategorySelect.options[i].value) === parseInt(categoryId)) {
                    optionalCategorySelect.selectedIndex = i;
                    break;
                }
            }
            renderSubscriptionOptions(options, defaultAmount);
            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('subscriptionModal'));
            modal.show();
        };

        // Fonction pour préparer le modal de paiement de reliquat
        window.prepareReliquatPaymentModal = function(reliquatId, montantRestant, nomFrais) {
            debugLog('🔄 Préparation modal paiement reliquat:', { reliquatId, montantRestant, nomFrais });

            // Remplir les données du reliquat
            document.getElementById('reliquat_id').value = reliquatId;
            document.getElementById('reliquat_frais_name').textContent = nomFrais;
            document.getElementById('reliquat_amount').textContent = new Intl.NumberFormat('fr-FR').format(montantRestant);
            document.getElementById('reliquat_montant').value = montantRestant;
            document.getElementById('reliquat_montant').max = montantRestant;

            // Réinitialiser les autres champs
            document.getElementById('reliquat_mode_paiement').value = '';
            document.getElementById('reliquat_notes').value = '';
        };

        // Fonction pour préparer le modal d'édition de souscription (SuperAdmin)
        window.prepareEditSubscriptionModal = function(subscriptionId, categoryName, currentAmount) {
            debugLog('✏️ Préparation modal édition souscription:', { subscriptionId, categoryName, currentAmount });

            try {
                // Vérifier que tous les éléments existent
                const elements = {
                    id: document.getElementById('edit_subscription_id'),
                    categoryName: document.getElementById('edit_subscription_category_name'),
                    currentAmount: document.getElementById('edit_subscription_current_amount'),
                    newAmount: document.getElementById('edit_subscription_new_amount'),
                    reason: document.getElementById('edit_subscription_reason'),
                    form: document.getElementById('editSubscriptionForm')
                };

                debugLog('🔍 Éléments trouvés:', elements);

                // Vérifier si tous les éléments existent
                for (const [key, element] of Object.entries(elements)) {
                    if (!element) {
                        debugError(`❌ Élément manquant: ${key}`);
                        alert(`Erreur: Élément manquant dans le modal: ${key}`);
                        return;
                    }
                }

                // Remplir les données de la souscription
                elements.id.value = subscriptionId;
                elements.categoryName.value = categoryName;
                elements.currentAmount.value = new Intl.NumberFormat('fr-FR').format(currentAmount) + ' FCFA';
                elements.newAmount.value = currentAmount;

                // Réinitialiser les champs de saisie
                elements.reason.value = '';

                // Mettre à jour l'action du formulaire
                const currentInscriptionId = {{ $inscription->id }};
                elements.form.action = `/esbtp/inscriptions/${currentInscriptionId}/subscriptions/${subscriptionId}`;

                debugLog('✅ Modal édition souscription préparé, action:', elements.form.action);
                debugLog('✅ Valeurs définies:', {
                    id: elements.id.value,
                    categoryName: elements.categoryName.value,
                    currentAmount: elements.currentAmount.value,
                    newAmount: elements.newAmount.value
                });

            } catch (error) {
                debugError('❌ Erreur dans prepareEditSubscriptionModal:', error);
                alert('Erreur lors de la préparation du modal: ' + error.message);
            }
        };

        // Gestionnaire de soumission du formulaire d'édition
        const editForm = document.getElementById('editSubscriptionForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const newAmount = document.getElementById('edit_subscription_new_amount').value;
            const reason = document.getElementById('edit_subscription_reason').value;

            // Validation côté client
            if (!newAmount || newAmount < 0) {
                alert('Veuillez saisir un montant valide');
                return;
            }

            if (!reason.trim()) {
                alert('Veuillez indiquer le motif de la modification');
                return;
            }

            // Confirmation avant soumission
            if (!confirm(`Êtes-vous sûr de vouloir modifier le montant de cette souscription ?\n\nNouveau montant: ${new Intl.NumberFormat('fr-FR').format(newAmount)} FCFA\nMotif: ${reason}`)) {
                return;
            }

            // Ajouter la méthode PUT pour Laravel
            formData.append('_method', 'PUT');

            // Debug: afficher les données envoyées
            debugLog('📤 Données envoyées:', {
                action: this.action,
                amount: formData.get('amount'),
                reason: formData.get('reason'),
                _method: formData.get('_method'),
                _token: formData.get('_token')
            });

            // Soumission via fetch
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Fermer le modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editSubscriptionModal'));
                    modal.hide();

                    // Recharger la page pour afficher les changements
                    location.reload();
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                debugError('Erreur:', error);
                alert('Une erreur est survenue lors de la mise à jour');
            });
            });
        } else {
            debugWarn('⚠️ Formulaire editSubscriptionForm non trouvé');
        }
    });

    // ========================================
    // PROTECTION CONTRE LES DOUBLE-CLICS
    // ========================================

    /**
     * Fonction générique pour protéger un formulaire contre les double-clics
     * @param {string} formSelector - Sélecteur jQuery du formulaire (#paymentForm, etc.)
     * @param {string} modalSelector - Sélecteur jQuery du modal parent (#paymentModal, etc.)
     */
    function protectFormAgainstDoubleClick(formSelector, modalSelector) {
        const $form = $(formSelector);
        const $modal = $(modalSelector);

        if ($form.length === 0) {
            debugWarn(`⚠️ Formulaire ${formSelector} non trouvé`);
            return;
        }

        let isSubmitting = false;
        let originalButtonText = '';

        // Handler sur le BOUTON SUBMIT (pas sur le formulaire) - se déclenche AVANT submit
        $form.off('click', 'button[type="submit"]').on('click', 'button[type="submit"]', function(e) {
            const $submitBtn = $(this);

            // Si déjà en cours de soumission, bloquer immédiatement
            if (isSubmitting) {
                e.preventDefault();
                e.stopImmediatePropagation();
                debugWarn(`⚠️ ${formSelector} - Clic bloqué, soumission déjà en cours`);
                return false;
            }

            // Marquer comme en cours de soumission IMMÉDIATEMENT
            isSubmitting = true;
            debugLog(`🔒 ${formSelector} - Bouton cliqué, verrouillage immédiat`);

            // Sauvegarder le texte original (pour restauration si la validation échoue)
            originalButtonText = $submitBtn.html();

            // NE PAS désactiver le bouton ici : désactiver le bouton dans le click handler
            // empêche Chrome de déclencher l'événement submit du formulaire.
            // La désactivation se fait dans le handler submit ci-dessous.
        });

        // Handler de soumission : désactive le bouton APRÈS que le navigateur a décidé de soumettre
        $form.off('submit').on('submit', function(e) {
            const $submitBtn = $(this).find('button[type="submit"]');
            $submitBtn.prop('disabled', true);
            $submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Traitement en cours...');
            $submitBtn.addClass('disabled');
            $modal.find('[data-bs-dismiss="modal"]').prop('disabled', true);
            // Laisser le formulaire se soumettre normalement
            return true;
        });

        // Exposer une fonction de reset pour les handlers natifs qui bloquent la soumission
        // (ex: validation montant échouée → l'utilisateur peut corriger et réessayer)
        $form.data('resetSubmitting', function() {
            isSubmitting = false;
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', false);
            if (originalButtonText) $submitBtn.html(originalButtonText);
            $submitBtn.removeClass('disabled');
            $modal.find('[data-bs-dismiss="modal"]').prop('disabled', false);
            debugLog(`🔓 ${formSelector} - Soumission annulée par validation, reset effectué`);
        });

        // Réinitialiser quand le modal est fermé (au cas où l'utilisateur ferme sans soumettre)
        if ($modal.length > 0) {
            $modal.on('hidden.bs.modal', function() {
                if (isSubmitting) {
                    debugLog(`🔓 ${formSelector} - Modal fermé, réinitialisation`);
                    isSubmitting = false;

                    const $submitBtn = $form.find('button[type="submit"]');
                    $submitBtn.prop('disabled', false);
                    if (originalButtonText) {
                        $submitBtn.html(originalButtonText);
                    }
                    $submitBtn.removeClass('disabled');

                    // Réactiver le bouton de fermeture
                    $modal.find('[data-bs-dismiss="modal"]').prop('disabled', false);
                }
            });
        }

        debugLog(`✅ Protection double-clic activée pour ${formSelector} (modal: ${modalSelector})`);
    }

    // Appliquer la protection sur tous les formulaires de paiement
    $(document).ready(function() {
        protectFormAgainstDoubleClick('#paymentForm', '#paymentModal');              // Modal associer un paiement
        protectFormAgainstDoubleClick('#validationForm', '#validationModal');        // Modal validation définitive
        protectFormAgainstDoubleClick('#reliquatPaymentForm', '#reliquatPaymentModal'); // Modal paiement reliquat
        protectFormAgainstDoubleClick('#subscriptionForm', '#subscriptionModal');    // Modal souscription optionnel

        // ========================================
        // TOGGLE des champs de paiement dans subscriptionModal
        // (checkbox "Effectuer et valider le paiement immédiatement")
        // ========================================
        const subCheckbox = document.getElementById('subscription_create_and_pay');
        const subPaymentFields = document.getElementById('subscription_payment_fields');
        const subModeSelect = document.getElementById('subscription_mode_paiement');
        if (subCheckbox && subPaymentFields && subModeSelect) {
            subCheckbox.addEventListener('change', function() {
                subPaymentFields.style.display = this.checked ? 'flex' : 'none';
                if (this.checked) {
                    subModeSelect.setAttribute('required', 'required');
                } else {
                    subModeSelect.removeAttribute('required');
                    subModeSelect.value = '';
                }
            });
        }

        // ========================================
        // VALIDATION MONTANT PAIEMENT - PAYMENTMODAL
        // ========================================
        const feeCategorySelect = document.getElementById('fee_category_id');
        const montantInput = document.getElementById('montant');
        const paymentForm = document.getElementById('paymentForm');

        // Créer un élément pour afficher le message de validation (si pas déjà créé)
        if (montantInput && !document.getElementById('montant-validation-message')) {
            const messageDiv = document.createElement('div');
            messageDiv.id = 'montant-validation-message';
            messageDiv.style.cssText = 'margin-top: 0.5rem; font-size: 0.875rem; display: none;';
            montantInput.closest('.col-md-6').appendChild(messageDiv);
        }

        // ========================================
        // NOTE: La fonction updateMontantRestant() est définie en GLOBAL (ligne ~3023)
        // pour être accessible par preparePaymentModalForCategory()
        // ========================================

        // Écouter le changement de catégorie de frais
        if (feeCategorySelect) {
            console.log('✅ Event listener "change" attaché au select catégorie');
            feeCategorySelect.addEventListener('change', function(e) {
                console.log('🔔 Event "change" détecté sur fee_category_id');
                console.log('  - Nouvelle valeur:', e.target.value);
                window.updateMontantRestant();  // ✅ Utilisation explicite de window.
            });
        } else {
            console.error('❌ feeCategorySelect NON TROUVÉ - Event listener PAS attaché!');
        }

        // Validation finale avant soumission du formulaire
        if (paymentForm) {
            paymentForm.addEventListener('submit', function(e) {
                console.log('🚀 SUBMIT PAYMENTFORM - Validation avant envoi');
                const categoryId = feeCategorySelect ? feeCategorySelect.value : null;
                console.log('  - categoryId:', categoryId);
                console.log('  - window.isSubscribedToCategory:', window.isSubscribedToCategory);

                // Note : on ne bloque plus la soumission si l'étudiant n'est pas souscrit
                // car le backend renvoie un message d'erreur lisible (redirect with error)
                // et bloquer côté JS avec alert() était jugé peu ergonomique (dialog fugace).

                const montantSaisi = parseFloat(montantInput.value) || 0;
                const montantMax = parseFloat(montantInput.getAttribute('max')) || Infinity;
                console.log('  - montantSaisi:', montantSaisi);
                console.log('  - montantMax:', montantMax);

                if (categoryId && montantSaisi > montantMax) {
                    console.log('  ❌ BLOCAGE: Montant dépasse le maximum');
                    e.preventDefault();
                    const resetFn = $('#paymentForm').data('resetSubmitting');
                    if (resetFn) resetFn();
                    alert(`❌ Le montant saisi (${montantSaisi.toLocaleString('fr-FR')} FCFA) dépasse le montant maximum autorisé (${montantMax.toLocaleString('fr-FR')} FCFA).\n\nVeuillez ajuster le montant.`);
                    montantInput.focus();
                    return false;
                }

                console.log('  ✅ Validation OK - Soumission autorisée');
            });
        }

        // ========================================
        // VALIDATION MONTANT PAIEMENT - RELIQUATPAYMENTMODAL
        // ========================================
        const reliquatMontantInput = document.getElementById('reliquat_montant');
        const reliquatPaymentForm = document.getElementById('reliquatPaymentForm');

        // Créer un élément pour afficher le message de validation
        if (reliquatMontantInput && !document.getElementById('reliquat-montant-validation-message')) {
            const messageDiv = document.createElement('div');
            messageDiv.id = 'reliquat-montant-validation-message';
            messageDiv.style.cssText = 'margin-top: 0.5rem; font-size: 0.875rem; display: none;';
            reliquatMontantInput.closest('.col-md-6').appendChild(messageDiv);
        }

        // Validation en temps réel sur l'input montant reliquat
        if (reliquatMontantInput) {
            reliquatMontantInput.addEventListener('input', function() {
                const montantSaisi = parseFloat(this.value) || 0;
                const montantMax = parseFloat(this.getAttribute('max')) || Infinity;
                const messageDiv = document.getElementById('reliquat-montant-validation-message');

                if (montantSaisi > montantMax && montantMax !== Infinity) {
                    // Afficher le message d'erreur
                    if (messageDiv) {
                        messageDiv.style.display = 'block';
                        messageDiv.innerHTML = `
                            <div style="
                                background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%);
                                border-left: 4px solid #dc3545;
                                border-radius: 8px;
                                padding: 0.75rem 1rem;
                            ">
                                <div style="display: flex; align-items: start; gap: 0.5rem;">
                                    <i class="fas fa-exclamation-triangle" style="color: #dc3545; margin-top: 2px;"></i>
                                    <div style="flex: 1; color: #842029;">
                                        <strong>Montant trop élevé !</strong><br>
                                        Le montant saisi (${montantSaisi.toLocaleString('fr-FR')} FCFA) dépasse le solde restant (${montantMax.toLocaleString('fr-FR')} FCFA).
                                    </div>
                                </div>
                            </div>
                        `;
                    }

                    // Validation HTML5
                    this.setCustomValidity(`Le montant ne peut pas dépasser ${montantMax.toLocaleString('fr-FR')} FCFA`);
                } else {
                    // Masquer le message d'erreur
                    if (messageDiv) {
                        messageDiv.style.display = 'none';
                    }

                    // Réinitialiser la validation HTML5
                    this.setCustomValidity('');
                }
            });
        }

        // Validation finale avant soumission du formulaire reliquat
        if (reliquatPaymentForm) {
            reliquatPaymentForm.addEventListener('submit', function(e) {
                const montantSaisi = parseFloat(reliquatMontantInput.value) || 0;
                const montantMax = parseFloat(reliquatMontantInput.getAttribute('max')) || Infinity;

                if (montantSaisi > montantMax && montantMax !== Infinity) {
                    e.preventDefault();
                    const resetFn = $('#reliquatPaymentForm').data('resetSubmitting');
                    if (resetFn) resetFn();
                    alert(`Le montant saisi (${montantSaisi.toLocaleString('fr-FR')} FCFA) dépasse le solde restant du reliquat (${montantMax.toLocaleString('fr-FR')} FCFA).`);
                    reliquatMontantInput.focus();
                    return false;
                }
            });
        }
    });

    // ── Désabonnement frais optionnel ─────────────────────────────────────
    window.prepareUnsubscribeModal = function(categoryId, categoryName, montantSouscrit, montantPaye) {
        document.getElementById('unsubscribeCategoryId').value  = categoryId;
        document.getElementById('unsubscribeCategoryName').textContent = categoryName;
        document.getElementById('unsubscribeModalSubtitle').textContent = 'Frais optionnel — ' + categoryName;
        document.getElementById('unsubscribeMontant').textContent =
            new Intl.NumberFormat('fr-FR').format(montantSouscrit) + ' FCFA';

        const alertEl = document.getElementById('unsubscribeAlert');
        if (montantPaye > 0) {
            alertEl.innerHTML = `
                <div style="background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;align-items:flex-start;gap:10px;">
                    <i class="fas fa-exclamation-triangle" style="color:#dc2626;font-size:15px;margin-top:1px;flex-shrink:0;"></i>
                    <div>
                        <div style="font-size:12px;font-weight:700;color:#dc2626;margin-bottom:3px;">Paiements enregistrés</div>
                        <div style="font-size:12px;color:#7f1d1d;line-height:1.45;">
                            ${new Intl.NumberFormat('fr-FR').format(montantPaye)} FCFA déjà payés pour ce frais.
                            Le désabonnement n'annule <strong>pas</strong> les paiements existants.
                        </div>
                    </div>
                </div>`;
        } else {
            alertEl.innerHTML = `
                <div style="background:#f0fdf4;border:1.5px solid #86efac;border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-check-circle" style="color:#16a34a;font-size:15px;flex-shrink:0;"></i>
                    <div style="font-size:12px;color:#14532d;line-height:1.45;">
                        Aucun paiement enregistré pour ce frais. Le désabonnement est sans risque.
                    </div>
                </div>`;
        }
    };
</script>
@endpush

<!-- Les styles z-index pour les modals sont gérés par modal-force-fix.css -->

@endsection
