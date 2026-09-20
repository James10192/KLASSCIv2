<?php

namespace App\Http\Requests\Bulletin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBulletinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * ⚠️ CET ECRAN NE PASSE PAS CETTE VALIDATION, ET C'EST UN DEFAUT CONNU.
     *
     * `resources/views/esbtp/bulletins/edit.blade.php` porte TROIS formulaires
     * vers cette route, et aucun ne satisfait ces regles :
     *
     *  - « moyenne generale » (~:81) et « absences » (~:141) n'envoient aucun
     *    `resultats` — que `required|array` exige ;
     *  - « moyennes par matiere » (~:205) envoie `id`, `coefficient`,
     *    `moyenne` et `appreciation`, JAMAIS `matiere_id` — que la regle
     *    suivante exige aussi.
     *
     * Reparer demande de trancher d'abord si cet ecran doit exister :
     * `/esbtp/resultats/etudiant/{id}`, « Editer les professeurs » et
     * « Editer les absences » couvrent deja ses trois formulaires, et le
     * remettre en service ouvrirait une SECONDE source d'ecriture sur les
     * memes donnees. C'est un arbitrage produit, pas un correctif.
     *
     * La ligne `resultats.*.commentaire` a ete retiree : `commentaire` n'est
     * une colonne d'aucune des deux tables de resultats. La colonne reelle est
     * `appreciation`, et c'est bien ce que le formulaire envoie.
     */
    public function rules(): array
    {
        return [
            'resultats'                  => 'required|array',
            'resultats.*.matiere_id'     => 'required|exists:esbtp_matieres,id',
            'resultats.*.moyenne'        => 'nullable|numeric|min:0|max:20',
            'resultats.*.coefficient'    => 'required|numeric|min:0',
            'appreciation_generale'      => 'nullable|string',
            'decision_conseil'           => 'nullable|string',
        ];
    }
}
