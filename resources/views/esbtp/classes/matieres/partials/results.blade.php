<div class="card-moderne">
    <div class="p-lg">
        <div class="d-flex align-items-center justify-content-between mb-md">
            <div class="section-title d-flex align-items-center gap-2">
                <i class="fas fa-list"></i>
                <span>Liste des Matières Disponibles</span>
            </div>
            <div class="text-muted small">
                {{ $stats['used_by_class'] }} matière(s) prises en compte sur {{ $stats['suggested_total'] }} configurée(s) pour {{ $classe->name }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle classe-matieres-table">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">Code</th>
                        <th>Matière</th>
                        <th style="width: 260px;">Combinaisons catalogue</th>
                        <th style="width: 220px;">Statut pour {{ $classe->name }}</th>
                        <th style="width: 200px;" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $hasRows = ($matieres->count() > 0) || (($availableMatieres ?? collect())->count() > 0);
                    @endphp

                    @if(!$hasRows)
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-2x mb-3"></i>
                                <div>Aucune matière disponible pour cette formation.</div>
                            </td>
                        </tr>
                    @else
                        @foreach($matieres as $matiere)
                            @include('esbtp.classes.matieres.partials.matiere-row', ['matiere' => $matiere, 'classe' => $classe])
                        @endforeach
                        @foreach($availableMatieres ?? [] as $matiere)
                            @include('esbtp.classes.matieres.partials.matiere-row', ['matiere' => $matiere, 'classe' => $classe])
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
