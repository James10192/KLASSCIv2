<?php

namespace App\Models;

use App\Enums\EtatPieceDossier;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

/**
 * Un dépôt : ce qu'un étudiant a remis une fois, au guichet.
 *
 * Ancré sur l'étudiant. Ce qu'une année en prend vit dans
 * {@see ESBTPInscriptionPiece}.
 */
class ESBTPPieceDeposee extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'esbtp_pieces_deposees';

    protected $fillable = [
        'etudiant_id',
        'piece_dossier_id',
        'inscription_id',
        'quantite_deposee',
        'etat',
        'motif',
        'decidee_par',
        'decidee_at',
        'date_delivrance',
        'date_depot',
        'document_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'etat' => EtatPieceDossier::class,
        'quantite_deposee' => 'integer',
        'decidee_at' => 'datetime',
        'date_delivrance' => 'date',
        'date_depot' => 'date',
    ];

    /**
     * Filet de dernier recours, pas la validation que voit un utilisateur.
     *
     * Une exception levée depuis `saving` remonte en 500 : un refus sans motif
     * donnerait une page blanche là où il faut un 422 et un message. La
     * validation d'écran vit dans un FormRequest ; ceci rattrape ce qui passe
     * par le modèle sans écran. Et ce qui ne passe même pas par le modèle — les
     * écritures de masse — est rattrapé par la contrainte en base.
     */
    protected static function booted(): void
    {
        static::saving(function (self $ligne) {
            // Un état absent vaut ATTENDUE, avant tout contrôle. Sans cette
            // ligne, la valeur par défaut de la migration serait inatteignable :
            // `create(['etudiant_id' => 1, 'piece_dossier_id' => 1])` arrive ici
            // avec un état nul, et le contrôle accuserait « valeur inconnue » là
            // où il n'y a pas de valeur du tout.
            $etat = $ligne->etat instanceof EtatPieceDossier
                ? $ligne->etat
                : EtatPieceDossier::tryFromLibre((string) ($ligne->etat ?? EtatPieceDossier::ATTENDUE->value));

            if ($etat === null) {
                throw new RuntimeException('Etat de piece inconnu : « '.((string) $ligne->etat).' »');
            }

            $ligne->etat = $etat;

            if ($etat->exigeUnMotif() && trim((string) $ligne->motif) === '') {
                throw new RuntimeException('Un refus doit dire pourquoi : le motif est obligatoire.');
            }
        });
    }

    public function etudiant(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function piece(): BelongsTo
    {
        return $this->belongsTo(ESBTPPieceDossier::class, 'piece_dossier_id');
    }

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(ESBTPInscription::class, 'inscription_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ESBTPEtudiantDocument::class, 'document_id');
    }

    public function decideur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidee_par');
    }

    /**
     * La date à partir de laquelle la validité court.
     *
     * La délivrance d'abord : un extrait délivré en 2019 et remis en 2026 est
     * déjà périmé sous une validité de trois mois. Le dépôt n'est qu'un repli
     * quand l'école n'a pas noté la délivrance.
     */
    public function depuisQuand(): ?CarbonInterface
    {
        return $this->date_delivrance ?? $this->date_depot ?? $this->created_at;
    }

    /**
     * Ce dépôt est-il périmé au regard de la durée de validité de sa pièce ?
     *
     * Une pièce sans durée (`duree_validite_mois` nul) ne périme jamais — c'est
     * le sens du nul, et le remplacer par zéro périmerait tout à l'instant du
     * dépôt.
     */
    public function estPerime(?ESBTPPieceDossier $piece = null): bool
    {
        $piece = $piece ?? $this->piece;
        $mois = $piece?->duree_validite_mois;

        if ($mois === null || $mois <= 0) {
            return false;
        }

        $depart = $this->depuisQuand();

        if ($depart === null) {
            return false;
        }

        return $depart->copy()->addMonths($mois)->isPast();
    }

    /** Ce dépôt alimente-t-il le stock disponible ? */
    public function compteDansLeStock(?ESBTPPieceDossier $piece = null): bool
    {
        return $this->etat instanceof EtatPieceDossier
            && $this->etat->estSoldee()
            && ! $this->estPerime($piece);
    }

    public function scopeSoldees(Builder $query): Builder
    {
        return $query->where('etat', EtatPieceDossier::VALIDEE->value);
    }

    public function scopePourEtudiant(Builder $query, int $etudiantId): Builder
    {
        return $query->where('etudiant_id', $etudiantId);
    }
}
