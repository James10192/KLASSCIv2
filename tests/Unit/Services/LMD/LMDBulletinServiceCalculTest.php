<?php

namespace Tests\Unit\Services\LMD;

use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMDBulletinService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Verrouille le coeur de calcul du bulletin universitaire (LMD).
 *
 * Ces tests n'ouvrent aucune base : ils appellent le vrai service avec un profil de
 * regles dont le resolveur de reglages est simule, et lui passent des resultats
 * d'unites d'enseignement sous forme d'objets simples. Le service ne lit ces objets
 * que par leurs proprietes `moyenne`, `credit` et `id`.
 *
 * Les scenarios ou une unite est acquise par compensation ecrivent en base (mise a
 * jour groupee du statut) : ils vivent dans LMDBulletinServiceCompensationTest.
 */
class LMDBulletinServiceCalculTest extends TestCase
{
    /** Construit le service avec les reglages d'une ecole donnee. */
    private function service(array $reglages = []): LMDBulletinService
    {
        $profil = new LmdAcademicRuleProfile(
            fn (string $cle, mixed $defaut = null): mixed => $reglages[$cle] ?? $defaut
        );

        return new LMDBulletinService($profil);
    }

    /** Resultat d'unite d'enseignement minimal, tel que le service le consomme. */
    private function resultatUe(?float $moyenne, int $credit, ?int $id = null): stdClass
    {
        $resultat = new stdClass();
        $resultat->id = $id;
        $resultat->moyenne = $moyenne;
        $resultat->credit = $credit;

        return $resultat;
    }

    /** Note d'evaluation minimale, telle que calculerMoyenneECUE la consomme. */
    private function note(float $valeur, float $bareme = 20, float $coefficient = 1, bool $absent = false): stdClass
    {
        $evaluation = new stdClass();
        $evaluation->bareme = $bareme;
        $evaluation->coefficient = $coefficient;

        $note = new stdClass();
        $note->note = $valeur;
        $note->is_absent = $absent;
        $note->evaluation = $evaluation;

        return $note;
    }

    // ---------------------------------------------------------------------
    // Moyenne d'un element constitutif (ECUE)
    // ---------------------------------------------------------------------

    public function test_la_moyenne_d_un_element_pondere_les_notes_par_le_coefficient_de_l_evaluation(): void
    {
        $notes = new Collection([
            $this->note(8, 20, 1),
            $this->note(14, 20, 3),
        ]);

        // (8 x 1 + 14 x 3) / 4 = 12.5
        $this->assertSame(12.5, $this->service()->calculerMoyenneECUE(1, 2, 3, 1, 4, $notes));
    }

    public function test_la_moyenne_d_un_element_ramene_chaque_note_sur_vingt(): void
    {
        $notes = new Collection([
            $this->note(30, 60, 1), // 30/60 -> 10/20
            $this->note(8, 10, 1),  // 8/10  -> 16/20
        ]);

        $this->assertSame(13.0, $this->service()->calculerMoyenneECUE(1, 2, 3, 1, 4, $notes));
    }

    public function test_une_absence_compte_comme_un_zero(): void
    {
        $notes = new Collection([
            $this->note(16, 20, 1),
            $this->note(16, 20, 1, absent: true),
        ]);

        $this->assertSame(8.0, $this->service()->calculerMoyenneECUE(1, 2, 3, 1, 4, $notes));
    }

    public function test_sans_note_l_element_reste_sans_moyenne(): void
    {
        $this->assertNull($this->service()->calculerMoyenneECUE(1, 2, 3, 1, 4, new Collection()));
    }

    // ---------------------------------------------------------------------
    // Moyenne generale ponderee par les credits
    // ---------------------------------------------------------------------

    public function test_la_moyenne_generale_est_ponderee_par_les_credits_des_unites(): void
    {
        $unites = [
            $this->resultatUe(8.0, 2),
            $this->resultatUe(14.0, 6),
        ];

        // (8 x 2 + 14 x 6) / 8 = 12.5 : l'unite lourde tire la moyenne, pas la moyenne simple (11)
        $this->assertSame(12.5, $this->service()->calculerMoyenneGenerale($unites));
    }

    public function test_la_moyenne_generale_ignore_les_unites_sans_moyenne_ou_sans_credit(): void
    {
        $unites = [
            $this->resultatUe(12.0, 4),
            $this->resultatUe(null, 6),  // pas encore evaluee
            $this->resultatUe(20.0, 0),  // aucun credit : hors ponderation
        ];

        $this->assertSame(12.0, $this->service()->calculerMoyenneGenerale($unites));
    }

    public function test_sans_aucune_unite_creditee_la_moyenne_generale_est_absente(): void
    {
        $this->assertNull($this->service()->calculerMoyenneGenerale([]));
        $this->assertNull($this->service()->calculerMoyenneGenerale([$this->resultatUe(null, 6)]));
    }

    // ---------------------------------------------------------------------
    // Capitalisation des credits (sans ecriture : aucune unite compensee)
    // ---------------------------------------------------------------------

