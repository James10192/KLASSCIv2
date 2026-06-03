@php
    $volumeHoraireTotal = $volumeHoraireTotal ?? 0;
    $isGlobal = $isGlobal ?? false;
    $fmtHours = fn ($h) => \App\Support\Attendance\HoursFormatter::format((float) $h);
@endphp
<div class="amh-panel" data-classe-id="{{ $classe->id }}" data-matiere-id="{{ $isGlobal ? '' : $matiere->id }}" data-periode="{{ $periode }}" data-annee-id="{{ $anneeUniversitaire->id }}" data-volume-total="{{ $volumeHoraireTotal }}" data-mode="{{ $isGlobal ? 'global' : 'per-matiere' }}">
    <div class="amh-header">
        <div class="amh-header__titles">
            <div class="amh-header__eyebrow">
                {{ $classe->name }}
                @if($isGlobal)
                    · <span class="amh-chip amh-chip--blue"><i class="fas fa-globe"></i>Saisie globale</span>
                @else
                    · {{ $matiere->name }}
                @endif
            </div>
            <div class="amh-header__title">
                @if($isGlobal)
                    Saisie globale (sans matière) — {{ ucfirst(str_replace('semestre', 'Semestre ', $periode)) }}
                @else
                    Saisie manuelle — {{ ucfirst(str_replace('semestre', 'Semestre ', $periode)) }}
                @endif
            </div>
            <div class="amh-header__meta">
                Année {{ $anneeUniversitaire->name ?? $anneeUniversitaire->libelle ?? '—' }}
                · {{ $etudiants->count() }} étudiant{{ $etudiants->count() > 1 ? 's' : '' }}
                @if(!$isGlobal && $volumeHoraireTotal > 0)
                    · <span class="amh-chip amh-chip--blue"><i class="fas fa-clock"></i>Volume prévu : {{ $fmtHours($volumeHoraireTotal) }}h</span>
                @elseif(!$isGlobal)
                    · <span class="amh-chip amh-chip--muted"><i class="fas fa-circle-info"></i>Pas de volume horaire défini dans les planifications</span>
                @endif
                @if($existing->count() > 0)
                    · <span class="amh-chip amh-chip--blue"><i class="fas fa-database"></i>{{ $existing->count() }} ligne{{ $existing->count() > 1 ? 's' : '' }} sauvegardée{{ $existing->count() > 1 ? 's' : '' }}</span>
                @endif
            </div>
        </div>
        @if(!$etudiants->isEmpty())
            <div class="amh-header__actions">
                <button type="button" class="amh-btn amh-btn--ghost" id="amh-reset-all"
                        title="Annule toutes les modifications non enregistrées et restaure les valeurs sauvegardées">
                    <i class="fas fa-rotate-left"></i>Tout réinitialiser
                </button>
            </div>
        @endif
    </div>

    @if($existingSessionsCount > 0)
        <div class="amh-alert amh-alert--warning">
            <i class="fas fa-triangle-exclamation"></i>
            <div>
                <strong>{{ $existingSessionsCount }} séance{{ $existingSessionsCount > 1 ? 's' : '' }} déjà enregistrée{{ $existingSessionsCount > 1 ? 's' : '' }}</strong>
                pour cette matière et cette année.
                Si vous saisissez des totaux manuels ci-dessous, ils deviendront <strong>la source prioritaire</strong> sur le bulletin pour cette matière et cette période.
                Les séances restent conservées et seront réutilisées automatiquement si vous supprimez la ligne manuelle.
            </div>
        </div>
    @endif

    @if($isGlobal)
        <div class="amh-alert amh-alert--info">
            <i class="fas fa-circle-info"></i>
            <div>
                La saisie <strong>globale</strong> enregistre des heures d'absence et de présence <strong>sans les rattacher à une matière</strong>.
                Ces heures n'écrasent <strong>jamais</strong> une saisie par matière : sur le bulletin, la priorité est
                <em>saisie par matière &gt; saisie globale &gt; séances</em>. Elles s'affichent en en-tête du bulletin.
            </div>
        </div>
    @endif

    @if($etudiants->isEmpty())
        <div class="amh-alert amh-alert--muted">
            <i class="fas fa-user-slash"></i>
            <div>Aucun étudiant inscrit dans cette classe pour l'année universitaire en cours.</div>
        </div>
    @else
        <form id="amh-form" class="amh-form" autocomplete="off">
            @csrf
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            @if(!$isGlobal)
                <input type="hidden" name="matiere_id" value="{{ $matiere->id }}">
            @endif
            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">
            <input type="hidden" name="periode" value="{{ $periode }}">

            <div class="amh-table-wrap">
                <table class="amh-table">
                    <thead>
                        <tr>
                            <th class="amh-col-name">Étudiant</th>
                            <th class="amh-col-hours">Abs. justifiées (h)</th>
                            <th class="amh-col-hours">Abs. non justifiées (h)</th>
                            <th class="amh-col-total" title="Total absences = Justifiées + Non justifiées">Total absences</th>
                            <th class="amh-col-note">Note</th>
                            <th class="amh-col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($etudiants as $i => $etu)
                            @php
                                $row = $existing->get($etu->id);
                                $origPres  = $row ? rtrim(rtrim(number_format((float) $row->heures_presence, 2, '.', ''), '0'), '.') : '';
                                $origAbsJ  = $row ? rtrim(rtrim(number_format((float) $row->heures_absence_justifiees, 2, '.', ''), '0'), '.') : '';
                                $origAbsNj = $row ? rtrim(rtrim(number_format((float) $row->heures_absence_non_justifiees, 2, '.', ''), '0'), '.') : '';
                                $origNotes = $row?->notes ?? '';
                                $initialState = $row ? 'saved' : 'empty';
                            @endphp
                            <tr class="amh-row"
                                data-etudiant-id="{{ $etu->id }}"
                                data-row-id="{{ $row?->id }}"
                                data-state="{{ $initialState }}"
                                data-orig-pres="{{ $origPres }}"
                                data-orig-abs-j="{{ $origAbsJ }}"
                                data-orig-abs-nj="{{ $origAbsNj }}"
                                data-orig-notes="{{ $origNotes }}">
                                <td class="amh-col-name">
                                    <div class="amh-etu">
                                        <span class="amh-etu__name">{{ trim(mb_strtoupper($etu->nom ?? '', 'UTF-8').' '.($etu->prenoms ?? '')) }}</span>
                                        <span class="amh-state-chip amh-state-chip--saved" data-state-for="saved"
                                              title="Valeurs sauvegardées sur le bulletin">
                                            <i class="fas fa-check"></i>Enregistré
                                        </span>
                                        <span class="amh-state-chip amh-state-chip--modified" data-state-for="modified"
                                              title="Modifications non sauvegardées">
                                            <i class="fas fa-pen"></i>Modifié
                                        </span>
                                        <span class="amh-state-chip amh-state-chip--empty" data-state-for="empty"
                                              title="Aucune donnée — le bulletin utilisera les séances">
                                            <i class="far fa-circle"></i>Vierge
                                        </span>
                                    </div>
                                </td>
                                {{-- Sous-lot A : champ heures_presence retiré du formulaire (les écoles ne saisissent que les absences). Un input hidden 0 est gardé pour rester compat avec backend qui valide nullable|numeric|min:0 — le bulletin déduit la présence implicite du volume total. --}}
                                <input type="hidden" name="entries[{{ $i }}][heures_presence]" value="0" class="amh-input--pres">
                                <td class="amh-col-hours">
                                    <input type="number" step="0.25" min="0" max="999.99"
                                           name="entries[{{ $i }}][heures_absence_justifiees]"
                                           value="{{ $origAbsJ }}"
                                           class="amh-input amh-input--abs-j"
                                           placeholder="0">
                                </td>
                                <td class="amh-col-hours">
                                    <input type="number" step="0.25" min="0" max="999.99"
                                           name="entries[{{ $i }}][heures_absence_non_justifiees]"
                                           value="{{ $origAbsNj }}"
                                           class="amh-input amh-input--abs-nj"
                                           placeholder="0">
                                </td>
                                <td class="amh-col-total">
                                    <span class="amh-row-total" data-row-total>0</span>
                                    <span class="amh-row-total-unit">h</span>
                                </td>
                                <td class="amh-col-note">
                                    <input type="text" maxlength="500"
                                           name="entries[{{ $i }}][notes]"
                                           value="{{ $origNotes }}"
                                           class="amh-input amh-input--note"
                                           placeholder="Note optionnelle">
                                </td>
                                <td class="amh-col-actions">
                                    <input type="hidden" name="entries[{{ $i }}][etudiant_id]" value="{{ $etu->id }}">
                                    <div class="amh-row-actions">
                                        <button type="button" class="amh-btn amh-btn--ghost-sm amh-reset-btn"
                                                title="Réinitialiser cette ligne">
                                            <i class="fas fa-rotate-left"></i>
                                        </button>
                                        @if($row)
                                            <button type="button" class="amh-btn amh-btn--danger amh-delete-btn"
                                                    data-row-id="{{ $row->id }}"
                                                    title="Supprimer la saisie manuelle (le bulletin retombera sur les séances)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="amh-actions">
                <button type="submit" class="amh-btn amh-btn--primary" id="amh-submit">
                    <i class="fas fa-save"></i>Enregistrer les heures
                </button>
                <span class="amh-hint">
                    Laisser vide = aucune saisie manuelle pour cet étudiant (le bulletin utilise les séances).
                </span>
            </div>
        </form>
    @endif
</div>
