<?php

namespace App\Services\Audit;

use App\Helpers\SettingsHelper;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Instantane des statistiques de la page d'audit.
 *
 * Les sept compteurs affiches en tete de /esbtp/audit etaient calcules a chaque
 * affichage, par sept COUNT(*) sur la table `audits`. Sur esbtp-abidjan la page
 * ne repondait plus du tout : plus de 199 secondes sans reponse.
 *
 * Un cache pose A LA DEMANDE ne pouvait pas la sauver — la requete est tuee
 * avant d'atteindre l'ecriture du cache, donc le cache ne se rechauffe jamais et
 * chaque visiteur repaie le prix complet. Le calcul est donc sorti du chemin web
 * : une commande planifiee (`audit:rafraichir-statistiques`) le fait et depose
 * le resultat ici, la page ne fait plus qu'une lecture.
 *
 * Le depot est un fichier JSON dans storage/app, PAS le cache applicatif : le
 * pilote de cache par defaut est `file` (sans verrou atomique), et surtout le
 * runbook de deploiement lance `cache:clear` a chaque mise en ligne. Un
 * instantane en cache serait donc efface a chaque deploiement et la page
 * afficherait « indisponible » jusqu'au prochain passage du planificateur.
 */
class AuditStatsSnapshot
{
    /** Frequence de recalcul par defaut, en minutes. */
    public const FREQUENCE_MINUTES_DEFAUT = 60;

    /**
     * Age au-dela duquel l'instantane est annonce comme perime, en minutes.
     *
     * Volontairement large devant la frequence de recalcul : une seule execution
     * manquee (planificateur en retard, deploiement en cours) ne doit pas faire
     * crier au loup.
     */
    public const PEREMPTION_MINUTES_DEFAUT = 180;

    public function __construct(private readonly ?string $chemin = null)
    {
    }

    public function chemin(): string
    {
        return $this->chemin ?? storage_path('app/audit/statistiques.json');
    }

    /**
     * Depose l'instantane. Ecriture via fichier temporaire puis renommage : un
     * lecteur ne doit jamais tomber sur un JSON a moitie ecrit.
     */
    public function ecrire(array $statistiques, ?CarbonInterface $calculeLe = null): void
    {
        $calculeLe = $calculeLe ?: Carbon::now();
        $chemin = $this->chemin();

        $dossier = dirname($chemin);
        if (! is_dir($dossier)) {
            mkdir($dossier, 0775, true);
        }

        $contenu = json_encode([
            'calcule_le' => $calculeLe->toIso8601String(),
            'statistiques' => $statistiques,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $temporaire = $chemin . '.' . getmypid() . '.tmp';
        file_put_contents($temporaire, $contenu);
        rename($temporaire, $chemin);
    }

    /**
     * Relit l'instantane. Renvoie null si rien n'a encore ete calcule ou si le
     * fichier est illisible : l'appelant doit alors annoncer « statistiques
     * indisponibles », JAMAIS relancer le calcul en synchrone.
     *
     * @return array{calcule_le: CarbonInterface, statistiques: array}|null
     */
    public function lire(): ?array
    {
        $chemin = $this->chemin();

        if (! is_file($chemin)) {
            return null;
        }

        $brut = @file_get_contents($chemin);
        if ($brut === false) {
            return null;
        }

        $donnees = json_decode($brut, true);
        if (! is_array($donnees) || ! isset($donnees['statistiques'], $donnees['calcule_le'])) {
            return null;
        }

        try {
            $calculeLe = Carbon::parse($donnees['calcule_le']);
        } catch (\Throwable) {
            return null;
        }

        return [
            'calcule_le' => $calculeLe,
            'statistiques' => (array) $donnees['statistiques'],
        ];
    }

    public static function estPerime(
        CarbonInterface $calculeLe,
        int $peremptionMinutes,
        ?CarbonInterface $maintenant = null
    ): bool {
        $maintenant = $maintenant ?: Carbon::now();

        return $calculeLe->diffInMinutes($maintenant, false) >= $peremptionMinutes;
    }

    /**
     * Reglable par instance : une ecole a fort volume peut vouloir un recalcul
     * horaire, une petite instance un recalcul quotidien. Le defaut reproduit un
     * rafraichissement horaire, le compromis le moins surprenant.
     */
    public static function frequenceMinutes(): int
    {
        return max(1, (int) SettingsHelper::get(
            'audit.stats.frequence_minutes',
            self::FREQUENCE_MINUTES_DEFAUT
        ));
    }

    public static function peremptionMinutes(): int
    {
        return max(1, (int) SettingsHelper::get(
            'audit.stats.peremption_minutes',
            self::PEREMPTION_MINUTES_DEFAUT
        ));
    }
}
