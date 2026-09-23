<?php

namespace Tests\Feature\Permissions;

use App\Domain\Permissions\AccesTemporaires;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Un acces temporaire ne doit rien ouvrir qui survive a son echeance. Le cas qui
 * le casse : une permission qui laisse creer un compte ou poser un role — le
 * beneficiaire se donne un acces permanent pendant ses deux jours.
 *
 * Une liste ecrite a la main s'est deja revelee incomplete (les comptes de
 * scolarite, de direction, de caisse restaient ouverts). Ce test ne recopie donc
 * pas la liste : il la DEDUIT du code. Il cherche tout controleur qui pose un
 * role ou une permission, releve les permissions qui gardent ses methodes
 * d'ecriture (middleware de route, authorize(), can()), et exige qu'aucune ne
 * soit accordable pour un temps.
 */
class AccesTemporairesNonAccordablesTest extends TestCase
{
    /**
     * Controleurs qui creent des comptes de niveau etudiant seulement. Le compte
     * est une donnee metier (une inscription), pas un acces pour le
     * beneficiaire : les laisser accordables est un choix, pas un oubli.
     */
    private const COMPTES_ETUDIANTS = [
        \App\Http\Controllers\ESBTPEtudiantController::class,
    ];

    public function test_aucune_permission_qui_pose_un_role_n_est_accordable(): void
    {
        $service = app(AccesTemporaires::class);
        $fautives = [];

        foreach ($this->gardesDesEcrituresQuiPosentUnRole() as $permission => $source) {
            if ($service->estAccordable($permission)) {
                $fautives[] = "{$permission} ({$source})";
            }
        }

        $this->assertSame([], array_values(array_unique($fautives)),
            "Ces permissions permettent de poser un role ou de creer un compte du personnel, et restent accordables pour un temps limite.");
    }

    public function test_restaurer_une_sauvegarde_ne_s_accorde_pas(): void
    {
        $this->assertFalse(app(AccesTemporaires::class)->estAccordable('security.backup.restore'));
    }

    public function test_dans_une_famille_de_comptes_seule_la_consultation_s_accorde(): void
    {
        $service = app(AccesTemporaires::class);
        $registre = app(\App\Services\PermissionRegistry::class)->all()->keys();

        foreach (AccesTemporaires::FAMILLES_DE_COMPTES as $famille) {
            foreach ($registre->filter(fn ($n) => str_starts_with($n, $famille.'.')) as $nom) {
                $this->assertSame($nom === $famille.'.view', $service->estAccordable($nom), $nom);
            }
        }
    }

    /** @return array<string, string> permission => controleur@methode */
    private function gardesDesEcrituresQuiPosentUnRole(): array
    {
        $gardes = [];

        foreach (Route::getRoutes() as $route) {
            $action = $route->getAction('uses');
            if (! is_string($action) || ! str_contains($action, '@')) {
                continue;
            }
            [$classe, $methode] = explode('@', $action, 2);
            if (in_array($classe, self::COMPTES_ETUDIANTS, true) || ! $this->posteUnRole($classe)) {
                continue;
            }
            // Creer et modifier posent un role ; supprimer un compte n'ouvre
            // aucun acces qui survivrait a l'echeance.
            if (array_intersect($route->methods(), ['POST', 'PUT', 'PATCH']) === [] || $methode === 'destroy') {
                continue;
            }

            foreach ($route->gatherMiddleware() as $middleware) {
                if (is_string($middleware) && str_starts_with($middleware, 'permission:')) {
                    foreach (explode('|', substr($middleware, strlen('permission:'))) as $nom) {
                        $gardes[$nom] = class_basename($classe)."@{$methode}";
                    }
                }
            }
            foreach ($this->abilitesDansLaMethode($classe, $methode) as $nom) {
                $gardes[$nom] = class_basename($classe)."@{$methode}";
            }
        }

        return $gardes;
    }

    private function posteUnRole(string $classe): bool
    {
        if (! class_exists($classe) || str_contains($classe, '\\API\\CLI\\') || str_ends_with($classe, 'InstallController')) {
            return false;
        }
        $source = file_get_contents((new \ReflectionClass($classe))->getFileName());

        return (bool) preg_match('/assignRole\(|syncRoles\(|givePermissionTo\(|syncPermissions\(/', $source);
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

        // Une methode doublee par UserManagementPolicy (authorize('update'…),
        // canManage) ne s'ouvre qu'aux roles de l'acteur — la matrice « qui
        // gere qui » — qu'un acces temporaire ne change pas. Ses permissions
        // ne suffisent donc pas a poser quoi que ce soit.
        if (preg_match("/authorize\\(\\s*'(update|assignRole|delete)'|canManage\\(/", $corps)) {
            return [];
        }
        preg_match_all("/(?:authorize|can|hasPermissionTo)\\(\\s*'([a-z_]+\\.[a-z_.]+)'/", $corps, $m);

        return $m[1];
    }
}
