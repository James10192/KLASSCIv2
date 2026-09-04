<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

/**
 * Ce qu'une inscription prend sur le stock déposé.
 *
 * Rien ici ne décrit l'état d'une pièce : cet état-là vit sur le dépôt
 * ({@see ESBTPPieceDeposee}). Cette table ne dit qu'une chose — combien cette
 * rentrée-là a consommé — plus le seul cas qui soit vraiment annuel : la pièce
 * ne concerne pas cet étudiant cette année.
 */
class ESBTPInscriptionPiece extends Model
{
    use HasFactory;

    protected $table = 'esbtp_inscription_pieces';

    protected $fillable = [
        'etudiant_id',
        'piece_dossier_id',
        'inscription_id',
        'quantite_consommee',
        'non_applicable',
        'motif',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantite_consommee' => 'integer',
        'non_applicable' => 'boolean',
    ];

    /**
     * Filet de dernier recours. La validation d'écran vit dans un FormRequest —
     * une exception levée ici remonterait en 500.
     */
    protected static function booted(): void
    {
        static::saving(function (self $ligne) {
            if ($ligne->non_applicable && trim((string) $ligne->motif) === '') {
                throw new RuntimeException('Une pièce écartée doit dire pourquoi : le motif est obligatoire.');
            }

            // Une pièce écartée ne consomme rien. Le laisser à la charge de
            // l'appelant produirait, un jour, une ligne « non applicable » qui
            // retire deux photos du stock.
            if ($ligne->non_applicable) {
                $ligne->quantite_consommee = 0;
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

    /**
     * Les consommations qui comptent encore.
     *
     * Le disponible ne se décrémente pas : il se calcule, et « retenues » est ce
     * `where`. Selon le réglage `pieces_dossier.restitution_annulation`, une
     * inscription annulée — ou supprimée, donc orpheline — rend ce qu'elle avait
     * pris, ou le garde.
     *
     * Seul « annulee » est écarté. Pas « terminee » : une année achevée a
     * légitimement consommé ses pièces, et les lui rendre gonflerait le stock
     * d'un étudiant à chaque rentrée. Les valeurs de `status` sont celles que
     * pose {@see ESBTPInscription} — en_attente, active, annulee, terminee.
     *
     * `whereHas` écarte aussi les lignes orphelines, dont l'inscription a été
     * supprimée : c'est voulu, une inscription qui n'existe plus rend ce qu'elle
     * avait pris. Quand l'école a choisi de ne rien rendre, aucun filtre ne
     * s'applique et les orphelines continuent de compter.
     */
    public function scopeRetenues(Builder $query, bool $restitueALAnnulation): Builder
    {
        if (! $restitueALAnnulation) {
            return $query;
        }

        return $query->whereHas('inscription', function (Builder $q) {
            $q->where('status', '<>', 'annulee');
        });
    }
}
