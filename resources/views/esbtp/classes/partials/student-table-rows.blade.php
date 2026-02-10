@if($classe->etudiants->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="studentsDataTable">
            <thead class="bg-light">
                <tr>
                    <th>Matricule</th>
                    <th>Nom complet</th>
                    <th>Genre</th>
                    <th>Date de naissance</th>
                    <th>Contact</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classe->etudiants as $etudiant)
                    <tr data-etudiant-id="{{ $etudiant->id }}">
                        <td>{{ $etudiant->matricule }}</td>
                        <td>{{ $etudiant->nom }} {{ $etudiant->prenoms }}</td>
                        <td>{{ $etudiant->genre == 'M' ? 'Masculin' : 'Féminin' }}</td>
                        <td>{{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : 'Non renseigné' }}</td>
                        <td>
                            {{ $etudiant->telephone }}<br>
                            <small>{{ $etudiant->email }}</small>
                        </td>
                        <td>
                            <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $etudiant->id]) }}" class="btn btn-info btn-sm rounded-pill shadow-sm d-inline-flex align-items-center gap-1" title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info mb-0">
        <i class="fas fa-info-circle me-2"></i>Aucun étudiant inscrit dans cette classe pour l'année courante.
    </div>
@endif
