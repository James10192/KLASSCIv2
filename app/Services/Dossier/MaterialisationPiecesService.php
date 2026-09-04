<?php

namespace App\Services\Dossier;

use App\Enums\StatutPieceDossier;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPieceDossier;
use Illuminate\Support\Facades\DB;

/**
 * Cree, pour une inscription, les lignes d'etat correspondant au catalogue des
 * pieces applicables a sa filiere et a son niveau.
 *
 * Deux garanties tiennent tout le reste :
 *
 * 1. IDEMPOTENCE. Rouvrir une inscription ne recree aucune ligne et n'ecrase
 *    aucun statut deja saisi. L'insertion ignore les doublons et s'appuie sur
 *    l'index unique (inscription_id, piece_dossier_id), qui protege aussi contre
 *    deux requetes simultanees.
 *
 * 2. SILENCE PAR DEFAUT. Tant que l'ecole n'a rien mis dans son catalogue, ce
 *    service ne cree rien et ne change donc aucun ecran existant.
 */
class MaterialisationPiecesService
{
    /**
     * Reglage : que faire des inscriptions DEJA creees quand une piece est
     * ajoutee au catalogue ensuite ?
     *
     * - 'aucun'          : rien. Les dossiers en cours restent figes.
     * - 'annee_courante' : la piece apparait sur les dossiers de l'annee en cours.
     * - 'toutes'         : la piece apparait sur tous les dossiers, meme clos.
     */
    public const REGLAGE_RATTRAPAGE = 'dossier.rattrapage_catalogue';

    public const RATTRAPAGE_AUCUN = 'aucun';
    public const RATTRAPAGE_ANNEE_COURANTE = 'annee_courante';
    public const RATTRAPAGE_TOUTES = 'toutes';

    /**
     * Defaut : l'annee en cours.
     *
     * Une ecole qui ajoute "photo d'identite" en octobre, apres la rentree, veut
     * la voir apparaitre sur les dossiers ouverts — sinon la liste transmise au
     * ministere est fausse et le secretariat reprend 2000 dossiers a la main.
     * Elle ne veut PAS la voir apparaitre sur les annees closes : un dossier clos
     * etait complet au regard des exigences de son annee, et le reouvrir en
     * "incomplet" reecrirait l'histoire.
     */
    public const RATTRAPAGE_DEFAUT = self::RATTRAPAGE_ANNEE_COURANTE;

    /**
     * Materialise le dossier d'une inscription.
     *
     * @return int Nombre de lignes reellement creees (0 = rien a faire).
     */
    public function materialiser(ESBTPInscription $inscription, ?int $auteurId = null): int
    {
        $applicables = ESBTPPieceDossier::applicablesPour(
            $inscription->filiere_id,
            $inscription->niveau_id
        );

        if ($applicables->isEmpty()) {
            return 0;
        }

        $dejaPresentes = ESBTPInscriptionPiece::query()
            ->where('inscription_id', $inscription->id)
            ->pluck('piece_dossier_id')
            ->all();

        // Dossier deja materialise : toute nouvelle piece releve du rattrapage,
        // qui est un choix de l'ecole et pas une decision du code.
        if ($dejaPresentes !== [] && ! $this->rattrapageAutorisePour($inscription)) {
            return 0;
        }

        $manquantes = $applicables->reject(
            fn (ESBTPPieceDossier $piece) => in_array($piece->id, $dejaPresentes, true)
        );

        if ($manquantes->isEmpty()) {
            return 0;
        }

        $maintenant = now();
        $lignes = $manquantes->map(fn (ESBTPPieceDossier $piece) => [
            'inscription_id' => $inscription->id,
            'piece_dossier_id' => $piece->id,
            'statut' => StatutPieceDossier::defaut()->value,
            'created_by' => $auteurId,
            'updated_by' => $auteurId,
            'created_at' => $maintenant,
            'updated_at' => $maintenant,
        ])->all();

        // insertOrIgnore plutot que insert : si deux requetes materialisent le
        // meme dossier en meme temps, la seconde n'echoue pas sur l'index unique.
        return DB::table('esbtp_inscription_pieces')->insertOrIgnore($lignes);
    }

    /**
     * Rattrape le catalogue sur un lot d'inscriptions deja creees.
     *
     * @param  iterable<ESBTPInscription>  $inscriptions
     * @return int Nombre total de lignes creees.
     */
    public function rattraper(iterable $inscriptions, ?int $auteurId = null): int
    {
        $total = 0;

        foreach ($inscriptions as $inscription) {
            $total += $this->materialiser($inscription, $auteurId);
        }

        return $total;
    }

    /**
     * Le rattrapage est-il autorise pour cette inscription ?
     */
    public function rattrapageAutorisePour(ESBTPInscription $inscription): bool
    {
        $anneeCouranteId = ESBTPAnneeUniversitaire::getCurrent()?->id;

        return self::rattrapageAutorise(
            $this->modeRattrapage(),
            $inscription->annee_universitaire_id === null ? null : (int) $inscription->annee_universitaire_id,
            $anneeCouranteId === null ? null : (int) $anneeCouranteId
        );
    }

    /**
     * La decision, isolee de toute lecture de base pour rester verifiable.
     *
     * En mode 'annee_courante', un dossier d'une annee close ne bouge plus : il
     * etait complet au regard des exigences de son annee, et le rouvrir en
     * « incomplet » reecrirait l'histoire devant un ministere. Si l'annee
     * courante est indeterminee, on ne touche a rien plutot que de deviner.
     */
    public static function rattrapageAutorise(string $mode, ?int $anneeInscriptionId, ?int $anneeCouranteId): bool
    {
        if ($mode === self::RATTRAPAGE_TOUTES) {
            return true;
        }

        if ($mode !== self::RATTRAPAGE_ANNEE_COURANTE) {
            return false;
        }

        return $anneeCouranteId !== null
            && $anneeInscriptionId !== null
            && $anneeInscriptionId === $anneeCouranteId;
    }

    /**
     * Mode de rattrapage configure pour cette instance.
     *
     * Une valeur inconnue retombe sur le defaut plutot que de desactiver le
     * rattrapage en silence : une faute de frappe dans un reglage ne doit pas
     * faire disparaitre sans bruit des pieces attendues par un ministere.
     */
    public function modeRattrapage(): string
    {
        $brut = SettingsHelper::get(self::REGLAGE_RATTRAPAGE, self::RATTRAPAGE_DEFAUT);
        $mode = is_scalar($brut) ? trim((string) $brut) : '';

        return in_array($mode, self::modesRattrapage(), true) ? $mode : self::RATTRAPAGE_DEFAUT;
    }

    /** @return array<int, string> */
    public static function modesRattrapage(): array
    {
        return [
            self::RATTRAPAGE_AUCUN,
            self::RATTRAPAGE_ANNEE_COURANTE,
            self::RATTRAPAGE_TOUTES,
        ];
    }
}
