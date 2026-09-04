<?php

namespace App\Domain\Inscriptions\Pieces;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscription;
use App\Models\ESBTPInscriptionPiece;
use App\Models\ESBTPPieceDossier;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Les pieces attendues au dossier d'une inscription, et ce qui a ete recu.
 *
 * Tout part d'un catalogue configure par l'ecole. Tant qu'il est vide, ce
 * service repond « inactif » et la fiche d'inscription reste exactement ce
 * qu'elle etait : aucune ecole ne voit apparaitre un panneau qu'elle n'a pas
 * demande.
 */
class PiecesDossierService
{
    /**
     * Ce que compte « 4 pieces sur 7 » : toutes les pieces attendues, ou les
     * seules obligatoires. Les deux lectures se defendent — une ecole qui
     * n'exige presque rien veut voir son total complet, une ecole au dossier
     * lourd veut suivre le noyau dur — donc c'est un reglage d'instance.
     */
    public const REGLAGE_BASE_COMPTEUR = 'inscriptions.pieces.compteur_base';

    public const BASE_TOUTES = 'toutes';

    public const BASE_OBLIGATOIRES = 'obligatoires';

    /**
     * Le signal « il manque une piece obligatoire » sur la fiche. Une ecole qui
     * se sert du panneau comme d'un simple aide-memoire peut vouloir l'eteindre.
     */
    public const REGLAGE_SIGNAL_FICHE = 'inscriptions.pieces.signal_fiche';

    /**
     * Le catalogue applicable a une inscription, deja resolu et ordonne.
     */
    public function catalogueApplicable(?int $filiereId, ?int $niveauId): Collection
    {
        $lignes = ESBTPPieceDossier::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('filiere_id')->orWhere('filiere_id', $filiereId))
            ->where(fn ($q) => $q->whereNull('niveau_id')->orWhere('niveau_id', $niveauId))
            ->get();

