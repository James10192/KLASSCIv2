<?php

namespace Tests\Unit\Domain\Academique;

use App\Domain\Academique\CoherenceSystemeAcademique;
use App\Http\Controllers\ESBTPEtudiantController;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Unit\Domain\Notes\SchemaDesMoyennes;

/**
 * Faute de bulletin, le certificat de scolarité fait la moyenne des moyennes
 * enregistrées de l'année (`ESBTPEtudiantController::attachMoyenneCalculee()`).
 * Une ligne posée sur une matière étrangère au système de la classe — une ECUE
 * dans une classe BTS, celle que laisse par exemple une évaluation rebasculée
 * vers la bonne matière — n'y entre pas : la même règle que le bulletin et le
 * parcours ({@see CoherenceSystemeAcademique::resultatsRetenus()}).
 */
class CertificatEcarteLesMoyennesIncoherentesTest extends TestCase
{
    use SchemaDesMoyennes;

    private const ETUDIANT = 100;

    private const CLASSE = 10;

    private const MATHS = 5;

    private const ECUE = 9;

    protected function setUp(): void
    {
        parent::setUp();

        $this->monterLeSchemaDesMoyennes();

        DB::table('esbtp_classes')->insert(['id' => self::CLASSE, 'name' => '2BTS GBAT E', 'systeme_academique' => 'BTS']);
        DB::table('esbtp_matieres')->insert([
            ['id' => self::MATHS, 'name' => 'Maths', 'unite_enseignement_id' => null, 'is_active' => 1],
            ['id' => self::ECUE, 'name' => 'OGC', 'unite_enseignement_id' => 3, 'is_active' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        $this->demonterLeSchemaDesMoyennes();

        parent::tearDown();
    }

    public function test_le_certificat_ne_compte_pas_la_moyenne_d_une_ecue_dans_une_classe_bts(): void
    {
        // Maths porte 16 et 4 depuis la rebascule : 10. La ligne de l'ECUE est
        // restée à 4. (10 + 4) / 2 = 7 compterait le 4 deux fois ; 10 est juste.
        $this->resultat(self::MATHS, 10);
        $this->resultat(self::ECUE, 4);

        $inscription = (object) ['anneeUniversitaire' => (object) ['id' => 1]];
        $controleur = app(ESBTPEtudiantController::class);
        $methode = new \ReflectionMethod($controleur, 'attachMoyenneCalculee');
        $methode->setAccessible(true);
        $methode->invoke($controleur, collect([$inscription]), self::ETUDIANT);

        $this->assertSame(10.0, (float) $inscription->moyenne_generale_calculee);
    }

    private function resultat(int $matiereId, float $moyenne): void
    {
        DB::table('esbtp_resultats')->insert([
            'etudiant_id' => self::ETUDIANT, 'classe_id' => self::CLASSE, 'matiere_id' => $matiereId,
            'annee_universitaire_id' => 1, 'periode' => 'semestre1', 'moyenne' => $moyenne, 'coefficient' => 1,
        ]);
    }
}
