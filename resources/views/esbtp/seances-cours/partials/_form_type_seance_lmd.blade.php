{{-- Radio cards type_seance LMD UEMOA — 2 groupes selon le top-type (Cours vs Devoir) --}}
{{--
    Chantier type_seance Cours/Devoir (juin 2026) :
      - Cours  (topType === 'course')   -> enseignement : CM / TD / TP / Projet / Autre
      - Devoir (topType === 'homework') -> evaluation : Examen / Partiel (CC) / Rattrapage / Soutenance
    EXAMEN/PARTIEL/RATTRAPAGE/SOUTENANCE quittent la liste Cours -> ils sont des evaluations.
    Un seul <input name="type_seance"> ; bascule pilotee par l'evenement 'session-type-changed'.
    TPE reste exclu du form (metadonnee ECUE — standards UEMOA Apogee/Cocktail).

    Sister partial : _form_type_seance_bts.blade.php
    Rule .claude/rules/type-seance-enum-extension.md
    Rule .claude/rules/blade-alpine-pitfalls.md (pas de {{ }} dans un object-literal Alpine)
--}}
@php
    $currentType = old('type_seance', 'CM');

    // Cours : enseignement (3 tones monochrome bleu — rule premium-redesign)
    $teachingTypes = [
        'CM'     => ['label' => 'Cours Magistral',   'desc' => 'Theorique en amphi avec professeur', 'icon' => 'fa-chalkboard-user', 'tone' => 'primary'],
        'TD'     => ['label' => 'Travaux Diriges',   'desc' => 'Exercices encadres par enseignant',  'icon' => 'fa-pen-ruler',       'tone' => 'primary'],
        'TP'     => ['label' => 'Travaux Pratiques', 'desc' => 'Manipulation en laboratoire',        'icon' => 'fa-flask-vial',      'tone' => 'primary'],
        'PROJET' => ['label' => 'Projet',            'desc' => 'Suivi de projet en presentiel',      'icon' => 'fa-diagram-project', 'tone' => 'accent'],
        'AUTRE'  => ['label' => 'Autre',             'desc' => 'Autre seance en presentiel',         'icon' => 'fa-ellipsis',        'tone' => 'muted'],
    ];

    // Devoir : evaluations UEMOA (genere une note)
    $evalTypes = [
        'EXAMEN'     => ['label' => 'Examen',         'desc' => 'Examen terminal — genere une note', 'icon' => 'fa-file-pen',         'tone' => 'primary'],
        'PARTIEL'    => ['label' => 'Partiel (CC)',   'desc' => 'Controle continu mi-semestre',      'icon' => 'fa-file-lines',       'tone' => 'accent'],
        'RATTRAPAGE' => ['label' => 'Rattrapage',     'desc' => 'Session de rattrapage (2e session)', 'icon' => 'fa-rotate-right',    'tone' => 'accent'],
        'SOUTENANCE' => ['label' => 'Soutenance',     'desc' => 'Soutenance memoire / projet',       'icon' => 'fa-microphone-lines', 'tone' => 'muted'],
    ];
@endphp

<div class="sce-type-seance"
     data-teach-default="CM"
     data-eval-default="EXAMEN"
     data-teach-set="CM,TD,TP,PROJET,AUTRE"
     data-eval-set="EXAMEN,PARTIEL,RATTRAPAGE,SOUTENANCE"
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

    {{-- Groupe Cours : enseignement --}}
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

    {{-- Groupe Devoir : evaluations UEMOA --}}
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

    {{-- Info TPE : retire du form, c'est une metadonnee de l'ECUE --}}
    <div class="sce-tpe-info" x-show="topType === 'course'" x-cloak>
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>Travail Personnel Etudiant (TPE)</strong> non planifiable en emploi du temps.
            C'est un volume theorique alloue par ECUE (configurable dans la
            <a href="{{ route('esbtp.lmd.ue.index', array_filter(['parcours_id' => $emploiTemps->classe->parcours_id, 'niveau_id' => $emploiTemps->classe->niveau_etude_id])) }}">maquette pedagogique LMD</a>),
            que l'etudiant gere lui-meme. Standard UEMOA — cf Apogee, Cocktail.
        </div>
    </div>

    @error('type_seance')
        <div class="form-error">{{ $message }}</div>
    @enderror
</div>
