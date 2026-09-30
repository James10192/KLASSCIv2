<?php

namespace App\Services\Admissions;

use App\Models\Setting;

/**
 * Réglages tenant du parcours d'inscription.
 *
 * Aucune école n'est citée ici : la branche `presentation` reste canonique et
 * chaque instance active le comportement dont elle a besoin via ses Settings.
 * Les valeurs par défaut sont volontairement neutres pour ne rien changer sur
 * les tenants existants après propagation.
 */
class ParcoursInscriptionReglages
{
    public const ACTIF = 'inscriptions.workflow.enabled';
    public const ORDRE = 'inscriptions.workflow.order';
    public const CHOIX_CLASSE = 'inscriptions.workflow.class_selection';
    public const ACTIVATION_COMPTE = 'inscriptions.workflow.account_activation';
    public const CANAL_IDENTIFIANTS = 'inscriptions.workflow.credentials_channel';
    public const TEMPLATE_WHATSAPP = 'inscriptions.workflow.whatsapp_template';
    public const PAIEMENT_VALIDE_REQUIS = 'inscriptions.workflow.require_validated_payment';
    public const PIECES_PHYSIQUES_REQUISES = 'inscriptions.workflow.require_physical_documents';

    public const ORDRE_CLASSIQUE = 'classic';
    public const ORDRE_CAISSE_PUIS_PIECES = 'cashier_then_documents';
    public const ORDRE_PIECES_PUIS_CAISSE = 'documents_then_cashier';

    public const CHOIX_CLASSE_AGENT = 'agent';
    public const CHOIX_CLASSE_ETUDIANT_UNIQUE = 'student_once';

    public const ACTIVATION_APRES_VALIDATION = 'after_validation';
    public const ACTIVATION_APRES_PAIEMENT = 'after_cashier_payment';

    public const CANAL_EMAIL = 'email';
    public const CANAL_WHATSAPP = 'whatsapp';
    public const CANAL_EMAIL_WHATSAPP = 'email_whatsapp';

