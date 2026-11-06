@if($ligne)
<div class="sfc-details-summary">
    <strong>{{ $ligne['classe'] }}</strong>
    <span>{{ $ligne['effectif'] }} étudiant(s) · Attendu {{ number_format($ligne['attendu'], 0, ',', ' ') }} · Reste {{ number_format($ligne['restant'], 0, ',', ' ') }}</span>
</div>
<div class="sfc-table-scroll">
<table class="sfc-table">
    <thead><tr><th>Étudiant</th><th class="sfc-money">Attendu</th><th class="sfc-money">Réglé</th><th class="sfc-money">Reste</th><th class="sfc-money">Échu</th></tr></thead>
    <tbody>
    @forelse($ligne['etudiants'] as $e)
    <tr>
        <td><strong>{{ $e['nom'] }}</strong><small>{{ $e['matricule'] }}</small></td>
        @foreach(['attendu', 'regle', 'restant', 'echu'] as $col)
            <td class="sfc-money">{{ number_format($e[$col], 0, ',', ' ') }}</td>
        @endforeach
    </tr>
    @empty
    <tr><td colspan="5" class="sfc-empty">Aucune inscription correspondante pour cette classe et cette année.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@else
<p class="sfc-empty">Classe introuvable dans le périmètre filtré.</p>
@endif
