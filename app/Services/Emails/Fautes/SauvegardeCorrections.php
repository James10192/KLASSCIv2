<?php

namespace App\Services\Emails\Fautes;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * La sauvegarde ecrite et relue AVANT toute correction : table, id, colonne,
 * ancienne et nouvelle valeur. Nom unique (microsecondes et suffixe
 * aleatoire, jamais un fichier existant) : deux appels dans la meme seconde
 * ne s'ecrasent pas.
 */
class SauvegardeCorrections
{
    private const DOSSIER = 'backups';

    private const ESSAIS = 5;

    /**
     * @param  list<array<string, mixed>>  $lignes
     * @return string le nom du fichier
     *
     * @throws EchecSauvegarde sans sauvegarde relue a l'identique
     */
    public function ecrire(array $lignes): string
    {
        $disque = Storage::disk('local');
        $contenu = (string) json_encode($lignes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        for ($essai = 0; $essai < self::ESSAIS; $essai++) {
            $nom = 'emails-fautes-'.now()->format('Ymd_His_u').'-'.Str::lower(Str::random(6)).'.json';
            $chemin = self::DOSSIER.'/'.$nom;
            if ($disque->exists($chemin)) {
                continue;
            }
            if (! $disque->put($chemin, $contenu) || $disque->get($chemin) !== $contenu) {
                throw new EchecSauvegarde('Sauvegarde illisible apres ecriture : '.$nom);
            }

            return $nom;
        }

        throw new EchecSauvegarde('Aucun nom de sauvegarde libre');
    }
}
