@php
    $lmdGet = static fn (string $key, $default = '') => \App\Helpers\SettingsHelper::get($key, $default);
    $lmdFontFields = \App\Support\LMDBulletinPrintSettings::fontFields();
    $lmdLayoutFields = \App\Support\LMDBulletinPrintSettings::layoutFields();
@endphp

<div class="bcfg-card">
    <div class="bcfg-card-header">
        <div class="bcfg-section-header">
            <div class="bcfg-section-icon"><i class="fas fa-flag"></i></div>
            <div><h3>En-tête officiel</h3><p>République et ministère du bulletin LMD.</p></div>
        </div>
        <span class="bcfg-card-badge">République / Ministère</span>
    </div>
    <div class="bcfg-card-body">
        <div class="bcfg-toggles" style="margin-bottom:1rem;">
            <label class="bcfg-toggle" for="lmd_bulletin_show_republic_info">
                <span class="bcfg-toggle-label">Informations de la République</span>
                <input type="hidden" name="lmd_bulletin_show_republic_info_present" value="1">
                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_republic_info" name="lmd_bulletin_show_republic_info" value="1" {{ $lmdGet('lmd_bulletin_show_republic_info','1') == '1' ? 'checked' : '' }}>
            </label>
            <label class="bcfg-toggle" for="lmd_bulletin_show_ministry_info">
                <span class="bcfg-toggle-label">Informations du ministère</span>
                <input type="hidden" name="lmd_bulletin_show_ministry_info_present" value="1">
                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_ministry_info" name="lmd_bulletin_show_ministry_info" value="1" {{ $lmdGet('lmd_bulletin_show_ministry_info','1') == '1' ? 'checked' : '' }}>
            </label>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><label class="bcfg-label">Fond zone République / Ministère</label><input type="color" class="form-control form-control-color" name="lmd_bulletin_official_header_bg" value="{{ $lmdGet('lmd_bulletin_official_header_bg','#ffffff') }}"></div>
            <div class="col-md-4"><label class="bcfg-label">Couleur République / Ministère</label><input type="color" class="form-control form-control-color" name="lmd_bulletin_header_label_color" value="{{ $lmdGet('lmd_bulletin_header_label_color','#1f2937') }}"></div>
            <div class="col-md-4"><label class="bcfg-label">Couleur libellés année / niveau / édition</label><input type="color" class="form-control form-control-color" name="lmd_bulletin_meta_label_color" value="{{ $lmdGet('lmd_bulletin_meta_label_color','#1f2937') }}"></div>
            @foreach(['lmd_bulletin_header_label_bold'=>'République en gras','lmd_bulletin_ministry_bold'=>'Ministère en gras','lmd_bulletin_meta_label_bold'=>'Libellés année / édition / niveau / semestre en gras'] as $option=>$label)
                <div class="col-md-4"><label class="bcfg-toggle" for="{{ $option }}"><span class="bcfg-toggle-label">{{ $label }}</span><input type="hidden" name="{{ $option }}_present" value="1"><input class="form-check-input" type="checkbox" name="{{ $option }}" id="{{ $option }}" value="1" {{ $lmdGet($option,'0') == '1' ? 'checked' : '' }}></label></div>
            @endforeach
            <div class="col-md-6"><label class="bcfg-label">Texte République</label><input type="text" class="bcfg-input" name="lmd_bulletin_republic_text" value="{{ $lmdGet('lmd_bulletin_republic_text', "REPUBLIQUE DE COTE D'IVOIRE") }}"></div>
            <div class="col-md-6"><label class="bcfg-label">Devise nationale</label><input type="text" class="bcfg-input" name="lmd_bulletin_union_text" value="{{ $lmdGet('lmd_bulletin_union_text','Union - Discipline - Travail') }}"></div>
            <div class="col-12"><label class="bcfg-label">Texte Ministère</label><input type="text" class="bcfg-input" name="lmd_bulletin_ministry_text" value="{{ $lmdGet('lmd_bulletin_ministry_text',"MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE") }}"></div>
        </div>
    </div>
