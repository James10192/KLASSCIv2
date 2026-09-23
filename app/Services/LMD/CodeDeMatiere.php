<?php

namespace App\Services\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * La reponse des ecrans LMD et de l'import de maquette a « ce code de matiere
 * est-il libre ? ». L'ecran BTS des matieres (ESBTPMatiereController) repond
 * encore par sa propre regle `unique`, sans liberation : c'est un ecart connu.
 *
 * `esbtp_matieres.code` porte un index unique qui compte AUSSI les matieres
 * supprimees en douceur. Tant que chaque ecran repondait a sa facon, la meme
 * saisie donnait trois resultats : le modal ECUE levait une erreur serveur
 * (USAT, septembre 2026), le formulaire d'UE ressuscitait la matiere supprimee
 * sous le nouveau nom — historique compris —, et l'import CLI levait lui aussi
 * une erreur serveur.
 *
 * La regle, desormais une seule :
 * - une matiere ACTIVE garde son code. Qui veut la reutiliser la lie ; la
 *   creer une seconde fois est refuse, avec un message qui dit laquelle, et
 *   ou la retrouver ;
 * - une matiere SUPPRIMEE ne bloque plus personne. Son code est suffixe de son
 *   identifiant (`CODE~suppr-<id>`) : elle reste en base, trouvable, et
 *   l'audit garde la trace du renommage. On ne la restaure pas : restaurer
 *   grefferait l'historique d'une matiere retiree (notes, resultats) sur une
 *   saisie nouvelle, sans que personne l'ait demande.
 */
class CodeDeMatiere
{
    public const SUFFIXE_ARCHIVE = '~suppr-';

    /**
     * Libere le code s'il n'est tenu que par une matiere supprimee.
     *
     * A appeler dans la meme transaction que l'ecriture qui reprend le code :
     * si elle echoue, le renommage est annule avec elle.
     *
     * @return string|null Ce qui a ete fait, a montrer a l'utilisateur ; null si rien.
     */
    public function libererSiArchive(?string $code, ?int $saufId = null): ?string
    {
        if ($code === null || $code === '') {
            return null;
        }

        $archivee = ESBTPMatiere::onlyTrashed()
            ->where('code', $code)
            ->when($saufId, fn ($q) => $q->where('id', '!=', $saufId))
            ->first();

        if (! $archivee) {
            return null;
        }

        // Le code stocke, pas le code saisi : sous une collation insensible a la
        // casse, `abc` retrouve `ABC`, et l'archive doit garder la graphie d'origine.
        $code = $archivee->code;
        $codeArchive = $code . self::SUFFIXE_ARCHIVE . $archivee->id;
        $archivee->code = $codeArchive;
        $archivee->save();

        return sprintf(
            'Le code « %s » était encore réservé par « %s », supprimée le %s. Il a été libéré : '
            . 'l\'ancienne matière reste archivée sous « %s ».',
            $code,
            $archivee->name,
            $archivee->deleted_at->format('d/m/Y'),
            $codeArchive
        );
    }

    /**
     * Ecrit une matiere portant ce code, ou refuse en disant pourquoi.
     *
     * Refuse si une matiere active tient deja le code ; libere le code d'une
     * matiere supprimee ; transforme une collision d'unicite concurrente (deux
     * saisies simultanees du meme code) en ce meme refus plutot qu'en erreur
     * serveur.
     *
     * @param  callable(): mixed  $ecrire
     * @return array{0: mixed, 1: string|null} Le resultat d'$ecrire, et le message de liberation.
     */
    public function ecrire(
        string $code,
        ?int $saufId,
        ESBTPUniteEnseignement $ue,
        int $portee,
        callable $ecrire
    ): array {
        try {
            return DB::transaction(function () use ($code, $saufId, $ue, $portee, $ecrire) {
                $this->refuserSiActive($code, $saufId, $ue, $portee);
                $message = $this->libererSiArchive($code, $saufId);

                return [$ecrire(), $message];
            });
        } catch (QueryException $e) {
            // `UniqueConstraintViolationException` n'existe qu'a partir de
            // Laravel 10 ; ce depot accepte encore 9.x.
            if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                throw $e;
            }

            $this->refuserSiActive($code, $saufId, $ue, $portee);

            throw $e;
        }
    }

    private function refuserSiActive(string $code, ?int $saufId, ESBTPUniteEnseignement $ue, int $portee): void
    {
        $titulaire = ESBTPMatiere::where('code', $code)
            ->when($saufId, fn ($q) => $q->where('id', '!=', $saufId))
            ->first();

        if ($titulaire) {
            throw ValidationException::withMessages(['code' => $this->pourquoi($titulaire, $ue, $portee)]);
        }
    }

    /**
     * Ne proposer « Lier un existant » que si la matiere y sera proposee :
     * l'onglet ne liste que des elements LMD actifs, pas deja poses dans cette
     * unite pour cette maquette (ESBTPLMDUEController::matieresDisponibles()).
     */
    private function pourquoi(ESBTPMatiere $titulaire, ESBTPUniteEnseignement $ue, int $portee): string
    {
        $pivot = DB::table('esbtp_ue_matiere')->where('matiere_id', $titulaire->id);
        $estLmd = $titulaire->unite_enseignement_id !== null || (clone $pivot)->exists();

        if (! $estLmd) {
            return sprintf(
                'Le code « %s » est déjà celui d\'une matière du cursus BTS (« %s »). Choisissez un autre code.',
                $titulaire->code,
                $titulaire->name
            );
        }

        // Une matiere desactivee n'est listee nulle part, pas meme dans son unite
        // (getEcuesEffectifs l'ecarte) : la dire « deja dans l'unite » serait faux.
        if (! $titulaire->is_active) {
            return sprintf(
                'Le code « %s » est déjà celui de la matière désactivée « %s ». Choisissez un autre code, ou réactivez-la.',
                $titulaire->code,
                $titulaire->name
            );
        }

        // Sans aucune ligne de pivot, une unite lit ses elements par la cle
        // etrangere : l'element en fait alors deja partie, toutes maquettes.
        $dejaDansLUnite = (clone $pivot)
            ->where('unite_enseignement_id', $ue->id)
            ->where('parcours_id', $portee)
            ->exists()
            || ((int) $titulaire->unite_enseignement_id === (int) $ue->id && ! (clone $pivot)->exists());

        if ($dejaDansLUnite) {
            return sprintf(
                'Le code « %s » est déjà celui de « %s », qui fait déjà partie de cette unité.',
                $titulaire->code,
                $titulaire->name
            );
        }

        return sprintf(
            'Le code « %s » est déjà celui de la matière « %s ». Choisissez un autre code, ou utilisez '
            . 'l\'onglet « Lier un existant » s\'il s\'agit bien de la même matière.',
            $titulaire->code,
            $titulaire->name
        );
    }
}
