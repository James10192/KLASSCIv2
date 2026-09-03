            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-flag"></i></div>
                        <div>
                            <h3>En-tête officiel</h3>
                            <p>République et ministère du bulletin LMD.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">République / Ministère</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles" style="margin-bottom:1rem;">
                        <label class="bcfg-toggle" for="lmd_bulletin_show_republic_info">
                            <span class="bcfg-toggle-label">Informations de la République</span>
                            <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_republic_info" name="lmd_bulletin_show_republic_info" value="1"
                                   {{ ($settings['lmd_bulletin_show_republic_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="lmd_bulletin_show_ministry_info">
                            <span class="bcfg-toggle-label">Informations du ministère</span>
                            <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_ministry_info" name="lmd_bulletin_show_ministry_info" value="1"
                                   {{ ($settings['lmd_bulletin_show_ministry_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bcfg-label">Texte République</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_republic_text"
                                   value="{{ $settings['lmd_bulletin_republic_text'] ?? 'REPUBLIQUE DE COTE D\'IVOIRE' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">Devise nationale</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_union_text"
                                   value="{{ $settings['lmd_bulletin_union_text'] ?? 'Union - Discipline - Travail' }}">
                        </div>
                        <div class="col-12">
                            <label class="bcfg-label">Texte Ministère</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_ministry_text"
                                   value="{{ $settings['lmd_bulletin_ministry_text'] ?? 'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-university"></i></div>
                        <div>
                            <h3>Encadré Établissement</h3>
                            <p>Code, statut et direction du relevé LMD.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">Code / Statut / Direction</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles" style="margin-bottom:1rem;">
                        <label class="bcfg-toggle" for="lmd_bulletin_show_etablissement_box">
                            <span class="bcfg-toggle-label">Afficher l'encadré établissement</span>
                            <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_etablissement_box" name="lmd_bulletin_show_etablissement_box" value="1"
                                   {{ ($settings['lmd_bulletin_show_etablissement_box'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="bcfg-label">Code établissement</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_code_etablissement"
                                   value="{{ $settings['lmd_bulletin_code_etablissement'] ?? '' }}" placeholder="Ex: 2720328001">
                        </div>
                        <div class="col-md-4">
                            <label class="bcfg-label">Statut</label>
                            <select class="bcfg-select" name="lmd_bulletin_statut">
                                <option value="Privé" {{ ($settings['lmd_bulletin_statut'] ?? 'Privé') === 'Privé' ? 'selected' : '' }}>Privé</option>
                                <option value="Public" {{ ($settings['lmd_bulletin_statut'] ?? '') === 'Public' ? 'selected' : '' }}>Public</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="bcfg-label">Direction</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_direction"
                                   value="{{ $settings['lmd_bulletin_direction'] ?? '' }}" placeholder="Ex: DOREX">
                            <div class="bcfg-hint">Si vide, utilisera le nom du directeur des paramètres généraux</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <h3>Champs sur le bulletin</h3>
                            <p>Hiérarchie UEMOA affichée sur le relevé.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">Hiérarchie UEMOA</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles" style="margin-bottom:1rem;">
                        <label class="bcfg-toggle" for="lmd_bulletin_show_domaine">
                            <span class="bcfg-toggle-label">Afficher Domaine</span>
                            <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_domaine" name="lmd_bulletin_show_domaine" value="1"
                                   {{ ($settings['lmd_bulletin_show_domaine'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="lmd_bulletin_show_mention">
                            <span class="bcfg-toggle-label">Afficher Mention</span>
                            <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_mention" name="lmd_bulletin_show_mention" value="1"
                                   {{ ($settings['lmd_bulletin_show_mention'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="lmd_bulletin_show_specialite">
                            <span class="bcfg-toggle-label">Afficher Spécialité</span>
                            <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_specialite" name="lmd_bulletin_show_specialite" value="1"
                                   {{ ($settings['lmd_bulletin_show_specialite'] ?? '0') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="lmd_bulletin_show_parcours">
                            <span class="bcfg-toggle-label">Afficher Parcours</span>
                            <input class="form-check-input" type="checkbox" id="lmd_bulletin_show_parcours" name="lmd_bulletin_show_parcours" value="1"
                                   {{ ($settings['lmd_bulletin_show_parcours'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="bcfg-label">Libellé Domaine</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_label_domaine" value="{{ $settings['lmd_bulletin_label_domaine'] ?? 'DOMAINE' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="bcfg-label">Libellé Mention</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_label_mention" value="{{ $settings['lmd_bulletin_label_mention'] ?? 'MENTION' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="bcfg-label">Libellé Spécialité</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_label_specialite" value="{{ $settings['lmd_bulletin_label_specialite'] ?? 'SPÉCIALITÉ' }}">
                        </div>
                        <div class="col-md-3">
                            <label class="bcfg-label">Libellé Parcours</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_label_parcours" value="{{ $settings['lmd_bulletin_label_parcours'] ?? 'PARCOURS' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-file-alt"></i></div>
                        <div>
                            <h3>Textes du bulletin</h3>
                            <p>Notice et pied de page LMD.</p>
                        </div>
                    </div>
                </div>
                <div class="bcfg-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="bcfg-label">Notice importante</label>
                            <textarea class="bcfg-textarea" name="lmd_bulletin_notice_text" rows="2">{{ $settings['lmd_bulletin_notice_text'] ?? \App\Services\LMDBulletinService::NOTICE_DEFAUT }}</textarea>
                        </div>
                        <div class="col-12">
                            <label class="bcfg-label">Texte de pied de page</label>
                            <input type="text" class="bcfg-input" name="lmd_bulletin_bottom_text"
                                   value="{{ $settings['lmd_bulletin_bottom_text'] ?? 'Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.' }}">
                        </div>
                    </div>
                </div>
            </div>
