<?php

namespace App\Domain\Assistant\Actions\Classes;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Propose de modifier des classes existantes : nombre de places, nom, code,
 * activation. Une ou plusieurs classes, désignées par code ou identifiant, ou
 * par filière × niveau (« passe toutes les GBAT de 1re année à 60 places »).
 *
 * Volontairement hors de portée : la filière et le niveau. Les changer touche
 * les matières de la classe (l'écran les réattache), le reflet LMD et les
 * inscriptions : ça passe par l'écran, où la personne voit tout.
 *
 * Gardes :
 *  - les places ne descendent jamais sous l'effectif inscrit cette année ;
 *  - renommer ou recoder ne vaut que pour UNE classe, et le code reste unique
 *    (classes supprimées comprises, comme à la création) ;
 *  - désactiver une classe qui a des inscrits est signalé, pas refusé : la
 *    classe disparaît des listes de choix, les inscrits restent.
 */
class ModifierClasses extends ActionAgent
{
    private const MAX_CLASSES = 120;

    public function cle(): string
    {
        return 'modification_classes';
    }

    public function libelle(): string
    {
        return 'Préparation de la modification des classes…';
    }

    public function description(): string
    {
        return 'PROPOSE de modifier des classes existantes : places (nombre), nom et code (une seule classe), active (true/false). '
            . 'Désigne les classes par `classes` (codes ou identifiants, vus avec search_classes) OU par `filieres` + `niveaux` (codes). '
            . "Ne change ni la filière ni le niveau : renvoie vers l'écran. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'classes' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes ou identifiants des classes.'],
                'filieres' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes de filières (à la place de `classes`).'],
                'niveaux' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes de niveaux (avec `filieres`).'],
                'places' => ['type' => 'integer', 'description' => 'Nouveau nombre de places.'],
                'nom' => ['type' => 'string', 'description' => 'Nouveau nom (une seule classe).'],
                'code' => ['type' => 'string', 'description' => 'Nouveau code (une seule classe).'],
                'active' => ['type' => 'boolean', 'description' => 'Activer (true) ou désactiver (false).'],
            ],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Modifier des classes';
        [$classes, $manques] = $this->classes($args);

        $changements = array_filter([
            'places_totales' => isset($args['places']) ? (int) $args['places'] : null,
            'name' => isset($args['nom']) ? trim((string) $args['nom']) : null,
            'code' => isset($args['code']) ? mb_strtoupper(trim((string) $args['code'])) : null,
            'is_active' => array_key_exists('active', $args) ? (bool) $args['active'] : null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($changements === []) {
            $manques[] = 'Que faut-il changer : places, nom, code ou activation ?';
        }
        if (isset($changements['places_totales']) && $changements['places_totales'] < 1) {
            $manques[] = 'Le nombre de places doit être au moins 1.';
        }
        if ((isset($changements['name']) || isset($changements['code'])) && $classes->count() > 1) {
            $manques[] = 'Renommer ou recoder se fait classe par classe : indique une seule classe.';
        }
        if (isset($changements['code']) && $classes->count() === 1
            && ESBTPClasse::withTrashed()->whereRaw('UPPER(code) = ?', [$changements['code']])->where('id', '!=', $classes->first()->id)->exists()) {
            $manques[] = "Le code {$changements['code']} est déjà pris par une autre classe.";
        }
        if ($classes->count() > self::MAX_CLASSES) {
            $manques[] = 'Plus de '.self::MAX_CLASSES.' classes : précise la sélection.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($manques)));
        }

        $effectifs = $this->effectifs($classes->pluck('id')->all());
        $trop = [];
        $avertissements = [];
        $lignes = [];
        foreach ($classes as $c) {
            $inscrits = $effectifs[$c->id] ?? 0;
            if (isset($changements['places_totales']) && $changements['places_totales'] < $inscrits) {
                $trop[] = "{$c->name} ({$inscrits} inscrits)";
            }
            if (($changements['is_active'] ?? null) === false && $inscrits > 0) {
                $avertissements[] = "{$c->name} a {$inscrits} inscrit(s) cette année : désactivée, elle ne sera plus proposée, les inscrits restent.";
            }
            $lignes[] = [
                (string) $c->name, (string) $c->code, (string) $inscrits,
                $this->avantApres((int) $c->places_totales, $changements['places_totales'] ?? null),
                isset($changements['name']) ? $c->name.' → '.$changements['name'] : '—',
                isset($changements['code']) ? $c->code.' → '.$changements['code'] : '—',
                array_key_exists('is_active', $changements) ? ($c->is_active ? 'Active' : 'Inactive').' → '.($changements['is_active'] ? 'Active' : 'Inactive') : '—',
            ];
        }
        if ($trop !== []) {
            return new Proposition(titre: $titre, resume: '', manques: ['Moins de places que d\'inscrits cette année : '.implode(', ', $trop).'.']);
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d classe(s) modifiée(s).', $classes->count()),
            tableau: [
                'colonnes' => ['Classe', 'Code', 'Inscrits', 'Places', 'Nom', 'Nouveau code', 'Statut'],
                'lignes' => $lignes,
            ],
            avertissements: $avertissements,
            donnees: ['ids' => $classes->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all(), 'changements' => $changements],
            etat: ['classes' => $this->etat($classes->pluck('id')->all())],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $ids = $proposition->donnees['ids'];
        $changements = $proposition->donnees['changements'];

        DB::transaction(function () use ($ids, $changements, $proposition, $user) {
            ESBTPClasse::whereIn('id', $ids)->lockForUpdate()->get(['id']);
            if ($this->etat($ids) !== $proposition->etat['classes']) {
                throw new PropositionPerimee('Ces classes ont changé depuis la proposition : relance-la.');
            }
            if (isset($changements['places_totales'])) {
                foreach ($this->effectifs($ids) as $inscrits) {
                    if ($inscrits > $changements['places_totales']) {
                        throw new PropositionPerimee('Des inscriptions ont été ajoutées entre-temps : relance la proposition.');
                    }
                }
            }
            // Modèle par modèle : les événements (systeme_academique, audit) jouent.
            foreach (ESBTPClasse::whereIn('id', $ids)->get() as $classe) {
                $classe->update($changements + ['updated_by' => $user->id]);
            }
        });

        return [
            'message' => count($ids).' classe(s) modifiée(s).',
            'lien' => route('esbtp.classes.index', [], false),
            'model_type' => ESBTPClasse::class,
            'model_id' => $ids[0] ?? null,
            'details' => ['ids' => $ids, 'changements' => $changements],
        ];
    }

    /** @return array{0: Collection, 1: string[]} */
    private function classes(array $args): array
    {
        $designations = array_values(array_filter(array_map(fn ($v) => trim((string) $v), (array) ($args['classes'] ?? []))));
        $filieres = array_map('mb_strtoupper', array_filter((array) ($args['filieres'] ?? [])));
        $niveaux = array_map('mb_strtoupper', array_filter((array) ($args['niveaux'] ?? [])));

        if ($designations !== []) {
            $trouvees = ESBTPClasse::query()
                ->where(function ($q) use ($designations) {
                    $ids = array_filter($designations, 'ctype_digit');
                    $q->whereIn('id', $ids ?: [0])
                        ->orWhereIn(DB::raw('UPPER(code)'), array_map('mb_strtoupper', $designations));
                })->get();
            $reconnues = $trouvees->flatMap(fn ($c) => [(string) $c->id, mb_strtoupper((string) $c->code)])->all();
            $inconnues = array_filter($designations, fn ($d) => ! in_array(mb_strtoupper($d), $reconnues, true));

            return [$trouvees, $inconnues === [] ? [] : ['Classe(s) introuvable(s) : '.implode(', ', $inconnues).'. Vérifie avec search_classes.']];
        }
        if ($filieres === []) {
            return [collect(), ['Quelles classes ? Donne leurs codes, ou une filière (et un niveau).']];
        }

        $classes = ESBTPClasse::query()
            ->whereHas('filiere', fn ($q) => $q->whereIn(DB::raw('UPPER(code)'), $filieres))
            ->when($niveaux !== [], fn ($q) => $q->whereHas('niveau', fn ($n) => $n->whereIn(DB::raw('UPPER(code)'), $niveaux)))
            ->orderBy('name')->get();

        return [$classes, $classes->isEmpty() ? ['Aucune classe pour ces filières et niveaux.'] : []];
    }

    /** @return array<int, int> inscrits actifs de l'année courante, par classe */
    private function effectifs(array $ids): array
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        return DB::table('esbtp_inscriptions')->whereIn('classe_id', $ids)
            ->where('status', 'active')->whereNull('deleted_at')
            ->when($annee, fn ($q) => $q->where('annee_universitaire_id', $annee))
            ->groupBy('classe_id')->selectRaw('classe_id, count(*) as n')
            ->pluck('n', 'classe_id')->map(fn ($n) => (int) $n)->all();
    }

    /** @return array<int, array{id: int, name: string, code: string, places: int, active: bool}> */
    private function etat(array $ids): array
    {
        return ESBTPClasse::whereIn('id', $ids)->orderBy('id')->get(['id', 'name', 'code', 'places_totales', 'is_active'])
            ->map(fn ($c) => ['id' => (int) $c->id, 'name' => (string) $c->name, 'code' => (string) $c->code,
                'places' => (int) $c->places_totales, 'active' => (bool) $c->is_active])->all();
    }

    private function avantApres(int $avant, ?int $apres): string
    {
        return $apres === null || $apres === $avant ? (string) $avant : $avant.' → '.$apres;
    }
}
