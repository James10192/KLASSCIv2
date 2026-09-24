<?php

namespace App\Services\RendezVous\Rattrapage;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Les adresses de reservation videes par `emails:nettoyer-factices`, telles
 * que leur sauvegarde les garde (disque `local`, `backups/emails-factices-*.json`,
 * ecrit par NettoyageAdressesFactices::sauvegarder). Seule leur empreinte
 * sha256 sort d'ici : c'est ce qui permet d'exiger, pour une reservation sans
 * adresse, le courriel parti vers SON ancienne adresse et non n'importe quelle
 * adresse fabriquee du meme dossier.
 *
 * Une sauvegarde illisible est ignoree et signalee : la reservation retombe
 * alors sur la regle du domaine fabrique.
 */
class AnciennesAdresses
{
    private const DOSSIER = 'backups';

    private const MOTIF = '/^emails-factices-\d{8}_\d{6}\.json$/';

    private const TABLE = 'esbtp_rdv_reservations';

    /** @return array<int, string> identifiant de reservation => sha256 de l'ancienne adresse */
    public function empreintesDesReservations(): array
    {
        $disque = Storage::disk('local');
        $fichiers = array_filter($disque->files(self::DOSSIER), fn (string $f) => preg_match(self::MOTIF, basename($f)) === 1);
        sort($fichiers); // horodates dans le nom : le plus recent l'emporte

        $empreintes = [];
        foreach ($fichiers as $fichier) {
            $lignes = json_decode((string) $disque->get($fichier), true);
            if (! is_array($lignes)) {
                Log::warning('Rattrapage convocations : sauvegarde illisible', ['fichier' => basename($fichier)]);

                continue;
            }
            foreach ($lignes as $ligne) {
                if (($ligne['table'] ?? null) === self::TABLE && ($ligne['colonne'] ?? null) === 'email'
                    && is_string($ligne['ancienne_valeur'] ?? null) && trim($ligne['ancienne_valeur']) !== '') {
                    $empreintes[(int) $ligne['id']] = MessageConvocation::empreinte($ligne['ancienne_valeur']);
                }
            }
        }

        return $empreintes;
    }
}
