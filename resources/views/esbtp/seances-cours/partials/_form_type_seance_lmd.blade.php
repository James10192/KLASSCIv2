{{-- Radio cards type_seance LMD (CM/TD/TP) — visible uniquement si niveau LMD --}}
{{-- Recoit : aucun (utilise $emploiTemps + old() global de Blade) --}}
@php
    $currentType = old('type_seance', 'CM');
    $types = [
        'CM' => ['label' => 'Cours Magistral', 'desc' => 'Cours theorique en amphi', 'icon' => 'fa-chalkboard-user', 'tone' => 'primary'],
        'TD' => ['label' => 'Travaux Diriges', 'desc' => 'Exercices encadres en TD', 'icon' => 'fa-pen-ruler', 'tone' => 'accent'],
        'TP' => ['label' => 'Travaux Pratiques', 'desc' => 'Manipulation en laboratoire', 'icon' => 'fa-flask-vial', 'tone' => 'muted'],
    ];
@endphp

<div class="sce-type-seance" x-data="{ value: '{{ $currentType }}' }">
    <input type="hidden" name="type_seance" :value="value">
    <div class="sce-type-radio-group">
        @foreach($types as $code => $cfg)
            <button type="button"
                    class="sce-type-radio sce-type-radio--{{ $cfg['tone'] }}"
                    :class="value === '{{ $code }}' ? 'is-active' : ''"
                    @click="value = '{{ $code }}'"
                    aria-pressed="value === '{{ $code }}' ? 'true' : 'false'">
                <div class="sce-type-radio-icon"><i class="fas {{ $cfg['icon'] }}"></i></div>
                <div class="sce-type-radio-body">
                    <div class="sce-type-radio-label">{{ $code }}</div>
                    <div class="sce-type-radio-name">{{ $cfg['label'] }}</div>
                    <div class="sce-type-radio-desc">{{ $cfg['desc'] }}</div>
                </div>
                <i class="fas fa-check-circle sce-type-radio-check" x-show="value === '{{ $code }}'" x-cloak></i>
            </button>
        @endforeach
    </div>
    @error('type_seance')
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>
