{{--
    Le pied de bulletin qui explique les trous.

    Un bulletin ou une matiere porte « — » sans un mot d'explication laisse la
    famille deviner. Ce bloc nomme chaque dispense et son motif ; il ne s'affiche
    que s'il y en a.

    @param iterable $resultatsGeneraux
    @param iterable $resultatsTechniques
--}}
@php
    $_dispenses = collect($resultatsGeneraux ?? [])
        ->concat(collect($resultatsTechniques ?? []))
        ->filter(fn ($ligne) => ($ligne->statut ?? null) === \App\Models\ESBTPResultatMatiere::STATUT_DISPENSE)
        ->values();
@endphp
@if($_dispenses->isNotEmpty())
    <table class="absences-table">
        <thead>
            <tr class="section-header">
                <td colspan="2">Dispenses</td>
            </tr>
        </thead>
        <tbody>
            @foreach($_dispenses as $_dispense)
                <tr>
                    <td>{{ $_dispense->matiere->name ?? $_dispense->matiere->nom ?? 'Matière' }}</td>
                    <td>{{ $_dispense->motif_dispense ?: 'Dispense accordée' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif
