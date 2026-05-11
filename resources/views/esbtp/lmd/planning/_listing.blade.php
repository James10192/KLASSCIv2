@if($parcours->isEmpty())
    <div class="lp-empty">
        <div class="lp-empty-icon"><i class="fas fa-route"></i></div>
        <h3>Aucun parcours configuré</h3>
        <p>Pour utiliser le planning LMD, commencez par configurer au moins un domaine, une mention et un parcours.</p>
        <a href="{{ route('esbtp.lmd.parcours-domain.index') }}" class="lp-empty-cta"><i class="fas fa-plus"></i> Configurer un parcours</a>
    </div>
@elseif(!$parcoursSelected)
    <div class="lp-empty">
        <div class="lp-empty-icon"><i class="fas fa-hand-pointer"></i></div>
        <h3>Sélectionnez un parcours</h3>
        <p>Choisissez un parcours dans le filtre ci-dessus pour voir sa maquette pédagogique.</p>
    </div>
@elseif($rows->isEmpty())
    <div class="lp-empty">
        <div class="lp-empty-icon"><i class="fas fa-cubes"></i></div>
        <h3>Aucune UE liée à ce parcours</h3>
        <p>Le parcours <strong>{{ $parcoursSelected->name }}</strong> n'a pas encore d'unités d'enseignement associées.</p>
        <button type="button"
            class="lp-empty-cta"
            data-parcours-id="{{ $parcoursSelected->id }}"
            data-parcours-name="{{ $parcoursSelected->name }}"
            onclick="window.dispatchEvent(new CustomEvent('lpm:open', { detail: { parcoursId: parseInt(this.dataset.parcoursId, 10), parcoursName: this.dataset.parcoursName } }))">
            <i class="fas fa-link"></i> Lier des UE au parcours
        </button>
    </div>
