<?php

namespace App\Domain\Assistant\Actions\Academique;

use App\Domain\Academique\ReferentielAcademique;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPFiliere;
use Illuminate\Support\Facades\DB;

/**
 * Propose de créer des filières, ou de corriger celles qui existent (le code
 * fait foi). Écrit par ReferentielAcademique, le chemin de la CLI.
 *
 * Création : droit de l'écran de création (filieres.create). Corriger une
 * filière existante demande en plus celui de l'écran de modification
 * (filieres.edit) : un lot mixte ne passe pas avec un seul des deux.
 */
class EnregistrerFilieres extends ActionAgent
{
    private const MAX = 200;

    public function cle(): string
    {
        return 'filieres';
    }

    public function description(): string
    {
        return "PROPOSE de créer des filières, ou de corriger le nom/description/activation d'une filière existante (même code). `filieres` = [{code, nom, description?, active?}], codes et noms tels que donnés. "
            . "Ne change ni le tronc commun (proposer_tronc_commun_filiere) ni les reflets LMD. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filieres' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'code' => ['type' => 'string'],
                            'nom' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'active' => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ],
            'required' => ['filieres'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Filières';
        $lot = [];
        $manques = [];
        foreach (array_values(array_filter((array) ($args['filieres'] ?? []), 'is_array')) as $i => $f) {
            $code = trim((string) ($f['code'] ?? ''));
            $nom = trim((string) ($f['nom'] ?? ''));
            if ($code === '' || $nom === '') {
                $manques[] = 'Ligne '.($i + 1).' : il faut le code ET le nom de la filière.';
                continue;
            }
            if (mb_strlen($code) > 50 || mb_strlen($nom) > 255) {
                $manques[] = "Ligne ".($i + 1).' : code (50) ou nom (255) trop long.';
                continue;
            }
            $lot[] = ['code' => $code, 'name' => $nom, 'description' => isset($f['description']) ? trim((string) $f['description']) : null,
                'is_active' => array_key_exists('active', $f) ? (bool) $f['active'] : null];
        }
        if ($lot === [] && $manques === []) {
            $manques[] = 'Quelles filières ? Donne au moins un code et un nom.';
        }
        if (count($lot) > self::MAX) {
            $manques[] = 'Plus de '.self::MAX.' filières : découpe le lot.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $plan = app(ReferentielAcademique::class)->planFilieres($lot);
        $manques = $plan['refus'];
        $misesAJour = array_filter($plan['lignes'], fn ($l) => $l['action'] === 'mise a jour');
        if ($misesAJour !== [] && ! $user->can('filieres.edit')) {
            $manques[] = 'Ces codes existent déjà et vous n\'avez pas le droit de modifier une filière : '.implode(', ', array_column($misesAJour, 'code')).'.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $existantes = $this->etat($plan['lignes']);
        $lignes = [];
        $avertissements = [];
        foreach ($plan['lignes'] as $l) {
            $avant = $existantes[$l['code']] ?? null;
            $active = $l['is_active'] ?? ($avant['active'] ?? true);
            if ($avant && $avant['active'] && ! $active) {
                $avertissements[] = "{$l['code']} sera désactivée : elle ne sera plus proposée, ses classes restent.";
            }
            $lignes[] = [
                $l['code'],
                $avant ? ($avant['name'] === $l['name'] ? $l['name'] : $avant['name'].' → '.$l['name']) : $l['name'],
                $l['action'] === 'creation' ? 'Création' : 'Mise à jour',
                $active ? 'Active' : 'Inactive',
            ];
        }
        $crees = count($plan['lignes']) - count($misesAJour);

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d filière(s) créée(s), %d mise(s) à jour.', $crees, count($misesAJour)),
            tableau: ['colonnes' => ['Code', 'Nom', 'Action', 'Statut'], 'lignes' => $lignes],
            avertissements: $avertissements,
            donnees: ['lignes' => $plan['lignes']],
            etat: ['existantes' => $existantes],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $lignes = $proposition->donnees['lignes'];

        $compte = DB::transaction(function () use ($lignes, $proposition) {
            ESBTPFiliere::withTrashed()->whereIn('code', array_column($lignes, 'code'))->lockForUpdate()->get(['id']);
            if ($this->etat($lignes) !== $proposition->etat['existantes']) {
                throw new PropositionPerimee('Ces filières ont changé depuis la proposition.');
            }

            return app(ReferentielAcademique::class)->enregistrerFilieres($lignes);
        });

        return [
            'message' => "{$compte['crees']} filière(s) créée(s), {$compte['mis_a_jour']} mise(s) à jour.",
            'lien' => route('esbtp.filieres.index', [], false),
            'model_type' => ESBTPFiliere::class,
            'model_id' => null,
            'details' => $compte,
        ];
    }

    /** @return array<string, array{id: int, name: string, description: ?string, active: bool, supprimee: bool}> */
    private function etat(array $lignes): array
    {
        return ESBTPFiliere::withTrashed()->whereIn('code', array_column($lignes, 'code'))->orderBy('id')->get()
            ->mapWithKeys(fn ($f) => [mb_strtoupper((string) $f->code) => [
                'id' => (int) $f->id, 'name' => (string) $f->name, 'description' => $f->description,
                'active' => (bool) $f->is_active, 'supprimee' => $f->trashed(),
            ]])->all();
    }
}
