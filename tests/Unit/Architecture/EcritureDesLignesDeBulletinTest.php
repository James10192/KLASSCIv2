<?php

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\TestCase;

/**
 * Une seule porte pour écrire une ligne de matière sur un bulletin.
 *
 * `esbtp_resultats_matieres` porte une clé unique `(bulletin_id, matiere_id)`
 * qui **ne contient pas `deleted_at`**. Une ligne supprimée en douceur occupe
 * donc la place tout en étant invisible aux requêtes ordinaires : créer par
 * `updateOrCreate` ou par `new` bute dessus en « Duplicate entry », et comme
 * la ligne fantôme ne disparaît jamais d'elle-même, le bulletin de cet
 * étudiant devient **définitivement** ingénérable.
 *
 * C'est arrivé en production (ESBTP Abidjan, bulletin 1171, matière 358), et
 * la cause profonde n'est pas le défaut lui-même : la table jumelle
 * `esbtp_resultats` avait reçu le même traitement quinze lignes plus haut dans
 * le même fichier, avec un commentaire qui le décrivait. Un correctif isolé
 * s'oublie ; un invariant vérifié, non.
 *
 * Ce test n'a besoin d'aucune base : il lit le dépôt.
 */
class EcritureDesLignesDeBulletinTest extends TestCase
{
    /** Ce que personne ne doit écrire hors du modèle. */
    private const INTERDIT = [
        'new ESBTPResultatMatiere',
        'ESBTPResultatMatiere::create(',
        'ESBTPResultatMatiere::updateOrCreate(',
        'ESBTPResultatMatiere::firstOrCreate(',
        'ESBTPResultatMatiere::insert(',
    ];

    public function test_aucun_site_n_ecrit_une_ligne_hors_du_point_d_entree_unique(): void
    {
        $modele = realpath(__DIR__ . '/../../../app/Models/ESBTPResultatMatiere.php');
        $coupables = [];

        foreach ($this->fichiersPhpDeLApplication() as $fichier) {
            if ($fichier === $modele) {
                continue;
            }

            $contenu = file_get_contents($fichier) ?: '';

            foreach (self::INTERDIT as $motif) {
                if (str_contains($contenu, $motif)) {
                    $coupables[] = substr($fichier, strpos($fichier, 'app/')) . ' → ' . $motif;
                }
            }
        }

        $this->assertSame(
            [],
            $coupables,
            "Une ligne de matière s'écrit par ESBTPResultatMatiere::poserSurLeBulletin(), qui "
            . "ressuscite la ligne supprimée au lieu d'en créer une seconde. Sites fautifs :\n  "
            . implode("\n  ", $coupables)
        );
    }

    /** Le point d'entrée doit exister, et lire les lignes supprimées. */
    public function test_le_point_d_entree_unique_lit_bien_les_lignes_supprimees(): void
    {
        $source = file_get_contents(__DIR__ . '/../../../app/Models/ESBTPResultatMatiere.php') ?: '';

        $this->assertStringContainsString(
            'public static function poserSurLeBulletin(',
            $source,
            'Le point d\'entrée a disparu : le test précédent ne garde plus rien.'
        );
        $this->assertStringContainsString(
            'withTrashed()',
            $source,
            'Sans withTrashed(), la ligne supprimée reste invisible et la clé unique la refuse.'
        );
    }

    /** @return list<string> */
    private function fichiersPhpDeLApplication(): array
    {
        $racine = realpath(__DIR__ . '/../../../app');
        $fichiers = [];

        $iterateur = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

        foreach ($iterateur as $entree) {
            if ($entree->isFile() && $entree->getExtension() === 'php') {
                $fichiers[] = $entree->getRealPath();
            }
        }

        return $fichiers;
    }
}
