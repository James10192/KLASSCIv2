<?php

namespace Tests\Unit\Permissions;

use App\Services\PermissionRegistry;
use App\Services\PermissionSyncService;
use PHPUnit\Framework\TestCase;

/**
 * « Régénérer les frais » réécrit ce que l'école réclame aux familles, parfois
 * sur une année entière. Ce droit s'est détaché de `inscriptions.edit`.
 *
 * Une garde déplacée et pas les autres, c'est soit un bouton qui mène à un 403,
 * soit une route ouverte derrière un bouton caché. Ce test les tient ensemble.
 */
class DroitRegenererLesFraisTest extends TestCase
{
    private const DROIT = 'frais.regenerate';

    /** @return array<string, mixed> */
    private function registre(): array
    {
        return require __DIR__ . '/../../../config/permissions.php';
    }

    private function lire(string $chemin): string
    {
        return file_get_contents(__DIR__ . '/../../../' . $chemin);
    }

    public function test_le_droit_existe_dans_le_registre_avec_un_libelle_lisible(): void
    {
        $permissions = $this->registre()['permissions'];

        $this->assertArrayHasKey(self::DROIT, $permissions);
        $this->assertNotEmpty($permissions[self::DROIT]['label']);
        $this->assertSame('Frais', $permissions[self::DROIT]['group']);
    }

    public function test_seule_la_comptabilite_le_recoit_par_defaut(): void
    {
        $defauts = $this->registre()['role_defaults'];

        $this->assertContains(self::DROIT, $defauts['comptable']);

        // superAdmin et serviceTechnique passent par Gate::before ('*').
        // Tous les autres rôles d'organigramme ne l'ont pas : c'est l'école qui
        // décide, depuis /esbtp/custom-roles, si sa scolarité doit l'avoir.
        foreach (['secretaire', 'caissier', 'coordinateur', 'directeurEtudes',
                  'responsableScolarite', 'serviceScolarite', 'agentInscription',
                  'chargeCommunication', 'enseignant', 'etudiant'] as $role) {
            $this->assertNotContains(
                self::DROIT,
                $defauts[$role],
                "Le rôle {$role} ne doit pas retarifer une promotion par défaut."
            );
        }
    }

    public function test_la_synchronisation_le_distribue_aux_roles_deja_peuples(): void
    {
        // Sans cette entrée, `permissions:fix` annonce un succès et n'accorde
        // rien : la ligne 62 du service préserve tout rôle non vide, et sur les
        // instances en service la comptabilité l'est. Le bouton disparaîtrait
        // pour tout le monde, superAdmin excepté.
        $service = new PermissionSyncService(new PermissionRegistry());
        $methode = new \ReflectionMethod($service, 'newFeaturePermissions');
        $methode->setAccessible(true);

        $this->assertContains(self::DROIT, $methode->invoke($service));
    }

    public function test_la_route_et_le_controleur_exigent_le_droit(): void
    {
        $this->assertStringContainsString(
            "'permission:frais.regenerate'",
            $this->lire('routes/web.php'),
            'Le groupe de routes frais-manquants doit porter frais.regenerate.'
        );

        $this->assertStringContainsString(
            "middleware('permission:frais.regenerate')",
            $this->lire('app/Http/Controllers/ESBTP/CompleterFraisManquantsController.php')
        );

        $this->assertStringNotContainsString(
            "Route::middleware(['permission:inscriptions.edit', 'throttle:20,1'])",
            $this->lire('routes/web.php'),
            'Une garde de regeneration est restee sur l ancien droit.'
        );
    }

    public function test_tout_ecran_portant_le_bouton_exige_le_droit(): void
    {
        // La version precedente de ce test enumerait trois fichiers en dur. Elle
        // etait verte alors qu'un QUATRIEME ecran — la fiche etudiant — etait
        // reste sur `inscriptions.edit` : les quatre roles de scolarite y
        // voyaient le bouton et recoltaient un 403 en anglais, tandis que la
        // comptabilite, seule titulaire du nouveau droit, ne le voyait pas.
        //
        // Une liste en dur ne rattrape pas le fichier qu'on a oublie d'y mettre.
        // On part donc du bouton lui-meme : la classe `js-regenerer-frais` est ce
        // qui declenche la modale, et elle seule.
        $porteurs = $this->vuesContenant('js-regenerer-frais');

        $this->assertGreaterThanOrEqual(
            3,
            count($porteurs),
            'Le balayage ne trouve plus les ecrans connus : le test ne prouverait plus rien.'
        );

        foreach ($porteurs as $chemin => $contenu) {
            $this->assertStringContainsString(
                "@can('frais.regenerate')",
                $contenu,
                "{$chemin} montre le bouton sans exiger frais.regenerate."
            );
        }
    }

    /**
     * @return array<string, string> chemin relatif => contenu
     */
    private function vuesContenant(string $aiguille): array
    {
        $racine = __DIR__ . '/../../../resources/views';
        $trouves = [];

        $fichiers = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($racine, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($fichiers as $fichier) {
            if (! $fichier->isFile() || ! str_ends_with($fichier->getFilename(), '.blade.php')) {
                continue;
            }

            $contenu = file_get_contents($fichier->getPathname());

            if (str_contains($contenu, $aiguille)) {
                $trouves[str_replace($racine . '/', '', $fichier->getPathname())] = $contenu;
            }
        }

        return $trouves;
    }
}
