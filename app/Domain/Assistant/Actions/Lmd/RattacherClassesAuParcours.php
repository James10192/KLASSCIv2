<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Designations;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPClasse;
use App\Models\ESBTPLMDParcours;
use App\Services\LMD\LMDClassLinkService;

/**
 * Rattacher des classes LMD à un parcours : LMDClassLinkService, le même chemin
 * que `POST /api/cli/lmd/link-classes` (sa simulation pour la proposition, son
 * application à la validation). Une classe dont le niveau n'est pas LMD est
 * laissée telle quelle, et c'est dit.
 */
class RattacherClassesAuParcours extends ActionAgent
{
    use Designations;

    private const MAX = 60;

    public function __construct(private LMDClassLinkService $liaison)
    {
    }

    public function cle(): string
    {
        return 'rattachement_classes_parcours';
    }

    public function libelle(): string
    {
        return 'Préparation du rattachement des classes…';
    }

    public function description(): string
    {
        return "PROPOSE de rattacher des classes LMD à un parcours (le domaine et la mention en découlent). "
            . "Parcours par son CODE, classes par code ou identifiant (search_classes). Une classe de niveau non LMD est ignorée.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'parcours' => ['type' => 'string', 'description' => 'Code du parcours.'],
                'classes' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Codes ou identifiants des classes.'],
            ],
            'required' => ['parcours', 'classes'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Rattachement de classes';
        $code = trim((string) ($args['parcours'] ?? ''));
        $parcours = $code === '' ? null : ESBTPLMDParcours::where('code', $code)->first();
        $manques = [];
        if (! $parcours) {
            $manques[] = $code === '' ? 'À quel parcours (code) ?' : "Parcours inconnu : {$code}.";
        }
        $designations = array_values(array_filter((array) ($args['classes'] ?? []), fn ($v) => trim((string) $v) !== ''));
        if ($designations === [] || count($designations) > self::MAX) {
            $manques[] = $designations === [] ? 'Quelles classes ?' : 'Trop de classes en une fois (' . self::MAX . ' au plus).';
        }
        $ids = [];
        foreach ($designations as $d) {
            [$classe, $manque] = $this->designerClasse($d);
            $classe ? $ids[(int) $classe->id] = (int) $classe->id : $manques[] = $manque;
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $simulation = $this->liaison->link($parcours->code, array_values($ids), true);
        $changees = array_values(array_filter($simulation['linked'], fn ($l) => $l['before'] !== $l['after']));
        if ($changees === []) {
            return new Proposition(titre: $titre, resume: '', manques: array_merge(
                ['Rien à rattacher : ces classes sont déjà sur ce parcours, ou ne sont pas de niveau LMD.'],
                array_map(fn ($s) => "{$s['name']} : {$s['reason']}.", $simulation['skipped'])
            ));
        }
        $codesParcours = ESBTPLMDParcours::pluck('code', 'id');

        return new Proposition(
            titre: "Rattacher " . count($changees) . " classe(s) au parcours {$parcours->code}",
            resume: count($changees) . " classe(s) passent sur le parcours {$parcours->name} ({$parcours->code}), en système LMD.",
            tableau: [
                'colonnes' => ['Classe', 'Parcours actuel', 'Système actuel', 'Après'],
                'lignes' => array_map(fn ($l) => [
                    (string) $l['name'],
                    $l['before']['parcours_id'] ? (string) ($codesParcours[$l['before']['parcours_id']] ?? '#' . $l['before']['parcours_id']) : '—',
                    (string) ($l['before']['systeme'] ?? '—'),
                    $parcours->code . ' (LMD)',
                ], $changees),
            ],
            avertissements: array_values(array_filter(array_merge(
                array_map(fn ($s) => "{$s['name']} ignorée : {$s['reason']}.", $simulation['skipped']),
                [$simulation['reflet_a_creer'] ? "Le parcours n'a pas encore de filière : une filière à son nom sera créée pour y ancrer les classes." : null],
            ))),
            donnees: ['parcours' => $parcours->code, 'classes' => array_column($changees, 'id')],
            etat: ['changees' => $changees],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $simulation = $this->liaison->link($d['parcours'], $d['classes'], true);
        if ($simulation['parcours'] === null
            || array_values(array_filter($simulation['linked'], fn ($l) => $l['before'] !== $l['after'])) !== $proposition->etat['changees']) {
            throw new PropositionPerimee('Ces classes ont changé depuis la proposition.');
        }
        $r = $this->liaison->link($d['parcours'], $d['classes'], false);

        return [
            'message' => $r['totals']['linked'] . " classe(s) rattachée(s) au parcours {$d['parcours']}.",
            'lien' => route('esbtp.classes.index', [], false),
            'model_type' => ESBTPClasse::class,
            'model_id' => (int) ($d['classes'][0] ?? 0) ?: null,
        ];
    }
}
