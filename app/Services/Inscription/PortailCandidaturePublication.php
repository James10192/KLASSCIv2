<?php

namespace App\Services\Inscription;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Services\Reinscription\PortailReinscriptionService;
use App\Support\Nationalites;
use Illuminate\Support\Facades\Log;

/**
 * Ce que le portail des candidatures PUBLIE : l'etat du canal et ses listes.
 *
 * De la lecture pure — aucune transaction, aucun verrou, aucune ecriture. La
 * machine a etats du depot vit a cote, dans PortailCandidatureService, et les
 * deux etaient dans le meme fichier de cinq cent dix lignes ou cohabitaient
 * cinq responsabilites.
 *
 * La couture est nette : ici on repond a « puis-je deposer, et sur quoi ? » ;
 * la-bas on repond a « que devient ce depot ? », avec trois transactions, deux
 * verrous et tout l'invariant « convertie est terminal ».
 *
 * La fenetre saisonniere et l'annee visee sont PARTAGEES avec la reinscription :
 * c'est la meme rentree. Seul l'interrupteur est propre a ce canal, parce
 * qu'une ecole peut vouloir reinscrire les siens sans ouvrir aux exterieurs.
 */
class PortailCandidaturePublication
{
    public const REGLAGE_ACTIF = 'inscriptions.en_ligne.enabled';

    public const REGLAGE_PHYSIQUES = 'inscriptions.physiques.debut';

    public function __construct(private readonly PortailReinscriptionService $saison) {}

    /**
     * Le canal des nouvelles candidatures est-il ouvert ?
     *
     * Deux conditions : l'interrupteur propre a ce canal, et la fenetre de
     * dates commune a la rentree. On reutilise la seconde plutot que de la
     * dupliquer — deux fenetres a maintenir finiraient par diverger, et une
     * ecole qui deplace sa periode d'inscription ne pense pas a le faire deux
     * fois.
     */
    public function canalOuvert(): bool
    {
        return $this->actif() && $this->saison->fenetreOuverte();
    }

    /**
     * L'annee que les candidatures viseront, ou null si l'ecole n'en a designe
     * aucune.
     *
     * L'annee EXPLICITEMENT choisie, et elle seule. Pas `anneeCible()`, qui
     * retombe sur `is_current` : ce repli est juste pour la reinscription, il
     * est faux ici.
     *
     * La raison tient au reglage lui-meme. `inscriptions.annee_cible` existe
     * parce qu'une ecole ouvre sa rentree AVANT d'avoir clos l'annee
     * precedente — la saisie des notes continue sur l'annee courante pendant
     * que les inscriptions visent la suivante. C'est exactement la situation
     * d'ESBTP Abidjan. Retomber sur `is_current` dans ce cas rangerait les
     * candidatures des bacheliers de la rentree sous l'annee qui s'acheve : la
     * cle d'unicite serait scopee sur la mauvaise annee, la corbeille
     * afficherait la mauvaise annee, et le pre-remplissage preparerait la
     * mauvaise inscription. Sans un mot, puisque le depot reussirait.
     *
     * La reinscription, elle, s'en apercevrait : tout le monde ressortirait
     * « non eligible ». Une candidature n'a rien a verifier, donc rien qui la
     * trahisse. On refuse plutot que de deviner, et le message dit a l'ecole
     * quoi renseigner.
     */
    public function anneeVisee(): ?ESBTPAnneeUniversitaire
    {
        return $this->saison->anneeChoisie();
    }

