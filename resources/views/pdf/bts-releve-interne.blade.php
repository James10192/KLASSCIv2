<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Relevé de notes interne BTS</title>
    <style>
        @@page { margin: 17mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        h1 { color: #0453cb; font-size: 17px; margin: 0 0 5px; }
        h2 { color: #153d76; font-size: 11px; margin: 16px 0 7px; }
        .head { border-bottom: 2px solid #0453cb; padding-bottom: 12px; }
        .school { font-size: 13px; font-weight: bold; margin-bottom: 10px; }
        .sub { color: #64748b; font-size: 9px; }
        .identity { margin: 12px 0 10px; border: 1px solid #dce3ef; padding: 11px; }
        .identity table { width: 100%; }
        .identity td { padding: 4px; width: 50%; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; }
        table.grades { width: 100%; border-collapse: collapse; margin-top: 7px; }
        table.grades th { background: #eaf2ff; color: #17498a; text-align: left; font-size: 9px; }
        table.grades th, table.grades td { border: 1px solid #dce3ef; padding: 7px 8px; }
        table.grades td.num, table.grades th.num { text-align: right; width: 14%; }
        .summary { margin-top: 7px; padding: 8px 10px; background: #f3f7ff; text-align: right; font-weight: bold; }
        .notice { border: 1px solid #f5c56f; color: #855e16; background: #fff8e9; padding: 9px; margin-top: 16px; font-size: 9px; }
        .foot { margin-top: 15px; color: #64748b; text-align: right; font-size: 8px; }
        .section { page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="head">
        <div class="school">{{ $schoolName }}</div>
        <h1>Relevé de notes — BTS</h1>
        <div class="sub">Document académique interne · Année universitaire {{ $annee->name }}</div>
    </div>

    <div class="identity">
        <table>
            <tr>
                <td><div class="label">Étudiant</div><strong>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</strong></td>
                <td><div class="label">Matricule</div><strong>{{ $etudiant->matricule ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td><div class="label">Période demandée</div>{{ $periode === 'annuel' ? 'Année (semestres disponibles)' : ($periode === 'semestre1' ? 'Semestre 1' : 'Semestre 2') }}</td>
                <td><div class="label">Année universitaire</div>{{ $annee->name }}</td>
            </tr>
        </table>
    </div>

    @foreach($bulletins as $bulletin)
        <div class="section">
            <h2>{{ in_array((string) $bulletin->periode, ['semestre1', '1'], true) ? 'Semestre 1' : 'Semestre 2' }}
                · {{ $bulletin->classe?->name ?? 'Classe non renseignée' }}</h2>
            <table class="grades">
                <thead><tr><th>Matière</th><th class="num">Coef.</th><th class="num">Moyenne /20</th></tr></thead>
                <tbody>
                @forelse($bulletin->resultats->sortBy(fn ($ligne) => $ligne->matiere?->name ?? '') as $ligne)
                    <tr>
                        <td>{{ $ligne->matiere?->name ?? 'Matière non renseignée' }}</td>
                        <td class="num">{{ number_format((float) $ligne->coefficient, 1, ',', ' ') }}</td>
                        <td class="num">
                            @if(($ligne->statut ?? '') === \App\Models\ESBTPResultatMatiere::STATUT_DISPENSE)
                                DISP
                            @elseif(($ligne->statut ?? '') === \App\Models\ESBTPResultatMatiere::STATUT_NON_NOTE || $ligne->moyenne === null)
                                —
                            @else
                                {{ number_format((float) $ligne->moyenne, 2, ',', ' ') }}
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3">Aucun détail de matière enregistré sur ce bulletin.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="summary">Moyenne du semestre :
                {{ $bulletin->moyenne_generale === null ? 'Non calculée' : number_format((float) $bulletin->moyenne_generale, 2, ',', ' ').' / 20' }}
            </div>
        </div>
    @endforeach

    <div class="notice">
        Ce relevé récapitule les résultats publiés dans {{ $schoolName }}. Il ne remplace pas le relevé
        officiel de l'examen national du BTS délivré par les autorités compétentes.
        Les semestres non publiés ne figurent pas dans ce document.
    </div>
    <div class="foot">Édité le {{ $generatedAt->format('d/m/Y à H:i') }} · KLASSCI</div>
</body>
</html>
