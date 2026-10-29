<?php

namespace App\Services\Security;

use App\Enums\ModeSeparationDesDevoirs;
use App\Helpers\SettingsHelper;
use App\Models\User;
use Illuminate\Support\Facades\Log;

final class SeparationOfDutiesService
{
    /**
     * La définition d'une règle, lue SANS passer par un chemin pointé.
     *
     * `config('sod.rules.lmd.jury.publish.enabled')` ne peut pas marcher :
     * `Arr::get()` découpe sur les points, donc il cherche un tableau imbriqué
     * `rules → lmd → jury → publish`, alors que la clé est littéralement
     * `'lmd.jury.publish'`, en un seul morceau. Il ne trouve rien et rend le
     * défaut — vide pour le mode, `null` pour `setting`.
     *
     * Conséquence : la règle retombait toujours sur « inactif », `violation()`
     * sortait au premier garde, et les TROIS règles de séparation des devoirs
     * n'empêchaient rien depuis leur écriture. Aucune erreur, aucun journal :
     * l'écran des jurys laissait simplement passer.
     *
     * @return array<string, mixed>
     */
    private function regle(string $rule): array
    {
        $regles = (array) config('sod.rules', []);

        if (! isset($regles[$rule])) {
            // Le repli muet par lequel le défaut ci-dessus est arrivé jusqu'en
            // production. Un nom inconnu — faute de frappe sur un site d'appel,
            // règle renommée dans `config/sod.php` sans que l'appelant suive —
            // rend un tableau vide, donc `mode()` rend INACTIF, donc
            // `violation()` sort au premier garde : le contrôle disparaît en
            // silence, exactement comme les trois règles l'ont fait.
            //
            // Journaliser ne rétablit pas le contrôle, mais rend la panne
            // trouvable. C'est la leçon déjà écrite dans
            // `ESBTPSettingsController::update()` à propos des clés pointées.
            Log::warning('Regle de separation des devoirs inconnue : le controle ne s appliquera pas', [
                'regle' => $rule,
                'regles_connues' => array_keys($regles),
            ]);

            return [];
        }

        return (array) $regles[$rule];
    }

    public function violation(string $rule, ?int $previousActorId, ?User $actor): ?string
    {
        $mode = $this->mode($rule);

        if (! $mode->sApplique() || ! $actor || ! $previousActorId) {
            return null;
        }

        if ((int) $previousActorId !== (int) $actor->id) {
            return null;
        }

        if (! $mode->refuse()) {
            // Mode observation : la regle constate et laisse passer. C'est ce
            // qui rend ces controles deployables sur des instances en service
            // ou la meme personne tient les deux bouts depuis toujours — elle
            // voit d'abord ce que la regle bloquerait, puis decide.
            //
            // Journalise au meme niveau qu'un contournement : c'est la meme
            // information — quelqu'un a enchaine deux gestes d'une meme chaine
            // — et elle doit se retrouver d'un seul coup d'oeil au journal.
            Log::warning('Separation des devoirs enfreinte, mode observation : le geste est passe', [
                'regle' => $rule,
                'mode' => $mode->value,
                'user_id' => $actor->id,
                'acteur_precedent_id' => (int) $previousActorId,
            ]);

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

        return $this->messageDeRefus($rule);
    }

    /** Le message montré à qui est bloqué par la règle. */
    public function messageDeRefus(string $rule): string
    {
        $message = $this->regle($rule)['message'] ?? null;

        return is_string($message) && $message !== ''
            ? $message
            : 'Separation des devoirs requise pour cette action.';
    }

    /**
     * Ce que la regle fait quand elle est enfreinte.
     *
     * Trois etats et non deux : voir `ModeSeparationDesDevoirs`, qui porte le
     * pourquoi. Une regle inconnue ou sans cle de reglage retombe sur INACTIF,
     * ce que `regle()` journalise deja.
     */
    public function mode(string $rule): ModeSeparationDesDevoirs
    {
        $definition = $this->regle($rule);
        $default = ModeSeparationDesDevoirs::depuisReglage($definition['mode'] ?? null)
            ?? ModeSeparationDesDevoirs::INACTIF;
        $setting = $definition['setting'] ?? null;

        if (! is_string($setting) || $setting === '') {
            return $default;
        }

        try {
            $value = SettingsHelper::get($setting, $default->value);
        } catch (\Throwable $e) {
            // Le repli est sur : il rend la valeur d'usine, donc la regle reste
            // active. Mais il le faisait sans un mot — et un repli muet sur une
            // regle de separation des devoirs se decouvre le jour d'un controle,
            // pas avant.
            Log::warning('Reglage de separation des devoirs illisible, repli sur la valeur d’usine', [
                'regle' => $rule,
                'reglage' => $setting,
                'valeur_usine' => $default->value,
                'erreur' => $e->getMessage(),
            ]);

            return $default;
        }

        $mode = ModeSeparationDesDevoirs::depuisReglage($value);

        if ($mode === null) {
            // Une valeur que personne ne sait lire : ni un mode connu, ni un
            // booleen d'avant le troisieme etat. Le repli est le defaut du
            // fichier de configuration, et il se DIT — un mode illisible sur
            // une regle de separation des devoirs se decouvre le jour d'un
            // controle, pas avant.
            Log::warning('Mode de separation des devoirs inconnu, repli sur la valeur d’usine', [
                'regle' => $rule,
                'reglage' => $setting,
                'valeur_lue' => is_scalar($value) ? (string) $value : gettype($value),
                'valeur_usine' => $default->value,
            ]);

            return $default;
        }

        return $mode;
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
     * @return array<int, array{cle: string, label: string, hint: string, defaut: string}>
     */
    public static function reglesExposables(): array
    {
        $exposables = [];

        foreach ((array) config('sod.rules', []) as $regle => $definition) {
            $cle = $definition['setting'] ?? null;

            if (! is_string($cle) || $cle === '') {
                // La derniere porte muette de ce fichier, et elle donne sur la
                // meme piece que les autres : une regle declaree sans cle de
                // reglage disparait de l'ecran et n'est semee par aucune
                // migration — mais elle continue de s'APPLIQUER, avec sa valeur
                // d'usine, que plus personne ne peut ni voir ni changer.
                //
                // `SeparationOfDutiesExpositionTest` l'attrape au moment de
                // l'ecrire ; ce journal l'attrape sur une instance en service,
                // ou aucun test ne tourne.
                Log::warning('Regle de separation des devoirs sans cle de reglage : elle s appliquera sans etre pilotable', [
                    'regle' => $regle,
                ]);

                continue;
            }

            $exposables[] = [
                'cle' => $cle,
                'label' => (string) ($definition['label'] ?? $regle),
                'hint' => (string) ($definition['hint'] ?? ''),
                'defaut' => (ModeSeparationDesDevoirs::depuisReglage($definition['mode'] ?? null)
                    ?? ModeSeparationDesDevoirs::INACTIF)->value,
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
