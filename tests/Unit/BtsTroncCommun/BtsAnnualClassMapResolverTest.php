<?php

namespace Tests\Unit\BtsTroncCommun;

use App\Domain\BtsTroncCommun\BtsAnnualClassMapResolver;
use App\Domain\BtsTroncCommun\BtsPhaseResolver;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPhase;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BtsAnnualClassMapResolverTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_requested_classe_for_both_semesters_when_no_inscription(): void
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $etudiant = ESBTPEtudiant::factory()->create();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($etudiant->id, 4242, $annee->id);

        $this->assertNull($map['inscription_id']);
        $this->assertSame('phase_based', $map['source_model']);
        $this->assertSame(4242, $map['semestre1_classe_id']);
        $this->assertSame(4242, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_resolves_pure_tronc_commun_to_same_classe_for_both_semesters(): void
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $tcClasse = ESBTPClasse::factory()->create([
            'filiere_id' => $tcFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();

        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $tcClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 2,
            'is_active' => true,
        ]);

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($etudiant->id, $tcClasse->id, $annee->id);

        $this->assertSame($inscription->id, $map['inscription_id']);
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($tcClasse->id, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_resolves_phase_based_oriented_student_to_tc_then_spe(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id);

        $this->assertSame($inscription->id, $map['inscription_id']);
        $this->assertSame('phase_based', $map['source_model']);
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_resolves_legacy_dual_inscription_origine_then_spe(): void
    {
        [$origine, $specialisation, $tcClasse, $specClasse] = $this->makeLegacyJourney();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($specialisation->etudiant_id, $specClasse->id, $specialisation->annee_universitaire_id);

        // Le résolveur ordonne par classe_id-match en premier → la spécialisation gagne.
        $this->assertSame($specialisation->id, $map['inscription_id']);
        $this->assertSame('legacy_dual_inscription', $map['source_model']);
        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    /** @test */
    public function it_picks_the_classe_id_matching_inscription_when_re_enrolled_same_year(): void
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $classeA = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $classeB = ESBTPClasse::factory()->create([
            'filiere_id' => $specFiliere->id,
            'niveau_etude_id' => $niveau->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $etudiant = ESBTPEtudiant::factory()->create();

        // Deux inscriptions la même année — une sur classeA, une sur classeB.
        $inscA = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classeA->id,
            'annee_universitaire_id' => $annee->id,
        ]);
        $inscB = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classeB->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $resolver = app(BtsAnnualClassMapResolver::class);

        // Demande classeB → c'est l'inscription B qui doit être résolue.
        $mapB = $resolver->resolve($etudiant->id, $classeB->id, $annee->id);
        $this->assertSame($inscB->id, $mapB['inscription_id']);

        // Demande classeA → c'est l'inscription A.
        $mapA = $resolver->resolve($etudiant->id, $classeA->id, $annee->id);
        $this->assertSame($inscA->id, $mapA['inscription_id']);
    }

    /** @test */
    public function it_propagates_source_model_from_journey(): void
    {
        [$inscription] = $this->makePhaseBasedInscription();

        $resolver = app(BtsAnnualClassMapResolver::class);
        $map = $resolver->resolve($inscription->etudiant_id, $inscription->classe_id, $inscription->annee_universitaire_id);

        $this->assertArrayHasKey('source_model', $map);
        $this->assertSame('phase_based', $map['source_model']);
    }

    /**
     * @return array{0: ESBTPAnneeUniversitaire, 1: ESBTPNiveauEtude, 2: ESBTPFiliere}
     */
    /**
     * Un etudiant passe en specialite sans que son parcours ait ete saisi garde
     * une seule inscription, sur la specialite, et aucune phase. La carte
     * rendait alors cette classe pour les deux semestres : le semestre passe en
     * tronc commun paraissait vide -- zero de moyenne sur la page de resultats,
     * et un bulletin sans matieres, alors que les notes existaient bel et bien.
     *
     * @test
     */
    public function il_lit_les_notes_quand_aucune_phase_ne_dit_ou_est_le_semestre(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makeSansPhase();

        $this->semerNotes($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id, 'semestre1', 19);
        $this->semerNotes($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id, 'semestre2', 6);

        $map = app(BtsAnnualClassMapResolver::class)
            ->resolve($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id);

        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    /**
     * Le repli est de dernier recours : sans note sur un semestre, rien ne
     * change par rapport a avant.
     *
     * @test
     */
    public function un_semestre_sans_note_garde_la_classe_de_l_inscription(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makeSansPhase();

        $this->semerNotes($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id, 'semestre1', 12);

        $map = app(BtsAnnualClassMapResolver::class)
            ->resolve($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id);

        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    /**
     * Une phase saisie prime toujours, meme contre des notes qui disent
     * l'inverse : sinon une correction de parcours serait ecrasee par
     * d'anciennes notes restees sur la mauvaise classe.
     *
     * @test
     */
    public function une_phase_saisie_prime_sur_les_notes(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makePhaseBasedInscription();

        $this->semerNotes($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id, 'semestre1', 30);
        $this->semerNotes($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id, 'semestre2', 30);

        $map = app(BtsAnnualClassMapResolver::class)
            ->resolve($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id);

        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
        $this->assertSame($specClasse->id, $map['semestre2_classe_id']);
    }

    /**
     * La garde, et c'est elle qui protege la production.
     *
     * Sans phase, la chronologie est vide pour TOUT etudiant dont la filiere
     * n'est pas un tronc commun. Si le repli s'armait la, le vote deviendrait
     * l'arbitre par defaut de la classe porteuse pour la population ordinaire
     * des six ecoles -- rangs, coefficients et absences compris.
     *
     * @test
     */
    public function un_etudiant_ordinaire_sans_phase_ne_declenche_pas_le_repli(): void
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        // Filiere racine : pas une sortie de tronc commun.
        $filiere = ESBTPFiliere::factory()->create(['parent_id' => null]);
        $classeA = ESBTPClasse::factory()->create(['filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $classeB = ESBTPClasse::factory()->create(['filiere_id' => $filiere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $filiere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $classeA->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        // Des notes en nombre sur l'autre classe : elles ne doivent rien changer.
        $this->semerNotes($etudiant->id, $classeB->id, $annee->id, 'semestre1', 20);
        $this->semerNotes($etudiant->id, $classeB->id, $annee->id, 'semestre2', 20);

        $map = app(BtsAnnualClassMapResolver::class)->resolve($etudiant->id, $classeA->id, $annee->id);

        $this->assertSame($classeA->id, $map['semestre1_classe_id']);
        $this->assertSame($classeA->id, $map['semestre2_classe_id']);
    }

    /**
     * Une evaluation annulee ne vote pas. Sans cela, la carte pourrait designer
     * une classe ou la generation ne comptera aucune note -- le zero que ce
     * correctif supprime, reintroduit ailleurs.
     *
     * @test
     */
    public function une_evaluation_annulee_ne_vote_pas(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makeSansPhase();

        $this->semerNotes($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id, 'semestre1', 3);
        $this->semerNotes($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id, 'semestre1', 20, 'cancelled');

        $map = app(BtsAnnualClassMapResolver::class)
            ->resolve($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id);

        $this->assertSame($tcClasse->id, $map['semestre1_classe_id']);
    }

    /**
     * A egalite, le depart est explicite : la classe demandee prime. Un
     * GROUP BY sans ordre rendait le rang d'un bulletin dependant de l'ordre de
     * retour de la base.
     *
     * @test
     */
    public function a_egalite_la_classe_demandee_l_emporte(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makeSansPhase();

        $this->semerNotes($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id, 'semestre1', 5);
        $this->semerNotes($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id, 'semestre1', 5);

        $resolver = app(BtsAnnualClassMapResolver::class);

        $this->assertSame($tcClasse->id, $resolver->resolve($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id)['semestre1_classe_id']);
    }

    /**
     * La seconde porte -- celle qu'emprunte la page etudiant -- doit departager
     * comme la premiere. Elle passait la classe de l'inscription en guise de
     * classe demandee : les deux preferences du departage s'effondraient sur la
     * meme valeur, et la page pouvait designer une autre classe que le bulletin
     * pour le meme semestre.
     *
     * @test
     */
    public function la_porte_par_inscription_departage_sur_la_classe_demandee(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makeSansPhase();

        $this->semerNotes($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id, 'semestre1', 5);
        $this->semerNotes($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id, 'semestre1', 5);

        $resolver = app(BtsAnnualClassMapResolver::class);
        $inscription->loadMissing(['filiere', 'phases']);

        $this->assertSame(
            $tcClasse->id,
            $resolver->resolveForInscription($inscription, $inscription->annee_universitaire_id, $tcClasse->id)['semestre1_classe_id'],
            'A egalite, la classe demandee doit primer aussi par cette porte.'
        );

        $this->assertSame(
            $specClasse->id,
            $resolver->resolveForInscription($inscription, $inscription->annee_universitaire_id, $specClasse->id)['semestre1_classe_id']
        );
    }

    /**
     * Les deux portes doivent rendre la meme carte pour le meme dossier.
     *
     * @test
     */
    public function les_deux_portes_rendent_la_meme_carte(): void
    {
        [$inscription, $tcClasse, $specClasse] = $this->makeSansPhase();

        $this->semerNotes($inscription->etudiant_id, $tcClasse->id, $inscription->annee_universitaire_id, 'semestre1', 19);
        $this->semerNotes($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id, 'semestre2', 6);

        $resolver = app(BtsAnnualClassMapResolver::class);
        $inscription->loadMissing(['filiere', 'phases']);

        $parEtudiant = $resolver->resolve($inscription->etudiant_id, $specClasse->id, $inscription->annee_universitaire_id);
        $parInscription = $resolver->resolveForInscription($inscription, $inscription->annee_universitaire_id, $specClasse->id);

        $this->assertSame($parEtudiant['semestre1_classe_id'], $parInscription['semestre1_classe_id']);
        $this->assertSame($parEtudiant['semestre2_classe_id'], $parInscription['semestre2_classe_id']);
    }

    /**
     * Le parcours est construit une fois par requete, et oublie des qu'une
     * phase bouge.
     *
     * Resoudre la carte annuelle d'un seul etudiant appelait buildJourney cinq
     * fois pour un resultat identique -- une depense multipliee par soixante-dix
     * sur une generation de classe, deja juste au regard de la limite
     * d'execution.
     *
     * @test
     */
    public function le_parcours_est_memorise_puis_oublie_quand_une_phase_bouge(): void
    {
        [$inscription, $tcClasse] = $this->makePhaseBasedInscription();
        $resolver = app(BtsPhaseResolver::class);
        $id = $inscription->id;

        $requetes = 0;
        DB::listen(function () use (&$requetes) { $requetes++; });

        // Instances NEUVES a chaque appel : sur une meme instance, le chargement
        // paresseux masquerait l'absence de cache.
        $cout = function () use ($resolver, $id, &$requetes): array {
            $avant = $requetes;
            $parcours = $resolver->buildJourney(ESBTPInscription::findOrFail($id));

            return [$requetes - $avant, $parcours];
        };

        [$coutPremier, $premier] = $cout();
        $this->assertCount(2, $premier['timeline']);
        $this->assertGreaterThan(1, $coutPremier, 'Construire un parcours coute plus que le chargement.');

        // Le second appel ne doit plus rien demander que l'inscription elle-meme :
        // sans memorisation, il couterait autant que le premier.
        // La reference est mesuree, non ecrite en dur : un eager-load ajoute un
        // jour au modele ferait rougir ce test en accusant le cache a tort.
        $avant = $requetes;
        ESBTPInscription::findOrFail($id);
        $chargementSeul = $requetes - $avant;

        [$coutSecond] = $cout();
        $this->assertSame($chargementSeul, $coutSecond, 'Le parcours ne doit pas etre reconstruit.');

        // Une phase qui bouge le rend caduc. L'assertion porte sur le CONTENU :
        // si l'oubli ne faisait rien, la timeline garderait ses deux etapes.
        ESBTPInscriptionPhase::create([
            'inscription_id' => $id,
            'type_phase' => 'specialisation',
            'classe_id' => $tcClasse->id,
            'semestre_debut' => 2,
            'is_active' => false,
        ]);

        [, $apresMutation] = $cout();
        $this->assertCount(3, $apresMutation['timeline']);
    }

    /**
     * Le meme decor que makePhaseBasedInscription, sans les phases : c'est
     * exactement l'etat des etudiants dont le parcours n'a jamais ete saisi.
     *
     * @return array{0: ESBTPInscription, 1: ESBTPClasse, 2: ESBTPClasse}
     */
    private function makeSansPhase(): array
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        return [$inscription, $tcClasse, $specClasse];
    }

    private function semerNotes(int $etudiantId, int $classeId, int $anneeId, string $periode, int $combien, string $statut = 'published'): void
    {
        for ($n = 0; $n < $combien; $n++) {
            // created_by / updated_by explicitement nuls : la fabrique pointe
            // l'utilisateur 1, qui n'existe pas dans une base de test vide.
            $evaluation = ESBTPEvaluation::factory()->create([
                'classe_id' => $classeId,
                'annee_universitaire_id' => $anneeId,
                'periode' => $periode,
                'status' => $statut,
                'created_by' => null,
                'updated_by' => null,
            ]);
            // Insertion directe : la fabrique de notes ecrit une colonne
            // « observation » que la table ne porte plus.
            DB::table('esbtp_notes')->insert([
                'etudiant_id' => $etudiantId,
                'evaluation_id' => $evaluation->id,
                'matiere_id' => $evaluation->matiere_id,
                'classe_id' => $classeId,
                'note' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function makeAcademicContext(): array
    {
        $annee = ESBTPAnneeUniversitaire::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create(['year' => 1, 'type' => 'BTS']);
        $tcFiliere = ESBTPFiliere::factory()->create(['is_tronc_commun' => true, 'semestres_tronc_commun' => 1]);

        return [$annee, $niveau, $tcFiliere];
    }

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPClasse, 2: ESBTPClasse}
     */
    private function makePhaseBasedInscription(): array
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create();
        $inscription = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'tronc_commun',
            'classe_id' => $tcClasse->id,
            'filiere_id' => $tcFiliere->id,
            'semestre_debut' => 1,
            'semestre_fin' => 1,
            'is_active' => false,
        ]);
        ESBTPInscriptionPhase::create([
            'inscription_id' => $inscription->id,
            'type_phase' => 'specialisation',
            'classe_id' => $specClasse->id,
            'filiere_id' => $specFiliere->id,
            'semestre_debut' => 2,
            'is_active' => true,
        ]);

        return [$inscription, $tcClasse, $specClasse];
    }

    /**
     * @return array{0: ESBTPInscription, 1: ESBTPInscription, 2: ESBTPClasse, 3: ESBTPClasse}
     */
    private function makeLegacyJourney(): array
    {
        [$annee, $niveau, $tcFiliere] = $this->makeAcademicContext();
        $specFiliere = ESBTPFiliere::factory()->create(['parent_id' => $tcFiliere->id]);
        $tcClasse = ESBTPClasse::factory()->create(['filiere_id' => $tcFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $specClasse = ESBTPClasse::factory()->create(['filiere_id' => $specFiliere->id, 'niveau_etude_id' => $niveau->id, 'annee_universitaire_id' => $annee->id]);
        $etudiant = ESBTPEtudiant::factory()->create();

        $origine = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $tcFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $tcClasse->id,
            'annee_universitaire_id' => $annee->id,
        ]);

        $specialisation = ESBTPInscription::factory()->create([
            'etudiant_id' => $etudiant->id,
            'filiere_id' => $specFiliere->id,
            'niveau_id' => $niveau->id,
            'classe_id' => $specClasse->id,
            'annee_universitaire_id' => $annee->id,
            'type_changement' => 'specialisation',
            'inscription_origine_id' => $origine->id,
        ]);

        return [$origine, $specialisation, $tcClasse, $specClasse];
    }
}
