<?php

namespace App\Enums;

/**
 * Ce qu'une règle de séparation des devoirs fait quand elle est enfreinte.
 *
 * ## Pourquoi trois états et pas une case à cocher
 *
 * La norme OHADA veut que personne ne signe les deux bouts d'une même chaîne.
 * Mais une école à une seule secrétaire enfreint cette règle tous les jours,
 * sans fraude — elle n'a personne d'autre. Livrer le contrôle en tout-ou-rien
 * la place devant un choix qui n'en est pas un : subir un 403 sur un geste
 * quotidien, ou désactiver la règle et perdre toute trace.
 *
 * `OBSERVATION` est la sortie. Le contrôle s'applique, constate, journalise —
 * et laisse passer. L'école voit dans son journal ce que la règle bloquerait,
 * décide si elle peut réorganiser, puis durcit. C'est ce que demande
 * `rien-en-dur.md` : « un défaut sensé, jamais bloquant, et l'école durcit si
 * elle le veut ».
 *
 * C'est aussi ce qui rend le déploiement possible. Les trois règles n'ont
 * jamais rien empêché depuis leur écriture (un chemin de configuration pointé
 * les rendait muettes). Les livrer bloquantes ferait basculer d'un coup six
 * instances en service d'« aucun contrôle » à « 403 », sans que personne ait
 * pu s'y préparer.
 *
 * ## Ce que ce n'est PAS
 *
 * Ce n'est pas une permission de contournement. Le contournement
 * (`sod.bypass`) est nominatif : il dit QUI a le droit d'enchaîner deux gestes,
 * et journalise chaque levée. Le mode, lui, décrit la politique de
 * l'établissement pour tout le monde. Les deux coexistent.
 */
enum ModeSeparationDesDevoirs: string
{
    /** La règle refuse le second geste. */
    case BLOQUANT = 'bloquant';

    /** La règle constate et journalise, sans refuser. */
    case OBSERVATION = 'observation';

    /** La règle ne s'applique pas. */
    case INACTIF = 'inactif';

    /**
     * Le mode écrit dans un réglage d'instance, quelle que soit sa forme.
     *
     * Les réglages posés avant l'existence de ce troisième état portent un
     * booléen — `true`, `'1'`, `false`, `'0'` — et la colonne `settings.value`
     * rend tantôt un booléen, tantôt une chaîne selon le type déclaré. Un
     * `tryFrom()` seul rendrait `null` sur toutes ces valeurs et ferait
     * silencieusement retomber la règle sur son défaut.
     *
     * Une valeur illisible rend `null` : c'est à l'appelant de décider, et de
     * le dire. Rendre un mode arbitraire ici ferait exactement ce que cette
     * branche corrige ailleurs — un repli muet.
     */
    public static function depuisReglage(mixed $valeur): ?self
    {
        if ($valeur instanceof self) {
            return $valeur;
        }

        if (is_string($valeur) && ($mode = self::tryFrom(strtolower(trim($valeur)))) !== null) {
            return $mode;
        }

        if (is_bool($valeur) || (is_string($valeur) && in_array(strtolower(trim($valeur)), ['1', '0', 'true', 'false', 'on', 'off'], true))) {
            return filter_var($valeur, FILTER_VALIDATE_BOOLEAN) ? self::BLOQUANT : self::INACTIF;
        }

        return null;
    }

    /** La règle a-t-elle quelque chose à dire quand elle est enfreinte ? */
    public function sApplique(): bool
    {
        return $this !== self::INACTIF;
    }

    public function refuse(): bool
    {
        return $this === self::BLOQUANT;
    }

    public function label(): string
    {
        return match ($this) {
            self::BLOQUANT => 'Bloquer',
            self::OBSERVATION => 'Observer sans bloquer',
            self::INACTIF => 'Ne rien contrôler',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::BLOQUANT => 'Le second geste est refusé.',
            self::OBSERVATION => 'Le geste passe, mais il est consigné au journal.',
            self::INACTIF => 'Aucun contrôle, aucune trace.',
        };
    }

    /** @return array<string, string> valeur => libellé, pour un sélecteur */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $mode) {
            $options[$mode->value] = $mode->label();
        }

        return $options;
    }
}