@else
    <div class="lp-card">
        <div class="lp-card-header">
            <h2 class="lp-card-title"><span class="lp-card-title-icon"><i class="fas fa-book"></i></span>{{ $parcoursSelected->name }}</h2>
            <div class="lp-card-actions">
                <span class="lp-card-meta">{{ $rows->count() }} UE · {{ $kpis['ecue_count'] }} ECUE</span>
                <button type="button"
                    class="lp-empty-cta lp-empty-cta-sm"
                    data-parcours-id="{{ $parcoursSelected->id }}"
                    data-parcours-name="{{ $parcoursSelected->name }}"
                    onclick="window.dispatchEvent(new CustomEvent('lpm:open', { detail: { parcoursId: parseInt(this.dataset.parcoursId, 10), parcoursName: this.dataset.parcoursName } }))">
                    <i class="fas fa-edit"></i> Modifier les UE
                </button>
            </div>
        </div>
        @php $bulkEnabled = auth()->user()?->can('lmd.planning.edit') && $filters['niveau_id'] && $filters['semestre']; @endphp
        <div style="overflow-x: auto;">
            <table class="lp-table" id="lpListing">
                <thead><tr>
                    @if($bulkEnabled)
                        <th class="lpb-check-cell">
                            <input type="checkbox" class="lpb-check lpb-check-all" title="Tout selectionner / desselectionner" aria-label="Tout selectionner">
                        </th>
                    @endif
                    <th style="width: 35%;">Intitulé</th><th>Type</th>
                    <th class="lp-th-num lp-col-cm lp-th-tip" title="Cours Magistraux — Heures théoriques en grand groupe">CM</th>
                    <th class="lp-th-num lp-col-td lp-th-tip" title="Travaux Dirigés — Heures pratiques en petit groupe">TD</th>
                    <th class="lp-th-num lp-col-tp lp-th-tip" title="Travaux Pratiques — Heures en laboratoire / atelier">TP</th>
                    <th class="lp-th-num lp-col-projet lp-th-tip" title="Heures dédiées au projet pédagogique">Projet</th>
                    <th class="lp-th-num lp-col-tpe lp-th-tip" title="Travail Personnel Étudiant — Travail individuel hors cours">TPE</th>
                    <th class="lp-th-num lp-col-total lp-th-tip" title="Volume horaire total = CM + TD + TP + Projet + TPE">Total</th>
                    <th class="lp-th-num lp-col-cect lp-th-tip" title="Crédits ECTS UEMOA">CECT</th>
                    <th>Enseignant</th>
                </tr></thead>
                <tbody>
                    @foreach($rows as $idx => $row)
                        @php $ue = $row['ue']; $hasCode = !empty($ue->code); $typeLabel = $ue->type_ue?->label() ?? '—'; @endphp
                        <tr class="lp-ue-row js-ue-row" data-idx="{{ $idx }}">
                            @if($bulkEnabled)<td class="lpb-check-cell"></td>@endif
                            <td>
                                <span class="lp-ue-caret js-ue-caret"><i class="fas fa-chevron-right"></i></span>
                                @if($hasCode)<span class="lp-ue-code">{{ $ue->code }}</span>@else<span class="lp-ue-code lp-ue-code-virtual">virtuelle</span>@endif
                                {{ $ue->name }}
                            </td>
                            <td><span class="lp-type-chip">{{ $typeLabel }}</span></td>
                            <td colspan="5" class="lp-volume">{{ $row['ecues']->count() }} ECUE</td>
                            <td class="lp-volume lp-volume-total lp-col-total">—</td>
                            <td class="lp-volume lp-volume-total lp-col-cect">{{ $row['cect'] }}</td>
                            <td><span class="lp-no-planif">UE</span></td>
                        </tr>
                        @foreach($row['ecues'] as $entry)
                            @php
                                $ecue = $entry['ecue'];
                                $planif = $entry['planif'];
                                $canEdit = auth()->user()?->can('lmd.planning.edit') && $filters['niveau_id'] && $filters['semestre'];
                                $teacherName = $planif?->enseignantPrincipal?->name;
                                $teacherId = $planif?->enseignant_principal_id;
                                $ecueLabel = trim((!empty($ecue->code) ? $ecue->code . ' · ' : '') . $ecue->name);
                            @endphp
                            <tr class="lp-ecue-row js-ecue-row" data-parent-idx="{{ $idx }}" style="display:none;">
                                @if($bulkEnabled)
                                    <td class="lpb-check-cell">
                                        <input type="checkbox"
                                               class="lpb-check"
                                               data-lpb-ecue-id="{{ $ecue->id }}"
                                               data-lpb-ecue-label="{{ $ecueLabel }}"
                                               aria-label="Selectionner {{ $ecueLabel }}">
                                    </td>
                                @endif
                                <td class="lp-ecue-indent">
                                    @if(!empty($ecue->code))<span class="lp-ecue-code">{{ $ecue->code }}</span>@endif
                                    {{ $ecue->name }}
                                </td>
                                <td>—</td>
                                @foreach(['cm', 'td', 'tp', 'projet', 'tpe'] as $vol)
                                    @php $val = $planif?->{'volume_horaire_'.$vol} ?? 0; @endphp
                                    @if($canEdit)
                                        <td class="lp-volume lp-col-{{ $vol }} lpe-cell @if(!$val) lp-volume-zero @endif"
                                            data-lpe-ecue-id="{{ $ecue->id }}"
                                            data-lpe-field="volume_horaire_{{ $vol }}"
                                            data-lpe-value="{{ $val }}"
                                            x-data="lpeCell()"
                                            @click="startEdit()"
                                            :class="{ 'lpe-cell--editing': editing, 'lpe-cell--saving': saving }">
                                            <span x-show="!editing" x-text="displayValue"></span>
                                            <input x-show="editing" x-cloak x-ref="input" type="number"
                                                   min="0" max="500" class="lpe-input"
                                                   x-model="value"
                                                   @blur="commit()"
                                                   @keydown.enter.prevent="commit()"
                                                   @keydown.escape.prevent="cancel()"
                                                   @click.stop>
                                        </td>
                                    @else
                                        <td class="lp-volume lp-col-{{ $vol }} @if(!$val) lp-volume-zero @endif">{{ $val }}</td>
                                    @endif
                                @endforeach
                                <td class="lp-volume lp-volume-total lp-col-total">{{ $planif?->volume_horaire_total ?? 0 }}</td>
                                @php $credit = $planif?->credits_ects ?? $ecue->credit_ecue; @endphp
                                @if($canEdit)
                                    <td class="lp-volume lp-col-cect lpe-cell @if(!$credit) lp-volume-zero @endif"
                                        data-lpe-ecue-id="{{ $ecue->id }}"
                                        data-lpe-field="credits_ects"
                                        data-lpe-value="{{ $credit ?? '' }}"
                                        x-data="lpeCell()"
                                        @click="startEdit()"
                                        :class="{ 'lpe-cell--editing': editing, 'lpe-cell--saving': saving }">
                                        <span x-show="!editing" x-text="displayValue || '—'"></span>
                                        <input x-show="editing" x-cloak x-ref="input" type="number"
                                               min="0" max="30" class="lpe-input"
                                               x-model="value"
                                               @blur="commit()"
                                               @keydown.enter.prevent="commit()"
                                               @keydown.escape.prevent="cancel()"
                                               @click.stop>
                                    </td>
                                @else
                                    <td class="lp-volume lp-col-cect">{{ $credit ?? '—' }}</td>
                                @endif
                                @if($canEdit)
                                    <td>
                                        <button type="button"
                                                class="lpe-teacher-btn @if($teacherName) lpe-teacher-btn--assigned @endif"
                                                data-lpe-ecue-id="{{ $ecue->id }}"
                                                data-lpe-teacher-id="{{ $teacherId ?? '' }}"
                                                data-lpe-teacher-name="{{ $teacherName ?? '' }}"
                                                data-lpe-ecue-label="{{ $ecueLabel }}"
                                                x-data="lpeTeacherTrigger()"
                                                @click="openPicker()">
                                            <i class="fas {{ $teacherName ? 'fa-user-check' : 'fa-user-plus' }}"></i>
                                            <span class="lpe-teacher-name">{{ $teacherName ?: '+ Assigner' }}</span>
                                        </button>
                                    </td>
                                @else
                                    <td>{{ $teacherName ?? '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
@endif
