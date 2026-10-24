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

    public const TABLE_REVOQUEES = 'role_permission_revocations';

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

        // Une interface enregistre l'ensemble COMPLET des droits accordés.
        // Les ajouts et les retraits explicites sont une seule décision atomique.
        // Fail closed si le schema n'est pas migré : ne jamais afficher un
        // succès alors que la synchronisation effacerait cette décision.
        if (! $this->tableRevocationsExiste()) {
            throw new \RuntimeException('La migration des révocations de permissions doit être exécutée avant toute modification de rôle.');
        }

        $voulues = $this->horsDefauts($roleName, $permissionsAccordees);
        $accordees = array_flip(array_map(
            fn (string $nom) => $this->registry->canonicalize($nom),
            $permissionsAccordees
        ));
        $retirees = array_values(array_filter(
            $this->registry->defaultPermissionsFor($roleName),
            fn (string $nom) => ! isset($accordees[$this->registry->canonicalize($nom)])
        ));

        DB::transaction(function () use ($roleName, $voulues, $retirees, $motif, $parUtilisateur) {
            DB::table(self::TABLE)->where('role_name', $roleName)->delete();
            DB::table(self::TABLE_REVOQUEES)->where('role_name', $roleName)->delete();

            $maintenant = now();
            $audit = fn (string $nom) => [
                'role_name' => $roleName,
                'permission_name' => $nom,
                'motif' => $motif,
                'accordee_par' => $parUtilisateur,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ];

            if ($voulues !== []) {
                DB::table(self::TABLE)->insert(array_map($audit, $voulues));
            }
            if ($retirees !== []) {
                DB::table(self::TABLE_REVOQUEES)->insert(array_map($audit, $retirees));
            }
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
     * Permissions par defaut retirees deliberement par cette instance.
     * Sur un serveur pas encore migre, la synchronisation peut toujours
     * tourner avant les migrations. Aucune revocation preexistante n'est alors
     * possible dans cette nouvelle table.
     *
     * @return list<string>
     */
    public function revoqueesPour(string $roleName): array
    {
        if (! $this->tableRevocationsExiste()) {
            return [];
        }

        return DB::table(self::TABLE_REVOQUEES)
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
     *
     * Seule la reponse POSITIVE est memorisee. Une reponse negative gardee pour
     * toute la duree du processus rendait la protection inerte precisement
     * quand elle sert : demandee une premiere fois avant les migrations, la
     * classe repondait « pas de table » jusqu'a la fin du processus, meme une
     * fois la table creee — et la decision de l'ecole repassait pour de la
     * derive au nettoyage suivant. La memoire est aussi tenue par connexion :
     * une table presente ici ne l'est pas forcement ailleurs.
     */
    private function tableRevocationsExiste(): bool
    {
        // Ne pas memoriser une absence (sync avant migrate), et tenir compte
        // de la connexion de la base de chaque instance.
        return DB::getSchemaBuilder()->hasTable(self::TABLE_REVOQUEES);
    }

    private function tableExiste(): bool
    {
        static $vue = [];

        $connexion = DB::getDefaultConnection();

        if (($vue[$connexion] ?? false) === true) {
            return true;
        }

        return $vue[$connexion] = DB::getSchemaBuilder()->hasTable(self::TABLE);
    }
}
