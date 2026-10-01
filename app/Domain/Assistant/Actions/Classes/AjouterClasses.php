<?php

namespace App\Domain\Assistant\Actions\Classes;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Propose d'ajouter une classe (ou plusieurs) à chaque couple filière × niveau
 * BTS demandé. Cas d'origine : ISLG, « une classe de plus de 60 places sur toutes
 * les filières et tous les niveaux » — trente classes d'un coup.
 *
 * Le nom suit les classes sœurs du couple : même préfixe, lettre suivante
 * (GBAT 1C → GBAT 1D ; « TP 1 », sans lettre, compte pour A). Un couple sans
 * aucune classe prend le code de la filière comme préfixe, et la proposition le
 * signale : c'est un choix à relire, pas une convention de l'école.
 *
 * LMD exclu : une classe LMD se range sous un parcours et s'ancre par un reflet
 * de filière (classe-lmd-filiere-as-mention) — elle passe par l'écran.
 */
class AjouterClasses extends ActionAgent
{
    private const MAX_CLASSES = 120;

    public function cle(): string
    {
        return 'creation_classes';
    }

    public function libelle(): string
    {
        return 'Préparation des nouvelles classes…';
    }

    public function description(): string
    {
        return "PROPOSE d'ajouter des classes BTS : une (ou `nombre`) par couple filière × niveau, avec un nombre de places. "
            . "filieres et niveaux : liste de CODES, ou vide pour « toutes / tous ». Le nom reprend celui des classes existantes avec la lettre suivante. "
            . "Le nombre de places vient de la personne : ne le suppose jamais. Rien n'est créé avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'places' => ['type' => 'integer', 'description' => 'Places par nouvelle classe.'],
                'filieres' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes de filières ; vide = toutes les filières BTS actives.'],
                'niveaux' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes de niveaux (BTS1, BTS2…) ; vide = tous les niveaux BTS actifs.'],
                'nombre' => ['type' => 'integer', 'description' => 'Classes à ajouter par couple (défaut 1).'],
            ],
            'required' => ['places'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Ajouter des classes';
        $places = (int) ($args['places'] ?? 0);
        $nombre = max(1, (int) ($args['nombre'] ?? 1));
        $manques = [];
        if ($places < 1) {
            $manques[] = 'Combien de places par nouvelle classe ?';
        }
        if (! ESBTPAnneeUniversitaire::where('is_current', true)->exists()) {
            $manques[] = "Aucune année universitaire courante : il faut d'abord en définir une.";
        }

        [$filieres, $inconnues] = $this->choisir(ESBTPFiliere::query()->horsMiroirLmd()->where('is_active', true)->get(), (array) ($args['filieres'] ?? []));
        [$niveaux, $inconnus] = $this->choisir(
            ESBTPNiveauEtude::query()->where('is_active', true)->get()->reject(fn ($n) => $n->estUnCycleLmd())->values(),
            (array) ($args['niveaux'] ?? [])
        );
        foreach (array_merge($inconnues, $inconnus) as $code) {
            $manques[] = "Code inconnu (ou LMD) : {$code}.";
        }
        if ($filieres->isEmpty() || $niveaux->isEmpty()) {
            $manques[] = 'Aucun couple filière × niveau BTS ne correspond.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($manques)));
        }

        $existantes = ESBTPClasse::query()->whereIn('filiere_id', $filieres->pluck('id'))->whereIn('niveau_etude_id', $niveaux->pluck('id'))
            ->get(['id', 'name', 'code', 'filiere_id', 'niveau_etude_id']);
        $codesPris = ESBTPClasse::withTrashed()->pluck('code')->map(fn ($c) => mb_strtoupper((string) $c))->flip();

        $nouvelles = [];
        $sansModele = [];
        foreach ($filieres as $f) {
            foreach ($niveaux as $n) {
                $soeurs = $existantes->where('filiere_id', $f->id)->where('niveau_etude_id', $n->id);
                foreach ($this->suivantes($f, $n, $soeurs, $nombre, $codesPris) as $c) {
                    $nouvelles[] = $c + ['filiere_id' => (int) $f->id, 'niveau_etude_id' => (int) $n->id, 'places' => $places,
                        'filiere' => (string) $f->name, 'niveau' => (string) $n->name, 'existantes' => $soeurs->pluck('name')->implode(', ') ?: '—'];
                }
                if ($soeurs->isEmpty()) {
                    $sansModele[] = $f->code.' / '.$n->code;
                }
            }
        }
        if (count($nouvelles) > self::MAX_CLASSES) {
            return new Proposition(titre: $titre, resume: '', manques: ['Plus de '.self::MAX_CLASSES.' classes : précise les filières ou les niveaux.']);
        }

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d classe(s) de %d places, sur %d filière(s) × %d niveau(x).', count($nouvelles), $places, $filieres->count(), $niveaux->count()),
            tableau: [
                'colonnes' => ['Filière', 'Niveau', 'Classes existantes', 'Nouvelle classe', 'Code', 'Places'],
                'lignes' => array_map(fn ($c) => [$c['filiere'], $c['niveau'], $c['existantes'], $c['name'], $c['code'], (string) $c['places']], $nouvelles),
            ],
            avertissements: $sansModele === [] ? [] : [
                'Sans classe existante, le nom part du code de la filière (à renommer si l\'école en utilise un autre) : '.implode(', ', $sansModele).'.',
            ],
            donnees: ['classes' => array_map(fn ($c) => array_intersect_key($c, array_flip(['name', 'code', 'filiere_id', 'niveau_etude_id', 'places'])), $nouvelles)],
            etat: ['existantes' => $existantes->pluck('id')->sort()->values()->all()],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $classes = $proposition->donnees['classes'];
        $anneeId = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        $crees = DB::transaction(function () use ($classes, $anneeId, $user) {
            $codes = array_column($classes, 'code');
            if (ESBTPClasse::withTrashed()->whereIn('code', $codes)->lockForUpdate()->exists()) {
                throw new PropositionPerimee('Une classe portant un de ces codes a été créée entre-temps : relance la proposition.');
            }
            $ids = [];
            foreach ($classes as $c) {
                $ids[] = ESBTPClasse::create([
                    'name' => $c['name'], 'code' => $c['code'],
                    'filiere_id' => $c['filiere_id'], 'niveau_etude_id' => $c['niveau_etude_id'],
                    'annee_universitaire_id' => $anneeId, 'places_totales' => $c['places'],
                    'systeme_academique' => 'BTS', 'is_active' => true,
                    'created_by' => $user->id, 'updated_by' => $user->id,
                ])->id;
            }

            return $ids;
        });

        return [
            'message' => count($crees).' classe(s) créée(s).',
            'lien' => route('esbtp.classes.index', [], false),
            'model_type' => ESBTPClasse::class,
            'model_id' => $crees[0] ?? null,
            'details' => ['ids' => $crees],
        ];
    }

