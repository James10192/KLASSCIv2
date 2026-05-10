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
                    <th class="lp-th-num">CM</th><th class="lp-th-num">TD</th><th class="lp-th-num">TP</th>
                    <th class="lp-th-num">Projet</th><th class="lp-th-num">TPE</th><th class="lp-th-num">Total</th>
                    <th class="lp-th-num">CECT</th><th>Enseignant</th>
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
                            <td class="lp-volume lp-volume-total">—</td>
                            <td class="lp-volume lp-volume-total">{{ $row['cect'] }}</td>
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
                                    <td class="lp-volume @if(!$planif->volume_horaire_cm) lp-volume-zero @endif">{{ $planif->volume_horaire_cm ?? 0 }}</td>
                                    <td class="lp-volume @if(!$planif->volume_horaire_td) lp-volume-zero @endif">{{ $planif->volume_horaire_td ?? 0 }}</td>
                                    <td class="lp-volume @if(!$planif->volume_horaire_tp) lp-volume-zero @endif">{{ $planif->volume_horaire_tp ?? 0 }}</td>
                                    <td class="lp-volume @if(!$planif->volume_horaire_projet) lp-volume-zero @endif">{{ $planif->volume_horaire_projet ?? 0 }}</td>
                                    <td class="lp-volume @if(!$planif->volume_horaire_tpe) lp-volume-zero @endif">{{ $planif->volume_horaire_tpe ?? 0 }}</td>
                                    <td class="lp-volume lp-volume-total">{{ $planif->volume_horaire_total }}</td>
                                    <td class="lp-volume">{{ $ecue->credit_ecue ?? '—' }}</td>
                                    <td>{{ $planif->enseignantPrincipal?->name ?? '—' }}</td>
                                @else
                                    <td colspan="6" class="lp-no-planif">Non planifié</td>
                                    <td class="lp-volume">{{ $ecue->credit_ecue ?? '—' }}</td>
                                    <td class="lp-no-planif">—</td>
                                @endif
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
