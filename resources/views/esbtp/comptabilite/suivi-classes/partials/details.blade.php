@if($ligne)
<div class="sf-details-summary">
    <strong>{{ $ligne['classe'] }}</strong>
    <span>{{ $ligne['effectif'] }} étudiant(s) · Attendu {{ number_format($ligne['attendu'], 0, ',', ' ') }} · Reste {{ number_format($ligne['restant'], 0, ',', ' ') }}</span>
</div>
<div class="sf-table-scroll">
<table class="sf-table">
    <thead><tr><th>Étudiant</th><th class="sf-money">Attendu</th><th class="sf-money">Réglé</th><th class="sf-money">Reste</th><th class="sf-money">Échu</th></tr></thead>
    <tbody>
    @forelse($ligne['etudiants'] as $e)
    <tr>
        <td><strong>{{ $e['nom'] }}</strong><small>{{ $e['matricule'] }}</small></td>
        @foreach(['attendu', 'regle', 'restant', 'echu'] as $col)
            <td class="sf-money">{{ number_format($e[$col], 0, ',', ' ') }}</td>
        @endforeach
    </tr>
    @empty
    <tr><td colspan="5" class="sf-empty">Aucune inscription correspondante pour cette classe et cette année.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@else
<p class="sf-empty">Classe introuvable dans le périmètre filtré.</p>
@endif
