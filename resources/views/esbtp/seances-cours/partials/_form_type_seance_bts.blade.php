{{-- Radio cards type_seance BTS — 2 groupes selon le top-type (Cours vs Devoir) --}}
{{--
    Chantier type_seance Cours/Devoir (juin 2026) : le sous-type affiche depend du top-type :
      - Cours  (topType === 'course')   -> modalites d'enseignement : CM / TD / TP
      - Devoir (topType === 'homework') -> types d'evaluation : Examen / Controle continu / Rattrapage
    Un seul <input name="type_seance"> ; bascule de groupe pilotee par l'evenement
    'session-type-changed' dispatch par selectSessionType() (cf create.blade.php).
    Aucun nouveau case d'enum : on reutilise EXAMEN/PARTIEL/RATTRAPAGE.

    Sister partial : _form_type_seance_lmd.blade.php (sets UEMOA enrichis).
    Rule .claude/rules/type-seance-enum-extension.md
    Rule .claude/rules/blade-alpine-pitfalls.md (pas de {{ }} dans un object-literal Alpine)
--}}
@php
    $rawCurrent = old('type_seance');
    if ($rawCurrent === null && isset($seancesCour) && $seancesCour && $seancesCour->type_seance) {
        $rawCurrent = $seancesCour->type_seance instanceof \App\Enums\TypeSeance
            ? $seancesCour->type_seance->value
            : (string) $seancesCour->type_seance;
    }
    $currentType = $rawCurrent ?: 'CM';

    // Cours : modalites d'enseignement BTS
    $teachingTypes = [
        'CM' => ['label' => 'Cours Magistral',   'desc' => 'Cours theorique en classe', 'icon' => 'fa-chalkboard-user', 'tone' => 'primary'],
        'TD' => ['label' => 'Travaux Diriges',    'desc' => 'Exercices encadres',        'icon' => 'fa-pen-ruler',       'tone' => 'primary'],
        'TP' => ['label' => 'Travaux Pratiques',  'desc' => 'Manipulation atelier',      'icon' => 'fa-flask-vial',      'tone' => 'primary'],
    ];

    // Devoir : types d'evaluation BTS (genere une note)
    $evalTypes = [
        'EXAMEN'     => ['label' => 'Examen',           'desc' => 'Devoir surveille / examen', 'icon' => 'fa-file-pen',     'tone' => 'primary'],
        'PARTIEL'    => ['label' => 'Controle continu', 'desc' => 'Controle de mi-parcours',   'icon' => 'fa-file-lines',   'tone' => 'accent'],
        'RATTRAPAGE' => ['label' => 'Rattrapage',       'desc' => 'Session de rattrapage',     'icon' => 'fa-rotate-right', 'tone' => 'muted'],
    ];
@endphp

<div class="sce-type-seance"
     data-teach-default="CM"
     data-eval-default="EXAMEN"
     data-teach-set="CM,TD,TP"
     data-eval-set="EXAMEN,PARTIEL,RATTRAPAGE"
     data-initial="{{ $currentType }}"
     x-data="{
        value: '',
        topType: 'course',
        teachDefault: '', evalDefault: '',
        teachSet: [], evalSet: [],
        init() {
            const d = this.$el.dataset;
            this.teachDefault = d.teachDefault;
            this.evalDefault = d.evalDefault;
            this.teachSet = (d.teachSet || '').split(',');
            this.evalSet = (d.evalSet || '').split(',');
            const st = document.getElementById('sessionType');
            this.topType = st ? st.value : 'course';
            this.value = d.initial || (this.topType === 'homework' ? this.evalDefault : this.teachDefault);
            document.addEventListener('session-type-changed', (e) => {
                this.topType = e.detail;
                if (this.topType === 'homework') {
                    if (!this.evalSet.includes(this.value)) this.value = this.evalDefault;
                } else if (this.topType === 'course') {
                    if (!this.teachSet.includes(this.value)) this.value = this.teachDefault;
                }
            });
        }
     }">
    <input type="hidden" name="type_seance" :value="value">

    {{-- Groupe Cours : modalites d'enseignement --}}
    <div class="sce-type-radio-group" x-show="topType === 'course'" x-cloak>
        @foreach($teachingTypes as $code => $cfg)
            <button type="button"
                    class="sce-type-radio sce-type-radio--{{ $cfg['tone'] }}"
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

    {{-- Groupe Devoir : types d'evaluation --}}
    <div class="sce-type-radio-group" x-show="topType === 'homework'" x-cloak>
        @foreach($evalTypes as $code => $cfg)
            <button type="button"
                    class="sce-type-radio sce-type-radio--{{ $cfg['tone'] }}"
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
