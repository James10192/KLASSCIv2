<?php

namespace App\Domain\Assistant\Actions\Frais;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPFraisCategory;
use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPNiveauEtude;
use App\Services\Frais\PoseDeBareme;
use App\Services\TenantScolariteSettings;
use Illuminate\Support\Facades\DB;

/**
 * Propose de poser un barème : les montants d'une ou plusieurs catégories de
 * frais pour des portées (filière BTS ou parcours LMD, et niveau), en créant
 * au besoin une catégorie nouvelle par son code.
 *
 * L'écriture est celle de la CLI (PoseDeBareme), qui passe par le même
 * FraisConfigurationWriter que l'écran de configuration des frais. Nanan ne
 * reçoit que des CODES (filière, parcours, niveau, catégorie) qu'elle résout
 * ici ; aucun montant n'est jamais supposé.
 */
class PoserBareme extends ActionAgent
{
    private const MAX_LIGNES = 120;

    public function __construct(private PoseDeBareme $pose)
    {
    }

    public function cle(): string
    {
        return 'pose_bareme';
    }

    public function libelle(): string
    {
        return 'Préparation du barème des frais…';
    }

    public function description(): string
    {
        return 'PROPOSE de poser des montants de frais. `configurations` : une ligne par frais et par portée {categorie, filiere (BTS) OU parcours (LMD), niveau : codes ou noms exacts ; montant, '
            . 'montant_affecte / montant_reaffecte / montant_non_affecte facultatifs}. `categories` : seulement pour CRÉER un frais nouveau ou changer son nom {code, name, is_mandatory, audience, default_amount}. '
            . 'Les montants viennent de la personne : n’en suppose jamais. `confirmer_statut` change un réglage d’établissement : seulement si la personne le demande. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        $ligne = [
            'type' => 'object',
            'properties' => [
                'categorie' => ['type' => 'string', 'description' => 'Code du frais.'],
                'filiere' => ['type' => 'string', 'description' => 'Code de filière (BTS).'],
                'parcours' => ['type' => 'string', 'description' => 'Code de parcours (LMD).'],
                'niveau' => ['type' => 'string', 'description' => 'Code de niveau.'],
                'montant' => ['type' => 'number'],
                'montant_affecte' => ['type' => 'number'],
                'montant_reaffecte' => ['type' => 'number'],
                'montant_non_affecte' => ['type' => 'number'],
            ],
            'required' => ['categorie', 'niveau'],
        ];

