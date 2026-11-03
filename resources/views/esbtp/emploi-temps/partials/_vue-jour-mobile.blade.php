{{-- Emploi du temps sur téléphone : un jour à la fois, au lieu d'une grille de six colonnes
     qui défile en largeur. La grille reste la vue du bureau ; ce bloc n'est visible que sous 768 px. --}}
@php
    $etjAujourdhui = strtolower(\Carbon\Carbon::now()->locale('fr')->isoFormat('dddd'));
    $etjSlugs = collect($normalizedDays)->pluck('slug')->all();
    $etjJour = in_array($etjAujourdhui, $etjSlugs, true) ? $etjAujourdhui : ($etjSlugs[0] ?? null);
@endphp
<div class="etj" x-data="{ jour: @js($etjJour) }">
    <div class="etj-jours" role="tablist" aria-label="Jour affiché">
        @foreach($normalizedDays as $dayInfo)
            @php $etjNombre = count($timelineSessions[$dayInfo['slug']] ?? []); @endphp
            <button type="button" class="etj-jour" role="tab"
                    :class="jour === @js($dayInfo['slug']) ? 'etj-jour--actif' : ''"
                    :aria-selected="jour === @js($dayInfo['slug']) ? 'true' : 'false'"
                    x-on:click="jour = @js($dayInfo['slug'])">
                <span class="etj-jour-nom">{{ mb_substr($dayInfo['label'], 0, 3, 'UTF-8') }}</span>
                <span class="etj-jour-nb">{{ $etjNombre }}</span>
            </button>
        @endforeach
    </div>

    @foreach($normalizedDays as $dayInfo)
        @php
            $etjSeances = collect($timelineSessions[$dayInfo['slug']] ?? [])->sortBy('gridRowStart')->values();
        @endphp
        <div class="etj-liste" role="tabpanel" x-show="jour === @js($dayInfo['slug'])" x-cloak>
            @forelse($etjSeances as $session)
                <div class="etj-seance">
                    <div class="etj-heure">{{ $session['timeRange'] ?: '—' }}</div>
                    <div class="etj-corps">
                        <div class="etj-matiere">{{ $session['matiere'] }}</div>
                        <div class="etj-details">
                            <span class="etj-type">{{ $session['typeLabel'] }}</span>
                            @if($session['enseignant'])<span><i class="fas fa-user-tie" aria-hidden="true"></i> {{ $session['enseignant'] }}</span>@endif
                            @if($session['salle'])<span><i class="fas fa-door-open" aria-hidden="true"></i> {{ $session['salle'] }}</span>@endif
                        </div>
                    </div>
                    @can('timetables.edit')
                        <a class="etj-modifier" href="{{ route('esbtp.seances-cours.edit', $session['id']) }}" aria-label="Modifier la séance {{ $session['matiere'] }}">
                            <i class="fas fa-pen" aria-hidden="true"></i>
                        </a>
                    @endcan
                </div>
            @empty
                <p class="etj-vide">Aucune séance le {{ mb_strtolower($dayInfo['label'], 'UTF-8') }}.</p>
            @endforelse
            {{-- Meme lien et meme garde que les « + » de la grille du bureau. --}}
            @if(($interactive ?? false) && ($emploiTemps ?? null))
                @php $etjNumero = is_numeric($dayInfo['key']) ? $dayInfo['key'] : ($dayToNumber[$dayInfo['slug']] ?? 1); @endphp
                <a class="etj-ajouter" href="{{ route('esbtp.seances-cours.create', ['emploi_temps_id' => $emploiTemps->id, 'jour' => $etjNumero]) }}">
                    <i class="fas fa-plus" aria-hidden="true"></i> Ajouter une séance le {{ mb_strtolower($dayInfo['label'], 'UTF-8') }}
                </a>
            @endif
        </div>
    @endforeach
</div>

@once
<style>
    .etj { display: none; }
    @media (max-width: 767.98px) {
        .timeline-grid { display: none !important; }
        .etj { display: block; }
    }
    .etj-jours { display: flex; gap: .4rem; overflow-x: auto; padding-bottom: .5rem; margin-bottom: .75rem; scrollbar-width: none; }
    .etj-jours::-webkit-scrollbar { display: none; }
    .etj-jour { flex: 1 0 auto; min-width: 52px; min-height: 52px; border-radius: 12px; border: 1px solid #e2e8f0; background: #fff; color: #1e293b; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .1rem; font-weight: 600; transition: background .2s ease, color .2s ease; }
    .etj-jour-nom { font-size: .82rem; }
    .etj-jour-nb { font-size: .7rem; color: #64748b; }
    .etj-jour--actif { background: #0453cb; border-color: #0453cb; color: #fff; }
    .etj-jour--actif .etj-jour-nb { color: rgba(255,255,255,.8); }
    .etj-jour:focus-visible { outline: 3px solid rgba(4,83,203,.35); outline-offset: 2px; }
    .etj-liste { display: flex; flex-direction: column; gap: .5rem; }
    .etj-seance { display: flex; align-items: center; gap: .75rem; background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #0453cb; border-radius: 12px; padding: .75rem; }
    .etj-heure { font-variant-numeric: tabular-nums; font-weight: 700; color: #0453cb; font-size: .82rem; min-width: 92px; }
    .etj-corps { flex: 1; min-width: 0; }
    .etj-matiere { font-weight: 700; color: #0f172a; font-size: .92rem; line-height: 1.3; }
    .etj-details { display: flex; flex-wrap: wrap; gap: .25rem .75rem; font-size: .78rem; color: #64748b; margin-top: .2rem; }
    .etj-type { font-weight: 700; color: #0453cb; }
    .etj-modifier { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #0453cb; background: rgba(4,83,203,.08); flex-shrink: 0; }
    .etj-ajouter { display: flex; align-items: center; justify-content: center; gap: .5rem; min-height: 48px; border: 1px dashed rgba(4,83,203,.4); border-radius: 12px; color: #0453cb; font-weight: 600; font-size: .88rem; text-decoration: none; background: rgba(4,83,203,.04); }
    .etj-vide { text-align: center; color: #64748b; padding: 1.5rem 1rem; background: #f8fafc; border-radius: 12px; margin: 0; }
</style>
@endonce
