@php
    $currentSort = request('sort', 'created_at');
    $currentDir = request('dir', 'desc');
    $sortLink = function($column, $label) use ($currentSort, $currentDir) {
        $nextDir = ($currentSort === $column && $currentDir === 'asc') ? 'desc' : 'asc';
        $ariaSort = $currentSort !== $column ? 'none' : ($currentDir === 'asc' ? 'ascending' : 'descending');
        $icon = 'fa-sort';
        if ($currentSort === $column) {
            $icon = $currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
        }
        return [
            'column' => $column,
            'label' => $label,
            'nextDir' => $nextDir,
            'ariaSort' => $ariaSort,
            'icon' => $icon,
        ];
    };
@endphp

@if($inscriptions->isEmpty())
    <div class="ii-empty">
        <div class="ii-empty-icon"><i class="fas fa-check-double"></i></div>
        <h4>Aucune inscription sous réserve</h4>
        <p>Toutes les inscriptions pour les filtres choisis sont confirmées.</p>
    </div>
@else
    <div class="ii-table-wrap">
        <table class="ii-table">
            <thead>
                <tr>
                    <th style="width:38px;">
                        <input type="checkbox" id="isr-select-all" class="form-check-input" aria-label="Tout sélectionner">
                    </th>
                    <th>Étudiant</th>
                    <th>Classe</th>
                    <th>Année</th>
                    @php $s = $sortLink('condition_reserve', 'Condition'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    <th>Paiement</th>
                    @php $s = $sortLink('workflow_step', 'Workflow'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    @php $s = $sortLink('created_at', 'Date'); @endphp
                    <th class="is-sortable" data-sort="{{ $s['column'] }}" data-next-dir="{{ $s['nextDir'] }}" aria-sort="{{ $s['ariaSort'] }}">
                        {{ $s['label'] }} <i class="fas {{ $s['icon'] }} ii-sort-icon"></i>
                    </th>
                    <th style="width:130px; text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inscriptions as $inscription)
                    @php
                        $paiementValide = $inscription->paiements->firstWhere('status', 'validé');
                        $totalPaye = $inscription->paiements->where('status', 'validé')->sum('montant');
                        $fullName = trim(($inscription->etudiant->nom ?? '') . ' ' . ($inscription->etudiant->prenoms ?? ''));
                        $initials = strtoupper(substr($inscription->etudiant->nom ?? '?', 0, 1));
                        $hue = crc32($fullName) % 360;
                        $photoUrl = $inscription->etudiant->photo_url ?? null;
                    @endphp
                    <tr data-inscription-id="{{ $inscription->id }}"
                        data-href="{{ route('esbtp.inscriptions.show', $inscription) }}"
                        data-student-label="{{ $fullName }}">
                        <td data-no-row-click>
                            <input class="form-check-input isr-row-checkbox" type="checkbox" value="{{ $inscription->id }}" aria-label="Sélectionner cette inscription">
                        </td>
                        <td>
                            <div class="ii-student">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}" alt="" class="ii-student-photo">
                                @else
                                    <div class="ii-student-photo" style="background:hsl({{ $hue }}, 60%, 55%);">{{ $initials }}</div>
                                @endif
                                <div>
                                    <div class="ii-student-name">{{ $fullName }}</div>
                                    <div class="ii-student-meta">{{ $inscription->etudiant->matricule ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div>{{ $inscription->classe->name ?? '—' }}</div>
                            @if($inscription->filiere)
                                <div class="ii-student-meta">{{ $inscription->filiere->name }}</div>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $inscription->anneeUniversitaire->name ?? '—' }}</strong>
                            @if($anneeEnCours && $inscription->annee_universitaire_id == $anneeEnCours->id)
                                <div class="ii-student-meta" style="color:var(--ii-success);"><i class="fas fa-check"></i> Courante</div>
                            @endif
                        </td>
                        <td>
                            <span class="isr-condition-badge">
                                <i class="fas fa-clock"></i> {{ $inscription->condition_reserve ?? 'Non précisé' }}
                            </span>
                        </td>
                        <td>
                            @if($totalPaye > 0)
                                <span class="ii-paiement-chip ii-paiement-chip--paye">
                                    <i class="fas fa-check"></i> {{ number_format($totalPaye, 0, ',', ' ') }} F
                                </span>
                            @else
                                <span class="ii-paiement-chip ii-paiement-chip--aucun">
                                    <i class="fas fa-times"></i> Aucun
                                </span>
                            @endif
                        </td>
                        <td>
                            <x-workflow-step-badge :inscription="$inscription" />
                        </td>
                        <td>{{ optional($inscription->created_at)->format('d/m/Y') }}</td>
                        <td style="text-align:right;" data-no-row-click>
                            <div class="ii-actions" style="justify-content:flex-end;">
                                <button type="button" class="ii-action-btn ii-action-btn--primary"
                                        onclick="isrLeverReserve({{ $inscription->id }}, '{{ addslashes($fullName) }}')"
                                        title="Lever la réserve">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button type="button" class="ii-action-btn ii-action-btn--danger"
                                        onclick="isrAnnuler({{ $inscription->id }}, '{{ addslashes($fullName) }}')"
                                        title="Annuler l'inscription">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($inscriptions->hasPages())
        <div class="ii-pagination">
            {{ $inscriptions->links() }}
        </div>
    @endif
@endif
