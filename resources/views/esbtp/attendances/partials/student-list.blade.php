@foreach($etudiants as $etudiant)
    @php
        $attendance = $existingAttendances[$etudiant->id] ?? null;
        $statut = $attendance ? $attendance->statut : 'present';
        if ($statut === 'late') { $statut = 'retard'; }
        $commentaire = $attendance ? $attendance->commentaire : '';
        $mode = $attendance ? 'modification' : 'nouveau';
        $initials = strtoupper(substr($etudiant->nom ?? '', 0, 1) . substr($etudiant->prenoms ?? '', 0, 1));
        $avatarHue = hexdec(substr(md5($etudiant->nom_complet ?? (string) $etudiant->id), 0, 4)) % 360;
    @endphp
    <tr data-etudiant-id="{{ $etudiant->id }}" data-mode="{{ $mode }}">
        <td>
            <div class="at-etu">
                <span class="at-etu-avatar" @if($etudiant->photo_url) style="background:transparent;padding:0;overflow:hidden;" @else style="background: hsl({{ $avatarHue }}, 55%, 92%); color: hsl({{ $avatarHue }}, 50%, 35%);" @endif>
                    @if($etudiant->photo_url)
                        <img src="{{ $etudiant->photo_url }}" alt="{{ $etudiant->nom_complet }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" onerror="this.onerror=null;this.parentElement.style.background='hsl({{ $avatarHue }}, 55%, 92%)';this.parentElement.style.color='hsl({{ $avatarHue }}, 50%, 35%)';this.outerHTML='{{ $initials ?: '?' }}';">
                    @else
                        {{ $initials ?: '?' }}
                    @endif
                </span>
                <span class="at-etu-name">{{ $etudiant->nom_complet }}</span>
                @if($attendance)
                    <span class="at-etu-badge at-etu-badge--edit" title="Modification d'une présence existante">
                        <i class="fas fa-edit"></i>Modifié
                    </span>
                @endif
            </div>
        </td>
        <td>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="present_{{ $etudiant->id }}" value="present" {{ $statut === 'present' ? 'checked' : '' }}>
                <label class="form-check-label text-success" for="present_{{ $etudiant->id }}">Présent</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="absent_{{ $etudiant->id }}" value="absent" {{ $statut === 'absent' ? 'checked' : '' }}>
                <label class="form-check-label text-danger" for="absent_{{ $etudiant->id }}">Absent</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="retard_{{ $etudiant->id }}" value="retard" {{ $statut === 'retard' ? 'checked' : '' }}>
                <label class="form-check-label text-warning" for="retard_{{ $etudiant->id }}">Retard</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="statuts[{{ $etudiant->id }}]" id="excuse_{{ $etudiant->id }}" value="excuse" {{ $statut === 'excuse' ? 'checked' : '' }}>
                <label class="form-check-label text-info" for="excuse_{{ $etudiant->id }}">Excusé</label>
            </div>
        </td>
        <td>
            <input type="text" name="commentaires[{{ $etudiant->id }}]" class="form-control" placeholder="Commentaire (optionnel)" value="{{ $commentaire }}">
        </td>
    </tr>
@endforeach
