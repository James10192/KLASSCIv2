<?php

namespace App\Services\Inscriptions;

use App\Models\ESBTPAnneeUniversitaire;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Les filtres structures de la liste des inscriptions, en un seul endroit.
 *
 * Deux ecrans posent aujourd'hui la meme question a la base : la liste
 * elle-meme, et « Regenerer les frais » quand on lui demande de travailler sur
 * ce que la liste affiche. Deux copies de ces filtres finiraient par diverger,
 * et la divergence ne se verrait pas : le bouton toucherait un ensemble
 * d'etudiants different de celui qu'on a sous les yeux — donc l'argent de
 * quelqu'un qui n'etait pas a l'ecran.
 *
 * La recherche libre n'est PAS ici. Elle passe par un score de ressemblance
 * plafonne a 150 candidats (InscriptionSearchService) : elle sert a retrouver
 * une personne, pas a definir un ensemble. En faire une portee d'ecriture
 * donnerait un resultat qui depend d'un seuil, ce qu'aucun utilisateur ne peut
 * verifier.
 */
class FiltresListeInscriptions
{
    /**
     * Applique les filtres de la requete a une requete d'inscriptions.
     *
     * `$avecDefautAnnee` reproduit le comportement de la liste : sans annee
     * choisie, on se limite a l'annee courante plutot que d'ouvrir sur tout
     * l'historique de l'etablissement.
     *
     * `$avecStatut` est desactive pour les compteurs du bandeau : ils comptent
     * justement PAR statut, donc filtrer dessus les mettrait tous a zero sauf un.
     */
    public function appliquer(
        Builder $query,
        Request $request,
        bool $avecStatut = true,
        bool $avecDefautAnnee = true
    ): Builder {
        $systeme = $this->systeme($request);
        $filiere = $request->input('filiere');
        $niveau = $request->input('niveau');
        $annee = $request->input('annee');
        $status = $request->input('status', 'active');
        $mention = $request->input('mention');
        $parcours = $request->input('parcours');

        // Filiere BTS : ne s'applique qu'en mode BTS (ou Tous systemes en mode
        // legacy). En LMD, le parametre `filiere` cede la place a mention + parcours.
        if ($filiere && $systeme !== 'LMD') {
            $query->where('filiere_id', $filiere);
        }

        if ($niveau) {
            $query->where('niveau_id', $niveau);
        }

        if ($systeme) {
            // Une inscription sans classe (en attente d'affectation) est exclue :
            // c'est la classe qui porte le systeme academique.
            $query->whereHas('classe', fn ($q) => $q->where('systeme_academique', $systeme));
        }

        // Filtres LMD (cf rule classe-lmd-filiere-as-mention). En tronc commun,
        // classe.filiere_id designe le reflet de la mention ; les lignes creees
        // avant les filieres reflets y portaient l'id de la mention elle-meme.
        if ($systeme === 'LMD' && $mention) {
            $query->whereHas('classe', function ($q) use ($mention) {
                $q->where('systeme_academique', 'LMD')
                  ->where(function ($qq) use ($mention) {
                      $qq->whereHas('filiere', fn ($f) => $f->where('lmd_mention_id', $mention))
                         ->orWhere('filiere_id', $mention)
                         ->orWhereHas('parcours', fn ($p) => $p->where('mention_id', $mention));
                  });
            });
        }

        if ($systeme === 'LMD' && $parcours) {
            $query->whereHas('classe', fn ($q) => $q->where('parcours_id', $parcours));
        }

        // Colonnes QUALIFIEES : le tri par nom d'etudiant ajoute un `leftJoin` sur
        // `esbtp_etudiants`, qui porte AUSSI une colonne `annee_universitaire_id`.
        // Sans le prefixe, MySQL refusait la requete — « Column
        // 'annee_universitaire_id' in WHERE is ambiguous », erreur 1052 — et
        // trier par etudiant rendait un 500. Meme raison pour `date_inscription`,
        // presente des deux cotes.
        if ($annee) {
            $query->where('esbtp_inscriptions.annee_universitaire_id', $annee);
        } elseif ($avecDefautAnnee && ($courante = ESBTPAnneeUniversitaire::where('is_current', true)->first())) {
            $query->where('esbtp_inscriptions.annee_universitaire_id', $courante->id);
        }

        [$debut, $fin] = $this->periode($request);

        if ($debut) {
            $query->whereDate('esbtp_inscriptions.date_inscription', '>=', $debut);
        }

        if ($fin) {
            $query->whereDate('esbtp_inscriptions.date_inscription', '<=', $fin);
        }

        if ($avecStatut && $status && $status !== 'all') {
            $this->appliquerStatut($query, $status);
        }

        return $query;
    }

