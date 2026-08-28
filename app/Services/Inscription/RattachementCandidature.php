<?php

namespace App\Services\Inscription;

use App\Enums\ClotureCandidature;
use App\Models\ESBTPInscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Le lien entre une inscription qu'on saisit et la candidature dont elle vient.
 *
 * Les deux moities d'une meme question — « est-ce bien ce candidat-la ? » avant
 * la creation, « on ferme son dossier » apres — et les phrases que l'agent lit.
 * Elles vivaient dans ESBTPInscriptionController, un fichier de pres de trois
 * mille lignes ou les trois messages de cloture se trouvaient a deux mille
 * lignes de l'enum dont ils sont la traduction : le jour ou un cas s'ajoute,
 * rien ne relie les deux endroits.
 *
 * Ce qui reste au controleur : appeler, et rendre. Deux lignes.
 */
final class RattachementCandidature
{
    public function __construct(private readonly PortailCandidatureService $candidatures) {}

    /**
     * La date saisie correspond-elle a la candidature qu'on inscrit ?
     *
     * Posee AVANT la creation, et c'est tout l'interet. Le meme controle apres
     * coup arrivait une fois l'inscription committee : il ne pouvait plus que
     * constater, laisser la candidature ouverte pour toujours — rien, dans la
     * corbeille, ne ferme un dossier « acceptee » — et renvoyer l'agent vers un
     * bouton « Creer l'inscription » toujours affiche. En le suivant, il creait
     * un second etudiant : exactement le doublon que la cloture existe pour
     * empecher. Ici, la decision est encore reversible.
     *
     * Le cas frequent n'est pas l'erreur d'aiguillage, c'est la correction : le
     * candidat a tape 2007 sur son telephone, la piece d'identite dit 2006, et
     * corriger est le geste normal de l'etape sur place. D'ou une confirmation
     * a cocher plutot qu'un refus sec — meme forme que la detection de doublons,
     * qui pose la meme question a propos d'une autre donnee.
     *
     * Le cas rare, lui, est celui qui coute : l'agent ouvre le formulaire
     * pre-rempli d'Aya puis inscrit le visiteur arrive au guichet en reecrivant
     * les champs. `candidature_id` voyage dans un champ cache, invisible, et
     * suit — Aya se retrouverait close et rattachee a l'etudiant d'un autre,
     * sans retour possible.
     *
     * @return RedirectResponse|null null si l'on peut creer
     */
    public function refuserSiNaissanceDivergente(Request $request)
    {
        if ($request->boolean('candidature_naissance_confirmee')) {
            return null;
        }

        $candidature = PreRemplissageCandidature::acceptee((int) $request->input('candidature_id'));

        if ($candidature === null) {
            return null;
        }

        $saisie = trim((string) $request->input('date_naissance'));

        if (! PreRemplissageCandidature::naissanceDiverge($candidature, $saisie)) {
            return null;
        }

        $deposee = optional($candidature->date_naissance)->toDateString();

        return redirect()
            ->back()
            ->withInput()
            ->withErrors([
                'candidature_naissance' => "La date de naissance saisie ({$saisie}) ne correspond pas à celle de la candidature en ligne ({$deposee}). S'il s'agit bien du même candidat, cochez la confirmation puis renvoyez le formulaire ; sinon, revenez à la corbeille et ouvrez le bon dossier.",
            ]);
    }

    /**
     * Ferme la candidature dont cette inscription est issue.
     *
     * Sans cela, la corbeille garderait eternellement son bouton « Creer
     * l'inscription » sur un dossier deja traite : deux agents presses pendant
     * la rentree en tireraient deux etudiants, et l'onglet « Inscrite »
     * afficherait zero pour toujours.
     *
     * Le droit exige est `inscriptions.candidatures.process`, celui de la
     * corbeille — sans lui, un caissier retirerait un dossier de la corbeille
     * par un simple champ cache. La bascule, elle, vit dans
     * PortailCandidatureService, a cote de la garde symetrique du portail.
     *
     * Aucune issue ne fait echouer la requete : l'inscription est deja
     * enregistree, et la faire echouer ici la laisserait en base avec une
     * erreur a l'ecran. L'agent est averti par le message rendu — un journal ne
     * se lit pas depuis la scolarite.
     *
     * @return string|null l'avertissement a montrer a l'agent, s'il y a lieu
     */
    public function fermer(Request $request, ?ESBTPInscription $inscription): ?string
    {
        $id = (int) $request->input('candidature_id');

        if ($id <= 0 || $inscription === null) {
            return null;
        }

        if (! auth()->user()?->can('inscriptions.candidatures.process')) {
            Log::warning('Fermeture de candidature refusee : droit manquant', [
                'candidature_id' => $id,
                'user_id' => auth()->id(),
            ]);

            // Ce lecteur-la ne voit pas la corbeille : toute la colonne
            // d'actions y est sous `@can('inscriptions.candidatures.process')`.
            // Lui parler d'un bouton qu'il n'a jamais vu, ou lui demander de
            // fermer un dossier auquel il n'a pas acces, ne l'aiderait pas. On
            // lui dit ce qui le concerne : son inscription est faite, et
            // quelqu'un d'autre doit savoir que la candidature reste ouverte.
            return "L'inscription est enregistrée. La candidature en ligne correspondante n'a pas été clôturée : vous n'avez pas le droit de traiter les candidatures. Signalez-le à la scolarité.";
        }

        try {
            $issue = $this->candidatures->fermerApresInscription($id, $inscription, auth()->id());
        } catch (\Throwable $e) {
            Log::error('Echec de la fermeture de candidature', [
                'candidature_id' => $id,
                'inscription_id' => $inscription->id,
                'message' => $e->getMessage(),
            ]);

            // Aucun bouton ne clot une candidature « acceptée » : on dit donc
            // ce qui est vrai et ce qui est faisable, et on n'envoie pas
            // l'agent chercher une action qui n'existe pas.
            return "L'inscription est enregistrée, mais la candidature en ligne est restée « acceptée » dans la corbeille : son bouton « Créer l'inscription » est toujours affiché, ne vous en resservez pas. Signalez-le au support.";
        }

        if ($issue !== ClotureCandidature::Fermee) {
            Log::warning('Candidature non fermee', [
                'candidature_id' => $id,
                'inscription_id' => $inscription->id,
                'issue' => $issue->value,
            ]);
        }

        // Les deux issues sont des fins normales, et elles ne demandent pas la
        // meme chose a l'agent — d'ou deux phrases, et non une seule qui serait
        // fausse une fois sur deux.
        return match ($issue) {
            ClotureCandidature::Fermee => null,

            // Un collegue l'a traitee entre-temps. Ce n'est plus le canal
            // public : depuis que `refusDeRedepot()` refuse toute reouverture d'une
            // candidature acceptee, un redepot ne peut plus lui faire changer
            // d'etat sous les pieds de l'agent.
            ClotureCandidature::DejaChangee => "L'inscription est enregistrée, mais la candidature en ligne n'a pas pu être marquée comme traitée : elle avait déjà changé d'état. Vérifiez la corbeille des candidatures.",
        };
    }
}
