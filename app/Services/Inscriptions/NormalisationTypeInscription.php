<?php

namespace App\Services\Inscriptions;

use App\Models\ESBTPInscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Remet `type_inscription` sur les seules valeurs que l'enum declare.
 *
 * La colonne est un enum : « première_inscription » et « réinscription »,
 * accentues. Mais le code a longtemps ecrit et compare « reinscription » sans
 * accent — dix occurrences contre huit accentuees au moment ou ce service est
 * ecrit. Selon le mode SQL du serveur, une ecriture non conforme est refusee ou
 * silencieusement remplacee par une chaine vide.
 *
 * Une inscription dont le type ne vaut aucune des deux valeurs attendues n'est
 * ni une premiere inscription ni une reinscription : elle n'est rien. Toute
 * regle qui se branche dessus — au premier chef « ce frais ne concerne que les
 * nouveaux » — se trompera sur ces lignes.
 *
 * On ne devine pas : on rapproche ce que dit la colonne du NOMBRE d'inscriptions
 * de l'etudiant, qui est un fait. Un etudiant a sa premiere inscription si c'est
 * la plus ancienne qu'il possede. Le recensement montre les desaccords ; la
 * normalisation ne corrige que ce qui est hors enum, jamais un type valide que
 * l'ecole aurait pose volontairement.
 */
class NormalisationTypeInscription
{
    public const PREMIERE = 'première_inscription';
    public const REINSCRIPTION = 'réinscription';

    /**
     * Ce que la colonne contient reellement, et ce que les faits en disent.
     *
     * Lecture seule.
     */
    public function recenser(): array
    {
        $parValeur = ESBTPInscription::query()
            ->select('type_inscription', DB::raw('COUNT(*) as total'))
            ->groupBy('type_inscription')
            ->pluck('total', 'type_inscription')
            ->toArray();

        $horsEnum = [];
        foreach ($parValeur as $valeur => $total) {
            if (! in_array((string) $valeur, [self::PREMIERE, self::REINSCRIPTION], true)) {
                $horsEnum[$valeur === null ? '(null)' : (string) $valeur] = $total;
            }
        }

        $desaccords = $this->desaccords();

        return [
            'par_valeur' => $parValeur,
            'hors_enum' => $horsEnum,
            'hors_enum_total' => array_sum($horsEnum),
            'desaccords' => $desaccords,
            'desaccords_total' => count($desaccords),
        ];
    }

    /**
     * Les lignes ou le type declare contredit le rang de l'inscription.
     *
     * Le rang est un fait : la plus ancienne inscription d'un etudiant est sa
     * premiere. On ne corrige PAS sur cette base — une ecole peut avoir ses
     * raisons — mais un desaccord signale une donnee a regarder.
     *
     * @return array<int, array>
     */
    public function desaccords(int $limite = 200): array
    {
        $lignes = [];

        ESBTPInscription::query()
            ->select('id', 'etudiant_id', 'type_inscription', 'annee_universitaire_id', 'created_at')
            ->orderBy('etudiant_id')
            ->orderBy('created_at')
            ->orderBy('id')
            ->chunk(500, function ($lot) use (&$lignes, $limite) {
                foreach ($lot as $inscription) {
                    if (count($lignes) >= $limite) {
                        return false;
                    }

                    $rang = ESBTPInscription::query()
                        ->where('etudiant_id', $inscription->etudiant_id)
                        ->where(fn ($q) => $q->where('created_at', '<', $inscription->created_at)
                            ->orWhere(fn ($e) => $e->where('created_at', $inscription->created_at)
                                ->where('id', '<', $inscription->id)))
                        ->count();

                    $attendu = $rang === 0 ? self::PREMIERE : self::REINSCRIPTION;

                    if ((string) $inscription->type_inscription !== $attendu) {
                        $lignes[] = [
                            'inscription_id' => $inscription->id,
                            'etudiant_id' => $inscription->etudiant_id,
                            'declare' => $inscription->type_inscription,
                            'rang' => $rang + 1,
                            'attendu_par_le_rang' => $attendu,
                        ];
                    }
                }

                return true;
            });

        return $lignes;
    }

    /**
     * Ramene les valeurs hors enum sur la valeur accentuee equivalente.
     *
     * On ne touche QUE ce qui est hors enum. Un type valide reste tel quel,
     * meme s'il contredit le rang : ce serait reecrire une decision de l'ecole.
     */
    public function executer(bool $appliquer = false): array
    {
        $correspondances = [
            'reinscription' => self::REINSCRIPTION,
            'reinscription ' => self::REINSCRIPTION,
            'Reinscription' => self::REINSCRIPTION,
            'RÉINSCRIPTION' => self::REINSCRIPTION,
            'premiere_inscription' => self::PREMIERE,
            'Premiere_inscription' => self::PREMIERE,
            'PREMIÈRE_INSCRIPTION' => self::PREMIERE,
            '' => self::PREMIERE,
        ];

        $plan = [];

        foreach ($correspondances as $avant => $apres) {
            $total = ESBTPInscription::query()->where('type_inscription', $avant)->count();

            if ($total > 0) {
                $plan[] = ['de' => $avant === '' ? '(vide)' : $avant, 'vers' => $apres, 'lignes' => $total];
            }
        }

        $nuls = ESBTPInscription::query()->whereNull('type_inscription')->count();

        if ($nuls > 0) {
            $plan[] = ['de' => '(null)', 'vers' => self::PREMIERE, 'lignes' => $nuls];
        }

        $total = array_sum(array_column($plan, 'lignes'));

        if (! $appliquer || $total === 0) {
            return ['plan' => $plan, 'total' => $total, 'applique' => false];
        }

        DB::transaction(function () use ($correspondances): void {
            foreach ($correspondances as $avant => $apres) {
                ESBTPInscription::query()
                    ->where('type_inscription', $avant)
                    ->update(['type_inscription' => $apres]);
            }

            ESBTPInscription::query()
                ->whereNull('type_inscription')
                ->update(['type_inscription' => self::PREMIERE]);
        });

        Log::warning('[inscriptions] type_inscription normalise', ['lignes' => $total, 'plan' => $plan]);

        return ['plan' => $plan, 'total' => $total, 'applique' => true];
    }
}
