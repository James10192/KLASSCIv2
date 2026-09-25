<?php

namespace App\Services\RendezVous\Renvoi;

use App\Enums\StatutConvocationRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\Portail\ReferencePublique;
use App\Services\RendezVous\FileConvocationsRdv;
use App\Services\Verification\MasqueContact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OwenIt\Auditing\Models\Audit;

/**
 * Remet en file la convocation de reservations PRECISES (au plus 50), par
 * exemple apres la correction de leur adresse. Jamais toute l'ecole.
 *
 * Meme chemin que « Remettre en attente » : FileConvocationsRdv::poser, qui
 * remet a zero les champs de la convocation. Rien ne part ici : la tache
 * planifiee (toutes les 5 minutes) envoie la file. L'etat precedent est garde
 * dans l'audit.
 */
class RenvoiConvocationsCiblees
{
    public function __construct(
        private readonly EligibiliteRenvoi $eligibilite,
        private readonly FileConvocationsRdv $file,
        private readonly ReferencePublique $references,
    ) {}

    /**
     * @param  list<int>  $ids
     * @return list<array{id: int, eligible: bool, raison: ?string, email_masque: ?string, statut_convocation: ?string, dossier_reference_masquee: ?string}>
     */
    public function simuler(array $ids): array
    {
        $reservations = $this->charger($ids);

        return array_map(function (int $id) use ($reservations) {
            $r = $reservations[$id] ?? null;
            $raison = $this->eligibilite->raison($r);

            return [
                'id' => $id,
                'eligible' => $raison === null,
                'raison' => $raison,
                'email_masque' => $r?->email ? MasqueContact::email($r->email) : null,
                'statut_convocation' => $r?->convocation_statut?->value,
                'dossier_reference_masquee' => $r === null ? null : $this->referenceMasquee($r),
            ];
        }, $ids);
    }

    /**
     * @param  list<int>  $ids
     * @return array{remises: int, non_eligibles: list<array{id: int, raison: string}>, a_envoyer: int}
     */
    public function executer(array $ids, string $motif, ?Model $auteur): array
    {
        $rapport = ['remises' => 0, 'non_eligibles' => [], 'a_envoyer' => 0];
        foreach ($ids as $id) {
            $raison = $this->remettre($id, $motif, $auteur);
            $raison === null
                ? $rapport['remises']++
                : $rapport['non_eligibles'][] = ['id' => $id, 'raison' => $raison];
        }
        $rapport['a_envoyer'] = $this->file->enAttente();

        return $rapport;
    }

    /** Sous verrou : l'eligibilite est relue au moment d'ecrire. */
    private function remettre(int $id, string $motif, ?Model $auteur): ?string
    {
        try {
            return DB::transaction(function () use ($id, $motif, $auteur) {
                $r = ESBTPRdvReservation::query()->with(['creneau', 'candidature', 'demande'])->lockForUpdate()->find($id);
                $raison = $this->eligibilite->raison($r);
                if ($raison !== null) {
                    return $raison;
                }

                $avant = $this->etatConvocation($r);
                $this->file->poser($r, $r->convocation_action ?: 'confirme');
                $r->refresh();
                if ($r->convocation_statut !== StatutConvocationRdv::EnAttente) {
                    // poser() a juge l'adresse inutilisable : on n'ecrit rien.
                    throw new RenvoiRefuse(EligibiliteRenvoi::ADRESSE);
                }
                $this->auditer($r, $avant, $motif, $auteur);

                return null;
            });
        } catch (RenvoiRefuse $e) {
            return $e->raison;
        } catch (\Throwable $e) {
            Log::error('Renvoi de convocation : echec', ['reservation_id' => $id, 'erreur' => $e->getMessage()]);

            return 'echec_ecriture';
        }
    }

    /** @return array<string, mixed> */
    private function etatConvocation(ESBTPRdvReservation $r): array
    {
        return [
            'convocation_statut' => $r->convocation_statut?->value,
            'convocation_envoyee_at' => $r->convocation_envoyee_at?->toIso8601String(),
            'convocation_message_id' => $r->convocation_message_id,
            'convocation_delivree_at' => $r->convocation_delivree_at?->toIso8601String(),
            'convocation_code_distant' => $r->convocation_code_distant,
        ];
    }

    private function auditer(ESBTPRdvReservation $r, array $avant, string $motif, ?Model $auteur): void
    {
        Audit::query()->create([
            'user_type' => $auteur?->getMorphClass(),
            'user_id' => $auteur?->getKey(),
            'event' => 'renvoi_convocation',
            'auditable_type' => $r->getMorphClass(),
            'auditable_id' => $r->id,
            'old_values' => $avant,
            'new_values' => ['convocation_statut' => StatutConvocationRdv::EnAttente->value, 'motif' => $motif],
            'url' => request()?->fullUrl(),
            'ip_address' => request()?->ip(),
            'user_agent' => mb_substr((string) request()?->userAgent(), 0, 1023),
            'tags' => 'cli,renvoi_convocation',
        ]);
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, ESBTPRdvReservation>
     */
    private function charger(array $ids): array
    {
        return ESBTPRdvReservation::query()->with(['creneau', 'candidature', 'demande'])->whereIn('id', $ids)->get()->keyBy('id')->all();
    }

    private function referenceMasquee(ESBTPRdvReservation $r): ?string
    {
        $brut = $this->references->normaliser((string) ($r->candidature ?? $r->demande)?->reference_publique);

        return $brut === '' ? null : mb_substr($brut, 0, 2).'**-****-**'.mb_substr($brut, -2);
    }
}
