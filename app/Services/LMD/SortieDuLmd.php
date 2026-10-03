<?php

namespace App\Services\LMD;

use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Ce que devient un element constitutif quand on le retire de la DERNIERE
 * maquette qui le portait.
 *
 * Retirer la derniere ligne coupait la cle etrangere `unite_enseignement_id`,
 * et l'element tombait dans le catalogue BTS : les ecrans BTS lisent
 * `unite_enseignement_id IS NULL` comme « matiere BTS ». ESBTP Abidjan, qui
 * melange les deux cursus, voyait donc « Algebre » revenir dans les selecteurs
 * de notes et d'evaluations BTS pour un simple menage de maquette (octobre 2026).
 * La seule question posee etait « le retirer quand meme ? », sans autre issue.
 *
 * L'ecole choisit desormais, et le defaut ne verse rien au BTS :
 *
 * - SUPPRIMER : l'element n'a jamais servi (aucune evaluation, note, seance ni
 *   resultat). Suppression douce ; sa cle etrangere reste posee, il ne peut
 *   donc reapparaitre nulle part, et son code se liberera seul a la prochaine
 *   saisie (`CodeDeMatiere::libererSiArchive`).
 * - ARCHIVER : l'element a un historique. Il reste dans le LMD, rattache a son
 *   unite par la cle etrangere, mais inactif : la lecture des maquettes
 *   (`getEcuesEffectifs`) ne reprend que les actifs. Le rattacher de nouveau
 *   depuis « Ajouter un ECUE » le reactive.
 * - CATALOGUE_BTS : l'ancien geste, a demander explicitement — un element qui
 *   etait reellement une matiere BTS importee par erreur.
 */
class SortieDuLmd
{
    public const SUPPRIMER = 'supprimer';

    public const ARCHIVER = 'archiver';

    public const CATALOGUE_BTS = 'catalogue_bts';

    /**
     * Les tables dont une ligne fait de l'element un element qui a servi. Les
     * libelles sont ceux montres a l'ecole pour expliquer le refus.
     */
    private const USAGES = [
        'esbtp_evaluations' => 'évaluation(s)',
        'esbtp_notes' => 'note(s)',
        'esbtp_seance_cours' => 'séance(s) d\'emploi du temps',
        'esbtp_lmd_resultats_ecues' => 'résultat(s) LMD',
        'esbtp_resultats' => 'moyenne(s) enregistrée(s)',
    ];

    public function __construct(private CompositionUe $composition) {}

    /**
     * Vrai si retirer cette ligne fait sortir l'element du LMD.
     *
     * Une ligne dans une AUTRE unite le retient : la cle etrangere y est
     * reportee par `CompositionUe::libererCleEtrangere()` au lieu d'etre coupee.
     */
    public function sortirait(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue, int $portee): bool
    {
        // Sans cle sur cette unite, rien n'est libere : un element sans cle du
        // tout est deja dans les listes BTS, la question mentirait.
        if ((int) $ecue->unite_enseignement_id !== (int) $ue->id) {
            return false;
        }

        return DB::table('esbtp_ue_matiere')
            ->where('matiere_id', $ecue->id)
            ->where(fn ($q) => $q
                ->where('unite_enseignement_id', '!=', $ue->id)
                ->orWhere('parcours_id', '!=', $portee))
            ->doesntExist();
    }

    /**
     * Ce qui a deja ete fait avec l'element, par nature. Vide : il n'a jamais servi.
     *
     * @return array<string, int> libelle => nombre
     */
    public function usages(ESBTPMatiere $ecue): array
    {
        $usages = [];

        foreach (self::USAGES as $table => $libelle) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'matiere_id')) {
                continue;
            }

            $requete = DB::table($table)->where('matiere_id', $ecue->id);
            if (Schema::hasColumn($table, 'deleted_at')) {
                $requete->whereNull('deleted_at');
            }

            $nombre = $requete->count();
            if ($nombre > 0) {
                $usages[$libelle] = ($usages[$libelle] ?? 0) + $nombre;
            }
        }

        return $usages;
    }

    /**
     * La question posee a l'ecole : le message, et les issues possibles.
     *
     * @return array{message: string, usages: array<string, int>, options: list<array{valeur: string, libelle: string, aide: string, possible: bool, recommande: bool}>}
     */
    public function question(ESBTPMatiere $ecue): array
    {
        $usages = $this->usages($ecue);
        $aServi = $usages !== [];
        $resume = collect($usages)->map(fn ($n, $libelle) => $n . ' ' . $libelle)->implode(', ');

        return [
            'message' => sprintf(
                '« %s » ne figure dans aucune autre maquette. Que doit-il devenir une fois retiré ?',
                $ecue->name ?? $ecue->code
            ),
            'usages' => $usages,
            'options' => [
                [
                    'valeur' => self::SUPPRIMER,
                    'libelle' => 'Le supprimer',
                    'aide' => $aServi
                        ? 'Impossible : il porte déjà ' . $resume . '.'
                        : 'Il n\'a jamais servi. Il disparaît de toutes les listes, et son code redevient libre.',
                    'possible' => ! $aServi,
                    'recommande' => ! $aServi,
                ],
                [
                    'valeur' => self::ARCHIVER,
                    'libelle' => 'L\'archiver dans le LMD',
                    'aide' => 'Il sort de la maquette mais reste un élément LMD, avec son historique. '
                        . 'Il n\'apparaît dans aucune liste BTS. On peut le rattacher de nouveau plus tard.',
                    'possible' => true,
                    'recommande' => $aServi,
                ],
                [
                    'valeur' => self::CATALOGUE_BTS,
                    'libelle' => 'En faire une matière BTS',
                    'aide' => 'Seulement si c\'était une matière BTS rattachée par erreur : '
                        . 'elle apparaîtra dans les notes, évaluations et bulletins BTS.',
                    'possible' => true,
                    'recommande' => false,
                ],
            ],
        ];
    }

    /**
     * Applique le choix, APRES le retrait de la ligne, dans la meme transaction.
     *
     * @throws ValidationException choix inconnu, ou suppression d'un element qui a servi
     */
    public function appliquer(string $devenir, ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue): string
    {
        $nom = $ecue->name ?? $ecue->code;

        switch ($devenir) {
            case self::SUPPRIMER:
                if ($this->usages($ecue) !== []) {
                    throw ValidationException::withMessages([
                        'devenir' => sprintf('« %s » a déjà servi : archivez-le plutôt que de le supprimer.', $nom),
                    ]);
                }
                // La planification n'est que de la configuration : elle designerait
                // sinon un element supprime, et la maquette le montrerait encore.
                if (Schema::hasTable('esbtp_planifications_academiques')) {
                    DB::table('esbtp_planifications_academiques')
                        ->where('matiere_id', $ecue->id)
                        ->whereNull('deleted_at')
                        ->update(['deleted_at' => now()]);
                }
                $ecue->delete();

                return sprintf('« %s » a été retiré et supprimé.', $nom);

            case self::ARCHIVER:
                $ecue->update(['is_active' => false, 'updated_by' => auth()->id()]);

                return sprintf('« %s » a été retiré et archivé dans le LMD.', $nom);

            case self::CATALOGUE_BTS:
                $this->composition->libererCleEtrangere($ue, [(int) $ecue->id]);

                return sprintf('« %s » a été retiré et rejoint les matières BTS.', $nom);
        }

        throw ValidationException::withMessages([
            'devenir' => 'Choix inconnu : supprimer, archiver ou catalogue_bts.',
        ]);
    }
}
