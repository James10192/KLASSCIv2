<?php

namespace App\Http\Controllers\RendezVous;

use App\Http\Controllers\Controller;
use App\Services\RendezVous\ConfigurationRendezVous;
use App\Services\RendezVous\GenerateurCreneaux;
use App\Services\RendezVous\VolumeARecevoir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ce que la grille donnerait, avant d'enregistrer quoi que ce soit.
 *
 * L'ecran de reglage doit repondre en direct a la seule question qui compte :
 * « a ce rythme, ai-je le temps de recevoir tout le monde ? ». Une ecole qui
 * saisit 8h-16h, une heure de pause et un quart d'heure par famille obtient
 * vingt-huit places par jour. Avec deux mille quatre cent cinquante eleves,
 * cela fait quatre-vingt-huit jours d'ouverture. Si l'ecran ne le dit pas au
 * moment de la saisie, l'ecole le decouvre en novembre.
 *
 * Le calcul se fait ICI et non dans le navigateur, alors que ce serait plus
 * simple. Le recopier en JavaScript donnerait deux arithmetiques a maintenir
 * pour une seule regle, et c'est la forme d'erreur que ce depot connait le
 * mieux : deux gardiens d'une meme porte finissent par apprendre la consigne
 * separement. L'ecran montrerait alors un nombre de places que la grille reelle
 * ne produit pas.
 *
 * Rien n'est enregistre. C'est un apercu, sur le modele de l'apercu PDF des
 * parametres : les valeurs viennent du formulaire en cours de saisie, pas de la
 * base.
 */
class ApercuGrilleController extends Controller
{
    public function __construct(private readonly VolumeARecevoir $volume) {}

    public function __invoke(Request $request): JsonResponse
    {
        // `$request->all()` et non `input()` : les cles de reglage contiennent
        // des points, et la notation a points de Laravel y lirait un chemin
        // imbrique. `inscriptions.rdv.duree_minutes` deviendrait une recherche
        // dans un tableau `inscriptions` qui n'existe pas — et la valeur
        // ressortirait vide, donc refusee, sans que rien ne l'explique.
        $soumis = $request->all();

        $config = ConfigurationRendezVous::depuisLesValeurs($soumis);
        $grille = new GenerateurCreneaux($config);

        $aRecevoir = $this->volume->pourLaRentree();
        $joursNecessaires = $grille->joursNecessairesPour($aRecevoir['total']);

        return response()->json([
            'complete' => $config->estComplete(),
            'problemes' => $config->problemes,

            'creneaux_par_jour' => $grille->creneauxParJour(),

            // Le chiffre que l'ecole voulait saisir, et qu'elle lit desormais.
            'places_par_jour' => $grille->placesParJour(),

            'jours_de_reception' => $grille->joursDeReception(),
            'places_sur_la_campagne' => $grille->placesSurLaCampagne(),

            'jours_necessaires' => $joursNecessaires,

            'verdict' => $this->verdict($grille, $aRecevoir, $joursNecessaires),
        ]);
    }

    /**
     * La phrase, et pas seulement les chiffres.
     *
     * Afficher « 28 » et « 2450 » cote a cote suppose que le lecteur fasse la
     * division. Il ne la fera pas : il lira deux nombres, trouvera le premier
     * raisonnable, et passera au champ suivant.
     *
     * @param  array{annee: ?string, reinscriptions: int, candidatures: int, total: int}  $aRecevoir
     */
    private function verdict(GenerateurCreneaux $grille, array $aRecevoir, ?int $joursNecessaires): ?string
    {
        if (! $grille->configuration()->estComplete()) {
            return null;
        }

        if ($aRecevoir['annee'] === null) {
            return "Renseignez l'annee visee par les inscriptions pour savoir combien de familles vous avez a recevoir.";
        }

        if ($aRecevoir['total'] === 0) {
            return "Aucune famille n'est en attente de rendez-vous pour {$aRecevoir['annee']} : votre grille n'a rien a absorber pour l'instant.";
        }

        if ($joursNecessaires === null) {
            return null;
        }

        $disponibles = $grille->joursDeReception();
        $s = $aRecevoir['candidatures'] > 1 ? 's' : '';

        // Le detail du total, parce que ses deux moities ne se comportent pas
        // pareil : les reinscriptions sont un effectif connu qui ne bougera
        // plus, les candidatures un constat a l'instant T qui grossira toute la
        // campagne. Les additionner sans le dire ferait prendre le total pour
        // une prevision.
        $constat = "A ce rythme, il vous faut {$joursNecessaires} jour".($joursNecessaires > 1 ? 's' : '')
            ." de reception pour recevoir les {$aRecevoir['total']} familles attendues"
            ." ({$aRecevoir['reinscriptions']} a reinscrire, plus {$aRecevoir['candidatures']} candidature{$s}"
            ." deja deposee{$s} — ce second nombre continuera d'augmenter).";

        return $constat.' '.match (true) {
            $disponibles <= 0 => "Votre periode n'en compte aucun : verifiez les dates et les jours d'ouverture.",
            $joursNecessaires > $disponibles => "Votre periode n'en compte que {$disponibles}. Allongez-la, "
                ."ouvrez plus de jours, ou recevez plus de familles a la fois.",
            default => "Votre periode en compte {$disponibles} : c'est suffisant.",
        };
    }
}
