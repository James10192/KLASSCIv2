<?php

namespace App\Domain\Assistant\Actions\Lmd;

use App\Domain\Assistant\Actions\ActionAgent;
use App\Domain\Assistant\Actions\LectureDeColonnes;
use App\Domain\Assistant\Actions\Proposition;
use App\Domain\Assistant\Actions\PropositionPerimee;
use App\Models\ESBTPPlanificationAcademique;
use App\Services\LMD\LMDEnseignantsImporter;
use Illuminate\Support\Facades\DB;

/**
 * Affecter l'enseignant principal des ECUE depuis une liste jointe (code ECUE →
 * enseignant) : LMDEnseignantsImporter, le même import que
 * `POST /api/cli/lmd/import-enseignants`, lu depuis le fichier au lieu des
 * JSON du dépôt.
 *
 * Deux gardes de plus que la CLI, parce que Nanan ne devine rien :
 *  - aucun compte n'est créé : un enseignant inconnu est signalé, et son
 *    ECUE reste sans affectation (créer un compte et son mot de passe relève
 *    de l'écran Enseignants) ;
 *  - aucun rattrapage par préfixe de code, et un nom ne désigne qu'un seul
 *    compte ENSEIGNANT (jamais un étudiant homonyme).
 */
class AffecterEnseignantsLmd extends ActionAgent
{
    use LectureDeColonnes;

    public function cle(): string
    {
        return 'affectation_enseignants_lmd';
    }

    public function libelle(): string
    {
        return 'Préparation des affectations d’enseignants…';
    }

