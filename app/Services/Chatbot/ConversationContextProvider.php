<?php

namespace App\Services\Chatbot;

use App\Models\ChatbotConversation;

/**
 * Gère un résumé minimal du dernier résultat d'outil pour permettre à Claude
 * de résoudre des questions de suivi contextuelles ("et dans la classe d'à côté ?",
 * "et pour la 2e année ?") sans gonfler les tokens en réinjectant tous les résultats.
 *
 * Format du summary stocké dans conversation->context['last_result_summary'] :
 *   {
 *     "tool":    "search_classes",                       // nom de l'outil appelé
 *     "filters": {"filiere":"BTP","has_places":true},    // filtres utiles (scalaires uniquement)
 *     "count":   5,                                       // nombre de résultats retournés
 *     "total":   12,                                      // total sans limit (si dispo)
 *     "ids":     [3, 7, 12, 18, 25],                     // max 5 IDs (stabilise les follow-ups)
 *     "names":   ["BTP L1", "BTP L2", …],                // max 5 noms (lecture humaine)
 *     "at":      "2026-04-25T10:22:03+00:00"
 *   }
 *
 * Aucune donnée PII brute (pas de matricule, email, téléphone).
 */
class ConversationContextProvider
{
    /** Limite stricte sur le nombre d'IDs/noms conservés dans le summary. */
    public const MAX_SAMPLE = 5;

    /** Clé sous laquelle le summary est stocké dans conversation->context. */
    public const CONTEXT_KEY = 'last_result_summary';

    /**
     * Renvoyer un bloc texte court à injecter dans le system prompt, ou null si pas de contexte.
     */
    public function summaryBlock(ChatbotConversation $conversation): ?string
    {
        $context = $conversation->context ?? [];
        $summary = $context[self::CONTEXT_KEY] ?? null;
        if (!is_array($summary) || empty($summary['tool'])) {
            return null;
        }

        $parts = [];
        $parts[] = 'tool=' . $summary['tool'];
        if (!empty($summary['filters']) && is_array($summary['filters'])) {
            $filters = [];
            foreach ($summary['filters'] as $k => $v) {
                $filters[] = $k . '=' . (is_bool($v) ? ($v ? 'true' : 'false') : (string) $v);
            }
            if ($filters) {
                $parts[] = 'filters={' . implode(', ', $filters) . '}';
            }
        }
        if (isset($summary['count'])) {
            $parts[] = 'count=' . $summary['count'];
        }
        if (!empty($summary['ids']) && is_array($summary['ids'])) {
            $parts[] = 'ids=[' . implode(',', $summary['ids']) . ']';
        }
        if (!empty($summary['names']) && is_array($summary['names'])) {
            $parts[] = 'names=[' . implode(', ', array_map(fn ($n) => '"' . $n . '"', $summary['names'])) . ']';
        }

        return implode(' | ', $parts);
    }

    /**
     * Mettre à jour le contexte avec un résumé du dernier tool result.
     * Sauvegarde uniquement si des résultats sont présents.
     */
    public function updateFromToolResult(
        ChatbotConversation $conversation,
        string $toolName,
        array $args,
        array $result,
    ): void {
        // Ne pas polluer le contexte avec des errors ou 0 résultats
        if (isset($result['error']) || empty($result['results'])) {
            return;
        }

        $sample = array_slice($result['results'], 0, self::MAX_SAMPLE);
        $ids = [];
        $names = [];
        foreach ($sample as $row) {
            if (isset($row['id']) && is_numeric($row['id'])) {
                $ids[] = (int) $row['id'];
            }
            $name = $row['nom'] ?? $row['etudiant'] ?? $row['name'] ?? null;
            if (is_string($name) && $name !== '' && $name !== 'N/A') {
                $names[] = mb_substr($name, 0, 60, 'UTF-8');
            }
        }

        $summary = [
            'tool' => $toolName,
            'filters' => $this->sanitizeFilters($args),
            'count' => $result['count'] ?? count($result['results'] ?? []),
            'ids' => $ids,
            'names' => $names,
            'at' => now()->toIso8601String(),
        ];

        if (isset($result['total'])) {
            $summary['total'] = (int) $result['total'];
        }

        $context = $conversation->context ?? [];
        if (!is_array($context)) {
            $context = [];
        }
        $context[self::CONTEXT_KEY] = $summary;
        $conversation->context = $context;
        $conversation->save();
    }

    /**
     * Garder uniquement les filtres scalaires (string/int/bool) pour éviter d'y mettre des
     * objets ou des payloads volumineux qui exploseraient la taille du summary.
     */
    protected function sanitizeFilters(array $args): array
    {
        $filtered = [];
        foreach ($args as $k => $v) {
            if (is_scalar($v) && $v !== '' && $v !== null) {
                $filtered[$k] = is_string($v) ? mb_substr($v, 0, 80, 'UTF-8') : $v;
            }
        }
        return $filtered;
    }
}
