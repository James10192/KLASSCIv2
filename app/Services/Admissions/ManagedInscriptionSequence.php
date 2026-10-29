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

    public const ETAPE_CAISSE = 'caisse';
    public const ETAPE_PIECES = 'pieces';
    public const ETAPE_SUITE = 'suite';
    public const ETAPE_TOUS = 'tous';

    /** Dossiers qui attendent la caisse (raccourci de dossiers()). */
    public function cashierQueue(?string $recherche = null, int $parPage = 30): ?LengthAwarePaginator
    {
        return $this->dossiers(self::ETAPE_CAISSE, $recherche, $parPage);
    }

    /**
     * Dossiers en cours (acceptés, pas encore inscrits), filtrés en base par
     * étape et paginés : chaque guichet retrouve SES dossiers, y compris ceux
     * qui ont déjà franchi la caisse.
     */
    public function dossiers(string $etape, ?string $recherche = null, int $parPage = 30): ?LengthAwarePaginator
    {
        if (! $this->settings->usesManagedWorkflow()) {
            return null;
        }

        $piecesAvant = $this->settings->mode() === InscriptionWorkflowSettings::MODE_PIECES_AVANT_CAISSE;
        $paye = fn (Builder $w) => $w->whereNotNull('paid_at');
        $piecesOk = fn (Builder $w) => $w->whereNotNull('documents_validated_at');

        return ESBTPCandidature::query()
            ->with(['filiere:id,name,code', 'niveau:id,name', 'anneeUniversitaire:id,name', 'managedWorkflow'])
            ->where('statut', ESBTPCandidature::STATUT_ACCEPTEE)
            ->whereNull('inscription_id')
            ->when($etape === self::ETAPE_CAISSE, fn (Builder $q) => $q
                ->whereDoesntHave('managedWorkflow', $paye)
                ->when($piecesAvant, fn (Builder $q) => $q->whereHas('managedWorkflow', $piecesOk)))
            ->when($etape === self::ETAPE_PIECES, fn (Builder $q) => $q
                ->whereDoesntHave('managedWorkflow', $piecesOk)
                ->when(! $piecesAvant, fn (Builder $q) => $q->whereHas('managedWorkflow', $paye)))
            ->when($etape === self::ETAPE_SUITE, fn (Builder $q) => $q
                ->whereHas('managedWorkflow', fn (Builder $w) => $w->whereNotNull('paid_at')->whereNotNull('documents_validated_at')))
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
