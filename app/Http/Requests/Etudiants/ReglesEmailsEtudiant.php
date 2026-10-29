<?php

namespace App\Http\Requests\Etudiants;

use App\Models\ESBTPEtudiant;
use App\Rules\EmailJoignable;

/**
 * Les regles d'adresse e-mail des ecrans etudiant (creation, edition, fiche de
 * reinscription), en un seul endroit.
 *
 * Les ecrans de ESBTPEtudiantController valident encore par `Validator::make`
 * avec leurs propres redirections : on y fusionne ces regles plutot que de
 * reecrire leur validation. A l'edition, les adresses deja en base (etudiant
 * et parents), renvoyees telles quelles par le formulaire, ne bloquent pas
 * l'enregistrement d'un autre champ : le nettoyage s'en charge.
 */
final class ReglesEmailsEtudiant
{
    /** @return array<string, list<mixed>> */
    public static function creation(bool $emailObligatoire = true): array
    {
        return [
            'email_personnel' => [$emailObligatoire ? 'required' : 'nullable', 'email', 'max:255', new EmailJoignable],
            'parents.*.email' => ['nullable', 'email', 'max:255', new EmailJoignable],
        ];
    }

    /** @return array<string, list<mixed>> */
    public static function edition(ESBTPEtudiant $etudiant): array
    {
        $enBase = self::adressesEnregistrees($etudiant);

        return [
            'email_personnel' => ['nullable', 'email', 'max:255', new EmailJoignable($enBase)],
            'parents.*.email' => ['nullable', 'email', 'max:255', new EmailJoignable($enBase)],
            'new_parent.email' => ['nullable', 'email', 'max:255', new EmailJoignable],
        ];
    }

    /** @return list<?string> */
    public static function adressesEnregistrees(ESBTPEtudiant $etudiant): array
    {
        return $etudiant->parents()->pluck('esbtp_parents.email')->push($etudiant->email_personnel, $etudiant->email)->all();
    }
}
