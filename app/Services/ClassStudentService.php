<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClassStudentService
{
    /**
     * Recherche les etudiants disponibles pour etre ajoutes a une classe.
     *
     * @param ESBTPClasse $classe
     * @param string $search
     * @return array{etudiants: \Illuminate\Support\Collection, count: int}
     */
    public function searchAvailableStudents(ESBTPClasse $classe, string $search = ''): array
    {
        $anneeCourante = $this->getAnneeCourante();

        $query = ESBTPEtudiant::query()
            ->whereHas('inscriptions', function ($q) use ($anneeCourante, $classe) {
                $q->where('annee_universitaire_id', $anneeCourante->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree')
                  ->where(function ($sub) use ($classe) {
                      $sub->where('classe_id', '!=', $classe->id)
                          ->orWhereNull('classe_id');
                  });
            })
            ->whereDoesntHave('inscriptions', function ($q) use ($anneeCourante, $classe) {
                $q->where('annee_universitaire_id', $anneeCourante->id)
                  ->where('status', 'active')
                  ->where('classe_id', $classe->id);
            });

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('matricule', 'like', "%{$search}%")
                  ->orWhere('nom', 'like', "%{$search}%")
                  ->orWhere('prenoms', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%")
                  ->orWhere('email_personnel', 'like', "%{$search}%");
            });
        }

        $etudiants = $query
            ->with(['inscriptions' => function ($q) use ($anneeCourante) {
                $q->where('annee_universitaire_id', $anneeCourante->id)
                  ->where('status', 'active')
                  ->where('workflow_step', 'etudiant_cree')
                  ->with('classe:id,name,code');
            }])
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->limit(50)
            ->get()
            ->map(function ($etudiant) {
                $inscription = $etudiant->inscriptions->first();
                return [
                    'id' => $etudiant->id,
                    'matricule' => $etudiant->matricule,
                    'nom' => $etudiant->nom,
                    'prenoms' => $etudiant->prenoms,
                    'nom_complet' => $etudiant->nom . ' ' . $etudiant->prenoms,
                    'genre' => $etudiant->genre,
                    'telephone' => $etudiant->telephone,
                    'classe_actuelle' => $inscription && $inscription->classe
                        ? $inscription->classe->name
                        : 'Non affecte',
                    'inscription_id' => $inscription ? $inscription->id : null,
                ];
            });

        return [
            'etudiants' => $etudiants,
            'count' => $etudiants->count(),
        ];
    }

    /**
     * Ajoute des etudiants a une classe en mettant a jour leurs inscriptions.
     *
     * @param ESBTPClasse $classe
     * @param array<int> $etudiantIds
     * @return array{added: int, errors: array<string>}
     */
    public function addStudents(ESBTPClasse $classe, array $etudiantIds): array
    {
        $anneeCourante = $this->getAnneeCourante();
        $added = 0;
        $errors = [];

        DB::beginTransaction();

        try {
            foreach ($etudiantIds as $etudiantId) {
                $inscription = ESBTPInscription::where('etudiant_id', $etudiantId)
                    ->where('annee_universitaire_id', $anneeCourante->id)
                    ->where('status', 'active')
                    ->first();

                if (!$inscription) {
                    $errors[] = "Etudiant ID {$etudiantId}: Aucune inscription active trouvee.";
                    continue;
                }

                if ($inscription->classe_id == $classe->id) {
                    $errors[] = "Etudiant ID {$etudiantId}: Deja dans cette classe.";
                    continue;
                }

                // Restaurer les notes/resultats/bulletins archives si l'etudiant revient dans cette classe
                $restoredNotes = ESBTPNote::withoutGlobalScope('not_archived')
                    ->where('etudiant_id', $etudiantId)
                    ->where('classe_id', $classe->id)
                    ->whereNotNull('archived_at')
                    ->update(['archived_at' => null]);

                $restoredResultats = ESBTPResultat::withoutGlobalScope('not_archived')
                    ->where('etudiant_id', $etudiantId)
                    ->where('classe_id', $classe->id)
                    ->whereNotNull('archived_at')
                    ->update(['archived_at' => null]);

                $restoredBulletins = ESBTPBulletin::withoutGlobalScope('not_archived')
                    ->where('etudiant_id', $etudiantId)
                    ->where('classe_id', $classe->id)
                    ->whereNotNull('archived_at')
                    ->update(['archived_at' => null]);

                if ($restoredNotes + $restoredResultats + $restoredBulletins > 0) {
                    \Log::info('Donnees restaurees pour etudiant reintegre', [
                        'etudiant_id' => $etudiantId,
                        'classe_id' => $classe->id,
                        'notes' => $restoredNotes,
                        'resultats' => $restoredResultats,
                        'bulletins' => $restoredBulletins,
                    ]);
                }

                $inscription->update([
                    'classe_id' => $classe->id,
                    'affectation_status' => $inscription->classe_id ? 'réaffecté' : ESBTPInscription::DEFAULT_AFFECTATION_STATUS,
                    'updated_by' => Auth::id(),
                ]);

                $added++;
            }

            DB::commit();

            \Log::info('Etudiants ajoutes a la classe', [
                'classe_id' => $classe->id,
                'classe_name' => $classe->name,
                'added' => $added,
                'errors_count' => count($errors),
                'user_id' => Auth::id(),
            ]);

            return ['added' => $added, 'errors' => $errors];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Retire des etudiants d'une classe, avec transfert optionnel.
     *
     * @param ESBTPClasse $classe
     * @param array<int> $etudiantIds
     * @param int|null $destinationClasseId
     * @return array{removed: int, errors: array<string>, action_message: string}
     */
    public function removeStudents(ESBTPClasse $classe, array $etudiantIds, ?int $destinationClasseId = null): array
    {
        $anneeCourante = $this->getAnneeCourante();
        $removed = 0;
        $errors = [];

        $destinationClasse = $destinationClasseId
            ? ESBTPClasse::find($destinationClasseId)
            : null;

        DB::beginTransaction();

        try {
            foreach ($etudiantIds as $etudiantId) {
                $inscription = ESBTPInscription::where('etudiant_id', $etudiantId)
                    ->where('annee_universitaire_id', $anneeCourante->id)
                    ->where('status', 'active')
                    ->where('classe_id', $classe->id)
                    ->first();

                if (!$inscription) {
                    $errors[] = "Etudiant ID {$etudiantId}: Pas d'inscription active dans cette classe.";
                    continue;
                }

                // Archiver les notes, resultats et bulletins de l'ancienne classe
                $now = now();
                $archivedNotes = ESBTPNote::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $classe->id)
                    ->update(['archived_at' => $now]);

                $archivedResultats = ESBTPResultat::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $classe->id)
                    ->update(['archived_at' => $now]);

                $archivedBulletins = ESBTPBulletin::where('etudiant_id', $etudiantId)
                    ->where('classe_id', $classe->id)
                    ->update(['archived_at' => $now]);

                \Log::info('Donnees archivees pour etudiant', [
                    'etudiant_id' => $etudiantId,
                    'classe_id' => $classe->id,
                    'notes' => $archivedNotes,
                    'resultats' => $archivedResultats,
                    'bulletins' => $archivedBulletins,
                ]);

                if ($destinationClasseId) {
                    $inscription->update([
                        'classe_id' => $destinationClasseId,
                        'affectation_status' => 'réaffecté',
                        'updated_by' => Auth::id(),
                    ]);
                } else {
                    $inscription->update([
                        'classe_id' => null,
                        'affectation_status' => 'non_affecté',
                        'updated_by' => Auth::id(),
                    ]);
                }

                $removed++;
            }

            DB::commit();

            $actionMsg = $destinationClasse
                ? "transfere(s) vers {$destinationClasse->name}"
                : "retire(s) de la classe (non affectes)";

            \Log::info('Etudiants retires de la classe', [
                'classe_id' => $classe->id,
                'destination_classe_id' => $destinationClasseId,
                'removed' => $removed,
                'user_id' => Auth::id(),
            ]);

            return ['removed' => $removed, 'errors' => $errors, 'action_message' => $actionMsg];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verifie les donnees academiques des etudiants avant retrait/transfert.
     *
     * @param ESBTPClasse $classe
     * @param array<int> $etudiantIds
     * @return array
     */
    public function checkStudentData(ESBTPClasse $classe, array $etudiantIds): array
    {
        $students = [];

        foreach ($etudiantIds as $etudiantId) {
            $etudiant = ESBTPEtudiant::find($etudiantId);
            if (!$etudiant) {
                continue;
            }

            $notesCount = ESBTPNote::where('etudiant_id', $etudiantId)
                ->where('classe_id', $classe->id)
                ->count();

            $resultatsCount = ESBTPResultat::where('etudiant_id', $etudiantId)
                ->where('classe_id', $classe->id)
                ->count();

            $bulletinsCount = ESBTPBulletin::where('etudiant_id', $etudiantId)
                ->where('classe_id', $classe->id)
                ->count();

            $students[] = [
                'etudiant_id' => $etudiantId,
                'nom' => $etudiant->nom . ' ' . $etudiant->prenoms,
                'notes_count' => $notesCount,
                'resultats_count' => $resultatsCount,
                'bulletins_count' => $bulletinsCount,
                'has_data' => ($notesCount + $resultatsCount + $bulletinsCount) > 0,
            ];
        }

        $totalData = collect($students)->sum(fn($s) => $s['notes_count'] + $s['resultats_count'] + $s['bulletins_count']);

        return [
            'students' => $students,
            'has_any_data' => $totalData > 0,
            'total_notes' => collect($students)->sum('notes_count'),
            'total_resultats' => collect($students)->sum('resultats_count'),
            'total_bulletins' => collect($students)->sum('bulletins_count'),
        ];
    }

    /**
     * @throws \RuntimeException if no active academic year
     */
    private function getAnneeCourante(): ESBTPAnneeUniversitaire
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (!$annee) {
            throw new \RuntimeException('Aucune annee universitaire active.');
        }
        return $annee;
    }
}
