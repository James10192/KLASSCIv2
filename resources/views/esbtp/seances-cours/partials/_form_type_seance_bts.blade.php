{{-- Radio cards type_seance BTS legacy — 3 options plannables (CM/TD/TP) --}}
{{--
    PR5 chantier emploi-temps-lmd-unification : variante BTS du selecteur sous-type.
    Sister partial : _form_type_seance_lmd.blade.php (6 options UEMOA).
    Inclus conditionnel dans seances-cours/create.blade.php + edit.blade.php selon
    classe.systeme_academique === 'BTS'.

    Rule .claude/rules/type-seance-enum-extension.md
    Rule .claude/rules/blade-alpine-pitfalls.md (Piege #1 : :style override style inline)
--}}
@php
    $currentType = old('type_seance', $seancesCour->type_seance ?? 'CM');
    // 3 types BTS canoniques (pas de PROJET/EXAMEN/RATTRAPAGE/SOUTENANCE qui sont LMD)
    $types = [
        'CM' => ['label' => 'Cours Magistral', 'desc' => 'Cours theorique en classe',     'icon' => 'fa-chalkboard-user'],
        'TD' => ['label' => 'Travaux Diriges', 'desc' => 'Exercices encadres',             'icon' => 'fa-pen-ruler'],
        'TP' => ['label' => 'Travaux Pratiques', 'desc' => 'Manipulation atelier',         'icon' => 'fa-flask-vial'],
    ];
@endphp

<div class="sce-type-seance" x-data="{ value: '{{ $currentType }}' }">
    <input type="hidden" name="type_seance" :value="value">
    <div class="sce-type-radio-group">
        @foreach($types as $code => $cfg)
            <button type="button"
                    class="sce-type-radio sce-type-radio--primary"
                    :class="value === '{{ $code }}' ? 'is-active' : ''"
                    @click="value = '{{ $code }}'"
                    :aria-pressed="value === '{{ $code }}' ? 'true' : 'false'">
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
