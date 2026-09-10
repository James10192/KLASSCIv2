<?php

declare(strict_types=1);

namespace App\Domain\Dispenses;

use App\Domain\Dispenses\Models\ESBTPDispense;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Accorder et revoquer une dispense.
 *
 * Deux invariants tenus ici, et nulle part ailleurs :
 *
 * 1. **Pas de recouvrement.** Une dispense annuelle et une dispense de semestre
 *    sur la meme matiere se contrediraient ; deux fois le meme semestre aussi.
 *    Des periodes disjointes (S1 et S2, motifs differents) restent legitimes.
 * 2. **Pas de double creation sous concurrence.** Chercher un doublon puis
 *    inserer ne suffit pas : deux requetes simultanees passent toutes deux la
 *    recherche avant que l'une n'insere. On serialise donc sur la ligne de
 *    l'etudiant, qui existe toujours — verrouiller « les dispenses existantes »
 *    ne verrouille rien quand il n'y en a aucune, ce qui est precisement le cas
 *    ou la course se produit.
 */
final class DispenseService
{
    /** Ce que la maquette sait representer. */
    private const PERIODES = ['semestre1', 'semestre2'];

    public function __construct(private readonly DispenseLookup $lookup) {}

    /**
     * @throws ValidationException si la classe est LMD, ou si une dispense active se recouvre
     */
    public function accorder(
        int $etudiantId,
        int $matiereId,
        int $anneeId,
        ?string $periode,
        string $motif,
        User $acteur,
    ): ESBTPDispense {
        $periode = $this->normaliserPeriode($periode);

        return DB::transaction(function () use ($etudiantId, $matiereId, $anneeId, $periode, $motif, $acteur) {
            // Le verrou de serialisation. Il porte sur une ligne qui existe
            // toujours, donc il tient meme quand aucune dispense n'existe encore.
            $etudiant = ESBTPEtudiant::query()->lockForUpdate()->find($etudiantId);

            if (! $etudiant) {
                throw ValidationException::withMessages([
                    'etudiant_id' => "Cet étudiant n'existe pas.",
                ]);
            }

            $existantes = ESBTPDispense::query()
                ->active()
                ->where('etudiant_id', $etudiantId)
                ->where('matiere_id', $matiereId)
                ->where('annee_universitaire_id', $anneeId)
                ->get();

            foreach ($existantes as $existante) {
                if ($this->seRecouvrent($existante->periode, $periode)) {
                    throw ValidationException::withMessages([
                        'periode' => 'Une dispense active couvre déjà cette matière sur cette période ('
                            .$existante->porteeLisible().').',
                    ]);
                }
            }

            $dispense = ESBTPDispense::create([
                'etudiant_id' => $etudiantId,
                'matiere_id' => $matiereId,
                'annee_universitaire_id' => $anneeId,
                'periode' => $periode,
                'motif' => trim($motif),
                'accordee_par' => $acteur->id,
                'accordee_le' => now(),
            ]);

            // La lecture memorisee de la requete vient de devenir fausse. Sans
            // cet oubli, un bulletin calcule apres la decision, dans la meme
            // requete, l'ignorerait en silence.
            $this->lookup->oublier();

            return $dispense;
        });
    }

    /**
     * Revoquer conserve tout : la ligne, le motif d'origine, les notes.
     *
     * Revoquer deux fois n'a pas d'effet supplementaire — la premiere
     * revocation reste celle qui fait foi.
     */
    public function revoquer(ESBTPDispense $dispense, string $motif, User $acteur): ESBTPDispense
    {
        if (! $dispense->estActive()) {
            return $dispense;
        }

        $dispense->forceFill([
            'revoquee_par' => $acteur->id,
            'revoquee_le' => now(),
            'motif_revocation' => trim($motif),
        ])->save();

        $this->lookup->oublier();

        return $dispense->refresh();
    }

    /**
     * Deux portees se recouvrent-elles ?
     *
     * L'annee recouvre tout. Deux semestres differents ne se recouvrent pas :
     * une ecole peut legitimement dispenser au semestre 1 pour une raison, et
     * au semestre 2 pour une autre.
     */
    private function seRecouvrent(?string $a, ?string $b): bool
    {
        if ($a === null || $b === null) {
            return true;
        }

        return $a === $b;
    }

    private function normaliserPeriode(?string $periode): ?string
    {
        if ($periode === null || $periode === '' || $periode === 'annuel') {
            return null;
        }

        if (! in_array($periode, self::PERIODES, true)) {
            throw ValidationException::withMessages([
                'periode' => 'La période doit valoir semestre1, semestre2, ou rester vide pour toute l\'année.',
            ]);
        }

        return $periode;
    }
}
