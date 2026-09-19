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
 *
 * SA PORTÉE EST LIMITÉE, ET C'EST ASSUMÉ. Il cherche cinq formes littérales
 * dans `app/` et `database/`. Lui échapperaient : une écriture relationnelle
 * (`->resultatsMatieres()->create(...)`), un `DB::table('esbtp_resultats_matieres')`,
 * ou un alias d'import. Ce test est un garde-fou, pas une preuve d'exhaustivité.
 * Ne le lisez pas comme « il n'y a qu'une porte ».
 *
 * UNE VERSION ANTÉRIEURE AFFIRMAIT ICI « Aucune n'existe aujourd'hui — vérifié ».
 * C'était faux, et falsifiable en une commande :
 *
 *     grep -rn "ESBTPResultatMatiere::create(" database/
 *     → database/seeders/old/ESBTPBulletinSeeder.php
 *
 * Ce fichier est versionné et non gitignoré. Le dégât pratique est nul — c'est un
 * seeder mort, jamais joué —, mais le dégât de méthode ne l'est pas : un fichier
 * dont la fonction est d'être cru ne peut pas affirmer sans avoir mesuré. C'est le
 * défaut que ce chantier a corrigé partout ailleurs, reproduit dans son propre
 * garde-fou. Le scan couvre donc `database/`, et la seule exemption est NOMMÉE
 * ci-dessous plutôt que couverte par une phrase.
 */
class EcritureDesLignesDeBulletinTest extends TestCase
{
    /**
     * La seule écriture tolérée hors du modèle, et pourquoi.
     *
     * `database/seeders/old/` est un dossier de seeders retirés du service. Le
     * corriger demanderait de rejouer un code que personne n'exécute ; le
     * supprimer est une décision qui ne relève pas d'un correctif de bulletin.
     * Il est donc exempté EXPLICITEMENT — pour qu'un lecteur voie l'exception au
     * lieu de croire qu'il n'y en a pas.
     */
    private const EXEMPTE = 'database/seeders/old/';

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

            if (str_contains(str_replace('\\', '/', $fichier), self::EXEMPTE)) {
                continue;
            }

            $contenu = file_get_contents($fichier) ?: '';

            foreach (self::INTERDIT as $motif) {
                if (str_contains($contenu, $motif)) {
                    $coupables[] = $this->cheminRelatif($fichier) . ' → ' . $motif;
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

    /**
     * Chemin lisible dans le rapport d'echec.
     *
     * L'ancienne version coupait sur `'app/'` : sur un fichier de `database/`,
     * `strpos` rendait `false`, `substr` repartait de zero et le rapport
     * affichait le chemin absolu de la machine.
     */
    private function cheminRelatif(string $fichier): string
    {
        $racine = realpath(__DIR__ . '/../../../');

        return $racine !== false && str_starts_with($fichier, $racine)
            ? ltrim(substr($fichier, strlen($racine)), '/\\')
            : $fichier;
    }

    /** @return list<string> */
    private function fichiersPhpDeLApplication(): array
    {
        $fichiers = [];

        foreach (['app', 'database'] as $dossier) {
            $racine = realpath(__DIR__ . '/../../../' . $dossier);

            if ($racine === false) {
                continue;
            }

            $iterateur = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));

            foreach ($iterateur as $entree) {
                if ($entree->isFile() && $entree->getExtension() === 'php') {
                    $fichiers[] = $entree->getRealPath();
                }
            }
        }

        return $fichiers;
    }
}
