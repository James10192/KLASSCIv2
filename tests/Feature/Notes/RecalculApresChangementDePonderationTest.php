<?php

namespace Tests\Feature\Notes;

use App\Domain\Notes\RecalculApresDeplacement;
use App\Http\Controllers\ESBTPEvaluationController;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPResultat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\TestCase;

/**
 * Ce qui arrive a `esbtp_resultats` quand le BAREME ou le COEFFICIENT d'une
 * evaluation notee change.
 *
 * Les notes ne bougent pas : l'observateur de `ESBTPNote` ne tourne donc pas,
 * et la moyenne enregistree d'avant — calculee sur l'ancienne ponderation —
 * garde la main a l'ecran comme au bulletin. Deux ecrans modifient ces deux
 * valeurs : le formulaire complet (`update()`) et l'edition rapide de la grille
 * des notes (`quickUpdate()`). Les deux sont couverts ici.
 *
 * Mesure en retirant, pas deduit : neutraliser `apresChangementDePonderation()`
 * fait tomber les trois premiers tests ; comparer les valeurs en chaines au lieu
 * de nombres fait tomber le quatrieme ; neutraliser `baremeMinimal()` fait tomber
 * les deux refus ; retirer le `refresh()` de `quickUpdate()` fait tomber le
 * test du coefficient renvoye ; rattraper moins large que `\Throwable` fait
 * tomber le test de la panne. Le test « deplacer et reponderer » garde un
 * comportement deja juste (le recalcul du deplacement lit le nouveau
 * coefficient) : il reste vert sans ce correctif, et c'est son role.
 */
class RecalculApresChangementDePonderationTest extends TestCase
{
    use MonteUneClasseBts;
    use RefreshDatabase;

    /** @test */
    public function changer_le_coefficient_depuis_l_edition_rapide_recalcule_la_moyenne(): void
    {
        [$etudiant, $matiere, $ponderee] = $this->deuxNotes();

        $reponse = $this->editionRapide($ponderee, bareme: 20, coefficient: 3);

        // (10 x 3 + 20 x 1) / 4 = 12,5 ; sans recalcul, la moyenne reste a 15.
        $this->assertSame(12.5, $this->moyenne($etudiant->id, $matiere->id));
        $this->assertSame(0, $reponse['moyennes_non_recalculees']);
    }

    /** @test */
    public function changer_le_bareme_depuis_l_edition_rapide_recalcule_la_moyenne(): void
    {
        [$etudiant, $matiere, $ponderee] = $this->deuxNotes();

        // 10 sur 40 vaut 5 sur 20 : (5 + 20) / 2 = 12,5.
        $this->editionRapide($ponderee, bareme: 40, coefficient: 1);

        $this->assertSame(12.5, $this->moyenne($etudiant->id, $matiere->id));
    }

    /** @test */
    public function changer_le_coefficient_depuis_le_formulaire_complet_recalcule_la_moyenne(): void
    {
        [$etudiant, $matiere, $ponderee] = $this->deuxNotes();

        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        app(ESBTPEvaluationController::class)->update(
            Request::create('/', 'PUT', [
                'titre' => 'Devoir repondere',
                'type' => 'devoir',
                'date_evaluation' => now()->toDateString(),
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'classe_id' => $this->classe->id,
                'matiere_id' => $matiere->id,
                'bareme' => 20,
                'coefficient' => 3,
                'periode' => 'semestre1',
            ]),
            $ponderee
        );

        $this->assertSame(3.0, (float) $ponderee->fresh()->coefficient);
        $this->assertSame(12.5, $this->moyenne($etudiant->id, $matiere->id));
    }

