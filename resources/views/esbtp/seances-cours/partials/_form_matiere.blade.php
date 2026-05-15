{{-- Picker matière conditionnel LMD vs BTS pour /esbtp/seances-cours/create --}}
{{-- Recoit : $matieres (collection planificationData), $emploiTemps --}}
{{-- NOTE : on garde le <select> natif (JS legacy data-* attributes utilise pour update teacher select). --}}
{{-- Le stylise via .sce-matiere-select pour un look premium tout en preservant les hooks JS. --}}
@php
    $isLmd = ($emploiTemps->classe->systeme_academique ?? '') === 'LMD';
@endphp

<div class="form-group">
    <label for="matiere_id" class="sce-form-label">
        Matière <span class="text-danger">*</span>
        @if($isLmd)
            <span class="sce-form-label-chip"><i class="fas fa-university"></i>LMD — ECUE enrichie</span>
        @endif
    </label>

    <div class="sce-select-wrap">
        <i class="fas fa-graduation-cap sce-select-icon"></i>
        <select name="matiere_id" id="matiere_id"
                class="sce-matiere-select @error('matiere_id') is-invalid @enderror"
                onchange="updateTeachersForSubject()"
                required>
            <option value="">{{ $isLmd ? 'Rechercher une ECUE...' : 'Sélectionner une matière...' }}</option>
            @foreach($matieres as $matiere)
                @php
                    $m = $matiere['matiere'];
                    $heuresRestantes = $matiere['heures_restantes_formatted'] ?? $matiere['heures_restantes'];
                    $volumeTotal = $matiere['volume_horaire_total_formatted'] ?? $matiere['volume_horaire_total'];

                    // Label enrichi LMD vs BTS
                    if ($isLmd) {
                        $code = $m->code ?: '';
                        $ue = $m->uniteEnseignement ?? null;
                        $ueLabel = '';
                        if ($ue) {
                            $type = $ue->type_ue?->label() ?? 'UE';
                            $ueLabel = '  ·  ' . $type . ' · ' . ($ue->code ?? $ue->name);
                        }
                        $optLabel = ($code ? $code . ' — ' : '') . $m->name . $ueLabel . '   (' . $heuresRestantes . '/' . $volumeTotal . ')';
                    } else {
                        $optLabel = $m->name . '   (' . $heuresRestantes . ' restantes / ' . $volumeTotal . ')';
                    }
                @endphp
                <option value="{{ $m->id }}"
                        data-heures-restantes="{{ $matiere['heures_restantes'] }}"
                        data-heures-restantes-formatted="{{ $matiere['heures_restantes_formatted'] ?? $matiere['heures_restantes'] }}"
                        data-volume-total="{{ $matiere['volume_horaire_total'] }}"
                        data-volume-total-formatted="{{ $matiere['volume_horaire_total_formatted'] ?? $matiere['volume_horaire_total'] }}"
                        data-enseignants="{{ ($matiere['enseignants_selectables'] ?? collect())->pluck('id')->toJson() }}"
                        data-planification-id="{{ $matiere['planification_id'] ?? '' }}"
                        {{ old('matiere_id') == $m->id ? 'selected' : '' }}>
                    {{ $optLabel }}
                </option>
            @endforeach
        </select>
        <i class="fas fa-chevron-down sce-select-caret"></i>
    </div>

    <div id="matiere-info" class="sce-form-info" style="display: none;">
        <i class="fas fa-clock"></i>
        <span id="heures-restantes-text"></span>
    </div>
    @error('matiere_id')
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>
