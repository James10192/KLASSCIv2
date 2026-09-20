<?php

namespace Tests\Feature\Bulletin;

use App\Domain\Bulletins\MoyennesDeLApercu;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPNote;
use App\Models\ESBTPResultat;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Quatre sources, une préséance — et elle n'était écrite nulle part.
 *
 * L'assemblage de l'aperçu des moyennes vivait sur 500 lignes dans
 * `ESBTPResultatController::previewMoyennes()`, et l'ordre de ses quatre
 * chemins d'ingestion ne se lisait que dans l'ordre de quatre boucles séparées
 * par des requêtes. Deux revues l'ont mal compté ; deux correctifs de fuite ont
 * visé le mauvais chemin.
 *
 * Le dernier test garde un défaut que l'extraction a mis au jour : les moyennes
 * étaient **lues avant d'être calculées**, si bien que le chemin 2 posait ses
 * lignes à 0,00.
 *
 * SA PORTÉE A ÉTÉ MESURÉE, PAS DÉDUITE. Une première version de ce fichier
 * annonçait « toute matière notée s'affichait à 0,00 » et le prouvait par un
 * test qui restait **vert quand on remettait l'ancien ordre** — donc il ne
 * prouvait rien. La raison : `ESBTPNoteObserver` écrit une ligne
 * `esbtp_resultats` dès qu'une note est enregistrée, donc le chemin 1 pose
 * presque toujours la ligne avant le chemin 2. Le seul cas qui atteint
 * vraiment le chemin 2 est l'élève **réinscrit**, dont les notes de l'année
 * précédente sont ramenées par `byAnneeUniversitaireWithPrevious()` alors que
 * ses lignes enregistrées portent l'autre année.
 *
 * @see \App\Domain\Bulletins\MoyennesDeLApercu
 */
class MoyennesDeLApercuTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ESBTPAnneeUniversitaire $annee;

    private ESBTPFiliere $filiere;

    private ESBTPNiveauEtude $niveau;

    private ESBTPClasse $classe;

    private ESBTPEtudiant $etudiant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->annee = ESBTPAnneeUniversitaire::factory()->create();
        $this->filiere = ESBTPFiliere::factory()->create();
        $this->niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);

        $this->classe = ESBTPClasse::factory()->create([
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'systeme_academique' => 'BTS',
        ]);

        $this->etudiant = ESBTPEtudiant::factory()->create();

        ESBTPInscription::factory()->create([
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'status' => 'active',
        ]);
    }

    /** Une matière rattachée au couple filière x niveau de la classe. */
    private function matiereDeLaMaquette(array $attributs = []): ESBTPMatiere
    {
        $matiere = ESBTPMatiere::factory()->create($attributs + ['is_active' => true]);
        $matiere->filieres()->syncWithoutDetaching([$this->filiere->id]);
        $matiere->niveaux()->syncWithoutDetaching([$this->niveau->id]);

        return $matiere;
    }

    /** Un element constitutif LMD : `unite_enseignement_id` non nul, et une vraie UE derriere. */
    private function ecue(string $suffixe): ESBTPMatiere
    {
        $ue = ESBTPUniteEnseignement::create([
            'name' => 'UE '.$suffixe,
            'code' => 'UE-'.$suffixe,
            'credit' => 6,
            'semestre' => 1,
            'is_active' => true,
        ]);

        return ESBTPMatiere::factory()->create([
            'code' => 'EC-'.$suffixe,
            'is_active' => true,
            'unite_enseignement_id' => $ue->id,
        ]);
    }

    private function noter(ESBTPMatiere $matiere, float $valeur, string $periode): void
    {
        $evaluation = ESBTPEvaluation::factory()->create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => $periode,
            'coefficient' => 1,
            'bareme' => 20,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        ESBTPNote::create([
            'evaluation_id' => $evaluation->id,
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $matiere->id,
            'note' => $valeur,
            'semestre' => $periode,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
    }

    private function coefficientConfigure(ESBTPMatiere $matiere, string $periode, float $valeur): void
    {
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => $periode,
            'coefficient' => $valeur,
        ]);
    }

    private function assembler(string $periode = 'annuel'): array
    {
        return app(MoyennesDeLApercu::class)->assembler(
            $this->etudiant,
            $this->classe,
            $this->annee,
            $periode
        );
    }

    public function test_une_matiere_notee_remonte_avec_sa_moyenne(): void
    {
        // CE TEST PASSE PAR LE CHEMIN 1, et c'est une surprise utile : saisir
        // une note suffit à créer la ligne `esbtp_resultats`, via
        // `ESBTPNoteObserver`. C'est ce qui rend le chemin 2 quasiment mort en
        // production — et c'est pour l'avoir vérifié que le test suivant existe
        // sous la forme qu'il a.
        $matiere = $this->matiereDeLaMaquette();
        $this->noter($matiere, 14.0, 'annuel');

        $apercu = $this->assembler('annuel');

        $this->assertArrayHasKey($matiere->id, $apercu['lignes']);
        $this->assertEqualsWithDelta(14.0, (float) $apercu['lignes'][$matiere->id]['moyenne'], 0.001);
    }

    public function test_une_note_de_l_annee_precedente_porte_sa_vraie_moyenne(): void
    {
        // LE SEUL CAS QUI ATTEINT VRAIMENT LE CHEMIN 2 : l'élève réinscrit.
        // `byAnneeUniversitaireWithPrevious()` ramène ses notes de l'année
        // d'avant, mais les lignes `esbtp_resultats` que l'observateur a
        // écrites portent CETTE année-là — donc le chemin 1 ne les trouve pas.
        //
        // C'est ici, et nulle part ailleurs, que l'ordre d'origine affichait
        // 0,00. Remettre le calcul des moyennes après le chemin 2 rend ce test
        // rouge ; c'est ce qui prouve qu'il mesure quelque chose.
        $anneeSuivante = ESBTPAnneeUniversitaire::factory()->create();
        $this->assertSame(
            $this->annee->id + 1,
            $anneeSuivante->id,
            'Le repli de `byAnneeUniversitaireWithPrevious()` porte sur des ids consécutifs.'
        );

        $matiere = $this->matiereDeLaMaquette();
        $this->noter($matiere, 14.0, 'annuel');

        $apercu = app(MoyennesDeLApercu::class)->assembler(
            $this->etudiant,
            $this->classe,
            $anneeSuivante,
            'annuel'
        );

        $this->assertArrayHasKey($matiere->id, $apercu['lignes']);
        $this->assertNull(
            $apercu['lignes'][$matiere->id]['id'],
            'Aucune ligne enregistrée sur cette année : la ligne vient bien du chemin 2.'
        );
        $this->assertEqualsWithDelta(14.0, (float) $apercu['lignes'][$matiere->id]['moyenne'], 0.001);
    }

    public function test_la_ligne_enregistree_gagne_sur_la_note(): void
    {
        // CHEMIN 1 CONTRE CHEMIN 2. La ligne enregistrée a la préséance : c'est
        // une décision humaine, la note est un calcul.
        $matiere = $this->matiereDeLaMaquette();
        $this->noter($matiere, 14.0, 'annuel');

        ESBTPResultat::create([
            'etudiant_id' => $this->etudiant->id,
            'classe_id' => $this->classe->id,
            'matiere_id' => $matiere->id,
            'periode' => 'annuel',
            'annee_universitaire_id' => $this->annee->id,
            'moyenne' => 9.5,
            'coefficient' => 1,
        ]);

        $apercu = $this->assembler('annuel');

        $this->assertEqualsWithDelta(9.5, (float) $apercu['lignes'][$matiere->id]['moyenne'], 0.001);
        $this->assertNotNull($apercu['lignes'][$matiere->id]['id']);
    }

    public function test_une_matiere_de_la_maquette_sans_note_apparait_sans_moyenne(): void
    {
        // CHEMIN 3. L'écran doit proposer la saisie manuelle, donc la ligne
        // existe — mais sa moyenne est nulle, pas zéro : un zéro est une note.
        $matiere = $this->matiereDeLaMaquette();

        $apercu = $this->assembler('annuel');

        $this->assertArrayHasKey($matiere->id, $apercu['lignes']);
        $this->assertNull($apercu['lignes'][$matiere->id]['moyenne']);
        $this->assertSame('manuelle', $apercu['lignes'][$matiere->id]['source']);
    }

    public function test_une_ecue_deja_enregistree_reste_visible_et_marquee_intruse(): void
    {
        // LE FILTRE DE COHÉRENCE, ET SES DEUX CONDUITES. Une ECUE LMD portant
        // une ligne enregistrée n'est PAS écartée : l'écarter emporterait sa
        // croix de suppression et la rendrait inextirpable. Elle est montrée,
        // et marquée.
        $ecue = $this->ecue('INTRUSE');

        ESBTPResultat::withoutEvents(function () use ($ecue) {
            ESBTPResultat::create([
                'etudiant_id' => $this->etudiant->id,
                'classe_id' => $this->classe->id,
                'matiere_id' => $ecue->id,
                'periode' => 'annuel',
                'annee_universitaire_id' => $this->annee->id,
                'moyenne' => 4.0,
                'coefficient' => 1,
            ]);
        });

        $apercu = $this->assembler('annuel');

        $this->assertArrayHasKey($ecue->id, $apercu['lignes']);
        $this->assertTrue($apercu['lignes'][$ecue->id]['intruse']);
    }

    public function test_une_ecue_notee_sans_ligne_enregistree_est_ecartee(): void
    {
        // L'AUTRE CONDUITE. Sur le chemin 2, rien ne la retient : elle n'a pas
        // de ligne à retirer, donc la montrer ne servirait qu'à la faire entrer
        // dans un bulletin BTS.
        $ecue = $this->ecue('NOTEE');
        $ecue->filieres()->syncWithoutDetaching([$this->filiere->id]);
        $ecue->niveaux()->syncWithoutDetaching([$this->niveau->id]);

        ESBTPEvaluation::withoutEvents(function () use ($ecue) {
            $this->noter($ecue, 4.0, 'annuel');
        });

        $apercu = $this->assembler('annuel');

        $this->assertArrayNotHasKey($ecue->id, $apercu['lignes']);
    }

    public function test_le_snapshot_recouvre_la_ligne_posee_par_les_chemins_precedents(): void
    {
        // CHEMIN 4, et il ne tournait dans AUCUN test. Les six premiers sont
        // tous en `annuel`, periode ou `recouvrirParLeSnapshot()` n est jamais
        // appele : la branche etait extraite sans etre eprouvee.
        //
        // LE DISCRIMINANT EST LE COEFFICIENT, PAS LA MOYENNE — une premiere
        // version de ce test desaccordait la moyenne enregistree et attendait
        // que le snapshot la recouvre. Elle est restee rouge, et elle avait
        // tort : le snapshot HONORE la moyenne manuelle (`manual_resultat`),
        // donc les deux chemins rendaient la meme valeur et le test ne pouvait
        // rien distinguer.
        //
        // Le coefficient, lui, separe nettement les deux : sur un semestre,
        // c est celui que le snapshot porte ; sur `annuel`, celui de
        // `coefficient()`. Meme montage, deux periodes, deux valeurs.
        //
        // LES DEUX VALEURS ONT CHANGE QUAND LE JOB A ETE CORRIGE, et c est le
        // signe que ce test mord. Le snapshot rendait `1` tant que
        // `RecomputeStudentResultatJob` ecrivait `1` en dur sur la ligne neuve ;
        // il lit desormais la maquette, donc `5` — le coefficient du SECOND
        // semestre, celui de l onglet ouvert.
        $matiere = $this->matiereDeLaMaquette();
        $this->coefficientConfigure($matiere, 'semestre1', 2.0);
        $this->coefficientConfigure($matiere, 'semestre2', 5.0);
        $this->noter($matiere, 12.0, 'semestre2');

        $surLeSemestre = $this->assembler('semestre2');
        $this->assertEqualsWithDelta(
            5.0,
            (float) $surLeSemestre['lignes'][$matiere->id]['coefficient'],
            0.001,
            'Le chemin 4 n a pas recouvert, ou il a recouvert par un coefficient qui n est pas celui du semestre.'
        );

        $surLAnnee = $this->assembler('annuel');
        $this->assertEqualsWithDelta(
            2.0,
            (float) $surLAnnee['lignes'][$matiere->id]['coefficient'],
            0.001,
            'Le chemin 4 a tourne sur `annuel`, ou il ne doit pas.'
        );
    }

    public function test_une_matiere_sans_note_garde_le_coefficient_de_sa_periode(): void
    {
        // C est le cas ou le threading de `$periode` dans `coefficient()` se
        // VOIT : le snapshot ne porte pas cette matiere (aucune note, aucune
        // ligne enregistree), donc le chemin 4 ne la recouvre pas et le
        // chemin 3 garde la main.
        //
        // Le controle a rejouer : retirer `$periode` de l appel a
        // `coefficientOrDefault()`. `getCoefficientForCombination()` normalise
        // alors a `semestre1`, rend 2, et ce test vire au rouge.
        $matiere = $this->matiereDeLaMaquette();
        $this->coefficientConfigure($matiere, 'semestre1', 2.0);
        $this->coefficientConfigure($matiere, 'semestre2', 5.0);

        $apercu = $this->assembler('semestre2');

        $this->assertEqualsWithDelta(
            5.0,
            (float) $apercu['lignes'][$matiere->id]['coefficient'],
            0.001,
            'Le second semestre affiche le coefficient du premier.'
        );
    }
}
