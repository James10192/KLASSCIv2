<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Reprise de donnees du decoupage « elements d une unite, par maquette ».
 *
 * La colonne `esbtp_ue_matiere.parcours_id` vient d etre posee. Elle vaut 0
 * partout, ce qui veut dire « commun a toutes les maquettes de l unite » : c est
 * le comportement livre, a l identique. Cette commande ne le change pas non
 * plus. Elle fait une chose, et une seule : MATERIALISER dans le pivot les liens
 * qui n existent aujourd hui que par la cle etrangere
 * `esbtp_matieres.unite_enseignement_id`.
 *
 * Pourquoi c est necessaire : l import de maquettes n a JAMAIS ecrit dans le
 * pivot — il ne pose que la cle etrangere. Le pivot est donc quasi vide, et tant
 * qu un lien n y figure pas, il ne peut porter aucune maquette. Rien ne peut se
 * decouper tant que rien n est materialise.
 *
 * Ce que la commande NE FAIT PAS, deliberement :
 *  - elle n ecrit rien sur `esbtp_matieres` : `unite_enseignement_id` reste le
 *    discriminateur BTS / LMD d une vingtaine d ecrans en service, et n est
 *    jamais vide par cette commande ni par aucune autre ;
 *  - elle ne requalifie pas les lignes de pivot deja presentes : elles restent
 *    communes. Les reserver serait reecrire un choix que l ecole a pu poser ;
 *  - elle ne devine pas quand une unite est portee par plusieurs maquettes :
 *    elle le signale et laisse l ecole trancher.
 */
class LmdRepriseParcoursEcueCommand extends Command
{
    protected $signature = 'lmd:reprise-parcours-ecue
        {--dry-run : mesure et rapporte, n ecrit rien}
        {--force : ecrit sans demander confirmation (usage non interactif)}';

    protected $description = 'Materialise dans esbtp_ue_matiere les liens element/unite qui n existent que par cle etrangere, et leur pose la maquette quand elle est certaine.';

    /** Valeur de `parcours_id` qui signifie « commun a toutes les maquettes ». */
    private const COMMUN = 0;

    public function handle(): int
    {
        $simulation = (bool) $this->option('dry-run');

        // Sans la colonne, l insertion echouerait sur un message SQL illisible
        // au lieu de dire ce qui manque reellement.
        if (! Schema::hasColumn('esbtp_ue_matiere', 'parcours_id')) {
            $this->error('La colonne esbtp_ue_matiere.parcours_id n existe pas encore. Lancez les migrations avant cette reprise.');

            return self::FAILURE;
        }

        // B6 : une empreinte des ENSEMBLES, pas un comptage. Deux comptages
        // identiques passent alors qu une matiere a bascule de BTS vers LMD et
        // une autre en sens inverse — la separation des deux cursus serait
        // silencieusement rompue sur des instances de plus de 2000 inscrits.
        $avant = $this->empreinteCursus();
        $this->afficherEmpreinte('Avant', $avant);

        $mesure = $this->mesurerPivot();
        $this->afficherMesure($mesure);

        $plan = $this->construirePlan();

        $this->line('');
        $this->info(sprintf(
            'A materialiser : %d lien(s) — dont %d reserve(s) a une maquette et %d commun(s).',
            count($plan['inserts']),
            collect($plan['inserts'])->where('parcours_id', '!=', self::COMMUN)->count(),
            collect($plan['inserts'])->where('parcours_id', self::COMMUN)->count()
        ));

        if ($plan['ambigus'] !== []) {
            $this->warn(sprintf(
                'A ARBITRER PAR L ECOLE : %d unite(s) portee(s) par plusieurs maquettes. Rien n a ete devine pour elles ; leurs elements restent visibles partout, comme aujourd hui.',
                count($plan['ambigus'])
            ));
            foreach (array_slice($plan['ambigus'], 0, 15) as $cas) {
                $this->line(sprintf(
                    '   UE #%d %s — maquettes : %s — %d element(s) concerne(s)',
                    $cas['unite_enseignement_id'],
                    $cas['code'] ?? '',
                    implode(', ', $cas['parcours_ids']),
                    $cas['elements']
                ));
            }
        }

        if ($plan['pivot_deja_rempli'] !== []) {
            $this->warn(sprintf(
                'IGNOREES : %d unite(s) ont deja des lignes de pivot. Le pivot fait foi, on ne le complete pas.',
                count($plan['pivot_deja_rempli'])
            ));
        }

        $chemin = $this->ecrireFichierDeReprise($avant, $mesure, $plan, $simulation);
        $this->line('Fichier de reprise : ' . $chemin);

        if ($simulation) {
            $this->comment('Simulation : rien n a ete ecrit.');

            return self::SUCCESS;
        }

        if ($plan['inserts'] === []) {
            $this->info('Rien a materialiser — la reprise est deja faite.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Materialiser %d lien(s) ?', count($plan['inserts'])), false)) {
            $this->comment('Abandon a la demande.');

            return self::SUCCESS;
        }