    public function description(): string
    {
        return "PROPOSE d'affecter l'enseignant principal des ECUE LMD depuis un fichier joint (une ligne : code de l'ECUE, nom de l'enseignant). "
            . "Passe piece_id et les noms EXACTS des colonnes ; le serveur relit le fichier. N'invente aucun code ni nom. "
            . "Un enseignant sans compte est signalé, jamais créé.";
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'piece' => [
                    'type' => 'object',
                    'properties' => [
                        'piece_id' => ['type' => 'string'],
                        'colonne_ecue' => ['type' => 'string', 'description' => "Colonne du code de l'ECUE."],
                        'colonne_enseignant' => ['type' => 'string', 'description' => "Colonne du nom de l'enseignant."],
                        'colonne_ue' => ['type' => 'string', 'description' => "Facultatif : colonne du code de l'UE."],
                        'feuille' => ['type' => 'string'],
                    ],
                    'required' => ['piece_id', 'colonne_ecue', 'colonne_enseignant'],
                ],
            ],
            'required' => ['piece'],
        ];
    }

    public function preparer(array $args, $user): Proposition
    {
        $titre = 'Affectation des enseignants';
        if (! is_array($args['piece'] ?? null)) {
            return new Proposition(titre: $titre, resume: '', manques: ['Joins la liste des enseignants (un fichier) et indique ses colonnes.']);
        }
        $p = $args['piece'];
        [$lignes, $manques, $avertissements] = $this->lireColonnes($p, $user, [
            'ecue' => $p['colonne_ecue'] ?? '', 'enseignant' => $p['colonne_enseignant'] ?? '', 'ue' => $p['colonne_ue'] ?? null,
        ]);
        if (($p['colonne_ecue'] ?? '') === '' || ($p['colonne_enseignant'] ?? '') === '') {
            $manques[] = 'Quelles colonnes donnent le code de l\'ECUE et le nom de l\'enseignant ?';
        }
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }

        [$donnees, $manques, $sansEnseignant] = $this->donnees($lignes);
        if ($manques !== []) {
            return new Proposition(titre: $titre, resume: '', manques: $manques);
        }
        $stats = $this->importer(true, $donnees);
        $effectives = $this->effectives($stats);

        $avertissements = array_merge($avertissements, $this->avertissements($stats, $sansEnseignant));
        if ($effectives === []) {
            return new Proposition(titre: $titre, resume: '', manques: array_merge(['Rien à affecter : aucune planification ne change.'], $avertissements));
        }

        return new Proposition(
            titre: 'Affecter ' . count($effectives) . ' ECUE à leur enseignant',
            resume: count($effectives) . ' ECUE reçoivent un enseignant principal (' . array_sum(array_column($effectives, 'planifications')) . ' planification(s)).',
            tableau: [
                'colonnes' => ['ECUE', 'Intitulé', 'Enseignant actuel', 'Après', 'Planifications'],
                'lignes' => array_map(fn ($a) => [$a['ecue'], $a['ecue_nom'], implode(', ', $a['avant']) ?: '—', $a['enseignant'], (string) $a['planifications']], $effectives),
            ],
            avertissements: $avertissements,
            donnees: ['donnees' => $donnees],
            etat: ['affectations' => $stats['affectations']],
        );
    }

    public function executer(Proposition $proposition, $user): array
    {
        $donnees = $proposition->donnees['donnees'];

        // L'import valide lui-même ; l'enveloppe permet de tout annuler si ce
        // qu'il a écrit n'est plus ce qui a été montré.
        $stats = DB::transaction(function () use ($donnees, $proposition) {
            $stats = $this->importer(false, $donnees);
            if ($stats['affectations'] !== $proposition->etat['affectations']) {
                throw new PropositionPerimee('Les affectations ont changé depuis la proposition.');
            }

            return $stats;
        });

        return [
            'message' => count($this->effectives($stats)) . ' ECUE affecté(s) à leur enseignant.',
            'lien' => route('esbtp.lmd.planning.index', [], false),
            'model_type' => ESBTPPlanificationAcademique::class,
            'model_id' => null,
            'details' => ['planifications' => $stats['ecues_assigned']],
        ];
    }

    private function importer(bool $simuler, array $donnees): array
    {
        $import = new LMDEnseignantsImporter($simuler, false, creerLesComptes: false, correspondanceStricte: true);

        return $import->importDonnees($donnees, 'nanan');
    }

    /** @return list<array<string, mixed>> */
    private function effectives(array $stats): array
    {
        return array_values(array_filter($stats['affectations'], fn ($a) => $a['planifications'] > 0));
    }

    /**
     * Les lignes du fichier → la forme de l'import (UE → ECUE, ou ECUE à plat).
     * Un même ECUE donné à deux enseignants est une question, pas un choix.
     *
     * @return array{0: array, 1: string[], 2: int}
     */
    private function donnees(array $lignes): array
    {
        $parEcue = [];
        $manques = [];
        $sansEnseignant = 0;
        foreach ($lignes as $l) {
            $ecue = trim((string) ($l['ecue'] ?? ''));
            $nom = trim((string) ($l['enseignant'] ?? ''));
            if ($ecue === '') {
                continue;
            }
            if ($nom === '') {
                $sansEnseignant++;
                continue;
            }
            $cle = mb_strtoupper($ecue);
            if (isset($parEcue[$cle]) && mb_strtolower($parEcue[$cle]['nom']) !== mb_strtolower($nom)) {
                $manques[] = "L'ECUE {$ecue} est donné à {$parEcue[$cle]['nom']} et à {$nom} : lequel est l'enseignant principal ?";
                continue;
            }
            $parEcue[$cle] = ['code' => $ecue, 'nom' => $nom, 'ue' => trim((string) ($l['ue'] ?? ''))];
        }
        if ($parEcue === [] && $manques === []) {
            $manques[] = 'Aucune ligne avec un code d\'ECUE et un enseignant dans ce fichier.';
        }

        $donnees = ['ues' => [], 'ecues' => []];
        foreach ($parEcue as $e) {
            $ecue = ['code' => $e['code'], 'enseignants' => [['name' => $e['nom']]]];
            $e['ue'] !== '' ? $donnees['ues'][$e['ue']]['ecues'][] = $ecue : $donnees['ecues'][] = $ecue;
        }
        $donnees['ues'] = array_values(array_map(fn ($code, $ue) => ['ue_code' => (string) $code, 'ecues' => $ue['ecues']], array_keys($donnees['ues']), $donnees['ues']));

        return [$donnees, $manques, $sansEnseignant];
    }

    /** @return string[] */
    private function avertissements(array $stats, int $sansEnseignant): array
    {
        $inconnus = array_values(array_unique($stats['enseignants_inconnus'] ?? []));
        $autres = array_values(array_filter($stats['warnings'], fn ($w) => ! str_starts_with($w, 'Enseignant inconnu')));
        $sansPlanif = array_values(array_filter($stats['affectations'], fn ($a) => $a['planifications'] === 0 && $a['avant'] === []));

        return array_values(array_filter([
            $inconnus !== [] ? count($inconnus) . ' enseignant(s) sans compte, leurs ECUE restent sans affectation : ' . implode(', ', array_slice($inconnus, 0, 10)) . '. Créez leur compte depuis l\'écran Enseignants, puis relancez.' : null,
            $sansEnseignant > 0 ? "{$sansEnseignant} ligne(s) sans enseignant, ignorées." : null,
            $sansPlanif !== [] ? count($sansPlanif) . ' ECUE sans planification (rien à affecter) : ' . implode(', ', array_slice(array_column($sansPlanif, 'ecue'), 0, 10)) . '.' : null,
            ...array_slice($autres, 0, 10),
        ]));
    }
}
