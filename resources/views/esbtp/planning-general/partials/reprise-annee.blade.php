{{-- Import non destructif des configurations BTS et affectations : jamais de séances datées. --}}
@php
    $anneesSources = $annees->filter(fn ($annee) => $annee->start_date
        && $anneeSelectionnee->start_date
        && $annee->start_date->lt($anneeSelectionnee->start_date));
@endphp
@if($anneesSources->isNotEmpty())
<details class="pg-section" style="background:#f8fafc;border:1px solid #dbeafe;border-radius:14px;padding:1rem 1.25rem;margin-bottom:1.25rem;">
    <summary style="cursor:pointer;color:#0453cb;font-weight:700;display:list-item;">
        <i class="fas fa-copy me-2"></i>Reprendre le planning d'une année précédente
    </summary>
    <p style="color:#64748b;margin:12px 0;font-size:.86rem;">
        Récupérer les affectations des professeurs et volumes horaires vers
        <strong>{{ $anneeSelectionnee->name }}</strong>, sans recréer les enseignants.
        Les configurations déjà présentes ne sont pas modifiées, et les séances datées ne sont pas copiées.
    </p>
    <form id="pg-reprise-form" action="{{ route('esbtp.planning-general.reprise-annee') }}" method="POST">
        @csrf
        <input type="hidden" name="target_annee_id" value="{{ $anneeSelectionnee->id }}">
        <div style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:end;">
            <div style="flex:1;min-width:220px;">
                <label for="pg-reprise-source" style="display:block;font-weight:600;margin-bottom:5px;font-size:.85rem;">Année source</label>
                <select id="pg-reprise-source" name="source_annee_id" class="form-select" required>
                    <option value="">Choisir l'année précédente…</option>
                    @foreach($anneesSources as $annee)
                        <option value="{{ $annee->id }}">{{ $annee->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" class="btn btn-outline-primary" id="pg-reprise-preview"
                    data-preview-url="{{ route('esbtp.planning-general.reprise-annee.preview') }}">
                <i class="fas fa-search me-1"></i>Vérifier la reprise
            </button>
        </div>
        <div id="pg-reprise-feedback" role="status" aria-live="polite" style="margin:12px 0;font-size:.85rem;"></div>
        <div id="pg-reprise-confirm-area" hidden>
            <label style="display:flex;gap:.55rem;align-items:flex-start;font-size:.82rem;color:#334155;margin-bottom:10px;">
                <input type="checkbox" id="pg-reprise-confirm" style="margin-top:3px;">
                Je confirme la reprise des configurations manquantes vers {{ $anneeSelectionnee->name }}.
            </label>
            <button type="submit" class="btn btn-primary" id="pg-reprise-submit" disabled>
                <i class="fas fa-check me-1"></i>Importer les configurations manquantes
            </button>
        </div>
    </form>
</details>
@endif
