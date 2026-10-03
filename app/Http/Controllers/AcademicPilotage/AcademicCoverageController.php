<?php

declare(strict_types=1);

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Services\AcademicActorScopeService;
use App\Domain\AcademicPilotage\Services\AcademicNoteCoverageService;
use App\Domain\AcademicPilotage\Services\AcademicPeriodNormalizer;
use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * La couverture des notes d'une classe, servie a la demande.
 *
 * Separe du tableau de bord : celui-ci recalcule tout a chaque appel, et
 * demander la couverture d'une classe ne doit pas payer le reste.
 */
class AcademicCoverageController extends Controller
{
    /** Le temps qu'une saisie en cours reste raisonnablement visible. */
    private const TTL = 600;

    public function __construct(
        private readonly AcademicNoteCoverageService $coverage,
        private readonly AcademicActorScopeService $scope,
    ) {}

    public function show(Request $request, ESBTPClasse $classe): JsonResponse
    {
        $anneeId = $request->integer('annee_universitaire_id') ?: null;
        $periode = (string) ($request->input('periode') ?: 'semestre1');

        // LE PERIMETRE D'ABORD, LE CACHE ENSUITE. Lire le cache avant de
        // verifier qui demande servirait a un acteur hors perimetre le contenu
        // mis en cache par un autre. La cle ne porte pas d'identite parce que
        // le contenu n'en depend pas : elle n'a donc rien pour rattraper un
        // controle d'acces oublie.
        $perimetre = $this->scope->dashboardScope($request->user(), $anneeId);
        $classesAutorisees = $perimetre->global ? null : $perimetre->classIds;

        abort_if(
            $classesAutorisees !== null && ! $classesAutorisees->contains((int) $classe->id),
            403,
            'Cette classe est hors de votre périmètre.'
        );

        // Cette route accepte `academic_health.view_own` — c'est un choix, et
        // documente sur la route : l'enseignant qui saisit est le premier a
        // devoir savoir ce qui manque. Mais le constat complet porte, pour
        // chaque evaluation et chaque eleve, la note chiffree et le nom de qui
        // l'a saisie. Son ecran n'en affiche rien ; il n'a donc pas a le
        // recevoir. Seul le porteur du droit global garde le detail.
        $detailComplet = (bool) $request->user()?->can('academic_health.view');

        if ($anneeId === null) {
            $payload = $this->coverage->summarize(null, $periode, null, (int) $classe->id);

            $payload = $this->ajouterNavigation($payload, $classe);

            return response()->json($detailComplet ? $payload : $this->coverage->sansLesNotesNiLeursAuteurs($payload), 200);
        }

        $cle = $this->cle((int) $classe->id, $anneeId, $periode);

        if ($request->boolean('recalculer')) {
            Cache::forget($cle);
        }

        $payload = Cache::remember(
            $cle,
            self::TTL,
            fn () => $this->coverage->summarize($anneeId, $periode, null, (int) $classe->id)
        );

        // Le lien est ajouté après le cache : il reste une aide de navigation,
        // pas une donnée calculée qui modifierait la clé ou le périmètre.
        $payload = $this->ajouterNavigation($payload, $classe);

        // APRES le cache, jamais avant : la premiere lecture par un enseignant
        // servirait sinon une version amputee a tous les suivants.
        return response()->json($detailComplet ? $payload : $this->coverage->sansLesNotesNiLeursAuteurs($payload), 200);
    }

    /**
     * Ce qui sert a naviguer, ajoute apres le cache : les periodes que la
     * classe peut afficher, et pour chaque matiere l'adresse qui ouvre sa
     * grille de saisie sans refaire le chemin.
     *
     * Une classe LMD a ses propres semestres (S3 et S4 en deuxieme annee) et sa
     * propre saisie : `esbtp.lmd.notes.index`, qui ouvre la classe et l'element.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function ajouterNavigation(array $payload, ESBTPClasse $classe): array
    {
        $lmd = strtoupper((string) $classe->systeme_academique) === 'LMD';
        $semestres = $lmd ? $classe->getSemestresLMD() : [1, 2];
        $payload['periodes'] = array_merge(
            [['valeur' => 'annuel', 'libelle' => 'Année']],
            array_map(fn (int $n) => ['valeur' => 'semestre'.$n, 'libelle' => 'S'.$n], $semestres),
        );

        if (! isset($payload['subjects']) || ! is_array($payload['subjects'])) {
            return $payload;
        }

        $semestre = (int) data_get($payload, 'maquette.semestre', 0);
        $periode = 'semestre'.(in_array($semestre, $semestres, true) ? $semestre : $semestres[0]);

        $payload['subjects'] = array_map(function (array $matiere) use ($classe, $periode, $lmd): array {
            if (empty($matiere['id'])) {
                return $matiere;
            }

            $matiere['saisie_url'] = $lmd
                ? route('esbtp.lmd.notes.index', ['classe' => $classe->id, 'ecue' => (int) $matiere['id']])
                : route('esbtp.notes.index', ['classe_id' => $classe->id, 'matiere_id' => (int) $matiere['id'], 'periode' => $periode]);

            return $matiere;
        }, $payload['subjects']);

        return $payload;
    }

    /**
     * La cle du cache.
     *
     * Le contenu ne depend que de la classe, de l'annee et de la periode —
     * jamais de qui regarde. Le prefixe de cache est deja propre a chaque
     * instance (`CACHE_PREFIX`), l'isolation entre ecoles est donc acquise.
     *
     * LA PERIODE EST NORMALISEE, et c'est tout l'interet de cette methode.
     * La cle se construisait sur la valeur brute passee en parametre : une
     * demande `?periode=S1` remplissait `…s1`, quand l'invalidation, qui parle
     * toujours canonique, oubliait `…semestre1`. L'entree survivait donc a
     * chaque saisie de note, et le bandeau annonçait pendant dix minutes des
     * notes manquantes qui venaient d'etre saisies — exactement le defaut que
     * le bouton « recalculer » avait ete ajoute pour corriger.
     *
     * Une periode que le normaliseur refuse garde sa forme brute : le service
     * rend de toute facon un refus, et deux ecritures illisibles differentes
     * meritent deux entrees plutot qu'une confusion de plus.
     */
    public static function cle(int $classeId, int $anneeId, string $periode): string
    {
        return AcademicNoteCoverageService::cleDeCache($classeId, $anneeId, $periode);
    }
}
