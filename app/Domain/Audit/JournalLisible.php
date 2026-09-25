<?php

namespace App\Domain\Audit;

use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Models\Audit;

/**
 * Transforme des lignes de la table `audits` en phrases qu'une personne lit.
 *
 * Tout se fait par lot pour une page : les objets (NommageDesObjets), les noms
 * lies des champs (ChampsLisibles) et les roles des auteurs. Le journal, la
 * page de detail et l'activite des personnes passent tous par ici : une seule
 * facon de dire ce qui s'est passe.
 */
class JournalLisible
{
    /** Le champ qui se lit sans etre nomme, pour chaque sorte d'objet (la note d'une note). */
    private const VALEUR_PRINCIPALE = [
        'App\Models\ESBTPNote' => ['note'],
        'App\Models\ESBTPPaiement' => ['montant'],
    ];

    public function __construct(
        private readonly NommageDesObjets $nommage,
        private readonly PermissionRegistry $registre,
    ) {
    }

    /**
     * @param  iterable<Audit>  $audits  avec `user.roles` charge, idealement
     * @return list<LigneDuJournal>
     */
    public function lignes(iterable $audits): array
    {
        $audits = collect($audits);
        $objets = $this->nommage->pour($audits);
        $champs = new ChampsLisibles($audits);

        return $audits->map(fn (Audit $a) => new LigneDuJournal(
            id: (int) $a->id,
            acteur: $a->user?->name ?? 'Système',
            role: $this->role($a->user),
            automatique: $a->user_id === null,
            verbe: self::verbe($a),
            objet: $objets[$a->id],
            changement: $champs->principal($a, self::VALEUR_PRINCIPALE[$a->auditable_type] ?? []),
            quand: $a->created_at,
            motifs: ThemesDuJournal::motifs($a),
        ))->values()->all();
    }

    public function ligne(Audit $audit): LigneDuJournal
    {
        return $this->lignes([$audit])[0];
    }

    /**
     * Ce que l'auteur a fait. Le verbe dit le geste metier quand les valeurs
     * le prouvent (valider, annuler, publier), sinon l'evenement.
     */
    public static function verbe(Audit $audit): string
    {
        $apres = ValeursDAudit::de($audit->new_values);
        $statut = (string) ($apres['status'] ?? $apres['statut'] ?? '');
        $type = (string) $audit->auditable_type;

        if ($audit->event === 'updated' && $statut !== '') {
            $geste = match (true) {
                $type === 'App\Models\ESBTPPaiement' && str_starts_with($statut, 'valid') => 'a validé',
                $type === 'App\Models\ESBTPPaiement' && str_starts_with($statut, 'rejet') => 'a rejeté',
                $type === 'App\Models\ESBTPPaiement' && str_starts_with($statut, 'annul') => 'a annulé',
                $statut === 'acceptee' => 'a accepté',
                $statut === 'rejetee' => 'a rejeté',
                $statut === 'convertie' => 'a clôturé',
                default => null,
            };
            if ($geste !== null) {
                return $geste;
            }
        }
        if ($audit->event === 'updated' && filter_var($apres['is_published'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return 'a publié';
        }

        return match ($audit->event) {
            'created' => match ($type) {
                'App\Models\ESBTPPaiement' => 'a enregistré',
                'App\Models\ESBTPNote' => 'a saisi',
                default => 'a créé',
            },
            'updated' => 'a modifié',
            'deleted' => 'a supprimé',
            'restored' => 'a restauré',
            'retrieved' => 'a consulté',
            default => 'a touché',
        };
    }

    private function role(?User $user): ?string
    {
        $role = $user?->relationLoaded('roles') ? $user->roles->first()?->name : null;

        return $role ? mb_strtolower((string) ($this->registre->roleMeta($role)['label'] ?? $role), 'UTF-8') : null;
    }

    /**
     * Le nombre d'actions automatiques d'une periode, et ce qu'elles ont
     * surtout touche : ce qu'on dit a la place de les afficher.
     *
     * @return array{nombre: int, surtout: ?string}
     */
    public static function resumeAutomatique(Collection $parType): array
    {
        $type = $parType->sortDesc()->keys()->first();

        return [
            'nombre' => (int) $parType->sum(),
            'surtout' => $type ? mb_strtolower(\App\Helpers\EntityLabelHelper::plural((string) $type), 'UTF-8') : null,
        ];
    }

    /** « Chrome · Windows » : ce qu'on retient d'un navigateur, sans la chaine brute. */
    public static function navigateur(?string $agent): ?string
    {
        if (! $agent) {
            return null;
        }
        $nav = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Safari/') => 'Safari',
            default => null,
        };
        $os = match (true) {
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $nav || $os ? implode(' · ', array_filter([$nav, $os])) : \Illuminate\Support\Str::limit($agent, 60);
    }
}