    /**
     * Le modele ne caste ni le bareme ni le coefficient : la base rend « 20.00 »
     * la ou le formulaire envoie « 20 ». Comparer les chaines declencherait un
     * recalcul a chaque enregistrement ; la comparaison est numerique.
     *
     * @test
     */
    public function une_ponderation_inchangee_ne_declenche_aucun_recalcul(): void
    {
        [$etudiant, $matiere, $ponderee] = $this->deuxNotes();

        // Perimee a la main : si un recalcul partait, il la remettrait a 15.
        DB::table('esbtp_resultats')->where('etudiant_id', $etudiant->id)
            ->where('matiere_id', $matiere->id)->update(['moyenne' => 7]);

        $bilan = RecalculApresDeplacement::apresChangementDePonderation(
            $ponderee->fresh(),
            ['bareme' => '20', 'coefficient' => '1'],
        );

        $this->assertSame(0, $bilan['recalculs_tentes']);
        $this->assertSame(7.0, $this->moyenne($etudiant->id, $matiere->id));
    }

    /**
     * La saisie refuse une note au-dessus du bareme ; baisser le bareme ensuite
     * contournait l'invariant, et le recalcul enregistrait 20 sur 20 pour un
     * 10 sur 5 — jusqu'a 36 pour un 18 ramene sur 10.
     *
     * @test
     */
    public function l_edition_rapide_refuse_un_bareme_sous_une_note_saisie(): void
    {
        [$etudiant, $matiere, $ponderee] = $this->deuxNotes();
        $this->actingAs(User::factory()->create());

        try {
            app(ESBTPEvaluationController::class)->quickUpdate(
                Request::create('/', 'PATCH', ['titre' => 'Devoir', 'bareme' => 5, 'coefficient' => 1]),
                $ponderee
            );
            $this->fail('Un bareme sous la note saisie (10) aurait du etre refuse.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('bareme', $e->errors());
        }

        $this->assertSame(20.0, (float) $ponderee->fresh()->bareme);
        $this->assertSame(15.0, $this->moyenne($etudiant->id, $matiere->id));
    }

    /** @test */
    public function le_formulaire_complet_refuse_un_bareme_sous_une_note_saisie(): void
    {
        [$etudiant, $matiere, $ponderee] = $this->deuxNotes();

        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        $reponse = app(ESBTPEvaluationController::class)->update(
            Request::create('/', 'PUT', [
                'titre' => 'Devoir',
                'type' => 'devoir',
                'date_evaluation' => now()->toDateString(),
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'classe_id' => $this->classe->id,
                'matiere_id' => $matiere->id,
                'bareme' => 5,
                'coefficient' => 1,
                'periode' => 'semestre1',
            ]),
            $ponderee
        );

        $this->assertStringContainsString('barème', (string) $reponse->getSession()->get('error'));
        $this->assertSame(20.0, (float) $ponderee->fresh()->bareme);
        $this->assertSame(15.0, $this->moyenne($etudiant->id, $matiere->id));
    }

    /**
     * Le coefficient n'a qu'une decimale en base. La grille recalcule avec la
     * valeur renvoyee : lui renvoyer la saisie brute (1,25) la ferait diverger
     * de la moyenne enregistree, calculee sur 1,3.
     *
     * @test
     */
    public function l_edition_rapide_renvoie_le_coefficient_tel_qu_enregistre(): void
    {
        [, , $ponderee] = $this->deuxNotes();

        $reponse = $this->editionRapide($ponderee, bareme: 20, coefficient: 1.25);

        $this->assertSame((float) $ponderee->fresh()->coefficient, (float) $reponse['evaluation']['coefficient']);
    }

    /**
     * Deplacer ET reponderer d'un coup : le recalcul du deplacement lit deja le
     * nouveau coefficient a l'arrivee, rien n'est a ajouter.
     *
     * @test
     */
    public function deplacer_et_reponderer_ensemble_recalcule_l_arrivee_avec_le_nouveau_coefficient(): void
    {
        $this->monterLaClasse();
        $depart = $this->matiereConfiguree();
        $arrivee = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $deplacee = $this->evaluationDe($depart);
        $this->noter($etudiant, $deplacee, 10);
        $this->noter($etudiant, $this->evaluationDe($arrivee), 20);

        Gate::before(fn () => true);
        $this->actingAs(User::factory()->create());

        app(ESBTPEvaluationController::class)->update(
            Request::create('/', 'PUT', [
                'titre' => 'Devoir deplace',
                'type' => 'devoir',
                'date_evaluation' => now()->toDateString(),
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'classe_id' => $this->classe->id,
                'matiere_id' => $arrivee->id,
                'bareme' => 20,
                'coefficient' => 3,
                'periode' => 'semestre1',
            ]),
            $deplacee
        );

        // (10 x 3 + 20 x 1) / 4 : le coefficient 3 est bien lu a l'arrivee.
        $this->assertSame(12.5, $this->moyenne($etudiant->id, $arrivee->id));
    }

    /**
     * L'evaluation est enregistree avant le recalcul. Qu'il casse ne doit pas
     * faire repondre « erreur » — la personne croirait que rien n'est sauve —
     * mais annoncer les moyennes restees en l'etat.
     *
     * @test
     */
    public function un_recalcul_qui_casse_est_annonce_sans_nier_l_enregistrement(): void
    {
        [, , $ponderee] = $this->deuxNotes();

        DB::listen(function ($requete) {
            if (str_contains($requete->sql, 'select distinct') && str_contains($requete->sql, 'esbtp_notes')) {
                throw new \RuntimeException('panne simulee');
            }
        });

        $reponse = $this->editionRapide($ponderee, bareme: 20, coefficient: 3);

        $this->assertTrue($reponse['success']);
        $this->assertSame(1, $reponse['moyennes_non_recalculees']);
        $this->assertStringContainsString("n'ont pas pu être recalculées", $reponse['message']);
        $this->assertSame(3.0, (float) $ponderee->fresh()->coefficient);
    }

    /**
     * Une matiere, deux evaluations a 10/20 et 20/20, coefficient 1 : la
     * moyenne enregistree vaut 15. Rend l'evaluation a 10, celle qu'on
     * reponderera.
     *
     * @return array{0:\App\Models\ESBTPEtudiant, 1:\App\Models\ESBTPMatiere, 2:ESBTPEvaluation}
     */
    private function deuxNotes(): array
    {
        $this->monterLaClasse();
        $matiere = $this->matiereConfiguree();
        $etudiant = $this->etudiantInscrit();

        $ponderee = $this->evaluationDe($matiere);
        $this->noter($etudiant, $ponderee, 10);
        $this->noter($etudiant, $this->evaluationDe($matiere), 20);

        $this->assertSame(15.0, $this->moyenne($etudiant->id, $matiere->id));

        return [$etudiant, $matiere, $ponderee];
    }

    /** @return array<string,mixed> */
    private function editionRapide(ESBTPEvaluation $evaluation, float $bareme, float $coefficient): array
    {
        $this->actingAs(User::factory()->create());

        $reponse = app(ESBTPEvaluationController::class)->quickUpdate(
            Request::create('/', 'PATCH', [
                'titre' => $evaluation->titre ?: 'Devoir',
                'bareme' => $bareme,
                'coefficient' => $coefficient,
            ]),
            $evaluation
        );

        $donnees = $reponse->getData(true);
        $this->assertSame(200, $reponse->getStatusCode(), json_encode($donnees));

        return $donnees;
    }

    private function moyenne(int $etudiantId, int $matiereId): ?float
    {
        $valeur = ESBTPResultat::where('etudiant_id', $etudiantId)
            ->where('classe_id', $this->classe->id)
            ->where('matiere_id', $matiereId)
            ->where('periode', 'semestre1')
            ->where('annee_universitaire_id', $this->annee->id)
            ->value('moyenne');

        return $valeur === null ? null : (float) $valeur;
    }
}
