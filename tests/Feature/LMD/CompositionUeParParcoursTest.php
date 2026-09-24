<?php

namespace Tests\Feature\LMD;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\CompositionUe;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le code d'une unite d'enseignement est unique dans l'ecole : la meme unite
 * sert reellement plusieurs parcours. Sa composition, elle, peut differer de
 * l'un a l'autre.
 *
 * Ces tests tiennent la frontiere. Ils comptent parce que les primitives
 * d'Eloquent se trompent ici SANS lever d'exception : `syncWithoutDetaching`
 * retrouve la ligne par le seul `matiere_id` et reecrit la reservation d'un
 * autre parcours, `detach` supprime toutes les lignes de l'element, toutes
 * maquettes confondues. Un test qui passe est la seule preuve qu'on ne les
 * utilise plus.
 */
class CompositionUeParParcoursTest extends TestCase
{
    use RefreshDatabase;

    private CompositionUe $composition;

    private ESBTPUniteEnseignement $ue;

    private ESBTPMatiere $ecue;

    private ESBTPLMDParcours $batiment;

    private ESBTPLMDParcours $travauxPublics;

    protected function setUp(): void
    {
        parent::setUp();

        // L'import exige une annee courante (LMDImportService:49).
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);

        $this->composition = app(CompositionUe::class);

        // Pas de factory pour ces modeles : on batit la maquette par l'import,
        // comme le fait deja SuppressionUePartageeTest. Plus fidele, en prime :
        // c'est le chemin par lequel les vraies maquettes arrivent.
        app(LMDImportService::class)->import($this->maquette('BU', 'Batiment', 'UE-PARTAGEE', 'ECUE-BU'));
        app(LMDImportService::class)->import($this->maquette('TIR', 'Travaux Publics', 'UE-TIR', 'ECUE-TIR'));

        $this->ue = ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail();
        $this->ecue = ESBTPMatiere::where('code', 'ECUE-BU')->firstOrFail();
        $this->batiment = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $this->travauxPublics = ESBTPLMDParcours::where('code', 'TIR')->firstOrFail();

