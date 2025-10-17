@foreach($etudiants as $etudiant)
    @php
        // Récupérer l'attendance existante pour cet étudiant (si elle existe)
        $attendance = $existingAttendances[$etudiant->id] ?? null;
        $statut = $attendance ? $attendance->statut : 'present'; // Défaut: present
        // Normaliser 'late' en 'retard' pour compatibilité avec le formulaire
        if ($statut === 'late') {
            $statut = 'retard';
        }
        $commentaire = $attendance ? $attendance->commentaire : '';
        $mode = $attendance ? 'modification' : 'nouveau';
        $modeLabel = $attendance ? '<i class="fas fa-edit"></i> Modification' : '<i class="fas fa-plus-circle"></i> Nouveau marquage';
        $modeBadgeClass = $attendance ? 'badge bg-warning text-dark' : 'badge bg-success';
    @endphp
    <tr data-etudiant-id="{{ $etudiant->id }}" data-mode="{{ $mode }}">
        <td>
            <div class="d-flex align-items-center justify-content-between">
                <span>{{ $etudiant->nom_complet }}</span>
                <span class="{{ $modeBadgeClass }} ms-2" style="font-size: 0.75rem;">{!! $modeLabel !!}</span>
            </div>
        </td>
        <td>
            <div class="form-check form-check-inline">
                <input class="form-check-input"
                       type="radio"
                       name="statuts[{{ $etudiant->id }}]"
                       id="present_{{ $etudiant->id }}"
                       value="present"
                       {{ $statut === 'present' ? 'checked' : '' }}>
                <label class="form-check-label text-success" for="present_{{ $etudiant->id }}">Présent</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input"
                       type="radio"
                       name="statuts[{{ $etudiant->id }}]"
                       id="absent_{{ $etudiant->id }}"
                       value="absent"
                       {{ $statut === 'absent' ? 'checked' : '' }}>
                <label class="form-check-label text-danger" for="absent_{{ $etudiant->id }}">Absent</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input"
                       type="radio"
                       name="statuts[{{ $etudiant->id }}]"
                       id="retard_{{ $etudiant->id }}"
                       value="retard"
                       {{ $statut === 'retard' ? 'checked' : '' }}>
                <label class="form-check-label text-warning" for="retard_{{ $etudiant->id }}">Retard</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input"
                       type="radio"
                       name="statuts[{{ $etudiant->id }}]"
                       id="excuse_{{ $etudiant->id }}"
                       value="excuse"
                       {{ $statut === 'excuse' ? 'checked' : '' }}>
                <label class="form-check-label text-info" for="excuse_{{ $etudiant->id }}">Excusé</label>
            </div>
        </td>
        <td>
            <input type="text"
                   name="commentaires[{{ $etudiant->id }}]"
                   class="form-control"
                   placeholder="Commentaire (optionnel)"
                   value="{{ $commentaire }}">
        </td>
    </tr>
@endforeach
