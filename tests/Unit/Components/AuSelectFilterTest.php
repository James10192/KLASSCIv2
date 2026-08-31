<?php

namespace Tests\Unit\Components;

use PHPUnit\Framework\TestCase;

/**
 * Verifie le filtrage local du composant <x-au-select>.
 *
 * Le test execute le VRAI code livre : il extrait le <script> du composant
 * Blade et l'evalue sous Node. Rien n'est recopie ici, donc le test ne peut
 * pas diverger silencieusement de ce que voit l'utilisateur.
 *
 * Ce qu'il protege : un etudiant s'affiche « 14196637U - YAO FRANCK PARFAIT
 * KONE » et la caissiere tape « KONE YAO PARFAIT ». Le serveur repond
 * correctement (recherche mot a mot), mais le composant refiltrait la reponse
 * avec un test de sous-chaine contigue et la jetait. La liste se vidait.
 */
class AuSelectFilterTest extends TestCase
{
    private const COMPOSANT = __DIR__ . '/../../../resources/views/components/au-select.blade.php';

    /** @var string|null */
    private static $scriptCache;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->cheminNode() === null) {
            $this->markTestSkipped('Node introuvable : impossible d\'executer le filtre du composant.');
        }
    }

    /**
     * @dataProvider correspondances
     */
    public function test_le_filtre_local_repond_comme_attendu(
        string $libelle,
        string $saisie,
        bool $attendu,
        string $pourquoi
    ): void {
        $this->assertSame(
            $attendu,
            $this->correspond($libelle, $saisie),
            $pourquoi
        );
    }

    public static function correspondances(): array
    {
        $etudiant = '14196637U - YAO FRANCK PARFAIT KONE';

        return [
            // Le cas signale par le fondateur : trois mots, aucun contigu.
            'mots dans le desordre' => [$etudiant, 'KONE YAO PARFAIT', true,
                'Chaque mot est present dans le libelle : la correspondance doit tenir quel que soit l\'ordre.'],
            'deux mots separes par un troisieme' => [$etudiant, 'YAO PARFAIT', true,
                'FRANCK se glisse entre YAO et PARFAIT ; un test de sous-chaine contigue echouait ici.'],
            'ordre inverse' => [$etudiant, 'PARFAIT YAO', true,
                'L\'ordre de saisie ne doit rien changer.'],
            'nom seul, minuscules' => [$etudiant, 'kone', true,
                'La casse ne doit pas compter.'],
            'matricule' => [$etudiant, '14196637U', true,
                'Le matricule fait partie du libelle affiche.'],
            'espaces multiples' => [$etudiant, '  KONE    PARFAIT  ', true,
                'Les espaces surnumeraires ne doivent pas produire de terme vide qui fausse le test.'],

            // Le filtre doit rester selectif : tous les mots sont exiges.
            'un mot absent' => [$etudiant, 'KONE MARTIN', false,
                'MARTIN est absent : la correspondance doit echouer, sinon le filtre ne filtre plus.'],
            'aucun mot present' => [$etudiant, 'DUPONT', false,
                'Aucun terme ne correspond.'],

            // Listes statiques (filtres, formulaires) : aucun resultat perdu.
            'liste statique, mot unique' => ['Genie Civil', 'civil', true,
                'Une saisie d\'un seul mot se comporte comme l\'ancien test de sous-chaine.'],
            'liste statique, sous-chaine contigue' => ['Genie Civil', 'nie civ', true,
                'Ce qui correspondait avant doit continuer de correspondre.'],
            'liste statique, sans rapport' => ['Genie Civil', 'informatique', false,
                'Une saisie sans rapport ne doit rien renvoyer.'],

            // Bords.
            'saisie vide' => [$etudiant, '', true,
                'Sans saisie, tout correspond — le menu affiche la liste entiere.'],
            'saisie espaces seuls' => [$etudiant, '   ', true,
                'Une saisie qui ne contient aucun mot equivaut a une saisie vide.'],
            'libelle vide' => ['', 'KONE', false,
                'Un libelle vide ne peut correspondre a un terme.'],
        ];
    }

    /**
     * Evalue le comparateur reellement livre dans le composant.
     */
    private function correspond(string $libelle, string $saisie): bool
    {
        $programme = $this->scriptDuComposant() . "\n"
            . 'process.stdout.write(window.auSelectMatchesQuery('
            . json_encode($libelle, JSON_UNESCAPED_UNICODE) . ', '
            . json_encode($saisie, JSON_UNESCAPED_UNICODE) . ') ? "1" : "0");';

        $fichier = tempnam(sys_get_temp_dir(), 'au-select-') . '.js';
        file_put_contents($fichier, $programme);

        try {
            $sortie = shell_exec(escapeshellarg($this->cheminNode()) . ' ' . escapeshellarg($fichier) . ' 2>&1');
        } finally {
            @unlink($fichier);
        }

        $this->assertContains(
            trim((string) $sortie),
            ['0', '1'],
            'Le comparateur du composant n\'a pas pu etre evalue. Sortie Node : ' . $sortie
        );

        return trim((string) $sortie) === '1';
    }

    /**
     * Extrait le <script> pousse par le composant, et le prepare pour Node :
     * le composant ecrit sur `window`, qui n'existe pas hors navigateur.
     */
    private function scriptDuComposant(): string
    {
        if (self::$scriptCache !== null) {
            return self::$scriptCache;
        }

        $source = file_get_contents(self::COMPOSANT);
        $this->assertNotFalse($source, 'Composant au-select introuvable.');

        $debut = strpos($source, "@push('scripts')");
        $this->assertNotFalse($debut, 'Le composant ne pousse plus de script — le test doit etre revu.');

        $ouverture = strpos($source, '<script>', $debut);
        $fermeture = strpos($source, '</script>', (int) $ouverture);
        $this->assertNotFalse($ouverture, 'Balise <script> introuvable dans le composant.');
        $this->assertNotFalse($fermeture, 'Balise de fermeture introuvable dans le composant.');

        $js = substr($source, (int) $ouverture + 8, (int) $fermeture - (int) $ouverture - 8);

        $this->assertStringContainsString(
            'auSelectMatchesQuery',
            $js,
            'Le comparateur a disparu du composant : le filtrage local n\'est plus teste.'
        );

        return self::$scriptCache = "var window = globalThis;\n" . $js;
    }

    private function cheminNode(): ?string
    {
        static $chemin = false;

        if ($chemin !== false) {
            return $chemin;
        }

        $sonde = shell_exec('node --version 2>&1');

        return $chemin = (is_string($sonde) && preg_match('/^v\d+\./', trim($sonde)) === 1)
            ? 'node'
            : null;
    }
}
