<?php

namespace App\Http\Requests\Paiement;

use Illuminate\Foundation\Http\FormRequest;

/**
 * La correction d'imputation d'un versement deja encaisse.
 *
 * Le montant n'est PAS dans les regles, et c'est le fond de l'affaire : on ne
 * corrige que la repartition. Le versement, son numero de recu, sa date et son
 * mode restent ce qu'ils sont. Accepter un montant ici ouvrirait une seconde
 * porte pour modifier de l'argent encaisse, a cote de celle qui existe deja et
 * qui a ses propres gardes.
 *
 * Le motif est OBLIGATOIRE et substantiel. Une correction sans motif ne se
 * distingue pas d'une erreur : six mois plus tard, personne ne saura si la
 * ventilation actuelle est la bonne ou la faute. Le seuil de trente caracteres
 * est celui que la reconciliation de caisse impose deja aux actions du meme
 * ordre (cf. `.claude/rules/reconciliation-paiements-caisse.md`) — assez pour
 * une phrase, trop pour un « ok ».
 */
class ReventilerPaiementRequest extends FormRequest
{
    /**
     * L'autorisation se joue dans la route (permission `paiements.reventiler`)
     * et dans le controleur, qui verifie en plus l'etat du versement lui-meme.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motif' => ['required', 'string', 'min:30', 'max:1000'],

            // frais => montant. Le total doit valoir le versement au franc
            // pres, et aucune ligne ne peut reclamer plus que son frais ne doit
            // — mais c'est le service qui le verifie
            // ({@see \App\Services\Frais\RepartitionDuVersement}), pas ce
            // formulaire : la meme regle vaut pour l'encaissement, et une
            // seconde ecriture ici finirait par en dire autre chose.
            'repartition' => ['required', 'array', 'min:1'],
            'repartition.*' => ['numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motif.required' => 'Indiquez pourquoi cette ventilation est corrigée.',
            'motif.min' => 'Le motif doit être explicite : décrivez en une phrase ce qui était faux et pourquoi (30 caractères minimum).',
            'repartition.required' => 'Indiquez sur quels frais ce versement doit être imputé.',
        ];
    }
}
