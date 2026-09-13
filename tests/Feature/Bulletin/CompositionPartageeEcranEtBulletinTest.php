<?php

namespace Tests\Feature\Bulletin;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\User;
use App\Services\BulletinService;
use App\Services\ESBTP\BtsCurrentResultSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'ecran de suivi et le bulletin doivent composer la moyenne de la MEME facon.
 *
 * L'ecran additionnait toutes les matieres a plat. Le bulletin, lui, respecte
 * le mode choisi par l'ecole. Tant que le mode restait « ponderee » les deux
 * tombaient juste — ils font alors le meme calcul. Des qu'une ecole compose par
 * blocs, le meme etudiant lisait deux moyennes differentes le meme jour, et
 * personne ne pouvait dire laquelle etait la bonne.
 *
 * Le cas ci-dessous est choisi pour que les deux compositions ne puissent pas
 * se confondre : 10.00 a plat, 12.00 par blocs.
 */
class CompositionPartageeEcranEtBulletinTest extends TestCase
{
    use RefreshDatabase;

    private ESBTPEtudiant $etudiant;

    private ESBTPClasse $classe;

    private ESBTPAnneeUniversitaire $annee;

    private User $auteur;

    protected function setUp(): void
    {
        parent::setUp();

        // La fabrique d'evaluation ecrit « created_by => 1 » en dur et la colonne
        // porte une cle etrangere vers users : sans utilisateur en base, le
        // fixture casse avant meme d'avoir mesure quoi que ce soit.
        $this->auteur = User::factory()->create();

        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $this->classe = ESBTPClasse::factory()->create([
            'filiere_id' => $filiere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $this->annee->id,
        ]);

        $this->etudiant = ESBTPEtudiant::factory()->create();

        // Un bloc general leger (coefficient 1) et un bloc professionnel lourd
        // (coefficient 3) : a plat le professionnel ecrase le general, par blocs
        // a poids egaux les deux pesent pareil.
        $this->matiereNotee('generale', 1, 16.0);
        $this->matiereNotee('technologique_professionnelle', 3, 8.0);
    }

    public function test_par_defaut_l_ecran_rend_la_ponderation_sur_toutes_les_matieres(): void
    {
        // (16 x 1 + 8 x 3) / 4
        $this->assertEqualsWithDelta(10.00, $this->moyenneDeLEcran(), 0.01);
    }

    public function test_en_mode_blocs_l_ecran_rend_la_moyenne_des_deux_blocs(): void
    {
        $this->modeBlocs();

        // (16 + 8) / 2 — et surtout pas 10.00, qui serait la ponderation a plat.
        $moyenne = $this->moyenneDeLEcran();

        $this->assertEqualsWithDelta(12.00, $moyenne, 0.01);
        $this->assertNotEqualsWithDelta(
            10.00,
            $moyenne,
            0.01,
            "L'ecran a compose a plat alors que l'ecole compose par blocs : il annonce une moyenne que le bulletin ne confirmera pas."
        );
    }

    public function test_l_ecran_et_la_composition_du_bulletin_rendent_le_meme_nombre(): void
    {
        $this->modeBlocs();

        $service = app(BulletinService::class);

        // Les memes lignes, passees a la composition que le bulletin utilise.
        $attendu = $service->composerLesBlocsDuSemestre([
            ['moyenne' => 16.0, 'coefficient' => 1.0, 'type_formation' => 'generale'],
            ['moyenne' => 8.0, 'coefficient' => 3.0, 'type_formation' => 'technologique_professionnelle'],
        ])['moyenne'];

        $this->assertEqualsWithDelta($attendu, $this->moyenneDeLEcran(), 0.01);
    }

    private function moyenneDeLEcran(): float
    {
        $snapshot = app(BtsCurrentResultSnapshotService::class)->getSemesterSnapshot(
            $this->etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'semestre1'
        );

        $this->assertSame('semester_complete', $snapshot['state'], 'Le releve doit etre complet, sinon le test ne mesure rien.');

        return (float) $snapshot['raw_total'];
    }

    private function matiereNotee(string $typeFormation, float $coefficient, float $note): void
    {
        $matiere = ESBTPMatiere::factory()->create(['type_formation' => $typeFormation]);

        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->classe->filiere_id,
            'niveau_etude_id' => $this->classe->niveau_etude_id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => $coefficient,
        ]);

        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'bareme' => 20,
            'coefficient' => 1,
            'status' => 'completed',
            'created_by' => $this->auteur->id,
            'updated_by' => $this->auteur->id,
        ]);

        ESBTPNote::factory()->create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $this->etudiant->id,
            'note' => $note,
            // `valeur` est un alias mutateur de `note` : laisser celui de la
            // fabrique ecraserait la note par un tirage aleatoire.
            'valeur' => $note,
            // Colonnes denormalisees de la note, non nulles en base.
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'semestre' => '1',
            'created_by' => $this->auteur->id,
            'updated_by' => $this->auteur->id,
        ]);

        // Enregistrer une note fait naitre sa ligne de resultat, avec un
        // coefficient de remplissage a 1. Le releve preferant ce coefficient-la
        // a celui configure, il faut y porter le vrai — c'est aussi ce que fait
        // la generation du bulletin (persistResultats).
        ESBTPResultat::where('etudiant_id', $this->etudiant->id)
            ->where('matiere_id', $matiere->id)
            ->update(['coefficient' => $coefficient]);
    }

    private function modeBlocs(): void
    {
        SettingsHelper::setOrCreate('bulletin_moyenne_mode', 'blocs', 'bulletin', 'string');
        SettingsHelper::setOrCreate('bulletin_bloc_general_coef', '1', 'bulletin', 'string');
        SettingsHelper::setOrCreate('bulletin_bloc_professionnel_coef', '1', 'bulletin', 'string');
    }
}
