<?php

namespace App\Services\Photos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

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

    /** @var array<string, string|null> resolutions deja faites dans cette requete */
    private array $resolus = [];

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Range une photo et rend la valeur a stocker en base.
     */
    public function enregistrer(UploadedFile $fichier, string $prefixe = 'photo'): string
    {
        $nom = Str::slug($prefixe).'-'.Str::random(16).'.'.$this->extension($fichier);

        $chemin = $fichier->storeAs(self::DOSSIER, $nom, 'public');

        // storeAs() rend false si l ecriture echoue — quota ou permissions sur un
        // hebergement mutualise. Sans strict_types, false devient '' : l appelant
        // enregistrait une photo vide en base APRES avoir efface l ancienne, et
        // repondait « Photo mise a jour avec succes ». On refuse bruyamment.
        if ($chemin === false || $chemin === '') {
            throw new RuntimeException("La photo n'a pas pu être enregistrée sur le disque.");
        }

        return $chemin;
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

        // Oublier la resolution : sans cela, une photo remplacee dans la meme
        // requete rendrait encore l ancien chemin, desormais efface.
        unset($this->resolus[trim((string) $valeur)]);
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

        // Memorise par valeur brute. Les vues font systematiquement
        // `@if($x->photo_url)` puis `src="{{ $x->photo_url }}"` : sans cela, une
        // liste de cinquante etudiants resout cent fois, et le cas le plus
        // frequent — la fiche sans photo — est celui qui parcourt TOUS les
        // candidats. Meme geste que SettingsHelper::resolveLogoPath().
        if (array_key_exists($valeur, $this->resolus)) {
            return $this->resolus[$valeur];
        }

        foreach ($this->candidats($valeur) as $candidat) {
            // is_file, et non Storage::exists() : sur Flysystem 3, exists() rend
            // vrai pour un DOSSIER. Une valeur comme '/storage/' normalise en
            // chaine vide, les candidats deviennent 'photos/etudiants/' — un
            // dossier, qui existe. On rendrait alors une URL de dossier, et pire :
            // le `@if($x->photo_url)` des vues passerait a vrai, supprimant le
            // repli aux initiales. Piege deja documente dans SettingsHelper.
            if (is_file(Storage::disk('public')->path($candidat))) {
                return $this->resolus[$valeur] = $candidat;
            }
        }

        return $this->resolus[$valeur] = null;
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

        // Storage::path() concatene sans normaliser : une valeur contenant « .. »
        // sortirait du disque public. La colonne est alimentee par des ecrans, donc
        // par l exterieur — on ne lui fait pas confiance.
        if ($base === '' || str_contains($nu, '..')) {
            return [];
        }

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
