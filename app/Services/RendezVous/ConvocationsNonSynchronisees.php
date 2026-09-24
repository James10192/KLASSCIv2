<?php

namespace App\Services\RendezVous;

use App\Enums\StatutConvocationRdv;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Pourquoi une convocation « envoyee » n'a jamais ete relue chez MailPulse.
 *
 * `a_synchroniser` : la synchronisation la relira (meme predicat qu'elle,
 * SynchroStatutsConvocations::selectionnables, meme date de coupure). Ce
 * detail ne compte que le perimetre des rendez-vous (PerimetreRdv), alors que
 * la synchronisation, elle, relit toutes les convocations de la fenetre, quelle
 * que soit leur annee. Les autres raisons sont des
 * impasses que la synchronisation ne peut pas lever :
 * - `sans_identifiant` : aucun identifiant MailPulse. C'est le cas des
 *   reservations d'avant le suivi des convocations (migration du 22/09), que
 *   cette migration a passees en « envoyee » d'apres `rdv_invite_at`, sans
 *   identifiant ni date d'envoi ;
 * - `sans_date_envoi` : identifiant, mais pas de date d'envoi ;
 * - `hors_fenetre` : envoyee il y a plus de FENETRE_JOURS jours ;
 * - `code_neutre` : verdict neutre definitif deja note ;
 * - `autre` : le reste, qui devrait valoir 0 : sinon le diagnostic et la
 *   synchronisation ont diverge. Une valeur negative est journalisee, pas masquee.
 *
 * Compteurs seulement.
 */
class ConvocationsNonSynchronisees
{
    public function __construct(private readonly PerimetreRdv $perimetre) {}

    /** @return array{a_synchroniser: int, sans_identifiant: int, sans_date_envoi: int, hors_fenetre: int, code_neutre: int, autre: int} */
    public function detail(): array
    {
        $jamaisRelues = fn (): Builder => $this->perimetre->reservations()
            ->where('convocation_statut', StatutConvocationRdv::Envoyee->value)
            ->whereNull('convocation_synchro_at');
        $avecIdentifiant = fn (): Builder => $jamaisRelues()->whereNotNull('convocation_message_id');
        $limite = now()->subDays(SynchroStatutsConvocations::FENETRE_JOURS);

        $detail = [
            'a_synchroniser' => SynchroStatutsConvocations::selectionnables($jamaisRelues(), $limite)->count(),
            'sans_identifiant' => $jamaisRelues()->whereNull('convocation_message_id')->count(),
            'sans_date_envoi' => $avecIdentifiant()->whereNull('convocation_envoyee_at')->count(),
            'hors_fenetre' => $avecIdentifiant()->where('convocation_envoyee_at', '<', $limite)->count(),
            'code_neutre' => $avecIdentifiant()->where('convocation_envoyee_at', '>=', $limite)
                ->whereIn('convocation_code_distant', SynchroStatutsConvocations::NEUTRES_DEFINITIFS)->count(),
        ];
        $detail['autre'] = $jamaisRelues()->count() - array_sum($detail);
        if ($detail['autre'] < 0) {
            // Des categories qui se recouvrent : les predicats ont diverge.
            Log::warning('Diagnostic convocations : categories non exclusives', $detail);
        }

        return $detail;
    }
}
