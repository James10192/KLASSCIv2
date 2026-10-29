<?php

namespace App\Services\RendezVous\Renvoi;

use App\Models\ESBTPRdvReservation;
use App\Services\Emails\DomainesSuspects;
use App\Services\Portail\ReferencePublique;
use App\Services\Verification\MasqueContact;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

/**
 * Les reservations ACTIVES dont la convocation est partie, ou partirait, vers
 * une mauvaise adresse :
 * - `adresse_corrigee` : l'adresse a ete corrigee par la correction des
 *   fautes de frappe (audit `correction_faute_email`), apres que la
 *   convocation a pu partir vers l'ancienne ;
 * - `domaine_piege` : l'adresse porte encore une faute CONNUE
 *   (`corrections_connues` des listes partagees). Certains de ces domaines
 *   (`gmai.com`) acceptent le courrier : la convocation est notee « delivree »
 *   alors qu'elle est arrivee chez un tiers.
 *
 * Lecture seule, donnees masquees. Sert a choisir les identifiants a passer
 * a `POST /api/cli/rendez-vous/convocations/renvoyer`.
 */
class AdressesCorrigeesRdv
{
    public const CORRIGEE = 'adresse_corrigee';

    public const PIEGE = 'domaine_piege';

    public function __construct(
        private readonly DomainesSuspects $listes,
        private readonly ReferencePublique $references,
    ) {}

    /** @return list<array<string, mixed>> */
    public function lister(): array
    {
        $corrections = $this->corrections();
        $pieges = array_keys($this->listes->correctionsConnues());

        $lignes = [];
        ESBTPRdvReservation::query()
            ->occupantes()
            ->with(['candidature:id,reference_publique', 'demande:id,reference_publique'])
            ->where(fn ($q) => $q->whereIn('id', $corrections->keys()->all())
                ->orWhereIn(DB::raw("LOWER(TRIM(SUBSTRING_INDEX(email, '@', -1)))"), $pieges))
            ->chunkById(200, function (Collection $lot) use (&$lignes, $corrections) {
                foreach ($lot as $r) {
                    $audit = $corrections->get($r->id);
                    $lignes[] = [
                        'id' => (int) $r->id,
                        'raison' => $audit !== null ? self::CORRIGEE : self::PIEGE,
                        'email_masque' => $r->email ? MasqueContact::email($r->email) : null,
                        'ancien_email_masque' => $audit !== null ? MasqueContact::email((string) ($audit->old_values['email'] ?? '')) : null,
                        'corrigee_le' => $audit?->created_at?->toIso8601String(),
                        'statut_convocation' => $r->convocation_statut?->value,
                        'delivree' => $r->convocation_delivree_at !== null,
                        'dossier_reference_masquee' => $this->referenceMasquee($r),
                    ];
                }
            });

        return $lignes;
    }

    /** @return Collection<int, Audit> la derniere correction de chaque reservation */
    private function corrections(): Collection
    {
        return Audit::query()
            ->where('event', 'correction_faute_email')
            ->where('auditable_type', (new ESBTPRdvReservation)->getMorphClass())
            ->orderBy('id')
            ->get(['id', 'auditable_id', 'old_values', 'created_at'])
            ->keyBy(fn (Audit $a) => (int) $a->auditable_id);
    }

    private function referenceMasquee(ESBTPRdvReservation $r): ?string
    {
        $brut = $this->references->normaliser((string) ($r->candidature ?? $r->demande)?->reference_publique);

        return $brut === '' ? null : mb_substr($brut, 0, 2).'**-****-**'.mb_substr($brut, -2);
    }
}
