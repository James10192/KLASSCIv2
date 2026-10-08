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
 * Propose de configurer les frais : montants, audience et échéance d'une ou
 * plusieurs catégories pour des portées (filière BTS ou parcours LMD, niveau).
 *
 * L'écriture est celle de la CLI (PoseDeBareme), qui passe par le même
 * FraisConfigurationWriter que l'écran de configuration des frais. Nanan ne
 * reçoit que des codes ou noms exacts qu'elle résout ici ; elle ne suppose
 * jamais une valeur absente.
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
        return 'Préparation de la configuration des frais…';
    }

    public function description(): string
    {
        return 'PROPOSE de configurer les frais par portée. `configurations` : une ligne par frais et par portée {categorie, filiere (BTS) OU parcours (LMD), niveau : codes ou noms exacts ; montant facultatif si la configuration existe déjà ; montant_affecte / montant_reaffecte / montant_non_affecte facultatifs ; audience facultative = tous|nouveaux_etablissement|anciens_etablissement ; echeance_jours facultative = 1..365}. '
            . 'L’audience placée dans `configurations` ne concerne QUE cette combinaison filière/parcours + niveau. Pour changer uniquement l’audience ou l’échéance d’une configuration existante, NE redemande PAS son montant : laisse `montant` absent, sa valeur actuelle sera conservée. `categories` sert seulement à CRÉER un frais nouveau ou modifier ses informations de catalogue {code, name, is_mandatory, default_amount}. '
            . 'Les montants viennent de la personne : n’en suppose jamais. Si elle dit « uniquement les nouveaux » ou « seulement les anciens », reporte exactement cette audience sur la ou les portées demandées. `confirmer_statut` change un réglage d’établissement : seulement si la personne le demande. Rien n’est écrit avant « Valider ».';
    }

    public function parameters(): array
    {
        $ligne = [
            'type' => 'object',
            'properties' => [
                'categorie' => ['type' => 'string', 'description' => 'Code ou nom exact du frais.'],
                'filiere' => ['type' => 'string', 'description' => 'Code ou nom exact de filière (BTS).'],
                'parcours' => ['type' => 'string', 'description' => 'Code ou nom exact de parcours (LMD).'],
                'niveau' => ['type' => 'string', 'description' => 'Code ou nom exact du niveau.'],
                'montant' => ['type' => 'number', 'description' => 'À fournir pour créer/changer le prix ; facultatif pour une modification audience/échéance sur une configuration existante.'],
                'montant_affecte' => ['type' => 'number'],
                'montant_reaffecte' => ['type' => 'number'],
                'montant_non_affecte' => ['type' => 'number'],
                'audience' => [
                    'type' => 'string',
                    'enum' => ['tous', 'nouveaux_etablissement', 'anciens_etablissement'],
                    'description' => 'Audience de CETTE combinaison uniquement.',
                ],
                'echeance_jours' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'maximum' => 365,
                    'description' => 'Échéance en jours après inscription pour CETTE combinaison.',
                ],
            ],
            'required' => ['categorie', 'niveau'],
        ];

        return [
            'type' => 'object',
            'properties' => [
                'configurations' => ['type' => 'array', 'items' => $ligne],
                'categories' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => [
                    'code' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'is_mandatory' => ['type' => 'boolean'],
                    'default_amount' => ['type' => 'number'],
                ], 'required' => ['code', 'name']]],
                'confirmer_statut' => ['type' => 'boolean', 'description' => 'Demander à l’agent de confirmer si l’élève est nouveau ou ancien.'],
            ],
            'required' => ['configurations'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Configurer les frais';
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
            'Les élèves déjà inscrits gardent le montant de leur souscription : cette configuration vaut pour les prochaines inscriptions.',
            $bareme['confirmer_statut'] ? 'Réglage de l’établissement : l’agent devra confirmer si chaque élève est nouveau ou ancien.' : null,
        ]));

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d frais configuré(s) sur %d portée(s).', count($lignes), count(array_unique(array_column($lignes, 1)))),
            tableau: [
                'colonnes' => ['Frais', 'Portée', 'Montant actuel', 'Nouveau montant', 'Audience', 'Échéance'],
                'lignes' => $lignes,
            ],
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
                throw new PropositionPerimee('La configuration des frais a changé depuis la proposition.');
            }

            return $this->pose->appliquer($bareme, (int) $user->id);
        });

        return [
            'message' => sprintf('Frais configurés : %d configuration(s) créée(s), %d mise(s) à jour.', $resultat['configurations_creees'], $resultat['configurations_maj']),
            'lien' => route('esbtp.frais.configure', [], false),
            'model_type' => ESBTPFraisCategory::class,
            'model_id' => null,
            'details' => $resultat,
        ];
    }

    /**
     * Traduit la demande de Nanan (codes ou noms exacts) dans la forme de
     * PoseDeBareme (identifiants).
     *
     * @return array{0: array, 1: array<int, string[]>, 2: string[]}
     */
    private function bareme(array $args, $user): array
    {
        $manques = [];
        $configurations = array_values((array) ($args['configurations'] ?? []));
        if ($configurations === []) {
            $manques[] = 'Quels frais, pour quelles filières ou parcours et quels niveaux, et que faut-il modifier ?';
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
                'code' => $code,
                'name' => trim((string) $c['name']),
                'is_mandatory' => isset($c['is_mandatory']) ? (bool) $c['is_mandatory'] : null,
                'default_amount' => isset($c['default_amount']) ? (float) $c['default_amount'] : null,
            ], fn ($v) => $v !== null);
        }

        $sortie = [];
        $lignes = [];
        foreach ($configurations as $l) {
            $code = strtoupper(trim((string) ($l['categorie'] ?? '')));
            $categoryModel = null;
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
                $categoryModel = $existante;
                $code = strtoupper((string) $existante->code);
                $categories[$code] ??= ['code' => $code, 'name' => (string) $existante->name];
            } else {
                $categoryModel = ESBTPFraisCategory::where('code', $code)->first();
            }

            [$portee, $libelle, $manque] = $this->portee($l);
            if ($manque) {
                $manques[] = $manque;
                continue;
            }

            $categoryId = $categoryModel?->id ?? ESBTPFraisCategory::where('code', $code)->value('id');
            $avant = $categoryId ? PoseDeBareme::etatActuel((int) $categoryId, $portee) : null;
            $hasMontant = array_key_exists('montant', $l);
            $hasAudience = array_key_exists('audience', $l);
            $hasDeadline = array_key_exists('echeance_jours', $l);
            $hasStatusAmount = collect(['affecte', 'reaffecte', 'non_affecte'])
                ->contains(fn (string $statut) => array_key_exists('montant_'.$statut, $l));

            if (! $hasMontant && ! $hasAudience && ! $hasDeadline && ! $hasStatusAmount) {
                $manques[] = "Que faut-il modifier pour {$code} : montant, audience ou échéance ?";
                continue;
            }

            if ($hasMontant && (! is_numeric($l['montant']) || (float) $l['montant'] < 0)) {
                $manques[] = "Quel montant valide pour {$code} ?";
                continue;
            }
            if (! $hasMontant && $avant === null) {
                $manques[] = "Quel montant pour {$code} ? Cette combinaison n'est pas encore configurée.";
                continue;
            }

            $montant = $hasMontant ? (float) $l['montant'] : (float) $avant['amount'];
            $ligne = $portee + ['category_code' => $code, 'amount' => $montant];

            foreach (['affecte', 'reaffecte', 'non_affecte'] as $statut) {
                $cle = 'montant_'.$statut;
                if (array_key_exists($cle, $l)) {
                    if (! is_numeric($l[$cle]) || (float) $l[$cle] < 0) {
                        $manques[] = "Montant {$statut} invalide pour {$code}.";
                        continue 2;
                    }
                    $ligne['amount_'.$statut] = (float) $l[$cle];
                } elseif (! $hasMontant && $avant !== null) {
                    // Une demande « seulement les nouveaux » ne doit surtout pas
                    // réécrire les trois montants de la combinaison.
                    $ligne['amount_'.$statut] = $avant['amount_'.$statut] ?? $avant['amount'];
                }
            }

            if ($hasAudience) {
                $audience = (string) $l['audience'];
                if (! in_array($audience, [
                    ESBTPFraisCategory::AUDIENCE_TOUS,
                    ESBTPFraisCategory::AUDIENCE_NOUVEAUX,
                    ESBTPFraisCategory::AUDIENCE_ANCIENS,
                ], true)) {
                    $manques[] = "Audience invalide pour {$code} : utilisez tous, nouveaux_etablissement ou anciens_etablissement.";
                    continue;
                }
                $ligne['audience'] = $audience;
            }

            if ($hasDeadline) {
                $echeance = filter_var($l['echeance_jours'], FILTER_VALIDATE_INT);
                if ($echeance === false || $echeance < 1 || $echeance > 365) {
                    $manques[] = "Quelle échéance valide pour {$code} ? Donnez un nombre de jours entre 1 et 365.";
                    continue;
                }
                $ligne['deadline_days'] = $echeance;
            } elseif ($avant !== null) {
                $ligne['deadline_days'] = $avant['deadline_days'];
            }

            $sortie[] = $ligne;

            $fcfa = fn ($v) => number_format((float) $v, 0, ',', ' ').' FCFA';
            $audience = $ligne['audience'] ?? ($avant['audience'] ?? ESBTPFraisCategory::AUDIENCE_TOUS);
            $audienceLabel = match ($audience) {
                ESBTPFraisCategory::AUDIENCE_NOUVEAUX => 'Nouveaux uniquement',
                ESBTPFraisCategory::AUDIENCE_ANCIENS => 'Anciens uniquement',
                default => 'Tous',
            };
            $echeance = $ligne['deadline_days'] ?? 30;

            $lignes[] = [
                $categories[$code]['name'],
                $libelle,
                $avant === null ? '—' : $fcfa($avant['amount']),
                $fcfa($ligne['amount']),
                $audienceLabel,
                $echeance.' j',
            ];
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
                $parcours->name.' · '.$niveau->name,
                null,
            ];
        }
        if (! empty($l['filiere'])) {
            [$filiere, $manque] = self::designer(ESBTPFiliere::class, (string) $l['filiere'], 'Filière');

            return $manque ? [[], '', $manque] : [
                ['systeme' => 'BTS', 'parcours_id' => null, 'filiere_id' => (int) $filiere->id, 'niveau_id' => (int) $niveau->id],
                $filiere->name.' · '.$niveau->name,
                null,
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
        $configurations = array_map(
            fn ($l) => isset($parCode[$l['category_code']])
                ? PoseDeBareme::etatActuel((int) $parCode[$l['category_code']], PoseDeBareme::portee($l))
                : null,
            $bareme['configurations']
        );

        return [
            'categories' => $categories,
            'configurations' => $configurations,
            'confirmer_statut' => (string) \App\Models\Setting::where('key', TenantScolariteSettings::CONFIRMER_STATUT_ETABLISSEMENT)->value('value'),
        ];
    }
}
