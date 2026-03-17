@if($classe->etudiants->count() > 0)
    <div class="table-responsive">
        <table class="table str-table align-middle mb-0" id="studentsDataTable">
            <thead>
                <tr>
                    <th style="width: 50px;"></th>
                    <th>Matricule</th>
                    <th>Nom complet</th>
                    <th>Genre</th>
                    <th>Date de naissance</th>
                    <th>Contact</th>
                    <th style="width: 60px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classe->etudiants as $etudiant)
                    @php
                        $initials = mb_strtoupper(mb_substr($etudiant->nom, 0, 1) . mb_substr($etudiant->prenoms, 0, 1));
                        $isMale = $etudiant->genre == 'M';
                    @endphp
                    <tr data-etudiant-id="{{ $etudiant->id }}">
                        <td>
                            <div class="str-avatar {{ $isMale ? 'str-avatar--m' : 'str-avatar--f' }}">
                                {{ $initials }}
                            </div>
                        </td>
                        <td>
                            <span class="str-matricule">{{ $etudiant->matricule }}</span>
                        </td>
                        <td>
                            <span class="str-name">{{ $etudiant->nom }}</span>
                            <span class="str-prenom">{{ $etudiant->prenoms }}</span>
                        </td>
                        <td>
                            <span class="str-gender-badge">
                                <i class="fas {{ $isMale ? 'fa-mars' : 'fa-venus' }}" style="color: var({{ $isMale ? '--primary, #0453cb' : '--success, #10b981' }});"></i>
                                {{ $isMale ? 'Masculin' : 'Féminin' }}
                            </span>
                        </td>
                        <td>
                            {{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            @if($etudiant->telephone)
                            <div class="str-contact-line">
                                <i class="fas fa-phone"></i>
                                {{ $etudiant->telephone }}
                            </div>
                            @endif
                            @if($etudiant->email)
                            <div class="str-contact-line">
                                <i class="fas fa-envelope"></i>
                                {{ $etudiant->email }}
                            </div>
                            @endif
                            @if(!$etudiant->telephone && !$etudiant->email)
                                <span style="color: var(--text-muted, #94a3b8); font-size: 0.8125rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align: center;">
                            <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $etudiant->id]) }}"
                               class="str-btn-view"
                               title="Voir la fiche">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="str-empty">
        <div class="str-empty-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="str-empty-title">Aucun étudiant inscrit</div>
        <div class="str-empty-text">Cette classe n'a pas encore d'étudiants pour l'année courante.</div>
    </div>
@endif