        return [
            'type' => 'object',
            'properties' => [
                'configurations' => ['type' => 'array', 'items' => $ligne],
                'categories' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'code' => ['type' => 'string'], 'name' => ['type' => 'string'], 'is_mandatory' => ['type' => 'boolean'],
                    'audience' => ['type' => 'string', 'enum' => ['tous', 'nouveaux_etablissement', 'anciens_etablissement']],
                    'default_amount' => ['type' => 'number'],
                ], 'required' => ['code', 'name']]],
                'confirmer_statut' => ['type' => 'boolean', 'description' => 'Demander à l’agent de confirmer si l’élève est nouveau ou ancien.'],
            ],
            'required' => ['configurations'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Poser un barème de frais';
        [$bareme, $lignes, $manques] = $this->bareme($args, $user);
        if ($manques === [] && ($refus = $this->pose->refus($bareme))) {
            $manques[] = $refus;
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_values(array_unique($manques)));
        }

        $nouvelles = array_values(array_filter(array_map(fn ($c) => ESBTPFraisCategory::where('code', strtoupper($c['code']))->exists() ? null : $c['name'], $bareme['categories'])));
        $inactives = ESBTPFraisCategory::whereIn('code', array_map(fn ($c) => $c['code'], $bareme['categories']))->where('is_active', false)->pluck('name')->all();
        $remplaces = count(array_filter($lignes, fn ($l) => $l[2] !== '—' && $l[2] !== $l[3]));
        $avertissements = array_values(array_filter([
            $nouvelles ? 'Frais créé(s) : '.implode(', ', $nouvelles).'.' : null,
            $inactives ? 'Frais désactivé(s) qui redeviendront actifs : '.implode(', ', $inactives).'.' : null,
            $remplaces ? $remplaces.' montant(s) existant(s) remplacé(s).' : null,
            'Les élèves déjà inscrits gardent le montant de leur souscription : le barème vaut pour les prochaines.',
            $bareme['confirmer_statut'] ? 'Réglage de l’établissement : l’agent devra confirmer si chaque élève est nouveau ou ancien.' : null,
        ]));

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d montant(s) de frais posé(s) sur %d portée(s).', count($lignes), count(array_unique(array_column($lignes, 1)))),
            tableau: ['colonnes' => ['Frais', 'Portée', 'Montant actuel', 'Nouveau montant'], 'lignes' => $lignes],
            avertissements: $avertissements,
            donnees: ['bareme' => $bareme],
            etat: $this->etat($bareme),
            risque: 'eleve',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        foreach (['frais.configure', 'frais.create', 'frais.edit'] as $droit) {
            if (! $user->can($droit)) {
                throw new PropositionPerimee('Vous n’avez plus le droit de configurer les frais.');
            }
        }
        $bareme = $proposition->donnees['bareme'];
        if ($bareme['confirmer_statut'] && ! $user->can('system.manage')) {
            throw new PropositionPerimee('Vous n’avez plus le droit de changer les réglages de l’établissement.');
        }

        $resultat = DB::transaction(function () use ($bareme, $proposition, $user) {
            ESBTPFraisCategory::whereIn('code', array_map(fn ($c) => strtoupper($c['code']), $bareme['categories']))->lockForUpdate()->get(['id']);
            if ($this->etat($bareme) !== $proposition->etat) {
                throw new PropositionPerimee('Le barème a changé depuis la proposition.');
            }

            return $this->pose->appliquer($bareme, (int) $user->id);
        });

        return [
            'message' => sprintf('Barème posé : %d montant(s) créé(s), %d mis à jour.', $resultat['configurations_creees'], $resultat['configurations_maj']),
            'lien' => route('esbtp.frais.index', [], false),
            'model_type' => ESBTPFraisCategory::class,
            'model_id' => null,
            'details' => $resultat,
        ];
    }

    /**
     * Traduit la demande de Nanan (codes) dans la forme de PoseDeBareme (identifiants).
     *
     * @return array{0: array, 1: array<int, string[]>, 2: string[]}
     */
    private function bareme(array $args, $user): array
    {
        $manques = [];
        $configurations = array_values((array) ($args['configurations'] ?? []));
        if ($configurations === []) {
            $manques[] = 'Quels frais, pour quelles filières ou parcours et quels niveaux, et à quels montants ?';
        }
        if (count($configurations) > self::MAX_LIGNES) {
            $manques[] = 'Plus de '.self::MAX_LIGNES.' lignes : procède par filière.';
        }

        $categories = [];
        foreach ((array) ($args['categories'] ?? []) as $c) {
            $code = strtoupper(trim((string) ($c['code'] ?? '')));
            if ($code === '' || trim((string) ($c['name'] ?? '')) === '') {
                $manques[] = 'Chaque frais à créer a besoin d’un code et d’un nom.';
                continue;
            }
            $categories[$code] = array_filter([
                'code' => $code, 'name' => trim((string) $c['name']),
                'is_mandatory' => isset($c['is_mandatory']) ? (bool) $c['is_mandatory'] : null,
                'audience' => $c['audience'] ?? null,
                'default_amount' => isset($c['default_amount']) ? (float) $c['default_amount'] : null,
            ], fn ($v) => $v !== null);
        }

        $sortie = [];
        $lignes = [];
        foreach ($configurations as $l) {
            $code = strtoupper(trim((string) ($l['categorie'] ?? '')));
            if (! isset($categories[$code])) {
                [$existante, $manque] = self::designer(ESBTPFraisCategory::class, (string) ($l['categorie'] ?? ''), 'Frais');
                if ($manque) {
                    $manques[] = $manque.' Pour en créer un nouveau, donne son code et son nom dans `categories`.';
                    continue;
                }
                if (trim((string) $existante->code) === '') {
                    $manques[] = "Le frais « {$existante->name} » n'a pas de code : il se configure depuis l'écran des frais.";
                    continue;
                }
                // Cité sans être redéfini : il est passé avec son nom actuel, donc
                // PoseDeBareme le réécrit à l'identique — mais le RÉACTIVE s'il était
                // désactivé. Ce cas est annoncé dans les avertissements.
                $code = strtoupper((string) $existante->code);
                $categories[$code] ??= ['code' => $code, 'name' => (string) $existante->name];
            }
            if (! isset($l['montant']) || ! is_numeric($l['montant']) || (float) $l['montant'] < 0) {
                $manques[] = "Quel montant pour {$code} ?";
                continue;
            }
            [$portee, $libelle, $manque] = $this->portee($l);
            if ($manque) {
                $manques[] = $manque;
                continue;
            }
            $ligne = $portee + ['category_code' => $code, 'amount' => (float) $l['montant']];
            foreach (['affecte', 'reaffecte', 'non_affecte'] as $statut) {
                if (isset($l['montant_'.$statut]) && is_numeric($l['montant_'.$statut])) {
                    $ligne['amount_'.$statut] = (float) $l['montant_'.$statut];
                }
            }
            $sortie[] = $ligne;

            $actuelle = ESBTPFraisCategory::where('code', $code)->value('id');
            $avant = $actuelle ? PoseDeBareme::montantActuel((int) $actuelle, PoseDeBareme::portee($ligne)) : null;
            $fcfa = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
            $lignes[] = [$categories[$code]['name'], $libelle, $avant === null ? '—' : $fcfa($avant), $fcfa($ligne['amount'])];
        }

        $confirmer = (bool) ($args['confirmer_statut'] ?? false);
        if ($confirmer && ! $user->can('system.manage')) {
            $manques[] = 'Demander si l’élève est nouveau ou ancien est un réglage de l’établissement : il se change dans Paramètres, par qui en a le droit.';
        }

        return [['categories' => array_values($categories), 'configurations' => $sortie, 'confirmer_statut' => $confirmer], $lignes, $manques];
    }

    /** @return array{0: array, 1: string, 2: ?string} */
    private function portee(array $l): array
    {
        [$niveau, $manque] = self::designer(ESBTPNiveauEtude::class, (string) ($l['niveau'] ?? ''), 'Niveau');
        if ($manque) {
            return [[], '', $manque];
        }

        if (! empty($l['parcours'])) {
            [$parcours, $manque] = self::designer(ESBTPLMDParcours::class, (string) $l['parcours'], 'Parcours');

            return $manque ? [[], '', $manque] : [
                ['systeme' => 'LMD', 'parcours_id' => (int) $parcours->id, 'filiere_id' => null, 'niveau_id' => (int) $niveau->id],
                $parcours->name.' · '.$niveau->name, null,
            ];
        }
        if (! empty($l['filiere'])) {
            [$filiere, $manque] = self::designer(ESBTPFiliere::class, (string) $l['filiere'], 'Filière');

            return $manque ? [[], '', $manque] : [
                ['systeme' => 'BTS', 'parcours_id' => null, 'filiere_id' => (int) $filiere->id, 'niveau_id' => (int) $niveau->id],
                $filiere->name.' · '.$niveau->name, null,
            ];
        }

        return [[], '', 'Pour quelle filière (BTS) ou quel parcours (LMD) ?'];
    }

    /**
     * Un enregistrement désigné par son code, ou à défaut par son nom exact.
     * Introuvable : la question liste ce qui existe, pour que Nanan la pose juste.
     *
     * @return array{0: ?\Illuminate\Database\Eloquent\Model, 1: ?string}
     */
    private static function designer(string $modele, string $valeur, string $quoi): array
    {
        $valeur = mb_strtoupper(trim($valeur));
        if ($valeur === '') {
            return [null, "{$quoi} manquant : lequel ?"];
        }
        $trouves = $modele::whereRaw('UPPER(code) = ?', [$valeur])->get();
        if ($trouves->isEmpty()) {
            $trouves = $modele::whereRaw('UPPER(name) = ?', [$valeur])->get();
        }
        if ($trouves->count() === 1) {
            return [$trouves->first(), null];
        }
        if ($trouves->count() > 1) {
            return [null, "{$quoi} « {$valeur} » ambigu : plusieurs portent ce code ou ce nom."];
        }
        $connus = $modele::orderBy('name')->limit(25)->get(['code', 'name'])
            ->map(fn ($m) => trim(($m->code ? $m->code.' ' : '').'('.$m->name.')'))->implode(', ');

        return [null, "{$quoi} « {$valeur} » introuvable. Existants : {$connus}."];
    }

    private function etat(array $bareme): array
    {
        $codes = array_map(fn ($c) => strtoupper($c['code']), $bareme['categories']);
        $categories = ESBTPFraisCategory::whereIn('code', $codes)->orderBy('id')
            ->get(['id', 'code', 'name', 'is_mandatory', 'audience', 'category_type', 'default_amount', 'is_active'])
            ->map(fn ($c) => [(int) $c->id, (string) $c->code, (string) $c->name, (bool) $c->is_mandatory, (string) $c->audience, (float) $c->default_amount, (bool) $c->is_active])->all();
        $parCode = ESBTPFraisCategory::whereIn('code', $codes)->pluck('id', 'code');
        $montants = array_map(fn ($l) => isset($parCode[$l['category_code']])
            ? PoseDeBareme::montantActuel((int) $parCode[$l['category_code']], PoseDeBareme::portee($l)) : null, $bareme['configurations']);

        return [
            'categories' => $categories,
            'montants' => $montants,
            'confirmer_statut' => (string) \App\Models\Setting::where('key', TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT)->value('value'),
        ];
    }
}
