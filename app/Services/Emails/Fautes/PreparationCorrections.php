<?php

namespace App\Services\Emails\Fautes;

use App\Services\Emails\InventaireAdresses;
use Illuminate\Support\Facades\DB;

/**
 * Transforme les cles validees par l'ecole en GROUPES a ecrire.
 *
 * Pour chaque cle : cible valide et presente, controle d'ecriture
 * (ControleEcriture), puis les lignes liees du meme dossier, recalculees ici.
 * Une cle dont une ligne liee manque a la requete est refusee
 * (`lies_non_valides`) : corriger l'une sans l'autre les ferait diverger.
 *
 * Les cles liees forment un groupe (composante connexe). Un groupe s'ecrit en
 * entier ou pas du tout : une seule cle refusee refuse les autres
 * (`groupe_refuse`).
 */
class PreparationCorrections
{
    public function __construct(
        private readonly InventaireAdresses $inventaire,
        private readonly ControleEcriture $controle,
        private readonly LiensDossier $liens,
    ) {}

    /**
     * @param  list<array{cle: string, domaine_propose: string, domaine_actuel?: ?string}>  $corrections
     * @return array{groupes: list<list<array{cible: CibleCorrection, ancienne: string, nouvelle: string}>>, ignorees: list<array{cle: string, motif: string}>}
     */
    public function preparer(array $corrections, bool $inclureComptes, bool $inclureProbables): array
    {
        $colonnes = $this->inventaire->colonnes();
        $demandees = array_column($corrections, null, 'cle');
        $etats = [];
        foreach ($demandees as $cle => $correction) {
            $etats[$cle] = $this->etat((string) $cle, $correction, $inclureComptes, $inclureProbables, $colonnes);
            if ($etats[$cle]['motif'] === null && array_diff($etats[$cle]['lies'], array_keys($demandees)) !== []) {
                $etats[$cle]['motif'] = MotifsCorrection::LIES_NON_VALIDES;
            }
        }

        $groupes = [];
        $ignorees = [];
        foreach ($this->composantes($etats) as $membres) {
            $refuse = array_filter($membres, fn ($cle) => $etats[$cle]['motif'] !== null) !== [];
            if (! $refuse) {
                $groupes[] = array_map(fn ($cle) => $etats[$cle]['prevue'], $membres);

                continue;
            }
            foreach ($membres as $cle) {
                $ignorees[] = ['cle' => $cle, 'motif' => $etats[$cle]['motif'] ?? MotifsCorrection::GROUPE_REFUSE];
            }
        }
        // Dans l'ordre de la requete.
        $ordre = array_flip(array_keys($demandees));
        usort($ignorees, fn ($a, $b) => $ordre[$a['cle']] <=> $ordre[$b['cle']]);

        return ['groupes' => $groupes, 'ignorees' => $ignorees];
    }

    /**
     * @param  list<array{table: string, colonne: string}>  $colonnes
     * @return array{motif: ?string, lies: list<string>, prevue: ?array{cible: CibleCorrection, ancienne: string, nouvelle: string}}
     */
    private function etat(string $cle, array $correction, bool $inclureComptes, bool $inclureProbables, array $colonnes): array
    {
        $cible = CibleCorrection::depuisCle($cle, $inclureComptes, $colonnes);
        if ($cible === null) {
            return ['motif' => MotifsCorrection::CLE_INVALIDE, 'lies' => [], 'prevue' => null];
        }
        $ligne = DB::table($cible->table)->where('id', $cible->id)->first([$cible->colonne]);
        if ($ligne === null) {
            return ['motif' => MotifsCorrection::INTROUVABLE, 'lies' => [], 'prevue' => null];
        }

        $actuelle = $ligne->{$cible->colonne};
        $lies = array_column($this->liens->lies($cible, (string) $actuelle), 'cle');
        [$motif, $nouvelle] = $this->controle->verifier($actuelle, $correction, $inclureProbables);

        return ['motif' => $motif, 'lies' => $lies,
            'prevue' => $motif === null ? ['cible' => $cible, 'ancienne' => (string) $actuelle, 'nouvelle' => (string) $nouvelle] : null];
    }

    /**
     * Les groupes de cles demandees reliees entre elles (liens dans les deux sens).
     *
     * @param  array<string, array{lies: list<string>}>  $etats
     * @return list<list<string>>
     */
    private function composantes(array $etats): array
    {
        $voisins = array_fill_keys(array_keys($etats), []);
        foreach ($etats as $cle => $etat) {
            foreach ($etat['lies'] as $lie) {
                if (isset($voisins[$lie])) {
                    $voisins[$cle][] = $lie;
                    $voisins[$lie][] = $cle;
                }
            }
        }

        $vues = [];
        $composantes = [];
        foreach (array_keys($voisins) as $depart) {
            if (isset($vues[$depart])) {
                continue;
            }
            $pile = [$depart];
            $membres = [];
            while ($pile !== []) {
                $cle = array_pop($pile);
                if (isset($vues[$cle])) {
                    continue;
                }
                $vues[$cle] = true;
                $membres[] = $cle;
                array_push($pile, ...$voisins[$cle]);
            }
            $composantes[] = $membres;
        }

        return $composantes;
    }
}
