@extends('layouts.app')

@section('title', 'Modifier le niveau d\'études')

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
.ne-form-actions { display:flex; justify-content:space-between; gap:.6rem; padding:1rem 1.5rem; background:#f8fafc; border-top:1px solid #f1f5f9; border-radius:0 0 14px 14px; flex-wrap:wrap; align-items:center; }
.ne-form-actions-right { display:flex; gap:.6rem; flex-wrap:wrap; }
.ne-meta-footer { padding:.75rem 1.5rem; background:#f8fafc; border:1px solid #f1f5f9; border-radius:10px; font-size:.78rem; color:#64748b; display:flex; gap:1.5rem; flex-wrap:wrap; margin-top:1rem; }
.ne-meta-footer span { display:inline-flex; align-items:center; gap:.4rem; }
.ne-warn-banner { background:#fff7ed; border:1px solid #fed7aa; border-radius:12px; padding:.95rem 1.1rem; margin-bottom:1.25rem; display:flex; gap:.75rem; align-items:flex-start; }
.ne-warn-banner i { color:#b45309; font-size:1.1rem; flex-shrink:0; margin-top:.1rem; }
.ne-warn-banner strong { color:#7c2d12; display:block; font-size:.88rem; margin-bottom:.15rem; }
.ne-warn-banner span { color:#9a3412; font-size:.82rem; }
</style>
@endpush

@section('content')
<div class="main-content">

<div class="dashboard-header">
    <div class="header-left">
        <h1><i class="fas fa-edit me-2" style="color:#0453cb;"></i>Modifier le niveau</h1>
        <p class="header-subtitle">{{ $niveauxEtude->name }}</p>
    </div>
    <div class="header-actions">
        <a href="{{ route('esbtp.niveaux-etudes.show', $niveauxEtude) }}" class="btn-acasi secondary">
            <i class="fas fa-eye"></i> Voir
        </a>
        <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

@if(is_null($niveauxEtude->type))
<div class="ne-warn-banner">
    <i class="fas fa-exclamation-triangle"></i>
    <div>
        <strong>Type de formation manquant</strong>
        <span>Ce niveau n'a pas de type assigné. Sélectionne-le ci-dessous (BTS, Licence, Master, etc.) pour corriger l'inconsistance et permettre son utilisation par les modules de planning et bulletin.</span>
    </div>
</div>
@endif

@if($errors->any())
<div class="alert alert-dismissible fade show mb-3" style="background:rgba(220,38,38,.08); border:1px solid #dc2626; border-radius:12px; padding:.85rem 1rem;" role="alert">
    <i class="fas fa-exclamation-circle" style="color:#dc2626;"></i>
    <strong style="color:#991b1b; margin-left:.4rem;">Veuillez corriger les erreurs ci-dessous.</strong>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('esbtp.niveaux-etudes.update', $niveauxEtude) }}" id="ne-edit-form">
    @csrf
    @method('PUT')

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
                    <input type="text" name="name" id="ne-name" value="{{ old('name', $niveauxEtude->name) }}"
                           class="ne-input @error('name') is-invalid @enderror"
                           placeholder="Ex: Première année Licence" required>
                    @error('name')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
                </div>
                <div class="ne-field">
                    <label for="ne-code" class="ne-label">Code<span class="req">*</span></label>
                    <input type="text" name="code" id="ne-code" value="{{ old('code', $niveauxEtude->code) }}"
                           class="ne-input @error('code') is-invalid @enderror"
                           placeholder="Ex: L1" required maxlength="50">
                    @error('code')<div class="ne-error"><i class="fas fa-exclamation-circle"></i>{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="ne-field">
                <label for="ne-libelle" class="ne-label">Libellé court</label>
                <input type="text" name="libelle" id="ne-libelle" value="{{ old('libelle', $niveauxEtude->libelle) }}"
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
                        :value="old('type', $niveauxEtude->type)"
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
                        :value="old('niveau', $niveauxEtude->year)"
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
                          placeholder="Description détaillée du niveau d'études (optionnel)">{{ old('description', $niveauxEtude->description) }}</textarea>
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
                               {{ old('is_active', $niveauxEtude->is_active) ? 'checked' : '' }} style="width:2.5em; height:1.4em; cursor:pointer;">
                    </div>
                </div>
            </div>
        </div>
        <div class="ne-form-actions">
            <button type="button" class="btn-acasi danger" data-bs-toggle="modal" data-bs-target="#ne-delete-modal">
                <i class="fas fa-trash"></i> Supprimer
            </button>
            <div class="ne-form-actions-right">
                <a href="{{ route('esbtp.niveaux-etudes.index') }}" class="btn-acasi secondary">Annuler</a>
                <button type="submit" class="btn-acasi primary">
                    <i class="fas fa-check"></i> Enregistrer les modifications
                </button>
            </div>
        </div>
    </div>
</form>

<div class="ne-meta-footer">
    <span><i class="fas fa-calendar-plus" style="color:#0453cb;"></i> Créé le {{ optional($niveauxEtude->created_at)->format('d/m/Y à H:i') ?: '—' }}</span>
    <span><i class="fas fa-history" style="color:#0453cb;"></i> Modifié le {{ optional($niveauxEtude->updated_at)->format('d/m/Y à H:i') ?: '—' }}</span>
</div>

{{-- Modal suppression --}}
<div class="modal fade" id="ne-delete-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; border:none; overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg, #dc2626, #ef4444); color:#fff; border:none; padding:1.1rem 1.5rem;">
                <h5 class="modal-title" style="font-weight:700; display:flex; align-items:center; gap:.55rem;">
                    <i class="fas fa-exclamation-triangle"></i> Confirmer la suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:1.25rem 1.5rem;">
                <p style="margin:0 0 .5rem; color:#1e293b;">Êtes-vous sûr de vouloir supprimer le niveau <strong>{{ $niveauxEtude->name }}</strong> ?</p>
                <p style="margin:0; color:#64748b; font-size:.85rem;">Cette action est irréversible. La suppression échouera si le niveau est lié à des classes, filières ou matières.</p>
            </div>
            <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #f1f5f9; padding:.85rem 1.5rem;">
                <button type="button" class="btn-acasi secondary" data-bs-dismiss="modal">Annuler</button>
                <form method="POST" action="{{ route('esbtp.niveaux-etudes.destroy', $niveauxEtude) }}" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-acasi danger">
                        <i class="fas fa-trash"></i> Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

</div>
@endsection