    /**
     * Quand l'ecole recoit les candidats pour finaliser leur dossier.
     *
     * Deposer en ligne ne finit rien : les pieces et le paiement se remettent
     * sur place. Reste la question que la scolarite entend le plus au
     * telephone — « je viens quand ? ». Le portail peut y repondre, a
     * condition que l'ecole ait renseigne la date.
     *
     * Trois etats, et le portail dit trois choses differentes :
     *   - pas de date : on invite simplement a se rapprocher de l'ecole ;
     *   - date a venir : on l'annonce, le candidat sait quand se deplacer ;
     *   - date atteinte ou passee : on dit d'y aller maintenant.
     *
     * La comparaison se fait au JOUR, pas a l'instant : « le 12 septembre »
     * veut dire toute la journee du 12, pas a partir de minuit une.
     *
     * @return array{debut: ?string, ouvertes: bool}
     */
    public function inscriptionsPhysiques(): array
    {
        $brut = trim((string) SettingsHelper::get(self::REGLAGE_PHYSIQUES, ''));

        if ($brut === '') {
            return ['debut' => null, 'ouvertes' => false];
        }

        // Le meme analyseur que les bornes de la fenetre saisonniere, et pour
        // la meme raison : il refuse « 2026-02-31 », que Carbon::parse aurait
        // silencieusement reporte au 3 mars. Deux severites differentes entre
        // ce que l'ecole enregistre et ce que le portail applique, c'est un
        // ecart que personne ne voit jusqu'a ce qu'un candidat se deplace le
        // mauvais jour.
        $debut = PortailReinscriptionService::interpreterDateIso($brut);

        if ($debut === null) {
            // Une date mal saisie ne doit pas casser le depot : le candidat a
            // rempli son formulaire, il a droit a sa confirmation. On retombe
            // sur le message neutre et on le signale a l'ecole.
            Log::warning('Date de debut des inscriptions physiques illisible', [
                'valeur' => $brut,
            ]);

            return ['debut' => null, 'ouvertes' => false];
        }

        $debut = $debut->startOfDay();

        return [
            'debut' => $debut->toDateString(),
            'ouvertes' => ! $debut->isFuture(),
        ];
    }

    /**
     * Ce que le portail publie comme choix d'orientation.
     *
     * Uniquement des noms de filieres et de niveaux actifs — aucune donnee
     * d'etudiant, aucun effectif, aucune place restante. Un candidat n'a pas a
     * savoir quelle classe est pleine ; c'est une information de gestion, et
     * la publier renseignerait un concurrent sur l'etat de l'ecole.
     *
     * Les statuts d'affectation et les nationalites voyagent avec, sous la
     * MEME forme : valeur exacte + libelle affichable. Le site vitrine n'ecrit
     * donc ni les valeurs accentuees, ni leurs traductions — il affiche ce
     * qu'on lui sert et renvoie la valeur telle qu'elle lui est venue.
     *
     * Servir une cle a traduire la-bas paraissait plus propre et ne l'etait
     * pas : le jour ou une ecole obtient un quatrieme statut, le menu
     * deroulant public afficherait le chemin de cle en toutes lettres, sans
     * qu'aucun compilateur ni aucun test des deux depots ne puisse le voir.
     *
     * Pour les nationalites, la raison est encore plus concrete :
     * le formulaire d'inscription de l'ecole les compare a l'identique, accents
     * et majuscules compris. Un candidat qui ecrirait « ivoirienne » en clair
     * produirait une valeur qu'aucune option ne reconnait — le champ, pourtant
     * obligatoire, retomberait a vide au pre-remplissage, sans que personne ne
     * le voie. Servir la liste supprime la question.
     *
     * @return array{filieres: list<array{id:int,nom:string}>, niveaux: list<array{id:int,nom:string}>, affectations: list<array{valeur:string,libelle:string}>, nationalites: list<array{valeur:string,libelle:string}>}
     */
    public function choixPublies(): array
    {
        return [
            'affectations' => collect(ESBTPCandidature::affectationsDeclarables())
                ->map(fn (string $libelle, string $valeur) => ['valeur' => $valeur, 'libelle' => $libelle])
                ->values()
                ->all(),
            'liens_tuteur' => collect(ESBTPCandidature::liensTuteurDeclarables())
                ->map(fn (string $libelle, string $valeur) => ['valeur' => $valeur, 'libelle' => $libelle])
                ->values()
                ->all(),
            'nationalites' => Nationalites::pourSelecteur(),
            'filieres' => ESBTPFiliere::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($f) => ['id' => (int) $f->id, 'nom' => (string) $f->name])
                ->all(),
            'niveaux' => ESBTPNiveauEtude::where('is_active', true)
                ->orderBy('year')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($n) => ['id' => (int) $n->id, 'nom' => (string) $n->name])
                ->all(),
        ];
    }

    private function actif(): bool
    {
        $valeur = SettingsHelper::get(self::REGLAGE_ACTIF, '0');

        return $valeur === true || $valeur === 1 || $valeur === '1';
    }
}
