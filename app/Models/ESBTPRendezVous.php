<?php

namespace App\Models;

use App\Enums\StatutRendezVous;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Un rendez-vous de guichet, pris depuis le portail public.
 *
 * Voir l'en-tete de la migration pour ce que cette table protege. L'essentiel
 * tient en deux phrases : ses heures sont figees a la reservation, donc un
 * changement d'horaires de l'ecole ne deplace personne ; et l'identite qu'elle
 * porte est un instantane, donc un redepot qui reecrit le dossier parent ne
 * change pas le titulaire du creneau.
 *
 * @property StatutRendezVous $statut
 */
class ESBTPRendezVous extends Model
{
    use HasFactory;

    protected $table = 'esbtp_rendez_vous';

    /**
     * `reference`, `debut_at` et `fin_at` ne sont pas remplissables en masse.
     *
     * Ce ne sont pas des champs de formulaire : la reference est tiree par le
     * serveur, et les heures sont recopiees depuis la grille apres verification
     * de la place restante. Les laisser passer par une affectation de masse
     * ouvrirait la porte a un client qui choisit son heure hors grille, ou qui
     * se donne la reference d'un autre.
     */
    protected $fillable = [
        'reinscription_demande_id',
        'candidature_id',
        'annee_universitaire_id',
        'nom',
        'prenoms',
        'telephone',
        'email',
        'statut',
        'ip_hash',
    ];

    protected $casts = [
        'debut_at' => 'datetime',
        'fin_at' => 'datetime',
        'arrive_at' => 'datetime',
        'termine_at' => 'datetime',
        'annule_at' => 'datetime',
        'statut' => StatutRendezVous::class,
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function demandeReinscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPReinscriptionDemande::class, 'reinscription_demande_id');
    }

    public function candidature(): BelongsTo
    {
        return $this->belongsTo(ESBTPCandidature::class, 'candidature_id');
    }

    public function anneeUniversitaire(): BelongsTo
    {
        return $this->belongsTo(ESBTPAnneeUniversitaire::class, 'annee_universitaire_id');
    }

    public function pointePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pointe_par');
    }

    // -----------------------------------------------------------------
    // Requetes
    // -----------------------------------------------------------------

    /**
     * Les rendez-vous qui retiennent reellement une place.
     *
     * Un annule ou un absent n'en occupe aucune. La liste des statuts concernes
     * vient de l'enum et non d'un `whereIn` recopie : la recopier ici puis dans
     * la liste du jour finirait par donner deux reponses differentes, et l'ecart
     * se verrait sous la forme d'un creneau annonce libre puis refuse.
     */
    public function scopeOccupants(Builder $requete): Builder
    {
        return $requete->whereIn('statut', StatutRendezVous::occupants());
    }

    public function scopePourLaJournee(Builder $requete, Carbon $jour): Builder
    {
        return $requete->whereBetween('debut_at', [
            $jour->copy()->startOfDay(),
            $jour->copy()->endOfDay(),
        ]);
    }

    /**
     * Combien de places sont prises sur chaque creneau d'une journee.
     *
     * Une seule requete groupee plutot qu'une par creneau : une journee en
     * compte une trentaine, et l'ecran de reservation les affiche toutes.
     *
     * @return array<string, int>  cle « Y-m-d H:i », valeur = places prises
     */
    public static function occupationDuJour(Carbon $jour): array
    {
        return static::query()
            ->occupants()
            ->pourLaJournee($jour)
            ->selectRaw('debut_at, COUNT(*) as pris')
            ->groupBy('debut_at')
            ->pluck('pris', 'debut_at')
            ->mapWithKeys(fn ($pris, $debut) => [Carbon::parse($debut)->format('Y-m-d H:i') => (int) $pris])
            ->all();
    }

    // -----------------------------------------------------------------
    // Reference
    // -----------------------------------------------------------------

    /**
     * La reference remise a la famille.
     *
     * Aleatoire, jamais derivee du dossier. Une reference qui se deduirait du
     * matricule ou du numero de candidature se devinerait, et permettrait de
     * consulter — puis d'annuler — le rendez-vous d'un autre.
     *
     * Sans les caracteres que l'on confond en les recopiant sur un bout de
     * papier ou en les dictant au telephone : ni 0 ni O, ni 1 ni I ni L. C'est
     * un code que des familles vont lire a voix haute a un agent.
     */
    public static function genererReference(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $reference = 'RDV-';

        for ($i = 0; $i < 8; $i++) {
            $reference .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $reference;
    }

    /**
     * Normalise une reference saisie par une famille.
     *
     * Elle arrive en minuscules, avec des espaces, parfois sans le prefixe. La
     * refuser pour cela ferait rappeler l'ecole, ce que le rendez-vous est cense
     * eviter.
     */
    public static function normaliserReference(string $saisie): string
    {
        $propre = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $saisie) ?? '');

        return Str::startsWith($propre, 'RDV') ? 'RDV-'.Str::substr($propre, 3) : 'RDV-'.$propre;
    }
}
