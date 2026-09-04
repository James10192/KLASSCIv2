<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/**
 * Une piece exigee au dossier d'inscription (catalogue).
 *
 * ATTENTION — ce modele appartient au Lot 1. Il est ecrit ici au strict minimum
 * pour que le Lot 2 ait une cible a referencer. Si le Lot 1 livre le sien, c'est
 * le sien qui fait foi.
 *
 * Une ligne sans filiere ni niveau est le defaut commun a l'ecole. Une ligne qui
 * porte un perimetre remplace ce defaut pour le meme code.
 */
class ESBTPPieceDossier extends Model
{
    use SoftDeletes;

    protected $table = 'esbtp_pieces_dossier';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'is_obligatoire',
        'filiere_id',
        'niveau_id',
        'ordre',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_obligatoire' => 'boolean',
        'is_active' => 'boolean',
        'ordre' => 'integer',
    ];

    public function filiere()
    {
        return $this->belongsTo(ESBTPFiliere::class, 'filiere_id');
    }

    public function niveau()
    {
        return $this->belongsTo(ESBTPNiveauEtude::class, 'niveau_id');
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Toutes les lignes qui peuvent concerner un couple filiere/niveau : celles
     * du perimetre exact, celles des perimetres plus larges, et le defaut de
     * l'ecole. Le classement par precision est fait en PHP (resoudreParCode),
     * pour qu'il reste lisible et verifiable sans base.
     */
    public function scopeCandidatesPour(Builder $query, ?int $filiereId, ?int $niveauId): Builder
    {
        return $query
            ->where(function (Builder $q) use ($filiereId) {
                $q->whereNull('filiere_id');
                if ($filiereId !== null) {
                    $q->orWhere('filiere_id', $filiereId);
                }
            })
            ->where(function (Builder $q) use ($niveauId) {
                $q->whereNull('niveau_id');
                if ($niveauId !== null) {
                    $q->orWhere('niveau_id', $niveauId);
                }
            });
    }

    /**
     * Resout le catalogue applicable : une seule piece par code, la plus precise.
     *
     * @return \Illuminate\Support\Collection<int, static>
     */
    public static function applicablesPour(?int $filiereId, ?int $niveauId): Collection
    {
        return static::resoudreParCode(
            static::query()->actives()->candidatesPour($filiereId, $niveauId)->get()
        );
    }

    /**
     * Ne garde qu'une ligne par code : la plus precise.
     *
     * Ordre de precision : filiere + niveau, puis filiere seule, puis niveau
     * seul, puis le defaut de l'ecole. La filiere prime sur le niveau parce
     * qu'elle decrit le dossier attendu ("un transfert n'apporte pas les memes
     * pieces"), la ou le niveau ne fait que graduer.
     *
     * Le resultat est trie par `ordre` puis `id` : c'est l'ordre d'affichage
     * du dossier, celui dans lequel le secretariat coche.
     *
     * @param  Collection<int, static>  $candidates
     * @return Collection<int, static>
     */
    public static function resoudreParCode(Collection $candidates): Collection
    {
        return $candidates
            // Cle composite plutot qu'un sortBy multi-criteres : dans cette
            // version de Laravel, une closure passee en tableau a sortBy est
            // traitee comme un COMPARATEUR, pas comme un extracteur de cle.
            ->sortBy(fn (self $piece) => sprintf(
                '%d-%06d-%012d',
                9 - static::precision($piece),   // le plus precis d'abord
                $piece->ordre ?? 0,
                $piece->id ?? 0
            ))
            ->unique('code')
            ->sortBy(fn (self $piece) => sprintf(
                '%06d-%012d',                    // ordre d'affichage du dossier
                $piece->ordre ?? 0,
                $piece->id ?? 0
            ))
            ->values();
    }

    /** Plus le score est haut, plus la ligne est specifique. */
    private static function precision(self $piece): int
    {
        return ($piece->filiere_id !== null ? 2 : 0)
            + ($piece->niveau_id !== null ? 1 : 0);
    }
}
