<?php

namespace App\Domain\Notifications\Notifiers;

use App\Domain\Notifications\AbstractNotifier;
use App\Domain\Notifications\Contracts\NotificationResult;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPaiement;
use App\Models\ESBTPRelance;
use App\Models\Setting;
use App\Services\RelanceCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Notifier du domaine "Relances de paiement".
 *
 * Responsabilités :
 * - Envoi de relances par canal (email, SMS — WhatsApp via Phase 3 webhook ready)
 * - Planification automatique des relances avec segmentation avancée
 * - Récupération des étudiants à relancer (filtrage seuil dette + jours retard + cooldown)
 * - Personnalisation des templates avec variables étudiant
 * - Calcul de la dette via RelanceCalculationService (vrai système ESBTPFraisSubscription)
 *
 * Extrait de NotificationService (lignes 36-318, 1063-1414) via strangler fig.
 * NotificationService devient temporairement une façade qui délègue ici.
 *
 * @see App\Services\NotificationService (façade legacy)
 * @see App\Services\RelanceCalculationService (calcul dette canonique)
 * @see App\Jobs\EnvoyerRelanceJob (caller principal côté queue)
 */
class RelanceNotifier extends AbstractNotifier
{
    public function domain(): string
    {
        return 'relance';
    }

    /**
     * Envoie une relance par email.
     *
     * Met à jour le statut ESBTPRelance + log dans parent_notification_logs.
     */
    public function envoyerEmail(ESBTPRelance $relance): NotificationResult
    {
        try {
            $etudiant = $relance->etudiant;

            if (! $etudiant || ! $etudiant->email) {
                $relance->update([
                    'statut' => 'echec',
                    'response_data' => json_encode(['error' => 'Email étudiant manquant']),
                ]);

                return NotificationResult::failure('Email étudiant manquant');
            }

            $template = $this->getTemplateEmail($relance->niveau, $relance->template_utilise);
            $contenu = $this->personaliserMessage($template, $etudiant, $relance);

            Mail::raw($contenu, function ($message) use ($etudiant) {
                $message->to($etudiant->email)
                    ->subject('Rappel de paiement - ' . Setting::get('school_name', config('app.name')));
            });

            $relance->update([
                'statut' => 'envoyee',
                'date_envoi' => now(),
                'contenu_message' => $contenu,
                'response_data' => json_encode(['status' => 'success']),
            ]);

            $this->logDispatch(
                parentId: null,
                etudiantId: $etudiant->id,
                notificationType: 'relance_email',
                channel: 'email',
                status: 'sent',
                recipient: $etudiant->email,
                metadata: ['relance_id' => $relance->id, 'niveau' => $relance->niveau],
            );

            return NotificationResult::success('Email envoyé avec succès', ['email' => 'sent']);
        } catch (Throwable $e) {
            $relance->update([
                'statut' => 'echec',
                'response_data' => json_encode(['error' => $e->getMessage()]),
            ]);

            Log::error('[relance] Erreur envoi email', [
                'relance_id' => $relance->id,
                'error' => $e->getMessage(),
            ]);

            return NotificationResult::failure($e->getMessage(), ['email' => $e->getMessage()]);
        }
    }

    /**
     * Envoie une relance par SMS.
     *
     * NB : Implémentation actuelle = placeholder (Orange contrat expiré).
     * Phase 9 du chantier WhatsApp-prod ré-active SMS via Beem/SMS.to + Orange réactivation.
     */
    public function envoyerSMS(ESBTPRelance $relance): NotificationResult
    {
        try {
            $etudiant = $relance->etudiant;
            $template = $this->getTemplateSMS($relance->niveau);
            $contenu = $this->personaliserMessage($template, $etudiant, $relance);

            $relance->update([
                'statut' => 'echec',
                'contenu_message' => $contenu,
                'response_data' => json_encode(['error' => 'SMS non configuré — aucun fournisseur SMS actif']),
            ]);

            Log::warning('[relance] Tentative SMS sans fournisseur configuré', [
                'relance_id' => $relance->id,
            ]);

            return NotificationResult::failure('Envoi SMS non disponible — aucun fournisseur configuré');
        } catch (Throwable $e) {
            $relance->update([
                'statut' => 'echec',
                'response_data' => json_encode(['error' => $e->getMessage()]),
            ]);

            return NotificationResult::failure($e->getMessage());
        }
    }

