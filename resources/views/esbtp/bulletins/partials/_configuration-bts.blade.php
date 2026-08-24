            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-university"></i></div>
                        <div>
                            <h3>Informations de l'établissement</h3>
                            <p>Nom, adresse et signatures visibles sur le bulletin.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">7 champs</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bcfg-label">Nom de l'école</label>
                            <input type="text" class="bcfg-input" name="school_name" value="{{ $settings['school_name'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">Nom personnalisé pour bulletin</label>
                            <input type="text" class="bcfg-input" name="bulletin_school_name_custom" value="{{ $settings['bulletin_school_name_custom'] ?? '' }}" placeholder="Vide = nom par défaut">
                        </div>
                        <div class="col-md-8">
                            <label class="bcfg-label">Adresse</label>
                            <input type="text" class="bcfg-input" name="school_address" value="{{ $settings['school_address'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="bcfg-label">Téléphone</label>
                            <input type="text" class="bcfg-input" name="school_phone" value="{{ $settings['school_phone'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">Email</label>
                            <input type="email" class="bcfg-input" name="school_email" value="{{ $settings['school_email'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">Nom du directeur</label>
                            <input type="text" class="bcfg-input" name="director_name" value="{{ $settings['director_name'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">Titre du directeur</label>
                            <input type="text" class="bcfg-input" name="director_title" value="{{ $settings['director_title'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-flag"></i></div>
                        <div>
                            <h3>En-tête et informations officielles</h3>
                            <p>République, ministère et logo restent configurables.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">4 toggles + 3 champs</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles" style="margin-bottom:1rem;">
                        <label class="bcfg-toggle" for="bulletin_show_header">
                            <span class="bcfg-toggle-label">En-tête complet</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_header" name="bulletin_show_header" value="1" {{ ($settings['bulletin_show_header'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_logo">
                            <span class="bcfg-toggle-label">Logo</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_logo" name="bulletin_show_logo" value="1" {{ ($settings['bulletin_show_logo'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_republic_info">
                            <span class="bcfg-toggle-label">Informations République</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_republic_info" name="bulletin_show_republic_info" value="1" {{ ($settings['bulletin_show_republic_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_ministry_info">
                            <span class="bcfg-toggle-label">Informations Ministère</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_ministry_info" name="bulletin_show_ministry_info" value="1" {{ ($settings['bulletin_show_ministry_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="bcfg-label">Texte République</label>
                            <input type="text" class="bcfg-input" name="bulletin_republic_text" value="{{ $settings['bulletin_republic_text'] ?? '' }}">
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">Devise nationale</label>
                            <input type="text" class="bcfg-input" name="bulletin_union_text" value="{{ $settings['bulletin_union_text'] ?? '' }}">
                        </div>
                        <div class="col-12">
                            <label class="bcfg-label">Texte Ministère</label>
                            <input type="text" class="bcfg-input" name="bulletin_ministry_text" value="{{ $settings['bulletin_ministry_text'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div>
                            <h3>Cycle et formation</h3>
                            <p>Libellé de cycle affiché dans l'en-tête.</p>
                        </div>
                    </div>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles" style="margin-bottom:1rem;">
                        <label class="bcfg-toggle" for="bulletin_show_cycle_info">
                            <span class="bcfg-toggle-label">Afficher les informations du cycle</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_cycle_info" name="bulletin_show_cycle_info" value="1" {{ ($settings['bulletin_show_cycle_info'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="bcfg-label">Nom du cycle</label>
                            <input type="text" class="bcfg-input" name="bulletin_cycle_text" value="{{ $settings['bulletin_cycle_text'] ?? '' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="bcfg-label">Abréviation</label>
                            <input type="text" class="bcfg-input" name="bulletin_cycle_abbreviation" value="{{ $settings['bulletin_cycle_abbreviation'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-table"></i></div>
                        <div>
                            <h3>Tableau des matières et notes</h3>
                            <p>Colonnes visibles dans le tableau officiel.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">5 toggles</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles">
                        <label class="bcfg-toggle" for="bulletin_show_subjects_table">
                            <span class="bcfg-toggle-label">Tableau des matières</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_subjects_table" name="bulletin_show_subjects_table" value="1" {{ ($settings['bulletin_show_subjects_table'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_subject_average">
                            <span class="bcfg-toggle-label">Moyennes par matière</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_subject_average" name="bulletin_show_subject_average" value="1" {{ ($settings['bulletin_show_subject_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_coefficient">
                            <span class="bcfg-toggle-label">Coefficients</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_coefficient" name="bulletin_show_coefficient" value="1" {{ ($settings['bulletin_show_coefficient'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_teachers">
                            <span class="bcfg-toggle-label">Professeurs</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_teachers" name="bulletin_show_teachers" value="1" {{ ($settings['bulletin_show_teachers'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_appreciations">
                            <span class="bcfg-toggle-label">Appréciations</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_appreciations" name="bulletin_show_appreciations" value="1" {{ ($settings['bulletin_show_appreciations'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_appreciation_plain">
                            <span class="bcfg-toggle-label">Appréciations en noir sans couleur de fond</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_appreciation_plain" name="bulletin_appreciation_plain" value="1" {{ ($settings['bulletin_appreciation_plain'] ?? '0') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-chart-bar"></i></div>
                        <div>
                            <h3>Moyennes et statistiques</h3>
                            <p>Rang, effectif, assiduité et décision de conseil.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">10 toggles</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles">
                        <label class="bcfg-toggle" for="bulletin_show_general_average">
                            <span class="bcfg-toggle-label">Moyenne générale</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_general_average" name="bulletin_show_general_average" value="1" {{ ($settings['bulletin_show_general_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_technical_average">
                            <span class="bcfg-toggle-label">Moyenne technique</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_technical_average" name="bulletin_show_technical_average" value="1" {{ ($settings['bulletin_show_technical_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_class_rank">
                            <span class="bcfg-toggle-label">Rang de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_rank" name="bulletin_show_class_rank" value="1" {{ ($settings['bulletin_show_class_rank'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_class_size">
                            <span class="bcfg-toggle-label">Effectif de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_size" name="bulletin_show_class_size" value="1" {{ ($settings['bulletin_show_class_size'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_attendance">
                            <span class="bcfg-toggle-label">Informations d'assiduité</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_attendance" name="bulletin_show_attendance" value="1" {{ ($settings['bulletin_show_attendance'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_attendance_note">
                            <span class="bcfg-toggle-label">Note d'assiduité (bonus/malus)</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_attendance_note" name="bulletin_show_attendance_note" value="1" {{ ($settings['bulletin_show_attendance_note'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_highest_average">
                            <span class="bcfg-toggle-label">Plus forte moyenne</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_highest_average" name="bulletin_show_highest_average" value="1" {{ ($settings['bulletin_show_highest_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_lowest_average">
                            <span class="bcfg-toggle-label">Plus faible moyenne</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_lowest_average" name="bulletin_show_lowest_average" value="1" {{ ($settings['bulletin_show_lowest_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_class_average">
                            <span class="bcfg-toggle-label">Moyenne de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_class_average" name="bulletin_show_class_average" value="1" {{ ($settings['bulletin_show_class_average'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_council_decision">
                            <span class="bcfg-toggle-label">Décision du conseil de classe</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_council_decision" name="bulletin_show_council_decision" value="1" {{ ($settings['bulletin_show_council_decision'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-gavel"></i></div>
                        <div>
                            <h3>Règles BTS par établissement</h3>
                            <p>Pondération, seuils et titre 1 BTS semestre 1. Aucun tenant n'est figé dans le code.</p>
                        </div>
                    </div>
                    <span class="bcfg-card-badge">Pondération et conseil</span>
                </div>
                <div class="bcfg-card-body">
                    <div class="row g-3">
                        @foreach([1 => 'BTS 1', 2 => 'BTS 2'] as $btsYear => $btsLabel)
                            <div class="col-md-6">
                                <label class="bcfg-label">{{ $btsLabel }} · Coefficient semestre 1</label>
                                <input type="number" class="bcfg-input" name="bulletin_bts{{ $btsYear }}_semester1_weight"
                                       value="{{ $settings['bulletin_bts'.$btsYear.'_semester1_weight'] ?? '1' }}" min="0" step="0.01">
                            </div>
                            <div class="col-md-6">
                                <label class="bcfg-label">{{ $btsLabel }} · Coefficient semestre 2</label>
                                <input type="number" class="bcfg-input" name="bulletin_bts{{ $btsYear }}_semester2_weight"
                                       value="{{ $settings['bulletin_bts'.$btsYear.'_semester2_weight'] ?? ($btsYear === 1 ? '2' : '1') }}" min="0" step="0.01">
                            </div>
                        @endforeach

                        <div class="col-md-6">
                            <label class="bcfg-label">BTS 1 · Décision semestre 2</label>
                            <select class="bcfg-select" name="bulletin_bts1_council_mode">
                                @foreach(['manual' => 'Manuel, zone vide', 'threshold' => 'Selon un seuil'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($settings['bulletin_bts1_council_mode'] ?? 'manual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">BTS 1 · Moyenne de décision</label>
                            <select class="bcfg-select" name="bulletin_bts1_council_average_source">
                                <option value="semestre2" {{ ($settings['bulletin_bts1_council_average_source'] ?? 'semestre2') === 'semestre2' ? 'selected' : '' }}>Moyenne du semestre 2 avec assiduité</option>
                                <option value="annual" {{ ($settings['bulletin_bts1_council_average_source'] ?? 'semestre2') === 'annual' ? 'selected' : '' }}>Moyenne annuelle avec assiduité</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="bcfg-label">BTS 1 · Seuil</label>
                            <input type="number" class="bcfg-input" name="bulletin_bts1_council_threshold"
                                   value="{{ $settings['bulletin_bts1_council_threshold'] ?? '10' }}" min="0" max="20" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label class="bcfg-label">BTS 1 · Sous le seuil</label>
                            <input type="text" class="bcfg-input" name="bulletin_bts1_council_below_text"
                                   value="{{ $settings['bulletin_bts1_council_below_text'] ?? 'Redouble la classe' }}">
                        </div>
                        <div class="col-md-4">
                            <label class="bcfg-label">BTS 1 · Au seuil ou au-dessus</label>
                            <input type="text" class="bcfg-input" name="bulletin_bts1_council_at_or_above_text"
                                   value="{{ $settings['bulletin_bts1_council_at_or_above_text'] ?? 'Admis(e) en 2e Année BTS' }}">
                        </div>
                        <div class="col-12">
                            <label class="bcfg-label">Titre du conseil 1 BTS semestre 1</label>
                            <input type="text" class="bcfg-input" name="bulletin_bts1_s1_council_title"
                                   value="{{ $settings['bulletin_bts1_s1_council_title'] ?? 'Décision du conseil de classe' }}">
                            <div class="bcfg-hint">Indépendant du gabarit. Plateau : Appréciation du Conseil de Classe.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">BTS 2 · Décision semestre 2</label>
                            <select class="bcfg-select" name="bulletin_bts2_council_mode">
                                @foreach(['manual' => 'Manuel, zone vide', 'fixed' => 'Texte fixe'] as $value => $label)
                                    <option value="{{ $value }}" {{ ($settings['bulletin_bts2_council_mode'] ?? 'manual') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="bcfg-label">BTS 2 · Texte fixe</label>
                            <input type="text" class="bcfg-input" name="bulletin_bts2_council_fixed_text"
                                   value="{{ $settings['bulletin_bts2_council_fixed_text'] ?? "Redouble en cas d'échec à l'examen du BTS" }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="bcfg-card">
                <div class="bcfg-card-header">
                    <div class="bcfg-section-header">
                        <div class="bcfg-section-icon"><i class="fas fa-signature"></i></div>
                        <div>
                            <h3>Signatures et validation</h3>
                            <p>Cadre directeur et signatures de bas de page.</p>
                        </div>
                    </div>
                </div>
                <div class="bcfg-card-body">
                    <div class="bcfg-toggles">
                        <label class="bcfg-toggle" for="bulletin_show_signatures">
                            <span class="bcfg-toggle-label">Section signatures</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_signatures" name="bulletin_show_signatures" value="1" {{ ($settings['bulletin_show_signatures'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                        <label class="bcfg-toggle" for="bulletin_show_director_signature">
                            <span class="bcfg-toggle-label">Signature du directeur</span>
                            <input class="form-check-input" type="checkbox" id="bulletin_show_director_signature" name="bulletin_show_director_signature" value="1" {{ ($settings['bulletin_show_director_signature'] ?? '1') == '1' ? 'checked' : '' }}>
                        </label>
                    </div>
                </div>
            </div>