</div>

<div class="bcfg-card">
    <div class="bcfg-card-header">
        <div class="bcfg-section-header">
            <div class="bcfg-section-icon"><i class="fas fa-university"></i></div>
            <div><h3>Établissement</h3><p>Code, statut et direction du bulletin LMD.</p></div>
        </div>
        <span class="bcfg-card-badge">Code / Statut / Direction</span>
    </div>
    <div class="bcfg-card-body">
        <div class="bcfg-toggles" style="margin-bottom:1rem;">
            <label class="bcfg-toggle" for="lmd_bulletin_show_etablissement_box">
                <span class="bcfg-toggle-label">Afficher l'encadré établissement</span>
                <input type="hidden" name="lmd_bulletin_show_etablissement_box_present" value="1">
                <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_etablissement_box" name="lmd_bulletin_show_etablissement_box" value="1" {{ $lmdGet('lmd_bulletin_show_etablissement_box','1') == '1' ? 'checked' : '' }}>
            </label>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><label class="bcfg-label">Code établissement</label><input type="text" class="bcfg-input" name="lmd_bulletin_code_etablissement" value="{{ $lmdGet('lmd_bulletin_code_etablissement','') }}" placeholder="Ex: 2720328001"></div>
            <div class="col-md-4"><label class="bcfg-label">Statut</label><select class="bcfg-select" name="lmd_bulletin_statut"><option value="Privé" {{ $lmdGet('lmd_bulletin_statut','Privé') === 'Privé' ? 'selected' : '' }}>Privé</option><option value="Public" {{ $lmdGet('lmd_bulletin_statut','Privé') === 'Public' ? 'selected' : '' }}>Public</option></select></div>
            <div class="col-md-4"><label class="bcfg-label">Direction affichée</label><input type="text" class="bcfg-input" name="lmd_bulletin_direction" value="{{ $lmdGet('lmd_bulletin_direction','') }}" placeholder="Ex: Direction des Études"><div class="bcfg-hint">Texte du bandeau, distinct du nom du directeur signataire.</div></div>
        </div>
    </div>
</div>

