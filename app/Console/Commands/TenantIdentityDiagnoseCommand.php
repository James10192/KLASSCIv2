<?php

namespace App\Console\Commands;

use App\Domain\Exploitation\CoherenceIdentiteInstance;
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

        $anomalies = CoherenceIdentiteInstance::anomalies($code, $hoteApp, $base);

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
