<?php

namespace App\Services\LMD;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDDomaine;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;

/**
 * Parcours guidé d'ouverture de classes LMD.
 *
 * Cherche d'abord une structure existante. Lorsqu'elle manque, demande les
 * données réelles de l'école et construit un aperçu via HierarchieLmd.
 * Aucune écriture ici ; installer() n'est appelé qu'après Valider.
 */
class PreparationParcoursClassesLmd
{
    public function __construct(private HierarchieLmd $hierarchie)
    {
    }

    /** @return array{choix?:array, spec?:array, apercu?:array, manques:array, avertissements:array} */
    public function preparer(string $cible, array $structure, string $type, $user): array
    {
        $sortie = ['manques' => [], 'avertissements' => []];
        if (! $user->can('lmd.structure.manage')) {
            $sortie['manques'][] = "La création de la structure nécessite la permission lmd.structure.manage en plus de classes.create.";

            return $sortie;
        }

        $existante = ESBTPFiliere::withTrashed()->where('code', $cible)->first();
        if ($existante?->trashed() || ($existante && ! $existante->is_active)) {
            $sortie['manques'][] = "La filière {$cible} existe mais est désactivée/supprimée : demande sa restauration ou sa réactivation avant de l'utiliser.";
            return $sortie;
        }
        $spec = [];
        foreach (['domaine' => ESBTPLMDDomaine::class, 'mention' => ESBTPLMDMention::class, 'parcours' => ESBTPLMDParcours::class] as $partie => $modele) {
            $valeurs = is_array($structure[$partie] ?? null) ? $structure[$partie] : [];
            $code = trim((string) ($valeurs['code'] ?? ''));
            $nom = trim((string) ($valeurs['name'] ?? ''));
            if ($code === '') {
                $sortie['manques'][] = "Quel est le CODE du {$partie} LMD pour {$cible} ? Indique aussi son nom s'il n'existe pas.";
                continue;
            }
            $fiche = $modele::withTrashed()->where('code', $code)->first();
            if ($fiche && ($fiche->trashed() || ! $fiche->is_active)) {
                $sortie['manques'][] = "Le {$partie} {$code} existe mais n'est pas actif : demande sa restauration/réactivation, sans créer de doublon.";
                continue;
            }
            if ($fiche && $nom !== '' && $nom !== $fiche->name) {
                $sortie['manques'][] = "Le {$partie} {$code} existe sous le nom « {$fiche->name} ». Confirme la fiche existante sans la renommer, ou donne un nouveau code.";
                continue;
            }
            if ($nom === '' && ! $fiche) {
                $sortie['manques'][] = "Quel est le NOM officiel du {$partie} {$code} ? Aucun nom ne doit être inventé.";
                continue;
            }
            $spec[$partie] = ['code' => $code, 'name' => $fiche?->name ?? $nom];
        }

        if ($sortie['manques'] !== []) {
            $sortie['avertissements'][] = $existante
                ? "La filière {$cible} (« {$existante->name} ») existe déjà : on peut la rattacher à un parcours LMD après confirmation de la hiérarchie."
                : "La filière {$cible} n'existe pas : un ancrage pourra être créé lors de la validation.";
            return $sortie;
        }

        $filiereSpec = is_array($structure['filiere'] ?? null) ? $structure['filiere'] : [];
        $filiereCode = trim((string) ($filiereSpec['code'] ?? ''));
        if ($existante && $filiereCode === '') {
            $filiereCode = $cible;
        }
        if ($filiereCode !== '') {
            $filiere = ESBTPFiliere::withTrashed()->where('code', $filiereCode)->first();
            $nom = trim((string) ($filiereSpec['name'] ?? ''));
            if ($filiere && ($filiere->trashed() || ! $filiere->is_active)) {
                $sortie['manques'][] = "La filière {$filiereCode} est désactivée/supprimée : restauration préalable nécessaire.";
            } elseif ($filiere && $filiere->estMiroirLmd()) {
                $sortie['manques'][] = "La filière {$filiereCode} est un reflet LMD : il faut sélectionner sa mention ou son parcours, pas la modifier.";
            } elseif ($filiere && $nom !== '' && $nom !== $filiere->name) {
                $sortie['manques'][] = "La filière {$filiereCode} s'appelle « {$filiere->name} » : impossible de la renommer dans cette opération.";
            } elseif (! $filiere && $nom === '') {
                $sortie['manques'][] = "Quel est le nom officiel de la filière {$filiereCode} à créer ?";
            } else {
                $spec['filiere'] = ['code' => $filiereCode, 'name' => $filiere?->name ?? $nom];
            }
        }

        // L'école demande GGT : son code doit désigner le parcours, la
        // mention ou la filière explicitement rattachée, pas une autre entité.
        $codes = array_filter([$spec['parcours']['code'], $spec['mention']['code'], $filiereCode]);
        if (! in_array($cible, $codes, true)) {
            $sortie['manques'][] = "Le code {$cible} ne correspond ni au parcours, ni à la mention, ni à la filière indiqués. Précise le rattachement réel.";
        }
        if ($type === 'mention' && $spec['mention']['code'] !== $cible) {
            $sortie['manques'][] = "Pour une classe de mention, le code de la mention doit être {$cible}.";
        }
        if ($type === 'parcours' && $spec['parcours']['code'] !== $cible && $filiereCode !== $cible) {
            $sortie['manques'][] = "Pour une classe de parcours, précise son code ou la filière {$cible} qui lui correspond.";
        }
        if ($sortie['manques'] !== []) {
            return $sortie;
        }

        // On installe un vrai parcours, pas un simple alias ou un niveau BTS.
        $apercu = $this->hierarchie->apercu($spec);
        if ($apercu['conflits'] !== []) {
            $sortie['manques'] = array_column($apercu['conflits'], 'detail');
            return $sortie;
        }
        if (! $existante && ! isset($spec['filiere']) && $spec['parcours']['code'] !== $cible) {
            $sortie['manques'][] = "Le parcours {$spec['parcours']['code']} n'a pas de filière {$cible} : donne son nom/code exact pour éviter un mauvais rattachement.";
            return $sortie;
        }

        $sortie['spec'] = $spec;
        $sortie['apercu'] = $apercu;
        $sortie['choix'] = [
            'type' => $type === 'mention' ? 'mention' : 'parcours',
            'id' => null,
            'code' => $type === 'mention' ? $spec['mention']['code'] : $spec['parcours']['code'],
            'nom' => $type === 'mention' ? $spec['mention']['name'] : $spec['parcours']['name'],
        ];
        $sortie['avertissements'][] = 'Structure Domaine → Mention → Parcours à créer ou réutiliser ; chaque modification sera détaillée avant Valider.';

        return $sortie;
    }
}
