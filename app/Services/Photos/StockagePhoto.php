<?php

namespace App\Services\Photos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Le seul endroit ou une photo de personne s'ecrit, se lit et s'efface.
 *
 * Avant ce service, trois ecrans ecrivaient la meme colonne `photo` de trois
 * facons differentes :
 *
 *   - le formulaire d'inscription y mettait un NOM DE FICHIER nu, le fichier
 *     vivant dans `photos/etudiants` ;
 *   - l'ecran d'edition de la fiche y mettait une URL `/storage/etudiants/photos/…` ;
 *   - le bouton camera y mettait un CHEMIN RELATIF `photos/…`.
 *
 * Et deux lecteurs les interpretaient differemment. Consequence concrete, pas
 * theorique : une photo posee depuis l'ecran d'edition ressortait en
 * `storage/photos/etudiants//storage/etudiants/photos/x.jpg` — une image cassee,
 * sur les tableaux de bord, les bulletins et la messagerie.
 *
 * Deux regles, donc :
 *
 * 1. On ECRIT toujours la meme forme : un chemin relatif au disque `public`.
 * 2. On LIT toutes les formes, y compris les anciennes. Les valeurs deja en base
 *    ne se reecrivent pas : elles se comprennent.
 */
class StockagePhoto
{
    /**
     * Le dossier ou tout atterrit desormais.
     *
     * C'est celui que l'accesseur du modele essayait deja en premier : les photos
     * d'inscription, les plus nombreuses, y sont deja.
     */
    public const DOSSIER = 'photos/etudiants';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Range une photo et rend la valeur a stocker en base.
     */
    public function enregistrer(UploadedFile $fichier, string $prefixe = 'photo'): string
    {
        $nom = Str::slug($prefixe).'-'.Str::random(16).'.'.$this->extension($fichier);

        return $fichier->storeAs(self::DOSSIER, $nom, 'public');
    }

    /**
     * L'adresse publique d'une photo, quelle que soit la forme stockee.
     */
    public function url(?string $valeur): ?string
    {
        $chemin = $this->chemin($valeur);

        return $chemin === null ? null : Storage::disk('public')->url($chemin);
    }

    /**
     * Efface le fichier, quelle que soit la forme stockee. Silencieux s'il a
     * deja disparu : effacer une photo absente n'est pas une erreur.
     */
    public function supprimer(?string $valeur): void
    {
        $chemin = $this->chemin($valeur);

        if ($chemin !== null && Storage::disk('public')->exists($chemin)) {
            Storage::disk('public')->delete($chemin);
        }
    }

    /**
     * Ramene n'importe quelle forme historique au chemin relatif du disque public.
     *
     * Rend null quand le fichier reste introuvable : mieux vaut afficher
     * l'image par defaut qu'une vignette brisee.
     */
    public function chemin(?string $valeur): ?string
    {
        $valeur = trim((string) $valeur);

        if ($valeur === '') {
            return null;
        }

        foreach ($this->candidats($valeur) as $candidat) {
            if ($candidat !== '' && Storage::disk('public')->exists($candidat)) {
                return $candidat;
            }
        }

        return null;
    }

    /**
     * Les endroits ou la photo peut se trouver, du plus probable au moins.
     */
    private function candidats(string $valeur): array
    {
        // Une URL absolue ou racine : on ne garde que ce qui suit `/storage/`.
        $nu = preg_replace('#^https?://[^/]+#i', '', $valeur);
        $nu = ltrim((string) $nu, '/');
        $nu = preg_replace('#^storage/#', '', $nu);
        $nu = ltrim((string) $nu, '/');

        $base = basename($nu);

        return array_values(array_unique([
            $nu,                              // chemin relatif deja canonique
            self::DOSSIER.'/'.$nu,            // nom nu ecrit par l'inscription
            self::DOSSIER.'/'.$base,          // meme fichier, range depuis
            'etudiants/photos/'.$base,        // ancien dossier de l'ecran d'edition
            'photos/'.$base,                  // ancien dossier du bouton camera
        ]));
    }

    /**
     * L'extension deduite du CONTENU, jamais du nom envoye par le navigateur.
     *
     * `getClientOriginalExtension()` rend ce que le client a bien voulu ecrire.
     * Sur un disque public, cela revient a laisser choisir l'extension du fichier
     * depose a la personne qui le depose.
     */
    private function extension(UploadedFile $fichier): string
    {
        $extension = strtolower((string) $fichier->extension());

        return in_array($extension, self::EXTENSIONS, true) ? $extension : 'jpg';
    }
}
