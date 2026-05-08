@php
    $canViewFull = auth()->user()->can('students.accessibility.view_full');
    $categories = \App\Models\ESBTPStudentAccessibilityProfile::CATEGORIES;
    $accommodations = \App\Models\ESBTPStudentAccessibilityProfile::ACCOMMODATIONS;
    $oldAcc = old('accessibility', []);
    $isOpen = ! empty($oldAcc); // ré-ouvert si validation a échoué et le user avait rempli
@endphp

@once
@push('styles')
<style>
/* ═══════ Namespace ia-* (Inscription Accessibility) — design system KLASSCI ═══════ */
.ia-section {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.ia-toggle-btn {
    width: 100%;
    background: linear-gradient(135deg, rgba(4,83,203,.04), rgba(94,145,222,.03));
    border: none;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: .75rem;
    cursor: pointer;
    transition: background .15s;
    font-size: .92rem;
    color: #1e293b;
    text-align: left;
}
.ia-toggle-btn:hover { background: linear-gradient(135deg, rgba(4,83,203,.08), rgba(94,145,222,.05)); }
.ia-toggle-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    background: linear-gradient(135deg, #0453cb, #5e91de);
    color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: .9rem;
    flex-shrink: 0;
}
.ia-toggle-text { flex: 1; }
.ia-toggle-title { font-weight: 700; color: #1e293b; }
.ia-toggle-sub { font-size: .78rem; color: #64748b; margin-top: 2px; }
.ia-toggle-caret {
    color: #64748b;
    transition: transform .2s;
    font-size: .85rem;
}
.ia-section--open .ia-toggle-caret { transform: rotate(180deg); }

.ia-body {
    display: none;
    padding: 1.5rem;
    border-top: 1px solid #e2e8f0;
}
.ia-section--open .ia-body { display: block; }

.ia-warn {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border: 1px solid #f59e0b;
    border-left: 4px solid #d97706;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 16px;
    font-size: .82rem;
    color: #78350f;
}

.ia-chip-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 8px;
}
.ia-chip {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    cursor: pointer;
    transition: all .2s;
    background: #fff;
}
.ia-chip:hover { border-color: #5e91de; background: #f8fafc; }
.ia-chip input[type="checkbox"] { margin: 0; accent-color: #0453cb; cursor: pointer; }
.ia-chip-label { font-size: .85rem; color: #1e293b; font-weight: 500; }
.ia-chip:has(input:checked) {
    border-color: #0453cb;
    background: linear-gradient(135deg, rgba(4,83,203,.06), rgba(94,145,222,.04));
}
.ia-chip:has(input:checked) .ia-chip-label { color: #0453cb; font-weight: 600; }

.ia-toggle-row {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    background: #f8fafc;
    margin-bottom: 10px;
}
.ia-toggle-row input[type="checkbox"] { accent-color: #0453cb; cursor: pointer; }
.ia-toggle-row label { margin: 0; cursor: pointer; font-weight: 500; color: #1e293b; }

.ia-field-group { margin-bottom: 1rem; }
.ia-label {
    display: block;
    font-size: .82rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 6px;
}
.ia-label-hint { color: #64748b; font-weight: 500; font-size: .75rem; }
.ia-input,
.ia-textarea {
    width: 100%;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    padding: 9px 14px;
    font-size: .88rem;
    transition: border-color .2s, box-shadow .2s;
}
.ia-input:focus,
.ia-textarea:focus {
    outline: none;
    border-color: #0453cb;
    box-shadow: 0 0 0 3px rgba(4,83,203,.1);
}
.ia-help {
    display: block;
    font-size: .75rem;
    color: #64748b;
    margin-top: 4px;
}
.ia-restricted-badge {
    display: inline-block;
    background: #fef3c7;
    color: #78350f;
    padding: 2px 8px;
    border-radius: 50px;
    font-size: .68rem;
    font-weight: 700;
    margin-left: 6px;
    vertical-align: middle;
}

/* Champ numérique premium avec suffixe (pour pourcentage tiers-temps) */
.ia-input-suffix {
    position: relative;
    display: block;
}
.ia-input-suffix .ia-input {
    padding-right: 38px;
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}
.ia-input-suffix__addon {
    position: absolute;
    right: 1px;
    top: 1px;
    bottom: 1px;
    width: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(4,83,203,.08), rgba(94,145,222,.05));
    color: #0453cb;
    font-size: .82rem;
    font-weight: 700;
    border-left: 1.5px solid #e2e8f0;
    border-radius: 0 6.5px 6.5px 0;
    pointer-events: none;
    transition: background .2s, border-color .2s;
}
.ia-input-suffix .ia-input:focus + .ia-input-suffix__addon {
    background: linear-gradient(135deg, rgba(4,83,203,.15), rgba(94,145,222,.1));
    border-left-color: #0453cb;
}
</style>
@endpush
@endonce

<div class="ia-section {{ $isOpen ? 'ia-section--open' : '' }}" id="ia-accessibility-section" data-ia-open="{{ $isOpen ? '1' : '0' }}">
    <button type="button" class="ia-toggle-btn" onclick="iaToggleAccessibility()">
        <div class="ia-toggle-icon"><i class="fas fa-universal-access"></i></div>
        <div class="ia-toggle-text">
            <div class="ia-toggle-title">Profil d'accessibilité <span class="ia-label-hint">(optionnel)</span></div>
            <div class="ia-toggle-sub">Cocher si l'étudiant a un handicap ou nécessite des aménagements pédagogiques. Vous pourrez compléter plus tard depuis sa fiche.</div>
        </div>
        <i class="fas fa-chevron-down ia-toggle-caret"></i>
    </button>

    <div class="ia-body">
        <div class="ia-warn">
            <i class="fas fa-shield-alt me-1"></i>
            Donnée de santé. Toute saisie est tracée dans l'audit log.
            @if(! $canViewFull)
                <span class="d-block mt-1"><i class="fas fa-info-circle me-1"></i> La description médicale détaillée est restreinte à un périmètre supérieur ; vous pouvez saisir le résumé court visible aux enseignants.</span>
            @endif
        </div>

        {{-- Reconnaissance officielle --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="ia-toggle-row">
                    <input type="hidden" name="accessibility[has_official_recognition]" value="0">
                    <input type="checkbox" id="ia-has_official_recognition" name="accessibility[has_official_recognition]" value="1"
                           {{ ! empty($oldAcc['has_official_recognition']) ? 'checked' : '' }}>
                    <label for="ia-has_official_recognition">
                        <i class="fas fa-stamp me-1 text-primary"></i> Reconnaissance officielle (CDPH ou équivalent)
                    </label>
                </div>
            </div>
            <div class="col-md-6 ia-field-group">
                <label class="ia-label">Référence du document officiel</label>
                <input type="text" class="ia-input" name="accessibility[recognition_reference]"
                       value="{{ $oldAcc['recognition_reference'] ?? '' }}"
                       placeholder="N° dossier, attestation..." maxlength="100">
            </div>
        </div>

        {{-- Catégories --}}
        <div class="ia-field-group">
            <label class="ia-label">Catégories</label>
            <div class="ia-chip-grid">
                @foreach($categories as $key => $label)
                    <label class="ia-chip">
                        <input type="checkbox" name="accessibility[categories][]" value="{{ $key }}"
                               {{ in_array($key, (array) ($oldAcc['categories'] ?? [])) ? 'checked' : '' }}>
                        <span class="ia-chip-label">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Description courte (visible enseignants) --}}
        <div class="ia-field-group">
            <label class="ia-label">
                Résumé visible aux enseignants <span class="ia-label-hint">(max 200)</span>
            </label>
            <input type="text" class="ia-input" name="accessibility[short_description]"
                   value="{{ $oldAcc['short_description'] ?? '' }}"
                   placeholder="Ex: Déficience visuelle partielle — supports agrandis"
                   maxlength="200">
            <span class="ia-help">Ce texte apparaît à côté du nom de l'étudiant pour les enseignants.</span>
        </div>

        {{-- Description complète (gated view_full) --}}
        @if($canViewFull)
        <div class="ia-field-group">
            <label class="ia-label">
                Description médicale complète <span class="ia-restricted-badge">Restreint</span>
            </label>
            <textarea class="ia-textarea" name="accessibility[full_description]" rows="3"
                      placeholder="Diagnostic, contexte médical, suivi...">{{ $oldAcc['full_description'] ?? '' }}</textarea>
            <span class="ia-help">Visible uniquement aux rôles avec la permission « voir le détail médical ».</span>
        </div>
        @endif

        {{-- Aménagements --}}
        <div class="ia-field-group">
            <label class="ia-label">Aménagements pédagogiques</label>
            <div class="ia-chip-grid">
                @foreach($accommodations as $key => $label)
                    <label class="ia-chip">
                        <input type="checkbox" name="accessibility[accommodations][]" value="{{ $key }}"
                               {{ in_array($key, (array) ($oldAcc['accommodations'] ?? [])) ? 'checked' : '' }}>
                        <span class="ia-chip-label">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Tiers-temps + assistant --}}
        <div class="row mb-3">
            <div class="col-md-6">
                <div class="ia-toggle-row">
                    <input type="hidden" name="accessibility[requires_third_time]" value="0">
                    <input type="checkbox" id="ia-requires_third_time" name="accessibility[requires_third_time]" value="1"
                           {{ ! empty($oldAcc['requires_third_time']) ? 'checked' : '' }}>
                    <label for="ia-requires_third_time"><i class="fas fa-hourglass-half me-1 text-primary"></i> Tiers-temps aux examens</label>
                </div>
            </div>
            <div class="col-md-3 ia-field-group">
                <label class="ia-label">Pourcentage</label>
                <div class="ia-input-suffix">
                    <input type="number" class="ia-input" name="accessibility[third_time_percentage]" min="0" max="100" step="1"
                           value="{{ $oldAcc['third_time_percentage'] ?? 33 }}">
                    <span class="ia-input-suffix__addon">%</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="ia-toggle-row">
                    <input type="hidden" name="accessibility[assistant_required]" value="0">
                    <input type="checkbox" id="ia-assistant_required" name="accessibility[assistant_required]" value="1"
                           {{ ! empty($oldAcc['assistant_required']) ? 'checked' : '' }}>
                    <label for="ia-assistant_required"><i class="fas fa-hands-helping me-1 text-primary"></i> Assistant requis</label>
                </div>
            </div>
        </div>

        {{-- Notes aménagements (gated view_full) --}}
        @if($canViewFull)
        <div class="ia-field-group">
            <label class="ia-label">
                Notes sur les aménagements <span class="ia-restricted-badge">Restreint</span>
            </label>
            <textarea class="ia-textarea" name="accessibility[accommodations_notes]" rows="2"
                      placeholder="Précisions sur la mise en œuvre des aménagements...">{{ $oldAcc['accommodations_notes'] ?? '' }}</textarea>
        </div>
        @endif

        {{-- Validité --}}
        <div class="row mb-3">
            <div class="col-md-6 ia-field-group">
                <label class="ia-label">Validité du</label>
                <input type="date" class="ia-input" name="accessibility[effective_from]"
                       value="{{ $oldAcc['effective_from'] ?? '' }}">
            </div>
            <div class="col-md-6 ia-field-group">
                <label class="ia-label">au</label>
                <input type="date" class="ia-input" name="accessibility[effective_to]"
                       value="{{ $oldAcc['effective_to'] ?? '' }}">
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
window.iaToggleAccessibility = function () {
    var section = document.getElementById('ia-accessibility-section');
    if (!section) return;
    section.classList.toggle('ia-section--open');
    section.dataset.iaOpen = section.classList.contains('ia-section--open') ? '1' : '0';
};
</script>
@endpush
@endonce
