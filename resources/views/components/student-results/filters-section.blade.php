{{-- 2. Filter Bar — Classe + Année only (période handled by tabs) --}}
<div class="sr-filter-bar sr-animate sr-animate-delay-1">
    <form id="sr-filter-form" action="{{ route('esbtp.resultats.etudiant', $etudiant) }}" method="GET" class="filter-form">
        {{-- Hidden période field — updated by JS when tabs are clicked --}}
        <input type="hidden" name="periode" id="sr-periode-input" value="{{ $periode ?? 'annuel' }}">

        <div class="sr-filter-row">
            <div class="sr-filter-group">
                <label class="sr-filter-label">Classe</label>
                <select class="sr-filter-select sr-auto-filter" name="classe_id">
                    @foreach($classes ?? [] as $c)
                        <option value="{{ $c->id }}" {{ isset($classe_id) && $classe_id == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sr-filter-group">
                <label class="sr-filter-label">Année universitaire</label>
                <select class="sr-filter-select sr-auto-filter" name="annee_universitaire_id">
                    @foreach($anneesUniversitaires ?? [] as $annee)
                        @php
                            $anneeLabel = $annee->name;
                            if (! $anneeLabel && $annee->start_date && $annee->end_date) {
                                $anneeLabel = $annee->start_date->format('Y').'-'.$annee->end_date->format('Y');
                            }
                            if (! $anneeLabel && isset($annee->annee_debut, $annee->annee_fin)) {
                                $anneeLabel = $annee->annee_debut.'-'.$annee->annee_fin;
                            }
                            if (! $anneeLabel) {
                                $anneeLabel = 'Annee '.$annee->id;
                            }
                        @endphp
                        <option value="{{ $annee->id }}" {{ isset($annee_id) && $annee_id == $annee->id ? 'selected' : '' }}>
                            {{ $anneeLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sr-filter-group" style="flex: 0 0 auto; min-width: auto;">
                <label class="sr-filter-label">&nbsp;</label>
                <button type="submit" class="sr-filter-btn" id="sr-filter-submit">
                    <i class="fas fa-search"></i>Filtrer
                </button>
            </div>
        </div>

        <label class="sr-filter-toggle">
            <input type="checkbox" name="include_all_statuses" value="1" class="sr-auto-filter"
                   {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
            <span class="sr-toggle-track"></span>
            <span>Inclure les inscriptions inactives</span>
        </label>
    </form>
</div>
