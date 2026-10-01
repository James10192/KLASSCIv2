<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * Garde l'ordre des etapes physiques du workflow configurable.
 *
 * Le service met la meme regle derriere la file de caisse ET les actions : un
 * lien direct ne peut donc pas contourner l'ordre choisi par l'etablissement.
 */
final class ManagedInscriptionSequence
{
    public function __construct(private readonly InscriptionWorkflowSettings $settings)
    {
    }

    /**
     * Dossiers acceptés qui attendent la caisse, filtrés en base et paginés :
     * la file reste rapide avec des milliers de candidatures.
     */
    public function cashierQueue(?string $recherche = null, int $parPage = 30): ?LengthAwarePaginator
    {
        if (! $this->settings->usesManagedWorkflow()) {
            return null;
        }

        $piecesAvant = $this->settings->mode() === InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE;

        return ESBTPCandidature::query()
            ->with(['filiere:id,name,code', 'niveau:id,name', 'anneeUniversitaire:id,name'])
            ->where('statut', ESBTPCandidature::STATUT_ACCEPTEE)
            ->whereNull('inscription_id')
            ->whereDoesntHave('managedWorkflow', fn (Builder $w) => $w->whereNotNull('paid_at'))
            ->when($piecesAvant, fn (Builder $q) => $q->whereHas(
                'managedWorkflow',
                fn (Builder $w) => $w->whereNotNull('documents_validated_at')
            ))
            ->when($recherche !== null && trim($recherche) !== '', function (Builder $q) use ($recherche) {
                $terme = '%'.trim($recherche).'%';
                $q->where(fn (Builder $s) => $s
                    ->where('nom', 'like', $terme)
                    ->orWhere('prenoms', 'like', $terme)
                    ->orWhere('telephone', 'like', $terme)
                    ->orWhere('reference_publique', 'like', $terme));
            })
            ->orderBy('traite_at')
            ->orderBy('id')
            ->paginate($parPage)
            ->withQueryString();
    }

    public function assertPaymentAllowed(ESBTPCandidatureWorkflow $workflow): void
    {
        if ($this->settings->mode() === InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE
            && ! $workflow->documentsValidated()) {
            throw ValidationException::withMessages([
                'workflow' => 'Le contrôle physique des pièces doit être validé avant le passage à la caisse.',
            ]);
        }
    }

    public function assertDocumentsAllowed(ESBTPCandidatureWorkflow $workflow): void
    {
        if ($this->settings->mode() === InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES
            && ! $workflow->paymentRecorded()) {
            throw ValidationException::withMessages([
                'workflow' => 'Le paiement de préinscription doit être validé avant le contrôle physique des pièces.',
            ]);
        }
    }
}
