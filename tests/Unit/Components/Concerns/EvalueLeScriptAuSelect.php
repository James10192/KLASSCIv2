<?php

namespace Tests\Unit\Components\Concerns;

/**
 * Execute le VRAI script livre par <x-au-select>.
 *
 * Le script est extrait du composant Blade, jamais recopie : un test qui
 * recopierait la logique continuerait de passer apres qu'on l'ait cassee dans
 * le composant. Ce qui est verifie ici est exactement ce que recoit le
 * navigateur.
 */
trait EvalueLeScriptAuSelect
{
    private const COMPOSANT_AU_SELECT = __DIR__ . '/../../../../resources/views/components/au-select.blade.php';

    /** @var string|null */
    private static $scriptAuSelectCache;

    /**
     * Evalue `$programme` apres le script du composant et rend sa sortie
     * standard, debarrassee des espaces de bord.
     */
    protected function evalueAvecLeComposant(string $programme): string
    {
        $fichier = tempnam(sys_get_temp_dir(), 'au-select-') . '.js';
        file_put_contents($fichier, $this->scriptDuComposant() . "\n" . $programme);

        try {
            $sortie = shell_exec(escapeshellarg($this->cheminNode()) . ' ' . escapeshellarg($fichier) . ' 2>&1');
        } finally {
            @unlink($fichier);
        }

        return trim((string) $sortie);
    }

    /**
     * Extrait le <script> pousse par le composant et le prepare pour Node :
     * le composant ecrit sur `window`, qui n'existe pas hors navigateur.
     */
    protected function scriptDuComposant(): string
    {
        if (self::$scriptAuSelectCache !== null) {
            return self::$scriptAuSelectCache;
        }

        $source = file_get_contents(self::COMPOSANT_AU_SELECT);
        $this->assertNotFalse($source, 'Composant au-select introuvable.');

        $debut = strpos($source, "@push('scripts')");
        $this->assertNotFalse($debut, 'Le composant ne pousse plus de script — le test doit etre revu.');

        $ouverture = strpos($source, '<script>', $debut);
        $this->assertNotFalse($ouverture, 'Balise <script> introuvable dans le composant.');

        $fermeture = strpos($source, '</script>', (int) $ouverture);
        $this->assertNotFalse($fermeture, 'Balise de fermeture introuvable dans le composant.');

        $js = substr($source, (int) $ouverture + 8, (int) $fermeture - (int) $ouverture - 8);

        // La detection d'ancetre et le deplacement du menu vivent dans un
        // partiel partage avec x-au-user-picker : on l'inline comme Blade.
        $js = preg_replace_callback(
            "/@include\\('([^']+)'\\)/",
            function (array $m): string {
                $chemin = __DIR__ . '/../../../../resources/views/' . str_replace('.', '/', $m[1]) . '.blade.php';
                $contenu = file_get_contents($chemin);
                $this->assertNotFalse($contenu, 'Partiel inclus introuvable : ' . $m[1]);

                return (string) $contenu;
            },
            $js
        );

        return self::$scriptAuSelectCache = "var window = globalThis;\n" . $js;
    }

    protected function cheminNode(): ?string
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
