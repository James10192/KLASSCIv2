<?php

namespace App\Services\Inscription;

use App\Domain\Notifications\PhoneFormatter;
use App\Models\ESBTPCandidature;
use App\Services\Reinscription\PortailReinscriptionService;

/**
 * Ce qu'une candidature acceptee donne au formulaire d'inscription.
 *
 * Une lecture pure, sortie du controleur d'inscription : elle n'y decidait
 * rien, elle y ajoutait soixante lignes de correspondance champ a champ a un
 * fichier qui en compte deja pres de trois mille.
 *
 * Le droit d'agir, lui, reste au controleur : savoir qui peut ouvrir un
 * dossier de candidature n'est pas la meme question que savoir ce qu'il y a
 * dedans.
 */
final class PreRemplissageCandidature
{
    /**
     * La candidature designee, si elle est bien a inscrire ET si l'on a le
     * droit de la lire.
     *
     * Seule une candidature ACCEPTEE se pre-remplit, et c'est le meme statut
     * que la fermeture exigera a l'enregistrement. Sans ce filtre, une adresse
     * modifiee a la main ouvrirait un formulaire pre-rempli depuis un dossier
     * rejete, sous un bandeau affirmant qu'il vient de la candidature — puis la
     * fermeture refuserait de lier quoi que ce soit, en silence.
     *
     * Le DROIT est verifie ici, et non chez les appelants. Il l'etait dans deux
     * des trois : l'ouverture du formulaire et la fermeture le demandaient, le
     * garde de date de naissance non. Or ce garde repond en clair — « la date
     * saisie (1999-01-01) ne correspond pas a celle de la candidature en ligne
     * (2007-03-15) ». Un agent qui a `inscriptions.create` sans voir la
     * corbeille pouvait donc lire la date de naissance d'un candidat par
     * identifiant, un a la fois, en postant le formulaire. La regle que le
     * projet s'ecrit a lui-meme — « deux moities qui ne gardent pas la meme
     * porte n'en gardent aucune » — vaut a trois portes comme a deux, et la
     * seule facon de ne plus en oublier est de garder le passage, pas les
     * passants.
     */
    public static function acceptee(int $id): ?ESBTPCandidature
    {
        if ($id <= 0 || ! auth()->user()?->can('inscriptions.candidatures.process')) {
            return null;
        }

        return ESBTPCandidature::whereKey($id)
            ->where('statut', ESBTPCandidature::STATUT_ACCEPTEE)
            ->first();
    }

    /**
     * La date saisie s'ecarte-t-elle de celle du dossier depose ?
     *
     * Une date ILLISIBLE n'est pas une divergence : la validation la refusera
     * avec un message qui parle d'elle. Repondre « ne correspond pas a la
     * candidature » pour une case vide enverrait l'agent chercher a cote.
     *
     * Meme raison cote candidature : le champ est obligatoire au depot, donc
     * une candidature sans date est une anomalie de donnees, pas un signal sur
     * l'identite de qui l'on inscrit.
     */
    public static function naissanceDiverge(ESBTPCandidature $candidature, string $saisie): bool
    {
        $deposee = optional($candidature->date_naissance)->toDateString();
        $saisie = trim($saisie);

        if ($deposee === null || $saisie === '') {
            return false;
        }

        $lue = PortailReinscriptionService::interpreterDateIso($saisie);

        return $lue !== null && $lue->toDateString() !== $deposee;
    }

    /**
     * Les valeurs de depart, limitees aux champs que le formulaire porte.
     *
     * Les champs vides sont retires plutot qu'envoyes vides, pour que le
     * formulaire garde ses propres valeurs par defaut.
     *
     * Le telephone est remis en forme lisible : il est stocke en E.164
     * (« +2250707121234 ») parce qu'il sert de cle d'unicite au canal public,
     * et c'est un agent qui va le composer.
     *
     * Rien sur le tuteur ici. Le formulaire ne l'expose pas en champs plats
     * mais en `parents[0][...]`, remplis par le bouton « Reprendre ce tuteur »
     * du bandeau. Trois cles `tuteur_*` ont vecu dans ce tableau sans
     * qu'aucun champ ne porte leur nom : `old()` les cherchait, ne les
     * trouvait jamais, et le seul effet visible etait de faire croire au
     * lecteur que le tuteur se pre-remplissait tout seul.
     *
     * @return array<string, string>
     */
    public static function valeurs(ESBTPCandidature $candidature): array
    {
        $valeurs = [
            'nom' => $candidature->nom,
            'prenoms' => $candidature->prenoms,
            'sexe' => $candidature->sexe,
            'date_naissance' => $candidature->date_naissance?->format('Y-m-d'),
            'lieu_naissance' => $candidature->lieu_naissance,
            'nationalite' => $candidature->nationalite,
            'telephone' => PhoneFormatter::toReadable($candidature->telephone) ?: $candidature->telephone,
            'email_personnel' => $candidature->email,
            'ville' => $candidature->ville,
            'commune' => $candidature->commune,
            'affectation_status' => $candidature->affectation_status,
        ];

        return array_filter($valeurs, static fn ($v) => $v !== null && $v !== '');
    }
}
