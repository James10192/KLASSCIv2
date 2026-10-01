<?php

namespace App\Domain\Bulletins\Taches;

use App\Domain\Support\Services\AdresseJoignable;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
    /**
     * Au-delà, une tâche active que rien n'a fait bouger est dite « en pause » :
     * la planification ne tourne plus, ou une étape se fait tuer.
     */
    public const PAUSE_MINUTES = 5;

    /**
     * @param  User|null  $pour  la personne qui regarde (par défaut, la connectée) :
     *                           c'est son adresse qu'on propose de confirmer
     * @return array<string, mixed>
     */
    public static function etat(BulletinTache $tache, ?User $pour = null): array
    {
        $resultat = $tache->resultat ?? [];
        $taille = ExecuteurTachesBulletins::TAILLE_TRANCHE;
        $tranches = max(1, (int) ceil($tache->total / $taille));

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
            // Une seule source pour la taille des tranches : la page ne la recopie pas.
            'taille' => $taille,
            'tranche' => min(intdiv($tache->position, $taille) + 1, $tranches),
            'tranches' => $tranches,
            'en_pause' => self::estEnPause($tache),
            'message' => $tache->message,
            'url' => self::lienResultat($tache),
            'libelle_lien' => self::libelleLien($tache),
            'classe_id' => $tache->classe_id,
            'annee_universitaire_id' => $tache->annee_universitaire_id,
            'periode' => $tache->periode,
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
        ] + AdresseJoignable::etat($pour ?? auth()->user());
    }

    public static function estEnPause(BulletinTache $tache): bool
    {
        return ! $tache->estFinale()
            && $tache->updated_at !== null
            && $tache->updated_at->lt(now()->subMinutes(self::PAUSE_MINUTES));
    }

    /** Le libellé du lien de fin, le même pour le toast, la cloche et l'e-mail. */
    public static function libelleLien(BulletinTache $tache): ?string
    {
        if (! $tache->estFinale()) {
            return null;
        }

        return match (true) {
            $tache->statut !== BulletinTache::TERMINEE && $tache->type === BulletinTache::TYPE_GENERATION => 'Relancer la génération',
            $tache->statut !== BulletinTache::TERMINEE => 'Revenir aux bulletins',
            $tache->type === BulletinTache::TYPE_GENERATION => 'Voir les bulletins',
            $tache->parametre('mode') === 'apercu' => "Ouvrir l'aperçu",
            default => 'Télécharger le PDF',
        };
    }

    /**
     * Où mène la notification : le document pour un PDF prêt, la liste
     * filtrée pour une génération. Une tâche échouée ramène à l'écran d'où
     * elle est partie, pré-rempli, pour la relancer.
     *
     * Toujours un chemin relatif : la cloche et le toast restent sur l'hôte
     * qui les affiche (une école servie sous deux noms ne perd pas ses liens).
     * Seul l'e-mail le rend absolu.
     */
    public static function lienResultat(BulletinTache $tache): ?string
    {
        if ($tache->type === BulletinTache::TYPE_EXPORT) {
            if ($tache->statut === BulletinTache::TERMINEE) {
                return route('esbtp.bulletins.taches.fichier', [
                    'tache' => $tache->id,
                    'mode' => $tache->parametre('mode', 'telechargement'),
                ], false);
            }

            return $tache->estFinale()
                ? route('esbtp.bulletins.index', $tache->parametre('filtres', []), false)
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
            ]), false)
            // L'écran de génération lit ces paramètres et se remet sur le périmètre.
            : route('esbtp.bulletins.select', array_filter([
                'classe_id' => $tache->classe_id,
                'annee_universitaire_id' => $tache->annee_universitaire_id,
                'periode' => $tache->periode,
            ]), false);
    }

    /**
     * Les tâches que le toast global doit connaître : celles qui tournent, et
     * celles qui se sont terminées sans que la personne les ait vues — qu'elle
     * les ait lancées ou rejointes.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pourUtilisateur(?User $user): array
    {
        if ($user === null || ! self::tableDisponible()) {
            return [];
        }

        // Au-delà, le toast ne sert plus : la cloche et l'e-mail ont pris le relais.
        $recentes = now()->subDays(3);
        $abonnements = fn (bool $nonVues) => DB::table(BulletinTache::TABLE_ABONNES)
            ->select('tache_id')
            ->where('user_id', $user->id)
            ->when($nonVues, fn ($q) => $q->whereNull('vue_at'));

        return BulletinTache::query()
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('user_id', $user->id)
                    ->where(fn ($q) => $q->whereIn('statut', BulletinTache::ACTIFS)
                        ->orWhere(fn ($q) => $q->whereIn('statut', BulletinTache::FINAUX)
                            ->whereNull('vue_at')
                            ->where('terminee_at', '>=', $recentes))))
                ->orWhere(fn ($q) => $q->whereIn('id', $abonnements(false))->whereIn('statut', BulletinTache::ACTIFS))
                ->orWhere(fn ($q) => $q->whereIn('id', $abonnements(true))
                    ->whereIn('statut', BulletinTache::FINAUX)
                    ->where('terminee_at', '>=', $recentes)))
            ->orderBy('id')
            ->limit(10)
            ->get()
            ->map(fn (BulletinTache $t) => self::etat($t, $user))
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
            return (bool) Cache::remember('bulletin_taches.tables', 600, fn () => Schema::hasTable('esbtp_bulletin_taches') && Schema::hasTable(BulletinTache::TABLE_ABONNES));
        } catch (\Throwable $e) {
            Log::warning('Suivi des tâches bulletins indisponible : '.$e->getMessage());

            return false;
        }
    }
}
