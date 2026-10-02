<?php

namespace App\Services\Reinscription;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * La moyenne annuelle d'un eleve BTS telle que son bulletin l'imprime, pour
 * toute une promotion d'un coup.
 *
 * POURQUOI. La reinscription tranchait passage / rattrapage / redoublement sur
 * sa propre moyenne : moyenne simple des matieres de l'annee, sans coefficient
 * ni poids de semestre. Le bulletin, lui, decide sur la moyenne annuelle
 * PONDEREE (`bulletin_btsN_semester1_weight` / `semester2_weight`). Les deux
 * pouvaient se contredire sur le meme eleve : « Admis » au bulletin,
 * « redoublement » a la reinscription, ou l'inverse.
 *
 * LA SOURCE, et elle seule : le chemin de `BulletinService::buildDonneesBulletin()`
 * qui calcule `$moyenneAnnuelle` imprimee sur le bulletin du semestre 2 —
 *
 * - chaque semestre : le bulletin enregistre (`esbtp_bulletins`, moyenne > 0)
 *   s'il existe, via `getEffectiveBulletinAverage()` (moyenne + assiduite),
 *   sinon le calcul courant (snapshot du semestre + note d'assiduite), comme
 *   `getAlignedBulletinAverageForPeriode()` ;
 * - le semestre 1 lu dans la classe qui le porte (tronc commun d'un oriente),
 *   par la carte annuelle (`BtsAnnualClassMapResolver`) ;
 * - les deux combines par `calculateAnnualAverage()` avec
 *   `getSemesterWeights($classe)`.
 *
 * Un semestre sans moyenne rend `null`, comme au bulletin, qui laisse alors la
 * decision du conseil vide. C'est a l'appelant de dire ce qu'il fait d'une
 * moyenne absente ; ce service ne l'invente jamais a zero.
 *
 * COUT. Les bulletins enregistres d'un lot se lisent en une requete par classe.
 * Le calcul courant, lui, passe par `getPeriodeSnapshotsPourCohorte()` (notes et
 * moyennes en une requete par semestre et par classe) ; ce qui reste par eleve
 * sur ce chemin est la note d'assiduite et la carte des classes du bulletin,
 * exactement ce que le classement de la fiche etudiant paie deja.
 *
 * BTS uniquement : l'appelant ne doit pas y passer une classe LMD (rule
 * `lmd-bts-bulletin-separation`). Une classe LMD rencontree est ignoree.
 */
class MoyennesAnnuellesDuBulletin
{
    /** Ce que la carte des classes lit sur l'inscription, charge pour tout le lot. */
    private const RELATIONS_DE_LA_CARTE = [
        'filiere',
        'classe.filiere',
        'phases.classe.filiere',
        'inscriptionOrigine.classe.filiere',
        'inscriptionSpecialisation.classe.filiere',
    ];

    public function __construct(
        private readonly BulletinService $bulletins,
        private readonly BtsCurrentResultSnapshotService $snapshots,
        private readonly BtsAnnualClassMapResolver $cartes,
    ) {
    }

    /**
     * @param  iterable<ESBTPInscription>  $inscriptions  inscriptions de l'annee terminee
     * @return array<int, array{moyenne: float|null, semestre1: float|null, semestre2: float|null, poids: array{semester1: float, semester2: float}}>
     *         par identifiant d'inscription, classes BTS seulement
     */
    public function pour(iterable $inscriptions): array
    {
        $bts = EloquentCollection::make(collect($inscriptions)->filter(
            fn ($inscription) => $inscription instanceof ESBTPInscription
                && $inscription->classe
                && $inscription->classe->isBTS()
                && $inscription->annee_universitaire_id
                && $inscription->etudiant_id
        )->values()->all());

        if ($bts->isEmpty()) {
            return [];
        }

        $this->chargerLaCarte($bts);

        $resultat = [];
        foreach ($bts->groupBy(fn ($i) => $i->classe_id.':'.$i->annee_universitaire_id) as $groupe) {
            $resultat += $this->pourUneClasse($groupe);
        }

        return $resultat;
    }

    /**
     * Ce que lit `BtsAnnualClassMapResolver::resolveForInscription()`, en une
     * requete par relation pour tout le lot plutot qu'une par eleve.
     */
    protected function chargerLaCarte(EloquentCollection $inscriptions): void
    {
        $inscriptions->loadMissing(self::RELATIONS_DE_LA_CARTE);
    }

    /**
     * @param  Collection<int, ESBTPInscription>  $inscriptions  meme classe, meme annee
     * @return array<int, array{moyenne: float|null, semestre1: float|null, semestre2: float|null, poids: array{semester1: float, semester2: float}}>
     */
    private function pourUneClasse(Collection $inscriptions): array
    {
        $premiere = $inscriptions->first();
        $classe = $premiere->classe;
        $anneeId = (int) $premiere->annee_universitaire_id;
        $poids = $this->bulletins->getSemesterWeights($classe);

        // La classe qui porte chaque semestre, eleve par eleve.
        $classesDuSemestre = [];
        foreach ($inscriptions as $inscription) {
            $carte = $this->cartes->resolveForInscription($inscription, $anneeId);
            $classesDuSemestre[$inscription->id] = [
                'semestre1' => (int) ($carte['semestre1_classe_id'] ?? $inscription->classe_id),
                'semestre2' => (int) ($carte['semestre2_classe_id'] ?? $inscription->classe_id),
            ];
        }

        $enregistrees = $this->moyennesEnregistrees($inscriptions, $classesDuSemestre, $anneeId);
        $courantes = $this->moyennesCourantes($inscriptions, $classesDuSemestre, $enregistrees, $anneeId);

        $resultat = [];
        foreach ($inscriptions as $inscription) {
            $s1 = $enregistrees[$inscription->id]['semestre1'] ?? $courantes[$inscription->id]['semestre1'] ?? null;
            $s2 = $enregistrees[$inscription->id]['semestre2'] ?? $courantes[$inscription->id]['semestre2'] ?? null;

            $resultat[(int) $inscription->id] = [
                'moyenne' => $this->bulletins->calculateAnnualAverage($s1, $s2, $poids),
                'semestre1' => $s1,
                'semestre2' => $s2,
                'poids' => ['semester1' => (float) $poids['semester1'], 'semester2' => (float) $poids['semester2']],
            ];
        }

        return $resultat;
    }

    /**
     * Les moyennes des bulletins enregistres, comme `getAlignedBulletinAverageForPeriode()`
     * les lit : periode et ses alias, classe du semestre, moyenne strictement positive.
     *
     * @param  array<int, array{semestre1: int, semestre2: int}>  $classesDuSemestre
     * @return array<int, array<string, float>>
     */
    protected function moyennesEnregistrees(Collection $inscriptions, array $classesDuSemestre, int $anneeId): array
    {
        $aliases = [];
        foreach (['semestre1', 'semestre2'] as $semestre) {
            foreach ($this->bulletins->periodeAliases($semestre) as $alias) {
                $aliases[$alias] = $semestre;
            }
        }

        $bulletins = ESBTPBulletin::query()
            ->whereIn('etudiant_id', $inscriptions->pluck('etudiant_id')->unique()->values())
            ->where('annee_universitaire_id', $anneeId)
            ->whereIn('periode', array_keys($aliases))
            ->whereIn('classe_id', collect($classesDuSemestre)->flatten()->unique()->values())
            ->orderBy('id')
            ->get()
            ->groupBy('etudiant_id');

        $moyennes = [];
        foreach ($inscriptions as $inscription) {
            foreach ($bulletins->get($inscription->etudiant_id, collect()) as $bulletin) {
                $semestre = $aliases[(string) $bulletin->periode] ?? null;
                if ($semestre === null
                    || (int) $bulletin->classe_id !== $classesDuSemestre[$inscription->id][$semestre]
                    || isset($moyennes[$inscription->id][$semestre])
                    || $bulletin->moyenne_generale === null
                    || $bulletin->moyenne_generale <= 0) {
                    continue;
                }

                $moyennes[$inscription->id][$semestre] = $this->bulletins->getEffectiveBulletinAverage($bulletin);
            }
        }

        return $moyennes;
    }

    /**
     * Le calcul courant des semestres qui n'ont pas de bulletin enregistre :
     * le snapshot du semestre (meme lecture que `calculateStudentAverageForPeriode()`)
     * plus la note d'assiduite, comme `getAlignedBulletinAverageForPeriode()`.
     *
     * @param  array<int, array{semestre1: int, semestre2: int}>  $classesDuSemestre
     * @param  array<int, array<string, float>>  $enregistrees
     * @return array<int, array<string, float|null>>
     */
    protected function moyennesCourantes(Collection $inscriptions, array $classesDuSemestre, array $enregistrees, int $anneeId): array
    {
        $aCalculer = [];
        foreach ($inscriptions as $inscription) {
            foreach (['semestre1', 'semestre2'] as $semestre) {
                if (! isset($enregistrees[$inscription->id][$semestre])) {
                    $classe = $classesDuSemestre[$inscription->id][$semestre];
                    $aCalculer[$semestre][$classe][(int) $inscription->id] = (int) $inscription->etudiant_id;
                }
            }
        }

        $moyennes = [];
        foreach ($aCalculer as $semestre => $parClasse) {
            foreach ($parClasse as $classeId => $etudiants) {
                $snapshots = $this->snapshots->getPeriodeSnapshotsPourCohorte(
                    array_values(array_unique($etudiants)),
                    (int) $classeId,
                    $anneeId,
                    $semestre
                );

                foreach ($etudiants as $inscriptionId => $etudiantId) {
                    $snapshot = $snapshots[$etudiantId] ?? null;
                    $moyennes[$inscriptionId][$semestre] = ($snapshot['raw_total'] ?? null) === null
                        ? null
                        : (float) $snapshot['raw_total'] + (float) ($snapshot['attendance_note'] ?? 0);
                }
            }
        }

        return $moyennes;
    }
}
