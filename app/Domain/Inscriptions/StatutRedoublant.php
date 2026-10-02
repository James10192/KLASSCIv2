<?php

namespace App\Domain\Inscriptions;

use App\Models\ESBTPInscription;
use App\Services\Inscriptions\NormalisationTypeInscription;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Le statut « redoublant » d'une inscription : ce que le logiciel en déduit,
 * ce qu'une personne en a confirmé.
 *
 * Le logiciel déduit (même niveau d'étude que l'année d'avant, règle unique de
 * {@see ESBTPInscription::estUnRedoublement()}). Une personne habilitée confirme
 * la valeur telle quelle, ou la corrige en disant pourquoi. Une valeur
 * confirmée ou corrigée n'est plus jamais réécrite par une déduction, sauf si
 * l'inscription change de niveau : la confirmation portait sur l'ancien.
 *
 * Tant que personne n'a confirmé, le bulletin imprime la valeur déduite et la
 * génération des bulletins prévient (décision de l'établissement, octobre 2026).
 */
class StatutRedoublant
{
    public const PERMISSION = 'inscriptions.redoublant.confirm';

    public const SOURCE_DEDUIT = 'deduit';

    public const SOURCE_CONFIRME = 'confirme';

    public const SOURCE_CORRIGE = 'corrige';

    /** Une correction se justifie, comme une correction de note. */
    public const MOTIF_MINIMUM = 10;

    public const DECISIONS = ['passage', 'redoublement', 'rattrapage'];

    /** Ce que le logiciel conclut des inscriptions de l'étudiant. */
    public function deduire(ESBTPInscription $inscription): bool
    {
        $annee = $inscription->anneeUniversitaire;

        if ($annee === null) {
            return false;
        }

        return ESBTPInscription::estUnRedoublement(
            ESBTPInscription::precedantAnnee((int) $inscription->etudiant_id, $annee),
            $inscription->niveau_id !== null ? (int) $inscription->niveau_id : null,
        );
    }

    public function estEtabliParUnePersonne(ESBTPInscription $inscription): bool
    {
        return in_array($inscription->redoublant_source, [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE], true);
    }

    /**
     * Une inscription attend une confirmation quand la question se pose
     * vraiment : l'étudiant était déjà là (réinscription), vient d'ailleurs
     * (transfert, il a pu redoubler chez l'autre), ou le logiciel le dit
     * redoublant. Un nouvel étudiant qui arrive de rien ne redouble pas ici :
     * le confirmer un par un ne serait que du bruit.
     */
    public function aConfirmer(ESBTPInscription $inscription): bool
    {
        if ($this->estEtabliParUnePersonne($inscription)) {
            return false;
        }

        return $inscription->type_inscription === NormalisationTypeInscription::REINSCRIPTION
            || (bool) $inscription->est_transfert
            || (bool) $inscription->is_redoublant;
    }

    /** Le même tri que {@see aConfirmer()}, en requête. */
    public static function contraindreAConfirmer(Builder $requete): Builder
    {
        $table = $requete->getModel()->getTable();

        return $requete
            ->where(fn ($q) => $q->whereNull("{$table}.redoublant_source")
                ->orWhereNotIn("{$table}.redoublant_source", [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE]))
            ->where(fn ($q) => $q->where("{$table}.type_inscription", NormalisationTypeInscription::REINSCRIPTION)
                ->orWhere("{$table}.est_transfert", true)
                ->orWhere("{$table}.is_redoublant", true));
    }

    /**
     * Pose la valeur déduite, sans toucher à ce qu'une personne a établi.
     * `$valeur` permet à l'appelant qui vient de la calculer de ne pas la
     * recalculer.
     */
    public function poserDeduit(ESBTPInscription $inscription, ?bool $valeur = null): void
    {
        if ($this->estEtabliParUnePersonne($inscription)) {
            return;
        }

        $inscription->forceFill([
            'is_redoublant' => $valeur ?? $this->deduire($inscription),
            'redoublant_source' => self::SOURCE_DEDUIT,
        ])->save();
    }

    /**
     * Le niveau a changé : la confirmation portait sur l'ancien. On repart de
     * la déduction, et l'inscription repasse « à confirmer ».
     */
    public function reouvrir(ESBTPInscription $inscription): void
    {
        $inscription->forceFill([
            'is_redoublant' => $this->deduire($inscription),
            'redoublant_source' => self::SOURCE_DEDUIT,
            'redoublant_confirme_par' => null,
            'redoublant_confirme_le' => null,
            'redoublant_motif' => null,
        ])->save();
    }

    /**
     * Une personne établit le statut. La même valeur que la déduction est une
     * confirmation ; une autre est une correction. Changer la valeur déjà
     * enregistrée exige un motif.
     *
     * @throws ValidationException
     */
    public function etablir(ESBTPInscription $inscription, User $personne, bool $valeur, ?string $motif = null): void
    {
        $motif = trim((string) $motif);
        $change = (bool) $inscription->is_redoublant !== $valeur;

        if ($change && mb_strlen($motif) < self::MOTIF_MINIMUM) {
            throw ValidationException::withMessages([
                'motif' => 'Dites en quelques mots pourquoi le statut change (au moins '.self::MOTIF_MINIMUM.' caractères).',
            ]);
        }

        $source = $valeur === $this->deduire($inscription) ? self::SOURCE_CONFIRME : self::SOURCE_CORRIGE;

        $inscription->forceFill([
            'is_redoublant' => $valeur,
            'redoublant_source' => $source,
            'redoublant_confirme_par' => $personne->id,
            'redoublant_confirme_le' => now(),
            'redoublant_motif' => $motif !== '' ? $motif : ($change ? null : $inscription->redoublant_motif),
        ])->save();
    }

    /**
     * Ce qui mérite un second regard : la décision de réinscription dit l'un,
     * la classe choisie dit l'autre. Le logiciel ne tranche pas, il le montre.
     */
    public function incoherence(ESBTPInscription $inscription): ?string
    {
        if ($this->estEtabliParUnePersonne($inscription) || ! $inscription->decision_reinscription) {
            return null;
        }

        $decideRedoublement = $inscription->decision_reinscription === 'redoublement';

        if ($decideRedoublement === (bool) $inscription->is_redoublant) {
            return null;
        }

        return $decideRedoublement
            ? 'La décision de réinscription est « redoublement », mais la classe choisie est d\'un autre niveau que l\'an dernier.'
            : 'La classe choisie est du même niveau que l\'an dernier, mais la décision de réinscription est « '.$inscription->decision_reinscription.' ».';
    }

    /**
     * Ce que l'écran montre : la valeur, d'où elle vient, et s'il reste
     * quelque chose à faire.
     *
     * @return array{valeur: bool, etat: string, libelle: string, detail: ?string, motif: ?string, incoherence: ?string, a_confirmer: bool}
     */
    public function pourAffichage(ESBTPInscription $inscription): array
    {
        $valeur = (bool) $inscription->is_redoublant;
        $aConfirmer = $this->aConfirmer($inscription);
        $etat = $inscription->redoublant_source === self::SOURCE_CORRIGE ? 'corrige'
            : ($inscription->redoublant_source === self::SOURCE_CONFIRME ? 'confirme'
            : ($aConfirmer ? 'a_confirmer' : 'deduit'));

        $qui = $inscription->redoublantConfirmePar?->name;
        $quand = $inscription->redoublant_confirme_le?->format('d/m/Y');
        $detail = match ($etat) {
            'confirme' => trim('Confirmé'.($qui ? ' par '.$qui : '').($quand ? ' le '.$quand : '')),
            'corrige' => trim('Corrigé'.($qui ? ' par '.$qui : '').($quand ? ' le '.$quand : '')),
            'a_confirmer' => 'Déduit du niveau de l\'an dernier, à confirmer.',
            default => 'Première inscription dans l\'établissement.',
        };

        return [
            'valeur' => $valeur,
            'etat' => $etat,
            'libelle' => $valeur ? 'Redoublant' : 'Non redoublant',
            'detail' => $detail,
            'motif' => $inscription->redoublant_motif,
            'incoherence' => $this->incoherence($inscription),
            'a_confirmer' => $aConfirmer,
        ];
    }

    /** La décision lue en tête du texte de réinscription (« Redoublement - … »). */
    public static function decisionDepuisObservations(?string $observations): ?string
    {
        if ($observations === null) {
            return null;
        }

        // Première ligne seulement : la réinscription groupée ajoute « [BATCH …] » dessous.
        $premiereLigne = strtok(trim($observations), "\n") ?: '';
        $tete = mb_strtolower(trim(explode(' - ', $premiereLigne, 2)[0]));

        return in_array($tete, self::DECISIONS, true) ? $tete : null;
    }

    /**
     * Pose la valeur déduite sur toutes les inscriptions qu'aucune personne n'a
     * établies, toutes années confondues, et reprend la décision de
     * réinscription depuis son texte. Rien de ce qu'une personne a confirmé ou
     * corrigé n'est réécrit. Les inscriptions d'une année sans date de début
     * restent sans statut : on ne sait pas quelle année les précède.
     *
     * @return array{examinees:int, redoublants:int, a_poser:int, changees:int, decisions:int, indeterminees:int, etablies:int, ecrit:bool}
     */
    public function recenser(bool $ecrire = false): array
    {
        $lignes = DB::table('esbtp_inscriptions as i')
            ->leftJoin('esbtp_annee_universitaires as a', 'a.id', '=', 'i.annee_universitaire_id')
            ->whereNull('i.deleted_at')
            ->orderBy('i.etudiant_id')
            ->get([
                'i.id', 'i.etudiant_id', 'i.niveau_id', 'i.annee_universitaire_id', 'a.start_date',
                'i.is_redoublant', 'i.redoublant_source', 'i.decision_reinscription', 'i.reinscription_observations',
            ]);

        $bilan = ['examinees' => $lignes->count(), 'redoublants' => 0, 'a_poser' => 0, 'changees' => 0,
            'decisions' => 0, 'indeterminees' => 0, 'etablies' => 0, 'ecrit' => $ecrire];
        $aPoser = [true => [], false => []];
        $decisions = [];

        foreach ($lignes->groupBy('etudiant_id') as $inscriptions) {
            foreach ($inscriptions as $ligne) {
                $decision = $ligne->decision_reinscription ?: self::decisionDepuisObservations($ligne->reinscription_observations);
                if ($decision !== null && $ligne->decision_reinscription === null) {
                    $decisions[$decision][] = $ligne->id;
                }

                if (in_array($ligne->redoublant_source, [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE], true)) {
                    $bilan['etablies']++;
                    $bilan['redoublants'] += (int) $ligne->is_redoublant;

                    continue;
                }

                if ($ligne->start_date === null) {
                    $bilan['indeterminees']++;

                    continue;
                }

                $valeur = $this->deduireDansLeLot($ligne, $inscriptions);
                $bilan['redoublants'] += (int) $valeur;

                if ($ligne->redoublant_source !== self::SOURCE_DEDUIT || (bool) $ligne->is_redoublant !== $valeur) {
                    $aPoser[$valeur][] = $ligne->id;
                    $bilan['a_poser']++;
                    $bilan['changees'] += (int) ((bool) $ligne->is_redoublant !== $valeur);
                }
            }
        }

        $bilan['decisions'] = array_sum(array_map('count', $decisions));

        if ($ecrire) {
            $this->ecrireLeRecensement($aPoser, $decisions);
            Log::info('Statut redoublant recensé', $bilan);
        }

        return $bilan;
    }

    /**
     * Même règle que {@see ESBTPInscription::precedantAnnee()}, appliquée aux
     * inscriptions déjà chargées de l'étudiant : l'inscription d'une autre année
     * commencée avant celle-ci, la plus récente, à identifiant égal la dernière.
     */
    private function deduireDansLeLot(object $ligne, $inscriptions): bool
    {
        $precedente = $inscriptions
            ->filter(fn ($autre) => $autre->annee_universitaire_id !== $ligne->annee_universitaire_id
                && $autre->start_date !== null
                && $autre->start_date < $ligne->start_date)
            ->sortBy([['start_date', 'desc'], ['id', 'desc']])
            ->first();

        if ($precedente === null || $precedente->niveau_id === null || $ligne->niveau_id === null) {
            return false;
        }

        return (int) $precedente->niveau_id === (int) $ligne->niveau_id;
    }

    /**
     * @param  array<int, array<int, int>>  $aPoser  valeur => ids
     * @param  array<string, array<int, int>>  $decisions  décision => ids
     */
    private function ecrireLeRecensement(array $aPoser, array $decisions): void
    {
        DB::transaction(function () use ($aPoser, $decisions) {
            foreach ($aPoser as $valeur => $ids) {
                foreach (array_chunk($ids, 500) as $lot) {
                    DB::table('esbtp_inscriptions')->whereIn('id', $lot)
                        ->whereNotIn(DB::raw("COALESCE(redoublant_source, '')"), [self::SOURCE_CONFIRME, self::SOURCE_CORRIGE])
                        ->update(['is_redoublant' => (bool) $valeur, 'redoublant_source' => self::SOURCE_DEDUIT]);
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
