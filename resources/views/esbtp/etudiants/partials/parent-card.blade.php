@php
    $relation = $parent->pivot->relation ?? 'Autre';
@endphp
<div class="parent-card mb-4" data-parent-index="{{ $index }}" style="background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.08); border: 1px solid #e9ecef;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h6 class="mb-1 text-primary" style="font-weight: 600;">
                <i class="fas fa-user-friends me-2"></i>Parent / Tuteur #{{ $index + 1 }}
            </h6>
            <small class="text-muted">{{ $parent->nom }} {{ $parent->prenoms }}</small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border">{{ $relation }}</span>
            <button type="button" class="btn btn-sm btn-outline-danger remove-parent"
                    data-parent-id="{{ $parent->id }}" title="Retirer ce parent">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <div class="parent-card-body">
        <input type="hidden" name="parents[{{ $index }}][id]" value="{{ $parent->id }}">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Nom <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="parents[{{ $index }}][nom]"
                       value="{{ old('parents.'.$index.'.nom', $parent->nom) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Prénom(s) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="parents[{{ $index }}][prenoms]"
                       value="{{ old('parents.'.$index.'.prenoms', $parent->prenoms) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Relation <span class="text-danger">*</span></label>
                <select class="form-select" name="parents[{{ $index }}][relation]" required>
                    <option value="Père" {{ $relation === 'Père' ? 'selected' : '' }}>Père</option>
                    <option value="Mère" {{ $relation === 'Mère' ? 'selected' : '' }}>Mère</option>
                    <option value="Tuteur" {{ $relation === 'Tuteur' ? 'selected' : '' }}>Tuteur</option>
                    <option value="Autre" {{ $relation === 'Autre' ? 'selected' : '' }}>Autre</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Téléphone <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="parents[{{ $index }}][telephone]"
                       value="{{ old('parents.'.$index.'.telephone', $parent->telephone) }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="parents[{{ $index }}][email]"
                       value="{{ old('parents.'.$index.'.email', $parent->email) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Profession</label>
                <input type="text" class="form-control" name="parents[{{ $index }}][profession]"
                       value="{{ old('parents.'.$index.'.profession', $parent->profession) }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Adresse</label>
                <textarea class="form-control" name="parents[{{ $index }}][adresse]" rows="1">{{ old('parents.'.$index.'.adresse', $parent->adresse) }}</textarea>
            </div>
        </div>
    </div>
</div>
