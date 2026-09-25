<?php

namespace App\Services\RendezVous;

use App\Domain\Notifications\PhoneNormalizer;
use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Enums\StatutWhatsappRdv;
use App\Models\ESBTPRdvReservation;
use App\Services\MailPulse\MailPulseClient;
use App\Services\MailPulse\MailPulseResult;
use App\Services\MailPulse\MailPulseTenantContext;
use App\Support\ColonnesDeployees;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * La convocation par WhatsApp, pour les familles que le courriel n'a pas
 * atteintes (pas d'adresse, ou envoi refuse), et seulement apres leur accord.
 *
 * KLASSCI ne tient pas le consentement : il remet a MailPulse la convocation
 * ET le texte de la demande d'accord. MailPulse demande l'accord, garde le
 * document, l'envoie sur OUI, l'abandonne sur NON, STOP ou 48 h sans reponse,
 * et rend compte par le callback signe (SuiviWhatsappConvocationRdv).
 *
 * La famille reste sur la liste d'appel tant que la convocation n'est pas
 * remise (FamillesAPrevenirRdv) : un accord qui ne vient pas ne doit pas la
 * faire oublier.
 */
class RelaisWhatsappConvocationRdv
{
    /** La cle d'operation que MailPulse renvoie dans chaque evenement. */
    public const OPERATION = 'rdv.convocation';

    /** 48 h : au-dela, la demande est abandonnee et la famille revient a l'appel. */
    public const EXPIRATION_SECONDES = 172800;

    /** Une demande restee « en file » sans reponse de MailPulse (processus coupe) se rejoue avec la meme cle. */
    private const REPRISE_APRES_MINUTES = 10;

    private const LEGENDE_MAX = 1024;

    private const TEXTE_MAX = 1024;

    /** Un refus qui arrete tout le lot : c'est la configuration, pas la famille. */
    private const BLOQUANTS = ['disabled', 'missing_external_application_credentials', 'auth_failed', 'endpoint_not_found', 'invalid_payload'];

    /** Une panne de transport : la ligne se rejoue plus tard, avec la meme cle d'idempotence. */
    private const PASSAGERS = ['connection_failed', 'rate_limited', 'provider_unavailable'];

    public function __construct(
        private readonly RendezVousReglages $reglages,
        private readonly MailPulseClient $mailpulse,
        private readonly CourrielConvocationRdv $courriel,
        private readonly ConvocationRdvPdf $pdf,
    ) {
    }

    /** Le relais est-il propose ? Reglage d'instance, et colonnes deployees. */
    public function actif(): bool
    {
        return $this->reglages->whatsappRelais() && self::deploye();
    }

    public static function deploye(): bool
    {
        return ColonnesDeployees::existe('esbtp_rdv_reservations', 'whatsapp_statut');
    }

    /**
     * Une famille a qui l'on peut proposer la convocation par WhatsApp : rendez-vous
     * a venir, courriel absent ou refuse, mobile lisible, et rien de deja en cours
     * sur WhatsApp. Un refus ou un silence de 48 h ne se redemande pas d'ici :
     * c'est la famille qui a repondu, l'appel prend le relais.
     */
    public function eligible(ESBTPRdvReservation $r): bool
    {
        return $r->statut === StatutReservationRdv::Confirmee
            && in_array($r->convocation_statut, [StatutConvocationRdv::SansEmail, StatutConvocationRdv::Echec], true)
            && $r->creneau !== null && ! $r->creneau->aCommence()
            && $r->dossierOuvert()
            && $this->reprenable($r)
            && PhoneNormalizer::toE164((string) $r->telephone) !== null;
    }

    public function compterEligibles(): int
    {
        return $this->actif() ? $this->eligibles()->count() : 0;
    }

    /**
     * Les familles dont la convocation passe par WhatsApp, rendez-vous a venir :
     * pour les badges de l'ecran.
     *
     * @return list<array{nom: string, quand: string, libelle: string, ton: string, detail: ?string}>
     */
    public function suivi(int $limite = 50): array
    {
        if (! self::deploye()) {
            return [];
        }

        return ESBTPRdvReservation::query()
            ->select('esbtp_rdv_reservations.*')
            ->join('esbtp_rdv_creneaux as c', 'c.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->whereNotNull('esbtp_rdv_reservations.whatsapp_statut')
            ->where('esbtp_rdv_reservations.statut', StatutReservationRdv::Confirmee->value)
            ->whereDate('c.date', '>=', Carbon::today()->toDateString())
            ->with('creneau')
            ->orderBy('c.date')->orderBy('c.heure_debut')
            ->limit($limite)
            ->get()
            ->map(fn (ESBTPRdvReservation $r) => [
                'nom' => $r->nomComplet(),
                'quand' => ucfirst($r->creneau->date->translatedFormat('D j M')).' '.$r->creneau->heureDebutHi(),
                'libelle' => $r->whatsapp_statut->label(),
                'ton' => $r->whatsapp_statut->ton(),
                'detail' => $r->whatsapp_erreur,
            ])->all();
    }

    /**
     * Un paquet borne dans le temps, comme l'envoi des courriels : l'ecran
     * rappelle tant qu'il en reste.
     *
     * @return array{demandees: int, envoyees: int, refusees: int, echecs: int, restantes: int, bloque: ?string}
     */
    public function envoyerUnPaquet(int $agentId, int $maximum = 10, float $budgetSecondes = 20.0): array
    {
        $rapport = ['demandees' => 0, 'envoyees' => 0, 'refusees' => 0, 'echecs' => 0, 'restantes' => 0, 'bloque' => null];
        if (! $this->actif()) {
            $rapport['bloque'] = 'Le relais WhatsApp est désactivé dans les réglages.';

            return $rapport;
        }

        $debut = microtime(true);
        foreach ($this->eligibles()->take($maximum) as $reservation) {
            if (microtime(true) - $debut >= $budgetSecondes) {
                break;
            }

            $issue = $this->proposer($reservation, $agentId);
            if ($issue['bloque'] !== null) {
                $rapport['bloque'] = $issue['bloque'];
                break;
            }
            if ($issue['compte'] !== null) {
                $rapport[$issue['compte']]++;
            }
        }

        $rapport['restantes'] = $this->compterEligibles();

        return $rapport;
    }

    /**
     * La commande remise a MailPulse pour cette famille.
     *
     * @return array<string, mixed>
     */
    public function commande(ESBTPRdvReservation $r, string $idempotencyKey): array
    {
        $donnees = $this->courriel->donneesConvocation($r, 'confirme');
        $legende = 'Convocation — '.$donnees['schoolName']."\n".CourrielConvocationRdv::resumeCourt($donnees);

        return [
            'operation_key' => self::OPERATION,
            'channel' => 'whatsapp',
            'recipient' => ['type' => 'phone', 'value' => (string) PhoneNormalizer::toE164((string) $r->telephone)],
            'content' => [
                'type' => 'document',
                'url' => $this->pdf->url($r),
                'filename' => $this->nomDeFichier((string) $donnees['reference'], $r->id),
                'mimeType' => 'application/pdf',
                'caption' => $this->borner($legende, self::LEGENDE_MAX),
            ],
            'consent' => [
                'request' => ['text' => $this->borner($this->texteAccord($r, $donnees), self::TEXTE_MAX)],
                'expiresInSeconds' => self::EXPIRATION_SECONDES,
            ],
            'metadata' => ['idempotency_key' => $idempotencyKey],
        ];
    }

    /** @param  array<string, mixed>  $donnees */
    public function texteAccord(ESBTPRdvReservation $r, array $donnees): string
    {
        $creneau = $r->creneau;

        return strtr($this->reglages->texteAccordWhatsapp(), [
            '{ecole}' => (string) $donnees['schoolName'],
            '{candidat}' => $r->nomComplet(),
            '{date}' => $creneau?->date?->translatedFormat('l j F Y') ?? '',
            '{heure}' => $creneau?->heureDebutHi() ?? '',
            '{reference}' => (string) $donnees['reference'],
        ]);
    }

    /**
     * @return array{compte: ?string, bloque: ?string}
     */
    private function proposer(ESBTPRdvReservation $reservation, int $agentId): array
    {
        // Le verrou de la ligne, le temps de poser « en file » et la cle : deux
        // onglets qui envoient en meme temps ne proposent pas deux fois.
        $cle = DB::transaction(function () use ($reservation, $agentId) {
            $r = ESBTPRdvReservation::query()->with(['creneau', 'candidature', 'demande'])->whereKey($reservation->id)->lockForUpdate()->first();
            if ($r === null || ! $this->eligible($r)) {
                return null;
            }

            // Meme tentative tant que MailPulse n'a pas tranche : la cle d'idempotence
            // reste la meme, et un double envoi est deduplique chez lui.
            $nouvelle = $r->whatsapp_idempotency_key === null || $r->whatsapp_statut === StatutWhatsappRdv::Echec;
            $tentative = $nouvelle ? $r->whatsapp_tentative + 1 : $r->whatsapp_tentative;
            $r->forceFill([
                'whatsapp_statut' => StatutWhatsappRdv::Demandee,
                'whatsapp_tentative' => $tentative,
                'whatsapp_idempotency_key' => $nouvelle ? $this->cle($r->id, $tentative) : $r->whatsapp_idempotency_key,
                'whatsapp_demandee_at' => now(),
                'whatsapp_demandee_par' => $agentId,
                'whatsapp_erreur' => null,
                'whatsapp_operation_id' => $nouvelle ? null : $r->whatsapp_operation_id,
            ])->save();

            return $r->whatsapp_idempotency_key;
        });

        if ($cle === null) {
            return ['compte' => null, 'bloque' => null];
        }

        $reservation = ESBTPRdvReservation::query()->with(['creneau', 'candidature', 'demande'])->find($reservation->id);
        $url = $this->pdf->url($reservation);
        if (! str_starts_with($url, 'https://')) {
            $this->remettre($reservation, null);

            return ['compte' => null, 'bloque' => 'Le lien de la convocation n\'est pas en HTTPS (APP_URL) : WhatsApp ne peut pas l\'ouvrir.'];
        }

        $resultat = $this->mailpulse->sendExternalApplicationCommand($this->commande($reservation, $cle), $cle);

        return $this->consigner($reservation, $resultat);
    }

    /**
     * @return array{compte: ?string, bloque: ?string}
     */
    private function consigner(ESBTPRdvReservation $r, MailPulseResult $resultat): array
    {
        Log::info('Convocation rdv WhatsApp remise a MailPulse', [
            'reservation_id' => $r->id,
            'status' => $resultat->status,
            'http_status' => $resultat->httpStatus,
            'operation_id' => $resultat->id,
        ]);

        if ($resultat->status === MailPulseClient::CONSENT_PENDING) {
            $r->forceFill(['whatsapp_operation_id' => $resultat->id])->save();

            return ['compte' => 'demandees', 'bloque' => null];
        }

        if ($resultat->status === MailPulseClient::CONSENT_REFUSED) {
            $r->forceFill([
                'whatsapp_statut' => StatutWhatsappRdv::Refusee,
                'whatsapp_erreur' => 'La famille a refusé WhatsApp (NON ou STOP).',
            ])->save();

            return ['compte' => 'refusees', 'bloque' => null];
        }

        if ($resultat->ok) {
            // Accord deja donne : MailPulse envoie tout de suite, les accuses suivront.
            $r->forceFill([
                'whatsapp_statut' => StatutWhatsappRdv::Accordee,
                'whatsapp_accord_at' => $r->whatsapp_accord_at ?? now(),
                'whatsapp_operation_id' => $resultat->id,
            ])->save();

            return ['compte' => 'envoyees', 'bloque' => null];
        }

        if (in_array($resultat->status, self::BLOQUANTS, true)) {
            $this->remettre($r, null);

            return ['compte' => null, 'bloque' => $resultat->message ?? 'MailPulse a refusé la commande.'];
        }

        if (in_array($resultat->status, self::PASSAGERS, true) || $resultat->status === 'dispatch_conflict') {
            // La cle est gardee : le prochain envoi rejoue la meme commande.
            $this->remettre($r, $resultat->message);

            return ['compte' => null, 'bloque' => $resultat->message ?? 'MailPulse est indisponible, réessayez plus tard.'];
        }

        $r->forceFill([
            'whatsapp_statut' => StatutWhatsappRdv::Echec,
            'whatsapp_erreur' => mb_substr(($resultat->message ?? 'Envoi refusé').($resultat->errorCode ? ' ('.$resultat->errorCode.')' : ''), 0, 255),
        ])->save();

        return ['compte' => 'echecs', 'bloque' => null];
    }

    /** Rien n'est parti : la ligne redevient proposable, sa cle est conservee. */
    private function remettre(ESBTPRdvReservation $r, ?string $erreur): void
    {
        $r->forceFill(['whatsapp_statut' => null, 'whatsapp_erreur' => $erreur === null ? null : mb_substr($erreur, 0, 255)])->save();
    }

    /** Aucun relais en cours, un echec technique a rejouer, ou une demande restee en file. */
    private function reprenable(ESBTPRdvReservation $r): bool
    {
        return match ($r->whatsapp_statut) {
            null, StatutWhatsappRdv::Echec => true,
            StatutWhatsappRdv::Demandee => $r->whatsapp_operation_id === null
                && $r->whatsapp_demandee_at !== null
                && $r->whatsapp_demandee_at->lt(now()->subMinutes(self::REPRISE_APRES_MINUTES)),
            default => false,
        };
    }

    /** @return Collection<int, ESBTPRdvReservation> */
    private function eligibles(): Collection
    {
        return $this->requete()
            ->with(['creneau', 'candidature', 'demande'])
            ->get()
            ->filter(fn (ESBTPRdvReservation $r) => $this->eligible($r))
            ->values();
    }

    private function requete(): Builder
    {
        $maintenant = Carbon::now();

        return ESBTPRdvReservation::query()
            ->select('esbtp_rdv_reservations.*')
            ->join('esbtp_rdv_creneaux as c', 'c.id', '=', 'esbtp_rdv_reservations.creneau_id')
            ->where('esbtp_rdv_reservations.statut', StatutReservationRdv::Confirmee->value)
            ->dossierOuvert()
            ->whereIn('esbtp_rdv_reservations.convocation_statut', [StatutConvocationRdv::SansEmail->value, StatutConvocationRdv::Echec->value])
            ->where(fn (Builder $q) => $q->whereNull('esbtp_rdv_reservations.whatsapp_statut')
                ->orWhereIn('esbtp_rdv_reservations.whatsapp_statut', [StatutWhatsappRdv::Echec->value, StatutWhatsappRdv::Demandee->value]))
            ->where(function (Builder $q) use ($maintenant) {
                $q->whereDate('c.date', '>', $maintenant->toDateString())
                    ->orWhere(fn (Builder $j) => $j->whereDate('c.date', $maintenant->toDateString())
                        ->where('c.heure_debut', '>', $maintenant->format('H:i:s')));
            })
            ->orderBy('c.date')->orderBy('c.heure_debut')->orderBy('esbtp_rdv_reservations.id');
    }

    private function cle(int $reservationId, int $tentative): string
    {
        return MailPulseTenantContext::scopedIdentifier('rdv-convocation-'.$reservationId.'-'.$tentative);
    }

    /** `convocation-<reference>.pdf` : sans separateur de chemin, 120 caracteres au plus. */
    private function nomDeFichier(string $reference, int $id): string
    {
        $propre = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $reference), '-.');

        return 'convocation-'.mb_substr($propre !== '' ? $propre : (string) $id, 0, 100).'.pdf';
    }

    private function borner(string $texte, int $maximum): string
    {
        return mb_strlen($texte) <= $maximum ? $texte : mb_substr($texte, 0, $maximum - 1).'…';
    }
}
