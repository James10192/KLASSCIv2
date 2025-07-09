<?php

namespace App\Listeners;

use App\Events\KPIsCalcules;
use App\Services\NotificationService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MettreAJourDashboard implements ShouldQueue
{
    use InteractsWithQueue;

    protected $notificationService;

    /**
     * Create the event listener.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Handle the event.
     */
    public function handle(KPIsCalcules $event): void
    {
        try {
            Log::info("Mise à jour dashboard suite au calcul des KPIs", [
                'periode' => $event->periode,
                'annee_universitaire' => $event->anneeUniversitaireId
            ]);

            // Mettre à jour le cache des KPIs pour un accès rapide
            $this->mettreAJourCacheKPIs($event);

            // Vérifier si des seuils sont atteints et déclencher des alertes
            $this->verifierSeuils($event);

            // Notifier les utilisateurs connectés au dashboard en temps réel
            $this->notifierUtilisateursDashboard($event);

            Log::info("Dashboard mis à jour avec succès", [
                'periode' => $event->periode,
                'kpis_count' => count($event->kpis)
            ]);

        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du dashboard", [
                'periode' => $event->periode,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mettre à jour le cache des KPIs
     */
    private function mettreAJourCacheKPIs($event): void
    {
        // Utiliser les nouveaux stores de cache spécialisés
        $cacheKey = "kpis_{$event->periode}_{$event->anneeUniversitaireId}";

        // Cache dans le store spécialisé pour les KPIs
        Cache::store('comptabilite_kpis')->put($cacheKey, [
            'kpis' => $event->kpis,
            'periode' => $event->periode,
            'date_calcul' => $event->dateCalcul,
            'last_updated' => now()
        ], now()->addHours(24));

        // Cache global pour le dashboard principal avec TTL plus court
        Cache::store('dashboard_queries')->put('dashboard_kpis_last_update', now(), now()->addMinutes(15));

        // Invalider le cache dashboard principal pour forcer la régénération
        Cache::store('dashboard_queries')->forget('dashboard_main');

        Log::debug("Cache KPIs mis à jour avec nouveau système", [
            'cache_key' => $cacheKey,
            'store' => 'comptabilite_kpis'
        ]);

        // Déclencher l'invalidation intelligente via le service
        try {
            $comptabiliteService = app(\App\Services\ComptabiliteService::class);
            $comptabiliteService->invalidateCache('dashboard', $event->anneeUniversitaireId);
        } catch (\Exception $e) {
            Log::warning("Erreur invalidation cache via service", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Vérifier les seuils et déclencher des alertes
     */
    private function verifierSeuils($event): void
    {
        $kpis = $event->kpis;

        // Seuil critique: Résultat net négatif > 1M FCFA
        if (isset($kpis['resultat_net']) && $kpis['resultat_net'] < -1000000) {
            event(new \App\Events\SeuilAtteint(
                'Résultat Net',
                $kpis['resultat_net'],
                -1000000,
                "Le résultat net est critique: " . number_format($kpis['resultat_net']) . " FCFA",
                'critique'
            ));
        }

        // Seuil warning: Taux de recouvrement < 70%
        if (isset($kpis['taux_recouvrement']) && $kpis['taux_recouvrement'] < 70) {
            event(new \App\Events\SeuilAtteint(
                'Taux de Recouvrement',
                $kpis['taux_recouvrement'],
                70,
                "Le taux de recouvrement est faible: " . round($kpis['taux_recouvrement'], 2) . "%",
                'warning'
            ));
        }

        // Seuil info: Croissance des recettes > 10%
        if (isset($kpis['croissance_recettes']) && $kpis['croissance_recettes'] > 10) {
            event(new \App\Events\SeuilAtteint(
                'Croissance Recettes',
                $kpis['croissance_recettes'],
                10,
                "Excellente croissance des recettes: +" . round($kpis['croissance_recettes'], 2) . "%",
                'info'
            ));
        }
    }

    /**
     * Notifier les utilisateurs du dashboard
     */
    private function notifierUtilisateursDashboard($event): void
    {
        $utilisateursDashboard = User::role(['comptable', 'directeur', 'superAdmin'])->get();

        $message = "Les KPIs de la période '{$event->periode}' ont été recalculés.";

        if (isset($event->kpis['total_recettes'])) {
            $message .= " Recettes: " . number_format($event->kpis['total_recettes']) . " FCFA";
        }

        if (isset($event->kpis['resultat_net'])) {
            $message .= " | Résultat: " . number_format($event->kpis['resultat_net']) . " FCFA";
        }

        foreach ($utilisateursDashboard as $utilisateur) {
            $this->notificationService->createNotification(
                $utilisateur,
                'Mise à jour des KPIs',
                $message,
                'info',
                route('esbtp.comptabilite.dashboard-avance'),
                null
            );
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(KPIsCalcules $event, \Throwable $exception): void
    {
        Log::error("Listener MettreAJourDashboard failed", [
            'periode' => $event->periode,
            'error' => $exception->getMessage()
        ]);
    }
}
