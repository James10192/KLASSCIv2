<?php

namespace App\Services\WhatsApp\Chatbot;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPEtudiant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Chatbot auto-reply IA Gemini sur WhatsApp inbox (Phase 10 Plan v4).
 *
 * Réutilise infra Gemini KLASSCI existante (GEMINI_API_KEY déjà configuré pour
 * le chatbot interne). Tool use pour query DB tenant : paiements, notes, absences.
 *
 * Pattern :
 * 1. Parent envoie message via WhatsApp → webhook MetaWhatsAppWebhookController
 * 2. PhoneToParentResolver identifie le parent + étudiant
 * 3. ChatbotGeminiService génère réponse contextualisée avec tool use
 * 4. Si confidence < seuil → escalation humain (assignation secrétaire UI)
 * 5. Sinon WhatsAppReplyService envoie réponse (dans fenêtre 24h gratuite)
 *
 * Settings tenant :
 *   - chatbot.whatsapp.enabled
 *   - chatbot.whatsapp.confidence_threshold (default 0.7)
 *   - chatbot.whatsapp.max_turns_before_escalation (default 3)
 */
class ChatbotGeminiService
{
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-exp:generateContent';

    /**
     * Génère une réponse au message entrant en fonction du contexte étudiant.
     *
     * @return array{response: string, confidence: float, intent: string, escalate: bool}
     */
    public function answer(string $message, ?ESBTPEtudiant $etudiant = null): array
    {
        if (! SettingsHelper::get('chatbot.whatsapp.enabled', false)) {
            return $this->emptyResponse(escalate: true, reason: 'chatbot disabled');
        }

        $apiKey = env('GEMINI_API_KEY');
        if (empty($apiKey)) {
            return $this->emptyResponse(escalate: true, reason: 'GEMINI_API_KEY missing');
        }

        $systemPrompt = $this->buildSystemPrompt($etudiant);

        try {
            $response = Http::timeout(15)
                ->withQueryParameters(['key' => $apiKey])
                ->post(self::GEMINI_API_URL, [
                    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents' => [
                        ['role' => 'user', 'parts' => [['text' => $message]]],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 500,
                    ],
                    'tools' => $this->buildTools($etudiant),
                ]);

            if (! $response->successful()) {
                Log::warning('[chatbot-gemini] API non-2xx', ['status' => $response->status()]);
                return $this->emptyResponse(escalate: true, reason: "Gemini HTTP {$response->status()}");
            }

            $body = $response->json();
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
            $confidence = $this->estimateConfidence($body);

            $threshold = (float) SettingsHelper::get('chatbot.whatsapp.confidence_threshold', 0.7);

            return [
                'response' => trim($text),
                'confidence' => $confidence,
                'intent' => $this->detectIntent($message),
                'escalate' => $confidence < $threshold || empty(trim($text)),
            ];
        } catch (\Throwable $e) {
            Log::error('[chatbot-gemini] Exception', ['error' => $e->getMessage()]);

            return $this->emptyResponse(escalate: true, reason: $e->getMessage());
        }
    }

    private function buildSystemPrompt(?ESBTPEtudiant $etudiant): string
    {
        $schoolName = SettingsHelper::get('school_name', 'KLASSCI');

        $context = "Tu es l'assistant WhatsApp officiel de {$schoolName}. Tu réponds aux questions des parents en français, de manière concise et professionnelle (max 200 caractères pour WhatsApp).";

        if ($etudiant) {
            $context .= "\n\nContexte étudiant : {$etudiant->prenoms} {$etudiant->nom}, matricule {$etudiant->matricule}.";
        }

        $context .= "\n\nSi tu ne peux pas répondre avec certitude OU si la question implique une action administrative (paiement, modification dossier, rendez-vous), réponds 'Je transfère votre demande au secrétariat qui vous recontactera sous 24h.'";
        $context .= "\n\nNe JAMAIS inventer un montant, une date, ou une information non disponible dans le contexte.";

        return $context;
    }

    private function buildTools(?ESBTPEtudiant $etudiant): array
    {
        if (! $etudiant) {
            return [];
        }

        // Tool declarations Gemini (function calling)
        return [[
            'functionDeclarations' => [
                [
                    'name' => 'get_solde_paiement',
                    'description' => "Retourne le solde restant à payer de l'étudiant pour l'année courante",
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
                [
                    'name' => 'get_nb_absences_mois',
                    'description' => "Retourne le nombre d'absences ce mois-ci",
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
                [
                    'name' => 'get_derniere_note_publiee',
                    'description' => "Retourne la dernière note publiée pour l'étudiant",
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass()],
                ],
            ],
        ]];
    }

    /**
     * Heuristic intent detection (Phase 11 routing — version simple, ML futur).
     */
    private function detectIntent(string $message): string
    {
        $lower = mb_strtolower($message, 'UTF-8');

        return match (true) {
            str_contains($lower, 'paiement') || str_contains($lower, 'frais') || str_contains($lower, 'scolarité') => 'paiement',
            str_contains($lower, 'note') || str_contains($lower, 'bulletin') || str_contains($lower, 'moyenne') => 'notes',
            str_contains($lower, 'absence') || str_contains($lower, 'présence') => 'absences',
            str_contains($lower, 'rendez-vous') || str_contains($lower, 'rdv') => 'rdv',
            str_contains($lower, 'inscription') || str_contains($lower, 'réinscription') => 'inscription',
            default => 'general',
        };
    }

    private function estimateConfidence(array $geminiResponse): float
    {
        $finishReason = $geminiResponse['candidates'][0]['finishReason'] ?? 'OTHER';

        return match ($finishReason) {
            'STOP' => 0.85,
            'MAX_TOKENS' => 0.6,
            'SAFETY', 'RECITATION', 'OTHER' => 0.3,
            default => 0.5,
        };
    }

    private function emptyResponse(bool $escalate, string $reason = ''): array
    {
        return [
            'response' => '',
            'confidence' => 0.0,
            'intent' => 'unknown',
            'escalate' => $escalate,
            'reason' => $reason,
        ];
    }
}
