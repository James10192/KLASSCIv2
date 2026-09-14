<?php

namespace App\Services\LMD;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPLMDDomaine;

/**
 * Le vocabulaire de la structure LMD d'un etablissement.
 *
 * Trois rangs, toujours : c'est le mot qui change d'une universite a l'autre
 * (Domaine → Mention → Parcours, ou Composante → Departement → Specialite).
 * Un ecran qui nomme un rang passe par ici plutot que d'ecrire « Domaine ».
 */
class VocabulaireStructure
{
    public const CLE_DOMAINE = 'lmd.structure_libelle_domaine';

    public const CLE_MENTION = 'lmd.structure_libelle_mention';

    public const CLE_PARCOURS = 'lmd.structure_libelle_parcours';

    public function domaine(): string
    {
        return $this->libelle(self::CLE_DOMAINE, 'Domaine');
    }

    public function mention(): string
    {
        return $this->libelle(self::CLE_MENTION, 'Mention');
    }

    public function parcours(): string
    {
        return $this->libelle(self::CLE_PARCOURS, 'Parcours');
    }

    /**
     * Le nom qu'un element du premier rang porte en propre : sa nature s'il en
     * a une (UFR, Ecole...), sinon le nom du rang.
     */
    public function natureDe(ESBTPLMDDomaine $domaine): string
    {
        return $domaine->nature?->label() ?? $this->domaine();
    }

    /** Le nom d'un rang par sa cle : 'domaine', 'mention' ou 'parcours'. */
    public function rang(string $cle): string
    {
        return match ($cle) {
            'domaine' => $this->domaine(),
            'mention' => $this->mention(),
            'parcours' => $this->parcours(),
            default => throw new \InvalidArgumentException("Rang LMD inconnu : {$cle}"),
        };
    }

    /** Le nom d'un rang au pluriel. */
    public function rangs(string $cle): string
    {
        return $this->pluriel($this->rang($cle));
    }

    /** @return array{domaine: string, mention: string, parcours: string} */
    public function tous(): array
    {
        return ['domaine' => $this->domaine(), 'mention' => $this->mention(), 'parcours' => $this->parcours()];
    }

    /**
     * Pluriel francais courant : « Domaine » → « Domaines », « Parcours » et
     * « Prix » invariables. Suffit aux intitules de rang ; un libelle irregulier
     * se regle en le saisissant tel qu'il doit s'afficher.
     */
    public function pluriel(string $libelle): string
    {
        if (preg_match('/[sxz]$/iu', $libelle)) {
            return $libelle;
        }

        return preg_match('/(au|eu)$/iu', $libelle) ? $libelle.'x' : $libelle.'s';
    }

    private function libelle(string $cle, string $defaut): string
    {
        $valeur = trim((string) SettingsHelper::get($cle, $defaut));

        return $valeur !== '' ? $valeur : $defaut;
    }
}
