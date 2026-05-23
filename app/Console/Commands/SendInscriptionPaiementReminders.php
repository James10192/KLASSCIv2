<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\NotificationReminder;
use App\Models\ESBTPSystemSetting;
use App\Jobs\SendInscriptionReminderJob;
use App\Jobs\SendPaiementReminderJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendInscriptionPaiementReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-inscription-paiement {--test : Mode test sans envoi réel}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer les rappels automatiques pour les inscriptions et paiements en attente';

    public function __construct()
    {
        parent::__construct();
        // Phase 14 — la commande ne fait que dispatcher des jobs, plus de DI Notifier
        // (les jobs eux-mêmes injectent les Notifier shells dans leurs handle())
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('🚀 Démarrage de l\'envoi des rappels automatiques...');

        $isTestMode = $this->option('test');
        if ($isTestMode) {
            $this->warn('⚠️  MODE TEST - Aucun rappel ne sera envoyé');
        }

        $inscriptionsSent = $this->processInscriptionReminders($isTestMode);
        $paiementsSent = $this->processPaiementReminders($isTestMode);

        $this->info("\n✅ Rappels envoyés avec succès:");
        $this->table(
            ['Type', 'Nombre de rappels envoyés'],
            [
                ['Inscriptions', $inscriptionsSent],
                ['Paiements', $paiementsSent],
                ['TOTAL', $inscriptionsSent + $paiementsSent]
            ]
        );

        Log::info('Commande rappels automatiques terminée', [
            'inscriptions_sent' => $inscriptionsSent,
            'paiements_sent' => $paiementsSent,
            'test_mode' => $isTestMode
        ]);

        return Command::SUCCESS;
    }

    /**
     * Traiter les rappels pour les inscriptions en attente
     */
    protected function processInscriptionReminders($isTestMode = false): int
    {
        // Vérifier si les rappels sont activés
        $enabled = ESBTPSystemSetting::getValue('reminder_inscription_enabled', true);
        if (!$enabled) {
            $this->comment('ℹ️  Rappels inscriptions désactivés');
            return 0;
        }

        // Récupérer les paramètres
        $firstDelay = (int) ESBTPSystemSetting::getValue('reminder_inscription_first_delay', 3);
        $frequency = (int) ESBTPSystemSetting::getValue('reminder_inscription_frequency', 2);
        $maxCount = (int) ESBTPSystemSetting::getValue('reminder_inscription_max_count', 5);

        $this->line("\n📝 Paramètres inscriptions: Premier rappel après {$firstDelay}j, puis tous les {$frequency}j (max: {$maxCount})");

        // Récupérer les inscriptions en attente
        $inscriptions = ESBTPInscription::where('status', 'en_attente')
            ->with(['etudiant', 'classe', 'filiere', 'paiements'])
            ->get();

        $this->line("📊 {$inscriptions->count()} inscription(s) en attente trouvée(s)");

        $remindersSent = 0;

        foreach ($inscriptions as $inscription) {
            // Obtenir ou créer le reminder pour cette inscription
            $reminder = NotificationReminder::getOrCreateForRemindable(
                'App\Models\ESBTPInscription',
                $inscription->id
            );

            // Vérifier si le reminder est actif
            if (!$reminder->is_active) {
                continue;
            }

            // Calculer le nombre de jours depuis la création
            $daysPending = now()->diffInDays($inscription->created_at);

            // Premier rappel ?
            if ($reminder->reminder_count === 0) {
                if ($daysPending >= $firstDelay) {
                    if (!$isTestMode) {
                        // Phase 14 — dispatch sur queue 'reminders' (parallélisable, retry exponential)
                        SendInscriptionReminderJob::dispatch($inscription->id, $daysPending, 1);
                        $reminder->recordReminderSent($frequency);
                        $remindersSent++;
                    }
                    $this->line("  → Inscription #{$inscription->id} ({$inscription->etudiant->nom}): Premier rappel envoyé ({$daysPending}j)");
                }
            } else {
                // Rappels suivants
                if ($reminder->next_reminder_at && now()->greaterThanOrEqualTo($reminder->next_reminder_at)) {
                    // Vérifier la limite
                    if ($maxCount > 0 && $reminder->reminder_count >= $maxCount) {
                        $this->comment("  ⚠️  Inscription #{$inscription->id}: Limite de rappels atteinte ({$maxCount})");
                        if (!$isTestMode) {
                            $reminder->deactivate();
                        }
                        continue;
                    }

                    if (!$isTestMode) {
                        // Phase 14 — dispatch sur queue 'reminders' (rappel suivant)
                        SendInscriptionReminderJob::dispatch($inscription->id, $daysPending, $reminder->reminder_count + 1);
                        $reminder->recordReminderSent($frequency);
                        $remindersSent++;
                    }
                    $this->line("  → Inscription #{$inscription->id} ({$inscription->etudiant->nom}): Rappel #{$reminder->reminder_count} envoyé ({$daysPending}j)");
                }
            }
        }

        return $remindersSent;
    }

    /**
     * Traiter les rappels pour les paiements en attente
     */
    protected function processPaiementReminders($isTestMode = false): int
    {
        // Vérifier si les rappels sont activés
        $enabled = ESBTPSystemSetting::getValue('reminder_paiement_enabled', true);
        if (!$enabled) {
            $this->comment('ℹ️  Rappels paiements désactivés');
            return 0;
        }

        // Récupérer les paramètres
        $firstDelay = (int) ESBTPSystemSetting::getValue('reminder_paiement_first_delay', 2);
        $frequency = (int) ESBTPSystemSetting::getValue('reminder_paiement_frequency', 1);
        $maxCount = (int) ESBTPSystemSetting::getValue('reminder_paiement_max_count', 7);

        $this->line("\n💰 Paramètres paiements: Premier rappel après {$firstDelay}j, puis tous les {$frequency}j (max: {$maxCount})");

        // Récupérer les paiements en attente
        $paiements = ESBTPPaiement::where('status', 'en_attente')
            ->with(['etudiant'])
            ->get();

        $this->line("📊 {$paiements->count()} paiement(s) en attente trouvé(s)");

        $remindersSent = 0;

        foreach ($paiements as $paiement) {
            // Obtenir ou créer le reminder pour ce paiement
            $reminder = NotificationReminder::getOrCreateForRemindable(
                'App\Models\ESBTPPaiement',
                $paiement->id
            );

            // Vérifier si le reminder est actif
            if (!$reminder->is_active) {
                continue;
            }

            // Calculer le nombre de jours depuis la création
            $daysPending = now()->diffInDays($paiement->created_at);

            // Premier rappel ?
            if ($reminder->reminder_count === 0) {
                if ($daysPending >= $firstDelay) {
                    if (!$isTestMode) {
                        // Phase 14 — dispatch sur queue 'reminders' (premier rappel paiement)
                        SendPaiementReminderJob::dispatch($paiement->id, $daysPending, 1);
                        $reminder->recordReminderSent($frequency);
                        $remindersSent++;
                    }
                    $this->line("  → Paiement #{$paiement->id} ({$paiement->etudiant->nom}): Premier rappel envoyé ({$daysPending}j)");
                }
            } else {
                // Rappels suivants
                if ($reminder->next_reminder_at && now()->greaterThanOrEqualTo($reminder->next_reminder_at)) {
                    // Vérifier la limite
                    if ($maxCount > 0 && $reminder->reminder_count >= $maxCount) {
                        $this->comment("  ⚠️  Paiement #{$paiement->id}: Limite de rappels atteinte ({$maxCount})");
                        if (!$isTestMode) {
                            $reminder->deactivate();
                        }
                        continue;
                    }

                    if (!$isTestMode) {
                        // Phase 14 — dispatch sur queue 'reminders' (rappel suivant paiement)
                        SendPaiementReminderJob::dispatch($paiement->id, $daysPending, $reminder->reminder_count + 1);
                        $reminder->recordReminderSent($frequency);
                        $remindersSent++;
                    }
                    $this->line("  → Paiement #{$paiement->id} ({$paiement->etudiant->nom}): Rappel #{$reminder->reminder_count} envoyé ({$daysPending}j)");
                }
            }
        }

        return $remindersSent;
    }
}
