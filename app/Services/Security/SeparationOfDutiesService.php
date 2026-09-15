<?php

namespace App\Services\Security;

use App\Helpers\SettingsHelper;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class SeparationOfDutiesService
{
    public function violation(string $rule, ?int $previousActorId, ?User $actor): ?string
    {
        if (! $this->enabled($rule) || ! $actor || ! $previousActorId) {
            return null;
        }

        if ((int) $previousActorId !== (int) $actor->id) {
            return null;
        }

        if ($actor->can(self::permissionDeContournement())) {
            // Le contournement du verrouillage comptable, lui, ecrit un
            // avertissement permanent ; celui-ci ne laissait aucune trace. Un
            // controle qui se leve sans se dire ne protege plus rien : on ne
            // peut pas savoir apres coup qui a signe les deux bouts d'une
            // meme chaine, ni combien de fois.
            Log::warning('Separation des devoirs contournee', [
                'regle' => $rule,
                'permission' => self::permissionDeContournement(),
                'user_id' => $actor->id,
                'acteur_precedent_id' => (int) $previousActorId,
            ]);

            return null;
        }

        return (string) config("sod.rules.{$rule}.message", 'Separation des devoirs requise pour cette action.');
    }

    public function enabled(string $rule): bool
    {
        $default = (bool) config("sod.rules.{$rule}.enabled", false);
        $setting = config("sod.rules.{$rule}.setting");

        if (! is_string($setting) || $setting === '') {
            return $default;
        }

        try {
            $value = SettingsHelper::get($setting, $default ? '1' : '0');
        } catch (\Throwable $e) {
            // Le repli est sur : il rend la valeur d'usine, donc la regle reste
            // active. Mais il le faisait sans un mot — et un repli muet sur une
            // regle de separation des devoirs se decouvre le jour d'un controle,
            // pas avant.
            Log::warning('Reglage de separation des devoirs illisible, repli sur la valeur d’usine', [
                'regle' => $rule,
                'reglage' => $setting,
                'valeur_usine' => $default,
                'erreur' => $e->getMessage(),
            ]);

            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /** La permission qui autorise a enchainer deux gestes d'une meme chaine. */
    public static function permissionDeContournement(): string
    {
        return (string) config('sod.bypass_permission', 'sod.bypass');
    }

    /**
     * Les regles telles que l'ecran des reglages doit les afficher.
     *
     * L'ecran ne connait aucune cle en dur : il boucle sur ce tableau. Ajouter
     * une regle dans `config/sod.php` (et la semer par migration) l'expose sans
     * toucher ni a la vue ni au controleur — c'est la lecon de la PR #591, ou
     * une chaine recopiee remettait une bascule a zero en silence.
     *
     * @return array<int, array{cle: string, label: string, hint: string, defaut: bool}>
     */
    public static function reglesExposables(): array
    {
        $exposables = [];

        foreach ((array) config('sod.rules', []) as $regle => $definition) {
            $cle = $definition['setting'] ?? null;

            if (! is_string($cle) || $cle === '') {
                continue;
            }

            $exposables[] = [
                'cle' => $cle,
                'label' => (string) ($definition['label'] ?? $regle),
                'hint' => (string) ($definition['hint'] ?? ''),
                'defaut' => (bool) ($definition['enabled'] ?? false),
            ];
        }

        return $exposables;
    }

    /**
     * Les cles de reglage des regles, pour la boucle d'enregistrement.
     *
     * @return array<int, string>
     */
    public static function clesDeReglage(): array
    {
        return array_column(self::reglesExposables(), 'cle');
    }
}
