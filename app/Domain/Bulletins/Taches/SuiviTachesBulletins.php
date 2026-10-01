<?php

namespace App\Domain\Bulletins\Taches;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Ce que l'écran, la notification et l'e-mail disent d'une tâche.
 *
 * Une seule forme pour les trois : la page des bulletins, le toast global et
 * le courriel lisent les mêmes champs, donc annoncent la même chose.
 */
class SuiviTachesBulletins
{
    /** @return array<string, mixed> */
    public static function etat(BulletinTache $tache): array
    {
        $resultat = $tache->resultat ?? [];

        return [
            'id' => $tache->id,
            'type' => $tache->type,
            'mode' => $tache->parametre('mode'),
            'libelle' => $tache->libelle(),
            'statut' => $tache->statut,
            'finale' => $tache->estFinale(),
            'total' => $tache->total,
            'position' => $tache->position,
            'pourcent' => $tache->pourcent(),
            'message' => $tache->message,
            'url' => self::lienResultat($tache),
            'resultat' => $tache->type === BulletinTache::TYPE_GENERATION
                ? [
                    'created' => (int) ($resultat['created'] ?? 0),
                    'regenerated' => (int) ($resultat['regenerated'] ?? 0),
                    'skipped' => $resultat['skipped'] ?? [],
                    'blocking_errors' => $resultat['blocking_errors'] ?? [],
                    'errors' => $resultat['errors'] ?? [],
                ]
                : [
                    'rendus' => (int) ($resultat['rendus'] ?? 0),
                    'echecs' => count($resultat['echecs'] ?? []),
                ],
            'demarree_at' => optional($tache->demarree_at)->toIso8601String(),
            'terminee_at' => optional($tache->terminee_at)->toIso8601String(),
        ];
    }

    /**
     * Où mène la notification : le document pour un PDF prêt, la liste
     * filtrée pour une génération. Une tâche échouée ramène à l'écran d'où
     * elle est partie, pour la relancer.
     */
    public static function lienResultat(BulletinTache $tache): ?string
    {
        if ($tache->type === BulletinTache::TYPE_EXPORT) {
            if ($tache->statut === BulletinTache::TERMINEE) {
                return route('esbtp.bulletins.taches.fichier', [
                    'tache' => $tache->id,
                    'mode' => $tache->parametre('mode', 'telechargement'),
                ]);
            }

            return $tache->estFinale()
                ? route('esbtp.bulletins.index', $tache->parametre('filtres', []))
                : null;
        }

        if (! $tache->estFinale()) {
            return null;
        }

        return $tache->statut === BulletinTache::TERMINEE
            ? route('esbtp.bulletins.index', array_filter([
                'classe_id' => $tache->classe_id,
                'annee_universitaire_id' => $tache->annee_universitaire_id,
                'periode_id' => $tache->periode,
            ]))
            : route('esbtp.bulletins.select');
    }

    /**
     * Les tâches que le toast global doit connaître : celles qui tournent, et
     * celles qui se sont terminées sans que leur demandeur les ait vues.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pourUtilisateur(?User $user): array
    {
        if ($user === null || ! self::tableDisponible()) {
            return [];
        }

        return BulletinTache::query()
            ->where('user_id', $user->id)
            ->where(fn ($q) => $q->whereIn('statut', BulletinTache::ACTIFS)
                ->orWhere(fn ($q) => $q->whereIn('statut', BulletinTache::FINAUX)
                    ->whereNull('vue_at')
                    // Au-delà, le toast ne sert plus : la notification et
                    // l'e-mail ont pris le relais.
                    ->where('terminee_at', '>=', now()->subDays(3))))
            ->orderBy('id')
            ->limit(10)
            ->get()
            ->map(fn (BulletinTache $t) => self::etat($t))
            ->all();
    }

    /**
     * Le composant est rendu sur toutes les pages : sur une instance où la
     * migration n'est pas encore passée, il doit se taire, pas casser la page.
     * La réponse est gardée dix minutes pour ne pas interroger le schéma à
     * chaque affichage.
     */
    private static function tableDisponible(): bool
    {
        try {
            return (bool) Cache::remember('bulletin_taches.table', 600, fn () => Schema::hasTable('esbtp_bulletin_taches'));
        } catch (\Throwable $e) {
            Log::warning('Suivi des tâches bulletins indisponible : '.$e->getMessage());

            return false;
        }
    }
}
