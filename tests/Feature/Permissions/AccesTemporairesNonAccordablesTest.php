<?php

namespace Tests\Feature\Permissions;

use App\Domain\Permissions\AccesTemporaires;
use App\Services\PermissionRegistry;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Un acces temporaire ne doit rien ouvrir qui survive a son echeance. Le cas qui
 * le casse : une permission qui laisse creer un compte, poser un role ou fixer le
 * mot de passe de quelqu'un — le beneficiaire se donne un acces permanent
 * pendant ses deux jours.
 *
 * Une liste ecrite a la main s'est deja revelee incomplete. Ce test ne la
 * recopie donc pas : il la DEDUIT du code. Pour chaque route d'ecriture d'un
 * controleur qui pose un role, une permission ou un mot de passe, il releve ses
 * gardes, et echoue si un acces temporaire suffit a toutes les franchir.
 *
 * On suppose que le beneficiaire detient deja, de facon permanente, toutes les
 * AUTRES gardes de la route : une responsable scolarite porte `identity.registrar`,
 * qui ouvre le groupe des routes de creation de comptes. Une garde qu'il detient
 * ne l'arrete pas. La route n'est donc sure que si AUCUNE de ses gardes n'est
 * accordable, ou si elle est reservee a superAdmin / serviceTechnique (qui ont
 * deja tout). Une politique fondee sur les roles (UserManagementPolicy) n'est pas
 * comptee : elle restreint les cibles, elle ne remplace pas la permission.
 * (Une premiere version tenait pour sure une route dont un groupe n'avait aucun
 * membre accordable : elle laissait passer precisement la creation d'un compte
 * de direction par une responsable scolarite.)
 */
class AccesTemporairesNonAccordablesTest extends TestCase
{
    /**
     * Controleurs qui ne creent que des comptes etudiants (role `etudiant` ecrit
     * en dur). Le compte est une donnee metier, pas un acces du personnel pour le
     * beneficiaire : les laisser accordables est un choix documente.
     */
    private const COMPTES_ETUDIANTS = [
        \App\Http\Controllers\ESBTPEtudiantController::class,
    ];

    private const MOTIF_ECRITURE_SENSIBLE = '/assignRole\(|syncRoles\(|givePermissionTo\(|syncPermissions\(|Hash::make\(|->password\s*=/';

    public function test_aucune_ecriture_sensible_ne_s_ouvre_par_un_acces_temporaire(): void
    {
        $fautives = [];

        foreach (Route::getRoutes() as $route) {
            $cible = $this->cible($route);
            if ($cible === null) {
                continue;
            }
            [$classe, $methode] = $cible;

            $groupes = $this->groupesDeGardes($route, $classe, $methode);
            $accordables = $this->permissionsAccordables($groupes);
            if ($accordables === [] || $this->uneGardeResiste($route, $groupes)) {
                continue;
            }
            $fautives[] = class_basename($classe)."@{$methode} (".implode(', ', $accordables).')';
        }

        $this->assertSame([], $fautives,
            'Ces ecritures posent un role, une permission ou un mot de passe et s\'ouvrent par un acces temporaire.');
    }

    public function test_un_alias_legacy_se_juge_sous_son_nom_canonique(): void
    {
        $registre = app(PermissionRegistry::class);
        $service = app(AccesTemporaires::class);

        foreach ($registre->aliasMap() as $alias => $canonique) {
            $this->assertSame($service->estAccordable($canonique), $service->estAccordable($alias), $alias);
        }
    }

    public function test_restaurer_une_sauvegarde_ne_s_accorde_pas(): void
    {
        $this->assertFalse(app(AccesTemporaires::class)->estAccordable('security.backup.restore'));
    }

    public function test_dans_une_famille_de_comptes_seule_la_consultation_s_accorde(): void
    {
        $service = app(AccesTemporaires::class);
        $registre = app(PermissionRegistry::class)->all()->keys();

        foreach (AccesTemporaires::FAMILLES_DE_COMPTES as $famille) {
            foreach ($registre->filter(fn ($n) => str_starts_with($n, $famille.'.')) as $nom) {
                $this->assertSame($nom === $famille.'.view', $service->estAccordable($nom), $nom);
            }
        }
    }

    /** @return array{0: class-string, 1: string}|null */
    private function cible(RouteDefinition $route): ?array
    {
        $action = $route->getAction('uses');
        if (! is_string($action) || ! str_contains($action, '@')) {
            return null;
        }
        [$classe, $methode] = explode('@', $action, 2);
        if (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH']) === [] || $methode === 'destroy') {
            return null;
        }
        if (in_array($classe, self::COMPTES_ETUDIANTS, true) || ! class_exists($classe)
            || str_contains($classe, '\\API\\CLI\\') || str_ends_with($classe, 'InstallController')) {
            return null;
        }
        $source = file_get_contents((new \ReflectionClass($classe))->getFileName());

        return preg_match(self::MOTIF_ECRITURE_SENSIBLE, $source) ? [$classe, $methode] : null;
    }

    /** @return array<int, array<int, string>> une liste par garde ; chaque garde s'ouvre avec l'un de ses membres */
    private function groupesDeGardes(RouteDefinition $route, string $classe, string $methode): array
    {
        $groupes = [];
        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'permission:')) {
                $groupes[] = explode('|', substr($middleware, strlen('permission:')));
            }
        }
        foreach ($this->abilitesDansLaMethode($classe, $methode) as $nom) {
            $groupes[] = [$nom];
        }

        return $groupes;
    }

    /** @param array<int, array<int, string>> $groupes */
    private function permissionsAccordables(array $groupes): array
    {
        $service = app(AccesTemporaires::class);

        return array_values(array_unique(array_filter(
            array_merge([], ...$groupes),
            fn (string $nom) => $service->estAccordable($nom)
        )));
    }

    /** @param array<int, array<int, string>> $groupes */
    private function uneGardeResiste(RouteDefinition $route, array $groupes): bool
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'role:')) {
                continue;
            }
            $roles = explode('|', substr($middleware, strlen('role:')));
            if (array_diff($roles, ['superAdmin', 'serviceTechnique']) === []) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private function abilitesDansLaMethode(string $classe, string $methode): array
    {
        if (! method_exists($classe, $methode)) {
            return [];
        }
        $reflexion = new \ReflectionMethod($classe, $methode);
        $corps = implode('', array_slice(file($reflexion->getFileName()), $reflexion->getStartLine() - 1,
            $reflexion->getEndLine() - $reflexion->getStartLine() + 1));
        preg_match_all("/(?:authorize|can|hasPermissionTo)\\(\\s*'([a-z_ ]+\\.?[a-z_.]*)'/", $corps, $m);

        // Les abilities de politique (update, delete, assignRole…) ne sont pas des permissions.
        return array_values(array_filter($m[1], fn (string $nom) => str_contains($nom, '.') || str_contains($nom, '_') || str_contains($nom, ' ')));
    }
}
