<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\LectureDeColonnes;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Enums\TypeUE;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\LMD\ConflitDeMaquette;
use App\Services\LMD\LmdAcademicRuleProfile;
use App\Services\LMD\LMDImportService;
use App\Services\LMD\ReglesDeMaquette;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * Importer la maquette d'un parcours LMD (UE, ECUE, crédits, heures) depuis un
 * fichier joint : LMDImportService, le même import que `POST /api/cli/lmd/import`.
 *
 * Le modèle ne recopie AUCUNE valeur : il désigne la pièce et ses colonnes, le
 * serveur relit le fichier. Les codes et le type d'UE viennent du fichier ou de
 * la personne, jamais déduits. Avant de proposer :
 *  - les mêmes règles de validation que la CLI (ReglesDeMaquette) ;
 *  - chaque semestre totalise les crédits attendus par l'école (30 en UEMOA),
 *    sauf si la personne confirme une maquette partielle ;
 *  - l'import est REJOUÉ puis annulé (LMDImportService::simuler) : ce qu'il
 *    écrirait et ce qu'il refuserait (code repris par une autre unité, mention
 *    déplacée d'un domaine à l'autre) sont montrés sans rien laisser en base.
 */
class ImporterMaquetteLmd extends ActionAgent
{
    use LectureDeColonnes;

    private const COLONNES = ['ue_code', 'ue_intitule', 'ue_credit', 'ue_type', 'semestre', 'annee',
        'ecue_code', 'ecue_intitule', 'ecue_credit', 'cm', 'td', 'tp', 'projet', 'tpe'];

    public function __construct(
        private LMDImportService $import,
        private LmdAcademicRuleProfile $regles,
    ) {
    }

    public function cle(): string
    {
        return 'import_maquette_lmd';
    }

    public function libelle(): string
    {
        return 'Lecture de la maquette LMD…';
    }

    public function description(): string
    {
        return "PROPOSE d'importer la maquette d'UN parcours LMD (UE, ECUE, crédits, heures) depuis un fichier joint. "
            . "Passe la pièce et les noms EXACTS de ses colonnes ; ne recopie aucune valeur. Domaine, mention, parcours : noms ET codes donnés par l'école. "
            . "Si le fichier n'a pas de colonne semestre, année ou type d'UE, demande la valeur à la personne. "
            . "Les conflits et les semestres qui ne totalisent pas les crédits attendus reviennent en manques : relaie-les, ne corrige rien toi-même.";
    }

