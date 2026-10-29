<x-pdf-document
    title="RELEVÉ PROVISOIRE — BTS"
    :subtitle="'Document consultatif non officiel · Année universitaire '.$annee->name">
    <style>
        .bpr-warning { border: 1px solid #edc46b; color: #855e16; background: #fff7e6; padding: 10px; margin: 12px 0; font-weight: bold; }
        .bpr-identity { border: 1px solid #dce3ef; padding: 11px; margin: 12px 0; }
        .bpr-identity table { width: 100%; border-collapse: collapse; }
        .bpr-identity td { padding: 4px; width: 50%; }
        .bpr-label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        .bpr-section { page-break-inside: avoid; }
        .bpr-section h2 { color: #153d76; font-size: 11px; margin: 15px 0 7px; }
        .bpr-grades { width: 100%; border-collapse: collapse; margin-top: 7px; }
        .bpr-grades th { background: #eaf2ff; color: #17498a; text-align: left; }
        .bpr-grades th, .bpr-grades td { border: 1px solid #dce3ef; padding: 7px 8px; }
        .bpr-num { text-align: right; }
        .bpr-summary { padding: 8px; background: #f3f7ff; text-align: right; margin-top: 7px; }
        .bpr-foot { color: #64748b; font-size: 8px; margin-top: 10px; }
    </style>

    <div class="bpr-warning">
        DOCUMENT CONSULTATIF — NON OFFICIEL — NON PUBLIÉ.
        Ce relevé repose exclusivement sur les notes actuellement disponibles.
        Il peut être incomplet et évoluer. Il ne constitue ni un bulletin publié,
        ni une décision du conseil, ni le relevé national BTS délivré par les autorités compétentes.
    </div>

    <div class="bpr-identity">
        <table>
            <tr>
                <td><div class="bpr-label">Étudiant</div><strong>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</strong></td>
                <td><div class="bpr-label">Matricule</div><strong>{{ $etudiant->matricule ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td><div class="bpr-label">Période</div>{{ $periode === 'annuel' ? 'Semestres disponibles' : ($periode === 'semestre1' ? 'Semestre 1' : 'Semestre 2') }}</td>
                <td><div class="bpr-label">Année universitaire</div>{{ $annee->name }}</td>
            </tr>
        </table>
    </div>

    @foreach($sections as $section)
        <div class="bpr-section">
            <h2>{{ $section['periode'] === 'semestre1' ? 'Semestre 1' : 'Semestre 2' }} · {{ $section['classe_nom'] }}</h2>
            <table class="bpr-grades">
                <thead>
                    <tr><th>Matière</th><th class="bpr-num">Coef.</th><th class="bpr-num">Moyenne indicative /20</th></tr>
                </thead>
                <tbody>
                    @forelse($section['subjects'] as $subject)
                        <tr>
                            <td>{{ $subject['matiere'] ?? 'Matière non renseignée' }}</td>
                            <td class="bpr-num">{{ isset($subject['coefficient']) ? number_format((float) $subject['coefficient'], 1, ',', ' ') : '—' }}</td>
                            <td class="bpr-num">{{ isset($subject['moyenne']) ? number_format((float) $subject['moyenne'], 2, ',', ' ') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">Aucune note exploitable pour ce semestre à ce jour.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="bpr-summary">
                Moyenne indicative :
                {{ $section['moyenne'] === null ? 'Non calculée' : number_format((float) $section['moyenne'], 2, ',', ' ').' / 20' }}
                @if($section['coefficients_missing'] || ! $section['configuration_ready'])
                    <div>Coefficients ou configuration incomplets : cette moyenne n'est pas définitive.</div>
                @endif
            </div>
        </div>
    @endforeach

    <div class="bpr-warning">
        PROVISOIRE — NE PAS UTILISER COMME DOCUMENT OFFICIEL.
        Seuls les bulletins publiés font foi pour le relevé interne publié.
    </div>
    <div class="bpr-foot">Consulté le {{ $generatedAt->format('d/m/Y à H:i') }} · KLASSCI</div>
</x-pdf-document>
