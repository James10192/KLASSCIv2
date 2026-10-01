<?php

namespace App\Domain\Assistant\Actions\Academique;

use App\Domain\Academique\ReferentielAcademique;
use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\DB;

/**
 * Propose de créer des niveaux d'études, ou de corriger leur nom. L'identité
 * d'un niveau est son couple (type, année) : « BTS 1 » et « Première année
 * BTS » désignent le même. Écrit par ReferentielAcademique, le chemin de la CLI.
 *
 * Changer l'ANNÉE d'un niveau existant n'est pas ici : c'est
 * proposer_annee_niveau, qui vérifie ce qui y est rattaché.
 */
class EnregistrerNiveaux extends ActionAgent
{
    private const MAX = 50;

    public function cle(): string
    {
        return 'niveaux';
    }

    public function description(): string
    {
        return "PROPOSE de créer des niveaux d'études, ou de corriger le nom d'un niveau existant (même type et même année). `niveaux` = [{nom, type, annee, code?, libelle?, active?}] ; "
            . "type = BTS, Licence, Master, Doctorat… ; en LMD l'année se compte depuis la Licence (Master 1 = 4). Valeurs telles que données. Rien n'est écrit avant « Valider ».";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'niveaux' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'nom' => ['type' => 'string'],
                            'type' => ['type' => 'string'],
                            'annee' => ['type' => 'integer'],
                            'code' => ['type' => 'string'],
                            'libelle' => ['type' => 'string'],
                            'active' => ['type' => 'boolean'],
                        ],
                    ],
                ],
            ],
            'required' => ['niveaux'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = "Niveaux d'études";
        $lot = [];
        $manques = [];
        foreach (array_values(array_filter((array) ($args['niveaux'] ?? []), 'is_array')) as $i => $n) {
            $nom = trim((string) ($n['nom'] ?? ''));
            $type = trim((string) ($n['type'] ?? ''));
            $annee = $n['annee'] ?? null;
            if ($nom === '' || $type === '' || ! is_numeric($annee)) {
                $manques[] = 'Ligne '.($i + 1).' : il faut le nom, le type (BTS, Licence, Master…) et l\'année.';
                continue;
            }
            if ((int) $annee < 1 || (int) $annee > 10) {
                $manques[] = 'Ligne '.($i + 1).' : l\'année va de 1 à 10.';
                continue;
            }
            $lot[] = ['name' => $nom, 'type' => $type, 'year' => (int) $annee, 'code' => $n['code'] ?? null,
                'libelle' => $n['libelle'] ?? null, 'is_active' => array_key_exists('active', $n) ? (bool) $n['active'] : null];
        }
        if ($lot === [] && $manques === []) {
            $manques[] = 'Quels niveaux ? Donne au moins un nom, un type et une année.';
        }
        if (count($lot) > self::MAX) {
            $manques[] = 'Plus de '.self::MAX.' niveaux : découpe le lot.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $plan = app(ReferentielAcademique::class)->planNiveaux($lot);
        $manques = $plan['refus'];
        $misesAJour = array_filter($plan['lignes'], fn ($l) => $l['action'] === 'mise a jour');
        if ($misesAJour !== [] && ! $user->can('niveaux.edit')) {
            $manques[] = "Ces niveaux existent déjà et vous n'avez pas le droit de les modifier : "
                .implode(', ', array_map(fn ($l) => $l['nom_actuel'], $misesAJour)).'.';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $lignes = array_map(fn ($l) => [
            $l['type'].' · année '.$l['year'],
            $l['nom_actuel'] && $l['nom_actuel'] !== $l['name'] ? $l['nom_actuel'].' → '.$l['name'] : $l['name'],
            $l['code'] ?? '—',
            $l['action'] === 'creation' ? 'Création' : 'Mise à jour',
        ], $plan['lignes']);

        return new Proposition(
            titre: $titre,
            resume: sprintf('%d niveau(x) créé(s), %d mis à jour.', count($plan['lignes']) - count($misesAJour), count($misesAJour)),
            tableau: ['colonnes' => ['Type et année', 'Nom', 'Code', 'Action'], 'lignes' => $lignes],
            donnees: ['lignes' => $plan['lignes']],
            etat: ['existants' => $this->etat($plan['lignes'])],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $lignes = $proposition->donnees['lignes'];

        $compte = DB::transaction(function () use ($lignes, $proposition) {
            ESBTPNiveauEtude::whereIn('type', array_unique(array_column($lignes, 'type')))->lockForUpdate()->get(['id']);
            if ($this->etat($lignes) !== $proposition->etat['existants']) {
                throw new PropositionPerimee('Ces niveaux ont changé depuis la proposition.');
            }

            return app(ReferentielAcademique::class)->enregistrerNiveaux($lignes);
        });

        return [
            'message' => "{$compte['crees']} niveau(x) créé(s), {$compte['mis_a_jour']} mis à jour.",
            'lien' => route('esbtp.niveaux-etudes.index', [], false),
            'model_type' => ESBTPNiveauEtude::class,
            'model_id' => null,
            'details' => $compte,
        ];
    }

    /** @return array<string, array{id: int, name: string, code: ?string, active: bool}> */
    private function etat(array $lignes): array
    {
        $etat = [];
        foreach ($lignes as $l) {
            $n = ESBTPNiveauEtude::where('type', $l['type'])->where('year', $l['year'])->first();
            $etat[$l['type'].'#'.$l['year']] = $n ? ['id' => (int) $n->id, 'name' => (string) $n->name, 'code' => $n->code, 'active' => (bool) $n->is_active] : null;
        }

        return $etat;
    }
}
