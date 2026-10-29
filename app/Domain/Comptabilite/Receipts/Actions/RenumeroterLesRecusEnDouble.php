<?php

namespace App\Domain\Comptabilite\Receipts\Actions;

use App\Models\ESBTPPaiement;
use Illuminate\Support\Facades\DB;

/**
 * Lever l'ambiguite des numeros de recu rendus deux fois.
 *
 * La cause est corrigee : la numerotation ne relit plus le dernier numero en
 * ignorant les paiements supprimes, donc elle ne recule plus. Restent les
 * numeros deja rendus deux fois — 28 a Abidjan, 3 a ISLG, 1 a Yakro au
 * releve de septembre 2026.
 *
 * Ce qui rend l'operation sure, et sans quoi elle ne le serait pas : dans
 * CHAQUE groupe releve, un seul recu est vivant, les autres sont supprimes.
 * On ne touche donc jamais au numero qu'un etudiant detient — seul celui d'un
 * paiement efface est modifie. Un groupe portant deux recus vivants serait une
 * vraie ambiguite comptable, qu'aucun script ne doit trancher : il interrompt
 * l'operation ENTIERE et se signale.
 *
 * Le nouveau numero est l'ancien suffixe `-D2`, `-D3`… Ni une renumerotation
 * dans la sequence vivante — qui consommerait des numeros pour des paiements
 * effaces — ni un identifiant opaque : le lien avec le recu d'origine reste
 * lisible dans les archives, et le suffixe dit lui-meme pourquoi il est la.
 *
 * L'ecriture passe par Eloquent, et non par une requete directe : `numero_recu`
 * figure dans `$auditInclude`, donc chaque changement laisse une trace avec son
 * avant et son apres.
 *
 * Deux passes plutot qu'une transaction qu'on annulerait : on decide d'abord
 * ce qu'il faudrait ecrire, on refuse s'il le faut, et on n'ecrit qu'ensuite.
 * Une simulation ne touche alors jamais a la base, meme le temps d'une
 * transaction.
 */
class RenumeroterLesRecusEnDouble
{
    /**
     * @return array{simulation: bool, groupes: int, lignes_a_modifier: int,
     *               refus: list<string>, ecrit: bool, detail: list<array<string,mixed>>}
     */
    public function executer(bool $simulation = true): array
    {
        $plan = [];
        $refus = [];

        foreach ($this->groupesEnDouble() as $numero) {
            $this->planifierUnGroupe($numero, $plan, $refus);
        }

        $ecrit = false;
        if (! $simulation && $refus === [] && $plan !== []) {
            DB::transaction(function () use ($plan) {
                foreach ($plan as $ligne) {
                    $paiement = ESBTPPaiement::withTrashed()->find($ligne['id']);
                    if ($paiement === null) {
                        continue;
                    }
                    $paiement->numero_recu = $ligne['nouveau'];
                    $paiement->save();
                }
            });
            $ecrit = true;
        }

        return [
            'simulation' => $simulation,
            'groupes' => count(array_unique(array_column($plan, 'ancien'))),
            'lignes_a_modifier' => count($plan),
            'refus' => $refus,
            'ecrit' => $ecrit,
            'detail' => $plan,
        ];
    }

    /** @return list<string> */
    private function groupesEnDouble(): array
    {
        return DB::table('esbtp_paiements')
            ->select('numero_recu')
            ->whereNotNull('numero_recu')
            ->where('numero_recu', '!=', '')
            ->groupBy('numero_recu')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('numero_recu')
            ->pluck('numero_recu')
            ->all();
    }

    /**
     * @param  list<array<string,mixed>>  $plan
     * @param  list<string>  $refus
     */
    private function planifierUnGroupe(string $numero, array &$plan, array &$refus): void
    {
        // Les vivants d'abord, puis par anciennete : celui qui garde le numero
        // est le recu en circulation, ou a defaut le plus ancien.
        $lignes = ESBTPPaiement::withTrashed()
            ->where('numero_recu', $numero)
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('id')
            ->get()
            ->values();

        $vivants = $lignes->filter(fn ($p) => $p->deleted_at === null);

        if ($vivants->count() > 1) {
            $refus[] = sprintf(
                'Le numero %s porte %d recus VIVANTS (ids %s). Deux preuves de paiement valides '
                .'sous le meme numero : lequel garde le sien releve de la comptabilite, pas de ce script.',
                $numero,
                $vivants->count(),
                $vivants->pluck('id')->implode(', ')
            );

            return;
        }

        foreach ($lignes as $rang => $ligne) {
            if ($rang === 0) {
                continue; // celui-la garde son numero
            }

            $plan[] = [
                'id' => $ligne->id,
                'ancien' => $numero,
                'nouveau' => $this->numeroLibre($numero, $rang + 1, $plan),
                'supprime' => $ligne->deleted_at !== null,
                'montant' => (float) $ligne->montant,
                'date_paiement' => optional($ligne->date_paiement)->toDateString(),
            ];
        }
    }

    /**
     * Le suffixe attendu peut deja exister — un passage precedent, ou une
     * saisie manuelle. On avance jusqu'a en trouver un libre plutot que
     * d'echouer a l'ecriture. Le plan en cours compte autant que la base :
     * deux lignes du meme lot ne doivent pas viser le meme numero.
     *
     * @param  list<array<string,mixed>>  $plan
     */
    private function numeroLibre(string $base, int $rang, array $plan): string
    {
        $dejaPrevus = array_column($plan, 'nouveau');

        while (true) {
            $candidat = $base . '-D' . $rang;

            $pris = in_array($candidat, $dejaPrevus, true)
                || ESBTPPaiement::withTrashed()->where('numero_recu', $candidat)->exists();

            if (! $pris) {
                return $candidat;
            }

            $rang++;
        }
    }
}
