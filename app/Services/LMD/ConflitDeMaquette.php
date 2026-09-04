<?php

namespace App\Services\LMD;

use RuntimeException;

/**
 * L'import refuse d'ecraser une maquette deja validee.
 *
 * Le code d'une UE et celui d'un ECUE sont uniques dans toute la base. L'import
 * cherchait donc par code, puis ecrasait `parcours_id`, `semestre`, `credit` et
 * `niveau_id` de l'UE trouvee — ou reparentait l'ECUE vers une autre UE.
 *
 * Consequence, silencieuse : importer la maquette d'un second parcours REECRIVAIT
 * celle du premier. Le parcours importe en premier heritait du semestre et du
 * credit de l'autre, et comme la lecture des ECUE rend l'union du pivot et de la
 * cle etrangere, les deux parcours voyaient le meme sac de matieres. Deux
 * maquettes fausses, sans un message.
 *
 * Le partage reel d'une UE entre parcours — decide en septembre 2026 : code unique
 * dans l'ecole, UE partagee — demande des colonnes qui n'existent pas encore
 * (`parcours_id` sur `esbtp_ue_matiere`, `credit` sur `esbtp_lmd_parcours_ue`).
 * En attendant, on refuse plutot que de detruire. Voir l'issue #942.
 */
class ConflitDeMaquette extends RuntimeException
{
    /** @param list<array{type: string, code: string, detail: string}> $conflits */
    public function __construct(public readonly array $conflits)
    {
        parent::__construct(sprintf(
            "L'import a été refusé : %d élément(s) porteraient atteinte à une maquette déjà enregistrée.",
            count($conflits)
        ));
    }

    /** @return list<array{type: string, code: string, detail: string}> */
    public function conflits(): array
    {
        return $this->conflits;
    }
}
