@extends('layouts.app')

@section('title', 'Créer un niveau d\'études')

@push('styles')
<style>
.ne-form-card { background:#fff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 1px 3px rgba(15,23,42,.04), 0 1px 2px rgba(15,23,42,.06); margin-bottom:1.25rem; }
.ne-form-card-head { padding:1rem 1.5rem .85rem; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:.7rem; }
.ne-form-icon { width:38px; height:38px; border-radius:10px; background:linear-gradient(135deg, #0453cb, #3b7ddb); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.92rem; flex-shrink:0; }
.ne-form-card-head h5 { margin:0; font-size:.98rem; font-weight:700; color:#1e293b; }
.ne-form-card-head p { margin:.1rem 0 0; font-size:.78rem; color:#64748b; }
.ne-form-body { padding:1.25rem 1.5rem; }
.ne-field { margin-bottom:1.1rem; }
.ne-field:last-child { margin-bottom:0; }
.ne-label { display:block; font-size:.82rem; font-weight:600; color:#1e293b; margin-bottom:.4rem; }
.ne-label .req { color:#dc2626; margin-left:.15rem; }
.ne-input, .ne-textarea { width:100%; padding:.62rem .85rem; border:1px solid #e2e8f0; border-radius:10px; font-size:.88rem; color:#1e293b; background:#fff; transition:all .15s; }
.ne-input:focus, .ne-textarea:focus { outline:none; border-color:#0453cb; box-shadow:0 0 0 3px rgba(4,83,203,.12); }
.ne-input.is-invalid, .ne-textarea.is-invalid { border-color:#dc2626; }
.ne-help { font-size:.74rem; color:#64748b; margin-top:.3rem; }
.ne-error { font-size:.76rem; color:#dc2626; margin-top:.3rem; display:flex; align-items:center; gap:.3rem; }
.ne-toggle-row { display:flex; align-items:center; gap:.85rem; padding:.85rem 1rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; }
.ne-toggle-text { flex:1; }
.ne-toggle-text strong { font-size:.85rem; color:#1e293b; display:block; }
.ne-toggle-text small { font-size:.74rem; color:#64748b; }
.ne-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
@@media (max-width: 768px) {
    .ne-form-grid { grid-template-columns:1fr; }
    .ne-form-body { padding:1rem; }
}
.ne-form-actions { display:flex; justify-content:flex-end; gap:.6rem; padding:1rem 1.5rem; background:#f8fafc; border-top:1px solid #f1f5f9; border-radius:0 0 14px 14px; flex-wrap:wrap; }
</style>
@endpush

@section('content')
<div class="main-content">

<div class="dashboard-header">
    <div class="header-left">
        <h1><i class="fas fa-plus-circle me-2" style="color:#0453cb;"></i>Nouveau niveau d'études</h1>
        <p class="header-subtitle">Configurer un nouveau niveau ou cycle d'études</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-dismissible fade show mb-3" style="background:rgba(220,38,38,.08); border:1px solid #dc2626; border-radius:12px; padding:.85rem 1rem;" role="alert">
    <i class="fas fa-exclamation-circle" style="color:#dc2626;"></i>
    <strong style="color:#991b1b; margin-left:.4rem;">Veuillez corriger les erreurs ci-dessous.</strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('esbtp.niveaux-etudes.store') }}" id="ne-create-form">
    @csrf

    {{-- Section 1 : Identité --}}
    <div class="ne-form-card">
        <div class="ne-form-card-head">
            <div class="ne-form-icon"><i class="fas fa-id-card"></i></div>
            <div>
                <h5>Identité</h5>
                <p>Nom, code et libellé du niveau d'études</p>
            </div>
        </div>
        <div class="ne-form-body">
            <div class="ne-form-grid">
                <div class="ne-field">
                    <label for="ne-name" class="ne-label">Nom du niveau<span class="req">*</span></label>
                    <input type="text" name="name" id="ne-name" value="{{ old('name') }}"
                           class="ne-input @error('name') is-invalid @enderror"
                           placeholder="Ex: Première année Licence" required>
                    @error('name')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
                </div>
                <div class="ne-field">
                    <label for="ne-code" class="ne-label">Code<span class="req">*</span></label>
                    <input type="text" name="code" id="ne-code" value="{{ old('code') }}"
                           class="ne-input @error('code') is-invalid @enderror"
                           placeholder="Ex: L1" required maxlength="50">
                    <div class="ne-help">Auto-rempli à partir du nom — modifiable</div>
                    @error('code')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="ne-field">
                <label for="ne-libelle" class="ne-label">Libellé court</label>
                <input type="text" name="libelle" id="ne-libelle" value="{{ old('libelle') }}"
                       class="ne-input @error('libelle') is-invalid @enderror"
                       placeholder="Ex: L1 LMD" maxlength="255">
                <div class="ne-help">Affiché dans les listes courtes (optionnel)</div>
                @error('libelle')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Section 2 : Classification --}}
    <div class="ne-form-card">
        <div class="ne-form-card-head">
            <div class="ne-form-icon"><i class="fas fa-layer-group"></i></div>
            <div>
                <h5>Classification</h5>
                <p>Type de formation et année dans le cycle</p>
            </div>
        </div>
        <div class="ne-form-body">
            <div class="ne-form-grid">
                <div class="ne-field">
                    <label for="ne-type" class="ne-label">Type de formation<span class="req">*</span></label>
                    <x-au-select
                        name="type"
                        :value="old('type')"
                        icon="fa-tag"
                        placeholder="Sélectionner un type"
                        :searchable="false"
                        :options="['BTS' => 'BTS', 'Bachelor' => 'Bachelor', 'Licence' => 'Licence', 'Master' => 'Master', 'Doctorat' => 'Doctorat', 'Diplôme' => 'Diplôme', 'Certificat' => 'Certificat']" />
                    @error('type')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
                </div>
                <div class="ne-field">
                    <label for="ne-niveau" class="ne-label">Année dans le cycle<span class="req">*</span></label>
                    <x-au-select
                        name="niveau"
                        :value="old('niveau')"
                        icon="fa-graduation-cap"
                        placeholder="Sélectionner une année"
                        :searchable="false"
                        :options="['1' => 'Année 1', '2' => 'Année 2', '3' => 'Année 3', '4' => 'Année 4', '5' => 'Année 5', '6' => 'Année 6', '7' => 'Année 7']" />
                    @error('niveau')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Section 3 : Description et statut --}}
    <div class="ne-form-card">
        <div class="ne-form-card-head">
            <div class="ne-form-icon"><i class="fas fa-info-circle"></i></div>
            <div>
                <h5>Description et statut</h5>
                <p>Détails complémentaires et activation</p>
            </div>
        </div>
        <div class="ne-form-body">
            <div class="ne-field">
                <label for="ne-description" class="ne-label">Description</label>
                <textarea name="description" id="ne-description" rows="3"
                          class="ne-textarea @error('description') is-invalid @enderror"
                          placeholder="Description détaillée du niveau d'études (optionnel)">{{ old('description') }}</textarea>
                @error('description')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
            </div>
            <div class="ne-field">
                <div class="ne-toggle-row">
                    <div class="ne-toggle-text">
                        <strong>Niveau actif</strong>
                        <small>Disponible pour les inscriptions et les classes</small>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="is_active" id="ne-is-active" value="1"
                               {{ old('is_active', '1') ? 'checked' : '' }} style="width:2.5em; height:1.4em; cursor:pointer;">
                    </div>
                </div>
            </div>
        </div>
        <div class="ne-form-actions">
            <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary">Annuler</a>
            <button type="submit" class="btn-acasi primary">
                <i class="fas fa-check"></i> Créer le niveau
            </button>
        </div>
    </div>
</form>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var nameInput = document.getElementById('ne-name');
    var codeInput = document.getElementById('ne-code');
    var codeManuallyEdited = false;

    if (codeInput.value && codeInput.value.length > 0) {
        codeManuallyEdited = true;
    }

    codeInput.addEventListener('input', function() {
        codeManuallyEdited = true;
    });

    nameInput.addEventListener('input', function() {
        if (!codeManuallyEdited) {
            var auto = (nameInput.value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 6);
            codeInput.value = auto;
        }
    });
});
</script>
@endpush
@endsection
