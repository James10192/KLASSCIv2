<?php

namespace App\Console\Commands\Release;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

/**
 * Le flux de version que lit l'agent qui alimente klassci-landing.
 *
 * Le site public tirait ses nouveautes d'une relecture a la main du
 * CHANGELOG : quelqu'un devait y penser, et personne n'y pensait toutes les
 * semaines. Cette commande produit la meme information sous une forme qu'une
 * machine peut consommer, directement depuis l'historique — donc sans etape
 * humaine a oublier.
 *
 * Elle ne remplace pas le CHANGELOG : celui-ci raconte pour un humain, ce
 * fichier-ci decrit pour un programme. Les deux viennent des memes commits.
 */
class NotesDeVersionCommand extends Command
{
    protected $signature = 'release:notes
        {--depuis= : Reference de depart (tag, sha, branche). Defaut : le dernier tag, sinon 50 commits.}
        {--jusqua=HEAD : Reference d arrivee.}
        {--sortie= : Ecrire le JSON dans ce fichier plutot que sur la sortie standard.}
        {--tenant= : Code du tenant, repris tel quel dans le flux.}';

    protected $description = "Produit les notes de version en JSON depuis l'historique des commits";

    /**
     * Ce qu'une ecole voit changer, et ce qu'elle ne voit pas.
     *
     * L'ordre compte : c'est celui dans lequel les sections paraitront.
     */
    private const SECTIONS = [
        'feat' => ['titre' => 'Ajouts', 'visible' => true],
        'fix' => ['titre' => 'Corrections', 'visible' => true],
        'perf' => ['titre' => 'Améliorations', 'visible' => true],
        'refactor' => ['titre' => 'Interne', 'visible' => false],
        'docs' => ['titre' => 'Documentation', 'visible' => false],
        'test' => ['titre' => 'Tests', 'visible' => false],
        'chore' => ['titre' => 'Maintenance', 'visible' => false],
        'build' => ['titre' => 'Build', 'visible' => false],
        'ci' => ['titre' => 'Intégration continue', 'visible' => false],
        'style' => ['titre' => 'Style', 'visible' => false],
        'revert' => ['titre' => 'Retours arrière', 'visible' => true],
    ];

    public function handle(): int
    {
        $depuis = $this->option('depuis') ?: $this->dernierPointDeDepart();
        $jusqua = $this->option('jusqua') ?: 'HEAD';

        $commits = $this->lireLesCommits($depuis, $jusqua);

        if ($commits === []) {
            $this->warn("Aucun commit entre {$depuis} et {$jusqua}.");
        }

        $flux = [
            'genere_le' => now()->toIso8601String(),
            'depuis' => $depuis,
            'jusqua' => $this->resoudre($jusqua),
            'tenant' => $this->option('tenant'),
            'total' => count($commits),
            // Le saut de version se deduit des commits, il ne se decide pas :
            // une rupture ou un ajout ne se remarquent pas a l'oeil sur
            // cinquante lignes d'historique.
            'saut' => $this->sautDeVersion($commits),
            'sections' => $this->grouper($commits),
            'ruptures' => array_values(array_filter($commits, fn (array $c) => $c['rupture'])),
        ];

        $json = json_encode($flux, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($chemin = $this->option('sortie')) {
            file_put_contents($chemin, $json.PHP_EOL);
            $this->info("Écrit dans {$chemin} — {$flux['total']} commit(s), saut « {$flux['saut']} ».");

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lireLesCommits(string $depuis, string $jusqua): array
    {
        // %x1f et %x1e : separateurs qu'aucun message de commit ne contient,
        // contrairement au point-virgule ou a la tabulation.
        $format = '%H%x1f%h%x1f%an%x1f%aI%x1f%s%x1f%b%x1e';
        $sortie = $this->git(['log', "{$depuis}..{$jusqua}", "--format={$format}", '--no-merges']);

        $commits = [];

        foreach (array_filter(explode("\x1e", $sortie), fn ($bloc) => trim($bloc) !== '') as $bloc) {
            $champs = explode("\x1f", trim($bloc, "\n"));
            if (count($champs) < 5) {
                continue;
            }

            [$sha, $court, $auteur, $date, $sujet] = $champs;
            $corps = $champs[5] ?? '';

            if (! preg_match('/^(?<type>[a-z]+)(?:\((?<portee>[^)]+)\))?(?<bang>!)?: (?<titre>.+)$/', $sujet, $m)) {
                // Un commit hors convention n'est pas ignore : le taire
                // reviendrait a livrer des notes incompletes sans le dire.
                $commits[] = [
                    'sha' => $sha, 'sha_court' => $court, 'auteur' => $auteur, 'date' => $date,
                    'type' => 'inconnu', 'portee' => null, 'titre' => $sujet,
                    'rupture' => false, 'conventionnel' => false,
                ];

                continue;
            }

            $commits[] = [
                'sha' => $sha,
                'sha_court' => $court,
                'auteur' => $auteur,
                'date' => $date,
                'type' => $m['type'],
                'portee' => $m['portee'] ?: null,
                'titre' => $m['titre'],
                'rupture' => $m['bang'] === '!' || Str::contains($corps, 'BREAKING CHANGE'),
                'conventionnel' => true,
            ];
        }

        return $commits;
    }

    /**
     * @param  array<int, array<string, mixed>>  $commits
     * @return array<string, mixed>
     */
    private function grouper(array $commits): array
    {
        $sections = [];

        foreach ($commits as $commit) {
            $type = $commit['type'];
            $meta = self::SECTIONS[$type] ?? ['titre' => 'Autres', 'visible' => false];

            $sections[$type]['titre'] ??= $meta['titre'];
            $sections[$type]['visible_ecole'] ??= $meta['visible'];
            $sections[$type]['commits'][] = [
                'sha_court' => $commit['sha_court'],
                'portee' => $commit['portee'],
                'titre' => $commit['titre'],
                'rupture' => $commit['rupture'],
                'date' => $commit['date'],
            ];
        }

        // Remettre dans l'ordre declare : un JSON dont l'ordre depend de
        // l'historique se relit differemment a chaque generation.
        $ordonnees = [];
        foreach (array_keys(self::SECTIONS) as $type) {
            if (isset($sections[$type])) {
                $ordonnees[$type] = $sections[$type];
            }
        }
        foreach ($sections as $type => $section) {
            $ordonnees[$type] ??= $section;
        }

        return $ordonnees;
    }

    /**
     * @param  array<int, array<string, mixed>>  $commits
     */
    private function sautDeVersion(array $commits): string
    {
        foreach ($commits as $commit) {
            if ($commit['rupture']) {
                return 'majeur';
            }
        }

        foreach ($commits as $commit) {
            if ($commit['type'] === 'feat') {
                return 'mineur';
            }
        }

        return 'correctif';
    }

    private function dernierPointDeDepart(): string
    {
        $tag = trim($this->git(['describe', '--tags', '--abbrev=0']));

        return $tag !== '' ? $tag : trim($this->git(['rev-parse', 'HEAD~50']));
    }

    private function resoudre(string $reference): string
    {
        return trim($this->git(['rev-parse', '--short', $reference]));
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function git(array $arguments): string
    {
        $process = new Process(array_merge(['git'], $arguments), base_path());
        $process->run();

        return $process->isSuccessful() ? $process->getOutput() : '';
    }
}
