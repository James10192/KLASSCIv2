<?php

namespace App\Console\Commands;

use App\Models\ESBTPPaiement;
use App\Models\ESBTPPaiementAllocation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Liste les versements dont la repartition ne boucle pas.
 *
 * Le calcul par categorie repose sur un invariant muet : un versement qui porte
 * des allocations est lu PAR elles, et plus du tout par sa propre categorie. Si
 * leur somme ne fait pas le montant, la difference sort des totaux sans erreur
 * ni trace — c'est exactement le mode de defaillance que la repartition existait
 * pour corriger.
 *
 * Cette commande NE CORRIGE RIEN. Elle regarde. Ce qu'elle trouve se repare en
 * rejouant la repartition (`reset`), pas en modifiant des lignes a la main.
 */
class VerifierAllocationsPaiements extends Command
{
    protected $signature = 'frais:verifier-allocations
                            {--inscription= : Ne verifier qu\'une inscription}
                            {--annee= : Ne verifier qu\'une annee universitaire}';

    protected $description = 'Liste les versements dont les allocations ne totalisent pas le montant (lecture seule)';

    public function handle(): int
    {
        $nonBouclants = $this->versementsQuiNeBouclentPas();
        $invisibles = $this->allocationsHorsPerimetreDeLecture();

        $this->afficherNonBouclants($nonBouclants);
        $this->newLine();
        $this->afficherInvisibles($invisibles);

        if ($nonBouclants->isEmpty() && $invisibles->isEmpty()) {
            $this->newLine();
            $this->info('Toutes les repartitions bouclent, et aucune allocation n\'est hors perimetre.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('Rejouer la repartition avec `reset` sur les inscriptions concernees recalcule ces lignes.');

        // Un ecart n'est pas un plantage : la commande a fait son travail en le
        // montrant. Elle sort en echec pour qu'une surveillance automatique
        // puisse s'en saisir, sans jamais rien ecrire.
        return self::FAILURE;
    }

    /**
     * Les versements dont les allocations ne totalisent pas le montant.
     */
    private function versementsQuiNeBouclentPas(): \Illuminate\Support\Collection
    {
        return $this->paiementsAvecAllocations()
            ->select([
                'esbtp_paiements.id',
                'esbtp_paiements.numero_recu',
                'esbtp_paiements.inscription_id',
                'esbtp_paiements.montant',
                DB::raw('(SELECT SUM(montant) FROM esbtp_paiement_allocations
                          WHERE esbtp_paiement_allocations.paiement_id = esbtp_paiements.id) as alloue'),
            ])
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'numero_recu' => $p->numero_recu,
                'inscription_id' => $p->inscription_id,
                'montant' => (float) $p->montant,
                'alloue' => (float) $p->alloue,
                'ecart' => round((float) $p->montant - (float) $p->alloue, 2),
            ])
            ->filter(fn ($l) => abs($l['ecart']) > 0.009)
            ->values();
    }

    /**
     * Les allocations que le lecteur ne regardera jamais.
     *
     * netPaidByCategory() ne lit que les versements valides ou en attente, hors
     * reliquat. Une allocation posee sur un versement qui sort de ce perimetre
     * est ecrite, occupe un frais dans le calcul de la repartition, et n'apparait
     * nulle part — c'est le defaut qui avait laisse un reliquat priver un
     * versement reel du frais qu'il devait couvrir.
     */
    private function allocationsHorsPerimetreDeLecture(): \Illuminate\Support\Collection
    {
        $query = ESBTPPaiementAllocation::query()
            ->join('esbtp_paiements', 'esbtp_paiements.id', '=', 'esbtp_paiement_allocations.paiement_id')
            ->where(fn ($q) => $q
                ->where('esbtp_paiements.type_paiement', 'reliquat')
                ->orWhereNotIn('esbtp_paiements.status', ['validé', 'en_attente']));

        $this->restreindreAuPerimetre($query, 'esbtp_paiements');

        return $query
            ->select([
                'esbtp_paiements.id',
                'esbtp_paiements.numero_recu',
                'esbtp_paiements.inscription_id',
                'esbtp_paiements.status',
                'esbtp_paiements.type_paiement',
                DB::raw('SUM(esbtp_paiement_allocations.montant) as alloue'),
            ])
            ->groupBy(
                'esbtp_paiements.id',
                'esbtp_paiements.numero_recu',
                'esbtp_paiements.inscription_id',
                'esbtp_paiements.status',
                'esbtp_paiements.type_paiement'
            )
            ->get();
    }

    private function paiementsAvecAllocations(): \Illuminate\Database\Eloquent\Builder
    {
        $query = ESBTPPaiement::query()
            ->whereExists(fn ($q) => $q
                ->select(DB::raw(1))
                ->from('esbtp_paiement_allocations')
                ->whereColumn('esbtp_paiement_allocations.paiement_id', 'esbtp_paiements.id'));

        $this->restreindreAuPerimetre($query, 'esbtp_paiements');

        return $query;
    }

    private function restreindreAuPerimetre($query, string $table): void
    {
        if ($inscription = $this->option('inscription')) {
            $query->where($table.'.inscription_id', (int) $inscription);
        }

        if ($annee = $this->option('annee')) {
            $query->where($table.'.annee_universitaire_id', (int) $annee);
        }
    }

    private function afficherNonBouclants(\Illuminate\Support\Collection $lignes): void
    {
        if ($lignes->isEmpty()) {
            $this->info('Repartitions qui ne bouclent pas : aucune.');

            return;
        }

        $this->error(sprintf(
            '%d versement(s) dont les allocations ne font pas le montant — %s F sortent des totaux par frais.',
            $lignes->count(),
            number_format($lignes->sum(fn ($l) => abs($l['ecart'])), 2, ',', ' ')
        ));

        $this->table(
            ['Versement', 'Recu', 'Inscription', 'Montant', 'Alloue', 'Ecart'],
            $lignes->map(fn ($l) => [
                $l['id'],
                $l['numero_recu'] ?: '—',
                $l['inscription_id'],
                number_format($l['montant'], 2, ',', ' '),
                number_format($l['alloue'], 2, ',', ' '),
                number_format($l['ecart'], 2, ',', ' '),
            ])->all()
        );
    }

    private function afficherInvisibles(\Illuminate\Support\Collection $lignes): void
    {
        if ($lignes->isEmpty()) {
            $this->info('Allocations hors perimetre de lecture : aucune.');

            return;
        }

        $this->error(sprintf(
            '%d versement(s) portent des allocations que le calcul par frais ne lit jamais.',
            $lignes->count()
        ));

        $this->table(
            ['Versement', 'Recu', 'Inscription', 'Statut', 'Type', 'Alloue'],
            $lignes->map(fn ($p) => [
                $p->id,
                $p->numero_recu ?: '—',
                $p->inscription_id,
                $p->status,
                $p->type_paiement ?: '—',
                number_format((float) $p->alloue, 2, ',', ' '),
            ])->all()
        );
    }
}