    /** @return array{0: Collection, 1: string[]} */
    private function choisir(Collection $tous, array $codes): array
    {
        $codes = array_values(array_filter(array_map(fn ($c) => mb_strtoupper(trim((string) $c)), $codes)));
        if ($codes === []) {
            return [$tous, []];
        }
        $choisis = $tous->filter(fn ($x) => in_array(mb_strtoupper((string) $x->code), $codes, true))->values();

        return [$choisis, array_values(array_diff($codes, $choisis->map(fn ($x) => mb_strtoupper((string) $x->code))->all()))];
    }

    /** @return array<int, array{name: string, code: string}> */
    private function suivantes(ESBTPFiliere $f, ESBTPNiveauEtude $n, Collection $soeurs, int $nombre, Collection $codesPris): array
    {
        $annee = (int) ($n->year ?? 0) ?: 1;
        $lettres = [];
        $prefixe = null;
        $modele = null;
        foreach ($soeurs->sortBy('id') as $s) {
            if (preg_match('/^(.*?)\s*'.$annee.'([A-Z])?$/u', trim((string) $s->name), $m)) {
                $lettres[] = isset($m[2]) && $m[2] !== '' ? ord($m[2]) - 64 : 1;
                $prefixe = trim($m[1]) ?: $prefixe;
                $modele = ($m[2] ?? '') !== '' ? $s : ($modele ?? $s);
            }
        }
        $prefixe ??= mb_strtoupper((string) $f->code);
        $rang = $lettres === [] ? max(0, $soeurs->count()) : max($lettres);

        $sortie = [];
        for ($i = 1; $i <= $nombre; $i++) {
            $lettre = chr(64 + $rang + $i);
            $nom = "{$prefixe} {$annee}{$lettre}";
            // Le code suit celui de la soeur s'il finit par son suffixe (1BTS_GBAT_1C → 1BTS_GBAT_1D).
            $code = $modele && preg_match('/^(.*?)'.$annee.'[A-Z]$/', (string) $modele->code, $c)
                ? $c[1].$annee.$lettre
                : mb_strtoupper(preg_replace('/[^A-Za-z0-9]+/', '_', $nom));
            while ($codesPris->has(mb_strtoupper($code))) {
                $code .= '_N';
            }
            $codesPris->put(mb_strtoupper($code), true);
            $sortie[] = ['name' => $nom, 'code' => $code];
        }

        return $sortie;
    }
}
