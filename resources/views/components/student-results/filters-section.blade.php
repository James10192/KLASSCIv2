{{-- Composant pour les filtres de la page résultats étudiant --}}
<div class="main-card mb-4">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-filter"></i>
            Filtres de recherche
        </div>
        <div class="main-card-subtitle">Affinez votre recherche de résultats</div>
    </div>
    <div class="main-card-body">
        <form action="{{ route('esbtp.resultats.etudiant', $etudiant) }}" method="GET" class="filter-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Classe</label>
                    <select class="form-select" name="classe_id">
                        @foreach($classes ?? [] as $c)
                            <option value="{{ $c->id }}" {{ isset($classe_id) && $classe_id == $c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Année Universitaire</label>
                    <select class="form-select" name="annee_universitaire_id">
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
                
                <div class="col-md-3">
                    <label class="form-label">Période</label>
                    <select class="form-select" name="periode">
                        @foreach($periodes ?? [] as $p)
                            @php
                                // Gérer les différents formats de période (1, 2, semestre1, semestre2)
                                $isSelected = false;
                                if (isset($periode)) {
                                    $isSelected = $periode == $p->id ||
                                                  $periode == 'semestre'.$p->id ||
                                                  (str_contains($periode, 'semestre') && str_replace('semestre', '', $periode) == $p->id);
                                }
                            @endphp
                            <option value="{{ $p->id }}" {{ $isSelected ? 'selected' : '' }}>
                                {{ $p->nom }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn-acasi primary w-100">
                        <i class="fas fa-search"></i>Filtrer
                    </button>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="include_all_statuses" value="1" 
                               {{ isset($include_all_statuses) && $include_all_statuses ? 'checked' : '' }}>
                        <label class="form-check-label">
                            Inclure tous les étudiants (même ceux avec des inscriptions inactives)
                        </label>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
