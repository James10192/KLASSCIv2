@php
    $profile = $etudiant->accessibilityProfile;
    $canEdit = auth()->user()->can('students.accessibility.edit');
    $canViewFull = auth()->user()->can('students.accessibility.view_full');
    $categories = \App\Models\ESBTPStudentAccessibilityProfile::CATEGORIES;
    $accommodations = \App\Models\ESBTPStudentAccessibilityProfile::ACCOMMODATIONS;
    $selectedCats = $profile?->categories ?? [];
    $selectedAccs = $profile?->accommodations ?? [];
@endphp

<style>
.acc-chip-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
}
.acc-chip {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s;
    background: #fff;
}
.acc-chip:hover { border-color: #5e91de; background: #f8fafc; }
.acc-chip input[type="checkbox"] { margin: 0; accent-color: #0453cb; cursor: pointer; }
.acc-chip-label { font-size: .85rem; color: #1e293b; font-weight: 500; }
.acc-chip:has(input:checked) {
    border-color: #0453cb;
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(94,145,222,.04));
}
.acc-chip:has(input:checked) .acc-chip-label { color: #0453cb; font-weight: 600; }

.acc-toggle-row {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    margin-bottom: 10px;
}
.acc-toggle-row input[type="checkbox"] { accent-color: #0453cb; cursor: pointer; }
.acc-toggle-row label { margin: 0; cursor: pointer; font-weight: 500; color: #1e293b; }

.acc-warn-banner {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #f59e0b;
    border-left: 4px solid #d97706;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 16px;
    font-size: .82rem;
    color: #78350f;
}
</style>

<div class="se-section" id="accessibility-section">
    <div class="se-section-header">
        <div class="se-section-icon"><i class="fas fa-universal-access"></i></div>
        <div style="flex:1;">
            <div class="se-section-title">Accessibilité &amp; aménagements pédagogiques</div>
            <div class="se-section-desc">Suivi du handicap et adaptations. Données sensibles — visibilité contrôlée par permissions.</div>
        </div>
        @if($profile && $canEdit)
            <button type="button" class="btn btn-sm btn-outline-danger" id="acc-delete-btn" data-route="{{ route('esbtp.etudiants.accessibility.destroy', $etudiant) }}" title="Supprimer le profil">
                <i class="fas fa-trash"></i>
            </button>
        @endif
    </div>
    <div class="se-section-body">

        <div class="acc-warn-banner">
            <i class="fas fa-shield-alt me-1"></i>
            Donnée de santé. Toute modification est tracée dans l'audit log.
            @if(! $canViewFull)
                <span class="d-block mt-1"><i class="fas fa-info-circle me-1"></i> Vous voyez uniquement le résumé. La description médicale complète est restreinte.</span>
            @endif
        </div>

        <form action="{{ route('esbtp.etudiants.accessibility.store', $etudiant) }}" method="POST" id="acc-form">
            @csrf

            {{-- Reconnaissance officielle --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="acc-toggle-row">
                        <input type="hidden" name="has_official_recognition" value="0">
                        <input type="checkbox" id="has_official_recognition" name="has_official_recognition" value="1"
                               {{ $profile?->has_official_recognition ? 'checked' : '' }}
                               {{ ! $canEdit ? 'disabled' : '' }}>
                        <label for="has_official_recognition">
                            <i class="fas fa-stamp me-1 text-primary"></i> Reconnaissance officielle (CDPH ou équivalent)
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Référence du document officiel</label>
                    <input type="text" class="form-control" name="recognition_reference"
                           value="{{ old('recognition_reference', $profile?->recognition_reference) }}"
                           placeholder="N° dossier, attestation..." maxlength="100"
                           {{ ! $canEdit ? 'readonly' : '' }}>
                </div>
            </div>

            {{-- Catégories --}}
            <div class="mb-3">
                <label class="form-label">Catégories</label>
                <div class="acc-chip-grid">
                    @foreach($categories as $key => $label)
                        <label class="acc-chip">
                            <input type="checkbox" name="categories[]" value="{{ $key }}"
                                   {{ in_array($key, $selectedCats) ? 'checked' : '' }}
                                   {{ ! $canEdit ? 'disabled' : '' }}>
                            <span class="acc-chip-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Description courte (visible enseignants) --}}
            <div class="mb-3">
                <label class="form-label">
                    Résumé visible aux enseignants <span class="text-muted">(max 200)</span>
                </label>
                <input type="text" class="form-control" name="short_description"
                       value="{{ old('short_description', $profile?->short_description) }}"
                       placeholder="Ex: Déficience visuelle partielle — supports agrandis"
                       maxlength="200" {{ ! $canEdit ? 'readonly' : '' }}>
                <small class="form-text text-muted">Ce texte apparaît à côté du nom de l'étudiant pour les enseignants.</small>
            </div>

            {{-- Description complète (gated) --}}
            @if($canViewFull)
                <div class="mb-3">
                    <label class="form-label">
                        Description médicale complète <span class="badge bg-warning text-dark ms-1">Restreint</span>
                    </label>
                    <textarea class="form-control" name="full_description" rows="3"
                              placeholder="Diagnostic, contexte médical, suivi..."
                              {{ ! $canEdit ? 'readonly' : '' }}>{{ old('full_description', $profile?->full_description) }}</textarea>
                    <small class="form-text text-muted">Visible uniquement aux rôles avec la permission « voir le détail médical ».</small>
                </div>
            @endif

            {{-- Aménagements --}}
            <div class="mb-3">
                <label class="form-label">Aménagements pédagogiques</label>
                <div class="acc-chip-grid">
                    @foreach($accommodations as $key => $label)
                        <label class="acc-chip">
                            <input type="checkbox" name="accommodations[]" value="{{ $key }}"
                                   {{ in_array($key, $selectedAccs) ? 'checked' : '' }}
                                   {{ ! $canEdit ? 'disabled' : '' }}>
                            <span class="acc-chip-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Tiers-temps + assistant --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="acc-toggle-row">
                        <input type="hidden" name="requires_third_time" value="0">
                        <input type="checkbox" id="requires_third_time" name="requires_third_time" value="1"
                               {{ $profile?->requires_third_time ? 'checked' : '' }}
                               {{ ! $canEdit ? 'disabled' : '' }}>
                        <label for="requires_third_time"><i class="fas fa-hourglass-half me-1 text-primary"></i> Tiers-temps aux examens</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pourcentage</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="third_time_percentage" min="0" max="100"
                               value="{{ old('third_time_percentage', $profile?->third_time_percentage ?? 33) }}"
                               {{ ! $canEdit ? 'readonly' : '' }}>
                        <span class="input-group-text">%</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="acc-toggle-row">
                        <input type="hidden" name="assistant_required" value="0">
                        <input type="checkbox" id="assistant_required" name="assistant_required" value="1"
                               {{ $profile?->assistant_required ? 'checked' : '' }}
                               {{ ! $canEdit ? 'disabled' : '' }}>
                        <label for="assistant_required"><i class="fas fa-hands-helping me-1 text-primary"></i> Assistant requis</label>
                    </div>
                </div>
            </div>

            {{-- Notes aménagements (gated) --}}
            @if($canViewFull)
                <div class="mb-3">
                    <label class="form-label">
                        Notes sur les aménagements <span class="badge bg-warning text-dark ms-1">Restreint</span>
                    </label>
                    <textarea class="form-control" name="accommodations_notes" rows="2"
                              placeholder="Précisions sur la mise en œuvre des aménagements..."
                              {{ ! $canEdit ? 'readonly' : '' }}>{{ old('accommodations_notes', $profile?->accommodations_notes) }}</textarea>
                </div>
            @endif

            {{-- Validité --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Validité du</label>
                    <input type="date" class="form-control" name="effective_from"
                           value="{{ old('effective_from', $profile?->effective_from?->format('Y-m-d')) }}"
                           {{ ! $canEdit ? 'readonly' : '' }}>
                </div>
                <div class="col-md-6">
                    <label class="form-label">au</label>
                    <input type="date" class="form-control" name="effective_to"
                           value="{{ old('effective_to', $profile?->effective_to?->format('Y-m-d')) }}"
                           {{ ! $canEdit ? 'readonly' : '' }}>
                </div>
            </div>

            @if($canEdit)
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> {{ $profile ? 'Mettre à jour' : 'Enregistrer' }} le profil
                    </button>
                </div>
            @endif

            @if($profile)
                <div class="text-muted mt-2" style="font-size:.75rem;">
                    Dernière mise à jour : {{ $profile->updated_at->format('d/m/Y H:i') }}
                    @if($profile->updatedBy) par {{ $profile->updatedBy->name }} @endif
                </div>
            @endif
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var btn = document.getElementById('acc-delete-btn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        if (!confirm('Supprimer définitivement le profil d\'accessibilité de cet étudiant ?')) return;
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = btn.dataset.route;
        var token = document.createElement('input');
        token.type = 'hidden'; token.name = '_token'; token.value = '{{ csrf_token() }}';
        var method = document.createElement('input');
        method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';
        form.appendChild(token); form.appendChild(method);
        document.body.appendChild(form);
        form.submit();
    });
})();
</script>
@endpush
