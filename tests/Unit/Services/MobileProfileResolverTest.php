<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\Mobile\MobileProfileResolver;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le profil mobile se deduit des PERMISSIONS, dans l'ordre du dispatcher de
 * tableau de bord — jamais du nom d'un role. Sans base : User est un mock,
 * le reglage d'instance et la session sont remplaces par deux methodes
 * protegees du resolver.
 */
class MobileProfileResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        MobileProfileResolver::oublier();
    }

    protected function tearDown(): void
    {
        MobileProfileResolver::oublier();
        Mockery::close();
        parent::tearDown();
    }

    public function test_sans_utilisateur_pas_de_profil(): void
    {
        $this->assertNull($this->resolver()->resolve(null));
    }

    public function test_reglage_coupe_pas_de_profil_meme_pour_un_caissier(): void
    {
        $user = $this->utilisateur(superAdmin: false, permissions: ['module.caisse.access']);
        $user->shouldNotReceive('can');
        $user->shouldNotReceive('hasRole');

        $this->assertNull($this->resolver(actif: false)->resolve($user));
    }

    public function test_super_admin_recoit_comptable_par_defaut(): void
    {
        $user = $this->utilisateur(superAdmin: true);
        $user->shouldNotReceive('can');

        $this->assertSame(MobileProfileResolver::COMPTABLE, $this->resolver()->resolve($user));
    }

    public function test_super_admin_suit_le_profil_pose_en_session(): void
    {
        foreach (MobileProfileResolver::PROFILS as $profil) {
            MobileProfileResolver::oublier();
            $user = $this->utilisateur(superAdmin: true);

            $this->assertSame($profil, $this->resolver(session: $profil)->resolve($user));
        }
    }

    public function test_super_admin_ignore_une_valeur_de_session_inconnue(): void
    {
        $user = $this->utilisateur(superAdmin: true);

        $this->assertSame(MobileProfileResolver::COMPTABLE, $this->resolver(session: 'directeur')->resolve($user));
    }

    /**
     * @dataProvider permissionsVersProfil
     */
    public function test_la_permission_decide_dans_l_ordre_du_dispatcher(array $permissions, string $attendu): void
    {
        $user = $this->utilisateur(superAdmin: false, permissions: $permissions);

        $this->assertSame($attendu, $this->resolver()->resolve($user));
    }

    public static function permissionsVersProfil(): array
    {
        return [
            'caisse seule' => [['module.caisse.access'], MobileProfileResolver::CAISSIER],
            'comptabilite seule' => [['comptabilite.access'], MobileProfileResolver::COMPTABLE],
            'enseignant seul' => [['identity.teach'], MobileProfileResolver::ENSEIGNANT],
            'etudiant seul' => [['identity.student'], MobileProfileResolver::ETUDIANT],
            'caisse prime sur comptabilite' => [['comptabilite.access', 'module.caisse.access'], MobileProfileResolver::CAISSIER],
            'comptabilite prime sur enseignant' => [['identity.teach', 'comptabilite.access'], MobileProfileResolver::COMPTABLE],
            'enseignant prime sur etudiant' => [['identity.student', 'identity.teach'], MobileProfileResolver::ENSEIGNANT],
        ];
    }

    public function test_sans_permission_le_premier_role_declarant_un_profil_decide(): void
    {
        $user = $this->utilisateur(superAdmin: false, permissions: [], roles: [null, 'enseignant', 'caissier']);

        $this->assertSame(MobileProfileResolver::ENSEIGNANT, $this->resolver()->resolve($user));
    }

    public function test_un_profil_de_role_inconnu_est_ignore(): void
    {
        $user = $this->utilisateur(superAdmin: false, permissions: [], roles: ['directeur']);

        $this->assertNull($this->resolver()->resolve($user));
    }

    public function test_sans_permission_ni_role_declarant_pas_de_profil(): void
    {
        $user = $this->utilisateur(superAdmin: false, permissions: [], roles: []);

        $this->assertNull($this->resolver()->resolve($user));
    }

    public function test_la_colonne_absente_pendant_le_deploiement_ne_casse_rien(): void
    {
        // Avant la migration, roles.mobile_profile n'existe pas : l'attribut vaut null.
        $user = $this->utilisateur(superAdmin: false, permissions: [], roles: [null]);

        $this->assertNull($this->resolver()->resolve($user));
    }

    public function test_le_resultat_est_memoise_pour_la_requete(): void
    {
        // La caisse est la premiere permission testee : un seul appel a can()
        // pour deux resolutions prouve que la seconde vient de la memo.
        $user = $this->utilisateur(superAdmin: false, permissions: ['module.caisse.access'], appelsCan: 1);

        $resolver = $this->resolver();
        $this->assertSame(MobileProfileResolver::CAISSIER, $resolver->resolve($user));
        $this->assertSame(MobileProfileResolver::CAISSIER, $resolver->resolve($user));
    }

    public function test_start_url_revient_au_tableau_de_bord_sans_profil(): void
    {
        $this->assertSame('/dashboard', MobileProfileResolver::startUrl(null));
        $this->assertSame('/dashboard', MobileProfileResolver::startUrl('inconnu'));
        $this->assertSame('/dashboard?profil=caissier', MobileProfileResolver::startUrl('caissier'));
    }

    public function test_les_libelles_couvrent_exactement_les_quatre_profils(): void
    {
        $this->assertSame(MobileProfileResolver::PROFILS, array_keys(MobileProfileResolver::libelles()));
    }

    /**
     * Resolver dont le reglage d'instance et la session sont pilotes par le test.
     */
    private function resolver(bool $actif = true, ?string $session = null): MobileProfileResolver
    {
        return new class($actif, $session) extends MobileProfileResolver {
            public function __construct(private bool $actif, private ?string $session)
            {
            }

            protected function shellActif(): bool
            {
                return $this->actif;
            }

            protected function profilForce(): ?string
            {
                return $this->session;
            }
        };
    }

    /**
     * @param string[]            $permissions permissions accordees par le Gate
     * @param array<int, ?string> $roles       mobile_profile de chaque role, dans l'ordre
     * @param int|null            $appelsCan   nombre exact d'appels a can() attendus, null = libre
     */
    private function utilisateur(bool $superAdmin, array $permissions = [], array $roles = [], ?int $appelsCan = null): MockInterface
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasRole')->with('superAdmin')->andReturn($superAdmin);
        $can = $user->shouldReceive('can')->andReturnUsing(
            fn (string $permission): bool => in_array($permission, $permissions, true)
        );
        if ($appelsCan !== null) {
            $can->times($appelsCan);
        }
        $user->shouldReceive('getRelationValue')->with('roles')->andReturn(
            collect($roles)->map(fn (?string $profil) => $this->role($profil))
        );

        return $user;
    }

    private function role(?string $profil): object
    {
        return new class($profil) {
            public function __construct(private ?string $profil)
            {
            }

            public function getAttribute(string $cle): ?string
            {
                return $cle === 'mobile_profile' ? $this->profil : null;
            }
        };
    }
}
