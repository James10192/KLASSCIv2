<?php

namespace App\Services\Frais;

use App\Models\ESBTPFraisConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Retire les configurations de frais qui ne servent a rien, sans en toucher
 * une seule qui porte une decision.
 *
 * `poser-bareme` insere ou met a jour, il ne supprime jamais, et l'ecran ne sait
 * pas retirer une configuration isolee. Deux residus s'accumulent donc :
 *
 * 1. Les DOUBLONS de portee — meme frais, meme filiere ou parcours, meme niveau,
 *    meme annee. Ils ne facturent rien de plus : la recherche ne garde qu'une
 *    ligne par frais. Mais laquelle depend de l'ordre de lecture, si bien que
 *    modifier l'une par l'ecran peut rester sans effet visible. Un doublon n'est
 *    retire que si ses quatre montants sont identiques a ceux du premier : s'ils
 *    different, ce n'est plus un residu mais une contradiction, et elle revient
 *    a l'ecole.
 *
 * 2. Des configurations ENTIEREMENT a zero, designees une par une. Jamais
 *    detectees automatiquement : sur une instance, un tarif configure a zero peut
 *    etre une decision — un etudiant exempte — et non un oubli. Seule la personne
 *    qui sait pourquoi la ligne existe peut la nommer.
 *
 * Dans les deux cas, une configuration qui porte des options ou une regle
 * d'echeancier est laissee en place : les retirer effacerait un parametrage.
 *
 * Retrait doux (SoftDeletes) : tout se restaure. Rien n'est ecrit sans `apply`.
 */
class RetraitConfigurationsInutiles
{
    private const MONTANTS = ['amount', 'amount_affecte', 'amount_reaffecte', 'amount_non_affecte'];

    /**
     * @param  list<int>  $idsNuls
     * @return array{applique: bool, a_retirer: list<array>, refus: list<string>}
     */
    public function executer(bool $appliquer, array $idsNuls = []): array
    {
        $aRetirer = [];
        $refus = [];

        foreach ($this->groupesEnDouble() as $groupe) {
            $this->planifierDoublons($groupe, $aRetirer, $refus);
        }

        foreach (ESBTPFraisConfiguration::whereIn('id', $idsNuls)->get() as $config) {
            $this->planifierZero($config, $aRetirer, $refus);
        }

        if ($appliquer && $aRetirer !== []) {
            DB::transaction(fn () => ESBTPFraisConfiguration::whereIn('id', array_column($aRetirer, 'id'))
                ->get()
                ->each->delete());
        }

        return ['applique' => $appliquer && $aRetirer !== [], 'a_retirer' => $aRetirer, 'refus' => $refus];
    }

    /** @return Collection<int, Collection<int, ESBTPFraisConfiguration>> */
    private function groupesEnDouble(): Collection
    {
        return ESBTPFraisConfiguration::orderBy('id')->get()
            ->groupBy(fn ($c) => implode(':', [
                $c->frais_category_id, $c->systeme_academique, $c->filiere_id,
                $c->parcours_id, $c->niveau_id, $c->annee_universitaire_id,
            ]))
            ->filter(fn ($g) => $g->count() > 1)
            ->values();
    }

    private function planifierDoublons(Collection $groupe, array &$aRetirer, array &$refus): void
    {
        $garde = $groupe->first();

        foreach ($groupe->slice(1) as $doublon) {
            if ($this->montants($doublon) !== $this->montants($garde)) {
                $refus[] = "Configurations {$garde->id} et {$doublon->id} : meme portee, montants differents. A trancher par l'ecole.";
                continue;
            }
            if ($this->portePlusQuUnTarif($doublon)) {
                $refus[] = "Configuration {$doublon->id} : doublon, mais porte des options ou un echeancier.";
                continue;
            }
            $aRetirer[] = $this->ligne($doublon, "doublon de {$garde->id}");
        }
    }

    private function planifierZero(ESBTPFraisConfiguration $config, array &$aRetirer, array &$refus): void
    {
        if (array_sum($this->montants($config)) != 0) {
            $refus[] = "Configuration {$config->id} : un de ses montants n'est pas nul, elle facture quelque chose.";
            return;
        }
        if ($this->portePlusQuUnTarif($config)) {
            $refus[] = "Configuration {$config->id} : a zero, mais porte des options ou un echeancier.";
            return;
        }
        if (! in_array($config->id, array_column($aRetirer, 'id'), true)) {
            $aRetirer[] = $this->ligne($config, 'entierement a zero');
        }
    }

    /** @return list<float> */
    private function montants(ESBTPFraisConfiguration $c): array
    {
        return array_map(fn ($champ) => (float) $c->{$champ}, self::MONTANTS);
    }

    private function portePlusQuUnTarif(ESBTPFraisConfiguration $c): bool
    {
        return $c->options()->exists() || $c->echeancierRules()->exists();
    }

    private function ligne(ESBTPFraisConfiguration $c, string $motif): array
    {
        return [
            'id' => $c->id,
            'frais_category_id' => $c->frais_category_id,
            'parcours_id' => $c->parcours_id,
            'filiere_id' => $c->filiere_id,
            'niveau_id' => $c->niveau_id,
            'montants' => $this->montants($c),
            'motif' => $motif,
        ];
    }
}
