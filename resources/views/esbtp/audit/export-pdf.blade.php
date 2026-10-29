{{-- Le journal d'audit en PDF : les phrases de l'ecran, avec les filtres appliques. --}}
<x-pdf-document
    title="Journal d'audit"
    subtitle="Qui a fait quoi, sur qui, et quand"
    :filters="array_filter($filtres)"
    orientation="landscape">

    <style>
        .jda-t { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .jda-t th { background: #0453cb; color: #fff; padding: 6px 5px; text-align: left; font-weight: 700; }
        .jda-t td { padding: 5px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        .jda-t tr:nth-child(even) td { background: #f8fafc; }
        .jda-sous { color: #64748b; font-size: 7.5pt; }
        .jda-alerte { color: #b91c1c; font-weight: 700; font-size: 7.5pt; }
    </style>

    <table class="jda-t">
        <thead>
            <tr>
                <th style="width: 12%;">Date et heure</th>
                <th>Ce qui s'est passé</th>
                <th style="width: 20%;">Changement</th>
                <th style="width: 14%;">À regarder</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lignes as $l)
                <tr>
                    <td>{{ $l->quand->format('d/m/Y H:i') }}</td>
                    <td>
                        {{ $l->phrase() }}
                        @if($l->objet->reperes !== [])<br><span class="jda-sous">{{ implode(' · ', $l->objet->reperes) }}</span>@endif
                    </td>
                    <td>{{ $l->changement ?? '' }}</td>
                    <td class="jda-alerte">{{ implode(', ', $l->motifs) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; padding:20px; color:#64748b;">Aucune action sur la période choisie.</td></tr>
            @endforelse
        </tbody>
    </table>
</x-pdf-document>