        $ecrits = $this->materialiser($plan['inserts']);
        $this->info(sprintf('%d lien(s) materialise(s).', $ecrits));

        // La verification est le point de la manoeuvre : si le discriminateur a
        // bouge, on le sait ici et pas trois semaines plus tard sur un bulletin.
        $apres = $this->empreinteCursus();
        $this->afficherEmpreinte('Apres', $apres);

        if ($apres['bts'] !== $avant['bts'] || $apres['lmd'] !== $avant['lmd']) {
            $this->error('EMPREINTE MODIFIEE : la repartition BTS / LMD des matieres a change. Ce n etait pas attendu — reportez-vous au fichier de reprise.');
            Log::error('[lmd:reprise-parcours-ecue] empreinte cursus modifiee', compact('avant', 'apres'));

            return self::FAILURE;
        }

        $this->info('Empreinte BTS / LMD inchangee.');

        return self::SUCCESS;
    }

    /**
     * Empreinte des ENSEMBLES d identifiants de chaque cursus.
     *
     * Calculee en PHP et non par GROUP_CONCAT : la longueur de ce dernier est
     * plafonnee par `group_concat_max_len` (1024 octets par defaut), ce qui
     * tronque silencieusement l empreinte au-dela de ~100 identifiants — elle ne
     * couvrirait alors que le debut de la table et ne prouverait plus rien.
     *
     * Lecture par requete brute, donc sans le filtre de suppression douce : ce
     * qu on verifie ici est l etat physique de la colonne, pas ce qu un ecran
     * affiche.
     *
     * @return array{bts:string, lmd:string, bts_total:int, lmd_total:int}
     */
    private function empreinteCursus(): array
    {
        $bts = DB::table('esbtp_matieres')->whereNull('unite_enseignement_id')->orderBy('id')->pluck('id')->all();
        $lmd = DB::table('esbtp_matieres')->whereNotNull('unite_enseignement_id')->orderBy('id')->pluck('id')->all();

        return [
            'bts' => md5(implode(',', $bts)),
            'lmd' => md5(implode(',', $lmd)),
            'bts_total' => count($bts),
            'lmd_total' => count($lmd),
        ];
    }

    /**
     * Etat du pivot avant toute ecriture — c est la mesure demandee avant de
     * decider quoi que ce soit.
     *
     * @return array<string, mixed>
     */
    private function mesurerPivot(): array
    {
        $parParcours = DB::table('esbtp_ue_matiere')
            ->select('parcours_id', DB::raw('COUNT(*) as total'))
            ->groupBy('parcours_id')
            ->pluck('total', 'parcours_id')
            ->all();

        $candidats = DB::table('esbtp_matieres')
            ->whereNotNull('unite_enseignement_id')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->count();

        $deja = DB::table('esbtp_matieres as m')
            ->join('esbtp_ue_matiere as p', function ($jointure) {
                $jointure->on('p.matiere_id', '=', 'm.id')
                    ->on('p.unite_enseignement_id', '=', 'm.unite_enseignement_id');
            })
            ->whereNotNull('m.unite_enseignement_id')
            ->whereNull('m.deleted_at')
            ->where('m.is_active', true)
            ->distinct()
            ->count('m.id');

        return [
            'lignes_pivot' => DB::table('esbtp_ue_matiere')->count(),
            'lignes_pivot_par_parcours' => $parParcours,
            'elements_lies_par_cle_etrangere' => $candidats,
            'elements_deja_dans_le_pivot' => $deja,
        ];
    }

    /**
     * Decide, pour chaque lien qui n existe que par cle etrangere, s il devient
     * commun, reserve, ou reste a arbitrer.
     *
     * Ne sont retenues que les matieres ACTIVES. `getEcuesEffectifs()` filtre
     * `is_active` sur la voie cle etrangere mais pas sur la voie pivot :
     * materialiser un element desactive le ferait REAPPARAITRE dans les
     * bulletins. Ce lot ne doit rien changer, donc on s en tient aux actives —
     * exactement le perimetre de materialiserPivotDepuisCleEtrangere().
     *
     * @return array{inserts:array<int, array<string, mixed>>, ambigus:array<int, array<string, mixed>>, pivot_deja_rempli:array<int, int>}
     */
    private function construirePlan(): array
    {
        // Unites dont le pivot parle deja : il fait foi, on n y touche pas.
        $unitesAvecPivot = DB::table('esbtp_ue_matiere')
            ->distinct()
            ->pluck('unite_enseignement_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $unitesAvecPivot = array_flip($unitesAvecPivot);

        // Maquettes de chaque unite, lues sur le pivot parcours/unite qui en est
        // l autorite — la colonne `parcours_id` de l unite, elle, a pu etre
        // reecrite par un import portant sur une autre maquette.
        //
        // Les maquettes supprimees sont ecartees : reserver un element a une
        // maquette qui n existe plus le retirerait de tout le monde le jour ou le
        // decoupage s activera. Une unite dont toutes les maquettes ont disparu
        // retombe alors sur « commun », ce qui preserve l etat actuel.
        $maquettesParUnite = [];
        $liens = DB::table('esbtp_lmd_parcours_ue as pu')
            ->join('esbtp_lmd_parcours as p', 'p.id', '=', 'pu.parcours_id')
            ->whereNull('p.deleted_at')
            ->select('pu.unite_enseignement_id', 'pu.parcours_id')
            ->distinct()
            ->get();
        foreach ($liens as $lien) {
            $maquettesParUnite[(int) $lien->unite_enseignement_id][(int) $lien->parcours_id] = true;
        }

        $codesUnites = DB::table('esbtp_unites_enseignement')->pluck('code', 'id')->all();

        $inserts = [];
        $ambigus = [];
        $ignorees = [];

        $elements = DB::table('esbtp_matieres')
            ->select('id', 'unite_enseignement_id', 'coefficient_ecue', 'credit_ecue', 'ordre_bulletin')
            ->whereNotNull('unite_enseignement_id')
            ->whereNull('deleted_at')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        foreach ($elements as $element) {
            $uniteId = (int) $element->unite_enseignement_id;

            if (isset($unitesAvecPivot[$uniteId])) {
                $ignorees[$uniteId] = $uniteId;
                continue;
            }

            $maquettes = array_keys($maquettesParUnite[$uniteId] ?? []);

            if (count($maquettes) > 1) {
                // On ne devine pas. Sans ligne de pivot, l element reste visible
                // partout par la cle etrangere : l etat actuel est preserve.
                $ambigus[$uniteId] ??= [
                    'unite_enseignement_id' => $uniteId,
                    'code' => $codesUnites[$uniteId] ?? null,
                    'parcours_ids' => $maquettes,
                    'elements' => 0,
                ];
                $ambigus[$uniteId]['elements']++;
                continue;
            }

            // Une seule maquette : le rattachement est certain, on le reserve.
            // Aucune maquette connue : commun assume, ce qui reproduit a
            // l identique ce que le lien signifie aujourd hui.
            $inserts[] = [
                'unite_enseignement_id' => $uniteId,
                'matiere_id' => (int) $element->id,
                'parcours_id' => count($maquettes) === 1 ? $maquettes[0] : self::COMMUN,
                'coefficient_ecue' => $element->coefficient_ecue,
                'credit_ecue' => $element->credit_ecue,
                'ordre_bulletin' => (int) ($element->ordre_bulletin ?? 0),
            ];
        }

        return [
            'inserts' => $inserts,
            'ambigus' => array_values($ambigus),
            'pivot_deja_rempli' => array_values($ignorees),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $inserts
     */
    private function materialiser(array $inserts): int
    {
        $horodatage = now();

        return DB::transaction(function () use ($inserts, $horodatage) {
            $ecrits = 0;

            foreach (array_chunk($inserts, 200) as $lot) {
                $lignes = array_map(static fn (array $ligne) => $ligne + [
                    'created_at' => $horodatage,
                    'updated_at' => $horodatage,
                ], $lot);

                // Rejouable : un second passage retombe sur l unicite du triplet
                // et n insere rien plutot que d echouer.
                $ecrits += DB::table('esbtp_ue_matiere')->insertOrIgnore($lignes);
            }

            return $ecrits;
        });
    }

    /**
     * Vide l etat AVANT dans un fichier, pour que la reprise soit annulable
     * meme si personne ne se souvient de ce qu il y avait.
     *
     * @param  array<string, mixed>  $empreinte
     * @param  array<string, mixed>  $mesure
     * @param  array<string, mixed>  $plan
     */
    private function ecrireFichierDeReprise(array $empreinte, array $mesure, array $plan, bool $simulation): string
    {
        $dossier = storage_path('app/lmd');
        if (! is_dir($dossier) && ! mkdir($dossier, 0775, true) && ! is_dir($dossier)) {
            throw new RuntimeException("Impossible de creer $dossier — la reprise s arrete plutot que d ecrire sans filet.");
        }

        $chemin = $dossier . '/reprise-parcours-ecue-' . now()->format('Ymd-His') . ($simulation ? '-simulation' : '') . '.json';

        // Le fichier N EST PAS un confort : il porte l etat AVANT. Si on ne peut
        // pas l ecrire, on n ecrit rien en base non plus.
        $ecrit = file_put_contents($chemin, json_encode([
            'genere_le' => now()->toIso8601String(),
            'simulation' => $simulation,
            'empreinte_avant' => $empreinte,
            'mesure_pivot_avant' => $mesure,
            'pivot_avant' => DB::table('esbtp_ue_matiere')->orderBy('id')->get()->all(),
            'inserts_prevus' => $plan['inserts'],
            'a_arbitrer' => $plan['ambigus'],
            'unites_ignorees_pivot_deja_rempli' => $plan['pivot_deja_rempli'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if ($ecrit === false) {
            throw new RuntimeException("Ecriture impossible dans $chemin — la reprise s arrete plutot que d ecrire sans filet.");
        }

        return $chemin;
    }

    /**
     * @param  array<string, mixed>  $empreinte
     */
    private function afficherEmpreinte(string $moment, array $empreinte): void
    {
        $this->line(sprintf(
            '%s — BTS %s (%d matieres) · LMD %s (%d matieres)',
            $moment,
            substr($empreinte['bts'], 0, 12),
            $empreinte['bts_total'],
            substr($empreinte['lmd'], 0, 12),
            $empreinte['lmd_total']
        ));
    }

    /**
     * @param  array<string, mixed>  $mesure
     */
    private function afficherMesure(array $mesure): void
    {
        $this->line('');
        $this->line('Etat du pivot esbtp_ue_matiere :');
        $this->line('  lignes                                : ' . $mesure['lignes_pivot']);
        $this->line('  elements lies par cle etrangere       : ' . $mesure['elements_lies_par_cle_etrangere']);
        $this->line('  dont deja presents dans le pivot      : ' . $mesure['elements_deja_dans_le_pivot']);

        foreach ($mesure['lignes_pivot_par_parcours'] as $parcoursId => $total) {
            $etiquette = (int) $parcoursId === self::COMMUN ? 'commun (0)' : 'maquette #' . $parcoursId;
            $this->line(sprintf('  lignes %-30s : %d', $etiquette, $total));
        }
    }
}
