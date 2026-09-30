<?php

namespace App\Services\Admissions;

use App\Helpers\SettingsHelper;
use App\Models\Setting;

/**
 * Règles de parcours d'inscription pilotées par tenant.
 *
 * Le code est déployé depuis presentation vers toutes les instances : aucun
 * ordre métier propre à une école ne doit donc être codé en dur. Le mode
 * legacy reste le comportement par défaut tant qu'un établissement n'active
 * pas explicitement le workflow configurable.
 */
final class InscriptionWorkflowSettings
{
    public const ENABLED = 'inscriptions.workflow.enabled';
    public const MODE = 'inscriptions.workflow.mode';
    public const REQUIRE_RDV = 'inscriptions.workflow.require_rdv';
    public const ACCOUNT_ACTIVATION_STEP = 'inscriptions.workflow.account_activation_step';
    public const CLASS_CHOICE_ACTOR = 'inscriptions.workflow.class_choice_actor';
    public const CLASS_CHOICE_ONCE = 'inscriptions.workflow.class_choice_once';
    public const NOTIFY_EMAIL = 'inscriptions.workflow.notify_email';
    public const NOTIFY_WHATSAPP = 'inscriptions.workflow.notify_whatsapp';

    public const MODE_LEGACY = 'legacy';
    public const MODE_CAISSE_AVANT_PIECES = 'caisse_avant_pieces';
    public const MODE_PIECES_AVANT_CAISSE = 'pieces_avant_caisse';

    public const ACTIVATION_AFTER_PAYMENT = 'after_payment';
    public const ACTIVATION_AFTER_DOCUMENTS = 'after_documents';

    public const CLASS_ACTOR_ADMIN = 'administration';
    public const CLASS_ACTOR_STUDENT = 'student';

    /** @return array<string, array<string, mixed>> */
    public static function defaults(): array
    {
        return [
            self::ENABLED => [
                'value' => '0',
                'type' => 'boolean',
                'description' => "Activer le workflow d'inscription configurable pour cet établissement",
                'sort_order' => 171,
            ],
            self::MODE => [
                'value' => self::MODE_LEGACY,
                'type' => 'string',
                'description' => "Ordre des étapes physiques du parcours d'inscription",
                'sort_order' => 172,
            ],
            self::REQUIRE_RDV => [
                'value' => '1',
                'type' => 'boolean',
                'description' => "Exiger un rendez-vous avant le passage au guichet",
                'sort_order' => 173,
            ],
            self::ACCOUNT_ACTIVATION_STEP => [
                'value' => self::ACTIVATION_AFTER_PAYMENT,
                'type' => 'string',
                'description' => "Étape à partir de laquelle le compte étudiant peut être activé",
                'sort_order' => 174,
            ],
            self::CLASS_CHOICE_ACTOR => [
                'value' => self::CLASS_ACTOR_ADMIN,
                'type' => 'string',
                'description' => "Qui choisit la classe lors de la finalisation de l'inscription",
                'sort_order' => 175,
            ],
            self::CLASS_CHOICE_ONCE => [
                'value' => '1',
                'type' => 'boolean',
                'description' => "Verrouiller le choix de classe après la première confirmation de l'étudiant",
                'sort_order' => 176,
            ],
            self::NOTIFY_EMAIL => [
                'value' => '1',
                'type' => 'boolean',
                'description' => "Envoyer les accès / étapes d'inscription par e-mail",
                'sort_order' => 177,
            ],
            self::NOTIFY_WHATSAPP => [
                'value' => '1',
                'type' => 'boolean',
                'description' => "Envoyer les accès / étapes d'inscription par WhatsApp quand disponible",
                'sort_order' => 178,
            ],
        ];
    }

