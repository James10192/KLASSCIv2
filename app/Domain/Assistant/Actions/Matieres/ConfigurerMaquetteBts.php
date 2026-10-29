<?php

namespace App\Domain\Assistant\Actions\Matieres;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\Proposition;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPMatiereFilierNiveau;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\DB;

/**
 * Nanan prépare une modification de maquette BTS au même grain que l'écran
 * /esbtp/matieres/classification. Rien n'est écrit avant validation humaine.
 */
final class ConfigurerMaquetteBts extends ActionAgent
{
    public function cle(): string
    {
        return 'configuration_maquette_bts';
    }

    public function libelle(): string
    {
        return 'Préparation de la maquette BTS…';
    }

    public function description(): string
    {
        return "PROPOSE une configuration de maquette BTS pour un couple filière + niveau : semestre (S1/S2/les deux), tronc commun/spécialité, bloc Général/Technique et rang. "
            . "Utilise search_classes/search_subjects pour obtenir les identifiants. N'invente jamais un identifiant. Rien n'est écrit avant le clic Valider.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'filiere_id' => ['type' => 'integer'],
                'niveau_id' => ['type' => 'integer'],
                'matieres' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'matiere_id' => ['type' => 'integer'],
                            'semestre' => ['type' => 'string', 'enum' => ['s1', 's2', 'les_deux']],
                            'classification' => ['type' => 'string', 'enum' => ['tronc_commun', 'specialite']],
                            'type_formation' => ['type' => 'string', 'enum' => ['general', 'technique']],
                            'ordre_bulletin' => ['type' => 'integer'],
                        ],
                        'required' => ['matiere_id'],
                    ],
                ],
                'valider_semestres' => ['type' => 'boolean', 'description' => 'Valide explicitement les semestres de ce combo.'],
            ],
            'required' => ['filiere_id', 'niveau_id', 'matieres'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $filiereId = (int) ($args['filiere_id'] ?? 0);
        $niveauId = (int) ($args['niveau_id'] ?? 0);
        $items = array_values(array_filter((array) ($args['matieres'] ?? []), 'is_array'));

        $filiere = ESBTPFiliere::find($filiereId);
        $niveau = ESBTPNiveauEtude::find($niveauId);
        if (! $filiere || ! $niveau) {
            return new Proposition('Maquette BTS', '', manques: ['Filière ou niveau introuvable : retrouve le bon couple avant de proposer.']);
        }
        if ($items === [] || count($items) > 100) {
            return new Proposition('Maquette BTS', '', manques: [$items === [] ? 'Aucune matière fournie.' : 'Trop de matières en une fois (100 maximum).']);
        }

        $ids = collect($items)->pluck('matiere_id')->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->count() !== count($items)) {
            return new Proposition('Maquette BTS', '', manques: ['Chaque matière doit apparaître une seule fois avec un identifiant valide.']);
        }

        $lignes = ESBTPMatiereFilierNiveau::query()
            ->forCombo($filiereId, $niveauId)
            ->whereIn('matiere_id', $ids)
            ->with('matiere:id,name,code')
            ->get()
            ->keyBy('matiere_id');

        $manques = [];
        $donnees = [];
        $tableau = [];
        $etat = [];

        foreach ($items as $item) {
            $matiereId = (int) ($item['matiere_id'] ?? 0);
            $ligne = $lignes->get($matiereId);
            if (! $ligne) {
                $manques[] = "La matière #{$matiereId} n'est pas rattachée à ce couple filière × niveau.";
                continue;
            }

            $modifs = ['matiere_id' => $matiereId];
            if (array_key_exists('classification', $item)) {
                if (! in_array($item['classification'], [ESBTPMatiereFilierNiveau::TRONC_COMMUN, ESBTPMatiereFilierNiveau::SPECIALITE], true)) {
                    $manques[] = "Classification invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['classification'] = $item['classification'];
                }
            }
            if (array_key_exists('type_formation', $item)) {
                if (! in_array($item['type_formation'], [ESBTPMatiereFilierNiveau::TYPE_GENERAL, ESBTPMatiereFilierNiveau::TYPE_TECHNIQUE], true)) {
                    $manques[] = "Bloc Général/Technique invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['type_formation'] = $item['type_formation'];
                }
            }
            if (array_key_exists('semestre', $item)) {
                $semestre = match ($item['semestre']) {
                    's1' => 1,
                    's2' => 2,
                    'les_deux' => null,
                    default => '__invalide__',
                };
                if ($semestre === '__invalide__') {
                    $manques[] = "Semestre invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['semestre'] = $semestre;
                }
            }
            if (array_key_exists('ordre_bulletin', $item)) {
                $ordre = (int) $item['ordre_bulletin'];
                if ($ordre < 1 || $ordre > 65535) {
                    $manques[] = "Rang invalide pour {$ligne->matiere?->name}.";
                } else {
                    $modifs['ordre_bulletin'] = $ordre;
                }
            }

            $donnees[] = $modifs;
            $etat[$matiereId] = [
                'classification' => $ligne->classification,
                'type_formation' => $ligne->type_formation,
                'semestre' => $ligne->semestre,
                'semestre_renseigne' => (bool) $ligne->semestre_renseigne,
                'ordre_bulletin' => $ligne->ordre_bulletin,
            ];
            $tableau[] = [
                $ligne->matiere?->name ?? "Matière #{$matiereId}",
                array_key_exists('semestre', $modifs) ? ($modifs['semestre'] === null ? 'Les deux' : 'S'.$modifs['semestre']) : 'inchangé',
                $modifs['classification'] ?? 'inchangé',
                $modifs['type_formation'] ?? 'inchangé',
                isset($modifs['ordre_bulletin']) ? (string) $modifs['ordre_bulletin'] : 'inchangé',
            ];
        }

        return new Proposition(
            titre: 'Maquette BTS · '.$filiere->name.' · '.$niveau->name,
            resume: count($donnees).' matière(s) à mettre à jour pour ce couple filière × niveau.',
            tableau: ['colonnes' => ['Matière', 'Semestre', 'TC / Spécialité', 'Bloc', 'Rang'], 'lignes' => $tableau],
            manques: $manques,
            avertissements: filter_var($args['valider_semestres'] ?? false, FILTER_VALIDATE_BOOLEAN)
                ? ['Les semestres seront validés : ce réglage devient actif pour les bulletins et le suivi des notes.']
                : [],
            donnees: [
                'filiere_id' => $filiereId,
                'niveau_id' => $niveauId,
                'matieres' => $donnees,
                'valider_semestres' => filter_var($args['valider_semestres'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ],
            etat: $etat,
            risque: filter_var($args['valider_semestres'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'eleve' : 'moyen',
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $d = $proposition->donnees;
        $updated = 0;

        DB::transaction(function () use ($d, &$updated): void {
            foreach ($d['matieres'] as $item) {
                $changements = collect($item)->except('matiere_id')->all();
                if ($d['valider_semestres']) {
                    $changements['semestre_renseigne'] = true;
                }
                if ($changements === []) {
                    continue;
                }

                $updated += ESBTPMatiereFilierNiveau::query()
                    ->forCombo($d['filiere_id'], $d['niveau_id'])
                    ->where('matiere_id', $item['matiere_id'])
                    ->update($changements);
            }
        });

        return [
            'message' => "Maquette mise à jour pour {$updated} matière(s).",
            'lien' => route('esbtp.matieres.classification', [
                'filiere_id' => $d['filiere_id'],
                'niveau_id' => $d['niveau_id'],
            ], false),
            'model_type' => ESBTPFiliere::class,
            'model_id' => (int) $d['filiere_id'],
            'details' => ['matieres_mises_a_jour' => $updated],
        ];
    }
}