        // Le geste du modal « Lier a des parcours » : l'unite de Batiment entre
        // aussi dans la maquette de Travaux Publics.
        DB::table('esbtp_lmd_parcours_ue')->insert([
            'parcours_id' => $this->travauxPublics->id,
            'unite_enseignement_id' => $this->ue->id,
            'semestre' => 1,
            'is_optional' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // On NE nettoie PAS ce que l'import a ecrit.
        //
        // Une premiere version de ces tests vidait le pivot et remettait la cle
        // etrangere a nul avant chaque cas. C'etait effacer precisement l'etat
        // qui produit le defaut : en production l'import pose TOUJOURS les deux,
        // et c'est cette coexistence qui faisait reapparaitre par le repli ce que
        // le filtre venait d'ecarter. La suite passait donc en etant fausse.
    }

    /**
     * Les elements que CETTE maquette voit, par leur code.
     *
     * @return array<int, string>
     */
    private function vusPar(ESBTPLMDParcours $parcours): array
    {
        return $this->ue->fresh()->getEcuesEffectifs($parcours->id)->pluck('code')->all();
    }

    /**
     * Une maquette minimale : un domaine, une mention, un parcours, une UE, un ECUE.
     */
    private function maquette(string $codeParcours, string $nomParcours, string $codeUe, string $codeEcue): array
    {
        return [
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => $nomParcours, 'code' => $codeParcours, 'credits_licence' => 180],
            'filiere' => ['name' => $nomParcours, 'code' => 'F'.$codeParcours],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => $codeUe,
                'name' => 'Unite '.$codeUe,
                'credit' => 12,
                'niveau_year' => 1,
                'semestre' => 1,
                'ecues' => [[
                    'code' => $codeEcue,
                    'name' => 'Matiere '.$codeEcue,
                    'credit_ecue' => 3,
                ]],
            ]],
        ];
    }

    private function lignes(?int $parcoursId = null): int
    {
        $requete = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $this->ue->id)
            ->where('matiere_id', $this->ecue->id);

        if ($parcoursId !== null) {
            $requete->where('parcours_id', $parcoursId);
        }

        return $requete->count();
    }

    public function test_deux_maquettes_importees_sur_la_meme_unite_restent_etanches(): void
    {
        // LE test du chantier, et celui qui manquait. Aucune cle etrangere n'est
        // touchee : on lit l'etat exact que l'import produit.
        //
        // L'import de TIR pose son element sur l'unite partagee, en le reservant
        // a TIR. Vu depuis Batiment, cet element ne doit pas exister.
        app(LMDImportService::class)->import([
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => 'Travaux Publics', 'code' => 'TIR', 'credits_licence' => 180],
            'filiere' => ['name' => 'Travaux Publics', 'code' => 'FTIR'],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => 'UE-PARTAGEE',
                'name' => 'Unite UE-PARTAGEE',
                'credit' => 12,
                'niveau_year' => 1,
                'semestre' => 1,
                'ecues' => [['code' => 'ECUE-RESERVE-TIR', 'name' => 'Reserve TIR', 'credit_ecue' => 3]],
            ]],
        ]);

        $vusParBatiment = $this->vusPar($this->batiment);

        $this->assertContains('ECUE-BU', $vusParBatiment, 'Batiment doit garder son propre element.');
        $this->assertNotContains(
            'ECUE-RESERVE-TIR',
            $vusParBatiment,
            "L'element reserve a Travaux Publics ne doit pas entrer dans le bulletin des etudiants de Batiment."
        );

        $vusParTir = $this->vusPar($this->travauxPublics);
        $this->assertContains('ECUE-RESERVE-TIR', $vusParTir, 'Travaux Publics doit voir le sien.');
    }

    public function test_un_element_n_est_jamais_rendu_deux_fois(): void
    {
        // Le repli sur la cle etrangere et le pivot decrivent le meme element :
        // le compter deux fois doublerait sa note au numerateur, son coefficient
        // au denominateur et son credit. La moyenne resterait juste par
        // compensation, les credits non.
        $codes = $this->vusPar($this->batiment);

        $this->assertSame(
            count($codes),
            count(array_unique($codes)),
            'Un element remonte deux fois : '.implode(', ', $codes)
        );
    }

    public function test_une_reservation_et_la_composition_commune_coexistent(): void
    {
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 3]);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 5], $this->batiment->id);

        $this->assertSame(1, $this->lignes(CompositionUe::COMMUN));
        $this->assertSame(1, $this->lignes($this->batiment->id));
        $this->assertSame(0, $this->lignes($this->travauxPublics->id));
    }

    public function test_poser_le_commun_ne_reecrit_pas_une_reservation(): void
    {
        // La regression que `syncWithoutDetaching` produisait : il retrouvait la
        // ligne par le seul `matiere_id` et faisait un UPDATE dessus. L'element
        // reserve a Batiment changeait donc de maquette, en silence.
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 5], $this->batiment->id);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 3]);

        $reserve = DB::table('esbtp_ue_matiere')
            ->where('unite_enseignement_id', $this->ue->id)
            ->where('matiere_id', $this->ecue->id)
            ->where('parcours_id', $this->batiment->id)
            ->first();

        $this->assertNotNull($reserve, 'La reservation doit survivre.');
        $this->assertSame(5, (int) $reserve->credit_ecue, 'Son credit ne doit pas avoir bouge.');
    }

    public function test_retirer_la_derniere_ligne_commune_ne_la_fait_pas_revenir(): void
    {
        // USAT, AGR2103 : un element reserve a une maquette ET pose en commun.
        // L'ecole retire la ligne commune. La liberation de la cle etrangere
        // materialisait alors la composition commune a partir de la cle
        // etrangere, et regravait l'element en commun : il revenait chez
        // l'autre parcours aussitot retire.
        $this->composition->poser($this->ue, (int) $this->ecue->id, ['coefficient_ecue' => 1, 'credit_ecue' => 3, 'ordre_bulletin' => 0]);
        $this->assertContains('ECUE-BU', $this->vusPar($this->travauxPublics));

        $this->composition->retirer($this->ue, [(int) $this->ecue->id], CompositionUe::COMMUN);
        $this->composition->libererCleEtrangere($this->ue, [(int) $this->ecue->id]);

        $this->assertSame(0, $this->lignes(CompositionUe::COMMUN), 'La ligne commune ne doit pas revenir.');
        $this->assertNotContains('ECUE-BU', $this->vusPar($this->travauxPublics));
        $this->assertContains('ECUE-BU', $this->vusPar($this->batiment), 'La reservation de Batiment reste.');
    }

    public function test_retirer_d_une_maquette_ne_touche_pas_les_autres(): void
    {
        // `detach($id)` supprimait toutes les lignes de cet element : retirer
        // l'element de Batiment le retirait aussi de Travaux Publics.
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 3]);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 5], $this->batiment->id);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 4], $this->travauxPublics->id);

        $this->composition->retirer($this->ue, [$this->ecue->id], $this->batiment->id);

        $this->assertSame(0, $this->lignes($this->batiment->id));
        $this->assertSame(1, $this->lignes($this->travauxPublics->id));
        $this->assertSame(1, $this->lignes(CompositionUe::COMMUN));
    }

    public function test_la_cle_etrangere_ne_se_libere_que_si_plus_aucune_maquette_ne_tient_l_element(): void
    {
        // La cle etrangere est GLOBALE, et c'est le discriminateur BTS/LMD que
        // vingt ecrans interrogent. La vider parce qu'un seul parcours a retire
        // l'element le verserait au catalogue BTS.
        $this->ecue->update(['unite_enseignement_id' => $this->ue->id]);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 5], $this->batiment->id);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 4], $this->travauxPublics->id);

        $this->composition->retirer($this->ue, [$this->ecue->id], $this->batiment->id);
        $this->composition->libererCleEtrangere($this->ue, [$this->ecue->id]);

        $this->assertSame(
            $this->ue->id,
            (int) $this->ecue->fresh()->unite_enseignement_id,
            'Travaux Publics tient encore l element : la cle ne doit pas etre coupee.'
        );

        $this->composition->retirer($this->ue, [$this->ecue->id], $this->travauxPublics->id);
        $this->composition->libererCleEtrangere($this->ue, [$this->ecue->id]);

        $this->assertNull(
            $this->ecue->fresh()->unite_enseignement_id,
            'Plus aucune maquette ne le tient : la cle se libere.'
        );
    }

    public function test_la_lecture_donne_le_reserve_et_non_le_commun(): void
    {
        // Sans cette regle, les deux lignes remonteraient et l'element serait
        // compte DEUX fois au bulletin : note doublee, coefficient doublee,
        // credit doublee. La moyenne resterait juste par compensation, les
        // credits non — et deux coefficients differents la fausseraient aussi.
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 3, 'coefficient_ecue' => 1]);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 5, 'coefficient_ecue' => 2], $this->batiment->id);

        $vus = $this->ue->fresh()->getEcuesEffectifs($this->batiment->id);

        $this->assertCount(1, $vus, 'Un element, une seule fois.');
        $this->assertSame(5, (int) $vus->first()->pivot->credit_ecue, 'Le reserve prime sur le commun.');
    }

    public function test_la_lecture_d_un_parcours_ignore_ce_qui_est_reserve_a_un_autre(): void
    {
        // Cet element-ci vient d'une AUTRE unite : sa cle ne designe pas la notre,
        // le repli ne le reprendra donc pas. On la coupe pour que le cas ne teste
        // que le pivot.
        $autre = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $autre->update(['unite_enseignement_id' => null]);

        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 3]);
        $this->composition->poser($this->ue, $autre->id, ['credit_ecue' => 4], $this->travauxPublics->id);

        $vus = $this->ue->fresh()->getEcuesEffectifs($this->batiment->id)->pluck('id')->all();

        $this->assertContains($this->ecue->id, $vus, 'Le commun vaut pour toutes les maquettes.');
        $this->assertNotContains($autre->id, $vus, "Ce que Travaux Publics reserve n'est pas a Batiment.");
    }

    public function test_une_portee_qui_ne_concerne_pas_l_unite_est_refusee(): void
    {
        // Retomber silencieusement sur « commun » ferait ecrire dans la
        // composition partagee par TOUTES les maquettes : le plus large rayon
        // d'action atteint par la plus petite faute de frappe. On refuse, et on
        // dit ou aller corriger.
        app(LMDImportService::class)->import($this->maquette('EXT', 'Exterieur', 'UE-EXT', 'ECUE-EXT'));
        $etranger = ESBTPLMDParcours::where('code', 'EXT')->firstOrFail();

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->composition->porteeValide($this->ue, $etranger->id);
    }

    public function test_une_portee_absente_vaut_la_composition_commune(): void
    {
        $this->assertSame(CompositionUe::COMMUN, $this->composition->porteeValide($this->ue, ''));
        $this->assertSame(CompositionUe::COMMUN, $this->composition->porteeValide($this->ue, null));
        $this->assertSame(CompositionUe::COMMUN, $this->composition->porteeValide($this->ue, 0));
        $this->assertSame(
            $this->batiment->id,
            $this->composition->porteeValide($this->ue, $this->batiment->id),
            'Un parcours rattache a l unite est accepte tel quel.'
        );
    }

    public function test_le_plafond_de_credits_compte_l_union_dedupliquee(): void
    {
        // Ne compter que les reservees laissait reserver a l'infini sur une unite
        // deja pourvue en commun : le plafond ne mordait jamais, et l'invariant
        // UEMOA des trente credits par semestre devenait franchissable en silence.
        $autre = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $autre->update(['unite_enseignement_id' => null]);

        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 8]);
        $this->composition->poser($this->ue, $autre->id, ['credit_ecue' => 3], $this->batiment->id);

        $this->assertSame(
            11,
            $this->composition->creditsDe($this->ue, $this->batiment->id),
            'Le commun ET le reserve comptent, chacun une fois.'
        );

        // La reservee ecrase la commune pour le meme element, elle ne s'y ajoute pas.
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 5], $this->batiment->id);

        $this->assertSame(
            8,
            $this->composition->creditsDe($this->ue, $this->batiment->id),
            'Le reserve remplace le commun : 5 + 3, et non 8 + 5 + 3.'
        );
    }

    public function test_la_materialisation_reprend_la_cle_etrangere_en_commun(): void
    {
        // Un element tenu par la SEULE cle etrangere : la lecture le montre comme
        // commun, la materialisation le grave donc en commun.
        DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $this->ue->id)->delete();
        $this->ecue->update(['unite_enseignement_id' => $this->ue->id, 'credit_ecue' => 3]);

        $this->composition->materialiserDepuisCleEtrangere($this->ue->id);

        $this->assertSame(1, $this->lignes(CompositionUe::COMMUN));
    }

    public function test_la_materialisation_ne_rend_pas_commun_un_element_reserve(): void
    {
        // L'import a reserve l'element a Batiment ET pose sa cle etrangere. La
        // lecture ne le montre qu'a Batiment : le graver en commun le montrerait
        // a Travaux Publics.
        $this->composition->materialiserDepuisCleEtrangere($this->ue->id);

        $this->assertSame(0, $this->lignes(CompositionUe::COMMUN));
        $this->assertNotContains('ECUE-BU', $this->vusPar($this->travauxPublics));
    }

    public function test_la_materialisation_reste_possible_malgre_une_reservation(): void
    {
        // La garde testait l'existence de N'IMPORTE QUELLE ligne : un seul
        // element reserve rendait la materialisation impossible, et laissait
        // l'unite exposee au depouillement qu'elle previent.
        $autre = ESBTPMatiere::where('code', 'ECUE-TIR')->firstOrFail();
        $autre->update(['unite_enseignement_id' => $this->ue->id, 'credit_ecue' => 2]);
        $this->composition->poser($this->ue, $this->ecue->id, ['credit_ecue' => 5], $this->batiment->id);

        $this->composition->materialiserDepuisCleEtrangere($this->ue->id);

        $this->assertSame(
            1,
            DB::table('esbtp_ue_matiere')
                ->where('unite_enseignement_id', $this->ue->id)
                ->where('matiere_id', $autre->id)
                ->where('parcours_id', CompositionUe::COMMUN)
                ->count(),
            'La composition commune doit avoir ete gravee malgre la reservation.'
        );
    }
}