    public function ensureDefaults(): void
    {
        foreach (self::defaults() as $key => $attrs) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $attrs['value'],
                    'type' => $attrs['type'],
                    'group' => 'scolarite',
                    'category' => 'scolarite',
                    'description' => $attrs['description'],
                    'is_required' => false,
                    'default_value' => $attrs['value'],
                    'validation_rules' => null,
                    'sort_order' => $attrs['sort_order'],
                ]
            );
        }
    }

    public function enabled(): bool
    {
        return $this->boolean(self::ENABLED, false);
    }

    public function mode(): string
    {
        $mode = (string) SettingsHelper::get(self::MODE, self::MODE_LEGACY);

        return array_key_exists($mode, self::modeOptions()) ? $mode : self::MODE_LEGACY;
    }

    public function usesManagedWorkflow(): bool
    {
        return $this->enabled() && $this->mode() !== self::MODE_LEGACY;
    }

    public function requiresAppointment(): bool
    {
        return $this->boolean(self::REQUIRE_RDV, true);
    }

    public function accountActivationStep(): string
    {
        $value = (string) SettingsHelper::get(self::ACCOUNT_ACTIVATION_STEP, self::ACTIVATION_AFTER_PAYMENT);

        return array_key_exists($value, self::activationOptions()) ? $value : self::ACTIVATION_AFTER_PAYMENT;
    }

    public function classChoiceActor(): string
    {
        $value = (string) SettingsHelper::get(self::CLASS_CHOICE_ACTOR, self::CLASS_ACTOR_ADMIN);

        return array_key_exists($value, self::classActorOptions()) ? $value : self::CLASS_ACTOR_ADMIN;
    }

    public function classChoiceOnce(): bool
    {
        return $this->boolean(self::CLASS_CHOICE_ONCE, true);
    }

    public function notifyEmail(): bool
    {
        return $this->boolean(self::NOTIFY_EMAIL, true);
    }

    public function notifyWhatsApp(): bool
    {
        return $this->boolean(self::NOTIFY_WHATSAPP, true);
    }

    /**
     * Première étape physique après acceptation du dossier en ligne.
     */
    public function firstPhysicalStep(): string
    {
        return match ($this->mode()) {
            self::MODE_CAISSE_AVANT_PIECES => 'caisse',
            self::MODE_PIECES_AVANT_CAISSE => 'pieces',
            default => 'legacy',
        };
    }

    public function acceptanceMessage(): string
    {
        if (! $this->usesManagedWorkflow()) {
            return 'Candidature acceptée.';
        }

        return match ($this->firstPhysicalStep()) {
            'caisse' => 'Candidature acceptée. Étape suivante : passage à la caisse pour la préinscription.',
            'pieces' => 'Candidature acceptée. Étape suivante : contrôle physique des pièces du dossier.',
            default => 'Candidature acceptée.',
        };
    }

    /** @return array<string, string> */
    public static function modeOptions(): array
    {
        return [
            self::MODE_LEGACY => 'Historique — comportement actuel de KLASSCI',
            self::MODE_CAISSE_AVANT_PIECES => 'Caisse puis contrôle physique des pièces (ESBTP Yamoussoukro)',
            self::MODE_PIECES_AVANT_CAISSE => 'Contrôle physique des pièces puis caisse',
        ];
    }

    /** @return array<string, string> */
    public static function activationOptions(): array
    {
        return [
            self::ACTIVATION_AFTER_PAYMENT => 'Après validation du paiement de préinscription',
            self::ACTIVATION_AFTER_DOCUMENTS => 'Après validation du contrôle physique des pièces',
        ];
    }

    /** @return array<string, string> */
    public static function classActorOptions(): array
    {
        return [
            self::CLASS_ACTOR_ADMIN => "L'administration choisit la classe",
            self::CLASS_ACTOR_STUDENT => "L'étudiant choisit sa classe dans son espace",
        ];
    }

    private function boolean(string $key, bool $default): bool
    {
        $value = SettingsHelper::get($key, $default ? '1' : '0');

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'oui', 'on'], true);
    }
}
