<?php

namespace Tests\Feature\PiecesDossier;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPieceDossier;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * L'écran de configuration du catalogue des pièces à fournir.
 *
 * Ces tests n'ont PAS été exécutés : la base locale est hors service au moment
 * où ils sont écrits (dépendances incomplètes, l'application ne démarre pas).
 * Ils valent comme spécification exécutable, à jouer avant toute fusion.
 */
class CataloguePiecesDossierTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckInstalled::class,
            \App\Http\Middleware\EnsureInstalled::class,
            \App\Http\Middleware\PaywallMiddleware::class,
        ]);

        Permission::findOrCreate('pieces_dossier.view', 'web');
        Permission::findOrCreate('pieces_dossier.configure', 'web');

        Cache::flush();
    }

    private function utilisateur(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    /** Corps minimal accepté par la validation, à compléter au cas par cas. */
    private function corpsValide(array $surcharges = []): array
    {
        return array_merge([
            'libelle' => 'Extrait de naissance',
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'exemplaires_par_inscription' => 1,
            'echeance' => 'inscription',
            'appartenance' => 'etudiant',
            'is_active' => true,
        ], $surcharges);
    }

    /** Pièce créée directement en base, sans passer par l'écran. */
    private function piece(array $surcharges = []): ESBTPPieceDossier
    {
        return ESBTPPieceDossier::create(array_merge([
            'code' => 'extrait_naissance',
            'libelle' => 'Extrait de naissance',
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'exemplaires_par_inscription' => 1,
            'echeance' => 'inscription',
            'appartenance' => 'etudiant',
            'is_active' => true,
            'ordre' => 10,
        ], $surcharges));
    }

    /**
     * La régression que ce lot corrige.
     *
     * L'essai précédent avait posé ces routes dans le groupe des paramètres
     * d'établissement, qui exige d'abord `admin.access|identity.*`. Un rôle sur
     * mesure ne portant que la permission du catalogue y prenait un 403 muet :
     * la permission promise ne suffisait jamais, et cela ne marchait que par
     * accident, parce que les rôles livrés portaient l'une de ces identités.
     */
    public function test_la_permission_de_lecture_suffit_seule_a_ouvrir_l_ecran(): void
    {
        $this->actingAs($this->utilisateur(['pieces_dossier.view']))
            ->get(route('esbtp.pieces-dossier.index'))
            ->assertOk()
            ->assertViewIs('esbtp.pieces-dossier.index');
    }

    public function test_sans_permission_l_ecran_est_refuse(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('esbtp.pieces-dossier.index'))
            ->assertForbidden();
    }

    /**
     * Lire n'est pas configurer : un agent de guichet doit pouvoir consulter la
     * liste sans pouvoir la changer pour toute l'école.
     */
    public function test_lire_ne_donne_pas_le_droit_de_configurer(): void
    {
        $this->actingAs($this->utilisateur(['pieces_dossier.view']))
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide())
            ->assertForbidden();
    }

    public function test_une_piece_se_cree_avec_sa_portee(): void
    {
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $reponse = $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'libelle' => "Photo d'identité",
                'description' => 'Fond uni.',
                'forme_attendue' => 'original',
                'exemplaires_par_inscription' => 2,
                'filiere_ids' => [$filiere->id],
                'niveau_ids' => [$niveau->id],
            ]))
            ->assertCreated();

        $piece = ESBTPPieceDossier::findOrFail($reponse->json('piece.id'));

        // Le code n'est pas saisi au guichet : il est dérivé du libellé. Str::slug
        // SUPPRIME l'apostrophe au lieu de la remplacer par le séparateur — d'où
        // « photo_didentite » et non « photo_d_identite ». Cette attente-là avait
        // été écrite de mémoire, et elle était fausse.
        $this->assertSame('photo_didentite', $piece->code);
        $this->assertSame(2, $piece->exemplaires_par_inscription);
        $this->assertSame([$filiere->id], $piece->filieres()->pluck('esbtp_filieres.id')->all());
        $this->assertSame([$niveau->id], $piece->niveaux()->pluck('esbtp_niveau_etudes.id')->all());
    }

    /**
     * Une portée vide n'est pas une portée nulle déguisée : elle veut dire
     * « tout le monde », et se lit comme telle sans aucune ligne de pivot.
     */
    public function test_une_piece_sans_portee_vaut_pour_tout_le_monde(): void
    {
        $reponse = $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide())
            ->assertCreated();

        $piece = ESBTPPieceDossier::with(['filieres', 'niveaux'])->findOrFail($reponse->json('piece.id'));

        $this->assertTrue($piece->concerneToutesFilieres());
        $this->assertTrue($piece->concerneTousNiveaux());
        $this->assertSame('Toutes filières / Tous niveaux', $reponse->json('piece.libelle_scope'));
    }

    /**
     * L'école dit, pièce par pièce, si le dépôt dure ou se redonne chaque
     * année. C'est la question qui décide de ce qu'un étudiant réapporte à
     * chaque rentrée, et elle ne peut donc pas être laissée au logiciel.
     */
    public function test_l_appartenance_est_enregistree_telle_que_l_ecole_la_choisit(): void
    {
        $utilisateur = $this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']);

        $dure = $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'libelle' => 'Extrait de naissance',
                'appartenance' => 'etudiant',
            ]))
            ->assertCreated();

        $annuelle = $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'libelle' => 'Certificat medical',
                'appartenance' => 'inscription',
            ]))
            ->assertCreated();

        $this->assertSame('etudiant', $dure->json('piece.appartenance'));
        $this->assertSame('inscription', $annuelle->json('piece.appartenance'));

        $this->assertTrue(
            ESBTPPieceDossier::findOrFail($dure->json('piece.id'))->appartenance->seReporte()
        );
        $this->assertFalse(
            ESBTPPieceDossier::findOrFail($annuelle->json('piece.id'))->appartenance->seReporte()
        );
    }

    public function test_une_appartenance_inconnue_est_refusee(): void
    {
        $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide(['appartenance' => 'annuelle']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('appartenance');
    }

    /**
     * Le piège du zéro, celui-là même que `rien-en-dur.md` documente.
     *
     * « Ne périme jamais » s'écrit en laissant le champ VIDE. Un zéro se lit
     * « valide zéro mois », donc périmée à l'instant du dépôt : l'exact
     * contraire de ce que voulait dire qui l'aurait saisi. La validation le
     * refuse plutôt que de deviner.
     */
    public function test_une_duree_de_validite_nulle_veut_dire_jamais_et_zero_est_refuse(): void
    {
        $utilisateur = $this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']);

        $jamais = $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'duree_validite_mois' => null,
            ]))
            ->assertCreated();

        $this->assertNull($jamais->json('piece.duree_validite_mois'));
        $this->assertNull(ESBTPPieceDossier::findOrFail($jamais->json('piece.id'))->duree_validite_mois);

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'libelle' => 'Certificat medical',
                'duree_validite_mois' => 0,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('duree_validite_mois');

        $trois = $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'libelle' => 'Certificat medical',
                'duree_validite_mois' => 3,
            ]))
            ->assertCreated();

        $this->assertSame(3, $trois->json('piece.duree_validite_mois'));
    }

    /**
     * Renommer une pièce ne doit pas rompre l'historique : le code est le point
     * d'ancrage des dépôts déjà saisis.
     */
    public function test_modifier_une_piece_ne_change_jamais_son_code(): void
    {
        $piece = $this->piece();

        $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->putJson(route('esbtp.pieces-dossier.update', $piece), $this->corpsValide([
                'libelle' => "Extrait d'acte de naissance",
                'is_obligatoire' => false,
                'forme_attendue' => 'indifferent',
                'exemplaires_par_inscription' => 3,
                'echeance' => 'avant_fin_annee',
            ]))
            ->assertOk();

        $piece->refresh();

        $this->assertSame('extrait_naissance', $piece->code);
        $this->assertSame("Extrait d'acte de naissance", $piece->libelle);
        $this->assertFalse($piece->is_obligatoire);
    }

    /**
     * Retirer une pièce l'archive. Elle disparaît du catalogue sans emporter
     * les dossiers déjà constitués, qui continuent de la citer.
     */
    public function test_retirer_une_piece_l_archive_sans_la_detruire(): void
    {
        $piece = $this->piece([
            'code' => 'certificat_scolarite',
            'libelle' => 'Certificat de scolarite',
            'is_obligatoire' => false,
        ]);

        $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->deleteJson(route('esbtp.pieces-dossier.destroy', $piece))
            ->assertOk();

        $this->assertSoftDeleted('esbtp_pieces_dossier', ['id' => $piece->id]);
        $this->assertTrue(ESBTPPieceDossier::withTrashed()->whereKey($piece->id)->exists());
    }

    /**
     * Le jeu proposé s'installe d'un clic, et un second clic ne rend pas au
     * secrétariat une pièce qu'il venait de retirer.
     */
    public function test_le_jeu_propose_s_installe_une_seule_fois(): void
    {
        $utilisateur = $this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']);
        $attendues = count(config('pieces_dossier.jeu_propose'));

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.jeu-propose'))
            ->assertOk()
            ->assertJsonCount($attendues, 'pieces');

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.jeu-propose'))
            ->assertOk()
            ->assertJsonCount($attendues, 'pieces');

        $this->assertSame($attendues, ESBTPPieceDossier::count());
    }

    /**
     * L'impasse que ce correctif ferme.
     *
     * On installe le jeu, on retire tout, l'écran vide revient avec son bouton,
     * on reclique — et l'école repartait avec un 200, un message annonçant que
     * « le catalogue contient déjà toutes les pièces proposées », et un écran
     * toujours vide. Aucune erreur, aucune trace, aucun moyen de s'en sortir.
     */
    public function test_apres_avoir_tout_retire_le_bouton_rend_le_jeu_propose(): void
    {
        $utilisateur = $this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']);
        $attendues = count(config('pieces_dossier.jeu_propose'));

        $this->actingAs($utilisateur)->postJson(route('esbtp.pieces-dossier.jeu-propose'))->assertOk();

        foreach (ESBTPPieceDossier::all() as $piece) {
            $this->actingAs($utilisateur)
                ->deleteJson(route('esbtp.pieces-dossier.destroy', $piece))
                ->assertOk();
        }

        $this->assertSame(0, ESBTPPieceDossier::count());

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.jeu-propose'))
            ->assertOk()
            ->assertJsonCount($attendues, 'pieces');

        $this->assertSame($attendues, ESBTPPieceDossier::count());
        // Restaurées, et non dupliquées : l'index unique du code voit les pièces
        // archivées, et une seconde création se serait brisée dessus.
        $this->assertSame($attendues, ESBTPPieceDossier::withTrashed()->count());
    }

    /**
     * Une pièce retirée puis rendue revient telle que l'école l'avait laissée.
     * Le libellé qu'elle avait corrigé n'est pas remplacé par celui du jeu.
     */
    public function test_une_piece_rendue_garde_le_libelle_corrige_par_l_ecole(): void
    {
        $premier = config('pieces_dossier.jeu_propose.0');

        $piece = $this->piece([
            'code' => $premier['code'],
            'libelle' => 'Libelle maison',
        ]);
        $piece->delete();

        $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->postJson(route('esbtp.pieces-dossier.jeu-propose'))
            ->assertOk();

        $piece->refresh();

        $this->assertNull($piece->deleted_at);
        $this->assertTrue($piece->is_active);
        $this->assertSame('Libelle maison', $piece->libelle);
    }

    public function test_le_reordonnancement_reecrit_l_ordre_complet(): void
    {
        $utilisateur = $this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']);

        $premiere = $this->piece(['code' => 'a', 'libelle' => 'A', 'ordre' => 10]);
        $seconde = $this->piece(['code' => 'b', 'libelle' => 'B', 'ordre' => 20]);

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.reorder'), ['ids' => [$seconde->id, $premiere->id]])
            ->assertOk();

        $this->assertSame(10, $seconde->refresh()->ordre);
        $this->assertSame(20, $premiere->refresh()->ordre);
    }

    /**
     * Le plafond d'exemplaires est un réglage d'école, pas une constante : le
     * changer dans `settings` doit changer ce que la validation accepte.
     */
    public function test_le_plafond_d_exemplaires_suit_le_reglage_de_l_ecole(): void
    {
        Setting::updateOrCreate(
            ['key' => 'pieces_dossier.exemplaires_max'],
            ['value' => '3', 'type' => 'integer', 'group' => 'scolarite', 'category' => 'scolarite', 'is_active' => true]
        );
        Cache::flush();

        $utilisateur = $this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']);

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'libelle' => 'Photo identite',
                'exemplaires_par_inscription' => 4,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('exemplaires_par_inscription');

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $this->corpsValide([
                'libelle' => 'Photo identite',
                'exemplaires_par_inscription' => 3,
            ]))
            ->assertCreated();
    }

    /**
     * Le défaut le plus grave du lot, et le seul qui fût déjà en service.
     *
     * Une filière-reflet LMD porte le nom de son parcours et côtoie souvent une
     * vraie filière BTS homonyme. L'écran les offrait toutes deux sous le même
     * nom : une portée posée sur la mauvaise n'est satisfaite par AUCUNE
     * inscription — zéro pièce réclamée, aucune erreur, aucune trace. Sur une
     * instance tout-LMD comme USAT, toutes les classes s'ancrent sur des
     * reflets, et le choix se jouait à pile ou face.
     *
     * Les masquer serait pire encore : USAT ne pourrait plus rien restreindre.
     * Elles sont donc offertes, mais nommées, et rangées après les vraies.
     */
    public function test_l_ecran_offre_les_filieres_reflets_lmd_en_disant_ce_qu_elles_sont(): void
    {
        $domaine = ESBTPLMDDomaine::create(['name' => 'Sciences', 'code' => 'SC-' . uniqid(), 'is_active' => true]);
        $mention = ESBTPLMDMention::create([
            'name' => 'Agronomie',
            'code' => 'AGRO-' . uniqid(),
            'domaine_id' => $domaine->id,
            'is_active' => true,
        ]);

        $reelle = ESBTPFiliere::factory()->create(['name' => 'Agronomie', 'is_active' => true]);
        $reflet = ESBTPFiliere::factory()->create(['name' => 'Agronomie', 'is_active' => true]);
        // Le reflet se marque après coup : le factory des filières ne connaît
        // pas ces deux colonnes, et elles portent chacune une clé étrangère.
        $reflet->forceFill(['lmd_mention_id' => $mention->id])->save();

        $reponse = $this->actingAs($this->utilisateur(['pieces_dossier.view']))
            ->get(route('esbtp.pieces-dossier.index'))
            ->assertOk();

        $filieres = $reponse->viewData('filieres');
        $ids = $filieres->pluck('id')->all();

        // Aucune n'est masquée.
        $this->assertContains($reelle->id, $ids);
        $this->assertContains($reflet->id, $ids);

        // Le reflet dit sa nature, la vraie filière n'a rien à préciser.
        $this->assertSame('Mention', $filieres->firstWhere('id', $reflet->id)->natureLmd());
        $this->assertNull($filieres->firstWhere('id', $reelle->id)->natureLmd());

        // Et il vient après elle, à nom égal.
        $this->assertLessThan(
            array_search($reflet->id, $ids, true),
            array_search($reelle->id, $ids, true)
        );
    }
}
