<?php

namespace App\Services;

use App\Services\WhatsApp\TenantConfigResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Service WhatsApp Business Cloud API (Meta) — multi-tenant aware.
 *
 * Refactoré Phase 1 step 3/3 Plan v4 : credentials résolus per-request via
 * TenantConfigResolver (API master adminKlassci, cache 5min). Plus de
 * `env()` direct — chaque tenant a son propre phone_number_id + access_token.
 *
 * Notifications transactionnelles UNIQUEMENT (UTILITY messages Meta) — pas
 * de marketing. Coût Meta 2026 ~2.4 FCFA / msg Utility "Rest of Africa".
 *
 * Si le tenant n'a pas configuré WhatsApp (enabled=false), TOUS les sends
 * retournent silencieusement false + log info — l'application continue
 * normalement via les canaux email/SMS.
 *
 * @see app/Services/WhatsApp/TenantConfigResolver.php
 * @see adminKlassci/app/Http/Controllers/API/TenantWhatsAppConfigController.php
 */
class WhatsAppService
{
    private const API_BASE_URL = 'https://graph.facebook.com/v18.0';

    public function __construct(private readonly TenantConfigResolver $configResolver) {}

    /**
     * Envoyer une notification d'inscription/réinscription
     * Template : inscription_confirmation (UTILITY)
     */
    public function sendInscriptionNotification($phoneNumber, $data)
    {
        try {
            $templateName = 'inscription_confirmation';

            // Paramètres du template Meta (définis dans Business Manager)
            $parameters = [
                ['type' => 'text', 'text' => $data['parentName']],
                ['type' => 'text', 'text' => $data['studentName']],
                ['type' => 'text', 'text' => $data['classe']],
                ['type' => 'text', 'text' => $data['anneeUniversitaire']],
                ['type' => 'text', 'text' => $data['dateInscription']],
            ];

            return $this->sendTemplateMessage($phoneNumber, $templateName, $parameters);
        } catch (\Exception $e) {
            Log::error('Erreur envoi WhatsApp inscription', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification de paiement validé
     * Template : paiement_valide (UTILITY)
     */
    public function sendPaiementValideNotification($phoneNumber, $data)
    {
        try {
            $templateName = 'paiement_valide';

            $parameters = [
                ['type' => 'text', 'text' => $data['parentName']],
                ['type' => 'text', 'text' => $data['studentName']],
                ['type' => 'text', 'text' => number_format($data['montant'], 0, ',', ' ') . ' FCFA'],
                ['type' => 'text', 'text' => $data['reference']],
                ['type' => 'text', 'text' => $data['datePaiement']],
                ['type' => 'text', 'text' => number_format($data['soldeRestant'], 0, ',', ' ') . ' FCFA'],
            ];

            return $this->sendTemplateMessage($phoneNumber, $templateName, $parameters);
        } catch (\Exception $e) {
            Log::error('Erreur envoi WhatsApp paiement validé', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification de paiement rejeté
     * Template : paiement_rejete (UTILITY)
     */
    public function sendPaiementRejeteNotification($phoneNumber, $data)
    {
        try {
            $templateName = 'paiement_rejete';

            $parameters = [
                ['type' => 'text', 'text' => $data['parentName']],
                ['type' => 'text', 'text' => $data['studentName']],
                ['type' => 'text', 'text' => number_format($data['montant'], 0, ',', ' ') . ' FCFA'],
                ['type' => 'text', 'text' => $data['motifRejet']],
                ['type' => 'text', 'text' => $data['dateRejet']],
            ];

            return $this->sendTemplateMessage($phoneNumber, $templateName, $parameters);
        } catch (\Exception $e) {
            Log::error('Erreur envoi WhatsApp paiement rejeté', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification d'absence
     * Template : absence_notification (UTILITY)
     */
    public function sendAbsenceNotification($phoneNumber, $data)
    {
        try {
            $templateName = 'absence_notification';

            $parameters = [
                ['type' => 'text', 'text' => $data['parentName']],
                ['type' => 'text', 'text' => $data['studentName']],
                ['type' => 'text', 'text' => $data['dateAbsence']],
                ['type' => 'text', 'text' => $data['matiere']],
                ['type' => 'text', 'text' => (string) $data['totalAbsencesMois']],
                ['type' => 'text', 'text' => (string) $data['tauxPresence'] . '%'],
            ];

            return $this->sendTemplateMessage($phoneNumber, $templateName, $parameters);
        } catch (\Exception $e) {
            Log::error('Erreur envoi WhatsApp absence', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification de bulletin publié
     * Template : bulletin_publie (UTILITY)
     */
    public function sendBulletinPublishedNotification($phoneNumber, $data)
    {
        try {
            $templateName = 'bulletin_publie';

            $parameters = [
                ['type' => 'text', 'text' => $data['parentName']],
                ['type' => 'text', 'text' => $data['studentName']],
                ['type' => 'text', 'text' => $data['periode']],
                ['type' => 'text', 'text' => (string) $data['moyenneGenerale'] . '/20'],
                ['type' => 'text', 'text' => (string) $data['rang']],
            ];

            return $this->sendTemplateMessage($phoneNumber, $templateName, $parameters);
        } catch (\Exception $e) {
            Log::error('Erreur envoi WhatsApp bulletin', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Envoyer une alerte de notes faibles
     * Template : alerte_notes_faibles (UTILITY)
     */
    public function sendLowGradesNotification($phoneNumber, $data)
    {
        try {
            $templateName = 'alerte_notes_faibles';

            // Construire liste matières en échec (max 3 pour WhatsApp)
            $matieresEchec = '';
            if (isset($data['matieresEnDifficulte']) && is_array($data['matieresEnDifficulte'])) {
                $top3 = array_slice($data['matieresEnDifficulte'], 0, 3);
                $matieresEchec = implode(', ', array_column($top3, 'nom'));
            }

            $parameters = [
                ['type' => 'text', 'text' => $data['parentName']],
                ['type' => 'text', 'text' => $data['studentName']],
                ['type' => 'text', 'text' => $data['periode']],
                ['type' => 'text', 'text' => (string) $data['moyenneGenerale'] . '/20'],
                ['type' => 'text', 'text' => $matieresEchec ?: 'Plusieurs matières'],
            ];

            return $this->sendTemplateMessage($phoneNumber, $templateName, $parameters);
        } catch (\Exception $e) {
            Log::error('Erreur envoi WhatsApp notes faibles', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
            ]);
            return false;
        }
    }

    /**
     * Envoyer un message template via WhatsApp Cloud API
     *
     * @param string $phoneNumber Numéro au format international (+2250XXXXXXXXX)
     * @param string $templateName Nom du template Meta approuvé
     * @param array $parameters Paramètres du template
     * @return bool|array
     */
    private function sendTemplateMessage($phoneNumber, $templateName, $parameters)
    {
        try {
            // Résolution per-request via TenantConfigResolver (cache 5min)
            $config = $this->configResolver->getConfig();

            if (! ($config['enabled'] ?? false)) {
                Log::info('[whatsapp] Skip: tenant WhatsApp disabled', [
                    'reason' => $config['reason'] ?? 'enabled=false',
                    'template' => $templateName,
                ]);
                return false;
            }

            $phoneNumberId = $config['phone_number_id'] ?? null;
            $accessToken = $config['access_token'] ?? null;

            if (empty($phoneNumberId) || empty($accessToken)) {
                Log::warning('[whatsapp] Tenant config incomplete', [
                    'has_phone_number_id' => ! empty($phoneNumberId),
                    'has_access_token' => ! empty($accessToken),
                ]);
                return false;
            }

            // Nettoyer le numéro de téléphone (enlever espaces, tirets, etc.)
            $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);

            // S'assurer que le numéro commence par +
            if (!str_starts_with($cleanPhone, '+')) {
                // Ajouter +225 pour la Côte d'Ivoire si pas de code pays
                $cleanPhone = '+225' . ltrim($cleanPhone, '0');
            }

            $url = self::API_BASE_URL . "/{$phoneNumberId}/messages";

            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $cleanPhone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'fr', // Français
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => $parameters,
                        ],
                    ],
                ],
            ];

            Log::info('Envoi WhatsApp template', [
                'template' => $templateName,
                'phone' => $cleanPhone,
                'payload' => $payload,
            ]);

            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('WhatsApp message envoyé avec succès', [
                    'message_id' => $result['messages'][0]['id'] ?? null,
                    'phone' => $cleanPhone,
                    'template' => $templateName,
                ]);
                return $result;
            } else {
                Log::error('Erreur API WhatsApp', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'phone' => $cleanPhone,
                    'template' => $templateName,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception envoi WhatsApp', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'template' => $templateName,
            ]);
            return false;
        }
    }

    /**
     * Vérifier si un numéro WhatsApp est valide
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isValidWhatsAppNumber($phoneNumber)
    {
        // Simple validation format (au minimum 10 chiffres)
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        return strlen($cleanPhone) >= 10;
    }

    /**
     * Obtenir le statut d'un message envoyé
     *
     * @param string $messageId
     * @return array|null
     */
    public function getMessageStatus($messageId)
    {
        try {
            $config = $this->configResolver->getConfig();
            $accessToken = $config['access_token'] ?? null;

            if (empty($accessToken)) {
                return null;
            }

            $url = self::API_BASE_URL . "/{$messageId}";

            $response = Http::withToken($accessToken)->get($url);

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Erreur récupération statut message WhatsApp', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
            ]);
            return null;
        }
    }
}
