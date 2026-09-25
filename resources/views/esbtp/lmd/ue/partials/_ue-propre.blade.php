{{-- Champ « UE propre à un parcours » du modal UE. Styles au plus près du
     champ : la page n'est pas chargée en AJAX, le style s'applique une fois. --}}
<style>
    .lu-propre-check { display: flex; align-items: flex-start; gap: .65rem; padding: .75rem .9rem; border: 1.5px solid #e2e8f0; border-radius: 10px; cursor: pointer; background: #fff; margin: 0; transition: border-color .2s ease, background .2s ease; }
    .lu-propre-check:has(input:checked) { border-color: #0453cb; background: rgba(4,83,203,.04); }
    .lu-propre-check input { margin-top: .2rem; accent-color: #0453cb; width: 16px; height: 16px; flex-shrink: 0; }
    .lu-propre-check strong { display: block; font-size: .88rem; color: #1e293b; }
    .lu-propre-check small { display: block; font-size: .76rem; color: #64748b; line-height: 1.45; margin-top: .15rem; }
    .lu-propre-champs { margin-top: .75rem; }
    .lu-propre-edition { display: flex; gap: .5rem; align-items: flex-start; margin-top: .6rem; font-size: .78rem; color: #475569; }
    .lu-propre-edition i { color: #0453cb; margin-top: .15rem; }
    .lu-propre-field { display: flex; flex-direction: column; min-width: 0; }
    .lu-propre-field .au-select, .lu-propre-field .au-select-trigger { max-width: 100%; min-width: 0; }
    .lu-propre-info { display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem; border-radius: 10px; background: rgba(4,83,203,.06); border: 1px solid rgba(4,83,203,.18); color: #1e293b; font-size: .82rem; margin-top: .75rem; }
    .lu-propre-info i { color: #0453cb; }
    .lu-propre-badge { display: inline-flex; align-items: center; gap: .25rem; margin-left: .35rem; padding: .08rem .4rem; border-radius: 5px; font-size: .62rem; font-weight: 700; background: rgba(4,83,203,.08); color: #0453cb; border: 1px solid rgba(4,83,203,.2); vertical-align: middle; }
</style>
{{-- UE propre à un parcours : même code qu'une UE d'un autre parcours,
     mais un autre enseignement (USAT : AGR2103 animale / végétale).
     Un relevé ne montre qu'un parcours : le code n'y est jamais ambigu. --}}
<div class="lu-field-group" id="ue_propre_group">
    <div class="lu-field-group-title"><i class="fas fa-circle"></i> Même code qu'une UE d'un autre parcours ?</div>
    <label class="lu-propre-check" for="ue_propre">
        <input type="checkbox" id="ue_propre" name="propre_au_parcours" value="1">
        <span>
            <strong>Cette UE est propre à un parcours</strong>
            <small>Cochez-la si un autre parcours utilise déjà ce code pour une UE différente. Chaque parcours garde son intitulé et ses ECUE ; le relevé imprime le code tel que vous le saisissez.</small>
        </span>
    </label>
    <div class="lu-propre-edition" id="ue_propre_edition" style="display:none;">
        <i class="fas fa-info-circle"></i>
        <span>L'UE devient propre à <strong>son parcours actuel</strong> : elle ne doit plus servir qu'à lui. Vous pourrez ensuite redonner à ses ECUE leurs codes officiels.</span>
    </div>
    @php
        $_optionsSemestres = collect(range(1, 10))->mapWithKeys(fn ($n) => [$n => 'Semestre ' . $n])->all();
    @endphp
    <div class="lu-field-row lu-propre-champs" id="ue_propre_champs" style="display:none;">
        <div class="lu-propre-field">
            <label><i class="fas fa-route"></i> Parcours <span class="text-danger">*</span></label>
            <x-au-select class="lu-au-full" id="ue_propre_parcours" name="parcours_id"
                placeholder="Choisir le parcours" icon="fa-route"
                :searchable="count($_optionsParcours) > 8" :options="$_optionsParcours" />
        </div>
        <div class="lu-propre-field">
            <label><i class="fas fa-calendar-alt"></i> Semestre <span class="text-danger">*</span></label>
            <x-au-select class="lu-au-full" id="ue_propre_semestre" name="semestre"
                placeholder="Choisir le semestre" :options="$_optionsSemestres" />
        </div>
    </div>
</div>
<div class="lu-propre-info" id="ue_propre_info" style="display:none;">
    <i class="fas fa-lock"></i>
    <span>UE propre au parcours <strong id="ue_propre_info_code"></strong> : son code est imprimé tel quel, sans conflit avec l'UE de même code d'un autre parcours.</span>
</div>
