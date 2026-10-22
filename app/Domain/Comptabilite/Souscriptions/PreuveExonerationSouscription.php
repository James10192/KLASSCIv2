<?php

namespace App\Domain\Comptabilite\Souscriptions;

use App\Domain\Inscriptions\ExonerationInscription;
use App\Models\ESBTPFraisSubscription;
use OwenIt\Auditing\Models\Audit;

/**
 * Même preuve pour l'écran étudiant, la fiche inscription et la CLI.
 * Une reconfirmation est une décision NOUVELLE : ne jamais inventer un auteur
 * ou une date rétroactive. Le contrôle d'écriture est fail-closed.
 */
final class PreuveExonerationSouscription
{
    public function ajuster(ESBTPFraisSubscription $souscription, float $montant, string $motif, int $auteurId): void
    {
        $zero = $montant === 0.0;
        if ($zero && ! config('audit.enabled')) {
            throw new \DomainException("Le journal d'audit est désactivé : exonération non enregistrée.");
        }

        $dernierId = $zero ? (int) Audit::query()
            ->where('auditable_type', ESBTPFraisSubscription::class)
            ->where('auditable_id', $souscription->id)
            ->max('id') : 0;

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

        if ($zero) {
            // L'audit doit être apparu pendant CETTE décision, pas dans
            // l'historique d'une dispense précédente.
            $audit = Audit::query()
                ->where('auditable_type', ESBTPFraisSubscription::class)
                ->where('auditable_id', $souscription->id)
                ->where('event', 'updated')
                ->where('id', '>', $dernierId)
                ->orderByDesc('id')->first();
            if (! $this->preuveValide($souscription, $audit, $auteurId)) {
                throw new \DomainException("La preuve d'audit n'a pas été enregistrée : l'exonération est annulée.");
            }
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
}
