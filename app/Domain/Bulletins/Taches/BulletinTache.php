<?php

namespace App\Domain\Bulletins\Taches;

use App\Models\ESBTPClasse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Un travail long sur les bulletins, suivi en base.
 *
 * Deux types : la génération d'une classe entière, et le PDF groupé (aperçu ou
 * téléchargement). La liste des éléments est figée au lancement ; `position`
 * dit combien sont déjà traités. Une tranche écrit sa progression avant la
 * suivante : un processus tué ne perd que la tranche en cours, et la reprise
 * repart de `position`.
 *
 * @property int $id
 * @property string $type
 * @property int $user_id
 * @property string $statut
 * @property array $elements
 * @property array|null $parametres
 * @property array|null $resultat
 * @property int $total
 * @property int $position
 */
class BulletinTache extends Model
{
    public const TYPE_GENERATION = 'generation';

    public const TYPE_EXPORT = 'export';

    public const EN_ATTENTE = 'en_attente';

    public const EN_COURS = 'en_cours';

    public const TERMINEE = 'terminee';

    public const ECHOUEE = 'echouee';

    public const ACTIFS = [self::EN_ATTENTE, self::EN_COURS];

    public const FINAUX = [self::TERMINEE, self::ECHOUEE];

    /**
     * Durée de conservation d'un PDF groupé produit en arrière-plan.
     *
     * Plus longue que celle des exports suivis à l'écran (une heure) : le lien
     * part par e-mail, et la personne peut l'ouvrir le lendemain. Assez courte
     * pour ne pas remplir le disque d'un hébergement mutualisé.
     */
    public const CONSERVATION_HEURES = 72;

    public const TABLE_ABONNES = 'esbtp_bulletin_tache_abonnes';

    protected $table = 'esbtp_bulletin_taches';

    protected $guarded = ['id'];

    protected $casts = [
        'parametres' => 'array',
        'elements' => 'array',
        'resultat' => 'array',
        'total' => 'integer',
        'position' => 'integer',
        'reprises' => 'integer',
        'position_essayee' => 'integer',
        'essais_position' => 'integer',
        'cloche_at' => 'datetime',
        'demarree_at' => 'datetime',
        'terminee_at' => 'datetime',
        'notifiee_at' => 'datetime',
        'vue_at' => 'datetime',
        'email_envoye_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(ESBTPClasse::class, 'classe_id');
    }

    public function scopeActives(Builder $query): Builder
    {
        return $query->whereIn('statut', self::ACTIFS);
    }

    /** Terminées ou échouées, que leur demandeur n'a pas encore vues. */
    public function scopeNonVues(Builder $query): Builder
    {
        return $query->whereIn('statut', self::FINAUX)->whereNull('vue_at');
    }

    /** Le demandeur, ou une personne qui a rejoint la tâche en relançant le même travail. */
    public function concerne(int $userId): bool
    {
        return (int) $this->user_id === $userId
            || DB::table(self::TABLE_ABONNES)->where('tache_id', $this->id)->where('user_id', $userId)->exists();
    }

    public function abonner(int $userId): void
    {
        if ((int) $this->user_id === $userId) {
            return;
        }

        DB::table(self::TABLE_ABONNES)->insertOrIgnore([
            'tache_id' => $this->id,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Le toast de cette personne ne la rejouera plus. Chacun a sa propre
     * marque : le demandeur sur la tâche, les autres sur leur abonnement.
     */
    public function marquerVuePar(int $userId): void
    {
        if (! $this->estFinale()) {
            return;
        }

        if ((int) $this->user_id === $userId) {
            if ($this->vue_at === null) {
                $this->forceFill(['vue_at' => now()])->save();
            }

            return;
        }

        DB::table(self::TABLE_ABONNES)->where('tache_id', $this->id)->where('user_id', $userId)
            ->whereNull('vue_at')->update(['vue_at' => now(), 'updated_at' => now()]);
    }

    /**
     * Qui prévenir, et si chacun a déjà vu la fin.
     *
     * @return Collection<int, array{user: User, vue: bool}>
     */
    public function destinataires(): Collection
    {
        $abonnes = DB::table(self::TABLE_ABONNES)->where('tache_id', $this->id)->get(['user_id', 'vue_at']);
        $users = User::whereIn('id', $abonnes->pluck('user_id')->push($this->user_id)->all())->get()->keyBy('id');

        return collect([['user' => $users->get($this->user_id), 'vue' => $this->vue_at !== null]])
            ->concat($abonnes->map(fn ($a) => ['user' => $users->get($a->user_id), 'vue' => $a->vue_at !== null]))
            ->filter(fn (array $d) => $d['user'] !== null)
            ->values();
    }

    public function estFinale(): bool
    {
        return in_array($this->statut, self::FINAUX, true);
    }

    public function parametre(string $cle, mixed $defaut = null): mixed
    {
        return ($this->parametres ?? [])[$cle] ?? $defaut;
    }

    public function libelle(): string
    {
        $classe = $this->parametre('classe_nom');
        $quoi = match (true) {
            $this->type === self::TYPE_GENERATION => 'Génération des bulletins',
            $this->parametre('mode') === 'apercu' => 'Aperçu groupé des bulletins',
            default => 'PDF groupé des bulletins',
        };

        return $classe ? $quoi.' · '.$classe : $quoi;
    }

    public function pourcent(): int
    {
        if ($this->total <= 0) {
            return $this->estFinale() ? 100 : 0;
        }

        return (int) min(100, round($this->position / $this->total * 100));
    }
}
