<?php

namespace App\Services;

use App\Enums\EcheancePieceDossier;
use App\Enums\FormePieceDossier;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPInscription;
use App\Models\ESBTPPieceDossier;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Lecture, réglages et amorçage du catalogue des pièces à fournir.
 *
 * Point d'entrée unique. Personne n'interroge esbtp_pieces_dossier
 * directement : sinon la règle « portée vide = tout le monde » finirait par
 * être réécrite un peu différemment à chaque appel, et deux écrans
 * réclameraient des pièces différentes au même étudiant.
 */
class CataloguePiecesDossier
{
    /**
     * Repli de dernier recours, quand le réglage d'école est vide ou illisible.
     *
     * Ce n'est pas une décision d'établissement déguisée : c'est ce que le
     * logiciel fait d'une donnée qu'il ne comprend pas. La décision, elle, est
     * dans `settings`, où l'école peut la changer.
     */
    private const EXEMPLAIRES_MAX_REPLI = 20;

    /**
     * Une école qui n'a rien configuré ne doit voir aucun changement dans les
     * écrans existants. Tout affichage de dossier se garde derrière ce test :
     * catalogue vide, aucun signal, aucun compteur, aucun contrôle.
     */
    public function estConfigure(): bool
    {
        return ESBTPPieceDossier::actives()->exists();
    }

    /** Catalogue complet, actif ou non, pour l'écran de configuration. */
    public function tout(): Collection
    {
        return ESBTPPieceDossier::with(['filieres:id,name', 'niveaux:id,name'])
            ->ordonne()
            ->get();
    }

    /**
     * Pièces réclamées à un étudiant de cette filière et de ce niveau.
     *
     * Le filtrage de portée se fait en mémoire, pas en SQL : un catalogue tient
     * en quelques dizaines de lignes, et la règle « aucune ligne de portée =
     * tout le monde » s'écrit en PHP sans détour, là où en SQL elle demande une
     * sous-requête NOT EXISTS doublée d'un OR, plus facile à écrire de travers.
     */
    public function pourScope(?int $filiereId, ?int $niveauId): Collection
    {
        return ESBTPPieceDossier::with(['filieres:id,name', 'niveaux:id,name'])
            ->actives()
            ->ordonne()
            ->get()
            ->filter(fn (ESBTPPieceDossier $piece) => $piece->sApplique($filiereId, $niveauId))
            ->values();
    }

    /**
     * Pièces réclamées pour une inscription donnée.
     *
     * On lit d'abord la filière et le niveau de la CLASSE : c'est elle qui
     * porte la vérité après une orientation ou un changement de classe en cours
     * d'année, l'inscription pouvant garder le vœu initial.
     */
    public function pourInscription(ESBTPInscription $inscription): Collection
    {
        $inscription->loadMissing('classe');

        $filiereId = $inscription->classe?->filiere_id ?? $inscription->filiere_id;
        $niveauId = $inscription->classe?->niveau_etude_id ?? $inscription->niveau_id;

        return $this->pourScope(
            $filiereId ? (int) $filiereId : null,
            $niveauId ? (int) $niveauId : null
        );
    }

    /** Jeu proposé à l'ouverture du catalogue (cf. config/pieces_dossier.php). */
    public function jeuPropose(): array
    {
        return (array) config('pieces_dossier.jeu_propose', []);
    }

    /**
     * Installe le jeu proposé.
     *
     * Idempotent par code : une pièce dont le code existe déjà — même archivée —
     * n'est ni recréée ni écrasée. Sans cette garde, un second clic rendrait au
     * secrétariat une pièce qu'il venait de retirer, ou effacerait le libellé
     * qu'il venait de corriger.
     *
     * @return int nombre de pièces réellement créées
     */
    public function installerJeuPropose(?int $userId = null): int
    {
        $codesExistants = ESBTPPieceDossier::withTrashed()->pluck('code')->all();
        $ordre = (int) ESBTPPieceDossier::withTrashed()->max('ordre');
        $exemplairesMax = $this->exemplairesMax();
        $creees = 0;

        foreach ($this->jeuPropose() as $piece) {
            $code = (string) ($piece['code'] ?? '');

            if ($code === '' || in_array($code, $codesExistants, true)) {
                continue;
            }

            $ordre += 10;

            ESBTPPieceDossier::create([
                'code' => $code,
                'libelle' => (string) ($piece['libelle'] ?? $code),
                'description' => $piece['description'] ?? null,
                'is_obligatoire' => (bool) ($piece['is_obligatoire'] ?? true),
                'forme_attendue' => (FormePieceDossier::tryFromLibre($piece['forme_attendue'] ?? null)
                    ?? $this->formeParDefaut())->value,
                'nombre_exemplaires' => max(1, min($exemplairesMax, (int) ($piece['nombre_exemplaires'] ?? 1))),
                'echeance' => (EcheancePieceDossier::tryFromLibre($piece['echeance'] ?? null)
                    ?? $this->echeanceParDefaut())->value,
                'is_active' => true,
                'ordre' => $ordre,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $codesExistants[] = $code;
            $creees++;
        }

        return $creees;
    }

    /**
     * Code technique dérivé du libellé, unique dans le catalogue.
     *
     * Le code n'est pas saisi au guichet : demander à une secrétaire d'inventer
     * un identifiant stable et unique, c'est lui demander de faire de
     * l'informatique. L'unicité est cherchée sur les pièces archivées aussi,
     * parce que l'index unique de la table, lui, les voit encore.
     */
    public function genererCode(string $libelle, ?int $ignorerId = null): string
    {
        $base = Str::limit(Str::slug($libelle, '_'), 50, '');

        if ($base === '') {
            $base = 'piece';
        }

        $code = $base;
        $suffixe = 1;

        while ($this->codeExiste($code, $ignorerId)) {
            $suffixe++;
            $code = $base . '_' . $suffixe;
        }

        return $code;
    }

    /**
     * Plafond du nombre d'exemplaires, réglable par l'école.
     *
     * Un plafond sert à arrêter la faute de frappe, pas à dire à une école
     * combien de photos elle a le droit de réclamer. Où le placer lui appartient.
     */
    public function exemplairesMax(): int
    {
        $valeur = (int) SettingsHelper::get('pieces_dossier.exemplaires_max', self::EXEMPLAIRES_MAX_REPLI);

        // Un réglage vide, nul ou négatif rendrait toute création impossible :
        // on ne laisse pas un champ mal saisi bloquer le guichet.
        return $valeur > 0 ? $valeur : self::EXEMPLAIRES_MAX_REPLI;
    }

    /** Forme proposée par défaut à la création d'une pièce. */
    public function formeParDefaut(): FormePieceDossier
    {
        return FormePieceDossier::tryFromLibre(
            SettingsHelper::get('pieces_dossier.forme_defaut')
        ) ?? FormePieceDossier::COPIE;
    }

    /** Échéance proposée par défaut à la création d'une pièce. */
    public function echeanceParDefaut(): EcheancePieceDossier
    {
        return EcheancePieceDossier::tryFromLibre(
            SettingsHelper::get('pieces_dossier.echeance_defaut')
        ) ?? EcheancePieceDossier::INSCRIPTION;
    }

    private function codeExiste(string $code, ?int $ignorerId): bool
    {
        return ESBTPPieceDossier::withTrashed()
            ->where('code', $code)
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->exists();
    }
}
