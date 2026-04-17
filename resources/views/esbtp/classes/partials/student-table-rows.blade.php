{{--
    Table des étudiants de la classe (vue admin/secrétaire/coordinateur).
    Design cs-* monochrome + photos avec fallback HSL.
    Pattern identique à resources/views/esbtp/attendances/partials/student-list.blade.php
--}}
@if($classe->etudiants->count() > 0)
    <div class="table-responsive">
        <table class="table cs-table align-middle mb-0" id="studentsDataTable">
            <thead>
                <tr>
                    <th>Étudiant</th>
                    <th>Matricule</th>
                    <th>Genre</th>
                    <th>Naissance</th>
                    <th>Contact</th>
                    <th style="width:60px;text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($classe->etudiants as $etudiant)
                    @php
                        $nomComplet = trim(($etudiant->nom ?? '').' '.($etudiant->prenoms ?? ''));
                        $initials = mb_strtoupper(mb_substr($etudiant->nom ?? '', 0, 1).mb_substr($etudiant->prenoms ?? '', 0, 1));
                        $avatarHue = hexdec(substr(md5($nomComplet ?: (string)$etudiant->id), 0, 4)) % 360;
                        $isMale = $etudiant->genre === 'M';
                    @endphp
                    <tr data-etudiant-id="{{ $etudiant->id }}"
                        data-matricule="{{ $etudiant->matricule }}"
                        data-nom="{{ $nomComplet }}">
                        <td>
                            <div class="cs-etu-cell">
                                <span class="cs-etu-avatar"
                                      @if($etudiant->photo_url)
                                          style="background:transparent;padding:0;overflow:hidden;"
                                      @else
                                          style="background: hsl({{ $avatarHue }}, 55%, 92%); color: hsl({{ $avatarHue }}, 50%, 35%);"
                                      @endif>
                                    @if($etudiant->photo_url)
                                        <img src="{{ $etudiant->photo_url }}"
                                             alt="{{ $nomComplet }}"
                                             width="36" height="36"
                                             loading="lazy"
                                             style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                                             onerror="this.onerror=null;this.parentElement.style.background='hsl({{ $avatarHue }}, 55%, 92%)';this.parentElement.style.color='hsl({{ $avatarHue }}, 50%, 35%)';this.outerHTML='{{ $initials ?: '?' }}';">
                                    @else
                                        {{ $initials ?: '?' }}
                                    @endif
                                </span>
                                <div>
                                    <div class="cs-etu-name">{{ $etudiant->nom }} <span class="cs-etu-prenom">{{ $etudiant->prenoms }}</span></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cs-matricule">{{ $etudiant->matricule }}</span>
                        </td>
                        <td>
                            <span class="cs-gender">
                                <i class="fas {{ $isMale ? 'fa-mars' : 'fa-venus' }}" style="color: var({{ $isMale ? '--cs-primary' : '--cs-accent' }});"></i>
                                {{ $isMale ? 'Masculin' : 'Féminin' }}
                            </span>
                        </td>
                        <td>
                            {{ $etudiant->date_naissance ? $etudiant->date_naissance->format('d/m/Y') : '—' }}
                        </td>
                        <td>
                            @if($etudiant->telephone)
                                <div class="cs-contact-line">
                                    <i class="fas fa-phone"></i>
                                    {{ $etudiant->telephone }}
                                </div>
                            @endif
                            @if($etudiant->email)
                                <div class="cs-contact-line">
                                    <i class="fas fa-envelope"></i>
                                    {{ $etudiant->email }}
                                </div>
                            @endif
                            @if(!$etudiant->telephone && !$etudiant->email)
                                <span style="color: var(--cs-muted);font-size:.8rem;">—</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <a href="{{ route('esbtp.etudiants.show', ['etudiant' => $etudiant->id]) }}"
                               class="cs-btn-view"
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
    <div class="cs-empty">
        <div class="cs-empty-icon">
            <i class="fas fa-user-graduate"></i>
        </div>
        <div class="cs-empty-title">Aucun étudiant inscrit</div>
        <div class="cs-empty-text">Cette classe n'a pas encore d'étudiants pour l'année courante.</div>
    </div>
@endif
