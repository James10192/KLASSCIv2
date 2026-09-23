<?php

namespace App\Services\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * L'ecriture d'un element constitutif depuis le modal ECUE de /esbtp/lmd/ue.
 *
 * La matiere, sa cle etrangere et sa ligne de pivot s'ecrivent ensemble, dans
 * une seule transaction : un echec au pivot ne doit pas laisser un code libere
 * et une matiere creee mais rattachee a rien.
 */
class EcritureEcue
{
    public function __construct(
        private CodeDeMatiere $codes,
        private CompositionUe $composition,
    ) {}

    /**
     * Ajoute un element a l'unite, pour la maquette `$portee` : une matiere
     * existante (`matiere_id`) ou une nouvelle (`name`, `code`).
     *
     * @return string|null Le message de liberation du code, s'il y en a eu une.
     */
    public function ajouter(ESBTPUniteEnseignement $ue, int $portee, array $donnees): ?string
    {
        $pivot = $this->pivot($donnees);

        return DB::transaction(function () use ($ue, $portee, $donnees, $pivot) {
            $codeLibere = null;

            if (! empty($donnees['matiere_id'])) {
                $matiere = ESBTPMatiere::findOrFail($donnees['matiere_id']);
                $this->refuserAbsorptionMatiereBts($matiere);
            } else {
                [$matiere, $codeLibere] = $this->codes->ecrire($donnees['code'], null, $ue, $portee, fn () => ESBTPMatiere::create([
                    'name' => $donnees['name'],
                    'code' => $donnees['code'],
                    'unite_enseignement_id' => $ue->id, // FK direct (retro-compat)
                    'credit_ecue' => $pivot['credit_ecue'],
                    'coefficient_ecue' => $pivot['coefficient_ecue'],
                    'ordre_bulletin' => $pivot['ordre_bulletin'],
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]));
            }

            // Cle etrangere (retro-compat) : on ne l'ecrit que si elle est libre
            // ou deja la notre. La reprendre a l'unite voisine qui ne tient ses
            // elements que par elle la depouillerait, en silence — meme regle que
            // synchroniserEcues(). Le partage passe par le pivot.
            $proprietaireId = $matiere->unite_enseignement_id;
            if ($proprietaireId !== null && (int) $proprietaireId !== (int) $ue->id) {
                $this->composition->materialiserDepuisCleEtrangere((int) $proprietaireId);
            } elseif ($proprietaireId === null) {
                $matiere->update(['unite_enseignement_id' => $ue->id, 'updated_by' => auth()->id()]);
            }

            // Pour LA maquette visee : `syncWithoutDetaching` se calerait sur le
            // seul `matiere_id` et reecrirait une version reservee.
            $this->composition->poser($ue, (int) $matiere->id, $pivot, $portee);

            return $codeLibere;
        });
    }

    /**
     * Met a jour la matiere et sa ligne de pivot dans la maquette `$portee`.
     *
     * @return string|null Le message de liberation du code, s'il y en a eu une.
     */
    public function modifier(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue, int $portee, array $donnees): ?string
    {
        $maj = fn () => $ecue->update([
            'name' => $donnees['name'] ?? $ecue->name,
            'code' => $donnees['code'] ?? $ecue->code,
            'coefficient_ecue' => $donnees['coefficient_ecue'] ?? $ecue->coefficient_ecue,
            'credit_ecue' => $donnees['credit_ecue'] ?? $ecue->credit_ecue,
            'ordre_bulletin' => $donnees['ordre_bulletin'] ?? $ecue->ordre_bulletin,
            'updated_by' => auth()->id(),
        ]);

        return DB::transaction(function () use ($ue, $ecue, $portee, $donnees, $maj) {
            $codeLibere = null;
            if (isset($donnees['code']) && $donnees['code'] !== $ecue->code) {
                [, $codeLibere] = $this->codes->ecrire($donnees['code'], (int) $ecue->id, $ue, $portee, $maj);
            } else {
                $maj();
            }

            // Le pivot de CETTE maquette : sans la portee, modifier le coefficient
            // commun reecrivait la ligne qu'un parcours avait surchargee.
            $this->composition->poser($ue, (int) $ecue->id, $this->pivot($donnees), $portee);

            return $codeLibere;
        });
    }

    /**
     * Refuse d'absorber dans le LMD une matiere du cursus BTS.
     *
     * Reutiliser le code d'une matiere BTS ecrirait `unite_enseignement_id` sur
     * elle : elle deviendrait un ECUE et disparaitrait de tous les selecteurs
     * BTS, qui filtrent precisement sur `unite_enseignement_id IS NULL` — en
     * emportant ses evaluations et ses notes. `esbtp_matieres` etant partagee
     * par les deux cursus, l'effet porte sur les instances BTS en service.
     *
     * Une matiere deja rattachee a une UE — par la colonne ou par le pivot
     * `esbtp_ue_matiere`, le partage d'un ECUE entre deux UE etant legitime —
     * n'est pas une matiere BTS : elle passe.
     */
    public function refuserAbsorptionMatiereBts(?ESBTPMatiere $matiere): void
    {
        if (! $matiere || $matiere->unite_enseignement_id !== null) {
            return;
        }

        if (DB::table('esbtp_ue_matiere')->where('matiere_id', $matiere->id)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'ecues' => sprintf(
                'Le code « %s » est déjà celui d\'une matière du cursus BTS (« %s »). Choisissez un autre code : réutiliser celui-ci retirerait cette matière des écrans BTS.',
                (string) $matiere->code,
                (string) $matiere->name
            ),
        ]);
    }

    private function pivot(array $donnees): array
    {
        return [
            'coefficient_ecue' => $donnees['coefficient_ecue'] ?? null,
            'credit_ecue' => $donnees['credit_ecue'] ?? null,
            'ordre_bulletin' => $donnees['ordre_bulletin'] ?? 0,
        ];
    }
}
