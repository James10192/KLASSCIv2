<?php

namespace App\Services\Reinscription;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPClasse;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPInscription;
use App\Services\BtsBulletinPolicy;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
 * CE QU'IL REPRODUIT : le chemin de `BulletinService::buildDonneesBulletin()`
 * qui calcule `$moyenneAnnuelle` imprimee sur le bulletin du semestre 2. Ce
 * n'est PAS la seule ecriture de ce calcul : `collectAnnualAveragesForClasse()`
 * (rang annuel) en tient une seconde, qui ignore `tronc_commun_mga_include_s1`.
 * C'est l'IMPRIMEE que la reinscription doit suivre, et
 * `MoyennesAnnuellesPariteBulletinTest` verifie que les deux concordent —
 *
 * - chaque semestre : le bulletin enregistre (`esbtp_bulletins`, moyenne > 0)
 *   s'il existe, via `getEffectiveBulletinAverage()` (moyenne + assiduite),
 *   sinon le calcul courant (snapshot du semestre + note d'assiduite), comme
 *   `getAlignedBulletinAverageForPeriode()` ;
 * - le semestre 1 lu dans la classe qui le porte (tronc commun d'un oriente),
 *   par la carte annuelle (`BtsAnnualClassMapResolver`), SEULEMENT si
 *   `tronc_commun_mga_include_s1` est actif, comme au bulletin ;
 * - les deux combines par `calculateAnnualAverage()` avec
 *   `getSemesterWeights($classe)`.
 *
 * Un semestre sans moyenne rend `null`, comme au bulletin, qui laisse alors la
 * decision du conseil vide. Ce service ne l'invente jamais a zero.
 *
 * LA MOYENNE DE DECISION. Le conseil ne tranche pas forcement sur l'annuelle :
 * `bulletin_btsN_council_average_source` peut designer le semestre 2.
 * `decision()` applique ce meme reglage, par `BtsBulletinPolicy`, et porte le
 * repli quand le bulletin n'a rien a dire.
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

    /** Replis deja journalises : une ligne par classe et par annee, pas une par eleve. */
    private array $replisJournalises = [];

    public function __construct(
        private readonly BulletinService $bulletins,
        private readonly BtsCurrentResultSnapshotService $snapshots,
        private readonly BtsAnnualClassMapResolver $cartes,
    ) {
    }

    /**
     * @param  iterable<ESBTPInscription>  $inscriptions  inscriptions de l'annee terminee
     * @return array<int, array{moyenne: float|null, semestre1: float|null, semestre2: float|null, poids: array{semester1: float, semester2: float}, source_conseil: string, moyenne_decision: float|null}>
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
     * La moyenne sur laquelle la reinscription decide, et d'ou elle vient.
     *
     * BTS : la moyenne que le conseil du bulletin retient (annuelle ou semestre 2,
     * selon `bulletin_btsN_council_average_source`). Sans elle — semestre sans
     * note, notes hors evaluation — le bulletin n'imprime pas de decision ; la
     * reinscription doit en proposer une, retombe sur `$repli` (sa moyenne des
     * notes brutes), le DIT dans `source` et le journalise une fois par classe.
     *
     * LMD : `$repli`, inchange (rule `lmd-bts-bulletin-separation`).
     *
     * @param  array|null  $annuelle  ligne de `pour()` deja calculee pour le lot, sinon relue ici
     * @return array{moyenne: float|int, source: string, semestres: array|null}
     */
    public function decision(?ESBTPInscription $inscription, ESBTPClasse $classe, ?array $annuelle, float|int $repli, bool $sansNote): array
    {
        if (! $classe->isBTS()) {
            return ['moyenne' => $repli, 'source' => 'lmd_notes_brutes', 'semestres' => null];
        }

        if ($annuelle === null && $inscription) {
            $annuelle = $this->pour([$inscription])[(int) $inscription->id] ?? null;
        }

        if (($annuelle['moyenne_decision'] ?? null) !== null) {
            $source = $annuelle['source_conseil'] === 'annual' ? 'bulletin_annuel' : 'bulletin_semestre2';

            return ['moyenne' => $annuelle['moyenne_decision'], 'source' => $source, 'semestres' => $annuelle];
        }

        $source = $sansNote ? 'aucune_note' : 'notes_brutes';
        $cle = $classe->id.':'.($inscription->annee_universitaire_id ?? '?').':'.$source;
        if (! isset($this->replisJournalises[$cle])) {
            $this->replisJournalises[$cle] = true;
            Log::warning('Reinscription : pas de moyenne de conseil au bulletin, repli sur les notes brutes', [
                'classe_id' => $classe->id,
                'annee_universitaire_id' => $inscription->annee_universitaire_id ?? null,
                'inscription_id' => $inscription->id ?? null,
                'source' => $source,
                'semestre1' => $annuelle['semestre1'] ?? null,
                'semestre2' => $annuelle['semestre2'] ?? null,
            ]);
        }

        return ['moyenne' => $repli, 'source' => $source, 'semestres' => $annuelle];
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
        $sourceConseil = $this->sourceDuConseil($classe);
        // Comme au bulletin : sans ce reglage, le semestre 1 se lit dans la
        // classe de l'inscription, jamais dans le tronc commun.
        $s1DuTroncCommun = (bool) SettingsHelper::get('tronc_commun_mga_include_s1', true);

        // La classe qui porte chaque semestre, eleve par eleve.
        $classesDuSemestre = [];
        foreach ($inscriptions as $inscription) {
            $carte = $this->cartes->resolveForInscription($inscription, $anneeId);
            $classesDuSemestre[$inscription->id] = [
                'semestre1' => (int) (($s1DuTroncCommun ? $carte['semestre1_classe_id'] ?? null : null) ?? $inscription->classe_id),
                'semestre2' => (int) ($carte['semestre2_classe_id'] ?? $inscription->classe_id),
            ];
        }

        $enregistrees = $this->moyennesEnregistrees($inscriptions, $classesDuSemestre, $anneeId);
        $courantes = $this->moyennesCourantes($inscriptions, $classesDuSemestre, $enregistrees, $anneeId);

        $resultat = [];
        foreach ($inscriptions as $inscription) {
            $s1 = $enregistrees[$inscription->id]['semestre1'] ?? $courantes[$inscription->id]['semestre1'] ?? null;
            $s2 = $enregistrees[$inscription->id]['semestre2'] ?? $courantes[$inscription->id]['semestre2'] ?? null;

            $annuelle = $this->bulletins->calculateAnnualAverage($s1, $s2, $poids);
            $resultat[(int) $inscription->id] = [
                'moyenne' => $annuelle,
                'semestre1' => $s1,
                'semestre2' => $s2,
                'poids' => ['semester1' => (float) $poids['semester1'], 'semester2' => (float) $poids['semester2']],
                'source_conseil' => $sourceConseil,
                'moyenne_decision' => BtsBulletinPolicy::decisionAverage($sourceConseil, $s2, $annuelle),
            ];
        }

        return $resultat;
    }

    /** Le reglage que `BulletinService::automaticCouncilDecision()` lit, pour ce niveau. */
    private function sourceDuConseil(ESBTPClasse $classe): string
    {
        $annee = $classe->relationLoaded('niveau') ? $classe->niveau?->year : $classe->niveau()->value('year');
        $cle = "bulletin_bts{$annee}_council_average_source";

        return BtsBulletinPolicy::councilAverageSource($annee !== null ? (int) $annee : null, [$cle => SettingsHelper::get($cle)]);
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
