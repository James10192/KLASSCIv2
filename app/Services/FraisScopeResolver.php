<?php

namespace App\Services;

use App\Models\ESBTPClasse;
use App\Models\ESBTPInscription;
use App\Models\ESBTPLMDParcours;

class FraisScopeResolver
{
    public const SYSTEME_BTS = 'BTS';
    public const SYSTEME_LMD = 'LMD';

    public function resolveForClasse(ESBTPClasse $classe): array
    {
        $classe->loadMissing(['filiere', 'niveau', 'parcours.mention.domaine']);

        $isLmd = $classe->systeme_academique === self::SYSTEME_LMD;
        $parcours = $isLmd ? $classe->parcours : null;
        $mention = $parcours?->mention;
        $domaine = $mention?->domaine;

        return [
            'systeme' => $isLmd ? self::SYSTEME_LMD : self::SYSTEME_BTS,
            'niveau_id' => $classe->niveau_etude_id,
            'filiere_id' => $isLmd ? null : $classe->filiere_id,
            'parcours_id' => $isLmd ? $classe->parcours_id : null,
            'classe_id' => $classe->id,
            'annee_universitaire_id' => $classe->annee_universitaire_id,
            'mention' => $mention?->name,
            'mention_id' => $mention?->id,
            'domaine' => $domaine?->name,
            'domaine_id' => $domaine?->id,
            'parcours' => $parcours?->name,
            'label_scope' => $this->buildLabel(
                $isLmd ? self::SYSTEME_LMD : self::SYSTEME_BTS,
                $classe->filiere?->name,
                $classe->niveau?->name,
                $parcours?->name,
                $mention?->name
            ),
        ];
    }

    public function resolveForInscription(ESBTPInscription $inscription): array
    {
        $inscription->loadMissing(['classe.parcours.mention.domaine', 'classe.filiere', 'classe.niveau', 'filiere', 'niveau']);

        if ($inscription->classe) {
            return $this->resolveForClasse($inscription->classe);
        }

        return [
            'systeme' => self::SYSTEME_BTS,
            'niveau_id' => $inscription->niveau_id,
            'filiere_id' => $inscription->filiere_id,
            'parcours_id' => null,
            'classe_id' => null,
            'annee_universitaire_id' => $inscription->annee_universitaire_id,
            'mention' => null,
            'mention_id' => null,
            'domaine' => null,
            'domaine_id' => null,
            'parcours' => null,
            'label_scope' => $this->buildLabel(self::SYSTEME_BTS, $inscription->filiere?->name, $inscription->niveau?->name),
        ];
    }

    public function resolveFromConfigurationParams(array $params): array
    {
        $systeme = strtoupper((string) ($params['systeme'] ?? self::SYSTEME_BTS));
        $niveauId = $params['niveau_id'] ?? null;
        $filiereId = $params['filiere_id'] ?? null;
        $parcoursId = $params['parcours_id'] ?? null;
        $parcours = null;

        if ($systeme === self::SYSTEME_LMD && $parcoursId) {
            $parcours = ESBTPLMDParcours::with('mention.domaine')->find($parcoursId);
        }

        return [
            'systeme' => $systeme === self::SYSTEME_LMD ? self::SYSTEME_LMD : self::SYSTEME_BTS,
            'niveau_id' => $niveauId,
            'filiere_id' => $systeme === self::SYSTEME_LMD ? null : $filiereId,
            'parcours_id' => $systeme === self::SYSTEME_LMD ? $parcoursId : null,
            'classe_id' => $params['classe_id'] ?? null,
            'annee_universitaire_id' => $params['annee_universitaire_id'] ?? null,
            'mention' => $parcours?->mention?->name,
            'mention_id' => $parcours?->mention?->id,
            'domaine' => $parcours?->mention?->domaine?->name,
            'domaine_id' => $parcours?->mention?->domaine?->id,
            'parcours' => $parcours?->name,
            'label_scope' => $this->buildLabel(
                $systeme,
                $params['filiere_name'] ?? null,
                $params['niveau_name'] ?? null,
                $parcours?->name,
                $parcours?->mention?->name
            ),
        ];
    }

    private function buildLabel(string $systeme, ?string $filiereName = null, ?string $niveauName = null, ?string $parcoursName = null, ?string $mentionName = null): string
    {
        if ($systeme === self::SYSTEME_LMD) {
            return trim(implode(' - ', array_filter([$mentionName, $parcoursName, $niveauName])));
        }

        return trim(implode(' - ', array_filter([$filiereName, $niveauName])));
    }
}
