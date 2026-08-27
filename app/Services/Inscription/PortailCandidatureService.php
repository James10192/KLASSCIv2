<?php

namespace App\Services\Inscription;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPCandidature;
use App\Models\ESBTPFiliere;
use App\Models\ESBTPNiveauEtude;
use App\Services\Reinscription\PortailReinscriptionService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

/**
 * Les candidatures des nouveaux eleves, deposees depuis klassci.com.
 *
 * Le pendant de PortailReinscriptionService pour ceux qui n'ont pas encore de
 * dossier. La difference de nature commande tout le reste :
 *
 * Une reinscription IDENTIFIE quelqu'un — d'ou l'obsession de la reponse
 * uniforme, puisque distinguer « ce matricule n'existe pas » de « la date ne
 * correspond pas » donnerait un oracle d'enumeration.
 *
 * Une candidature n'identifie personne : il n'y a rien a retrouver, donc rien
 * a enumerer. En echange, n'importe qui peut deposer. Le risque bascule de la
 * fuite d'information vers le remplissage abusif — d'ou l'unicite sur le
 * telephone, qui transforme un renvoi de formulaire en mise a jour plutot
 * qu'en doublon dans la corbeille.
 *
 * La fenetre saisonniere et l'annee visee sont PARTAGEES avec la reinscription :
 * c'est la meme rentree. Seul l'interrupteur est propre a ce canal, parce
 * qu'une ecole peut vouloir reinscrire les siens sans ouvrir aux exterieurs.
 */
class PortailCandidatureService
{
    public const REGLAGE_ACTIF = 'inscriptions.en_ligne.enabled';

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
     * Ce que le portail publie comme choix d'orientation.
     *
     * Uniquement des noms de filieres et de niveaux actifs — aucune donnee
     * d'eleve, aucun effectif, aucune place restante. Un candidat n'a pas a
     * savoir quelle classe est pleine ; c'est une information de gestion, et
     * la publier renseignerait un concurrent sur l'etat de l'ecole.
     *
     * @return array{filieres: list<array{id:int,nom:string}>, niveaux: list<array{id:int,nom:string}>}
     */
    public function choixPublies(): array
    {
        return [
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

    /**
     * Depose une candidature.
     *
     * Idempotent par (telephone, annee) : un double clic, un retour arriere du
     * navigateur ou une famille qui renvoie le formulaire mettent a jour la
     * candidature existante au lieu d'en creer une seconde. Une corbeille
     * remplie de doublons est une corbeille que la scolarite cesse de lire.
     *
     * @param  array<string, mixed>  $champs
     */
    public function deposer(array $champs, ?string $empreinteAdresse = null): ?ESBTPCandidature
    {
        $anneeCible = $this->saison->anneeCible();

        if ($anneeCible === null) {
            Log::warning('Candidature refusee : aucune annee universitaire visee');

            return null;
        }

        $cles = [
            'telephone' => $champs['telephone'],
            'annee_universitaire_id' => $anneeCible->id,
        ];

        $valeurs = [
            'nom' => $champs['nom'],
            'prenoms' => $champs['prenoms'],
            'date_naissance' => $champs['date_naissance'],
            'sexe' => $champs['sexe'] ?? null,
            'email' => $champs['email'] ?? null,
            'filiere_id' => $champs['filiere_id'] ?? null,
            'niveau_id' => $champs['niveau_id'] ?? null,
            'voeu_libre' => $champs['voeu_libre'] ?? null,
            'serie_bac' => $champs['serie_bac'] ?? null,
            'etablissement_origine' => $champs['etablissement_origine'] ?? null,
            'annee_bac' => $champs['annee_bac'] ?? null,
            'message' => $champs['message'] ?? null,
            'consentement_at' => now(),
            'ip_hash' => $empreinteAdresse,
        ];

        $existante = ESBTPCandidature::where($cles)->first();

        // Une candidature DEJA TRAITEE ne se reecrit pas en silence : l'ecole a
        // decide, et ecraser sa decision parce qu'une famille a renvoye le
        // formulaire lui ferait perdre son travail. On la rouvre explicitement,
        // et l'historique reste dans le journal d'audit.
        if ($existante !== null) {
            $existante->update($valeurs + [
                'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
                'motif_rejet' => null,
                'traite_par' => null,
                'traite_at' => null,
            ]);

            return $existante;
        }

        try {
            $candidature = ESBTPCandidature::create($cles + $valeurs + [
                'statut' => ESBTPCandidature::STATUT_EN_ATTENTE,
            ]);
        } catch (QueryException $e) {
            // Deux envois simultanes ont lu « pas de candidature » en meme
            // temps. L'index unique a tranche ; on rend la gagnante, pour que
            // le perdant recoive la meme reponse que s'il avait gagne.
            $rattrapee = ESBTPCandidature::where($cles)->first();

            if ($rattrapee === null) {
                throw $e;
            }

            return $rattrapee;
        }

        Log::info('Candidature deposee depuis le portail public', [
            'candidature_id' => $candidature->id,
            'annee_universitaire_id' => $anneeCible->id,
        ]);

        return $candidature;
    }

    private function actif(): bool
    {
        $valeur = SettingsHelper::get(self::REGLAGE_ACTIF, '0');

        return $valeur === true || $valeur === 1 || $valeur === '1';
    }
}
