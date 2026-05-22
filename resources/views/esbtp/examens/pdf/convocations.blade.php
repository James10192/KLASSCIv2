<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Convocations examens</title>
    <style>
        @@page { margin: 1.5cm 1.5cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
        .pdf-header { background: #0453cb; color: #fff; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.25rem; }
        .pdf-header h1 { margin: 0; font-size: 16px; font-weight: 700; }
        .pdf-header p { margin: 2px 0 0; font-size: 10px; color: rgba(255,255,255,.8); }
        .convocation { page-break-after: always; padding: 1.5rem 0; }
        .convocation:last-child { page-break-after: auto; }
        .conv-title { font-size: 14px; font-weight: 700; color: #0453cb; margin-bottom: .5rem; }
        .conv-subtitle { font-size: 11px; color: #64748b; margin-bottom: 1rem; }
        .conv-meta { display: table; width: 100%; margin-bottom: 1rem; border: 1px solid #e2e8f0; border-radius: 6px; padding: .5rem .75rem; }
        .conv-meta-row { display: table-row; }
        .conv-meta-label, .conv-meta-value { display: table-cell; padding: 3px 6px; font-size: 10.5px; }
        .conv-meta-label { color: #64748b; width: 30%; font-weight: 600; text-transform: uppercase; font-size: 9px; letter-spacing: .5px; }
        .conv-meta-value { color: #1e293b; font-weight: 600; }
        .surv-list { margin-top: 1rem; }
        .surv-list-title { font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .3rem; }
        .surv-list-item { padding: 3px 0; font-size: 10.5px; color: #1e293b; }
        .footer { position: fixed; bottom: 1cm; left: 1.5cm; right: 1.5cm; text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: .3rem; }
        .empty { padding: 3rem; text-align: center; color: #94a3b8; }
    </style>
</head>
<body>

<div class="pdf-header">
    <h1>CONVOCATIONS D'EXAMEN</h1>
    <p>Année universitaire {{ $annee->libelle ?? '—' }} · Généré le {{ $generated_at->format('d/m/Y à H:i') }}</p>
</div>

@forelse($examens as $e)
<div class="convocation">
    <div class="conv-title">{{ $e->titre }}</div>
    <div class="conv-subtitle">N° {{ $e->numero_convocation ?? '—' }} · {{ $e->type_examen }}</div>

    <div class="conv-meta">
        <div class="conv-meta-row">
            <div class="conv-meta-label">Date</div>
            <div class="conv-meta-value">{{ optional($e->date_debut)->format('l d F Y') }}</div>
        </div>
        <div class="conv-meta-row">
            <div class="conv-meta-label">Horaires</div>
            <div class="conv-meta-value">{{ optional($e->date_debut)->format('H:i') }} – {{ optional($e->date_fin)->format('H:i') }} ({{ $e->duree_minutes ?? '—' }} min)</div>
        </div>
        <div class="conv-meta-row">
            <div class="conv-meta-label">Classe</div>
            <div class="conv-meta-value">{{ $e->classe->name ?? '—' }}</div>
        </div>
        <div class="conv-meta-row">
            <div class="conv-meta-label">Matière</div>
            <div class="conv-meta-value">{{ $e->matiere->name ?? '—' }}</div>
        </div>
        <div class="conv-meta-row">
            <div class="conv-meta-label">Salle</div>
            <div class="conv-meta-value">{{ $e->salle ?? 'À définir' }}</div>
        </div>
        <div class="conv-meta-row">
            <div class="conv-meta-label">Coefficient × Barème</div>
            <div class="conv-meta-value">{{ rtrim(rtrim(number_format($e->coefficient, 2, '.', ''), '0'), '.') }} × /{{ (int) $e->bareme }}</div>
        </div>
        @if($e->is_anonymous)
        <div class="conv-meta-row">
            <div class="conv-meta-label">Anonymat</div>
            <div class="conv-meta-value">Oui — copies anonymisées</div>
        </div>
        @endif
    </div>

    @if($e->description)
    <div style="background:#f8fafc;padding:.75rem;border-radius:6px;margin-bottom:1rem;font-size:10.5px;color:#475569;">
        <strong>Consignes :</strong> {{ $e->description }}
    </div>
    @endif

    @if($e->surveillants->isNotEmpty())
    <div class="surv-list">
        <div class="surv-list-title">Surveillants assignés</div>
        @foreach($e->surveillants as $surv)
        <div class="surv-list-item">
            • {{ $surv->user->name ?? '—' }} ({{ str_replace('_', ' ', $surv->role) }})
        </div>
        @endforeach
    </div>
    @endif
</div>
@empty
<div class="empty">Aucun examen pour les filtres sélectionnés.</div>
@endforelse

<div class="footer">
    KLASSCI — Système de gestion académique · Document généré automatiquement
</div>

</body>
</html>
