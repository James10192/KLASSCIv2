<?php

namespace Tests\Feature\Frais;

use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use App\Services\Frais\MontantsParFrais;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Ce que chaque frais a REELLEMENT encaisse, une fois les avoirs deduits.
 *
 * Ce test existe parce que ce calcul a ete livre faux : la version deployee
 * sommait les encaissements sans soustraire les remboursements. Un etudiant
 * rembourse apparaissait alors comme ayant paye plus qu'il n'avait verse, et
 * basculait de « en retard » a « a jour » sur le suivi par categorie.
 *
 * Les tests unitaires ne l'avaient pas vu : ils couvraient la repartition d'UN
 * versement, pas l'agregat. C'est l'agregat qui produit les montants affiches.
 */
class MontantsNetsParFraisTest extends TestCase
{
    use RefreshDatabase;

    private MontantsParFrais $regle;

    private int $inscriptionId = 4242;

    private int $etudiantId = 4242;

    private int $recu = 1;

    protected function setUp(): void
    {
        parent::setUp();
        $this->regle = app(MontantsParFrais::class);

        // Ce qui est teste ici est une agregation SQL sur les versements et
        // leurs allocations. Fabriquer autour un etudiant, une inscription,
        // une classe, une filiere et une annee — chacun avec ses propres
        // colonnes obligatoires — testerait la fixture, pas la regle, et
        // rendrait le test illisible pour qui vient verifier un calcul.
        //
        // Les contraintes sont donc levees le temps du test. Le perimetre
        // reste honnete : aucune de ces relations n'entre dans le calcul.
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
    }

    protected function tearDown(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        parent::tearDown();
    }

    private function frais(string $nom): ESBTPFraisCategory
    {
        return ESBTPFraisCategory::create([
            'name' => $nom,
            'code' => strtoupper(substr(md5($nom.microtime()), 0, 8)),
            'is_mandatory' => true,
            'is_active' => true,
            'default_amount' => 0,
            'payment_deadline_days' => 30,
            'sort_order' => 1,
        ]);
    }

    /**
     * Ecrit un versement sans passer par le service d'encaissement : ce test
     * porte sur la LECTURE des montants, pas sur le circuit d'ecriture.
     */
    private function versement(float $montant, ?int $fraisId, string $nature = 'encaissement', string $statut = 'validé'): int
    {
        return DB::table('esbtp_paiements')->insertGetId([
            'inscription_id' => $this->inscriptionId,
            // Colonnes obligatoires sans valeur par defaut. Leur contenu
            // n'entre pas dans le calcul teste ici — qui lit des montants par
            // inscription et par frais — mais la table les exige.
            'etudiant_id' => $this->etudiantId,
            'annee_universitaire_id' => 1,
            'mode_paiement' => 'especes',
            'numero_recu' => 'TEST-'.$this->recu++,
            'montant' => $montant,
            'status' => $statut,
            'nature' => $nature,
            'avoir_kind' => $nature === 'avoir' ? 'refund' : null,
            'frais_category_id' => $fraisId,
            'date_paiement' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function netSur(ESBTPFraisCategory $frais): float
    {
        $net = $this->regle->netParInscriptionEtFrais([$this->inscriptionId]);

        return (float) ($net[$this->inscriptionId][$frais->id] ?? 0);
    }

    public function test_un_encaissement_simple_compte_en_entier(): void
    {
        $scolarite = $this->frais('Scolarité');
        $this->versement(150000, $scolarite->id);

        $this->assertSame(150000.0, $this->netSur($scolarite));
    }

    /**
     * LE test qui manquait.
     */
    public function test_un_remboursement_est_deduit(): void
    {
        $scolarite = $this->frais('Scolarité');
        $this->versement(150000, $scolarite->id);
        $this->versement(50000, $scolarite->id, nature: 'avoir');

        $this->assertSame(100000.0, $this->netSur($scolarite));
    }

    public function test_un_remboursement_non_valide_n_est_pas_deduit(): void
    {
        $scolarite = $this->frais('Scolarité');
        $this->versement(150000, $scolarite->id);
        // Pas encore valide : l'argent n'a pas quitte la caisse.
        $this->versement(50000, $scolarite->id, nature: 'avoir', statut: 'en_attente');

        $this->assertSame(150000.0, $this->netSur($scolarite));
    }

    public function test_le_net_ne_descend_jamais_sous_zero(): void
    {
        $scolarite = $this->frais('Scolarité');
        $this->versement(20000, $scolarite->id);
        $this->versement(50000, $scolarite->id, nature: 'avoir');

        $this->assertSame(0.0, $this->netSur($scolarite));
    }

    public function test_un_versement_reparti_alimente_chaque_frais_a_hauteur_de_sa_part(): void
    {
        $inscription = $this->frais('Inscription');
        $tenue = $this->frais('Tenue');

        $paiementId = $this->versement(255000, $inscription->id);
        ESBTPPaiementAllocation::create(['paiement_id' => $paiementId, 'frais_category_id' => $inscription->id, 'montant' => 195000]);
        ESBTPPaiementAllocation::create(['paiement_id' => $paiementId, 'frais_category_id' => $tenue->id, 'montant' => 60000]);

        $this->assertSame(195000.0, $this->netSur($inscription));
        $this->assertSame(60000.0, $this->netSur($tenue));
    }

    /**
     * Les deux sources ne doivent jamais se compter deux fois : le versement
     * reparti est lu par ses allocations, celui qui n'en a pas par sa categorie.
     */
    public function test_reparti_et_non_reparti_s_additionnent_sans_doublon(): void
    {
        $tenue = $this->frais('Tenue');

        $reparti = $this->versement(100000, $tenue->id);
        ESBTPPaiementAllocation::create(['paiement_id' => $reparti, 'frais_category_id' => $tenue->id, 'montant' => 40000]);

        $this->versement(25000, $tenue->id);

        // 40 000 (la part allouee, pas les 100 000) + 25 000.
        $this->assertSame(65000.0, $this->netSur($tenue));
    }

    public function test_les_versements_en_attente_sont_exclus_par_defaut(): void
    {
        $scolarite = $this->frais('Scolarité');
        $this->versement(150000, $scolarite->id);
        $this->versement(40000, $scolarite->id, statut: 'en_attente');

        $this->assertSame(150000.0, $this->netSur($scolarite));

        // Mais l'etat financier, lui, les compte : c'est ce que l'etudiant lit
        // sur sa propre fiche.
        $avecAttente = $this->regle->netParInscriptionEtFrais([$this->inscriptionId], inclurePending: true);
        $this->assertSame(190000.0, (float) $avecAttente[$this->inscriptionId][$scolarite->id]);
    }

    public function test_un_lot_vide_ne_declenche_aucune_requete(): void
    {
        $this->assertSame([], $this->regle->netParInscriptionEtFrais([]));
    }
}
