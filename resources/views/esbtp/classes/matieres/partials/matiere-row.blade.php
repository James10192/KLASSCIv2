@php
    use Illuminate\Support\Str;

    $isLinked = (bool) ($matiere->matches_combination ?? false);
    $isActiveGlobal = (bool) ($matiere->is_active ?? true);

    $liaisons = $matiere->liaisonsFilieresNiveaux ?? collect();
    $combos = $liaisons->map(function ($liaison) {
        $fCode = $liaison->filiere->code ?? Str::limit($liaison->filiere->name ?? '?', 6);
        $nCode = $liaison->niveauEtude->code ?? Str::limit($liaison->niveauEtude->name ?? '?', 4);
        return [
            'filiere_id' => $liaison->filiere_id,
            'niveau_id'  => $liaison->niveau_etude_id,
            'label'      => $fCode . ' · ' . $nCode,
        ];
    })->values();
@endphp

<tr data-matiere-id="{{ $matiere->id }}"
    data-linked="{{ $isLinked ? '1' : '0' }}"
    data-active="{{ $isActiveGlobal ? '1' : '0' }}"
    data-name="{{ strtolower($matiere->code . ' ' . $matiere->name) }}">
    <td class="align-middle">
        <span class="badge bg-primary">{{ $matiere->code ?? 'N/A' }}</span>
    </td>
    <td class="align-middle">
        <div class="fw-semibold color-primary">{{ $matiere->name }}</div>
        @if($matiere->description)
            <small class="text-muted">{{ Str::limit($matiere->description, 90) }}</small>
        @endif
    </td>
    <td class="align-middle">
        @if($combos->isNotEmpty())
            <div class="d-flex flex-wrap gap-1">
                @foreach($combos->take(3) as $combo)
                    <span class="badge d-inline-flex align-items-center gap-1 px-2 py-1"
                          style="background: linear-gradient(135deg,#e8f0fe 0%,#d2e3fc 100%); color:#1a56db; font-size:.72rem; font-weight:600; border:1px solid #c2d4f8; border-radius:999px;">
                        {{ $combo['label'] }}
                    </span>
                @endforeach
                @if($combos->count() > 3)
                    <span class="badge d-inline-flex align-items-center px-2 py-1"
                          style="background:#f1f5f9; color:#64748b; font-size:.72rem; border:1px solid #e2e8f0; border-radius:999px;"
                          title="{{ $combos->count() }} combinaisons au total">
                        +{{ $combos->count() - 3 }}
                    </span>
                @endif
            </div>
        @else
            <span class="badge bg-light text-muted border" style="border-color:#e2e8f0 !important; font-size:.72rem;">
                <i class="fas fa-unlink me-1 opacity-50"></i>Non configuré
            </span>
        @endif
    </td>
    <td class="align-middle">
        <span class="badge {{ $isLinked ? 'bg-primary' : 'bg-secondary text-dark' }}" data-role="class-status">
            {{ $isLinked ? 'Enseignée dans cette classe' : 'Disponible dans le catalogue' }}
        </span>
    </td>
    <td class="align-middle text-end">
        <button type="button"
                class="btn btn-sm {{ $isLinked ? 'btn-outline-danger' : 'btn-outline-primary' }} toggle-combination-btn"
                data-action="{{ $isLinked ? 'remove' : 'add' }}"
                data-matiere-id="{{ $matiere->id }}">
            <i class="fas {{ $isLinked ? 'fa-minus-circle' : 'fa-plus-circle' }} me-1"></i>
            {{ $isLinked ? 'Retirer de la classe' : 'Ajouter à la classe' }}
        </button>
    </td>
</tr>
