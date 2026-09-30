<?php

namespace App\Services\Admissions;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPCandidatureWorkflow;
use Illuminate\Support\Collection;
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

    /** @return Collection<int, ESBTPCandidature> */
    public function cashierQueue(): Collection
    {
        if (! $this->settings->usesManagedWorkflow()) {
            return collect();
        }

        $candidatures = ESBTPCandidature::query()
            ->with(['filiere:id,name,code', 'niveau:id,name', 'anneeUniversitaire:id,name'])
            ->where('statut', ESBTPCandidature::STATUT_ACCEPTEE)
            ->whereNull('inscription_id')
            ->orderBy('traite_at')
            ->orderBy('id')
            ->get();

        $workflows = ESBTPCandidatureWorkflow::query()
            ->whereIn('candidature_id', $candidatures->pluck('id'))
            ->get()
            ->keyBy('candidature_id');

        return $candidatures
            ->filter(function (ESBTPCandidature $candidature) use ($workflows) {
                /** @var ESBTPCandidatureWorkflow|null $workflow */
                $workflow = $workflows->get($candidature->id);

                if ($workflow?->paymentRecorded()) {
                    return false;
                }

                if ($this->settings->mode() === InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE) {
                    return $workflow?->documentsValidated() === true;
                }

                return true;
            })
            ->values();
    }

    public function assertPaymentAllowed(ESBTPCandidatureWorkflow $workflow): void
    {
        if ($this->settings->mode() === InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE
            && ! $workflow->documentsValidated()) {
            throw ValidationException::withMessages([
                'workflow' => "Le controle physique des pieces doit etre valide avant le passage a la caisse.",
            ]);
        }
    }

    public function assertDocumentsAllowed(ESBTPCandidatureWorkflow $workflow): void
    {
        if ($this->settings->mode() === InscriptionWorkflowSettings::MODE_CAISSE_AVANT_PIECES
            && ! $workflow->paymentRecorded()) {
            throw ValidationException::withMessages([
                'workflow' => "Le paiement de preinscription doit etre valide avant le controle physique des pieces.",
            ]);
        }
    }
}
