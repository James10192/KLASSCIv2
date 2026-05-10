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
        <div style="overflow-x: auto;">
            <table class="lp-table" id="lpListing">
                <thead><tr>
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
                            @php $ecue = $entry['ecue']; $planif = $entry['planif']; @endphp
                            <tr class="lp-ecue-row js-ecue-row" data-parent-idx="{{ $idx }}" style="display:none;">
                                <td class="lp-ecue-indent">
                                    @if(!empty($ecue->code))<span class="lp-ecue-code">{{ $ecue->code }}</span>@endif
                                    {{ $ecue->name }}
                                </td>
                                <td>—</td>
                                @if($planif)
                                    <td class="lp-volume lp-col-cm @if(!$planif->volume_horaire_cm) lp-volume-zero @endif">{{ $planif->volume_horaire_cm ?? 0 }}</td>
                                    <td class="lp-volume lp-col-td @if(!$planif->volume_horaire_td) lp-volume-zero @endif">{{ $planif->volume_horaire_td ?? 0 }}</td>
                                    <td class="lp-volume lp-col-tp @if(!$planif->volume_horaire_tp) lp-volume-zero @endif">{{ $planif->volume_horaire_tp ?? 0 }}</td>
                                    <td class="lp-volume lp-col-projet @if(!$planif->volume_horaire_projet) lp-volume-zero @endif">{{ $planif->volume_horaire_projet ?? 0 }}</td>
                                    <td class="lp-volume lp-col-tpe @if(!$planif->volume_horaire_tpe) lp-volume-zero @endif">{{ $planif->volume_horaire_tpe ?? 0 }}</td>
                                    <td class="lp-volume lp-volume-total lp-col-total">{{ $planif->volume_horaire_total }}</td>
                                    <td class="lp-volume lp-col-cect">{{ $ecue->credit_ecue ?? '—' }}</td>
                                    <td>{{ $planif->enseignantPrincipal?->name ?? '—' }}</td>
                                @else
                                    <td colspan="6" class="lp-no-planif">Non planifié</td>
                                    <td class="lp-volume lp-col-cect">{{ $ecue->credit_ecue ?? '—' }}</td>
                                    <td class="lp-no-planif">—</td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        @once
        @push('styles')
        <style>
        /* Subtle column tinting for LMD planning maquette — opacity ≤ 6% to preserve KLASSCI monochrome */
        .lp-table .lp-col-cm     { background: rgba(4, 83, 203, .04); }
        .lp-table .lp-col-td     { background: rgba(59, 125, 219, .05); }
        .lp-table .lp-col-tp     { background: rgba(94, 145, 222, .05); }
        .lp-table .lp-col-projet { background: rgba(99, 102, 241, .04); }
        .lp-table .lp-col-tpe    { background: rgba(244, 114, 182, .04); }
        .lp-table .lp-col-total  { background: rgba(16, 185, 129, .06); }
        .lp-table .lp-col-cect   { background: rgba(245, 158, 11, .06); }
        .lp-table .lp-th-tip {
            cursor: help;
            border-bottom: 1px dotted #cbd5e1;
        }
        /* Slightly stronger tint on header so the column "rail" reads from the top */
        .lp-table thead .lp-col-cm     { background: rgba(4, 83, 203, .07); }
        .lp-table thead .lp-col-td     { background: rgba(59, 125, 219, .08); }
        .lp-table thead .lp-col-tp     { background: rgba(94, 145, 222, .08); }
        .lp-table thead .lp-col-projet { background: rgba(99, 102, 241, .07); }
        .lp-table thead .lp-col-tpe    { background: rgba(244, 114, 182, .07); }
        .lp-table thead .lp-col-total  { background: rgba(16, 185, 129, .09); }
        .lp-table thead .lp-col-cect   { background: rgba(245, 158, 11, .09); }
        </style>
        @endpush
        @endonce
    </div>
@endif
