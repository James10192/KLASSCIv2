<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Ce qu'un etablissement a deliberement ajoute a un role canonique.
 *
 * La synchronisation nettoie cinq roles d'organigramme en retirant tout ce qui
 * ne figure pas dans les defauts partages. Sans distinction, elle efface aussi
 * bien une permission restee la par accident qu'une decision d'organisation.
 * Ce service porte cette distinction, et lui seul.
 *
 * Le principe tient en une phrase : une permission accordee explicitement, et
 * absente des defauts, est une decision — on l'inscrit, et la synchronisation
 * la respecte. Tout le reste est de la derive.
 */
class ExtensionsDeRole
{
    public const TABLE = 'role_permission_extensions';

    public function __construct(private readonly PermissionRegistry $registry)
    {
    }

    /**
     * Enregistre l'ecart entre ce qui vient d'etre accorde et les defauts.
     *
     * Remplace l'etat precedent du role plutot que de s'y ajouter : une
     * permission retiree par l'ecole ne doit pas survivre dans cette table et
     * ressusciter au prochain nettoyage.
     *
     * @param  list<string>  $permissionsAccordees  l'ensemble COMPLET des permissions du role apres l'operation
     */
    public function enregistrer(string $roleName, array $permissionsAccordees, ?string $motif = null, ?int $parUtilisateur = null): void
    {
        if (! $this->tableExiste()) {
            return;
        }

        $voulues = $this->horsDefauts($roleName, $permissionsAccordees);

        DB::transaction(function () use ($roleName, $voulues, $motif, $parUtilisateur) {
            DB::table(self::TABLE)->where('role_name', $roleName)->delete();

            if ($voulues === []) {
                return;
            }

            $maintenant = now();

            DB::table(self::TABLE)->insert(array_map(fn (string $permission) => [
                'role_name' => $roleName,
                'permission_name' => $permission,
                'motif' => $motif,
                'accordee_par' => $parUtilisateur,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ], $voulues));
        });
    }

    /**
     * Les permissions que cette instance a voulu ajouter au role.
     *
     * @return list<string>
     */
    public function pour(string $roleName): array
    {
        if (! $this->tableExiste()) {
            return [];
        }

        return DB::table(self::TABLE)
            ->where('role_name', $roleName)
            ->pluck('permission_name')
            ->all();
    }

    /**
     * Ce qui, dans un ensemble accorde, sort des defauts du role.
     *
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public function horsDefauts(string $roleName, array $permissions): array
    {
        $defauts = array_flip($this->registry->defaultPermissionsFor($roleName));

        $hors = [];
        foreach (array_unique($permissions) as $permission) {
            $canonique = $this->registry->canonicalize($permission);
            if (! isset($defauts[$permission]) && ! isset($defauts[$canonique])) {
                $hors[] = $permission;
            }
        }

        return $hors;
    }

    /**
     * La table peut manquer sur une instance qui n'a pas encore migre.
     *
     * On rend alors « aucune extension » plutot que de lever : la
     * synchronisation des permissions tourne pendant le deploiement, souvent
     * AVANT les migrations. Echouer la rendrait le deploiement impossible sur
     * la seule instance qui en a besoin.
     */
    private function tableExiste(): bool
    {
        static $existe = null;

        return $existe ??= DB::getSchemaBuilder()->hasTable(self::TABLE);
    }
}
