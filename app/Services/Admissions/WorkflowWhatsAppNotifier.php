<?php

namespace App\Services\Admissions;

use App\Domain\Notifications\PhoneNormalizer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notification WhatsApp des accès créés par le parcours d'inscription.
 *
 * Le template Meta n'est jamais codé en dur : chaque tenant renseigne le nom
 * de son template Utility approuvé. Le template attendu possède quatre
 * variables, dans cet ordre : nom étudiant, identifiant, mot de passe
 * temporaire, URL de connexion.
 */
class WorkflowWhatsAppNotifier
{
    public function __construct(private ParcoursInscriptionReglages $reglages)
    {
    }

    public function envoyer(string $telephone, string $nom, string $username, string $motDePasse, string $loginUrl): bool
    {
        if (! $this->reglages->envoyerParWhatsApp()) {
            return false;
        }

        $template = $this->reglages->templateWhatsApp();
        if ($template === '') {
            Log::warning('Accès étudiant non envoyé par WhatsApp : aucun template Meta configuré.');

            return false;
        }

        $numero = PhoneNormalizer::toE164($telephone);
        if ($numero === null) {
            Log::warning('Accès étudiant non envoyé par WhatsApp : numéro illisible.');

            return false;
        }

        $phoneNumberId = (string) env('WHATSAPP_PHONE_NUMBER_ID', '');
        $accessToken = (string) env('WHATSAPP_ACCESS_TOKEN', '');
        if ($phoneNumberId === '' || $accessToken === '') {
            Log::warning('Accès étudiant non envoyé par WhatsApp : API Meta non configurée.');

            return false;
        }

        try {
            $response = Http::withToken($accessToken)
                ->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $numero,
                    'type' => 'template',
                    'template' => [
                        'name' => $template,
                        'language' => ['code' => 'fr'],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $nom],
                                ['type' => 'text', 'text' => $username],
                                ['type' => 'text', 'text' => $motDePasse],
                                ['type' => 'text', 'text' => $loginUrl],
                            ],
                        ]],
                    ],
                ]);

            if ($response->successful()) {
                Log::info('Accès étudiant envoyé par WhatsApp.', [
                    'template' => $template,
                    'phone' => $numero,
                ]);

                return true;
            }

            Log::error('Échec WhatsApp lors de l’envoi des accès étudiant.', [
                'template' => $template,
                'phone' => $numero,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Exception WhatsApp lors de l’envoi des accès étudiant.', [
                'template' => $template,
                'phone' => $numero,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
