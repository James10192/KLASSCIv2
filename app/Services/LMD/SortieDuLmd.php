<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
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
        'esbtp_resultats_matieres' => 'moyenne(s) de bulletin',
        'esbtp_tpe_declarations' => 'déclaration(s) de TPE',
        'esbtp_examens_planifies' => 'examen(s) planifié(s)',
    ];

    /** @var array<string, array{0: bool, 1: bool}>|null table => [lisible, avec suppression douce] */
    private static ?array $tables = null;

    public function __construct(
        private CompositionUe $composition,
        private SuppressionUeService $suppressionUe,
    ) {}

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
            [$lisible, $douce] = $this->table($table);
            if (! $lisible) {
                continue;
            }

            $requete = DB::table($table)->where('matiere_id', $ecue->id);
            if ($douce) {
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
     * Retire des elements d'une maquette sans poser la question, pour le
     * formulaire d'UE qui en retire plusieurs d'un coup. Ceux qui sortent de
     * leur derniere maquette suivent le reglage de l'ecole, comme a la
     * suppression d'une UE : rendus au BTS, ou archives dans le LMD.
     *
     * @param  array<int, int>  $matiereIds
     */
    public function retirerSansQuestion(ESBTPUniteEnseignement $ue, array $matiereIds, int $portee): void
    {
        if ($matiereIds === []) {
            return;
        }

        $sortants = $this->suppressionUe->libereEcuesVersBts()
            ? []
            : ESBTPMatiere::whereIn('id', $matiereIds)->get()
                ->filter(fn (ESBTPMatiere $m) => $this->sortirait($ue, $m, $portee))
                ->map(fn (ESBTPMatiere $m) => (int) $m->id)->values()->all();

        $this->composition->retirer($ue, $matiereIds, $portee);

        if ($sortants !== []) {
            ESBTPMatiere::whereIn('id', $sortants)->update(['is_active' => false, 'updated_by' => auth()->id()]);
        }

        $this->composition->libererCleEtrangere($ue, array_values(array_diff($matiereIds, $sortants)));
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

    /**
     * Retire l'element de la maquette `$portee` de cette unite, et d'elle seule.
     * Le chemin de l'ecran (`destroyECUE`) et de Nanan (`proposer_retrait_ecue_lmd`).
     *
     * L'appelant a deja demande `$devenir` quand `sortirait()` est vrai.
     *
     * @return string|array{refus: string} le message, ou le refus a montrer
     *
     * @throws ValidationException choix inconnu, ou suppression d'un element qui a servi
     */
    public function retirer(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue, int $portee, ?string $devenir): string|array
    {
        $sortirait = $this->sortirait($ue, $ecue, $portee);

        return DB::transaction(function () use ($ue, $ecue, $portee, $sortirait, $devenir) {
            // `detach($id)` supprimait toutes les lignes de cet element, toutes
            // maquettes confondues : retirer un element de Batiment le retirait
            // aussi de Travaux Publics.
            $retires = $this->composition->retirer($ue, [(int) $ecue->id], $portee);

            // Rien retire alors que l'element figure dans une AUTRE maquette de
            // l'unite : on repondait « ECUE detache » a vide, et l'element restait.
            if ($retires === 0 && ($refus = $this->refusRetraitHorsMaquette($ue, $ecue, $portee))) {
                return ['refus' => $refus];
            }

            if ($sortirait) {
                if ($devenir === null) {
                    throw ValidationException::withMessages(['devenir' => 'Que doit devenir cet élément : supprimer, archiver ou catalogue_bts ?']);
                }

                return $this->appliquer($devenir, $ue, $ecue);
            }

            // Cle etrangere liberee, ou reportee sur une autre unite qui le porte.
            $this->composition->libererCleEtrangere($ue, [(int) $ecue->id]);

            return 'ECUE retiré de la maquette.';
        });
    }

    /**
     * Pourquoi le retrait n'a rien retiré, quand l'élément tient à l'unité par
     * une autre maquette que celle visée. Null si l'élément n'est dans aucune
     * ligne de pivot : c'est alors un rattachement hérité, par clé étrangère,
     * que l'appelant libère lui-même.
     */
    public function refusRetraitHorsMaquette(ESBTPUniteEnseignement $ue, ESBTPMatiere $ecue, int $portee): ?string
    {
        $portees = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $ue->id)
            ->where('matiere_id', $ecue->id)
            ->pluck('parcours_id')
            ->map(fn ($id) => (int) $id);

        if ($portees->isEmpty()) {
            return null;
        }

        $nom = $ecue->name ?? $ecue->code;

        if ($portee === CompositionUe::COMMUN) {
            $noms = ESBTPLMDParcours::whereIn('id', $portees->filter()->all())
                ->pluck('name')
                ->implode(', ');

            return sprintf(
                "« %s » n'est pas dans la composition commune : il est réservé à %s. "
                . 'Filtrez la liste sur ce parcours pour le retirer de sa maquette.',
                $nom,
                $noms !== '' ? $noms : 'une autre maquette'
            );
        }

        return sprintf(
            "« %s » est commun à tous les parcours de l'unité : il ne se retire pas d'une seule maquette. "
            . 'Retirez-le sans filtre de parcours, ou réservez à ce parcours les éléments qui lui sont propres.',
            $nom
        );
    }

    /**
     * Une table d'usage existe-t-elle sur cette instance, avec `matiere_id`,
     * et en suppression douce ? Lu une fois par processus : le schema ne
     * bouge pas entre deux requetes.
     *
     * @return array{0: bool, 1: bool}
     */
    private function table(string $table): array
    {
        return self::$tables[$table] ??= Schema::hasTable($table)
            ? [Schema::hasColumn($table, 'matiere_id'), Schema::hasColumn($table, 'deleted_at')]
            : [false, false];
    }
}
