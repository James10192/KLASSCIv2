<?php

namespace App\Models;

use App\Contracts\PorteurDeRendezVous;
use App\Enums\CanalConvocationRdv;
use App\Enums\StatutConvocationRdv;
use App\Enums\StatutReservationRdv;
use App\Services\RendezVous\RendezVousReglages;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ESBTPRdvReservation extends Model
{
    protected $table = 'esbtp_rdv_reservations';

    protected $fillable = [
        'creneau_id',
        'candidature_id',
        'reinscription_demande_id',
        'statut',
        'nom',
        'prenoms',
        'telephone',
        'date_naissance',
        'email',
        'libere_at',
        'convocation_statut',
        'convocation_action',
        'convocation_tentatives',
        'convocation_envoyee_at',
        'convocation_erreur',
        'convocation_message_id',
        'convocation_canal',
        'convocation_destination_masquee',
        'convocation_fallback_utilise',
        'accueilli_at',
        'accueilli_par',
        'prevenue_par',
        'convocation_delivree_at',
        'convocation_synchro_at',
        'convocation_code_distant',
    ];

    protected $casts = [
        'statut' => StatutReservationRdv::class,
        'date_naissance' => 'date',
        'libere_at' => 'datetime',
        'convocation_statut' => StatutConvocationRdv::class,
        'convocation_canal' => CanalConvocationRdv::class,
        'convocation_fallback_utilise' => 'boolean',
        'convocation_tentatives' => 'integer',
        'convocation_envoyee_at' => 'datetime',
        'accueilli_at' => 'datetime',
        'convocation_delivree_at' => 'datetime',
        'convocation_synchro_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Garde métier ultime de la fermeture à minuit. Le catalogue retire déjà
        // le jour courant et le scheduler ferme ses créneaux, mais une ancienne
        // page ouverte ou une requête forgée pourrait encore envoyer son ID dans
        // l'intervalle. On refuse donc toute NOUVELLE occupation ou tout
        // DÉPLACEMENT vers aujourd'hui/passé lorsque l'école a activé la règle.
        // Les mises à jour d'un rendez-vous déjà pris (accueil, convocation,
        // statut...) restent possibles : elles ne changent pas creneau_id.
        static::saving(function (self $reservation) {
            if (! $reservation->exists || $reservation->isDirty('creneau_id')) {
                $reglages = app(RendezVousReglages::class);
                if (! $reglages->fermerJourAMinuit()) {
                    return;
                }

                $creneauId = (int) $reservation->creneau_id;
                $date = ESBTPRdvCreneau::query()->whereKey($creneauId)->value('date');
                if ($date !== null && Carbon::parse($date)->startOfDay()->lte(Carbon::today())) {
                    throw ValidationException::withMessages([
                        'creneau_id' => 'La journée de ce créneau est fermée depuis minuit. Choisissez une date future.',
                    ]);
                }
            }
        });
    }

    public function creneau(): BelongsTo
    {
        return $this->belongsTo(ESBTPRdvCreneau::class, 'creneau_id');
    }

    public function accueilliPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accueilli_par');
    }

    public function prevenuePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prevenue_par');
    }

    public function reprogrammations(): HasMany
    {
        return $this->hasMany(ESBTPRdvReprogrammation::class, 'reservation_id');
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(ESBTPCandidature::class, 'candidature_id');
    }

    public function demande(): BelongsTo
    {
        return $this->belongsTo(ESBTPReinscriptionDemande::class, 'reinscription_demande_id');
    }

    public function porteur(): ?PorteurDeRendezVous
    {
        $this->loadMissing(['candidature', 'demande']);

        $porteur = $this->candidature ?? $this->demande;

        return $porteur instanceof PorteurDeRendezVous ? $porteur : null;
    }

    /**
     * Le dossier attend encore la famille. Inscrire un dossier ne libere pas sa
     * reservation, et le rejeter ne libere qu'un creneau pas encore commence
     * (ReservateurRdv::liberer) : sans ce filtre, un candidat inscrit, ou refuse
     * une fois son creneau commence, resterait attendu au guichet.
     */
    public function scopeDossierOuvert(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereHas('candidature', fn ($c) => $c->whereNotIn('statut', ESBTPCandidature::statutsDossierClos()))
            ->orWhereHas('demande', fn ($d) => $d->whereNotIn('statut', ESBTPReinscriptionDemande::statutsDossierClos())));
    }

    public function dossierOuvert(): bool
    {
        $porteur = $this->porteur();

        return $porteur !== null && ! $porteur->dossierClos();
    }

    public function scopeOccupantes(Builder $query): Builder
    {
        return $query->whereIn('statut', StatutReservationRdv::valeursOccupantes());
    }

    public function nomComplet(): string
    {
        return trim($this->nom.' '.$this->prenoms);
    }
}