<div class="bcfg-card">
    <div class="bcfg-card-header">
        <div class="bcfg-section-header">
            <div class="bcfg-section-icon"><i class="fas fa-ruler-combined"></i></div>
            <div><h3>Mise en page du PDF LMD</h3><p>Logo, hauteur du bandeau 50/50 et espace de signature.</p></div>
        </div>
        <span class="bcfg-card-badge">Dimensions en px</span>
    </div>
    <div class="bcfg-card-body">
        <div class="row g-3">
            @foreach($lmdLayoutFields as $key=>$field)
                <div class="col-md-6 col-xl-3">
                    <label class="bcfg-label">{{ $field['label'] }}</label>
                    <input type="number" class="bcfg-input" name="{{ $key }}" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}" value="{{ $lmdGet($key,$field['default']) }}">
                    <div class="bcfg-hint">{{ $field['hint'] }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="bcfg-card">
    <div class="bcfg-card-header">
        <div class="bcfg-section-header">
            <div class="bcfg-section-icon"><i class="fas fa-font"></i></div>
            <div><h3>Typographie par zone</h3><p>Chaque valeur agit directement sur la zone nommée du PDF LMD.</p></div>
        </div>
        <span class="bcfg-card-badge">6 à 32 px</span>
    </div>
    <div class="bcfg-card-body">
        <div class="row g-3">
            @foreach($lmdFontFields as $key=>$field)
                <div class="col-md-6 col-xl-4">
                    <label class="bcfg-label">{{ $field['label'] }}</label>
                    <input type="number" class="bcfg-input" name="{{ $key }}" min="6" max="32" step="0.5" value="{{ $lmdGet($key,$field['default']) }}">
                </div>
            @endforeach
        </div>
        <div class="bcfg-hint" style="margin-top:.75rem;">La densité des longues listes UE/ECUE reste automatique : KLASSCI resserre les marges de ligne, pas la taille de police que vous avez choisie.</div>
    </div>
</div>

<div class="bcfg-card">
    <div class="bcfg-card-header">
        <div class="bcfg-section-header">
            <div class="bcfg-section-icon"><i class="fas fa-id-card"></i></div>
            <div><h3>Champs sur le bulletin</h3><p>Hiérarchie UEMOA affichée sur le relevé.</p></div>
        </div>
        <span class="bcfg-card-badge">Hiérarchie UEMOA</span>
    </div>
    <div class="bcfg-card-body">
        <div class="bcfg-toggles" style="margin-bottom:1rem;">
            @foreach(['domaine'=>'Domaine','mention'=>'Mention','specialite'=>'Spécialité','parcours'=>'Parcours','effectif'=>'Effectif','redoublant'=>'Redoublant'] as $key=>$label)
                <label class="bcfg-toggle" for="lmd_bulletin_show_{{ $key }}">
                    <span class="bcfg-toggle-label">Afficher {{ $label }}</span>
                    <input type="hidden" name="lmd_bulletin_show_{{ $key }}_present" value="1">
                    <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_{{ $key }}" name="lmd_bulletin_show_{{ $key }}" value="1" {{ $lmdGet('lmd_bulletin_show_'.$key,$key==='specialite'?'0':'1') == '1' ? 'checked' : '' }}>
                </label>
            @endforeach
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="bcfg-label">Parcours automatique</label>
                <select class="bcfg-select" name="lmd_bulletin_parcours_auto"><option value="1" {{ $lmdGet('lmd_bulletin_parcours_auto','1') == '1' ? 'selected' : '' }}>Oui</option><option value="0" {{ $lmdGet('lmd_bulletin_parcours_auto','1') != '1' ? 'selected' : '' }}>Non</option></select>
            </div>
            @foreach(['domaine'=>'Domaine','mention'=>'Mention','specialite'=>'Spécialité','parcours'=>'Parcours'] as $key=>$label)
                <div class="col-md-4"><label class="bcfg-label">Libellé {{ $label }}</label><input type="text" class="bcfg-input" name="lmd_bulletin_label_{{ $key }}" value="{{ $lmdGet('lmd_bulletin_label_'.$key,$key==='specialite'?'SPÉCIALITÉ':'') }}" placeholder="{{ $label }}"></div>
            @endforeach
        </div>
    </div>
</div>

<div class="bcfg-card">
    <div class="bcfg-card-header">
        <div class="bcfg-section-header">
            <div class="bcfg-section-icon"><i class="fas fa-file-alt"></i></div>
            <div><h3>Textes du bulletin</h3><p>Notice et pied de page LMD.</p></div>
        </div>
    </div>
    <div class="bcfg-card-body">
        <div class="row g-3">
            <div class="col-12"><label class="bcfg-label">Notice importante</label><textarea class="bcfg-textarea" name="lmd_bulletin_notice_text" rows="2">{{ $lmdGet('lmd_bulletin_notice_text', \App\Services\LMDBulletinService::NOTICE_DEFAUT) }}</textarea></div>
            <div class="col-md-6"><label class="bcfg-label">Pied de page sur une seule ligne</label><select class="bcfg-select" name="lmd_bulletin_bottom_single_line"><option value="1" {{ $lmdGet('lmd_bulletin_bottom_single_line','1') == '1' ? 'selected' : '' }}>Oui (police adaptée)</option><option value="0" {{ $lmdGet('lmd_bulletin_bottom_single_line','1') == '0' ? 'selected' : '' }}>Non (deux lignes)</option></select></div>
            <div class="col-12"><label class="bcfg-label">Texte de pied de page</label><input type="text" class="bcfg-input" name="lmd_bulletin_bottom_text" value="{{ $lmdGet('lmd_bulletin_bottom_text','Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.') }}"></div>
        </div>
    </div>
</div>
