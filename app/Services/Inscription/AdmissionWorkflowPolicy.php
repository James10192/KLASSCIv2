<?php

namespace App\Services\Inscription;

use App\Helpers\SettingsHelper;

/**
 * Politique de parcours d'inscription propre a chaque instance KLASSCI.
 *
 * IMPORTANT : cette classe ne connait aucun nom d'ecole, aucun domaine et
 * aucun role. Une meme version du code est deployee partout ; seules les
 * Settings de l'instance decident de l'ordre des etapes et de ce que
 * l'etudiant peut faire lui-meme.
 */
final class AdmissionWorkflowPolicy
{
    public const MODE = 'inscriptions.workflow.mode';
    public const CHOIX_CLASSE_ETUDIANT = 'inscriptions.workflow.student_class_choice';
    public const CHOIX_CLASSE_UNIQUE = 'inscriptions.workflow.class_choice_once';
    public const ACTIVATION_STAGE = 'inscriptions.workflow.activation_stage';

    public const MODE_STANDARD = 'standard';
    public const MODE_CAISSE_PUIS_PIECES = 'caisse_puis_pieces';
    public const MODE_PIECES_PUIS_CAISSE = 'pieces_puis_caisse';

    public const ACTIVATION_PAIEMENT = 'payment';
    public const ACTIVATION_PIECES = 'documents';
    public const ACTIVATION_CLASSE = 'class';
    public const ACTIVATION_VALIDATION = 'validation';

    /** @return list<string> */
    public static function modes(): array
    {
        return [
            self::MODE_STANDARD,
            self::MODE_CAISSE_PUIS_PIECES,
            self::MODE_PIECES_PUIS_CAISSE,
        ];
    }

    /** @return list<string> */
    public static function activationStages(): array
    {
        return [
            self::ACTIVATION_PAIEMENT,
            self::ACTIVATION_PIECES,
            self::ACTIVATION_CLASSE,
            self::ACTIVATION_VALIDATION,
        ];
    }

    public function mode(): string
    {
        return self::normaliserMode((string) SettingsHelper::get(self::MODE, self::MODE_STANDARD));
    }

    public static function normaliserMode(string $mode): string
    {
        return in_array($mode, self::modes(), true) ? $mode : self::MODE_STANDARD;
    }

    public function caisseAvantPieces(): bool
    {
        return $this->mode() === self::MODE_CAISSE_PUIS_PIECES;
    }

    public function piecesAvantCaisse(): bool
    {
        return $this->mode() === self::MODE_PIECES_PUIS_CAISSE;
    }

    /**
     * Le mode standard conserve le comportement historique : la classe est
     * choisie par l'agent pendant l'inscription/pre-inscription.
     */
    public function choixClasseParEtudiant(): bool
    {
        return $this->flag(self::CHOIX_CLASSE_ETUDIANT, '0');
    }

    public function classeObligatoireALaCaisse(): bool
    {
        return ! $this->choixClasseParEtudiant();
    }

    public function choixClasseUnique(): bool
    {
        return $this->choixClasseParEtudiant()
            && $this->flag(self::CHOIX_CLASSE_UNIQUE, '1');
    }

    public function activationStage(): string
    {
        $stage = (string) SettingsHelper::get(self::ACTIVATION_STAGE, self::ACTIVATION_VALIDATION);

        return in_array($stage, self::activationStages(), true)
            ? $stage
            : self::ACTIVATION_VALIDATION;
    }

    /**
     * Sequence metier lisible par les ecrans et les services.
     *
     * Le rendez-vous reste pilote par les reglages existants
     * inscriptions.rdv.enabled / inscriptions.rdv.obligatoire. La sequence
     * indique sa place logique, pas qu'il est obligatoirement active.
     *
     * @return list<string>
     */
    public function sequence(): array
    {
        return self::sequencePour($this->mode(), $this->choixClasseParEtudiant());
    }

    /** @return list<string> */
    public static function sequencePour(string $mode, bool $choixClasseParEtudiant): array
    {
        $mode = self::normaliserMode($mode);

        $fin = $choixClasseParEtudiant
            ? ['classe_etudiant', 'validation']
            : ['validation'];

        return match ($mode) {
            self::MODE_CAISSE_PUIS_PIECES => array_merge(
                ['candidature', 'rendez_vous', 'caisse', 'pieces'],
                $fin,
            ),
            self::MODE_PIECES_PUIS_CAISSE => array_merge(
                ['candidature', 'rendez_vous', 'pieces', 'caisse'],
                $fin,
            ),
            default => array_merge(
                ['candidature', 'rendez_vous', 'inscription_agent'],
                $fin,
            ),
        };
    }

    private function flag(string $key, string $default): bool
    {
        $value = SettingsHelper::get($key, $default);

        return $value === true || $value === 1 || $value === '1';
    }
}
