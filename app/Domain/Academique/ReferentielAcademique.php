<?php

namespace App\Domain\Academique;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use Illuminate\Support\Facades\DB;

/**
 * Filières et niveaux d'études, créés ou mis à jour par lot. Un seul chemin
 * pour la CLI et pour Nanan : `plan*()` constate sans écrire, `enregistrer*()`
 * écrit exactement ce que le plan a montré.
 *
 * Identité : le CODE pour une filière, le couple (type, année) pour un niveau.
 * Rejouer un lot ne crée pas de doublon, il corrige.
 *
 * Refus (rien n'est écrit) : codes en double dans le lot, code tenu par une
 * filière supprimée (l'index unique refuserait la création), code tenu par un
 * reflet LMD (filière créée pour une mention ou un parcours : la réécrire
 * détacherait ses classes de leur sens), année hors du cycle LMD.
 */
class ReferentielAcademique
{
    /**
     * @param array<int, array{name: string, code: string, description?: ?string, is_active?: ?bool}> $lot
     * @return array{lignes: array<int, array<string, mixed>>, refus: string[]}
     */
    public function planFilieres(array $lot): array
    {
        $codes = array_map(fn (array $f) => $this->codeFiliere($f['code']), $lot);
        $refus = [];
        $doublons = array_unique(array_diff_assoc($codes, array_unique($codes)));
        if ($doublons !== []) {
            $refus[] = 'Codes en double dans le lot : '.implode(', ', $doublons).'.';
        }

        $existantes = ESBTPFiliere::withTrashed()->whereIn('code', $codes)->get()->keyBy(fn ($f) => mb_strtoupper((string) $f->code));
        $lignes = [];
        foreach ($lot as $i => $f) {
            $code = $codes[$i];
            $existante = $existantes->get($code);
            if ($existante?->trashed()) {
                $refus[] = "Le code {$code} appartient à une filière supprimée ({$existante->name}) : restaure-la ou choisis un autre code.";
            } elseif ($existante?->estMiroirLmd()) {
                $refus[] = "Le code {$code} est celui d'un reflet LMD ({$existante->name}) : il se gère depuis la structure LMD, pas ici.";
            }
            $lignes[] = [
                'code' => $code,
                'name' => trim((string) $f['name']),
                'description' => $f['description'] ?? null,
                // null : non précisé — vrai à la création, inchangé à la mise à jour.
                'is_active' => isset($f['is_active']) ? (bool) $f['is_active'] : null,
                'action' => $existante ? 'mise a jour' : 'creation',
                'id_existant' => $existante?->id,
                'nom_actuel' => $existante?->name,
            ];
        }

        return ['lignes' => $lignes, 'refus' => array_values(array_unique($refus))];
    }

    /**
     * @param array<int, array<string, mixed>> $lignes lignes d'un plan sans refus
     * @return array{crees: int, mis_a_jour: int}
     */
    public function enregistrerFilieres(array $lignes): array
    {
        return DB::transaction(function () use ($lignes) {
            $crees = 0;
            $misAJour = 0;
            foreach ($lignes as $l) {
                $donnees = ['name' => $l['name'], 'code' => $l['code']];
                if ($l['is_active'] !== null) {
                    $donnees['is_active'] = $l['is_active'];
                }
                // Une description absente du lot n'efface pas celle qui existe :
                // corriger un nom ne doit pas vider une autre colonne.
                if ($l['description'] !== null) {
                    $donnees['description'] = $l['description'];
                }
                $filiere = ESBTPFiliere::where('code', $l['code'])->first();
                if ($filiere) {
                    $filiere->update($donnees);
                    $misAJour++;
                } else {
                    ESBTPFiliere::create($donnees + ['is_active' => true]);
                    $crees++;
                }
            }

            return ['crees' => $crees, 'mis_a_jour' => $misAJour];
        });
    }

