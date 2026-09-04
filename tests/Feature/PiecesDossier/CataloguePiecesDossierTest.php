<?php

namespace Tests\Feature\PiecesDossier;

use App\Enums\EtatPieceDossier;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPNiveauEtude;
use App\Models\ESBTPPieceDossier;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
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
            ->postJson(route('esbtp.pieces-dossier.store'), [
                'libelle' => 'Extrait de naissance',
                'is_obligatoire' => true,
                'forme_attendue' => 'copie',
                'nombre_exemplaires' => 1,
                'echeance' => 'inscription',
                'is_active' => true,
            ])
            ->assertForbidden();
    }

    public function test_une_piece_se_cree_avec_sa_portee(): void
    {
        $filiere = ESBTPFiliere::factory()->create();
        $niveau = ESBTPNiveauEtude::factory()->create();

        $reponse = $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->postJson(route('esbtp.pieces-dossier.store'), [
                'libelle' => "Photo d'identité",
                'description' => 'Fond uni.',
                'is_obligatoire' => true,
                'forme_attendue' => 'original',
                'nombre_exemplaires' => 2,
                'echeance' => 'inscription',
                'filiere_ids' => [$filiere->id],
                'niveau_ids' => [$niveau->id],
                'is_active' => true,
            ])
            ->assertCreated();

        $piece = ESBTPPieceDossier::findOrFail($reponse->json('piece.id'));

        // Le code n'est pas saisi au guichet : il est dérivé du libellé.
        $this->assertSame('photo_d_identite', $piece->code);
        $this->assertSame(2, $piece->nombre_exemplaires);
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
            ->postJson(route('esbtp.pieces-dossier.store'), [
                'libelle' => 'Extrait de naissance',
                'is_obligatoire' => true,
                'forme_attendue' => 'copie',
                'nombre_exemplaires' => 1,
                'echeance' => 'inscription',
                'is_active' => true,
            ])
            ->assertCreated();

        $piece = ESBTPPieceDossier::with(['filieres', 'niveaux'])->findOrFail($reponse->json('piece.id'));

        $this->assertTrue($piece->concerneToutesFilieres());
        $this->assertTrue($piece->concerneTousNiveaux());
        $this->assertSame('Toutes filières / Tous niveaux', $reponse->json('piece.libelle_scope'));
    }

    /**
     * Renommer une pièce ne doit pas rompre l'historique : le code est le point
     * d'ancrage des états de dossier déjà saisis.
     */
    public function test_modifier_une_piece_ne_change_jamais_son_code(): void
    {
        $piece = ESBTPPieceDossier::create([
            'code' => 'extrait_naissance',
            'libelle' => 'Extrait de naissance',
            'is_obligatoire' => true,
            'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1,
            'echeance' => 'inscription',
            'is_active' => true,
            'ordre' => 10,
        ]);

        $this->actingAs($this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']))
            ->putJson(route('esbtp.pieces-dossier.update', $piece), [
                'libelle' => "Extrait d'acte de naissance",
                'is_obligatoire' => false,
                'forme_attendue' => 'indifferent',
                'nombre_exemplaires' => 3,
                'echeance' => 'avant_fin_annee',
                'is_active' => true,
            ])
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
        $piece = ESBTPPieceDossier::create([
            'code' => 'certificat_scolarite',
            'libelle' => 'Certificat de scolarité',
            'is_obligatoire' => false,
            'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1,
            'echeance' => 'inscription',
            'is_active' => true,
            'ordre' => 10,
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

    public function test_le_reordonnancement_reecrit_l_ordre_complet(): void
    {
        $utilisateur = $this->utilisateur(['pieces_dossier.view', 'pieces_dossier.configure']);

        $premiere = ESBTPPieceDossier::create([
            'code' => 'a', 'libelle' => 'A', 'is_obligatoire' => true, 'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1, 'echeance' => 'inscription', 'is_active' => true, 'ordre' => 10,
        ]);
        $seconde = ESBTPPieceDossier::create([
            'code' => 'b', 'libelle' => 'B', 'is_obligatoire' => true, 'forme_attendue' => 'copie',
            'nombre_exemplaires' => 1, 'echeance' => 'inscription', 'is_active' => true, 'ordre' => 20,
        ]);

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

        $corps = [
            'libelle' => "Photo d'identité",
            'is_obligatoire' => true,
            'forme_attendue' => 'original',
            'echeance' => 'inscription',
            'is_active' => true,
        ];

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $corps + ['nombre_exemplaires' => 4])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre_exemplaires');

        $this->actingAs($utilisateur)
            ->postJson(route('esbtp.pieces-dossier.store'), $corps + ['nombre_exemplaires' => 3])
            ->assertCreated();
    }

    /**
     * Le geste normal est la coche : une pièce se déclare reçue sans qu'aucun
     * fichier ne soit joint, aujourd'hui comme plus tard.
     */
    public function test_une_piece_se_declare_deposee_sans_aucun_fichier(): void
    {
        $ligne = $this->ligneDEtat(EtatPieceDossier::DEPOSEE, ['exemplaires_recus' => 1]);

        $this->assertNull($ligne->fichier_chemin);
        $this->assertSame(1, $ligne->exemplaires_recus);
    }

    /**
     * Un refus sans motif écrit produit un dossier que personne ne peut
     * débloquer. La règle est tenue par le modèle, donc par toutes les
     * écritures, et pas seulement par l'écran qui les déclenche.
     */
    public function test_un_refus_sans_motif_est_refuse(): void
    {
        $this->expectException(RuntimeException::class);

        $this->ligneDEtat(EtatPieceDossier::REFUSEE);
    }

    public function test_un_refus_motive_est_accepte(): void
    {
        $ligne = $this->ligneDEtat(EtatPieceDossier::REFUSEE, [
            'motif' => 'Copie illisible, à refaire.',
        ]);

        $this->assertSame(EtatPieceDossier::REFUSEE, $ligne->etat);
        $this->assertFalse($ligne->estSoldee());
    }

    private function ligneDEtat(EtatPieceDossier $etat, array $attributs = []): ESBTPInscriptionPiece
    {
        $inscription = \App\Models\ESBTPInscription::factory()->create();

        $piece = ESBTPPieceDossier::create([
            'code' => 'photo_identite_' . uniqid(),
            'libelle' => "Photo d'identité",
            'is_obligatoire' => true,
            'forme_attendue' => 'original',
            'nombre_exemplaires' => 2,
            'echeance' => 'inscription',
            'is_active' => true,
            'ordre' => 10,
        ]);

        return ESBTPInscriptionPiece::create(array_merge([
            'inscription_id' => $inscription->id,
            'piece_dossier_id' => $piece->id,
            'etat' => $etat->value,
        ], $attributs));
    }
}
