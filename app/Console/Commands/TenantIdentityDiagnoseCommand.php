<?php

namespace App\Console\Commands;

use App\Helpers\SettingsHelper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * L'instance sait-elle qui elle est ?
 *
 * `TENANT_CODE` n'est pas une étiquette : c'est l'identité que l'instance
 * donne d'elle-même partout où elle parle au reste du système. Une valeur
 * fausse ne provoque aucune erreur — l'application démarre, sert ses pages,
 * et se trompe en silence :
 *
 * 1. **Le paywall interroge les quotas d'un autre établissement.** Le
 *    middleware appelle `MASTER_API_URL/tenants/{TENANT_CODE}/limits`. Une
 *    instance qui se déclare sous le code d'une autre est mesurée contre les
 *    quotas de celle-là : elle se retrouve bloquée quand l'autre atteint sa
 *    limite, et inversement.
 * 2. **Les procès-verbaux de délibération portent le mauvais code.** La
 *    séquence `PV-{ANNÉE}-{TENANT_CODE}-{NNNN}` est tenue dans la base de
 *    chaque instance. Deux instances qui partagent un code émettent donc, sans
 *    jamais se croiser, des documents officiels numérotés à l'identique. Sur
 *    une pièce que l'établissement archive dix ans et qu'un étudiant peut
 *    produire à l'appui d'un recours, c'est le défaut le plus coûteux de la
 *    liste.
 * 3. Les journaux de notification, l'invalidation du cache de groupe et les
 *    références de planification d'examens sont étiquetés au nom d'un autre.
 *
 * Ce défaut a été observé : l'instance servie par `usat.klassci.com`
 * déclarait `esbtp-abidjan` sur son point d'entrée public, très probablement
 * parce que son `.env` a été recopié depuis celui d'Abidjan lors de sa mise en
 * service. Rien dans l'application ne le signalait.
 *
 * Ce contrôle compare donc ce que l'instance dit d'elle-même à ce que
 * l'infrastructure dit d'elle. Il ne corrige rien : le remède est une ligne du
 * `.env` sur le serveur, et il n'appartient pas à du code de la réécrire.
 *
 * Usage : php artisan tenant:verifier-identite [--json]
 */
class TenantIdentityDiagnoseCommand extends Command
{
    protected $signature = 'tenant:verifier-identite {--json : Sortie JSON, pour le CLI distant}';

    protected $description = "Vérifie que TENANT_CODE concorde avec l'hôte, la base et le nom de l'établissement";

    /**
     * Les instances dont l'hôte ne porte légitimement pas le code du tenant.
     *
     * `rostan` est servi par `islg.klassci.com` : l'établissement a changé de
     * nom commercial après sa mise en service. C'est la seule divergence
     * connue, et elle est déclarée ici plutôt que devinée — une exception
     * écrite se relit, une heuristique se contourne toute seule.
     */
    private const HOTES_ATTENDUS = [
        'rostan' => 'islg',
    ];

    public function handle(): int
    {
        $rapport = $this->construireRapport();

        if ($this->option('json')) {
            $this->line(json_encode($rapport, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $rapport['coherent'] ? self::SUCCESS : self::FAILURE;
        }

        $this->afficher($rapport);

        return $rapport['coherent'] ? self::SUCCESS : self::FAILURE;
    }

    private function construireRapport(): array
    {
        $code = strtolower(trim((string) config('app.tenant_code', '')));
        $hoteApp = $this->hote((string) config('app.url', ''));
        $base = (string) DB::connection()->getDatabaseName();
        $nomEcole = (string) SettingsHelper::get('school_name', '');

        $anomalies = [];

        if ($code === '' || $code === 'default') {
            $anomalies[] = [
                'clef' => 'code_absent',
                'message' => "TENANT_CODE n'est pas défini : l'instance se présentera partout sous « default ».",
            ];
        }

        // L'hôte doit contenir le code, ou celui qui est déclaré pour lui.
        $attendu = self::HOTES_ATTENDUS[$code] ?? $code;

        if ($code !== '' && $hoteApp !== '' && ! str_contains($hoteApp, $attendu)) {
            $anomalies[] = [
                'clef' => 'hote_divergent',
                'message' => "TENANT_CODE vaut « {$code} » mais APP_URL sert « {$hoteApp} ». "
                    . "Si l'instance a été mise en service en recopiant le .env d'un autre établissement, "
                    . "c'est TENANT_CODE qui est resté celui de l'autre.",
            ];
        }

        // La base porte presque toujours le code, avec des tirets bas.
        $codeBase = str_replace('-', '_', $code);

        if ($code !== '' && $base !== '' && ! str_contains($base, $codeBase)) {
            $anomalies[] = [
                'clef' => 'base_divergente',
                'message' => "TENANT_CODE vaut « {$code} » mais la base connectée est « {$base} ». "
                    . 'À vérifier : une instance branchée sur la base d\'un autre établissement '
                    . 'sert les données de celui-là.',
            ];
        }

        return [
            'tenant_code' => $code,
            'hote_app_url' => $hoteApp,
            'base_de_donnees' => $base,
            'nom_etablissement' => $nomEcole,
            'sequence_pv' => $code === ''
                ? null
                : sprintf('PV-{ANNÉE}-%s-{NNNN}', $code),
            'quotas_interroges' => $code === ''
                ? null
                : rtrim((string) config('services.master.api_url', ''), '/')
                    . '/tenants/' . $code . '/limits',
            'anomalies' => $anomalies,
            'coherent' => $anomalies === [],
        ];
    }

    private function hote(string $url): string
    {
        $hote = parse_url($url, PHP_URL_HOST);

        return is_string($hote) ? strtolower($hote) : '';
    }

    private function afficher(array $rapport): void
    {
        $this->newLine();
        $this->line("  Identité de l'instance");
        $this->line('  ' . str_repeat('-', 40));

        $this->line('    TENANT_CODE        : ' . ($rapport['tenant_code'] ?: '(vide)'));
        $this->line('    APP_URL sert       : ' . ($rapport['hote_app_url'] ?: '(vide)'));
        $this->line('    Base de données    : ' . ($rapport['base_de_donnees'] ?: '(vide)'));
        $this->line('    Établissement      : ' . ($rapport['nom_etablissement'] ?: '(non réglé)'));
        $this->newLine();
        $this->line('    Les PV seront numérotés   ' . ($rapport['sequence_pv'] ?? '—'));
        $this->line('    Les quotas sont lus sur   ' . ($rapport['quotas_interroges'] ?? '—'));
        $this->newLine();

        if ($rapport['coherent']) {
            $this->info("  L'instance se déclare de façon cohérente.");
            $this->newLine();

            return;
        }

        foreach ($rapport['anomalies'] as $anomalie) {
            $this->error('  X  ' . $anomalie['message']);
            $this->newLine();
        }

        $this->warn('  Corriger TENANT_CODE dans le .env du serveur, puis :');
        $this->warn('    php artisan config:clear && php artisan config:cache');
        $this->newLine();
    }
}
