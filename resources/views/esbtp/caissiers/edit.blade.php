@extends('layouts.app')

@section('title', 'Modifier Caissier — ' . $caissier->name . ' — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
/* ===================================================================
   CAISSIER EDIT — Premium Design — KLASSCI Design System
   Namespace: ce- (caissier-edit)
=================================================================== */

:root {
    --ce-blue:    #0453cb;
    --ce-blue-2:  #5e91de;
    --ce-surface: #f4f7fb;
    --ce-card:    #ffffff;
    --ce-border:  #e2e8f0;
    --ce-text:    #1e293b;
    --ce-muted:   #64748b;
    --ce-success: #10b981;
    --ce-danger:  #dc2626;
    --ce-radius:  12px;
}

.ce-page { background: var(--ce-surface); min-height: 100vh; padding-bottom: 40px; }

/* Header standard pour les formulaires (premium-redesign rule : pas de hero gradient sur les form pages) */
.ce-header {
    background: linear-gradient(135deg, var(--ce-blue) 0%, var(--ce-blue-2) 100%);
    color: #fff;
    padding: 28px 32px 24px;
    border-radius: 0 0 18px 18px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
}
.ce-header::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg width='20' height='20' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='1.5' cy='1.5' r='1' fill='rgba(255,255,255,0.07)'/%3E%3C/svg%3E");
    pointer-events: none;
}
.ce-header-row {
    position: relative; z-index: 2;
    max-width: 960px; margin: 0 auto;
    display: flex; justify-content: space-between; align-items: center;
    flex-wrap: wrap; gap: 16px;
}
.ce-header-left { display: flex; align-items: center; gap: 18px; flex-wrap: wrap; }
.ce-avatar {
    width: 64px; height: 64px;
    border-radius: 16px;
    background: rgba(255,255,255,.18);
    border: 1px solid rgba(255,255,255,.3);
    backdrop-filter: blur(8px);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; font-weight: 700; color: #fff;
    flex-shrink: 0;
}
.ce-header h1 { font-size: 1.4rem; font-weight: 700; color: #fff; margin: 0; }
.ce-header p { color: rgba(255,255,255,.78); font-size: .88rem; margin: 4px 0 0; }
.ce-header .ce-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.ce-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 18px; border-radius: 8px;
    font-size: .82rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    border: 1px solid transparent;
    transition: all .18s;
    white-space: nowrap;
}
.ce-btn-primary {
    background: linear-gradient(135deg, var(--ce-blue) 0%, var(--ce-blue-2) 100%);
    color: #fff;
}
.ce-btn-primary:hover { box-shadow: 0 4px 16px rgba(4,83,203,.3); transform: translateY(-1px); color: #fff; }
.ce-btn-glass {
    background: rgba(255,255,255,.18);
    color: #fff;
    border: 1px solid rgba(255,255,255,.32);
    backdrop-filter: blur(6px);
}
.ce-btn-glass:hover { background: rgba(255,255,255,.28); color: #fff; }
.ce-btn-white { background: #fff; color: var(--ce-blue); }
.ce-btn-white:hover { background: #fff; color: var(--ce-blue); box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.ce-btn-secondary { background: #f1f5f9; color: var(--ce-text); border-color: var(--ce-border); }
.ce-btn-secondary:hover { background: #e2e8f0; color: var(--ce-text); }
.ce-btn-danger { background: var(--ce-danger); color: #fff; }
.ce-btn-danger:hover { background: #b91c1c; color: #fff; }

/* Card du formulaire */
.ce-form-wrap { max-width: 960px; margin: 0 auto; padding: 0 24px; }
.ce-card {
    background: var(--ce-card);
    border: 1px solid var(--ce-border);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06);
    padding: 28px;
    margin-bottom: 20px;
}

.ce-section { margin-bottom: 28px; padding-bottom: 24px; border-bottom: 1px solid var(--ce-border); }
.ce-section:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
.ce-section-title {
    display: flex; align-items: center; gap: 12px;
    font-size: 1rem; font-weight: 700; color: var(--ce-text);
    margin-bottom: 20px;
}
.ce-section-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, var(--ce-blue), var(--ce-blue-2));
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: .9rem;
}

.ce-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}

.ce-field { display: flex; flex-direction: column; gap: 6px; }
.ce-label {
    font-size: .82rem; font-weight: 600; color: var(--ce-text);
    display: flex; align-items: center; gap: 6px;
}
.ce-label i { color: var(--ce-blue); font-size: .8rem; }
.ce-required::after { content: ' *'; color: var(--ce-danger); }

.ce-input, .ce-select {
    border: 1.5px solid var(--ce-border);
    border-radius: 10px;
    padding: 10px 14px;
    font-size: .92rem;
    background: #fff;
    color: var(--ce-text);
    transition: all .18s;
}
.ce-input:focus, .ce-select:focus {
    outline: none;
    border-color: var(--ce-blue);
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.ce-input[readonly] { background: var(--ce-surface); color: var(--ce-muted); cursor: not-allowed; }
.ce-input.is-invalid { border-color: var(--ce-danger); }
.ce-help { font-size: .78rem; color: var(--ce-muted); }
.ce-error { font-size: .78rem; color: var(--ce-danger); margin-top: 4px; }

/* Toggle is_active */
.ce-toggle {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 16px;
    background: var(--ce-surface);
    border: 1px solid var(--ce-border);
    border-radius: var(--ce-radius);
}
.ce-toggle input[type="checkbox"] {
    width: 20px; height: 20px;
    accent-color: var(--ce-blue);
    cursor: pointer;
}
.ce-toggle label {
    margin: 0; cursor: pointer; font-weight: 600; color: var(--ce-text); font-size: .9rem;
    display: flex; align-items: center; gap: 8px;
}
.ce-toggle label i { color: var(--ce-success); }

/* Footer actions */
.ce-actions-bar {
    display: flex; gap: 12px; justify-content: flex-end;
    padding-top: 24px;
    border-top: 1px solid var(--ce-border);
    flex-wrap: wrap;
}

/* Info card */
.ce-info-card {
    background: rgba(4,83,203,.04);
    border: 1px solid rgba(4,83,203,.15);
    border-left: 4px solid var(--ce-blue);
    border-radius: var(--ce-radius);
    padding: 12px 16px;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 12px;
    font-size: .85rem; color: var(--ce-text);
}
.ce-info-card i { color: var(--ce-blue); font-size: 1rem; }

/* Alert */
.ce-alert {
    padding: 14px 18px;
    border-radius: var(--ce-radius);
    margin-bottom: 20px;
    display: flex; gap: 12px;
    font-size: .9rem;
}
.ce-alert-danger { background: rgba(220,38,38,.08); color: var(--ce-danger); border: 1px solid rgba(220,38,38,.2); }
.ce-alert-danger i { font-size: 1.2rem; }

/* Responsive */
@media (max-width: 768px) {
    .ce-header { padding: 24px 20px 20px; border-radius: 0 0 14px 14px; }
    .ce-header h1 { font-size: 1.15rem; }
    .ce-card { padding: 20px; }
    .ce-grid { grid-template-columns: 1fr; gap: 16px; }
    .ce-actions-bar { justify-content: center; }
    .ce-actions-bar .ce-btn { flex: 1; justify-content: center; }
}
</style>
@endsection

@section('content')
<div class="ce-page">

    {{-- ============================================================
         HEADER
    ============================================================= --}}
    <div class="ce-header">
        <div class="ce-header-row">
            <div class="ce-header-left">
                <div class="ce-avatar">{{ strtoupper(substr($caissier->name, 0, 2)) }}</div>
                <div>
                    <h1><i class="fas fa-user-edit me-2"></i>Modifier le Caissier</h1>
                    <p>Modification du profil de <strong>{{ $caissier->name }}</strong></p>
                </div>
            </div>
            <div class="ce-actions">
                <a href="{{ route('esbtp.caissiers.show', $caissier) }}" class="ce-btn ce-btn-glass">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <a href="{{ route('esbtp.personnel.unified.index') }}" class="ce-btn ce-btn-white">
                    <i class="fas fa-list"></i> Liste
                </a>
            </div>
        </div>
    </div>

    <div class="ce-form-wrap">

        @if($errors->any())
        <div class="ce-alert ce-alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Erreurs de validation :</strong>
                <ul style="margin: 6px 0 0 1.2rem;">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        <div class="ce-info-card">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Informations de modification :</strong>
                Cree le {{ $caissier->created_at->format('d/m/Y a H:i') }} —
                Derniere modification le {{ $caissier->updated_at->format('d/m/Y a H:i') }}
            </div>
        </div>

        <form action="{{ route('esbtp.caissiers.update', $caissier) }}" method="POST" id="ceEditForm">
            @csrf
            @method('PUT')

            <div class="ce-card">
                {{-- ===== Section Informations personnelles ===== --}}
                <div class="ce-section">
                    <div class="ce-section-title">
                        <div class="ce-section-icon"><i class="fas fa-user"></i></div>
                        Informations Personnelles
                    </div>

                    <div class="ce-grid">
                        <div class="ce-field">
                            <label for="name" class="ce-label ce-required">
                                <i class="fas fa-user"></i> Nom complet
                            </label>
                            <input type="text"
                                   id="name"
                                   name="name"
                                   class="ce-input @error('name') is-invalid @enderror"
                                   value="{{ old('name', $caissier->name) }}"
                                   placeholder="Nom et prenoms du caissier"
                                   required>
                            @error('name')
                            <span class="ce-error">{{ $message }}</span>
                            @enderror
                            <span class="ce-help">Nom complet tel qu'il apparaitra dans le systeme</span>
                        </div>

                        <div class="ce-field">
                            <label for="email" class="ce-label">
                                <i class="fas fa-envelope"></i> Email
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   class="ce-input @error('email') is-invalid @enderror"
                                   value="{{ old('email', $caissier->email) }}"
                                   placeholder="caissier@etablissement.ci">
                            @error('email')
                            <span class="ce-error">{{ $message }}</span>
                            @enderror
                            <span class="ce-help">Email professionnel (optionnel)</span>
                        </div>

                        <div class="ce-field">
                            <label for="phone" class="ce-label">
                                <i class="fas fa-phone"></i> Telephone
                            </label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   class="ce-input @error('phone') is-invalid @enderror"
                                   value="{{ old('phone', $caissier->phone) }}"
                                   placeholder="+225 07 00 00 00 00">
                            @error('phone')
                            <span class="ce-error">{{ $message }}</span>
                            @enderror
                            <span class="ce-help">Numero de telephone de contact</span>
                        </div>

                        <div class="ce-field">
                            <label for="username" class="ce-label">
                                <i class="fas fa-at"></i> Nom d'utilisateur
                            </label>
                            <input type="text"
                                   id="username"
                                   class="ce-input"
                                   value="{{ $caissier->username }}"
                                   readonly>
                            <span class="ce-help">Le nom d'utilisateur ne peut pas etre modifie</span>
                        </div>
                    </div>
                </div>

                {{-- ===== Section Parametres ===== --}}
                <div class="ce-section">
                    <div class="ce-section-title">
                        <div class="ce-section-icon"><i class="fas fa-cog"></i></div>
                        Parametres du Compte
                    </div>

                    <div class="ce-toggle">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $caissier->is_active) ? 'checked' : '' }}>
                        <label for="is_active">
                            <i class="fas fa-user-check"></i>
                            Compte actif
                        </label>
                    </div>
                    <span class="ce-help" style="display: block; margin-top: 8px;">
                        Decochez pour desactiver temporairement ce caissier sans le supprimer
                    </span>
                </div>

                {{-- ===== Actions ===== --}}
                <div class="ce-actions-bar">
                    <a href="{{ route('esbtp.caissiers.show', $caissier) }}" class="ce-btn ce-btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </a>

                    <button type="button" class="ce-btn ce-btn-danger" data-bs-toggle="modal" data-bs-target="#ceDeleteModal">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>

                    <button type="submit" class="ce-btn ce-btn-primary" id="ceSubmitBtn">
                        <i class="fas fa-save"></i> Mettre a jour
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ================================================================
     MODAL: Confirmation de suppression
================================================================= --}}
<div class="modal fade" id="ceDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 14px; border: none; overflow: hidden;">
            <div class="modal-header" style="background: var(--ce-danger); color: white; border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center" style="padding: 1.5rem;">
                <i class="fas fa-user-times fa-3x mb-3" style="color: var(--ce-danger);"></i>
                <h6>Etes-vous sur de vouloir supprimer ce caissier ?</h6>
                <p style="color: var(--ce-muted); margin: .75rem 0;">
                    <strong>{{ $caissier->name }}</strong><br>
                    Cette action desactivera le compte et retirera le role caissier.
                </p>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ce-border);">
                <button type="button" class="ce-btn ce-btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <form action="{{ route('esbtp.caissiers.destroy', $caissier) }}" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="ce-btn ce-btn-danger">
                        <i class="fas fa-trash"></i> Confirmer
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var form = document.getElementById('ceEditForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        var isValid = true;
        var name = document.getElementById('name');
        var email = document.getElementById('email');

        if (!name.value.trim()) {
            name.classList.add('is-invalid');
            isValid = false;
        } else {
            name.classList.remove('is-invalid');
        }

        if (email.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            email.classList.add('is-invalid');
            isValid = false;
        } else {
            email.classList.remove('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            return false;
        }

        var btn = document.getElementById('ceSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mise a jour...';
        }
    });
})();
</script>
@endpush
