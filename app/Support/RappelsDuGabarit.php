<?php

namespace App\Support;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPInscription;
use App\Models\User;
use App\Services\EvaluationGradingShortcutService;
use App\Services\EvaluationPublishShortcutService;
use App\Services\TimetableShortcutService;

/**
 * Les quatre fenêtres de rappel du gabarit : inscriptions en attente, emplois du
 * temps à renouveler, notes à saisir, évaluations à publier.
 *
 * Le gabarit calculait leurs résumés à CHAQUE page, alors que le navigateur n'en
 * ouvre au plus qu'un par heure, jamais sous 992 px, et qu'aucun bouton ne les
 * rouvre. La page ne porte donc plus que la liste des rappels permis (des
 * contrôles de droits, aucune requête) ; le navigateur demande le rappel du moment
 * à `gabarit.rappel-du-moment` seulement quand l'heure est passée pour l'un d'eux.
 */
class RappelsDuGabarit
{
    /**
     * Ordre d'ouverture, et préfixe de la clé navigateur de chaque rappel
     * (inchangé : l'heure de dernière ouverture des utilisateurs est conservée).
     */
    public const ORDRE = [
        'inscriptions-en-attente' => 'pendingInscriptionsReminder',
        'emplois-du-temps' => 'timetableReminder',
        'notes-a-saisir' => 'evaluationGradingReminder',
        'evaluations-a-publier' => 'evaluationPublishReminder',
    ];

    /**
     * Les rappels que ce compte peut recevoir, avec leur clé navigateur.
     * Ce sont les conditions de droits d'avant, à l'identique.
     *
     * @return array<string, string>
     */
    public static function permis(User $user): array
    {
        $evaluations = $user->can('exams.view') || $user->can('evaluations.view');
        $notes = $user->can('notes.view') || $user->can('notes.create')
            || $user->can('notes.edit') || $user->can('notes.manage_own');

        $droits = [
            'inscriptions-en-attente' => $user->can('inscriptions.validate'),
            'emplois-du-temps' => $user->can('timetables.view') || $user->can('timetables.view_all'),
            'notes-a-saisir' => $evaluations || $notes,
            'evaluations-a-publier' => $evaluations,
        ];

        $permis = [];
        foreach (self::ORDRE as $rappel => $prefixe) {
            if ($droits[$rappel]) {
                $permis[$rappel] = $prefixe.'.user.'.$user->id;
            }
        }

        return $permis;
    }

    /**
     * Le premier rappel à montrer parmi ceux demandés, dans l'ordre d'ouverture.
     *
     * `verifies` liste les rappels demandés, regardés et vides : le navigateur ne
     * les redemandera pas avant une heure.
     *
     * @param  array<int, string>  $demandes
     * @return array{rappel: ?string, cle: ?string, html: ?string, verifies: array<int, string>}
     */
    public static function premierDu(User $user, ESBTPAnneeUniversitaire $annee, array $demandes): array
    {
        $permis = self::permis($user);
        $verifies = [];

        foreach (array_keys(self::ORDRE) as $rappel) {
            if (! in_array($rappel, $demandes, true) || ! isset($permis[$rappel])) {
                continue;
            }

            $resume = self::resume($rappel, $annee, $user);
            if (! self::aMontrer($rappel, $resume)) {
                $verifies[] = $rappel;

                continue;
            }

            $html = view('layouts.partials.rappels.'.$rappel, [
                'annee' => $annee,
                'resume' => $resume,
                'cle' => $permis[$rappel],
                'peutVoirEvaluations' => $user->can('exams.view') || $user->can('evaluations.view'),
            ])->render();

            return ['rappel' => $rappel, 'cle' => $permis[$rappel], 'html' => $html, 'verifies' => $verifies];
        }

        return ['rappel' => null, 'cle' => null, 'html' => null, 'verifies' => $verifies];
    }

    public static function resume(string $rappel, ESBTPAnneeUniversitaire $annee, User $user): array
    {
        return match ($rappel) {
            'inscriptions-en-attente' => self::inscriptionsEnAttente($annee),
            'emplois-du-temps' => app(TimetableShortcutService::class)->getShortcutSummary($annee),
            'notes-a-saisir' => app(EvaluationGradingShortcutService::class)->getShortcutSummary($annee, $user),
            'evaluations-a-publier' => app(EvaluationPublishShortcutService::class)->getShortcutSummary($annee),
        };
    }

    /** @return array{count: int, by_step: array<string, int>} */
    public static function inscriptionsEnAttente(ESBTPAnneeUniversitaire $annee): array
    {
        // Un seul GROUP BY au lieu de quatre comptes.
        $parEtape = ESBTPInscription::where('annee_universitaire_id', $annee->id)
            ->where(function ($query) {
                $query->whereIn('status', ['en_attente', 'pending'])
                    ->orWhere(function ($subQuery) {
                        $subQuery->where('status', 'active')
                            ->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation']);
                    });
            })
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
    }

    private static function aMontrer(string $rappel, array $resume): bool
    {
        if ($rappel === 'inscriptions-en-attente') {
            return ($resume['count'] ?? 0) > 0;
        }

        return (bool) ($resume['show'] ?? false);
    }
}
