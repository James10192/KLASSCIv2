<div class="bc-input-row" data-mention-row style="align-items: flex-start; margin-top: 8px;">
    <div class="bc-icon"><i class="fas fa-medal"></i></div>
    <div class="bc-body">
        <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
            <label class="form-switch-modern" title="Activer">
                <input type="checkbox" name="mention_rules[{{ $index }}][enabled]" value="1"
                       {{ ($rule['enabled'] ?? true) ? 'checked' : '' }}>
                <span class="slider"></span>
            </label>
            <input type="hidden" name="mention_rules[{{ $index }}][key]" value="{{ $rule['key'] ?? '' }}">
            <input type="text" class="form-control form-control-modern" style="min-width: 160px;"
                   name="mention_rules[{{ $index }}][label]"
                   value="{{ $rule['label'] ?? '' }}" placeholder="Libellé">
            <input type="number" class="form-control form-control-modern" style="max-width: 90px;"
                   name="mention_rules[{{ $index }}][min]"
                   value="{{ $rule['min'] }}" min="0" max="20" step="0.01" placeholder="Min">
            <input type="number" class="form-control form-control-modern" style="max-width: 90px;"
                   name="mention_rules[{{ $index }}][max]"
                   value="{{ $rule['max'] }}" min="0" max="21" step="0.01" placeholder="Max">
            <select class="form-control form-control-modern" style="max-width: 150px;"
                    name="mention_rules[{{ $index }}][source]">
                <option value="moyenne" {{ ($rule['source'] ?? 'moyenne') === 'moyenne' ? 'selected' : '' }}>Moyenne periode</option>
                <option value="conduite" {{ ($rule['source'] ?? '') === 'conduite' ? 'selected' : '' }}>Note conduite</option>
            </select>
            <button type="button" class="btn btn-sm btn-outline-danger" data-mention-remove>Retirer</button>
        </div>
    </div>
</div>
