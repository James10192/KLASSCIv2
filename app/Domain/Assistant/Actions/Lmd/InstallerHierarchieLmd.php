<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPLMDParcours;
use App\Services\LMD\ConflitDeMaquette;
use App\Services\LMD\HierarchieLmd;
use App\Services\LMD\ReglesDeMaquette;
use Illuminate\Support\Facades\Validator;

/**
 * Poser Domaine → Mention → Parcours (et sa filière) : HierarchieLmd, le même
 * chemin que `POST /api/cli/lmd/setup`. Ce qui existe sous le même code est
 * réutilisé ; un code qui désigne déjà une fiche rattachée ailleurs est refusé.
 *
 * Plus strict que la CLI sur un point : chaque niveau porte un CODE donné par
 * l'école. Déduit d'un nom, deux « Gestion » de deux domaines se télescopent
 * (lmd-cli-maquette-import).
 */
class InstallerHierarchieLmd extends ActionAgent
{
    public function __construct(private HierarchieLmd $hierarchie)
    {
    }

    public function cle(): string
    {
        return 'hierarchie_lmd';
    }

    public function libelle(): string
    {
        return 'Préparation de la structure LMD…';
    }

    public function description(): string
    {
        return "PROPOSE de créer (ou de compléter) un domaine, une mention et un parcours LMD, et la filière du parcours. "
            . "Chaque niveau : nom ET code exacts donnés par l'école (jamais déduits). Ce qui existe sous le même code est réutilisé.";
    }

    public function parameters(): array
    {
        $niveau = fn (string $desc) => ['type' => 'object', 'description' => $desc, 'properties' => [
            'name' => ['type' => 'string'], 'code' => ['type' => 'string'],
        ]];

        return [
            'type' => 'object',
            'properties' => [
                'domaine' => $niveau('Domaine (ex. Sciences et Technologies, ST).'),
                'mention' => $niveau('Mention (ex. Génie Civil, GC).'),
                'parcours' => ['type' => 'object', 'properties' => [
                    'name' => ['type' => 'string'], 'code' => ['type' => 'string'],
                    'credits_licence' => ['type' => 'integer'], 'credits_master' => ['type' => 'integer'],
                ]],
                'filiere' => $niveau('Facultatif : filière du parcours.'),
            ],
            'required' => ['domaine', 'mention', 'parcours'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Structure LMD';
        $spec = $this->spec($args);
        $manques = $this->manques($spec);
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        $apercu = $this->hierarchie->apercu($spec);
        if ($apercu['conflits'] !== []) {
            return new Proposition(titre: $titre, resume: '', manques: array_column($apercu['conflits'], 'detail'));
        }
        $codes = $this->hierarchie->codes($spec);
        $parcours = ESBTPLMDParcours::with('filiere')->where('code', $codes['parcours'])->first();
        $filiereInchangee = $codes['filiere'] === null || ($parcours && $parcours->filiere?->code === $codes['filiere']);
        if ($filiereInchangee && collect($apercu['lignes'])->every(fn ($l) => $l['avant'] === $l['apres'])) {
            return new Proposition(titre: $titre, resume: '', manques: ['Rien à changer : cette structure existe déjà telle quelle.']);
        }

        $effet = fn ($l) => $l['avant'] === null ? 'Créé' : ($l['avant'] === $l['apres'] ? 'Réutilisé' : 'Renommé');

        return new Proposition(
            titre: 'Structure LMD : ' . $spec['parcours']['name'],
            resume: sprintf('%s → %s → %s%s.', $spec['domaine']['name'], $spec['mention']['name'], $spec['parcours']['name'],
                $codes['filiere'] ? ' (filière ' . $codes['filiere'] . ')' : ''),
            tableau: [
                'colonnes' => ['Niveau', 'Code', 'Aujourd\'hui', 'Après', 'Effet'],
                'lignes' => array_map(fn ($l) => [$l['niveau'], $l['code'], $l['avant'] ?? '—', $l['apres'], $effet($l)], $apercu['lignes']),
            ],
            avertissements: array_values(array_filter([
                collect($apercu['lignes'])->contains(fn ($l) => $l['avant'] !== null && $l['avant'] !== $l['apres'])
                    ? 'Un nom existant sera remplacé : vérifiez la colonne « Après ».' : null,
            ])),
            donnees: ['spec' => $spec],
            etat: ['lignes' => $apercu['lignes'], 'filiere_du_parcours' => $parcours?->filiere?->code],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $spec = $proposition->donnees['spec'];
        if ($this->hierarchie->apercu($spec)['lignes'] !== $proposition->etat['lignes']) {
            throw new PropositionPerimee('La structure LMD a changé depuis la proposition.');
        }
        try {
            $r = $this->hierarchie->installer($spec, (int) $user->id);
        } catch (ConflitDeMaquette $e) {
            throw new PropositionPerimee(implode(' ', array_column($e->conflits(), 'detail')));
        }

        return [
            'message' => "Structure LMD en place : {$r['domaine']->code} → {$r['mention']->code} → {$r['parcours']->code}.",
            'lien' => route('esbtp.lmd.parcours-domain.index', [], false),
            'model_type' => ESBTPLMDParcours::class,
            'model_id' => (int) $r['parcours']->id,
        ];
    }

    private function spec(array $args): array
    {
        $niveau = fn ($n) => array_filter((array) $n, fn ($v) => $v !== null && $v !== '');
        $spec = [
            'domaine' => $niveau($args['domaine'] ?? []),
            'mention' => $niveau($args['mention'] ?? []),
            'parcours' => $niveau($args['parcours'] ?? []),
        ];
        if (! empty($args['filiere']['name'] ?? null) || ! empty($args['filiere']['code'] ?? null)) {
            $spec['filiere'] = $niveau($args['filiere']);
        }

        return $spec;
    }

    /** @return string[] */
    private function manques(array $spec): array
    {
        $manques = Validator::make($spec, ReglesDeMaquette::hierarchie())->errors()->all();
        foreach (['domaine' => 'du domaine', 'mention' => 'de la mention', 'parcours' => 'du parcours', 'filiere' => 'de la filière'] as $cle => $libelle) {
            if (! isset($spec[$cle])) {
                continue;
            }
            if (trim((string) ($spec[$cle]['name'] ?? '')) === '') {
                $manques[] = "Quel est le nom {$libelle} ?";
            }
            if (trim((string) ($spec[$cle]['code'] ?? '')) === '') {
                $manques[] = "Quel est le code {$libelle} ? (donné par l'école, jamais déduit du nom)";
            }
        }

        return array_values(array_unique($manques));
    }
}
