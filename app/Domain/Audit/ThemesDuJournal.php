<?php

namespace App\Domain\Audit;

use App\Models\User;
use App\Services\Planning\PlageHoraireJournee;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Models\Audit;

/**
 * Les onglets du journal d'audit, et ce qui merite qu'on le regarde.
 *
 * Trois pages lisaient la meme table : le journal, l'audit comptable et
 * l'activite des personnes. L'audit comptable n'etait que le journal filtre
 * sur les finances ; il devient un onglet, ouvert a qui a le droit comptable
 * seulement.
 *
 * « À regarder » remplace le « Risque élevé » que portait presque chaque
 * ligne, donc aucune : seules des actions de personnes, et seulement celles
 * qui ne se font pas d'ordinaire — une suppression de donnee sensible, un
 * paiement valide puis annule, un droit modifie, une action hors des heures de
 * l'etablissement. La regle vit ici une fois, en SQL pour l'onglet et en PHP
 * pour la ligne, sur la meme liste.
 */
final class ThemesDuJournal
{
    public const TOUT = 'tout';

    public const FINANCES = 'finances';

    public const NOTES = 'notes';

    public const INSCRIPTIONS = 'inscriptions';

    public const COMPTES = 'comptes';

    public const A_REGARDER = 'a_regarder';

    public const LIBELLES = [
        self::TOUT => 'Tout',
        self::FINANCES => 'Finances',
        self::NOTES => 'Notes et bulletins',
        self::INSCRIPTIONS => 'Inscriptions',
        self::COMPTES => 'Comptes et droits',
        self::A_REGARDER => 'À regarder',
    ];

    /** Les sortes d'objets de chaque onglet (noms de classe : une instance peut ne pas les avoir toutes). */
    private const TYPES = [
        self::FINANCES => [
            'App\Models\ESBTPPaiement', 'App\Models\ESBTPFraisSubscription', 'App\Models\ESBTPFraisCategory',
            'App\Models\ESBTPFraisOption', 'App\Models\ESBTPFacture', 'App\Models\ESBTPFactureDetail',
            'App\Models\ESBTPBourse', 'App\Models\ESBTPDepense', 'App\Models\ESBTPSalaire', 'App\Models\ESBTPFraisScolarite',
        ],
        self::NOTES => [
            'App\Models\ESBTPNote', 'App\Models\ESBTPEvaluation', 'App\Models\ESBTPResultat', 'App\Models\ESBTPBulletin',
            'App\Models\ESBTPLMDBulletin', 'App\Models\ESBTPLMDJury', 'App\Models\ESBTPLMDJuryDecision',
            'App\Models\ESBTPLMDResultatECUE', 'App\Models\ESBTPTpeDeclaration',
        ],
        self::INSCRIPTIONS => [
            'App\Models\ESBTPInscription', 'App\Models\ESBTPEtudiant', 'App\Models\ESBTPCandidature',
            'App\Models\ESBTPReinscriptionDemande', 'App\Models\ESBTPStudentAccessibilityProfile', 'App\Models\ESBTPParent',
        ],
        self::COMPTES => [
            'App\Models\User', 'Spatie\Permission\Models\Role', 'Spatie\Permission\Models\Permission',
            'App\Models\Role', 'App\Models\Permission', 'App\Models\Setting',
        ],
    ];

    private const DROITS = ['Spatie\Permission\Models\Role', 'Spatie\Permission\Models\Permission', 'App\Models\Role', 'App\Models\Permission'];

    /**
     * Les onglets que cet agent peut ouvrir. Le droit comptable seul n'ouvre
     * que les finances : c'etait la portee de l'ancien audit comptable.
     *
     * @return list<string>
     */
    public static function visibles(?User $agent): array
    {
        if ($agent?->can('security.audit.view')) {
            return array_keys(self::LIBELLES);
        }

        return $agent?->can('comptabilite.audit.view') ? [self::FINANCES] : [];
    }

