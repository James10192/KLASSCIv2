<?php

namespace Tests\Feature\Assistant;

use App\Domain\Assistant\Actions\Classes\AjouterClasses;
use App\Domain\Assistant\Actions\ContexteDEchange;
use App\Domain\Assistant\Actions\Lmd\LierUeAuxParcours;
use App\Domain\Assistant\Flux\UiMessageStream;
use App\Domain\Assistant\Fournisseurs\RequeteModele;
use App\Domain\Assistant\Harnais\BoucleAgent;
use App\Domain\Assistant\Harnais\ConstructeurDePrompt;
use App\Domain\Assistant\Modeles\ModeleIa;
use App\Domain\Assistant\Outils\CatalogueOutils;
use App\Domain\Assistant\Outils\ChercherDansPiece;
use App\Domain\Assistant\Pieces\LectureDePiece;
use App\Domain\Assistant\Pieces\PiecesJointes;
use App\Http\Middleware\CheckInstalled;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\PaywallMiddleware;
use App\Models\ChatbotConversation;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPUniteEnseignement;
use App\Models\User;
use App\Services\LMD\LMDImportService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Unit\Domain\Assistant\FauxFournisseur;

/**
 * Ce qui a été fait à la main le 1er octobre 2026, appris à Nanan : ajouter une
 * classe par filière et niveau (ISLG), retirer une UE d'un parcours (USAT), lire
 * un état d'arriérés réparti sur plusieurs feuilles et y chercher un matricule.
 */
class ActionsDuJourTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([PaywallMiddleware::class, EnsureInstalled::class, CheckInstalled::class]);
        Role::findOrCreate('superAdmin', 'web');
        foreach (['classes.create', 'lmd.structure.manage', 'lmd.structure.view', 'inscriptions.view'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->admin = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $this->admin->assignRole('superAdmin');

        ESBTPAnneeUniversitaire::query()->update(['is_current' => false]);
        ESBTPAnneeUniversitaire::factory()->create(['is_current' => true]);
        app(ContexteDEchange::class)->conversation = ChatbotConversation::create([
            'user_id' => $this->admin->id, 'session_id' => (string) Str::uuid(), 'last_activity_at' => now(),
        ]);
    }

    private function valider(array $resultat): void
    {
        $this->assertSame('approbation', $resultat['widget']['kind'] ?? null, json_encode($resultat, JSON_UNESCAPED_UNICODE));
        $this->actingAs($this->admin)
            ->postJson($resultat['widget']['valider_url'], ['jeton' => $resultat['widget']['jeton']])
            ->assertOk()->assertJson(['statut' => 'executee']);
    }

    // --- Lecture d'un classeur à plusieurs feuilles -------------------------------

    private function classeur(array $feuilles): UploadedFile
    {
        $classeur = new Spreadsheet();
        $classeur->removeSheetByIndex(0);
        foreach ($feuilles as $nom => $lignes) {
            $f = $classeur->createSheet();
            $f->setTitle($nom);
            $f->fromArray($lignes);
        }
        $chemin = tempnam(sys_get_temp_dir(), 'arr').'.xlsx';
        (new Xlsx($classeur))->save($chemin);

        return new UploadedFile($chemin, 'arrieres.xlsx', null, null, true);
    }

    public function test_un_etat_par_classe_se_lit_en_entier_avec_sa_feuille(): void
    {
        $tableau = app(LectureDePiece::class)->lire($this->classeur([
            'MGP L1' => [['CLASSE : L1MGP'], [], ['MATRICULE', 'NOM', 'RESTE'], ['GNAA0602080001', 'GNAKAN', '100.000 FCFA']],
            'DPP L1' => [['CLASSE : L1DPP'], ['MATRICULE', 'NOM', 'RESTE'], ['BROB1703050001', 'BROU', '100.000 FCFA']],
        ]));

        $this->assertSame(['Feuille', 'MATRICULE', 'NOM', 'RESTE'], $tableau['colonnes']);
        $this->assertCount(2, $tableau['lignes']);
        $this->assertSame(['DPP L1', 'BROB1703050001', 'BROU', '100.000 FCFA'], $tableau['lignes'][1]);
        $this->assertSame(['MGP L1', 'DPP L1'], $tableau['feuilles']);
    }

    public function test_des_feuilles_aux_entetes_differents_ne_se_melangent_pas(): void
    {
        $tableau = app(LectureDePiece::class)->lire($this->classeur([
            'Notes' => [['Matricule', 'Note'], ['M1', '12']],
            'Récap' => [['Classe', 'Moyenne'], ['L1', '11']],
        ]));

        $this->assertSame(['Matricule', 'Note'], $tableau['colonnes']);
        $this->assertCount(1, $tableau['lignes']);
        $this->assertSame(['Récap'], $tableau['autres_feuilles']);
    }

    public function test_chercher_dans_piece_dit_present_ou_absent_par_matricule(): void
    {
        $id = app(PiecesJointes::class)->garder($this->admin->id, 'arrieres.xlsx', [
            'colonnes' => ['Feuille', 'MATRICULE', 'RESTE'],
            'lignes' => [['DPP L1', 'BROB1703050001', '100.000 FCFA']],
            'tronque' => false,
        ]);

        $r = app(ChercherDansPiece::class)->executeAuthorized([
            'piece_id' => $id, 'colonne' => 'MATRICULE', 'valeurs' => ['brob 1703050001', 'BR001309060001'],
        ], $this->admin)['diagnostic']['resultats'];

        $this->assertTrue($r[0]['trouvee']);
        $this->assertSame('DPP L1', $r[0]['lignes'][0]['Feuille']);
        $this->assertFalse($r[1]['trouvee']);
    }

    public function test_une_piece_ne_se_lit_que_par_qui_l_a_deposee(): void
    {
        $id = app(PiecesJointes::class)->garder($this->admin->id, 'a.xlsx', ['colonnes' => ['M'], 'lignes' => [['1']], 'tronque' => false]);
        $autre = User::withoutEvents(fn () => User::factory()->create(['username' => 'u_'.Str::lower(Str::random(8))]));
        $autre->assignRole('superAdmin');

        $this->assertArrayHasKey('error', app(ChercherDansPiece::class)->executeAuthorized(['piece_id' => $id, 'colonne' => 'M', 'valeurs' => ['1']], $autre));
    }

    // --- UE et parcours (USAT) -----------------------------------------------------

    private function uePartagee(): ESBTPUniteEnseignement
    {
        $base = ['domaine' => ['name' => 'Sciences Agronomiques', 'code' => 'SAT'], 'mention' => ['name' => 'Productions', 'code' => 'PVAT'],
            'niveaux' => [['name' => 'Licence 2', 'year' => 2]]];
        foreach (['LPAT' => 'Productions Animales', 'LPVT' => 'Productions Végétales'] as $code => $nom) {
            app(LMDImportService::class)->import($base + [
                'parcours' => ['name' => $nom, 'code' => $code, 'credits_licence' => 180],
                'filiere' => ['name' => $nom, 'code' => $code],
                'ues' => [['code' => 'AGRT2103', 'name' => 'AMELIORATION GENETIQUE', 'type_ue' => 'fondamentale', 'credit' => 4, 'niveau_year' => 2, 'semestre' => 3, 'ecues' => []]],
            ]);
        }

        return ESBTPUniteEnseignement::where('code', 'AGRT2103')->sole();
    }

    public function test_retirer_une_ue_d_un_parcours_sans_toucher_l_autre(): void
    {
        $ue = $this->uePartagee();
        $lpa = ESBTPLMDParcours::where('code', 'LPAT')->value('id');
        $lpv = ESBTPLMDParcours::where('code', 'LPVT')->value('id');

        $codesSous = fn (int $parcours) => collect($this->actingAs($this->admin)
            ->getJson('/esbtp/lmd/ue?format=json&parcours_id='.$parcours)->assertOk()->json('ues'))->pluck('code')->all();
        $this->assertContains('AGRT2103', $codesSous($lpa), 'témoin : avant le retrait, elle est sous LPA');

        $resultat = app(LierUeAuxParcours::class)->executeAuthorized(['ue_code' => 'AGRT2103', 'retirer' => ['LPAT']], $this->admin);
        $this->assertSame(2, DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->count());
        $this->valider($resultat);

        $this->assertSame([(int) $lpv], DB::table('esbtp_lmd_parcours_ue')->where('unite_enseignement_id', $ue->id)->pluck('parcours_id')->map(fn ($v) => (int) $v)->all());

        // Et l'écran ne la montre plus sous LPA, même si sa colonne héritée nomme LPA.
        $ue->update(['parcours_id' => $lpa]);
        $this->assertNotContains('AGRT2103', $codesSous($lpa));
        $this->assertContains('AGRT2103', $codesSous($lpv));
    }

    public function test_retirer_un_parcours_non_lie_demande_une_precision(): void
    {
        $this->uePartagee();
        $r = app(LierUeAuxParcours::class)->executeAuthorized(['ue_code' => 'AGRT2103', 'retirer' => ['ZZZ']], $this->admin);

        $this->assertArrayNotHasKey('widget', $r);
        $this->assertNotEmpty($r['manques']);
    }

    // --- Classes (ISLG) ------------------------------------------------------------

    public function test_une_classe_de_plus_par_filiere_et_niveau_suit_les_noms_existants(): void
    {
        $gbat = ESBTPFiliere::factory()->create(['code' => 'GBATT']);
        $seit = ESBTPFiliere::factory()->create(['code' => 'SEIT']);
        $n1 = ESBTPNiveauEtude::factory()->create(['code' => 'BTS1T', 'year' => 1, 'type' => 'BTS']);
        foreach (['A', 'B', 'C'] as $l) {
            ESBTPClasse::factory()->create(['name' => "GBAT 1{$l}", 'code' => "1BTS_GBAT_1{$l}", 'filiere_id' => $gbat->id, 'niveau_etude_id' => $n1->id]);
        }

        $resultat = app(AjouterClasses::class)->executeAuthorized(['places' => 60, 'filieres' => ['GBATT', 'SEIT'], 'niveaux' => ['BTS1T']], $this->admin);
        $this->assertNotEmpty($resultat['avertissements'] ?? $resultat['widget']['avertissements'] ?? null, 'le couple sans classe doit etre signale');
        $this->assertSame(0, ESBTPClasse::where('code', '1BTS_GBAT_1D')->count());
        $this->valider($resultat);

        $nouvelle = ESBTPClasse::where('code', '1BTS_GBAT_1D')->sole();
        $this->assertSame('GBAT 1D', $nouvelle->name);
        $this->assertSame(60, (int) $nouvelle->places_totales);
        $this->assertSame('SEIT 1A', ESBTPClasse::where('filiere_id', $seit->id)->sole()->name);
    }

    public function test_sans_nombre_de_places_nanan_doit_le_demander(): void
    {
        $r = app(AjouterClasses::class)->executeAuthorized(['filieres' => [], 'niveaux' => []], $this->admin);

        $this->assertArrayNotHasKey('widget', $r);
        $this->assertStringContainsString('places', implode(' ', $r['manques']));
    }

    /**
     * Séance d'entraînement ISLG : la vraie boucle, le vrai catalogue, le vrai
     * prompt ; le modèle scripté suit le mode opératoire. Rien n'est créé avant
     * « Valider ».
     */
    public function test_seance_d_entrainement_islg(): void
    {
        $faux = new FauxFournisseur();
        $this->app->instance(FauxFournisseur::class, $faux);
        config(['assistant.adaptateurs.faux' => FauxFournisseur::class, 'assistant.limites.tours' => 3, 'assistant.limites.budget_tokens' => 0]);
        $f = ESBTPFiliere::factory()->create(['code' => 'FCGET']);
        ESBTPNiveauEtude::factory()->create(['code' => 'BTS2T', 'year' => 2, 'type' => 'BTS']);
        $faux->scripts['m'] = [
            FauxFournisseur::outil('t1', 'proposer_creation_classes', ['places' => 60, 'filieres' => ['FCGET'], 'niveaux' => ['BTS2T']]),
            FauxFournisseur::texte('Je propose une classe de 60 places : relisez puis validez.'),
        ];

        $catalogue = app(CatalogueOutils::class);
        $systeme = app(ConstructeurDePrompt::class)->systeme($this->admin, null, null);
        $this->assertStringContainsString('proposer_creation_classes', $systeme);
        $this->assertStringContainsString('chercher_dans_piece', $systeme);

        $r = (new BoucleAgent($catalogue))->executer(
            [new ModeleIa('m', 'faux', 'faux', 'm', 'M', true, true, 'cle', 'https://faux.test/')],
            new RequeteModele($systeme, [['role' => 'user', 'texte' => 'Ajoute une classe de 60 places à chaque filière et niveau']], $catalogue->schemas($this->admin)),
            $this->admin,
            new UiMessageStream(fn () => null),
        );

        $this->assertSame(['proposer_creation_classes'], array_column($r->appels, 'tool'));
        $this->assertSame(0, ESBTPClasse::where('filiere_id', $f->id)->count());
    }
}
