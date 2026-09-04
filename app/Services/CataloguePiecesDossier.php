<?php

namespace App\Services;

use App\Enums\AppartenancePieceDossier;
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
     * Les clés des trois réglages, en constantes plutôt qu'en chaînes.
     *
     * Le formulaire des réglages, la page de configuration et ce service les
     * nomment tous les trois. Écrites à la main de part et d'autre, une faute
     * de frappe d'un seul côté enregistrerait dans le vide sans rien signaler
     * — c'est l'incident de la PR #591, qu'on ne rejoue pas.
     */
    public const REGLAGE_EXEMPLAIRES_MAX = 'pieces_dossier.exemplaires_max';
    public const REGLAGE_FORME_DEFAUT = 'pieces_dossier.forme_defaut';
    public const REGLAGE_ECHEANCE_DEFAUT = 'pieces_dossier.echeance_defaut';

    /**
     * Les deux réglages du suivi pièce par pièce, qui vient au lot suivant.
     *
     * Ils sont posés dès maintenant parce que ce sont des décisions d'école, et
     * qu'une décision d'école ne se découvre pas le jour du déploiement.
     * L'écran de réglages les montre et dit qu'ils attendent cette suite.
     */
    public const REGLAGE_EPUISEMENT = 'pieces_dossier.epuisement';
    public const REGLAGE_RESTITUTION_ANNULATION = 'pieces_dossier.restitution_annulation';

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
     * On lit la filière et le niveau de la CLASSE, et l'on ne retombe sur ceux
     * de l'inscription que si elle n'a pas de classe. Ce n'est PAS que
     * l'inscription garderait un vœu différent : dans ce dépôt, ses colonnes
     * `filiere_id` et `niveau_id` sont dérivées de la classe à la création et
     * réécrites au changement de classe. Le repli couvre le seul cas réel — une
     * inscription sans classe — et rien d'autre.
     *
     * Attention en LMD : la classe s'ancre sur une filière-reflet, jamais sur la
     * filière BTS homonyme. C'est ce reflet qu'une portée doit désigner, sans
     * quoi elle n'est satisfaite par aucune inscription. L'écran de
     * configuration nomme les reflets pour cette raison.
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
     * Installe le jeu proposé, ou rend au catalogue les pièces archivées.
     *
     * Deux gardes, et non une seule :
     *
     *  - une pièce dont le code est déjà AU CATALOGUE n'est ni recréée ni
     *    écrasée. Un second clic ne rend pas une pièce retirée entre-temps, et
     *    n'efface pas le libellé que la scolarité venait de corriger ;
     *  - une pièce dont le code n'existe plus qu'ARCHIVÉ est restaurée, telle
     *    que l'école l'avait laissée.
     *
     * La distinction n'est pas cosmétique. Comparer aux codes archivés comme
     * s'ils étaient vivants produisait une impasse reproductible : on installe
     * le jeu, on retire les cinq pièces, l'écran vide revient avec son bouton,
     * on reclique — et le serveur répond « le catalogue contient déjà toutes les
     * pièces proposées » devant un écran resté vide. L'école n'avait plus aucun
     * moyen de repartir. Mais ne comparer qu'aux codes vivants sans traiter les
     * archives échouerait autrement : l'index unique du code voit les pièces
     * archivées, et la création se briserait sur lui.
     *
     * @return int nombre de pièces rendues au catalogue, créées ou restaurées
     */
    public function installerJeuPropose(?int $userId = null): int
    {
        $codesVivants = ESBTPPieceDossier::pluck('code')->all();
        $archiveesParCode = ESBTPPieceDossier::onlyTrashed()->get()->keyBy('code');
        $ordre = (int) ESBTPPieceDossier::withTrashed()->max('ordre');
        $exemplairesMax = $this->exemplairesMax();
        $rendues = 0;

        foreach ($this->jeuPropose() as $piece) {
            $code = (string) ($piece['code'] ?? '');

            if ($code === '' || in_array($code, $codesVivants, true)) {
                continue;
            }

            if ($archiveesParCode->has($code)) {
                $archivee = $archiveesParCode->get($code);
                $archivee->restore();
                // Une pièce archivée pouvait avoir été désactivée avant de
                // l'être : la restaurer sans la réactiver la rendrait au
                // catalogue tout en la laissant sans effet sur les dossiers,
                // ce qui se lit comme un bouton qui n'a rien fait.
                $archivee->forceFill(['is_active' => true, 'updated_by' => $userId])->save();

                $codesVivants[] = $code;
                $rendues++;

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
                'exemplaires_par_inscription' => max(1, min(
                    $exemplairesMax,
                    (int) ($piece['exemplaires_par_inscription'] ?? 1)
                )),
                'echeance' => (EcheancePieceDossier::tryFromLibre($piece['echeance'] ?? null)
                    ?? $this->echeanceParDefaut())->value,
                // Le repli est « à l'étudiant » parce que c'est le cas normal
                // d'un dossier d'inscription : état civil, photos et diplômes
                // se déposent une fois. Une pièce qui se redonne chaque année
                // le dit explicitement dans le jeu proposé.
                'appartenance' => (AppartenancePieceDossier::tryFromLibre($piece['appartenance'] ?? null)
                    ?? AppartenancePieceDossier::ETUDIANT)->value,
                'duree_validite_mois' => $this->dureeValiditeOuNul($piece['duree_validite_mois'] ?? null),
                'is_active' => true,
                'ordre' => $ordre,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $codesVivants[] = $code;
            $rendues++;
        }

        return $rendues;
    }

    /**
     * Une durée de validité en mois, ou null pour « ne périme jamais ».
     *
     * Zéro et les valeurs négatives sont ramenés au nul : « valide zéro mois »
     * n'est jamais ce que quelqu'un a voulu écrire, et laisser passer un zéro
     * rendrait la pièce périmée à l'instant même de son dépôt.
     */
    private function dureeValiditeOuNul($brut): ?int
    {
        if ($brut === null || $brut === '' || ! is_numeric($brut)) {
            return null;
        }

        $mois = (int) $brut;

        return $mois > 0 ? $mois : null;
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
        $valeur = (int) SettingsHelper::get(self::REGLAGE_EXEMPLAIRES_MAX, self::EXEMPLAIRES_MAX_REPLI);

        // Un réglage vide, nul ou négatif rendrait toute création impossible :
        // on ne laisse pas un champ mal saisi bloquer le guichet.
        return $valeur > 0 ? $valeur : self::EXEMPLAIRES_MAX_REPLI;
    }

    /** Forme proposée par défaut à la création d'une pièce. */
    public function formeParDefaut(): FormePieceDossier
    {
        return FormePieceDossier::tryFromLibre(
            SettingsHelper::get(self::REGLAGE_FORME_DEFAUT)
        ) ?? FormePieceDossier::COPIE;
    }

    /** Échéance proposée par défaut à la création d'une pièce. */
    public function echeanceParDefaut(): EcheancePieceDossier
    {
        return EcheancePieceDossier::tryFromLibre(
            SettingsHelper::get(self::REGLAGE_ECHEANCE_DEFAUT)
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
