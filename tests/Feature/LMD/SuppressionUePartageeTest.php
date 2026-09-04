<?php

namespace Tests\Feature\LMD;

use App\Helpers\SettingsHelper;
use App\Http\Controllers\ESBTPLMDUEController;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use App\Services\LMD\LMDCleanupService;
use App\Services\LMD\LMDImportService;
use App\Services\LMD\SuppressionUeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Supprimer une unite d'enseignement ne doit pas depouiller un parcours voisin.
 *
 * Le code d'une UE est unique dans l'ecole : la meme unite sert plusieurs
 * parcours. La suppression n'etait gardee que par l'existence de resultats LMD,
 * donc une maquette saisie et pas encore notee — l'etat exact d'une maquette en
 * cours de saisie — partait sans un mot, pour TOUS les parcours a la fois. Ses
 * elements constitutifs etaient de surcroit reverses au catalogue BTS, ou une
 * vingtaine d'ecrans en service les auraient affiches.
 *
 * Les cas ci-dessous verifient le refus, le nettoyage des liens qu'aucune
 * cascade ne touche (l'unite etant en suppression douce), et le reglage qui
 * decide du sort des elements constitutifs.
 */
class SuppressionUePartageeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // L'import exige une annee courante (LMDImportService:49).
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
    }

    public function test_supprimer_une_unite_partagee_est_refuse_et_nomme_les_maquettes(): void
    {
        [$ue, , $tir] = $this->maquetteAvecUnitePartagee();

        $refus = app(SuppressionUeService::class)->refusSiPartagee($ue);

        $this->assertNotNull($refus, 'Une unite partagee par deux parcours a pu etre supprimee.');
        $this->assertStringContainsString($tir->name, $refus);
        $this->assertStringContainsString('2 maquettes', $refus);
    }

    public function test_le_controleur_refuse_sans_rien_ecrire(): void
    {
        [$ue, , ] = $this->maquetteAvecUnitePartagee();

        $ecue = ESBTPMatiere::where('unite_enseignement_id', $ue->id)->firstOrFail();

        $requete = Request::create('/esbtp/lmd/ue/'.$ue->id, 'DELETE');
        $requete->headers->set('Accept', 'application/json');

        $reponse = app(ESBTPLMDUEController::class)->destroy($requete, $ue);

        $this->assertSame(422, $reponse->getStatusCode());

        // Le point qui compte : rien n'a bouge.
        $this->assertNotNull(ESBTPUniteEnseignement::find($ue->id), "L'unite a ete supprimee malgre le refus.");
        $this->assertSame(
            (int) $ue->id,
            (int) ESBTPMatiere::findOrFail($ecue->id)->unite_enseignement_id,
            "L'element constitutif a ete reverse au catalogue BTS malgre le refus."
        );
    }

    public function test_supprimer_une_unite_exclusive_nettoie_les_liens_orphelins(): void
    {
        $ue = $this->uniteExclusive();
        $ecue = ESBTPMatiere::where('unite_enseignement_id', $ue->id)->firstOrFail();

        // L'import ne renseigne que la cle etrangere : on materialise le lien de
        // pivot, qui est celui qui survivait a la suppression.
        DB::table('esbtp_ue_matiere')->insert([
            'unite_enseignement_id' => $ue->id,
            'matiere_id' => $ecue->id,
            'ordre_bulletin' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(SuppressionUeService::class)->supprimer($ue);

        $this->assertNull(ESBTPUniteEnseignement::find($ue->id));
        $this->assertSame(0, DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id)->count());
        $this->assertSame(0, DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->count());
    }

    public function test_le_reglage_retient_les_elements_constitutifs_dans_le_lmd(): void
    {
        $ue = $this->uniteExclusive();
        $ecue = ESBTPMatiere::where('unite_enseignement_id', $ue->id)->firstOrFail();

        // Defaut : le comportement d'avant, l'element repasse au catalogue BTS.
        app(SuppressionUeService::class)->supprimer($ue);
        $this->assertNull(ESBTPMatiere::findOrFail($ecue->id)->unite_enseignement_id);

        // Reglage coupe : une ecole tout-LMD garde ses elements du cote LMD.
        $autre = $this->uniteExclusive('TIR2', 'Travaux Publics 2', 'UE-EXCLUSIVE-2', 'ECUE-EXCLUSIF-2');
        $autreEcue = ESBTPMatiere::where('unite_enseignement_id', $autre->id)->firstOrFail();

        SettingsHelper::setOrCreate(SuppressionUeService::REGLAGE_LIBERER_ECUES, '0', 'lmd', 'boolean');
        \Illuminate\Support\Facades\Cache::forget('setting_'.SuppressionUeService::REGLAGE_LIBERER_ECUES);

        app(SuppressionUeService::class)->supprimer($autre);

        $this->assertSame(
            (int) $autre->id,
            (int) ESBTPMatiere::findOrFail($autreEcue->id)->unite_enseignement_id,
            "Le reglage coupe, l'element constitutif ne doit pas rejoindre le catalogue BTS."
        );
    }

    public function test_le_nettoyage_de_parcours_conserve_une_unite_partagee(): void
    {
        [$ue, , $tir] = $this->maquetteAvecUnitePartagee();
        $ecue = ESBTPMatiere::where('unite_enseignement_id', $ue->id)->firstOrFail();

        $resultat = app(LMDCleanupService::class)->cleanupParcours([$tir->code], dryRun: false);

        // Une seule des deux unites de TIR est partagee : l'autre lui appartient
        // en propre et part normalement.
        $this->assertSame(1, $resultat['totals']['ues_partagees']);
        $this->assertNull(ESBTPUniteEnseignement::where('code', 'UE-TIR')->first());

        // L'unite partagee et son element constitutif restent : seul le lien du
        // parcours nettoye disparait.
        $this->assertNotNull(ESBTPUniteEnseignement::find($ue->id));
        $this->assertNotNull(ESBTPMatiere::find($ecue->id));
        $this->assertSame(0, DB::table('esbtp_lmd_parcours_ue')
            ->where('parcours_id', $tir->id)->where('unite_enseignement_id', $ue->id)->count());
    }

    /**
     * Une unite qu'un second parcours utilise aussi.
     *
     * @return array{0: ESBTPUniteEnseignement, 1: ESBTPLMDParcours, 2: ESBTPLMDParcours}
     */
    private function maquetteAvecUnitePartagee(): array
    {
        app(LMDImportService::class)->import($this->maquette('BU', 'Batiment', 'UE-PARTAGEE', 'ECUE-BU'));
        app(LMDImportService::class)->import($this->maquette('TIR', 'Travaux Publics', 'UE-TIR', 'ECUE-TIR'));

        $ue = ESBTPUniteEnseignement::where('code', 'UE-PARTAGEE')->firstOrFail();
        $bu = ESBTPLMDParcours::where('code', 'BU')->firstOrFail();
        $tir = ESBTPLMDParcours::where('code', 'TIR')->firstOrFail();

        // Le geste du modal « Lier a des parcours » : l'unite de BU entre dans
        // la maquette de TIR.
        DB::table('esbtp_lmd_parcours_ue')->insert([
            'parcours_id' => $tir->id,
            'unite_enseignement_id' => $ue->id,
            'semestre' => 1,
            'is_optional' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$ue->fresh(), $bu, $tir];
    }

    private function uniteExclusive(
        string $codeParcours = 'EXC',
        string $nomParcours = 'Parcours exclusif',
        string $codeUe = 'UE-EXCLUSIVE',
        string $codeEcue = 'ECUE-EXCLUSIF'
    ): ESBTPUniteEnseignement {
        app(LMDImportService::class)->import($this->maquette($codeParcours, $nomParcours, $codeUe, $codeEcue));

        return ESBTPUniteEnseignement::where('code', $codeUe)->firstOrFail();
    }

    /**
     * Une maquette minimale : un domaine, une mention, un parcours, une UE, un ECUE.
     */
    private function maquette(
        string $codeParcours,
        string $nomParcours,
        string $codeUe,
        string $codeEcue
    ): array {
        return [
            'domaine' => ['name' => 'Sciences et Technologies', 'code' => 'ST'],
            'mention' => ['name' => 'Genie Civil', 'code' => 'GC'],
            'parcours' => ['name' => $nomParcours, 'code' => $codeParcours, 'credits_licence' => 180],
            'filiere' => ['name' => $nomParcours, 'code' => 'F'.$codeParcours],
            'niveaux' => [['name' => 'Licence 1', 'year' => 1]],
            'ues' => [[
                'code' => $codeUe,
                'name' => 'Unite '.$codeUe,
                'credit' => 6,
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
}
