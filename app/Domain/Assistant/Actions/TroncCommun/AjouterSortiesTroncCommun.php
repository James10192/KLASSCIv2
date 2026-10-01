<?php

namespace App\Domain\Assistant\Actions\TroncCommun;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Domain\BtsTroncCommun\ConfigurationTroncCommun;
use App\Models\ESBTPClasse;
use App\Models\ESBTPClasseOrientationTarget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Propose d'ouvrir des sorties à une classe de tronc commun : les classes de
 * spécialité vers lesquelles ses étudiants pourront être orientés. Écrit par
 * ConfigurationTroncCommun, le chemin de l'écran de la classe et de la CLI ;
 * droit bts_tronc_commun.manage_targets, comme l'écran.
 *
 * Les classes cibles viennent de la personne : jamais déduites d'un nom
 * proche. Une sortie déjà ouverte est signalée et laissée telle quelle.
 */
class AjouterSortiesTroncCommun extends ActionAgent
{
    private const MAX = 20;

    public function cle(): string
    {
        return 'sortie_tronc_commun';
    }

    public function description(): string
    {
        return "PROPOSE d'ouvrir des sorties à une classe de tronc commun BTS : `classe` = code ou identifiant de la classe TC, `cibles` = codes ou identifiants des classes de spécialité (même niveau), `semestre` = semestre à partir duquel l'orientation vaut (sans lui : 2 pour une nouvelle sortie, inchangé pour une sortie rouverte). "
            . 'Les classes cibles sont celles que la personne nomme (vérifie-les avec search_classes). Rien n\'est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classe' => ['type' => 'string', 'description' => 'Classe de tronc commun (code ou identifiant).'],
                'cibles' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Classes de spécialité (codes ou identifiants).'],
                'semestre' => ['type' => 'integer', 'description' => 'Semestre d\'activation (1 à 8).'],
            ],
            'required' => ['classe', 'cibles'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Sorties de tronc commun';
        $source = $this->classes([(string) ($args['classe'] ?? '')])->first();
        $designations = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($args['cibles'] ?? []))));
        $manques = [];
        if (! $source) {
            $manques[] = 'Quelle classe de tronc commun ? Donne son code (search_classes).';
        }
        if ($designations === []) {
            $manques[] = 'Vers quelles classes de spécialité ? Donne leurs codes.';
        }
        if (count($designations) > self::MAX) {
            $manques[] = 'Plus de '.self::MAX.' classes cibles : découpe la demande.';
        }
        // null : non donné — 2 pour une nouvelle sortie (défaut de l'écran),
        // inchangé pour une sortie rouverte.
        $semestre = isset($args['semestre']) ? (int) $args['semestre'] : null;
        if ($semestre !== null && ($semestre < 1 || $semestre > 8)) {
            $manques[] = 'Le semestre d\'activation va de 1 à 8.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $cibles = $this->classes($designations);
        $reconnues = $cibles->flatMap(fn ($c) => [(string) $c->id, mb_strtoupper((string) $c->code)])->all();
        $inconnues = array_filter($designations, fn ($d) => ! in_array(mb_strtoupper($d), $reconnues, true));
        if ($inconnues !== []) {
            $manques[] = 'Classe(s) introuvable(s) : '.implode(', ', $inconnues).'. Vérifie avec search_classes.';
        }
        $configuration = app(ConfigurationTroncCommun::class);
        foreach ($cibles as $cible) {
            if ($refus = $configuration->refusSortie($source, $cible)) {
                $manques[] = $refus;
            }
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($manques)));
        }

        $existantes = $this->existantes($source->id);
        $avertissements = [];
        $lignes = [];
        $nouvelles = [];
        foreach ($cibles as $cible) {
            $existante = $existantes[$cible->id] ?? null;
            if ($existante && $existante['active']) {
                $avertissements[] = "{$cible->name} est déjà une sortie ouverte : laissée telle quelle.";
            } else {
                $nouvelles[] = (int) $cible->id;
            }
            if (! $cible->is_active) {
                $avertissements[] = "{$cible->name} est inactive : elle ne sera proposée à l'orientation qu'une fois réactivée.";
            }
            $lignes[] = [(string) $cible->name, (string) $cible->code, (string) ($cible->filiere?->name ?? '—'),
                $existante ? ($existante['active'] ? 'Déjà ouverte' : 'Réactivée') : 'Nouvelle',
                'S'.($existante && $existante['active'] ? $existante['semestre'] : ($semestre ?? $existante['semestre'] ?? 2))];
        }
        if ($nouvelles === []) {
            return new Proposition(titre: $titre, resume: '', manques: ['Toutes ces sorties sont déjà ouvertes : rien à changer.']);
        }

        return new Proposition(
            titre: $titre.' · '.$source->name,
            resume: count($nouvelles)." sortie(s) ouverte(s) depuis {$source->name}.",
            tableau: ['colonnes' => ['Classe cible', 'Code', 'Filière', 'Sortie', 'À partir de'], 'lignes' => $lignes],
            avertissements: $avertissements,
            donnees: ['source_id' => (int) $source->id, 'cibles' => $nouvelles, 'semestre' => $semestre],
            etat: ['sorties' => $existantes],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        DB::transaction(function () use ($d, $proposition) {
            $source = ESBTPClasse::with('filiere')->lockForUpdate()->findOrFail($d['source_id']);
            if ($this->existantes($source->id) !== $proposition->etat['sorties']) {
                throw new PropositionPerimee('Les sorties de cette classe ont changé depuis la proposition.');
            }
            $configuration = app(ConfigurationTroncCommun::class);
            foreach (ESBTPClasse::with('filiere')->whereIn('id', $d['cibles'])->orderBy('id')->get() as $cible) {
                $configuration->ajouterSortie($source, $cible, $d['semestre']);
            }
        });

        return [
            'message' => count($d['cibles']).' sortie(s) de tronc commun ouverte(s).',
            'lien' => route('esbtp.classes.show', $d['source_id'], false),
            'model_type' => ESBTPClasse::class,
            'model_id' => (int) $d['source_id'],
            'details' => $d,
        ];
    }

    private function classes(array $designations): Collection
    {
        $designations = array_values(array_filter(array_map('trim', $designations)));
        if ($designations === []) {
            return collect();
        }
        $ids = array_filter($designations, 'ctype_digit');

        return ESBTPClasse::with('filiere')
            ->where(fn ($q) => $q->whereIn('id', $ids ?: [0])->orWhereIn(DB::raw('UPPER(code)'), array_map('mb_strtoupper', $designations)))
            ->orderBy('id')->get();
    }

    /** @return array<int, array{active: bool, semestre: int}> */
    private function existantes(int $sourceId): array
    {
        return ESBTPClasseOrientationTarget::where('source_classe_id', $sourceId)->orderBy('target_classe_id')->get()
            ->mapWithKeys(fn ($t) => [(int) $t->target_classe_id => ['active' => (bool) $t->is_active, 'semestre' => (int) $t->semestre_activation]])
            ->all();
    }
}
