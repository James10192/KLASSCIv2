<?php

namespace App\Services\Documents;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ou vivent les pieces du dossier d'un etudiant.
 *
 * Le probleme corrige : ces fichiers etaient ecrits sur le disque `public`,
 * c'est-a-dire sous `storage/app/public`, que le lien symbolique `public/storage`
 * expose au serveur web. Le serveur les servait donc DIRECTEMENT, sans passer par
 * Laravel : ni authentification, ni permission, ni trace. Les routes de
 * telechargement etaient pourtant bien gardees — le trou n'etait pas la, il etait
 * a cote d'elles.
 *
 * Le chemin restait a deviner, mais il ne resistait pas a qui s'y met :
 * `etudiants/{id}/documents/etudiant_{id}_{horodatage}_{nom-du-fichier}.pdf`.
 * L'identifiant est sequentiel, l'horodatage tient dans une journee, et le nom
 * vient du fichier d'origine — « extrait-de-naissance », « photo », « diplome ».
 * Un extrait de naissance etait lisible par qui devinait l'adresse.
 *
 * Desormais : disque `local`, hors de portee du serveur web, et un jeton
 * aleatoire dans le nom pour que meme un acces au disque ne se devine pas.
 *
 * La lecture reste TOLERANTE : les fichiers deja ecrits sur le disque public
 * s'y trouvent encore, et ils doivent continuer de s'ouvrir tant que la commande
 * de reprise n'est pas passee sur l'instance. Le jour ou elle l'est, ce repli ne
 * trouve plus rien et ne coute rien.
 */
class StockageDocumentEtudiant
{
    /**
     * La racine des nouveaux depots, sur le disque prive.
     *
     * Differente de l'ancienne (`etudiants/{id}/documents`) a dessein : elle
     * permet de reconnaitre d'un coup d'oeil ce qui a deja ete mis a l'abri.
     */
    public const DOSSIER = 'dossiers-etudiants';

    /** Le disque prive : `storage/app`, que le serveur web ne sert pas. */
    public const DISQUE = 'local';

    /** Le disque expose, ou dorment les depots d'avant. */
    public const DISQUE_HERITE = 'public';

    /**
     * Ecrit un fichier et rend le chemin a conserver en base.
     */
    public function enregistrer(UploadedFile $fichier, int $etudiantId): string
    {
        // Un jeton aleatoire, en plus du nom d'origine : sans lui, le chemin se
        // devine a partir de l'identifiant de l'eleve et du nom du document. La
        // defense principale reste le disque prive ; celle-ci vient derriere.
        $nom = sprintf(
            '%s_%s.%s',
            Str::slug(pathinfo($fichier->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'document',
            Str::random(24),
            $fichier->getClientOriginalExtension() ?: 'bin'
        );

        return $fichier->storeAs(self::DOSSIER.'/'.$etudiantId, $nom, self::DISQUE);
    }

    /**
     * Le disque sur lequel ce chemin se trouve reellement, ou null.
     *
     * `is_file`, et non `Storage::exists()` : sur Flysystem 3, `exists()` rend
     * vrai pour un DOSSIER. Un chemin degrade en chaine vide designerait alors le
     * repertoire racine, qui existe, et le fichier passerait pour present. Piege
     * deja rencontre sur les photos.
     */
    public function disqueDe(?string $chemin): ?string
    {
        $chemin = trim((string) $chemin);

        if ($chemin === '' || str_contains($chemin, '..')) {
            return null;
        }

        foreach ([self::DISQUE, self::DISQUE_HERITE] as $disque) {
            if (is_file(Storage::disk($disque)->path($chemin))) {
                return $disque;
            }
        }

        return null;
    }

    public function existe(?string $chemin): bool
    {
        return $this->disqueDe($chemin) !== null;
    }

    /**
     * Supprime le fichier ou qu'il soit.
     *
     * Les deux disques sont essayes : un document ecrit avant la reprise vit
     * encore sur le disque expose, et ne pas l'y effacer laisserait justement en
     * place ce qu'on cherche a retirer.
     */
    public function supprimer(?string $chemin): void
    {
        $chemin = trim((string) $chemin);

        if ($chemin === '' || str_contains($chemin, '..')) {
            return;
        }

        foreach ([self::DISQUE, self::DISQUE_HERITE] as $disque) {
            if (is_file(Storage::disk($disque)->path($chemin))) {
                Storage::disk($disque)->delete($chemin);
            }
        }
    }

    /**
     * Reste-t-il une copie de ce fichier sur le disque expose ?
     *
     * La question n'est PAS « ou vit ce fichier » : c'est « reste-t-il quelque
     * chose a retirer ». Les deux se separent des qu'une reprise est coupee
     * entre la copie et l'effacement, cas ou le fichier est sur les DEUX disques.
     *
     * Le formuler par `disqueDe() === DISQUE_HERITE` rendait alors faux — cette
     * methode-la interroge le prive en premier et s'arrete au premier disque
     * trouve. La reprise concluait « deja a l'abri », n'effaçait pas la copie
     * exposee et cessait meme de la compter : la commande annonçait zero expose
     * en en laissant derriere elle.
     */
    public function estEncoreExpose(?string $chemin): bool
    {
        $chemin = trim((string) $chemin);

        if ($chemin === '' || str_contains($chemin, '..')) {
            return false;
        }

        return is_file(Storage::disk(self::DISQUE_HERITE)->path($chemin));
    }
}
