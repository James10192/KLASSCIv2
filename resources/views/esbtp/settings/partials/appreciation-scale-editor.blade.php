@php
    $system = $system ?? 'bts';
    $scale = $scale ?? [];
    $field = 'appreciation_scale_' . $system;
@endphp

<div class="app-scale-editor" data-appreciation-scale-editor="{{ $system }}">
    <div class="app-scale-head">
        <div>
            <h4 class="app-scale-title">{{ $title }}</h4>
            <p class="app-scale-desc">{{ $description }}</p>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary app-scale-add" data-scale-add="{{ $system }}">
            <i class="fas fa-plus" aria-hidden="true"></i>
            Ajouter
        </button>
    </div>

    @error($field)
        <div class="alert alert-danger py-2 mb-3">{{ $message }}</div>
    @enderror

    <div class="app-scale-table-wrap">
        <table class="app-scale-table">
            <thead>
                <tr>
                    <th>Minimum</th>
                    <th>Maximum</th>
                    <th>Libellé affiché</th>
                    <th class="text-center">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scale as $index => $range)
                    <tr data-scale-row>
                        <td>
                            <input type="number"
                                   class="form-control form-control-modern"
                                   name="{{ $field }}[{{ $index }}][min]"
                                   value="{{ $range['min'] }}"
                                   min="0"
                                   max="20"
                                   step="0.01"
                                   aria-label="Minimum {{ $title }}">
                        </td>
                        <td>
                            <input type="number"
                                   class="form-control form-control-modern"
                                   name="{{ $field }}[{{ $index }}][max]"
                                   value="{{ $range['max'] }}"
                                   min="0"
                                   max="20"
                                   step="0.01"
                                   aria-label="Maximum {{ $title }}">
                        </td>
                        <td>
                            <input type="text"
                                   class="form-control form-control-modern"
                                   name="{{ $field }}[{{ $index }}][label]"
                                   value="{{ $range['label'] }}"
                                   maxlength="80"
                                   aria-label="Libellé {{ $title }}">
                        </td>
                        <td class="text-center">
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger app-scale-remove"
                                    data-scale-remove
                                    aria-label="Retirer cette ligne"
                                    title="Retirer cette ligne">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('click', function (event) {
                const addButton = event.target.closest('[data-scale-add]');
                if (addButton) {
                    const system = addButton.getAttribute('data-scale-add');
                    const editor = document.querySelector(`[data-appreciation-scale-editor="${system}"]`);
                    const tbody = editor ? editor.querySelector('tbody') : null;
                    if (!tbody) return;

                    const field = `appreciation_scale_${system}`;
                    const index = Date.now().toString();
                    const row = document.createElement('tr');
                    row.setAttribute('data-scale-row', '');
                    row.innerHTML = `
                        <td><input type="number" class="form-control form-control-modern" name="${field}[${index}][min]" min="0" max="20" step="0.01" aria-label="Minimum"></td>
                        <td><input type="number" class="form-control form-control-modern" name="${field}[${index}][max]" min="0" max="20" step="0.01" aria-label="Maximum"></td>
                        <td><input type="text" class="form-control form-control-modern" name="${field}[${index}][label]" maxlength="80" aria-label="Libellé"></td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger app-scale-remove" data-scale-remove aria-label="Retirer cette ligne" title="Retirer cette ligne">
                                <i class="fas fa-trash" aria-hidden="true"></i>
                            </button>
                        </td>`;
                    tbody.appendChild(row);
                    row.querySelector('input')?.focus();
                    return;
                }

                const removeButton = event.target.closest('[data-scale-remove]');
                if (removeButton) {
                    const tbody = removeButton.closest('tbody');
                    if (tbody && tbody.querySelectorAll('[data-scale-row]').length > 1) {
                        removeButton.closest('[data-scale-row]')?.remove();
                    }
                }
            });
        </script>
    @endpush
@endonce
