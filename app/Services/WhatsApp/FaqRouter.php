<?php

namespace App\Services\WhatsApp;

use App\Helpers\SettingsHelper;

/**
 * Routeur FAQ intelligent (Phase 11 Plan v4) — pattern matching simple > IA.
 *
 * Avant d'appeler Gemini (coût + latence), check si le message match un pattern
 * FAQ pré-défini. Réponse instantanée + gratuite.
 *
 * Patterns gérés via settings tenant `faq.patterns` (array de
 * ['regex' => ..., 'response' => ..., 'intent' => ...]).
 *
 * Si match → réponse template directe.
 * Si no match → escalation vers ChatbotGeminiService OU secrétaire UI.
 */
class FaqRouter
{
    /**
     * @return array{matched: bool, response?: string, intent?: string, escalate?: bool}
     */
    public function route(string $message): array
    {
        $normalized = mb_strtolower(trim($message), 'UTF-8');
        $patterns = (array) SettingsHelper::get('faq.patterns', $this->defaultPatterns());

        foreach ($patterns as $rule) {
            $pattern = $rule['regex'] ?? null;
            if (empty($pattern)) {
                continue;
            }

            if (preg_match("/{$pattern}/iu", $normalized)) {
                return [
                    'matched' => true,
                    'response' => $rule['response'] ?? '',
                    'intent' => $rule['intent'] ?? 'general',
                    'escalate' => $rule['escalate'] ?? false,
                ];
            }
        }

        return ['matched' => false, 'escalate' => true];
    }

    /**
     * Patterns par défaut FR — Côte d'Ivoire context.
     */
    private function defaultPatterns(): array
    {
        return [
            [
                'regex' => '\b(horaire|heures? ouverture|ouvert)\b',
                'response' => "Notre secrétariat est ouvert du lundi au vendredi de 8h à 17h, et le samedi de 8h à 12h.",
                'intent' => 'horaires',
            ],
            [
                'regex' => '\b(adresse|où.+situ|comment.+venir)\b',
                'response' => "Notre établissement se trouve à l'adresse indiquée sur notre site web. Pour plus de détails, contactez le secrétariat.",
                'intent' => 'adresse',
            ],
            [
                'regex' => '\b(merci|thanks|thx)\b',
                'response' => "Avec plaisir ! N'hésitez pas si vous avez d'autres questions.",
                'intent' => 'gratitude',
                'escalate' => false,
            ],
            [
                'regex' => '\b(bonjour|salut|bonsoir|hello)\b',
                'response' => "Bonjour ! Comment pouvons-nous vous aider aujourd'hui ?",
                'intent' => 'greeting',
            ],
        ];
    }
}
