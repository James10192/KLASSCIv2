<?php

namespace App\Domain\Comptabilite\Souscriptions;

use App\Domain\Inscriptions\ExonerationInscription;
use App\Models\ESBTPFraisSubscription;
use App\Models\ESBTPPaiement;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Même preuve pour l'écran étudiant, la fiche inscription et la CLI.
 * Une reconfirmation est une décision NOUVELLE : ne jamais inventer un auteur
 * ou une date rétroactive. Le contrôle d'écriture est fail-closed.
 */
final class PreuveExonerationSouscription
{
    public function ajuster(ESBTPFraisSubscription $souscription, float $montant, string $motif, int $auteurId): void
    {
        if (! config('audit.enabled')) {
            throw new \DomainException("Le journal d'audit est désactivé : ajustement financier non enregistré.");
        }

        // Même si un futur appelant oublie sa transaction, aucun montant
        // modifié ne peut survivre à une preuve d'audit manquante.
        DB::transaction(function () use ($souscription, $montant, $motif, $auteurId): void {
            $dernierId = (int) Audit::query()
                ->where('auditable_type', ESBTPFraisSubscription::class)
                ->where('auditable_id', $souscription->id)->max('id');
            $this->ecrireSouscription($souscription, $montant, $motif, $auteurId);
            $this->verifierNouvelAudit($souscription, $montant, $motif, $auteurId, $dernierId);
        });
    }

    private function ecrireSouscription(
        ESBTPFraisSubscription $souscription, float $montant, string $motif, int $auteurId
    ): void {
        $zero = $montant === 0.0;
        $trace = sprintf(
            '[%s] Montant dû %s → %s FCFA (utilisateur #%d) : %s',
            now()->format('Y-m-d H:i'),
            number_format((float) $souscription->amount, 0, ',', ' '),
            number_format($montant, 0, ',', ' '),
            $auteurId,
            $motif
        );
        $souscription->exonerationExplicite = $zero;
        $souscription->update([
            'amount' => $montant,
            'notes' => trim(($souscription->notes ? $souscription->notes."\n" : '')
                . ($zero ? ExonerationInscription::TRACE.' ' : '').$trace),
        ]);
    }

    private function verifierNouvelAudit(
        ESBTPFraisSubscription $souscription, float $montant, string $motif, int $auteurId, int $dernierId
    ): void {
        $audit = Audit::query()
            ->where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $souscription->id)
            ->where('event', 'updated')
            ->where('id', '>', $dernierId)
            ->orderByDesc('id')->first();
        $valeurs = $audit?->new_values;
        $notesAudit = is_array($valeurs) ? ($valeurs['notes'] ?? null) : null;
        $preuve = $montant === 0.0
            ? $this->preuveValide($souscription, $audit, $auteurId)
            : ($audit && (int) $audit->user_id === $auteurId
                && is_string($notesAudit)
                && str_contains($notesAudit, 'utilisateur #'.$auteurId)
                && str_contains($notesAudit, $motif));
        if (! $preuve) {
            throw new \DomainException("La preuve d'audit de l'ajustement n'a pas été enregistrée : modification annulée.");
        }
    }

    public function preuveValide(ESBTPFraisSubscription $souscription, ?Audit $audit, ?int $auteurAttendu = null): bool
    {
        if (! $audit
            || (int) $audit->auditable_id !== (int) $souscription->id
            || $audit->auditable_type !== ESBTPFraisSubscription::class
            || $audit->event !== 'updated'
            || (float) $souscription->amount !== 0.0
            || ! str_contains((string) $audit->tags, ESBTPFraisSubscription::TAG_EXONERATION)
            || ! $audit->user_id
            || ($auteurAttendu !== null && (int) $audit->user_id !== $auteurAttendu)) {
            return false;
        }

        $valeurs = $audit->new_values;
        $notesAuditees = is_array($valeurs) ? ($valeurs['notes'] ?? null) : null;
        $signature = 'utilisateur #'.(int) $audit->user_id;
        return is_string($notesAuditees)
            && str_contains($notesAuditees, ExonerationInscription::TRACE)
            && str_contains($notesAuditees, $signature)
            && str_contains((string) $souscription->notes, ExonerationInscription::TRACE)
            && str_contains((string) $souscription->notes, $signature)
            && (! array_key_exists('amount', $valeurs)
                || (float) $valeurs['amount'] === 0.0);
    }
    /**
     * Un zéro historique, un paiement ou un montant retouché est une décision
     * qui ne doit jamais disparaître lors d'un recalcul automatique BTS/LMD.
     */
    public function estProtegee(ESBTPFraisSubscription $souscription): bool
    {
        if ((float) $souscription->amount === 0.0
            || str_contains((string) $souscription->notes, ExonerationInscription::TRACE)
            || ESBTPPaiement::query()
                ->where('inscription_id', $souscription->inscription_id)
                ->where('frais_category_id', $souscription->frais_category_id)->exists()) {
            return true;
        }

        // Un motif humain legacy sans audit est également une décision à
        // préserver ; les préfixes standard de génération sont techniques.
        $notes = trim((string) $souscription->notes);
        if ($notes !== '' && ! preg_match('/(?:créé automatiquement|Regenerate automatically|Régénér[ée] automatiquement|Régénération des frais obligatoires)/iu', $notes)) {
            return true;
        }

        // Anciennes remises partielles sans tag : chercher l'audit de montant
        // non identifié comme réalignement technique, sans attribuer de motif.
        return Audit::query()
            ->where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $souscription->id)
            ->where('event', 'updated')
            ->where('new_values', 'like', '%"amount"%')
            ->where(function ($query) {
                $query->whereNull('tags')
                    ->orWhere('tags', 'not like', '%'.ESBTPFraisSubscription::TAG_REALIGNEMENT.'%');
            })
            ->exists();
    }

}