    /**
     * Planifie automatiquement les relances pour tous les étudiants éligibles.
     *
     * Récupère les étudiants en retard (via RelanceCalculationService) et crée
     * une relance ESBTPRelance pour chacun jusqu'à niveau 3 max.
     *
     * @return array{success: bool, relances_planifiees: int, message: string}
     */
    public function planifier(): array
    {
        $etudiants = $this->getEtudiantsARelancer();
        $relancesPlanifiees = 0;

        foreach ($etudiants as $etudiant) {
            $dernierRelance = ESBTPRelance::where('etudiant_id', $etudiant->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $niveau = $dernierRelance ? $dernierRelance->niveau + 1 : 1;

            if ($niveau <= 3) {
                $this->creerRelance($etudiant, $niveau);
                $relancesPlanifiees++;
            }
        }

        return [
            'success' => true,
            'relances_planifiees' => $relancesPlanifiees,
            'message' => "$relancesPlanifiees relances planifiées",
        ];
    }

    /**
     * Exécute les relances planifiées dont la date d'envoi est arrivée.
     *
     * @return array{total: int, reussies: int, echecs: int}
     */
    public function executerEnAttente(): array
    {
        $relances = ESBTPRelance::where('statut', 'planifiee')
            ->where('date_envoi', '<=', now())
            ->get();

        $resultats = [
            'total' => $relances->count(),
            'reussies' => 0,
            'echecs' => 0,
        ];

        foreach ($relances as $relance) {
            $result = match ($relance->type) {
                'email' => $this->envoyerEmail($relance),
                'sms' => $this->envoyerSMS($relance),
                'courrier' => $this->genererCourrier($relance),
                default => NotificationResult::failure('Type de relance non supporté'),
            };

            $result->success ? $resultats['reussies']++ : $resultats['echecs']++;
        }

        return $resultats;
    }

    /**
     * Récupère les étudiants éligibles à une relance (dette > seuil + retard >= délai).
     *
     * Respecte un cooldown 7 jours (pas de relance si une autre est récente).
     *
     * @return Collection<int, \App\Models\ESBTPEtudiant>
     */
    public function getEtudiantsARelancer(): Collection
    {
        $calcService = app(RelanceCalculationService::class);

        $montantMinimum = (int) (DB::table('settings')
            ->where('key', 'relances.montant_minimum')
            ->value('value') ?? 50000);

        $anneeActive = ESBTPAnneeUniversitaire::where('is_current', true)->first();

        if (! $anneeActive) {
            return collect();
        }

        $inscriptions = ESBTPInscription::with([
            'etudiant',
            'fraisSubscriptions',
            'paiements' => fn ($q) => $q->where('status', 'validé')->whereNull('deleted_at'),
        ])
            ->where('annee_universitaire_id', $anneeActive->id)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->get();

        if ($inscriptions->isEmpty()) {
            return collect();
        }

        $calcService->preloadForInscriptions($inscriptions);

        $delaiNiveau1 = (int) (DB::table('settings')
            ->where('key', 'relances.delai_niveau_1')
            ->value('value') ?? 60);

        return $inscriptions
            ->filter(function ($ins) use ($calcService, $montantMinimum, $delaiNiveau1) {
                $state = $calcService->getFinancialState($ins);
                $dette = (float) ($state['overdue_amount'] ?? 0);

                if ($dette < $montantMinimum) {
                    return false;
                }

                $joursRetard = (int) ($state['overdue_days'] ?? 0);
                if ($joursRetard < $delaiNiveau1) {
                    return false;
                }

                $relanceRecente = ESBTPRelance::where('etudiant_id', $ins->etudiant_id)
                    ->where('created_at', '>', Carbon::now()->subDays(7))
                    ->whereIn('statut', ['planifiee', 'envoyee', 'intent'])
                    ->exists();

                return ! $relanceRecente;
            })
            ->map(function ($ins) use ($calcService) {
                $state = $calcService->getFinancialState($ins);
                $etudiant = $ins->etudiant;

                if ($etudiant) {
                    $etudiant->setAttribute('relance_inscription_id', $ins->id);
                    $etudiant->setAttribute('relance_overdue_amount', (float) ($state['overdue_amount'] ?? 0));
                    $etudiant->setAttribute('relance_overdue_days', (int) ($state['overdue_days'] ?? 0));
                    $etudiant->setAttribute('relance_remaining_total', (float) ($state['remaining_total'] ?? 0));
                }

                return $etudiant;
            })
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Calcule la dette totale d'un étudiant via le système de frais canonique.
     */
    public function calculerDette($etudiant): float
    {
        if ($etudiant && $etudiant->getAttribute('relance_overdue_amount') !== null) {
            return (float) $etudiant->getAttribute('relance_overdue_amount');
        }

        return app(RelanceCalculationService::class)->calculerMontantEnRetardEtudiant($etudiant);
    }

    private function creerRelance($etudiant, int $niveau): ESBTPRelance
    {
        $type = $this->determinerType($niveau);
        $dateEnvoi = $this->calculerDateEnvoi($niveau);

        return ESBTPRelance::create([
            'etudiant_id' => $etudiant->id,
            'type' => $type,
            'niveau' => $niveau,
            'template_utilise' => "relance_niveau_{$niveau}",
            'date_envoi' => $dateEnvoi,
            'statut' => 'planifiee',
        ]);
    }

    private function determinerType(int $niveau): string
    {
        return match ($niveau) {
            1 => 'email',
            2 => 'sms',
            3 => 'courrier',
            default => 'email',
        };
    }

    private function calculerDateEnvoi(int $niveau): Carbon
    {
        $delaiSettings = DB::table('settings')
            ->where('key', "relances.delai_niveau_{$niveau}")
            ->value('value');

        if ($delaiSettings !== null) {
            return now()->addDays((int) $delaiSettings);
        }

        return match ($niveau) {
            1 => now(),
            2 => now()->addDays(7),
            3 => now()->addDays(14),
            default => now(),
        };
    }

    private function personaliserMessage(string $template, $etudiant, ESBTPRelance $relance): string
    {
        $dette = $this->calculerDette($etudiant);

        $dateEcheance = 'N/A';
        $joursRetard = 0;
        $calcService = app(RelanceCalculationService::class);

        $inscriptionActive = $etudiant->inscription;
        if ($inscriptionActive) {
            $calcService->preloadForSingle($inscriptionActive);
            $dateEcheance = $calcService->getDateEcheance($inscriptionActive)->format('d/m/Y');
            $joursRetard = $calcService->getJoursRetard($inscriptionActive);
        }

        $nomEcole = Setting::get('school_name', config('app.name'));
        $acronymeEcole = Setting::get('school_acronym', config('app.name'));

        $variables = [
            '{nom}' => $etudiant->nom,
            '{prenom}' => $etudiant->prenoms,
            '{montant_dette}' => number_format($dette, 0, ',', ' ') . ' FCFA',
            '{niveau_relance}' => $relance->niveau,
            '{date}' => Carbon::now()->format('d/m/Y'),
            '{date_echeance}' => $dateEcheance,
            '{jours_retard}' => $joursRetard,
            '{ecole}' => $nomEcole,
            '{nom_ecole}' => $nomEcole,
            '{acronyme}' => $acronymeEcole,
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    private function getTemplateEmail(int $niveau, ?string $templateName = null): string
    {
        $templates = [
            1 => "Cher/Chère {prenom} {nom},\n\nNous vous rappelons que votre solde de scolarité de {montant_dette} est en attente de paiement.\n\nMerci de régulariser votre situation dans les plus brefs délais.\n\nCordialement,\nL'administration {ecole}",
            2 => "Cher/Chère {prenom} {nom},\n\nCeci est un DEUXIÈME RAPPEL concernant votre dette de {montant_dette}.\n\nVeuillez contacter notre service comptabilité rapidement pour éviter toute mesure disciplinaire.\n\nCordialement,\nL'administration {ecole}",
            3 => "Cher/Chère {prenom} {nom},\n\nDERNIER AVERTISSEMENT - Votre dette de {montant_dette} doit être réglée IMMÉDIATEMENT.\n\nFaute de paiement sous 7 jours, des mesures administratives seront prises.\n\nCordialement,\nL'administration {ecole}",
        ];

        return $templates[$niveau] ?? $templates[1];
    }

    private function getTemplateSMS(int $niveau): string
    {
        $templates = [
            1 => "{acronyme}: Rappel paiement scolarité {montant_dette}. Merci de régulariser. Info: [telephone]",
            2 => "{acronyme}: 2e RAPPEL - Dette {montant_dette}. Contactez-nous rapidement. Info: [telephone]",
            3 => "{acronyme}: URGENT - Dette {montant_dette}. Paiement obligatoire sous 7j. Info: [telephone]",
        ];

        return $templates[$niveau] ?? $templates[1];
    }

    private function genererCourrier(ESBTPRelance $relance): NotificationResult
    {
        $relance->update([
            'statut' => 'echec',
            'response_data' => json_encode(['error' => 'Génération courrier PDF non implémentée']),
        ]);

        Log::warning('[relance] Génération courrier non implémentée', [
            'relance_id' => $relance->id,
        ]);

        return NotificationResult::failure('Génération de courrier non disponible');
    }
}
