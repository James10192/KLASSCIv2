<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\CodeDeMaquette;
use App\Services\LMD\CompositionUe;

/**
 * Retrouver une UE et l'un de ses éléments tels que l'école les nomme : par
 * identifiant, par code imprimé (`AGR2103` désigne aussi `AGR2103~LPA`) ou par
 * intitulé exact. Deux candidats, c'est une question, jamais un choix.
 *
 * L'action qui l'utilise injecte `CompositionUe $composition`.
 */
trait DesigneLaMaquette
{
    /** @return array{0: ?ESBTPUniteEnseignement, 1: ?string} */
    private function unite(string $designation): array
    {
        $designation = trim($designation);
        if ($designation === '') {
            return [null, 'Quelle UE (code ou identifiant) ?'];
        }
        if (ctype_digit($designation)) {
            $ue = ESBTPUniteEnseignement::find((int) $designation);

            return [$ue, $ue ? null : "UE #{$designation} introuvable."];
        }
        $code = mb_strtoupper($designation);
        $trouvees = ESBTPUniteEnseignement::query()
            ->where(fn ($q) => $q->whereRaw('UPPER(code) = ?', [$code])->orWhereRaw('UPPER(code) LIKE ?', [$code . CodeDeMaquette::SEPARATEUR . '%']))
            ->get();

        return $trouvees->count() === 1
            ? [$trouvees->first(), null]
            : [null, $trouvees->isEmpty() ? "Aucune UE au code {$designation}." : "Plusieurs UE portent le code {$designation} : indique son identifiant."];
    }

    /** L'élément, parmi ceux que l'unité porte, toutes maquettes confondues. @return array{0: ?ESBTPMatiere, 1: ?string} */
    private function elementDeLUnite(ESBTPUniteEnseignement $ue, string $designation): array
    {
        $designation = trim($designation);
        if ($designation === '') {
            return [null, "Quel élément de l'UE {$ue->code_affiche} ?"];
        }
        $ids = $this->composition->idsDe($ue)
            ->merge(ESBTPMatiere::where('unite_enseignement_id', $ue->id)->pluck('id'))
            ->map(fn ($id) => (int) $id)->unique()->all();
        $bas = mb_strtolower($designation);
        $trouves = ESBTPMatiere::whereIn('id', $ids)->get()->filter(fn (ESBTPMatiere $m) => (string) $m->id === $designation
            || mb_strtolower((string) $m->code) === $bas || mb_strtolower((string) $m->code_affiche) === $bas
            || mb_strtolower((string) $m->name) === $bas)->values();

        return $trouves->count() === 1
            ? [$trouves->first(), null]
            : [null, $trouves->isEmpty()
                ? "L'UE {$ue->code_affiche} ne porte aucun élément « {$designation} »."
                : "« {$designation} » désigne plusieurs éléments de l'UE {$ue->code_affiche} : lequel (identifiant) ?"];
    }

    /**
     * La maquette où agir sur l'élément : celle du parcours nommé s'il y est
     * réservé ; sinon la composition commune s'il y figure ; sinon la seule
     * maquette qui le porte. Deux maquettes possibles sans parcours nommé :
     * c'est une question.
     *
     * @return array{0: int, 1: ?string}
     */
    private function porteeDeLElement(ESBTPUniteEnseignement $ue, ESBTPMatiere $m, ?\App\Models\ESBTPLMDParcours $parcours): array
    {
        $portees = \Illuminate\Support\Facades\DB::table('esbtp_ue_matiere')
            ->where(['unite_enseignement_id' => $ue->id, 'matiere_id' => $m->id])
            ->pluck('parcours_id')->map(fn ($id) => (int) $id)->unique()->values();

        if ($parcours && $portees->contains((int) $parcours->id)) {
            return [(int) $parcours->id, null];
        }
        if ($portees->isEmpty() || $portees->contains(CompositionUe::COMMUN)) {
            return [CompositionUe::COMMUN, null];
        }
        if ($portees->count() === 1 && ! $parcours) {
            return [$portees->first(), null];
        }

        $codes = \App\Models\ESBTPLMDParcours::whereIn('id', $portees->all())->pluck('code')->implode(', ');

        return [CompositionUe::COMMUN, "« {$m->name} » est réservé à la maquette de {$codes}"
            . ($parcours ? ", pas à celle de {$parcours->code}" : '') . ' : de quel parcours s\'agit-il ?'];
    }

    /** Les codes des parcours de l'UE autres que celui-ci. @return list<string> */
    private function autresParcours(ESBTPUniteEnseignement $ue, ?int $sauf): array
    {
        return \App\Models\ESBTPLMDParcours::query()
            ->whereIn('id', \Illuminate\Support\Facades\DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->pluck('parcours_id'))
            ->when($sauf, fn ($q) => $q->where('id', '!=', $sauf))
            ->orderBy('code')->pluck('code')->all();
    }
}