    /**
     * « Non validee » n'est pas un statut en base : c'est une situation.
     *
     * Elle couvre les inscriptions en attente ET celles marquees actives dont le
     * parcours de validation n'est pas alle a son terme — le cas qui, sans cette
     * lecture, disparaissait des ecrans de relance.
     */
    private function appliquerStatut(Builder $query, string $status): void
    {
        // « Validee » est la contrepartie exacte de « non validee » ci-dessous :
        // active ET le parcours de validation alle a son terme. L'indicateur du
        // bandeau comptait deja ainsi ; le clic, lui, ne filtrait que sur le
        // statut. Sur une annee ou deux inscriptions sont actives avec un
        // parcours inacheve, l'indicateur annoncait 0 et le clic rendait 2 —
        // deux nombres contradictoires cote a cote, et personne pour savoir
        // lequel dit vrai. Ces deux-la appartiennent a « non validees », qui les
        // compte deja.
        if ($status === 'active') {
            $query->where('status', 'active')->where('workflow_step', 'etudiant_cree');

            return;
        }

        if ($status !== 'non_validee') {
            $query->where('status', $status);

            return;
        }

        $query->where(function ($q) {
            $q->where('status', 'en_attente')->orWhere(function ($subQ) {
                $subQ->where('status', 'active')
                    ->where(function ($wq) {
                        $wq->whereIn('workflow_step', ['prospect', 'documents_complets', 'en_validation'])
                            ->orWhereNull('workflow_step');
                    });
            });
        });
    }

    /** 'BTS' | 'LMD' | null (= tous systemes). */
    public function systeme(Request $request): ?string
    {
        $valeur = $request->input('systeme');

        return in_array($valeur, ['BTS', 'LMD'], true) ? $valeur : null;
    }

    /**
     * Les deux bornes de la periode demandee, au format Y-m-d, ou null.
     *
     * Le filtre porte sur la DATE D'INSCRIPTION — celle que porte le dossier —
     * et non sur la date de saisie, qui n'a de sens que pour un informaticien.
     *
     * Une date illisible est ignoree plutot que refusee : un parametre bricole
     * dans l'URL ne doit pas remplacer la liste par une page d'erreur.
     *
     * Les bornes inversees sont remises a l'endroit. Quelqu'un qui saisit « du
     * 30 septembre au 1er septembre » veut ce qui se trouve entre les deux, et
     * lui rendre zero resultat sans rien dire ne l'aide pas.
     *
     * @return array{0: string|null, 1: string|null}
     */
    public function periode(Request $request): array
    {
        $lire = static function ($valeur): ?string {
            if (! is_string($valeur) || trim($valeur) === '') {
                return null;
            }

            try {
                return \Carbon\Carbon::parse(trim($valeur))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        };

        $debut = $lire($request->input('date_debut'));
        $fin = $lire($request->input('date_fin'));

        if ($debut && $fin && $debut > $fin) {
            return [$fin, $debut];
        }

        return [$debut, $fin];
    }

    /**
     * Un filtre structure est-il pose ?
     *
     * Sert a nommer la portee a l'ecran : « la selection filtree » ne veut rien
     * dire quand rien n'est filtre.
     */
    public function auMoinsUnFiltre(Request $request): bool
    {
        foreach (['filiere', 'niveau', 'annee', 'systeme', 'mention', 'parcours', 'date_debut', 'date_fin'] as $cle) {
            if (filled($request->input($cle))) {
                return true;
            }
        }

        return $request->input('status', 'active') !== 'active';
    }

    public const TRIS = ['created_at', 'date_inscription', 'status', 'filiere_id', 'niveau_id', 'nom'];

    /**
     * Le tri de la liste, lu dans une liste blanche, applique a la requete.
     *
     * Il finit toujours par l'identifiant : sans ce departage, les lignes a
     * egalite (statut, filiere, meme seconde de creation) changent d'ordre d'une
     * tranche a l'autre, et la liste infinie en repete certaines et en saute
     * d'autres. Une recherche libre n'est pas triee ici : elle classe par score.
     *
     * @return array{0: string, 1: string} le tri et le sens retenus, pour l'ecran
     */
    public function trier(Builder $requete, Request $request): array
    {
        $tri = in_array((string) $request->input('sort'), self::TRIS, true) ? (string) $request->input('sort') : 'created_at';
        $sens = strtolower((string) $request->input('dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (filled($request->input('search'))) {
            return [$tri, $sens];
        }

        if ($tri === 'nom') {
            $requete
                ->leftJoin('esbtp_etudiants', 'esbtp_inscriptions.etudiant_id', '=', 'esbtp_etudiants.id')
                ->orderBy('esbtp_etudiants.nom', $sens)
                ->orderBy('esbtp_etudiants.prenoms', $sens)
                ->select('esbtp_inscriptions.*');
        } else {
            $requete->orderBy($tri, $sens);
        }
        $requete->orderBy('esbtp_inscriptions.id', $sens);

        return [$tri, $sens];
    }
}