    /**
     * Crée les réglages manquants sans modifier une valeur déjà choisie.
     */
    public function ensureDefaults(): void
    {
        foreach ($this->definitions() as $key => $definition) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $definition['default'],
                    'type' => $definition['type'],
                    'group' => 'admissions',
                    'category' => 'admissions',
                    'description' => $definition['description'],
                    'is_required' => false,
                    'default_value' => $definition['default'],
                    'validation_rules' => null,
                    'sort_order' => $definition['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    public function actif(): bool
    {
        return (bool) Setting::get(self::ACTIF, false);
    }

    public function ordre(): string
    {
        $value = (string) Setting::get(self::ORDRE, self::ORDRE_CLASSIQUE);

        return in_array($value, [
            self::ORDRE_CLASSIQUE,
            self::ORDRE_CAISSE_PUIS_PIECES,
            self::ORDRE_PIECES_PUIS_CAISSE,
        ], true) ? $value : self::ORDRE_CLASSIQUE;
    }

    public function choixClasse(): string
    {
        $value = (string) Setting::get(self::CHOIX_CLASSE, self::CHOIX_CLASSE_AGENT);

        return in_array($value, [self::CHOIX_CLASSE_AGENT, self::CHOIX_CLASSE_ETUDIANT_UNIQUE], true)
            ? $value
            : self::CHOIX_CLASSE_AGENT;
    }

    public function activationCompte(): string
    {
        $value = (string) Setting::get(self::ACTIVATION_COMPTE, self::ACTIVATION_APRES_VALIDATION);

        return in_array($value, [self::ACTIVATION_APRES_VALIDATION, self::ACTIVATION_APRES_PAIEMENT], true)
            ? $value
            : self::ACTIVATION_APRES_VALIDATION;
    }

    public function canalIdentifiants(): string
    {
        $value = (string) Setting::get(self::CANAL_IDENTIFIANTS, self::CANAL_EMAIL);

        return in_array($value, [self::CANAL_EMAIL, self::CANAL_WHATSAPP, self::CANAL_EMAIL_WHATSAPP], true)
            ? $value
            : self::CANAL_EMAIL;
    }

    public function templateWhatsApp(): string
    {
        return trim((string) Setting::get(self::TEMPLATE_WHATSAPP, ''));
    }

    public function paiementValideRequis(): bool
    {
        return (bool) Setting::get(self::PAIEMENT_VALIDE_REQUIS, true);
    }

    public function piecesPhysiquesRequises(): bool
    {
        return (bool) Setting::get(self::PIECES_PHYSIQUES_REQUISES, true);
    }

    public function parcoursGuideActif(): bool
    {
        return $this->actif() && $this->ordre() !== self::ORDRE_CLASSIQUE;
    }

    public function caisseAvantPieces(): bool
    {
        return $this->parcoursGuideActif() && $this->ordre() === self::ORDRE_CAISSE_PUIS_PIECES;
    }

    public function piecesAvantCaisse(): bool
    {
        return $this->parcoursGuideActif() && $this->ordre() === self::ORDRE_PIECES_PUIS_CAISSE;
    }

    public function etudiantChoisitClasse(): bool
    {
        return $this->parcoursGuideActif() && $this->choixClasse() === self::CHOIX_CLASSE_ETUDIANT_UNIQUE;
    }

    public function activerCompteApresPaiement(): bool
    {
        return $this->parcoursGuideActif() && $this->activationCompte() === self::ACTIVATION_APRES_PAIEMENT;
    }

    public function envoyerParEmail(): bool
    {
        return in_array($this->canalIdentifiants(), [self::CANAL_EMAIL, self::CANAL_EMAIL_WHATSAPP], true);
    }

    public function envoyerParWhatsApp(): bool
    {
        return in_array($this->canalIdentifiants(), [self::CANAL_WHATSAPP, self::CANAL_EMAIL_WHATSAPP], true);
    }

    /** @return array<string, array{default:string,type:string,description:string,sort_order:int}> */
    private function definitions(): array
    {
        return [
            self::ACTIF => [
                'default' => '0',
                'type' => 'boolean',
                'description' => "Active le parcours d'inscription configurable. Désactivé : le fonctionnement historique reste inchangé.",
                'sort_order' => 500,
            ],
            self::ORDRE => [
                'default' => self::ORDRE_CLASSIQUE,
                'type' => 'string',
                'description' => 'Ordre du parcours : classique, caisse puis pièces, ou pièces puis caisse.',
                'sort_order' => 501,
            ],
            self::CHOIX_CLASSE => [
                'default' => self::CHOIX_CLASSE_AGENT,
                'type' => 'string',
                'description' => "Détermine si la classe est choisie par un agent ou une seule fois par l'étudiant.",
                'sort_order' => 502,
            ],
            self::ACTIVATION_COMPTE => [
                'default' => self::ACTIVATION_APRES_VALIDATION,
                'type' => 'string',
                'description' => "Moment de création du compte étudiant : validation finale ou paiement de préinscription validé.",
                'sort_order' => 503,
            ],
            self::CANAL_IDENTIFIANTS => [
                'default' => self::CANAL_EMAIL,
                'type' => 'string',
                'description' => "Canal d'envoi des accès temporaires : email, WhatsApp ou les deux.",
                'sort_order' => 504,
            ],
            self::TEMPLATE_WHATSAPP => [
                'default' => '',
                'type' => 'string',
                'description' => "Nom du template WhatsApp Utility approuvé par Meta pour l'envoi des accès.",
                'sort_order' => 505,
            ],
            self::PAIEMENT_VALIDE_REQUIS => [
                'default' => '1',
                'type' => 'boolean',
                'description' => "Exige au moins un paiement validé avant le choix de classe par l'étudiant.",
                'sort_order' => 506,
            ],
            self::PIECES_PHYSIQUES_REQUISES => [
                'default' => '1',
                'type' => 'boolean',
                'description' => "Exige un dossier physique complet avant le choix de classe par l'étudiant.",
                'sort_order' => 507,
            ],
        ];
    }
}
