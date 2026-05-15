{{-- Radio cards type_seance LMD UEMOA — 6 options plannables (TPE exclus car metadonnee ECUE) --}}
@php
    $currentType = old('type_seance', 'CM');
    // Pattern UEMOA : 3 tones monochrome bleu (rule premium-redesign)
    // primary = presentiel principal (CM/TD/TP), accent = projet/examen (a part), muted = autre
    $types = [
        'CM'     => ['label' => 'Cours Magistral',    'desc' => 'Theorique en amphi avec professeur',   'icon' => 'fa-chalkboard-user', 'tone' => 'primary'],
        'TD'     => ['label' => 'Travaux Diriges',    'desc' => 'Exercices encadres par enseignant',     'icon' => 'fa-pen-ruler',       'tone' => 'primary'],
        'TP'     => ['label' => 'Travaux Pratiques',  'desc' => 'Manipulation en laboratoire',           'icon' => 'fa-flask-vial',      'tone' => 'primary'],
        'PROJET' => ['label' => 'Projet',             'desc' => 'Suivi de projet en presentiel',         'icon' => 'fa-diagram-project', 'tone' => 'accent'],
        'EXAMEN' => ['label' => 'Examen',             'desc' => 'Evaluation — genere une note auto',     'icon' => 'fa-file-pen',        'tone' => 'accent'],
        'AUTRE'  => ['label' => 'Autre',              'desc' => 'Autre seance en presentiel',            'icon' => 'fa-ellipsis',        'tone' => 'muted'],
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
    <div class="sce-tpe-info">
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