    /** L'argent lui-meme : son detail demande en plus l'acces aux donnees sensibles. */
    public const ARGENT = ['App\Models\ESBTPPaiement', 'App\Models\ESBTPDepense', 'App\Models\ESBTPFacture', 'App\Models\ESBTPSalaire'];

    /**
     * Ce lecteur peut-il ouvrir le detail d'une action sur ce type d'objet ?
     * La liste et le detail posent la meme question : une ligne ne mene
     * jamais a un refus.
     */
    public static function peutOuvrir(?User $lecteur, string $type): bool
    {
        if (! $lecteur) {
            return false;
        }
        $theme = $lecteur->can('security.audit.view')
            || ($lecteur->can('comptabilite.audit.view') && self::de($type) === self::FINANCES);

        return $theme && (! in_array($type, self::ARGENT, true) || $lecteur->can('comptabilite.sensitive.access'));
    }

    public static function appliquer(Builder $requete, string $theme): Builder
    {
        return match ($theme) {
            self::A_REGARDER => self::aRegarder($requete),
            self::TOUT => $requete,
            default => $requete->whereIn('auditable_type', self::TYPES[$theme] ?? []),
        };
    }

    /** La regle « à regarder », en SQL : le meme ensemble que motifs(). */
    public static function aRegarder(Builder $requete): Builder
    {
        $plage = app(PlageHoraireJournee::class);

        return $requete->whereNotNull('user_id')->where(fn (Builder $w) => $w
            ->where(fn (Builder $s) => $s->whereIn('event', ['deleted', 'restored'])->whereIn('auditable_type', self::sensibles()))
            ->orWhere(fn (Builder $p) => $p->where('auditable_type', 'App\Models\ESBTPPaiement')->where('event', 'updated')
                ->where('old_values', 'like', '%"status":"valid%')->where('new_values', 'like', '%"status":%')
                ->where('new_values', 'not like', '%"status":"valid%')->where('new_values', 'not like', '%"status":null%'))
            ->orWhereIn('auditable_type', self::DROITS)
            ->orWhereRaw('HOUR(created_at) < ?', [$plage->debut()])
            ->orWhereRaw('HOUR(created_at) >= ?', [$plage->fin()]));
    }

    /**
     * Pourquoi cette ligne est a regarder. Vide pour une action ordinaire ou
     * automatique.
     *
     * @return list<string>
     */
    public static function motifs(Audit $audit): array
    {
        if ($audit->user_id === null) {
            return [];
        }
        $plage = app(PlageHoraireJournee::class);
        $type = (string) $audit->auditable_type;
        $avant = (string) (ValeursDAudit::de($audit->old_values)['status'] ?? '');
        $apres = ValeursDAudit::de($audit->new_values)['status'] ?? null;
        $heure = $audit->created_at?->hour;

        return array_values(array_filter([
            in_array($audit->event, ['deleted', 'restored'], true) && in_array($type, self::sensibles(), true)
                ? ($audit->event === 'deleted' ? 'Suppression' : 'Restauration') : null,
            $type === 'App\Models\ESBTPPaiement' && $audit->event === 'updated' && str_starts_with($avant, 'valid')
                && $apres !== null && ! str_starts_with((string) $apres, 'valid') ? 'Annulation après validation' : null,
            in_array($type, self::DROITS, true) ? 'Droits modifiés' : null,
            $heure !== null && ($heure < $plage->debut() || $heure >= $plage->fin()) ? 'Hors horaires' : null,
        ]));
    }

    public static function de(string $type): ?string
    {
        foreach (self::TYPES as $theme => $types) {
            if (in_array($type, $types, true)) {
                return $theme;
            }
        }

        return null;
    }

    /** @return list<string> */
    private static function sensibles(): array
    {
        return array_merge(self::TYPES[self::FINANCES], self::TYPES[self::NOTES], self::TYPES[self::COMPTES]);
    }
}
