<?php

namespace App\Domain\Inscriptions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pose le statut redoublant déduit sur toutes les inscriptions qu'aucune
 * personne n'a établies, toutes années confondues, et reprend la décision de
 * réinscription depuis son texte.
 *
 * Rien de ce qu'une personne a confirmé ou corrigé n'est réécrit, y compris si
 * elle tranche pendant le recensement (garde dans la requête d'écriture). Les
 * inscriptions d'une année sans date de début restent sans statut : on ne sait
 * pas quelle année les précède. Rejouable : une seconde course n'écrit rien.
 */
class RecensementDesRedoublants
{
    private const ETABLIES = [StatutRedoublant::SOURCE_CONFIRME, StatutRedoublant::SOURCE_CORRIGE];

    /** @var array<string, true> les transférés qui déclarent recommencer, par « étudiant:année » */
    private array $declares = [];

    /**
     * @return array{examinees:int, redoublants:int, a_poser:int, changees:int, decisions:int, indeterminees:int, etablies:int, ecrit:bool}
     */
    public function executer(bool $ecrire = false): array
    {
        $lignes = DB::table('esbtp_inscriptions as i')
            ->leftJoin('esbtp_annee_universitaires as a', 'a.id', '=', 'i.annee_universitaire_id')
            ->whereNull('i.deleted_at')
            ->get([
                'i.id', 'i.etudiant_id', 'i.niveau_id', 'i.annee_universitaire_id', 'a.start_date',
                'i.is_redoublant', 'i.redoublant_source', 'i.decision_reinscription', 'i.reinscription_observations',
            ]);

        $bilan = ['examinees' => $lignes->count(), 'redoublants' => 0, 'a_poser' => 0, 'changees' => 0,
            'decisions' => 0, 'indeterminees' => 0, 'etablies' => 0, 'ecrit' => $ecrire];
        $aPoser = [1 => [], 0 => []];
        $decisions = [];
        $this->declares = app(DeclarationDuTransfere::class)->couples();

        foreach ($lignes->groupBy('etudiant_id') as $inscriptions) {
            foreach ($inscriptions as $ligne) {
                $decision = $ligne->decision_reinscription === null
                    ? StatutRedoublant::decisionDepuisObservations($ligne->reinscription_observations)
                    : null;
                if ($decision !== null) {
                    $decisions[$decision][] = $ligne->id;
                }

                $this->examiner($ligne, $inscriptions, $bilan, $aPoser);
            }
        }

        $bilan['decisions'] = array_sum(array_map('count', $decisions));

        if ($ecrire) {
            $this->ecrire($aPoser, $decisions);
            Log::info('Statut redoublant recensé', $bilan);
        }

        return $bilan;
    }

    /** @param  array<int, array<int, int>>  $aPoser */
    private function examiner(object $ligne, Collection $inscriptions, array &$bilan, array &$aPoser): void
    {
        if (in_array($ligne->redoublant_source, self::ETABLIES, true)) {
            $bilan['etablies']++;
            $bilan['redoublants'] += (int) $ligne->is_redoublant;

            return;
        }

        if ($ligne->start_date === null) {
            $bilan['indeterminees']++;

            return;
        }

        $valeur = $this->deduire($ligne, $inscriptions);
        $bilan['redoublants'] += (int) $valeur;

        if ($ligne->redoublant_source !== StatutRedoublant::SOURCE_DEDUIT || (bool) $ligne->is_redoublant !== $valeur) {
            $aPoser[(int) $valeur][] = $ligne->id;
            $bilan['a_poser']++;
            $bilan['changees'] += (int) ((bool) $ligne->is_redoublant !== $valeur);
        }
    }

    /**
     * Même règle que {@see StatutRedoublant::deduire()}, déclaration du transféré comprise,
     * et que {@see \App\Models\ESBTPInscription::precedantAnnee()},
     * appliquée aux inscriptions déjà chargées de l'étudiant : l'inscription
     * d'une autre année commencée avant celle-ci, la plus récente, à date égale
     * la dernière créée.
     */
    private function deduire(object $ligne, Collection $inscriptions): bool
    {
        $precedente = $inscriptions
            ->filter(fn ($autre) => $autre->annee_universitaire_id !== $ligne->annee_universitaire_id
                && $autre->start_date !== null
                && $autre->start_date < $ligne->start_date)
            ->sortBy([['start_date', 'desc'], ['id', 'desc']])
            ->first();

        if ($precedente === null) {
            return isset($this->declares[$ligne->etudiant_id.':'.$ligne->annee_universitaire_id]);
        }

        if ($precedente->niveau_id === null || $ligne->niveau_id === null) {
            return false;
        }

        return (int) $precedente->niveau_id === (int) $ligne->niveau_id;
    }

    /**
     * @param  array<int, array<int, int>>  $aPoser  valeur (1|0) => ids
     * @param  array<string, array<int, int>>  $decisions  décision => ids
     */
    private function ecrire(array $aPoser, array $decisions): void
    {
        DB::transaction(function () use ($aPoser, $decisions) {
            foreach ($aPoser as $valeur => $ids) {
                foreach (array_chunk($ids, 500) as $lot) {
                    DB::table('esbtp_inscriptions')->whereIn('id', $lot)
                        ->whereNotIn(DB::raw("COALESCE(redoublant_source, '')"), self::ETABLIES)
                        ->update(['is_redoublant' => (bool) $valeur, 'redoublant_source' => StatutRedoublant::SOURCE_DEDUIT]);
                }
            }

            foreach ($decisions as $decision => $ids) {
                foreach (array_chunk($ids, 500) as $lot) {
                    DB::table('esbtp_inscriptions')->whereIn('id', $lot)->whereNull('decision_reinscription')
                        ->update(['decision_reinscription' => $decision]);
                }
            }
        });
    }
}
