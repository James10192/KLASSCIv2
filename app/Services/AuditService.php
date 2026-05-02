<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

/**
 * AuditService — couche d'accès et de présentation pour la table `audits`.
 *
 * Sert le contrôleur ESBTPAuditController et les vues blade qui affichent :
 *  - l'historique d'une entité (qui a touché à ce paiement / cette note ?)
 *  - l'activité d'un utilisateur (qu'a fait ce caissier ces 30 derniers jours ?)
 *  - une diff lisible entre old_values et new_values
 *  - un niveau de risque pour signaler les actions critiques (suppression
 *    d'une entité financière, modification d'un montant, etc.).
 */
class AuditService
{
    /**
     * Champs sensibles dont la modification est marquée "Élevé".
     * Noms agrégés : on matche par contains() pour couvrir les variantes
     * (ex : montant, montant_total, montant_paye, taux_horaire...).
     */
    private const SENSITIVE_FIELDS = [
        'montant',
        'note',
        'moyenne',
        'rang',
        'mention',
        'decision',
        'taux_horaire',
        'amount',
        'is_published',
        'signature_directeur',
    ];

    /**
     * Modèles "financiers" — toute suppression est Critique.
     */
    private const FINANCIAL_MODELS = [
        \App\Models\ESBTPPaiement::class,
        \App\Models\ESBTPFraisSubscription::class,
        \App\Models\ESBTPFraisCategory::class,
        \App\Models\ESBTPFraisOption::class,
    ];

    /**
     * Modèles "académiques" — toute suppression est Élevé minimum.
     */
    private const ACADEMIC_MODELS = [
        \App\Models\ESBTPNote::class,
        \App\Models\ESBTPBulletin::class,
        \App\Models\ESBTPResultat::class,
        \App\Models\ESBTPEvaluation::class,
    ];

    /**
     * Récupère les N derniers événements pour une entité donnée.
     *
     * @param  Model  $model  L'instance dont on veut l'historique
     * @param  int  $limit  Nombre max d'événements (default 20)
     */
    public function historyFor(Model $model, int $limit = 20): Collection
    {
        return Audit::with('user')
            ->where('auditable_type', get_class($model))
            ->where('auditable_id', $model->getKey())
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Activité d'un utilisateur sur les N derniers jours.
     *
     * Utile pour la page "Monitoring utilisateur" (perm `security.users.monitor`).
     *
     * @param  int  $userId
     * @param  int  $days  Fenêtre temporelle (default 30)
     */
    public function activityFor(int $userId, int $days = 30): Collection
    {
        return Audit::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Format diff : retourne un tableau [field => ['old' => x, 'new' => y]]
     * en filtrant les champs vides ou identiques.
     *
     * Pratique pour générer des tableaux à 3 colonnes "Champ / Avant / Après"
     * dans la vue "détail d'un audit".
     */
    public function formatDiff(Audit $audit): array
    {
        $oldValues = $this->decodeJson($audit->old_values);
        $newValues = $this->decodeJson($audit->new_values);

        $diff = [];
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $old = $oldValues[$key] ?? null;
            $new = $newValues[$key] ?? null;

            // Filtrer les champs où ancien et nouveau sont identiques OU tous deux vides
            if ($this->valuesAreEqual($old, $new)) {
                continue;
            }

            $diff[$key] = [
                'old' => $this->normalizeForDisplay($old),
                'new' => $this->normalizeForDisplay($new),
            ];
        }

        return $diff;
    }

    /**
     * Calcule un niveau de risque (Critique/Élevé/Moyen/Faible).
     *
     * Échelle :
     *  - **Critique** : suppression d'une entité financière
     *  - **Élevé** : modification d'un champ sensible (montant, note, signature...),
     *               OU suppression d'une entité académique/utilisateur
     *  - **Moyen** : update standard ou create sur entité financière
     *  - **Faible** : create / retrieved sur entité non-sensible
     */
    public function riskLevel(Audit $audit): string
    {
        $event = $audit->event;
        $type = $audit->auditable_type;

        // Critique : suppression d'une entité financière
        if ($event === 'deleted' && in_array($type, self::FINANCIAL_MODELS, true)) {
            return 'Critique';
        }

        // Élevé : suppression d'une entité académique
        if ($event === 'deleted' && in_array($type, self::ACADEMIC_MODELS, true)) {
            return 'Élevé';
        }

        // Élevé : suppression d'un user
        if ($event === 'deleted' && $type === \App\Models\User::class) {
            return 'Élevé';
        }

        // Élevé : modification d'un champ sensible
        if ($event === 'updated' && $this->touchesSensitiveField($audit)) {
            return 'Élevé';
        }

        // Élevé : changement permission/rôle
        if ($audit->tags === 'permissions') {
            return 'Élevé';
        }

        // Moyen : update standard ou création sur entité financière
        if ($event === 'updated') {
            return 'Moyen';
        }

        if ($event === 'created' && in_array($type, self::FINANCIAL_MODELS, true)) {
            return 'Moyen';
        }

        // Faible : tout le reste (create non-financier, restored, retrieved)
        return 'Faible';
    }

    /**
     * Indique si l'audit touche au moins un champ marqué sensible.
     */
    private function touchesSensitiveField(Audit $audit): bool
    {
        $newValues = $this->decodeJson($audit->new_values);
        $oldValues = $this->decodeJson($audit->old_values);
        $changedKeys = array_unique(array_merge(array_keys($newValues), array_keys($oldValues)));

        foreach ($changedKeys as $key) {
            foreach (self::SENSITIVE_FIELDS as $sensitive) {
                if (str_contains($key, $sensitive)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Décode JSON en tableau, retourne [] sur erreur ou null.
     */
    private function decodeJson($value): array
    {
        if (empty($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Compare deux valeurs en considérant null/vide comme équivalent.
     */
    private function valuesAreEqual($a, $b): bool
    {
        // Les deux vides ou null => identiques
        $aEmpty = $a === null || $a === '' || $a === [];
        $bEmpty = $b === null || $b === '' || $b === [];

        if ($aEmpty && $bEmpty) {
            return true;
        }

        // Comparaison loose pour gérer "1" == 1 (cast Eloquent vs JSON)
        return $a == $b;
    }

    /**
     * Normalise une valeur pour l'affichage (string lisible).
     */
    private function normalizeForDisplay($value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'oui' : 'non';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }
}
