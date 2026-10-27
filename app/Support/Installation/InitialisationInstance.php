<?php

namespace App\Support\Installation;

use App\Helpers\InstallationHelper;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * L'étape « Initialiser » de l'assistant : migrations, setup.php, nom de l'école.
 *
 * Elle dure plus d'une minute. Lancée deux fois en même temps (double clic,
 * relance après un délai dépassé côté navigateur ou proxy), la seconde butait
 * sur une table que la première venait de créer (« Table 'cache' already
 * exists »). D'où trois garanties :
 *  - un verrou de fichier : une seule initialisation à la fois, quel que soit
 *    le pilote de cache (il peut viser une table qui n'existe pas encore) ;
 *  - un état écrit sur disque, que l'écran interroge : la réponse HTTP peut
 *    être perdue, le résultat ne l'est pas ;
 *  - ignore_user_abort : une connexion coupée n'interrompt pas le travail.
 */
class InitialisationInstance
{
    public const EN_COURS = 'en_cours';
    public const REUSSIE = 'reussie';
    public const ECHEC = 'echec';

    /** Le verrou est rendu à la fin du processus, même s'il meurt. */
    private $verrou = null;

    /**
     * @return array{ok: bool, code: string, message: string, terminal: array}
     */
    public function lancer(string $nomEtablissement): array
    {
        if (! $this->prendreVerrou()) {
            return [
                'ok' => false,
                'code' => 'install.migration.en_cours',
                'message' => 'Une initialisation est déjà en cours. Patientez, l’écran se met à jour seul.',
                'terminal' => $this->etat()['terminal'],
            ];
        }

        ignore_user_abort(true);
        set_time_limit(900);
        $terminal = [];
        $this->ecrireEtat(self::EN_COURS, 'Initialisation en cours…', $terminal);

        try {
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            $terminal[] = 'Caches Laravel vidés.';
            $this->ecrireEtat(self::EN_COURS, 'Migrations en cours…', $terminal);

            Artisan::call('migrate', ['--force' => true]);
            $terminal[] = trim(Artisan::output()) ?: 'Migrations vérifiées.';
            $this->ecrireEtat(self::EN_COURS, 'Permissions, paramètres et seeders…', $terminal);

            $setup = $this->lancerSetup();
            $terminal = array_merge($terminal, $setup['output']);

            if (! $setup['ok']) {
                return $this->conclure(false, 'install.setup.failed', $setup['message'], $terminal);
            }

            $this->nommerEtablissement($nomEtablissement);
            InstallationHelper::flushCachedStatus();

            return $this->conclure(true, 'install.migration.completed', 'Migrations, permissions, paramètres et seeders terminés.', $terminal);
        } catch (\Throwable $e) {
            Log::error("Initialisation de l'instance en échec : " . $e->getMessage());
            $terminal[] = $e->getMessage();

            return $this->conclure(false, 'install.migration.exception', 'Une erreur est survenue pendant l’installation Laravel.', $terminal);
        } finally {
            $this->rendreVerrou();
        }
    }

    /**
     * Dernier état connu. Un « en cours » sans verrou tenu veut dire que le
     * processus est mort en route (serveur redémarré) : relancer est sûr.
     *
     * @return array{statut: ?string, message: string, terminal: array, maj: ?string}
     */
    public function etat(): array
    {
        $etat = json_decode((string) @file_get_contents($this->chemin('initialisation.json')), true);
        $etat = is_array($etat) ? $etat : ['statut' => null, 'message' => '', 'terminal' => [], 'maj' => null];

        if (($etat['statut'] ?? null) === self::EN_COURS && $this->verrouLibre()) {
            $etat['statut'] = self::ECHEC;
            $etat['message'] = 'L’initialisation précédente s’est interrompue. Relancez-la : elle reprend là où elle s’est arrêtée.';
        }

        return $etat;
    }

    private function conclure(bool $ok, string $code, string $message, array $terminal): array
    {
        $this->ecrireEtat($ok ? self::REUSSIE : self::ECHEC, $message, $terminal);

        return ['ok' => $ok, 'code' => $code, 'message' => $message, 'terminal' => $terminal];
    }

    private function ecrireEtat(string $statut, string $message, array $terminal): void
    {
        File::ensureDirectoryExists(dirname($this->chemin('initialisation.json')));
        File::put($this->chemin('initialisation.json'), json_encode([
            'statut' => $statut,
            'message' => $message,
            'terminal' => array_values($terminal),
            'maj' => now()->toIso8601String(),
        ], JSON_UNESCAPED_UNICODE));
    }

    private function prendreVerrou(): bool
    {
        File::ensureDirectoryExists(dirname($this->chemin('initialisation.lock')));
        $this->verrou = fopen($this->chemin('initialisation.lock'), 'c');

        if ($this->verrou && flock($this->verrou, LOCK_EX | LOCK_NB)) {
            return true;
        }

        $this->rendreVerrou();

        return false;
    }

    private function rendreVerrou(): void
    {
        if ($this->verrou) {
            flock($this->verrou, LOCK_UN);
            fclose($this->verrou);
            $this->verrou = null;
        }
    }

    private function verrouLibre(): bool
    {
        $f = @fopen($this->chemin('initialisation.lock'), 'c');
        if (! $f) {
            return true;
        }
        $libre = flock($f, LOCK_EX | LOCK_NB);
        if ($libre) {
            flock($f, LOCK_UN);
        }
        fclose($f);

        return $libre;
    }

    /**
     * Une initialisation tourne-t-elle en ce moment ? Le planificateur le
     * demande : lancé pendant les migrations, il trouvait `users` mais pas
     * les tables suivantes, et chaque tâche échouait.
     */
    public function enCours(): bool
    {
        return is_file($this->chemin('initialisation.lock')) && ! $this->verrouLibre();
    }

    /**
     * Dans storage/instance : le seul dossier partagé entre l'application et
     * le planificateur sous Docker, et absent de l'archive remise à l'école.
     */
    private function chemin(string $fichier): string
    {
        return storage_path('instance/installation/' . $fichier);
    }

    private function lancerSetup(): array
    {
        $script = base_path('setup.php');

        if (! File::exists($script)) {
            return ['ok' => false, 'message' => 'Le fichier setup.php est introuvable à la racine du projet.', 'output' => ['setup.php introuvable.']];
        }

        $output = [];
        $exitCode = 0;
        exec(escapeshellarg($this->binairePhpEnLigneDeCommande()) . ' ' . escapeshellarg($script) . ' --force 2>&1', $output, $exitCode);

        return [
            'ok' => $exitCode === 0,
            'message' => $exitCode === 0
                ? 'Initialisation setup.php terminée.'
                : 'setup.php a échoué. Consultez la sortie terminal et corrigez le point bloquant.',
            'output' => array_values(array_filter(array_map(
                fn ($line) => trim(preg_replace('/\x1b\[[0-9;]*m/', '', (string) $line)),
                $output
            ))),
        ];
    }

    /**
     * Le PHP « en ligne de commande » qui doit exécuter setup.php.
     *
     * Sous PHP-FPM ou LiteSpeed, PHP_BINARY désigne le binaire du serveur
     * (php-fpm, lsphp), qui ne sait pas exécuter un script : setup.php
     * échouait alors à coup sûr. On prend le `php` installé à côté, et on
     * ne retombe sur le PATH que s'il n'existe pas.
     */
    private function binairePhpEnLigneDeCommande(): string
    {
        if (PHP_SAPI === 'cli') {
            return PHP_BINARY;
        }

        $voisin = PHP_BINDIR . DIRECTORY_SEPARATOR . 'php';

        return is_executable($voisin) ? $voisin : 'php';
    }

    /**
     * Donne à l'école le nom saisi dans l'assistant.
     *
     * Les paramètres sont semés avec le nom neutre « KLASSCI » (celui de
     * SettingsHelper::initializeDefaults) : seul ce nom, ou une valeur vide,
     * est remplacé. Un nom déjà réglé reste intact.
     */
    private function nommerEtablissement(string $nom): void
    {
        $nom = trim($nom);
        $reglage = $nom === '' ? null : Setting::where('key', 'school_name')->first();

        if ($reglage && in_array((string) $reglage->value, ['', 'KLASSCI'], true)) {
            $reglage->value = $nom;
            $reglage->save();
        }
    }
}
