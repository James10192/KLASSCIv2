@extends('layouts.app')

@section('title', 'Tableau de bord des résultats')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Tableau de bord des résultats</h5>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Filtres -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="mb-3"><i class="fas fa-filter me-2 text-secondary"></i>Filtrer les résultats</h6>
                                    <form action="{{ route('esbtp.resultats.dashboard') }}" method="GET" class="row">
                                        <div class="col-md-3 mb-2">
                                            <label for="classe_id" class="form-label">Classe :</label>
                                            <select class="form-select shadow-sm" id="classe_id" name="classe_id">
                                                <option value="">Toutes les classes</option>
                                                @foreach($classes ?? [] as $c)
                                                    <option value="{{ $c->id }}" {{ isset($classe_id) && $classe_id == $c->id ? 'selected' : '' }}>
                                                        {{ $c->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="annee_universitaire_id" class="form-label">Année Universitaire :</label>
                                            <select class="form-select shadow-sm" id="annee_universitaire_id" name="annee_universitaire_id">
                                                @foreach($anneesUniversitaires ?? [] as $annee)
                                                    <option value="{{ $annee->id }}" {{ isset($annee_universitaire_id) && $annee_universitaire_id == $annee->id ? 'selected' : '' }}>
                                                        {{ $annee->annee_debut }}-{{ $annee->annee_fin }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label for="periode" class="form-label">Période :</label>
                                            <select class="form-select shadow-sm" id="periode" name="periode">
                                                @foreach($periodes ?? [] as $p)
                                                    <option value="{{ $p->id }}" {{ isset($periode) && $periode == $p->id ? 'selected' : '' }}>
                                                        {{ $p->nom }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100 shadow-sm">
                                                <i class="fas fa-filter me-1"></i>Appliquer
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="display-6 text-primary mb-2">{{ $stats['total_bulletins'] ?? 0 }}</div>
                                    <div class="text-muted">Total des bulletins</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="display-6 text-success mb-2">{{ $stats['bulletins_publies'] ?? 0 }}</div>
                                    <div class="text-muted">Bulletins publiés</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="display-6 text-warning mb-2">{{ $stats['bulletins_en_attente'] ?? 0 }}</div>
                                    <div class="text-muted">Bulletins en attente</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="display-6 text-info mb-2">{{ $stats['bulletins_avec_signatures'] ?? 0 }}</div>
                                    <div class="text-muted">Bulletins signés</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tableau des bulletins -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Bulletins</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Classe</th>
                                            <th>Période</th>
                                            <th>Année Universitaire</th>
                                            <th>Moyenne Générale</th>
                                            <th>Rang</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($bulletins as $bulletin)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-light rounded-circle p-2 me-2">
                                                            <i class="fas fa-user text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $bulletin->etudiant->nom }} {{ $bulletin->etudiant->prenoms }}</div>
                                                            <div class="text-muted small">{{ $bulletin->etudiant->matricule }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $bulletin->classe->name ?? 'N/A' }}</td>
                                                <td>
                                                    @if($bulletin->periode == 'semestre1')
                                                        <span class="badge bg-info">Premier Semestre</span>
                                                    @elseif($bulletin->periode == 'semestre2')
                                                        <span class="badge bg-info">Deuxième Semestre</span>
                                                    @else
                                                        <span class="badge bg-info">Annuel</span>
                                                    @endif
                                                </td>
                                                <td>{{ $bulletin->anneeUniversitaire->annee_debut ?? '' }}-{{ $bulletin->anneeUniversitaire->annee_fin ?? '' }}</td>
                                                <td>
                                                    <span class="badge rounded-pill {{ $bulletin->moyenne_generale >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                        {{ number_format($bulletin->moyenne_generale, 2) }}/20
                                                    </span>
                                                </td>
                                                <td>{{ $bulletin->rang ?? 'N/A' }} / {{ $bulletin->effectif_classe ?? 'N/A' }}</td>
                                                <td>
                                                    @if($bulletin->is_published)
                                                        <span class="badge bg-success">Publié</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">En attente</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="{{ route('esbtp.resultats.etudiant', ['etudiant' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id]) }}" class="btn btn-sm btn-outline-primary" title="Voir les résultats">
                                                            <i class="fas fa-chart-line"></i>
                                                        </a>
                                                        <a href="{{ route('esbtp.bulletins.show', $bulletin->id) }}" class="btn btn-sm btn-outline-info" title="Voir le bulletin">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        @php $_dbPdfParams = ['bulletin' => $bulletin->etudiant_id, 'classe_id' => $bulletin->classe_id, 'periode' => $bulletin->periode, 'annee_universitaire_id' => $bulletin->annee_universitaire_id]; @endphp
                                                        <a href="{{ route('esbtp.bulletins.pdf-params-preview', $_dbPdfParams) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="Aperçu PDF">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="#" class="btn btn-sm btn-outline-danger" onclick="window.open('{{ route('esbtp.bulletins.pdf-params', $_dbPdfParams) }}', '_blank')" title="Télécharger PDF">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                        <p>Aucun bulletin trouvé avec les critères sélectionnés</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            {{ $bulletins->appends(request()->query())->links() }}
                        </div>
                    </div>

                    <!-- Tableau des résultats par étudiant -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2 text-primary"></i>Résultats par étudiant</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Étudiant</th>
                                            <th>Classe</th>
                                            <th>Matières évaluées</th>
                                            <th>Moyenne Générale</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($resultatsParEtudiant as $etudiant_id => $data)
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-light rounded-circle p-2 me-2">
                                                            <i class="fas fa-user text-primary"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">{{ $data['etudiant']->nom ?? 'N/A' }} {{ $data['etudiant']->prenoms ?? '' }}</div>
                                                            <div class="text-muted small">{{ $data['etudiant']->matricule ?? 'N/A' }}</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>{{ $data['classe']->name ?? 'N/A' }}</td>
                                                <td>{{ count($data['matieres']) }}</td>
                                                <td>
                                                    <span class="badge rounded-pill {{ $data['moyenne_generale'] >= 10 ? 'bg-success' : 'bg-danger' }} px-3 py-2">
                                                        {{ number_format($data['moyenne_generale'], 2) }}/20
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('esbtp.resultats.etudiant', ['etudiant' => $etudiant_id, 'classe_id' => $data['classe']->id ?? '', 'periode' => $periode, 'annee_universitaire_id' => $annee_universitaire_id]) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye me-1"></i>Détails
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="text-muted">
                                                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                        <p>Aucun résultat trouvé avec les critères sélectionnés</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions supplémentaires -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="mb-3"><i class="fas fa-cogs me-2 text-primary"></i>Actions disponibles</h5>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <a href="{{ route('esbtp.bulletins.index') }}" class="btn btn-outline-primary btn-lg w-100 mb-3">
                                                <i class="fas fa-file-alt me-2"></i>Gérer tous les bulletins
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="{{ route('esbtp.resultats.index') }}" class="btn btn-outline-info btn-lg w-100 mb-3">
                                                <i class="fas fa-chart-line me-2"></i>Voir tous les résultats
                                            </a>
                                        </div>
                                        <div class="col-md-4">
                                            <a href="{{ route('esbtp.bulletins.pending') }}" class="btn btn-outline-warning btn-lg w-100 mb-3">
                                                <i class="fas fa-clipboard-check me-2"></i>Traiter les bulletins en attente
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection