{{-- Une ligne de la liste des seances : rendue par la page et par la suite chargee au defilement. --}}
<tr data-li-cle="{{ $seance->id }}" @class(['sdc-row--off' => ! $seance->is_active])>
    <td>{{ \App\Domain\EmploiTemps\JourDeLaSemaine::libelle($seance->jour) ?? 'Inconnu' }}</td>
    {{-- `format('H:i')` et non l'attribut brut : le modèle déclare un
         accesseur qui fait `Carbon::parse()`, donc la lecture en contexte
         chaîne rendrait « 2026-09-15 08:00:00 ». C'est le piège #14 de
         klassci-debugging-discipline, et cette colonne en était le site
         le plus visible. --}}
    <td class="sdc-horaire">
        {{ $seance->heure_debut?->format('H:i') ?? '--:--' }}
        <span class="sdc-muted">→</span>
        {{ $seance->heure_fin?->format('H:i') ?? '--:--' }}
    </td>
    <td>{{ optional(optional($seance->emploiTemps)->classe)->name ?? '—' }}</td>
    <td>{{ optional($seance->matiere)->name ?? '—' }}</td>
    {{-- Par la relation, pas par `$seance->enseignant` : le modèle porte
         une colonne morte de ce nom ET une relation homonyme, et Eloquent
         sert l'attribut avant la relation. La colonne s'affichait donc
         vide sur toutes les lignes. `teacher.user` est chargé par
         `listeFiltree()`, sans quoi ce serait un N+1 sur la page.

         Le compte est testé AVANT le nom, et pas seulement l'affectation :
         `ESBTPTeacher::getNameAttribute()` ne rend jamais `null` — sans
         compte lié, il rend la chaîne « N/A ». Un `?? '—'` écrit ici
         serait donc du code mort, et afficherait ce sigle technique dans
         une colonne en français. --}}
    <td>{{ $seance->teacher?->user ? $seance->teacher->name : '—' }}</td>
    <td>{{ $seance->salle ?: '—' }}</td>
    <td>
        @php
            $ts = $seance->type_seance instanceof \App\Enums\TypeSeance
                ? $seance->type_seance
                : \App\Enums\TypeSeance::fromLegacy($seance->type_seance ?? null);
        @endphp
        <span class="sdc-badge" style="{{ $ts->badgeInlineStyle() }}">
            <i class="fas {{ $ts->badgeIcon() }}"></i>{{ $ts->label() }}
        </span>
    </td>
    <td>
        @if($seance->is_active)
            <span class="sdc-statut sdc-statut--on">Actif</span>
        @else
            <span class="sdc-statut sdc-statut--off">Inactif</span>
        @endif
    </td>
    <td>
        <div class="sdc-actions" style="justify-content:flex-end;">
            <a href="{{ route('esbtp.emploi-temps.show', $seance->emploi_temps_id) }}" class="sdc-act" title="Voir l'emploi du temps">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('esbtp.seances-cours.edit', $seance->id) }}" class="sdc-act" title="Modifier">
                <i class="fas fa-pen"></i>
            </a>
            <button type="button" class="sdc-act sdc-act--danger" data-bs-toggle="modal" data-bs-target="#sdcDelete{{ $seance->id }}" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        </div>

        <div class="modal fade" id="sdcDelete{{ $seance->id }}" tabindex="-1" aria-labelledby="sdcDeleteLabel{{ $seance->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:14px;border:none;">
                    <div class="modal-header" style="border-bottom:1px solid #eef2f7;">
                        <h5 class="modal-title" style="font-size:1rem;font-weight:700;" id="sdcDeleteLabel{{ $seance->id }}">Supprimer cette séance ?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body" style="font-size:.88rem;">
                        <p style="margin-bottom:.5rem;">
                            <strong>{{ \App\Domain\EmploiTemps\JourDeLaSemaine::libelle($seance->jour) ?? 'Jour inconnu' }}</strong>
                            de <strong>{{ $seance->heure_debut?->format('H:i') ?? '--:--' }}</strong>
                            à <strong>{{ $seance->heure_fin?->format('H:i') ?? '--:--' }}</strong>
                            — {{ optional($seance->matiere)->name ?? 'matière inconnue' }}
                        </p>
                        <p style="margin-bottom:.75rem;color:#64748b;">
                            Classe : {{ optional(optional($seance->emploiTemps)->classe)->name ?? '—' }}
                        </p>
                        <div class="sdc-alert sdc-alert--warn" style="margin-bottom:0;">
                            <i class="fas fa-triangle-exclamation" style="margin-top:.15rem;"></i>
                            <div>Cette action est irréversible.</div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top:1px solid #eef2f7;">
                        <button type="button" class="sdc-btn sdc-btn--ghost" data-bs-dismiss="modal">Annuler</button>
                        <form action="{{ route('esbtp.seances-cours.destroy', $seance->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sdc-btn" style="background:#dc2626;color:#fff;">
                                <i class="fas fa-trash"></i>Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>