    /**
     * @param array<int, array{name: string, type: string, year: int, code?: ?string, libelle?: ?string, is_active?: ?bool}> $lot
     * @return array{lignes: array<int, array<string, mixed>>, refus: string[]}
     */
    public function planNiveaux(array $lot): array
    {
        $refus = [];
        $lot = array_map(fn (array $n) => ['type' => $this->typeNiveau((string) $n['type'])] + $n, $lot);
        $couples = array_map(fn (array $n) => mb_strtolower($n['type']).'#'.(int) $n['year'], $lot);
        if (count($couples) !== count(array_unique($couples))) {
            $refus[] = 'Le lot contient deux fois le même couple type + année.';
        }

        $lignes = [];
        foreach ($lot as $n) {
            $type = $n['type'];
            $annee = (int) $n['year'];
            $attendues = ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD[$type] ?? null;
            if ($attendues !== null && ! in_array($annee, $attendues, true)) {
                $refus[] = sprintf('Un niveau %s porte l\'année %s (l\'année se compte depuis la Licence), pas %d.', $type, implode(' ou ', $attendues), $annee);
            }
            $existant = ESBTPNiveauEtude::where('type', $type)->where('year', $annee)->first();
            $code = isset($n['code']) && trim((string) $n['code']) !== '' ? trim((string) $n['code']) : null;
            if ($code !== null && ESBTPNiveauEtude::withTrashed()->where('code', $code)->when($existant, fn ($q) => $q->whereKeyNot($existant->id))->exists()) {
                $refus[] = "Le code {$code} est déjà pris par un autre niveau.";
            }
            $lignes[] = [
                'type' => $type,
                'year' => $annee,
                'name' => trim((string) $n['name']),
                // null : non donné — le nom à la création, inchangé à la mise à jour.
                'libelle' => isset($n['libelle']) && trim((string) $n['libelle']) !== '' ? trim((string) $n['libelle']) : null,
                'code' => $code,
                'is_active' => isset($n['is_active']) ? (bool) $n['is_active'] : null,
                'action' => $existant ? 'mise a jour' : 'creation',
                'id_existant' => $existant?->id,
                'nom_actuel' => $existant?->name,
            ];
        }

        return ['lignes' => $lignes, 'refus' => array_values(array_unique($refus))];
    }

    /**
     * @param array<int, array<string, mixed>> $lignes lignes d'un plan sans refus
     * @return array{crees: int, mis_a_jour: int}
     */
    public function enregistrerNiveaux(array $lignes): array
    {
        return DB::transaction(function () use ($lignes) {
            $crees = 0;
            $misAJour = 0;
            foreach ($lignes as $l) {
                $donnees = ['name' => $l['name'], 'type' => $l['type'], 'year' => $l['year']];
                if ($l['libelle'] !== null) {
                    $donnees['libelle'] = $l['libelle'];
                }
                if ($l['is_active'] !== null) {
                    $donnees['is_active'] = $l['is_active'];
                }
                // Le code n'écrase pas l'existant quand il n'est pas fourni :
                // un import partiel ne doit pas vider une colonne déjà remplie.
                if ($l['code'] !== null) {
                    $donnees['code'] = $l['code'];
                }
                $niveau = ESBTPNiveauEtude::where('type', $l['type'])->where('year', $l['year'])->first();
                if ($niveau) {
                    $niveau->update($donnees);
                    $misAJour++;
                } else {
                    ESBTPNiveauEtude::create($donnees + ['libelle' => $l['name'], 'is_active' => true]);
                    $crees++;
                }
            }

            return ['crees' => $crees, 'mis_a_jour' => $misAJour];
        });
    }

    /**
     * « master », « MASTER » et « Master » sont le même cycle : le nom canonique
     * est rendu, sinon estUnCycleLmd() (comparaison exacte) ne le reconnaîtrait
     * pas et le contrôle d'année ne s'appliquerait pas.
     */
    private function typeNiveau(string $type): string
    {
        $type = trim($type);
        foreach (array_keys(ESBTPNiveauEtude::ANNEES_PAR_CYCLE_LMD) as $cycle) {
            if (mb_strtolower($cycle) === mb_strtolower($type)) {
                return $cycle;
            }
        }

        return $type;
    }

    private function codeFiliere(string $code): string
    {
        return mb_strtoupper(trim($code));
    }
}
