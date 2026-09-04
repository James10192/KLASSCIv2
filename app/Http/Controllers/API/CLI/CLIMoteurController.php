<?php

namespace App\Http\Controllers\API\CLI;

use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Dit sur quel moteur de base une instance tourne réellement.
 *
 * Il n'y a pas de SSH vers l'hébergement, et rien n'exposait cette information.
 * Elle a pourtant bloqué une décision de schéma : une migration introduisant une
 * colonne générée `STORED` dans un index unique ne peut pas être écrite sans
 * savoir ce que les six écoles font tourner. MariaDB 10.2 et MySQL 5.7 ne
 * supportent pas les mêmes choses, et la suite de tests tourne sur MariaDB quand
 * la production tourne peut-être sur autre chose.
 *
 * On a donc conçu autour de l'inconnu, ce qui est prudent mais coûteux. Cet
 * endpoint supprime l'inconnu.
 *
 * Il ne rend QUE des caractéristiques du moteur : version, jeu de caractères,
 * collation, moteur de stockage par défaut, et les quelques réglages qui
 * décident si une migration passera. Jamais un nom de base, jamais un
 * identifiant de connexion, jamais une donnée d'école — cette réponse peut se
 * coller dans un ticket sans y réfléchir.
 */
class CLIMoteurController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        try {
            $version = (string) DB::selectOne('SELECT VERSION() AS v')->v;

            return $this->successResponse([
                'version' => $version,
                'famille' => $this->famille($version),
                'version_courte' => $this->versionCourte($version),
                'supporte_colonne_generee' => $this->supporteColonneGeneree($version),
                'variables' => $this->variables(),
            ], 'Moteur de base de données : '.$version);
        } catch (Throwable $e) {
            return $this->errorResponse(
                "Impossible d'interroger le moteur : ".$e->getMessage(),
                [],
                500
            );
        }
    }

    /**
     * MariaDB s'annonce dans sa propre chaîne de version ; MySQL non.
     */
    private function famille(string $version): string
    {
        return str_contains(strtolower($version), 'mariadb') ? 'MariaDB' : 'MySQL';
    }

    private function versionCourte(string $version): string
    {
        preg_match('/^(\d+\.\d+\.\d+)/', $version, $m);

        return $m[1] ?? $version;
    }

    /**
     * Une colonne générée STORED indexable existe depuis MySQL 5.7.8 et
     * MariaDB 5.2, mais elle n'est utilisable dans une contrainte UNIQUE que sur
     * des versions plus récentes, et le comportement diffère entre les deux
     * familles. On ne rend donc pas un simple oui : on rend la version et le
     * verdict, pour que la décision se prenne en connaissance de cause.
     */
    private function supporteColonneGeneree(string $version): bool
    {
        $courte = $this->versionCourte($version);

        return $this->famille($version) === 'MariaDB'
            ? version_compare($courte, '10.2.0', '>=')
            : version_compare($courte, '5.7.8', '>=');
    }

    /**
     * Les réglages qui décident si une migration passe ou casse.
     *
     * `innodb_large_prefix` et la taille de page bornent la longueur d'un index :
     * c'est ce qui fait échouer un UNIQUE sur trois colonnes dont une chaîne. Le
     * mode SQL décide, lui, si une valeur invalide est refusée ou silencieusement
     * tronquée.
     */
    private function variables(): array
    {
        $voulues = [
            'version_comment',
            'sql_mode',
            'character_set_database',
            'collation_database',
            'default_storage_engine',
            'innodb_page_size',
            'max_allowed_packet',
        ];

        $lues = [];

        foreach ($voulues as $nom) {
            // `SHOW VARIABLES LIKE ?` ne peut PAS recevoir de parametre lie : MySQL et
            // MariaDB refusent un marqueur a cet endroit d une requete preparee. La
            // premiere version le faisait, le catch avalait l echec, et l endpoint
            // rendait sept variables a null en annoncant un succes — precisement le
            // genre de panne muette qu il existe pour eviter.
            //
            // L interpolation est sans risque ici : la liste est fermee et ecrite dans
            // ce fichier, ce n est jamais une entree d utilisateur.
            try {
                $ligne = DB::selectOne("SHOW VARIABLES WHERE Variable_name = '".$nom."'");
                $lues[$nom] = $ligne->Value ?? null;
            } catch (Throwable $e) {
                // On DIT que la lecture a echoue, au lieu de rendre un null muet qui
                // se lit comme « cette variable n existe pas ».
                $lues[$nom] = 'illisible : '.$e->getMessage();
            }
        }

        return $lues;
    }
}
