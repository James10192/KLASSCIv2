{{-- Picker matière conditionnel LMD vs BTS pour /esbtp/seances-cours/create --}}
{{-- Recoit : $matieres (collection planificationData), $emploiTemps --}}
{{-- LMD : <x-sce-ecue-picker> avec recherche + groupement par UE (premium custom) --}}
{{-- BTS : <select> natif stylé .sce-matiere-select (preserve JS legacy data-* hooks) --}}
@php
    // Detection LMD inclusive : systeme_academique OR niveau.type (cf hotfix PR17.1 create.blade)
    $isLmd = ($emploiTemps->classe->systeme_academique ?? '') === 'LMD'
        || in_array($emploiTemps->classe->niveau->type ?? '', ['Licence', 'Master', 'Doctorat'], true);
@endphp

<div class="form-group">
    <label for="matiere_id" class="sce-form-label">
        Matière <span class="text-danger">*</span>
        @if($isLmd)
            <span class="sce-form-label-chip"><i class="fas fa-university"></i>LMD — ECUE enrichie</span>
        @endif
    </label>

    @if($isLmd)
        {{-- Custom picker premium avec recherche + groupement UE --}}
        <x-sce-ecue-picker
            name="matiere_id"
            :matieres="$matieres"
            :value="old('matiere_id')"
            :required="true"
            placeholder="Rechercher une ECUE par code, nom ou UE…"
            onchange-js="updateTeachersForSubject();" />
    @else
        {{-- Mode BTS : select natif stylé --}}
        <div class="sce-select-wrap">
            <i class="fas fa-graduation-cap sce-select-icon"></i>
            <select name="matiere_id" id="matiere_id"
                    class="sce-matiere-select @error('matiere_id') is-invalid @enderror"
                    onchange="updateTeachersForSubject()"
                    required>
                <option value="">Sélectionner une matière...</option>
                @foreach($matieres as $matiere)
                    @php
                        $m = $matiere['matiere'];
                        $heuresRestantes = $matiere['heures_restantes_formatted'] ?? $matiere['heures_restantes'];
                        $volumeTotal = $matiere['volume_horaire_total_formatted'] ?? $matiere['volume_horaire_total'];
                        $optLabel = $m->name . '   (' . $heuresRestantes . ' restantes / ' . $volumeTotal . ')';
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
    @endif

    <div id="matiere-info" class="sce-form-info" style="display: none;">
        <i class="fas fa-clock"></i>
        <span id="heures-restantes-text"></span>
    </div>
    @error('matiere_id')
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>
