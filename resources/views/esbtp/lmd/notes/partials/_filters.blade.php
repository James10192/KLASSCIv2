    {{-- Filtres partages avec les notes BTS, appliques en AJAX sans recharger la page. --}}
    <section class="ln-filter-panel" aria-label="Filtres des notes LMD">
        <div class="ln-filter-heading"><i class="fas fa-filter"></i>Filtres de recherche</div>
        <form method="GET" action="{{ route('esbtp.lmd.notes.index') }}" id="lmdYearForm">
            <div class="ln-filter-grid">
                <div class="ln-filter-field">
                    <label for="lmd_annee_universitaire_id">Année universitaire</label>
                    <x-au-select name="annee_universitaire_id" id="lmd_annee_universitaire_id" label="Année universitaire"
                        :value="$anneeSelectionnee?->id" icon="fa-calendar" :searchable="false" :placeholder-is-first-option="false"
                        :options="$anneesUniversitaires->mapWithKeys(fn($a) => [$a->id => $a->name.($a->is_current ? ' · courante' : '')])->toArray()" />
                </div>
                <div class="ln-filter-field">
                    <label for="lmd_search">Recherche</label>
                    <input type="search" id="lmd_search" name="search" class="ln-filter-search" maxlength="120"
                        value="{{ request('search') }}" placeholder="Classe, code, filière, niveau…" autocomplete="off">
                </div>
                <div class="ln-filter-field">
                    <label for="lmd_filiere_id">Filière</label>
                    <x-au-select id="lmd_filiere_id" name="filiere_id" label="Filière" icon="fa-graduation-cap"
                        :value="request('filiere_id', '')" placeholder="Toutes les filières"
                        :searchable="$filieres->count() > 8"
                        :options="$filieres->mapWithKeys(fn($f) => [$f->id => $f->name])->toArray()" />
                </div>
                <div class="ln-filter-field">
                    <label for="lmd_niveau_id">Niveau</label>
                    <x-au-select id="lmd_niveau_id" name="niveau_id" label="Niveau" icon="fa-layer-group"
                        :value="request('niveau_id', '')" placeholder="Tous les niveaux"
                        :searchable="$niveaux->count() > 8"
                        :options="$niveaux->mapWithKeys(fn($n) => [$n->id => $n->name])->toArray()" />
                </div>
                <div class="ln-filter-field">
                    <label for="lmd_statut">Statut</label>
                    <x-au-select id="lmd_statut" name="statut" label="Statut" icon="fa-circle-check"
                        :value="request('statut', '')" placeholder="Tous les statuts"
                        :options="['active' => 'Actives', 'inactive' => 'Inactives']" />
                </div>
                <div class="ln-filter-field">
                    <label for="lmd_capacite">Capacité</label>
                    <x-au-select id="lmd_capacite" name="capacite" label="Capacité" icon="fa-users"
                        :value="request('capacite', '')" placeholder="Toutes les capacités"
                        :options="['disponible' => 'Disponibles', 'pleine' => 'Pleines']" />
                </div>
            </div>
            <div class="ln-filter-actions">
                <button type="submit" class="ln-filter-btn"><i class="fas fa-search me-1"></i>Filtrer</button>
                <button type="button" class="ln-filter-btn ln-filter-btn--reset" id="lmd-reset-filters">
                    <i class="fas fa-times me-1"></i>Réinitialiser
                </button>
                <span class="ln-filter-count"><i class="fas fa-list me-1"></i><strong id="lmd-filter-count">{{ $totalClasses }}</strong> classe(s)</span>
            </div>
            <div id="lmd-year-feedback" class="ln-filter-feedback" role="status" aria-live="polite"></div>
        </form>
    </section>