    public function test_une_unite_au_dessus_du_seuil_capitalise_ses_credits(): void
    {
        $credits = $this->service()->appliquerCompensation(
            [$this->resultatUe(12.0, 6, 1), $this->resultatUe(10.0, 4, 2)],
            12.0
        );

        $this->assertSame(10, $credits);
    }

    public function test_une_unite_sous_le_seuil_ne_capitalise_rien_quand_la_moyenne_generale_est_insuffisante(): void
    {
        $credits = $this->service()->appliquerCompensation(
            [$this->resultatUe(8.0, 6, 1), $this->resultatUe(9.5, 4, 2)],
            8.6
        );

        $this->assertSame(0, $credits);
    }

    public function test_une_unite_sans_moyenne_est_ignoree_et_ne_capitalise_rien(): void
    {
        $credits = $this->service()->appliquerCompensation(
            [$this->resultatUe(null, 6, 1), $this->resultatUe(11.0, 4, 2)],
            11.0
        );

        $this->assertSame(4, $credits);
    }

    public function test_la_compensation_desactivee_prive_l_unite_faible_de_ses_credits(): void
    {
        $service = $this->service(['lmd_compensation_inter_ue' => '0']);

        $credits = $service->appliquerCompensation(
            [$this->resultatUe(14.0, 6, 1), $this->resultatUe(8.0, 4, 2)],
            11.6
        );

        // Sans compensation, seule l'unite acquise directement compte.
        $this->assertSame(6, $credits);
    }

    // ---------------------------------------------------------------------
    // Le seuil de validation appartient a l'ecole
    // ---------------------------------------------------------------------

    public function test_relever_le_seuil_de_validation_declasse_une_unite_jusque_la_acquise(): void
    {
        $unites = fn (): array => [$this->resultatUe(11.0, 6, 1), $this->resultatUe(9.0, 4, 2)];

        // Seuil par defaut (10) : l'unite a 11 est acquise, celle a 9 ne l'est pas.
        // La moyenne generale (9.8) reste sous le seuil, aucune compensation ne joue.
        $this->assertSame(6, $this->service()->appliquerCompensation($unites(), 9.8));

        // La meme ecole releve son seuil a 12 : plus aucune unite n'est acquise.
        $seuilReleve = $this->service(['lmd_validation_threshold' => '12']);
        $this->assertSame(0, $seuilReleve->appliquerCompensation($unites(), 9.8));
    }

    public function test_l_ancienne_cle_de_seuil_reste_lue_pour_les_ecoles_qui_l_ont_renseignee(): void
    {
        $service = $this->service(['lmd_seuil_validation_ecue' => '11']);

        // 10.5 passait avec un seuil a 10, plus avec un seuil a 11.
        $this->assertSame(0, $service->appliquerCompensation([$this->resultatUe(10.5, 6, 1)], 10.5));
    }

    /**
     * Caracterisation d'un manque, pas d'une regle souhaitee.
     *
     * Le reglage « note eliminatoire » existe dans le profil de regles mais le service
     * de bulletin ne le consulte jamais : seul le service de deliberation du jury
     * l'applique. Une unite au-dessus du seuil capitalise donc ses credits meme si
     * sa moyenne est sous la note eliminatoire configuree. Si cette regle est un jour
     * branchee sur le bulletin, ce test doit echouer et etre reecrit.
     */
    public function test_la_note_eliminatoire_n_influence_pas_le_calcul_du_bulletin(): void
    {
        $service = $this->service(['lmd_note_eliminatoire' => '12']);

        $credits = $service->appliquerCompensation([$this->resultatUe(11.0, 6, 1)], 11.0);

        $this->assertSame(6, $credits);
    }

    // ---------------------------------------------------------------------
    // Decision de deliberation, pilotee par le taux de capitalisation
    // ---------------------------------------------------------------------

    /** @dataProvider decisionsProvider */
    public function test_la_decision_depend_du_taux_de_credits_capitalises(
        ?float $moyenneGenerale,
        int $capitalises,
        int $totaux,
        string $attendu
    ): void {
        $this->assertSame(
            $attendu,
            $this->service()->determinerDecisionDeliberation($moyenneGenerale, $capitalises, $totaux)
        );
    }

    public static function decisionsProvider(): array
    {
        return [
            'sans moyenne generale' => [null, 30, 30, ''],
            'tous les credits, moyenne excellente' => [16.0, 30, 30, 'Félicitations du jury'],
            'tous les credits, tres bonne moyenne' => [14.0, 30, 30, 'Tableau d\'honneur'],
            'tous les credits, bonne moyenne' => [12.0, 30, 30, 'Encouragement pour le travail fourni'],
            'tous les credits, moyenne juste' => [10.0, 30, 30, 'Passage'],
            'credits partiels au-dessus de 70%' => [10.0, 21, 30, 'Passage conditionnel'],
            'credits partiels sous 70%' => [10.0, 20, 30, 'Ajourné(e)'],
            'aucun credit total connu' => [12.0, 0, 0, 'Ajourné(e)'],
        ];
    }
}