        return self::retenirLesPlusPrecises($lignes, $filiereId, $niveauId);
    }

    /**
     * Departage les declinaisons d'un meme code : la portee la plus precise
     * gagne sur le defaut de l'etablissement.
     *
     * Fonction pure, pour etre verifiable sans base de donnees.
     */
    public static function retenirLesPlusPrecises(Collection $lignes, ?int $filiereId, ?int $niveauId): Collection
    {
        return $lignes
            ->filter(function (ESBTPPieceDossier $ligne) use ($filiereId, $niveauId): bool {
                $filiereOk = $ligne->filiere_id === null || (int) $ligne->filiere_id === (int) $filiereId;
                $niveauOk = $ligne->niveau_id === null || (int) $ligne->niveau_id === (int) $niveauId;

                return $ligne->is_active && $filiereOk && $niveauOk;
            })
            ->groupBy('code')
            ->map(fn (Collection $declinaisons) => $declinaisons
                ->sortByDesc(fn (ESBTPPieceDossier $l) => $l->precisionPortee())
                ->first())
            // Les obligatoires d'abord : c'est ce que le secretariat reclame en
            // premier au guichet. Puis l'ordre voulu par l'ecole, puis l'alphabet.
            ->sortBy(fn (ESBTPPieceDossier $l) => sprintf(
                '%d-%05d-%s',
                $l->est_obligatoire ? 0 : 1,
                (int) $l->ordre,
                mb_strtolower((string) $l->libelle)
            ))
            ->values();
    }

    /**
     * L'etat complet a afficher : la liste, les compteurs, le signal.
     */
    public function etat(ESBTPInscription $inscription): array
    {
        try {
            $catalogue = $this->catalogueApplicable($inscription->filiere_id, $inscription->niveau_id);

            $recues = ESBTPInscriptionPiece::query()
                ->where('inscription_id', $inscription->id)
                ->with('marqueePar:id,name')
                ->get()
                ->keyBy('piece_code');
        } catch (QueryException $e) {
            // Le deploiement d'un tenant passe par « pull » puis « migrate » :
            // entre les deux, les tables n'existent pas encore. Une fiche
            // d'inscription qui tombe en 500 pendant cette fenetre serait une
            // regression pour des ecoles de plus de 2000 inscrits — on rend le
            // panneau inactif, la fiche reste ce qu'elle etait.
            Log::warning('[pieces-dossier] Catalogue illisible, panneau desactive.', [
                'inscription_id' => $inscription->id,
                'erreur' => $e->getMessage(),
            ]);

            return self::composerEtat(collect(), collect());
        }

        return self::composerEtat(
            $catalogue,
            $recues,
            (string) SettingsHelper::get(self::REGLAGE_BASE_COMPTEUR, self::BASE_TOUTES),
            (bool) SettingsHelper::get(self::REGLAGE_SIGNAL_FICHE, true),
        );
    }

    /**
     * Assemble la vue a partir du catalogue et de ce qui a ete recu.
     *
     * Fonction pure, pour etre verifiable sans base de donnees.
     */
    public static function composerEtat(
        Collection $catalogue,
        Collection $recues,
        string $baseCompteur = self::BASE_TOUTES,
        bool $signalActif = true
    ): array {
        $pieces = $catalogue->map(function (ESBTPPieceDossier $ligne) use ($recues): array {
            $recue = $recues->get($ligne->code);
            $fournie = $recue !== null && (bool) $recue->est_fournie;

            // On ne lit l'auteur que si la relation a ete chargee : y toucher
            // autrement declencherait une requete par ligne affichee.
            $auteur = $fournie && $recue->relationLoaded('marqueePar')
                ? optional($recue->getRelation('marqueePar'))->name
                : null;

            return [
                'code' => $ligne->code,
                'libelle' => $ligne->libelle,
                'description' => $ligne->description,
                'obligatoire' => (bool) $ligne->est_obligatoire,
                'fournie' => $fournie,
                'fournie_le' => $fournie && $recue->fournie_le ? $recue->fournie_le->format('d/m/Y') : null,
                'marquee_par' => $auteur,
            ];
        })->values();

        $obligatoires = $pieces->where('obligatoire', true);
        $obligatoiresManquantes = $obligatoires->where('fournie', false)->count();

        $base = $baseCompteur === self::BASE_OBLIGATOIRES ? $obligatoires : $pieces;

        return [
            'actif' => $pieces->isNotEmpty(),
            'pieces' => $pieces->all(),
            'compteur' => [
                'base' => $baseCompteur === self::BASE_OBLIGATOIRES ? self::BASE_OBLIGATOIRES : self::BASE_TOUTES,
                'fournies' => $base->where('fournie', true)->count(),
                'total' => $base->count(),
            ],
            'total_pieces' => $pieces->count(),
            'total_obligatoires' => $obligatoires->count(),
            'obligatoires_manquantes' => $obligatoiresManquantes,
            // Le signal se montre, il ne bloque pas : une inscription a laquelle
            // il manque une piece reste validable, decision prise en amont.
            'signal' => $signalActif && $pieces->isNotEmpty() && $obligatoiresManquantes > 0,
        ];
    }

    /**
     * Coche ou decoche une piece. Le geste inverse existe parce qu'on se trompe.
     *
     * Retourne l'etat complet recalcule, pour que l'ecran n'ait jamais a
     * recharger la page pour se remettre d'accord avec la base.
     */
    public function basculer(
        ESBTPInscription $inscription,
        string $code,
        bool $fournie,
        ?int $utilisateurId = null,
        ?string $observation = null
    ): array {
        $ligne = $this->catalogueApplicable($inscription->filiere_id, $inscription->niveau_id)
            ->firstWhere('code', $code);

        if ($ligne === null) {
            // Refuser plutot que de creer une ligne fantome : le catalogue est
            // la seule source de ce qui est attendu.
            throw new PieceHorsCatalogueException($code);
        }

        ESBTPInscriptionPiece::updateOrCreate(
            ['inscription_id' => $inscription->id, 'piece_code' => $code],
            [
                'piece_id' => $ligne->id,
                'est_fournie' => $fournie,
                // On efface la date au decochage : garder l'ancienne laisserait
                // croire que la piece a ete rendue puis reprise.
                'fournie_le' => $fournie ? now()->toDateString() : null,
                'marquee_par' => $utilisateurId,
                'observation' => $observation,
            ]
        );

        return $this->etat($inscription);
    }
}
