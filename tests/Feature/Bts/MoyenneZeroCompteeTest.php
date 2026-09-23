<?php

namespace Tests\Feature\Bts;

use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\User;
use App\Services\BulletinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Une matiere a 0 est une matiere notee : elle compte dans la moyenne generale.
 *
 * Deux calculs l'ecartaient par un `> 0` et faisaient passer 14 (coef 2) et
 * 0 (coef 1) pour 14,00 au lieu de 9,33 — dont un qui ECRIT dans
 * `esbtp_bulletins.moyenne_generale` (`BulletinAverageBackfillService`).
 * Voir `.claude/rules/rien-en-dur.md`, « un montant nul est une valeur ».
 */
class MoyenneZeroCompteeTest extends TestCase
{
    use RefreshDatabase;
    use MonteUneClasseBts;

    /** Une matiere du semestre de coefficient 1 (`matiereConfiguree()` pose 2). */
    private function matiereDeCoefficientUn(): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'config' => ['type' => 'general', 'coefficient' => 1],
        ]);

        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => 1,
        ]);

        return $matiere;
    }

    private function unSuperAdmin(): User
    {
        Role::findOrCreate('superAdmin', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user = User::withoutEvents(fn () => User::factory()->create());
        $user->assignRole('superAdmin');

        return $user;
    }

    public function test_la_fiche_resultats_compte_une_matiere_a_zero(): void
    {
        $this->monterLaClasse();
        $coefDeux = $this->matiereConfiguree();
        $coefUn = $this->matiereDeCoefficientUn();

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($coefDeux), 14);
        $this->noter($etudiant, $this->evaluationDe($coefUn), 0);

        // Onglet annuel, un seul semestre renseigne : la moyenne affichee est
        // celle du controleur, pas celle du snapshot (etat `annual_incomplete`).
        $reponse = $this->actingAs($this->unSuperAdmin())
            ->get(route('esbtp.resultats.etudiant', [
                'etudiant' => $etudiant->id,
                'classe_id' => $this->classe->id,
                'annee_universitaire_id' => $this->annee->id,
                'periode' => 'annuel',
            ]));

        $reponse->assertOk();
        $matieres = $reponse->viewData('notesByMatiere') ?? [];

        // Temoins : les deux matieres sont dans le tableau, et celle a zero
        // porte bien 0 — pas `null`, qui voudrait dire « pas de moyenne ».
        $this->assertArrayHasKey($coefDeux->id, $matieres, 'Temoin : la matiere a 14 doit etre dans le tableau.');
        $this->assertArrayHasKey($coefUn->id, $matieres, 'Temoin : la matiere a 0 doit etre dans le tableau.');
        $this->assertNotNull($matieres[$coefUn->id]['moyenne'], 'Une moyenne de 0 est une moyenne, pas une absence.');
        $this->assertEqualsWithDelta(0.0, (float) $matieres[$coefUn->id]['moyenne'], 0.001);

        $this->assertEqualsWithDelta(
            9.33,
            (float) $reponse->viewData('moyenneGenerale'),
            0.01,
            '(14 x 2 + 0 x 1) / 3 = 9,33 — avec le filtre `> 0` : 14,00.'
        );
    }

    public function test_la_moyenne_annuelle_de_repli_compte_une_matiere_a_zero(): void
    {
        $this->monterLaClasse();
        $coefDeux = $this->matiereConfiguree();
        $coefUn = $this->matiereDeCoefficientUn();

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($coefDeux), 14);
        $this->noter($etudiant, $this->evaluationDe($coefUn), 0);

        $moyenne = app(BulletinService::class)->calculateStudentAverageForPeriode(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'annuel'
        );

        $this->assertNotNull($moyenne, 'Temoin : sans moyenne, le test ne prouve rien.');
        $this->assertEqualsWithDelta(
            9.33,
            (float) $moyenne,
            0.01,
            '(14 x 2 + 0 x 1) / 3 = 9,33 — avec le filtre `> 0` : 14,00, ecrit tel quel par le backfill.'
        );
    }

    /**
     * Le bord qui distingue « moyenne 0 » de « pas de moyenne » : un eleve dont
     * la seule matiere vaut 0 a une moyenne de 0. Le filtre `> 0` rendait
     * `null` — le backfill classait alors le bulletin « non remplissable ».
     */
    public function test_une_seule_matiere_a_zero_donne_zero_et_non_null(): void
    {
        $this->monterLaClasse();
        $coefUn = $this->matiereDeCoefficientUn();

        $etudiant = $this->etudiantInscrit();
        $this->noter($etudiant, $this->evaluationDe($coefUn), 0);

        $moyenne = app(BulletinService::class)->calculateStudentAverageForPeriode(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'annuel'
        );

        $this->assertNotNull($moyenne, 'Une matiere notee 0 donne une moyenne de 0, pas une absence de moyenne.');
        $this->assertEqualsWithDelta(0.0, (float) $moyenne, 0.001);
    }

    /** Sans aucune note, il n'y a toujours pas de moyenne : `null`, pas 0. */
    public function test_sans_aucune_note_la_moyenne_de_repli_reste_nulle(): void
    {
        $this->monterLaClasse();
        $this->matiereDeCoefficientUn();

        $etudiant = $this->etudiantInscrit();

        $this->assertNull(app(BulletinService::class)->calculateStudentAverageForPeriode(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'annuel'
        ));
    }
}
