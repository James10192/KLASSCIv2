<?php

namespace App\Support;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\EvaluationGradingShortcutService;
use App\Services\EvaluationPublishShortcutService;
use App\Services\TimetableShortcutService;
use Illuminate\Support\Facades\Cache;

/**
 * Les résumés qui alimentent les fenêtres de rappel du gabarit (inscriptions en
 * attente, emplois du temps à renouveler, notes à saisir, évaluations à publier).
 *
 * Le gabarit les calculait à CHAQUE page : quatre comptes d'inscriptions, toutes
 * les classes actives et tous leurs emplois du temps, puis une douzaine de comptes
 * d'évaluations — avant même que le contrôleur ait servi la page. Ce sont des
 * rappels, pas des chiffres de caisse : une minute et demie de retard ne change
 * rien à leur sens. Ils sont donc gardés en cache court.
 *
 * Les clés portent le jour : les résumés dépendent de la date (évaluation passée,
 * emploi du temps expiré), un résumé d'hier ne doit pas servir après minuit.
 * Pas de Cache::tags : le cache des écoles est sur fichier.
 */
class RappelsDuGabarit
{
    public const DUREE_SECONDES = 90;

    public const PREFIXE = 'gabarit.rappels.';

    /** @return array{count: int, by_step: array<string, int>} */
    public static function inscriptionsEnAttente(ESBTPAnneeUniversitaire $annee): array
    {
        return Cache::remember(self::cle('inscriptions', $annee), self::DUREE_SECONDES, function () use ($annee): array {
            $enAttente = ESBTPInscription::where('annee_universitaire_id', $annee->id)
                ->where(function ($query) {
                    $query->whereIn('status', ['en_attente', 'pending'])
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('status', 'active')
                                ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                        });
                });

            // Un seul GROUP BY au lieu de quatre comptes.
            $parEtape = (clone $enAttente)
                ->selectRaw('workflow_step, COUNT(*) as total')
                ->groupBy('workflow_step')
                ->pluck('total', 'workflow_step');

            return [
                'count' => (int) $parEtape->sum(),
                'by_step' => [
                    'prospect' => (int) ($parEtape['prospect'] ?? 0),
                    'documents_complets' => (int) ($parEtape['documents_complets'] ?? 0),
                    'en_validation' => (int) ($parEtape['en_validation'] ?? 0),
                ],
            ];
        });
    }

    /**
     * Le gabarit n'affiche que les comptes : les modèles (classes, emplois du
     * temps) de la liste détaillée ne sont pas mis en cache.
     */
    public static function emploisDuTemps(ESBTPAnneeUniversitaire $annee): array
    {
        return Cache::remember(self::cle('emplois-du-temps', $annee), self::DUREE_SECONDES, function () use ($annee): array {
            $resume = app(TimetableShortcutService::class)->getShortcutSummary($annee);
            unset($resume['items']);

            return $resume;
        });
    }

    public static function evaluationsAPublier(ESBTPAnneeUniversitaire $annee): array
    {
        return Cache::remember(self::cle('evaluations-a-publier', $annee), self::DUREE_SECONDES,
            fn (): array => app(EvaluationPublishShortcutService::class)->getShortcutSummary($annee));
    }

    /** Dépend de l'utilisateur : un enseignant ne voit que ses évaluations. */
    public static function notesASaisir(ESBTPAnneeUniversitaire $annee, User $user): array
    {
        return Cache::remember(self::cle('notes-a-saisir', $annee).'.user.'.$user->id, self::DUREE_SECONDES,
            fn (): array => app(EvaluationGradingShortcutService::class)->getShortcutSummary($annee, $user));
    }

    public static function cle(string $nom, ESBTPAnneeUniversitaire $annee): string
    {
        return self::PREFIXE.$nom.'.annee.'.$annee->id.'.'.now()->format('Y-m-d');
    }
}
