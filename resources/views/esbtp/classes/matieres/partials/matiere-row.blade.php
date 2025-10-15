@php
    use Illuminate\Support\Str;

    $isLinked = (bool) ($matiere->matches_combination ?? false);
    $isActiveGlobal = (bool) ($matiere->is_active ?? true);

    $combos = collect($matiere->filieres)
        ->flatMap(function ($filiere) use ($matiere) {
            return collect($matiere->niveaux)->map(function ($niveau) use ($filiere) {
                return [
                    'filiere_id' => $filiere->id,
                    'niveau_id' => $niveau->id,
                    'label' => (
                        ($filiere->code ?? Str::limit($filiere->name, 6))
                    ) . ' · ' . (
                        ($niveau->code ?? Str::limit($niveau->name, 6))
                    ),
                ];
            });
        })
        ->unique(function ($combo) {
            return $combo['filiere_id'] . '-' . $combo['niveau_id'];
        })
        ->values();
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
        <div class="combo-badges" data-combos='@json($combos)'></div>
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
