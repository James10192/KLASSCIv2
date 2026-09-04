<?php

namespace Tests\Unit\Logging;

use App\Logging\CaviarderLeContexte;
use PHPUnit\Framework\TestCase;

/**
 * Le filtre de journaux reduit un vidage de requete a ses noms de champs — mais
 * il ne reconnait un vidage qu'a la CLE sous laquelle il est range. Une liste
 * fermee de noms de cles ne rattrape pas celle qu'on oubliera d'y mettre.
 *
 * C'est exactement ce qui est arrive : `data` manquait, alors que l'echec
 * d'enregistrement d'un reglage y versait `$request->all()`. Un reglage nomme
 * `whatsapp_token` partait donc en clair dans un fichier conserve quatorze
 * jours. Le filtre affichait un succes ; la fuite continuait.
 *
 * Ce test balaye les controleurs et fait echouer la suite des qu'un vidage
 * apparait sous une cle que le filtre ne connait pas. La liste reste fermee ;
 * elle n'est plus silencieuse.
 */
class VidagesDeRequeteCouvertsTest extends TestCase
{
    /**
     * Cles volontairement NON caviardees, avec la raison.
     *
     * `query` : la chaine de requete d'un ecran de liste. Les journaux de
     * diagnostic des recherches lentes existent pour dire QUEL filtre etait
     * lent — les vider les rendrait muets sur la seule chose qu'ils servent a
     * montrer. Une chaine de requete porte des filtres, pas de dossier ; et un
     * secret qui s'y glisserait (`?token=`) reste attrape par la liste SECRETS,
     * qui descend dans les tableaux imbriques.
     */
    private const GARDEES_SCIEMMENT = ['query'];

    public function test_toute_cle_portant_un_vidage_de_requete_est_couverte(): void
    {
        $couvertes = array_merge($this->clesCouvertes(), self::GARDEES_SCIEMMENT);
        $trouvees = $this->vidagesDansLesControleurs();

        $this->assertNotEmpty(
            $trouvees,
            'Le balayage ne trouve plus aucun vidage : le test ne prouverait plus rien.'
        );

        $decouvertes = [];

        foreach ($trouvees as $cle => $emplacements) {
            if (! in_array($cle, $couvertes, true)) {
                $decouvertes[$cle] = $emplacements;
            }
        }

        $this->assertSame(
            [],
            $decouvertes,
            "Un vidage de requete est journalise sous une cle que le filtre ignore.\n"
            . "Ajoutez-la a CaviarderLeContexte::VIDAGES, ou cessez de vider la requete ici :\n"
            . $this->rendre($decouvertes)
        );
    }

    /** @return array<int, string> */
    private function clesCouvertes(): array
    {
        $constante = new \ReflectionClassConstant(CaviarderLeContexte::class, 'VIDAGES');

        return $constante->getValue();
    }

    /**
     * Cherche `'cle' => $request->all()` et ses variantes dans les controleurs.
     *
     * @return array<string, array<int, string>> cle => emplacements
     */
    private function vidagesDansLesControleurs(): array
    {
        $racine = __DIR__ . '/../../../app/Http/Controllers';
        $trouves = [];

        $fichiers = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($racine, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($fichiers as $fichier) {
            if (! $fichier->isFile() || $fichier->getExtension() !== 'php') {
                continue;
            }

            $lignes = file($fichier->getPathname());

            foreach ($lignes as $numero => $ligne) {
                // `'quelque_chose' => $request->all()` — et les formes voisines
                // qui emportent autant : ->input() sans argument, ->post(),
                // ->query() sans argument, ->headers->all().
                $motif = '/[\'"]([A-Za-z_][A-Za-z0-9_]*)[\'"]\s*=>\s*\$request'
                    . '(?:->all\(\)|->input\(\)|->post\(\)|->query\(\)|->headers->all\(\))/';

                if (preg_match($motif, $ligne, $trouve)) {
                    $chemin = str_replace($racine . '/', '', $fichier->getPathname());
                    $trouves[$trouve[1]][] = $chemin . ':' . ($numero + 1);
                }
            }
        }

        return $trouves;
    }

    /** @param array<string, array<int, string>> $decouvertes */
    private function rendre(array $decouvertes): string
    {
        $lignes = [];

        foreach ($decouvertes as $cle => $emplacements) {
            $lignes[] = "  '{$cle}' — " . implode(', ', $emplacements);
        }

        return implode("\n", $lignes);
    }
}