    public function parameters(): array
    {
        $niveau = ['type' => 'object', 'properties' => ['name' => ['type' => 'string'], 'code' => ['type' => 'string']]];
        $colonnes = [];
        foreach (self::COLONNES as $c) {
            $colonnes[$c] = ['type' => 'string'];
        }

        return [
            'type' => 'object',
            'properties' => [
                'domaine' => $niveau,
                'mention' => $niveau,
                'parcours' => $niveau,
                'filiere' => $niveau + ['description' => 'Filière du parcours (code exact).'],
                'piece' => [
                    'type' => 'object',
                    'properties' => [
                        'piece_id' => ['type' => 'string'],
                        'feuille' => ['type' => 'string'],
                        'colonnes' => ['type' => 'object', 'properties' => $colonnes,
                            'description' => 'Rôle → nom exact de la colonne. Obligatoires : ue_code, ue_intitule, ue_credit, ecue_code, ecue_intitule, ecue_credit. Une ligne sans UE prolonge l\'UE de la ligne précédente.'],
                    ],
                    'required' => ['piece_id', 'colonnes'],
                ],
                'semestre' => ['type' => 'integer', 'description' => 'Si le fichier n\'a pas de colonne semestre : le semestre (1 à 10) donné par la personne.'],
                'annee' => ['type' => 'integer', 'description' => 'Si le fichier n\'a pas de colonne année : l\'année d\'études (1 = L1, 4 = M1).'],
                'type_ue' => ['type' => 'string', 'description' => 'Si le fichier n\'a pas de colonne type : le type des UE, donné par la personne.'],
                'ues_propres' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes d\'UE que l\'école dit propres à ce parcours alors qu\'un autre parcours imprime le même code.'],
                'credits_incomplets_confirmes' => ['type' => 'boolean', 'description' => 'true seulement si la personne confirme qu\'un semestre partiel est voulu.'],
            ],
            'required' => ['domaine', 'mention', 'parcours', 'piece'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Import de maquette LMD';
        $p = is_array($args['piece'] ?? null) ? $args['piece'] : [];
        $roles = array_intersect_key((array) ($p['colonnes'] ?? []), array_flip(self::COLONNES));
        $manques = [];
        foreach (['ue_code', 'ue_intitule', 'ue_credit', 'ecue_code', 'ecue_intitule', 'ecue_credit'] as $obligatoire) {
            if (trim((string) ($roles[$obligatoire] ?? '')) === '') {
                $manques[] = "Quelle colonne donne « {$obligatoire} » ?";
            }
        }
        foreach (['domaine', 'mention', 'parcours', 'filiere'] as $niveau) {
            foreach (['name' => 'le nom', 'code' => 'le code'] as $champ => $quoi) {
                if (trim((string) ($args[$niveau][$champ] ?? '')) === '') {
                    $manques[] = "Quel est {$quoi} de : {$niveau} ? (donné par l'école, jamais déduit)";
                }
            }
        }
        if ($p === [] || $manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $p === [] ? ['Joins la maquette (un fichier tableau) et indique ses colonnes.'] : $manques);
        }

        [$lignes, $manques, $avertissements] = $this->lireColonnes($p, $user, $roles);
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }
        [$spec, $manques] = $this->spec($args, $lignes);
        if ($manques === []) {
            $manques = array_merge(
                Validator::make($spec, ReglesDeMaquette::import())->errors()->all(),
                $this->creditsDesSemestres($spec, (bool) ($args['credits_incomplets_confirmes'] ?? false)),
            );
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_slice($manques, 0, 15));
        }

        try {
            $simulation = $this->import->simuler($spec, (int) $user->id);
        } catch (\RuntimeException|\InvalidArgumentException $e) {
            return new Proposition(titre: $titre, resume: '', manques: [$e->getMessage()]);
        }
        if ($simulation['conflits'] !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_map(fn ($c) => "{$c['type']} {$c['code']} : {$c['detail']}", $simulation['conflits']));
        }
        $stats = $simulation['resultat']['stats'];

        return new Proposition(
            titre: "Maquette {$spec['parcours']['code']} : " . count($spec['ues']) . ' UE',
            resume: sprintf('%s → %s → %s. %d UE (%d nouvelle(s)), %d ECUE (%d nouveau(x)), %d planification(s).',
                $spec['domaine']['code'], $spec['mention']['code'], $spec['parcours']['code'],
                $stats['ues_attached'] + $stats['ues_updated'], $stats['ues_attached'],
                $stats['ecues_attached'] + $stats['ecues_updated'], $stats['ecues_attached'],
                $stats['planifs_attached'] + $stats['planifs_updated']),
            tableau: [
                'colonnes' => ['Sem.', 'UE', 'Intitulé', 'Type', 'Crédits', 'ECUE (crédits)'],
                'lignes' => array_map(fn ($ue) => [
                    'S' . $ue['semestre'], (string) $ue['code'], $ue['name'], TypeUE::from($ue['type_ue'])->label(), (string) $ue['credit'],
                    collect($ue['ecues'])->map(fn ($e) => $e['code'] . ' (' . $e['credit_ecue'] . ')')->implode(', '),
                ], $spec['ues']),
            ],
            avertissements: array_merge($avertissements, $this->ecartsUeEcue($spec), $this->avertissementsDeStructure($stats)),
            donnees: ['spec' => $spec],
            etat: ['stats' => $stats],
            risque: $stats['ues_updated'] + $stats['ecues_updated'] > 0 ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $spec = $proposition->donnees['spec'];
        try {
            $resultat = DB::transaction(function () use ($spec, $user, $proposition) {
                $resultat = $this->import->import($spec, (int) $user->id);
                if ($resultat['stats'] !== $proposition->etat['stats']) {
                    throw new PropositionPerimee('Les maquettes ont changé depuis la proposition.');
                }

                return $resultat;
            });
        } catch (ConflitDeMaquette $e) {
            throw new PropositionPerimee(implode(' ', array_column($e->conflits(), 'detail')));
        }
        $s = $resultat['stats'];

        return [
            'message' => sprintf('Maquette %s importée : %d UE, %d ECUE, %d planification(s).', $spec['parcours']['code'],
                $s['ues_attached'] + $s['ues_updated'], $s['ecues_attached'] + $s['ecues_updated'], $s['planifs_attached'] + $s['planifs_updated']),
            'lien' => route('esbtp.lmd.ue.index', ['parcours_id' => $resultat['parcours']['id'] ?? null], false),
            'model_type' => ESBTPLMDParcours::class,
            'model_id' => (int) ($resultat['parcours']['id'] ?? 0) ?: null,
            'details' => $s,
        ];
    }

    /**
     * Les lignes du fichier → la maquette que l'import attend. Une ligne sans
     * code ni intitulé d'UE prolonge l'UE précédente (cellules fusionnées) ;
     * une même UE reprise plus bas est la même UE.
     *
     * @return array{0: array, 1: string[]}
     */
    private function spec(array $args, array $lignes): array
    {
        $manques = [];
        $ues = [];
        $courante = null;
        $propres = array_map(fn ($c) => mb_strtoupper(trim((string) $c)), (array) ($args['ues_propres'] ?? []));
        $types = $this->types();

        foreach ($lignes as $n => $l) {
            $num = $n + 1;
            if (($l['ue_code'] ?? '') !== '' || ($l['ue_intitule'] ?? '') !== '') {
                if (($l['ue_code'] ?? '') === '') {
                    $manques[] = "Ligne {$num} : l'UE « {$l['ue_intitule']} » n'a pas de code.";
                    $courante = null;
                    continue;
                }
                $cle = mb_strtoupper($l['ue_code']);
                if (! isset($ues[$cle])) {
                    [$ue, $manquesUe] = $this->ue($l, $args, $types, $num);
                    if ($manquesUe !== []) {
                        $manques = array_merge($manques, $manquesUe);
                        $courante = null;
                        continue;
                    }
                    $ues[$cle] = $ue + ['propre_au_parcours' => in_array($cle, $propres, true), 'ecues' => []];
                }
                $courante = $cle;
            }
            if (($l['ecue_code'] ?? '') === '' && ($l['ecue_intitule'] ?? '') === '') {
                continue;
            }
            if ($courante === null) {
                $manques[] = "Ligne {$num} : l'élément « " . ($l['ecue_intitule'] ?? $l['ecue_code']) . ' » ne suit aucune UE.';
                continue;
            }
            [$ecue, $manquesEcue] = $this->ecue($l, $num);
            $manques = array_merge($manques, $manquesEcue);
            if ($ecue !== null) {
                $ues[$courante]['ecues'][] = $ecue;
            }
        }
        if ($ues === [] && $manques === []) {
            $manques[] = 'Aucune UE lue dans ce fichier avec ces colonnes.';
        }

        $annees = array_values(array_unique(array_column($ues, 'niveau_year')));
        $spec = [
            'domaine' => ['name' => trim($args['domaine']['name']), 'code' => trim($args['domaine']['code'])],
            'mention' => ['name' => trim($args['mention']['name']), 'code' => trim($args['mention']['code'])],
            'parcours' => ['name' => trim($args['parcours']['name']), 'code' => trim($args['parcours']['code'])],
            'filiere' => ['name' => trim($args['filiere']['name']), 'code' => trim($args['filiere']['code'])],
            'niveaux' => array_map(fn (int $a) => $this->niveau($a), $annees),
            'ues' => array_values($ues),
        ];

        return [$spec, $manques];
    }

    /** @return array{0: array, 1: string[]} */
    private function ue(array $l, array $args, array $types, int $num): array
    {
        $manques = [];
        $code = $l['ue_code'];
        $credit = $this->entier($l['ue_credit'] ?? '');
        $semestre = ($l['semestre'] ?? '') !== '' ? $this->entier((string) preg_replace('/^s(emestre)?\s*/i', '', $l['semestre'])) : (isset($args['semestre']) ? (int) $args['semestre'] : null);
        $annee = ($l['annee'] ?? '') !== '' ? $this->annee($l['annee']) : (isset($args['annee']) ? (int) $args['annee'] : null);
        $typeBrut = ($l['ue_type'] ?? '') !== '' ? $l['ue_type'] : (string) ($args['type_ue'] ?? '');
        $type = $types[$this->cleType($typeBrut)] ?? null;

        if ($credit === null) {
            $manques[] = "UE {$code} (ligne {$num}) : crédits « " . ($l['ue_credit'] ?? '') . ' » illisibles.';
        }
        if ($semestre === null || $semestre < 1 || $semestre > 10) {
            $manques[] = "UE {$code} : quel semestre (1 à 10) ? Le fichier ne le dit pas.";
        }
        if ($annee === null) {
            $manques[] = "UE {$code} : quelle année d'études (1 = L1, 4 = M1) ?";
        }
        if ($type === null) {
            $manques[] = $typeBrut === ''
                ? "UE {$code} : quel type d'UE (fondamentale, transversale, méthodologique…) ? Le fichier ne le dit pas."
                : "UE {$code} : type « {$typeBrut} » inconnu. Types possibles : " . implode(', ', TypeUE::values()) . '.';
        }
        if ($semestre !== null && $annee !== null && ! in_array($semestre, [2 * $annee - 1, 2 * $annee], true)) {
            $manques[] = "UE {$code} : le semestre {$semestre} n'appartient pas à l'année {$annee}.";
        }

        return [[
            'code' => $code, 'name' => $l['ue_intitule'] !== '' ? $l['ue_intitule'] : $code, 'type_ue' => $type,
            'credit' => $credit, 'niveau_year' => $annee, 'semestre' => $semestre,
        ], $manques];
    }

    /** @return array{0: ?array, 1: string[]} */
    private function ecue(array $l, int $num): array
    {
        $code = $l['ecue_code'] ?? '';
        $credit = $this->entier($l['ecue_credit'] ?? '');
        $manques = [];
        if ($code === '') {
            $manques[] = "Ligne {$num} : l'élément « {$l['ecue_intitule']} » n'a pas de code.";
        }
        if ($credit === null) {
            $manques[] = "ECUE {$code} (ligne {$num}) : crédits « " . ($l['ecue_credit'] ?? '') . ' » illisibles.';
        }
        $ecue = ['code' => $code, 'name' => ($l['ecue_intitule'] ?? '') !== '' ? $l['ecue_intitule'] : $code, 'credit_ecue' => $credit];
        foreach (['cm', 'td', 'tp', 'projet', 'tpe'] as $h) {
            if (($l[$h] ?? '') === '') {
                continue;
            }
            $heures = $this->entier($l[$h]);
            $heures === null ? $manques[] = "ECUE {$code} : {$h} « {$l[$h]} » illisible." : $ecue[$h] = $heures;
        }

        return [$manques === [] ? $ecue : null, $manques];
    }

    /** Chaque semestre totalise les crédits attendus par l'école, sauf maquette partielle confirmée. */
    private function creditsDesSemestres(array $spec, bool $confirme): array
    {
        if ($confirme) {
            return [];
        }
        $attendu = $this->regles->expectedCreditsPerSemester();
        $parSemestre = [];
        foreach ($spec['ues'] as $ue) {
            $parSemestre[$ue['semestre']] = ($parSemestre[$ue['semestre']] ?? 0) + (int) $ue['credit'];
        }
        ksort($parSemestre);
        $ecarts = array_filter($parSemestre, fn ($total) => $total !== $attendu);

        return $ecarts === [] ? [] : [
            'Crédits par semestre : ' . implode(', ', array_map(fn ($s, $t) => "S{$s} = {$t}", array_keys($ecarts), $ecarts))
            . " au lieu de {$attendu}. Vérifie la lecture du fichier avec la personne ; si la maquette est volontairement partielle, qu'elle le confirme.",
        ];
    }

    private function ecartsUeEcue(array $spec): array
    {
        $ecarts = [];
        foreach ($spec['ues'] as $ue) {
            $somme = array_sum(array_column($ue['ecues'], 'credit_ecue'));
            if ($somme !== (int) $ue['credit']) {
                $ecarts[] = "{$ue['code']} : {$ue['credit']} crédit(s), ses ECUE en totalisent {$somme}";
            }
        }

        return $ecarts === [] ? [] : ['UE dont les ECUE ne totalisent pas les crédits : ' . implode(' ; ', array_slice($ecarts, 0, 8)) . '.'];
    }

    private function avertissementsDeStructure(array $stats): array
    {
        return array_values(array_filter([
            $stats['ues_updated'] > 0 ? $stats['ues_updated'] . ' UE existent déjà sous ces codes : elles sont reprises (partagées) sans changer leur fiche.' : null,
            $stats['ecues_updated'] > 0 ? $stats['ecues_updated'] . ' ECUE existent déjà sous ces codes : leurs intitulés et heures sont mis à jour.' : null,
        ]));
    }

    private function niveau(int $annee): array
    {
        $cycle = ESBTPNiveauEtude::cycleLmdPourAnnee($annee) ?? 'Licence';
        $rang = array_search($annee, ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD[$cycle] ?? [$annee], true);

        return ['name' => $cycle . ' ' . ((int) $rang + 1), 'year' => $annee, 'type' => $cycle];
    }

    /** « L1 » → 1, « M1 » → 4, « 2 » → 2 ; sinon null. */
    private function annee(string $valeur): ?int
    {
        $v = mb_strtoupper(trim($valeur));
        if (preg_match('/^(L|LICENCE)\s*([1-3])$/', $v, $m)) {
            return (int) $m[2];
        }
        if (preg_match('/^(M|MASTER)\s*([1-2])$/', $v, $m)) {
            return 3 + (int) $m[2];
        }

        return $this->entier($valeur);
    }

    /** @return array<string, string> clé normalisée → valeur de TypeUE */
    private function types(): array
    {
        $types = [];
        foreach (TypeUE::cases() as $t) {
            $types[$this->cleType($t->value)] = $t->value;
            $types[$this->cleType($t->label())] = $t->value;
        }

        return $types;
    }

    private function cleType(string $libelle): string
    {
        $cle = Str::of(Str::ascii($libelle))->lower()->replaceMatches('/[^a-z ]/', ' ')->squish()->toString();

        return (string) preg_replace('/^(ue )?(de |d )?/', '', $cle);
    }
}
