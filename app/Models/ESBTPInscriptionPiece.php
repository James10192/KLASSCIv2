<?php

namespace App\Models;

use App\Enums\EtatPieceDossier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Où en est une pièce, pour une inscription donnée.
 *
 * Une ligne par pièce et par inscription. L'inscription portant l'année
 * universitaire, la reprise annuelle des pièces se lit d'elle-même : trois
 * années d'études, trois lignes « extrait de naissance ».
 *
 * Le geste normal est la coche : l'agent regarde le dossier papier qu'on lui
 * tend et dit ce qu'il a reçu. Aucun fichier n'est exigé, ni ici ni plus tard.
 *
 * Ce lot ne crée aucune ligne ici. Le modèle existe pour que la règle du motif
 * soit posée au même endroit que la donnée, et non réinventée par le premier
 * écran qui écrira dedans.
 */
class ESBTPInscriptionPiece extends Model
{
    use HasFactory;

    protected $table = 'esbtp_inscription_pieces';

    protected $fillable = [
        'inscription_id',
        'piece_dossier_id',
        'etat',
        'motif',
        'exemplaires_recus',
        'decidee_par',
        'decidee_at',
        'fichier_chemin',
    ];

    protected $casts = [
        'etat' => EtatPieceDossier::class,
        'exemplaires_recus' => 'integer',
        'decidee_at' => 'datetime',
    ];

    /**
     * Un refus sans motif écrit produit un dossier que personne ne peut
     * débloquer : ni l'étudiant, qui ignore ce qu'on lui reproche, ni le
     * collègue qui reprendra le guichet demain.
     *
     * La règle est tenue ici plutôt que dans un écran, parce qu'un écran ne
     * couvre que lui-même : une commande, un import ou une reprise de données
     * contourneraient la règle sans le savoir.
     */
    protected static function booted(): void
    {
        static::saving(function (self $ligne) {
            $etat = $ligne->etat instanceof EtatPieceDossier
                ? $ligne->etat
                : EtatPieceDossier::tryFrom((string) $ligne->etat);

            if ($etat === null) {
                throw new RuntimeException(
                    "Etat de piece inconnu : « " . (string) $ligne->etat . " »."
                );
            }

            if ($etat->exigeUnMotif() && trim((string) $ligne->motif) === '') {
                throw new RuntimeException(
                    'Un refus doit dire pourquoi : le motif est obligatoire.'
                );
            }
        });
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function pieceDossier(): BelongsTo
    {
        // Sans withTrashed(), une pièce retirée du catalogue rendrait muettes
        // toutes les lignes d'historique qui la citent : le dossier afficherait
        // des états sans savoir de quelle pièce il parle.
        return $this->belongsTo(ESBTPPieceDossier::class, 'piece_dossier_id')->withTrashed();
    }

    public function decidePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidee_par');
    }

    /** Les pièces qui restent à traiter : ni validées, ni écartées. */
    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->whereIn('etat', [
            EtatPieceDossier::ATTENDUE->value,
            EtatPieceDossier::DEPOSEE->value,
            EtatPieceDossier::REFUSEE->value,
        ]);
    }

    public function estSoldee(): bool
    {
        return $this->etat instanceof EtatPieceDossier && $this->etat->estSoldee();
    }

    /**
     * Exemplaires encore attendus, face à ce que le catalogue réclame.
     *
     * Le compte, pas la coche : un étudiant qui a apporté une photo sur deux
     * n'a ni tout donné ni rien donné, et c'est la seule façon de le dire.
     */
    public function exemplairesManquants(int $exemplairesAttendus): int
    {
        return max(0, $exemplairesAttendus - (int) $this->exemplaires_recus);
    }
}
