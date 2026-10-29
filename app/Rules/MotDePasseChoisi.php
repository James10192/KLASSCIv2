<?php

namespace App\Rules;

/**
 * Les jeux de regles d'un mot de passe saisi par un administrateur sur la
 * fiche d'un compte (creation ou modification d'un membre du personnel, d'un
 * enseignant, d'un etudiant).
 *
 * Un seul endroit pour trois raisons : la regle « jamais le mot de passe
 * generique » avait ete posee sur un ecran et oubliee sur six autres qui
 * validaient exactement la meme chose ; recopier `'nullable|string|min:8|confirmed'`
 * dans dix controleurs garantit qu'un onzieme l'oublie ; et un lecteur qui
 * cherche « quelles regles pour un mot de passe pose par un administrateur »
 * trouve une reponse, pas dix.
 *
 * Le mot de passe qu'une personne choisit pour elle-meme (premiere connexion,
 * profil, mot de passe oublie) suit d'autres regles, plus strictes, et reste
 * dans ses controleurs.
 */
final class MotDePasseChoisi
{
    /** Modification d'une fiche : le champ peut rester vide, sinon il est confirme. */
    public static function facultatif(): array
    {
        return ['nullable', 'string', 'min:8', 'confirmed', new MotDePasseNonGenerique];
    }

    /** Idem, sur les formulaires qui n'ont pas de champ de confirmation. */
    public static function facultatifNonConfirme(): array
    {
        return ['nullable', 'string', 'min:8', new MotDePasseNonGenerique];
    }

    /** Creation d'un compte dont l'administrateur fixe lui-meme le mot de passe, confirme. */
    public static function obligatoireConfirme(): array
    {
        return ['required', 'string', 'min:8', 'confirmed', new MotDePasseNonGenerique];
    }
}
