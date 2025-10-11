<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

/**
 * Service SMS pour fallback urgences
 *
 * IMPORTANT: Utilisation limitée (payant ~7 FCFA/SMS)
 * Usage :
 * - Parents sans WhatsApp (≈5-10%)
 * - Notifications CRITIQUES uniquement
 * - Fallback si WhatsApp échoue
 *
 * Providers supportés :
 * - Orange CI (Orange Developer API)
 * - Beem Africa
 * - SMS.to
 *
 * Date : 11 octobre 2025
 */
class SmsService
{
    private $provider;
    private $apiKey;
    private $senderId;
    private $apiUrl;

    public function __construct()
    {
        $this->provider = env('SMS_PROVIDER', 'orange'); // orange, beem, smsto
        $this->apiKey = env('SMS_API_KEY');
        $this->senderId = env('SMS_SENDER_ID', 'KLASSCI');

        // URLs par provider
        $this->apiUrl = match($this->provider) {
            'orange' => 'https://api.orange.com/smsmessaging/v1/outbound',
            'beem' => 'https://apisms.beem.africa/v1/send',
            'smsto' => 'https://api.sms.to/sms/send',
            default => null,
        };
    }

    /**
     * Obtenir un token OAuth2 Orange (valide 1h)
     */
    private function getOrangeToken()
    {
        try {
            $cacheKey = 'orange_sms_token';

            // Vérifier si token en cache (valide 50 minutes)
            $cachedToken = Cache::get($cacheKey);
            if ($cachedToken) {
                return $cachedToken;
            }

            // Demander nouveau token
            // NOTE: Orange API nécessite client_id/secret dans le body (pas en Authorization header)
            $response = Http::asForm()->post('https://api.orange.com/oauth/v3/token', [
                'grant_type' => 'client_credentials',
                'client_id' => env('ORANGE_CLIENT_ID'),
                'client_secret' => env('ORANGE_CLIENT_SECRET'),
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];

                // Mettre en cache pour 50 minutes (token valide 1h)
                Cache::put($cacheKey, $token, now()->addMinutes(50));

                Log::info('Token Orange obtenu avec succès', ['expires_in' => $data['expires_in'] ?? 3600]);
                return $token;
            }

            Log::error('Échec obtention token Orange', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;

        } catch (\Exception $e) {
            Log::error('Exception obtention token Orange: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Envoyer notification d'inscription par SMS
     * (Courte version - 160 caractères max)
     */
    public function sendInscriptionNotification($phoneNumber, $data)
    {
        $message = "KLASSCI: Inscription confirmée pour {$data['studentName']} - {$data['classe']} ({$data['anneeUniversitaire']}). Identifiants envoyés par email.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Envoyer notification paiement validé par SMS
     */
    public function sendPaiementValideNotification($phoneNumber, $data)
    {
        $montant = number_format($data['montant'], 0, ',', ' ');
        $message = "KLASSCI: Paiement validé - {$montant} FCFA pour {$data['studentName']}. Réf: {$data['reference']}.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Envoyer notification paiement rejeté par SMS
     */
    public function sendPaiementRejeteNotification($phoneNumber, $data)
    {
        $montant = number_format($data['montant'], 0, ',', ' ');
        $message = "KLASSCI: Paiement rejeté - {$montant} FCFA. Motif: {$data['motifRejet']}. Contactez l'administration.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Envoyer notification absence par SMS
     */
    public function sendAbsenceNotification($phoneNumber, $data)
    {
        $message = "KLASSCI: {$data['studentName']} absent(e) le {$data['dateAbsence']} en {$data['matiere']}. Total mois: {$data['totalAbsencesMois']} absences.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Envoyer notification bulletin par SMS
     */
    public function sendBulletinPublishedNotification($phoneNumber, $data)
    {
        $message = "KLASSCI: Bulletin {$data['periode']} disponible pour {$data['studentName']}. Moyenne: {$data['moyenneGenerale']}/20. Consultez la plateforme.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Envoyer alerte notes faibles par SMS
     */
    public function sendLowGradesNotification($phoneNumber, $data)
    {
        $message = "KLASSCI: Alerte académique - {$data['studentName']} Moyenne: {$data['moyenneGenerale']}/20. Contactez l'établissement.";
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Envoyer un SMS générique
     *
     * @param string $phoneNumber Numéro au format international ou local
     * @param string $message Texte du SMS (max 160 caractères recommandé)
     * @return bool|array
     */
    public function sendMessage($phoneNumber, $message)
    {
        try {
            // Vérifier que l'API est configurée
            // Pour Orange, on utilise OAuth2 (pas besoin de SMS_API_KEY)
            $isConfigured = $this->provider === 'orange'
                ? !empty($this->apiUrl) && !empty(env('ORANGE_CLIENT_ID'))
                : !empty($this->apiKey) && !empty($this->apiUrl);

            if (!$isConfigured) {
                Log::warning('SMS API non configurée', [
                    'provider' => $this->provider,
                    'api_key' => !empty($this->apiKey),
                    'api_url' => !empty($this->apiUrl),
                    'orange_client_id' => !empty(env('ORANGE_CLIENT_ID')),
                ]);
                return false;
            }

            // Nettoyer le numéro
            $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);

            // Ajouter +225 si nécessaire (Côte d'Ivoire)
            if (!str_starts_with($cleanPhone, '+')) {
                $cleanPhone = '+225' . ltrim($cleanPhone, '0');
            }

            // Limiter message à 160 caractères (1 SMS standard)
            if (strlen($message) > 160) {
                $message = substr($message, 0, 157) . '...';
                Log::warning('Message SMS tronqué à 160 caractères', ['phone' => $cleanPhone]);
            }

            // Envoyer selon le provider
            $result = match($this->provider) {
                'orange' => $this->sendViaOrange($cleanPhone, $message),
                'beem' => $this->sendViaBeem($cleanPhone, $message),
                'smsto' => $this->sendViaSmsTo($cleanPhone, $message),
                default => throw new \Exception('Provider SMS non supporté: ' . $this->provider),
            };

            return $result;

        } catch (\Exception $e) {
            Log::error('Erreur envoi SMS', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber,
                'provider' => $this->provider,
            ]);
            return false;
        }
    }

    /**
     * Envoyer via Orange Developer API (Côte d'Ivoire)
     * Documentation: https://developer.orange.com/apis/sms-ci/api-reference
     */
    private function sendViaOrange($phoneNumber, $message)
    {
        try {
            // Obtenir token OAuth2
            $token = $this->getOrangeToken();
            if (!$token) {
                Log::error('Impossible d\'obtenir token Orange');
                return false;
            }

            // Format sender address (doit commencer par tel:)
            $senderAddress = 'tel:+225' . env('SMS_SENDER_NUMBER', '0000000000');

            // Construire l'URL avec le sender
            $url = $this->apiUrl . '/' . urlencode($senderAddress) . '/requests';

            // Payload selon doc Orange
            $payload = [
                'outboundSMSMessageRequest' => [
                    'address' => ['tel:' . $phoneNumber],
                    'senderAddress' => $senderAddress,
                    'outboundSMSTextMessage' => [
                        'message' => $message
                    ],
                    'senderName' => $this->senderId
                ]
            ];

            Log::info('Envoi SMS Orange', [
                'phone' => $phoneNumber,
                'message_length' => strlen($message),
                'url' => $url,
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('SMS Orange envoyé avec succès', [
                    'phone' => $phoneNumber,
                    'response' => $result,
                ]);
                return $result;
            } else {
                Log::error('Erreur API Orange SMS', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'phone' => $phoneNumber,
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception SMS Orange: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer via Beem Africa
     */
    private function sendViaBeem($phoneNumber, $message)
    {
        try {
            $payload = [
                'source_addr' => $this->senderId,
                'schedule_time' => '',
                'encoding' => 0,
                'message' => $message,
                'recipients' => [
                    ['recipient_id' => '1', 'dest_addr' => $phoneNumber]
                ]
            ];

            Log::info('Envoi SMS Beem', [
                'phone' => $phoneNumber,
                'message_length' => strlen($message),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('SMS Beem envoyé avec succès', [
                    'phone' => $phoneNumber,
                    'response' => $result,
                ]);
                return $result;
            } else {
                Log::error('Erreur API Beem SMS', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception SMS Beem: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer via SMS.to
     */
    private function sendViaSmsTo($phoneNumber, $message)
    {
        try {
            $payload = [
                'message' => $message,
                'to' => $phoneNumber,
                'sender_id' => $this->senderId,
            ];

            Log::info('Envoi SMS SMS.to', [
                'phone' => $phoneNumber,
                'message_length' => strlen($message),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $result = $response->json();
                Log::info('SMS SMS.to envoyé avec succès', [
                    'phone' => $phoneNumber,
                    'response' => $result,
                ]);
                return $result;
            } else {
                Log::error('Erreur API SMS.to', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception SMS SMS.to: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier si un numéro est valide
     */
    public function isValidPhoneNumber($phoneNumber)
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        return strlen($cleanPhone) >= 10;
    }
}
