<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Throwable;

/**
 * Fabrique un code QR. Rien d'autre.
 *
 * Ce service ne décide de RIEN : ni si l'on imprime un code sur un document, ni
 * ce que le code ouvre, ni combien de temps il vaut. Ces décisions-là
 * appartiennent à ceux qui l'appellent — {@see \App\Services\Documents\CodeQrDocument}
 * pour les documents imprimés, {@see \App\Services\Photos\CapturePhotoParTelephone}
 * pour la prise de vue au téléphone. Deux politiques, une seule fabrique.
 *
 * Rend null plutôt que d'échouer. La bibliothèque peut manquer sur une instance
 * dont les dépendances n'ont pas été réinstallées, et c'est déjà arrivé : un
 * document sans code QR reste imprimable, un document qui ne sort pas ne l'est
 * pas. À l'appelant de dire ce qu'il montre à la place.
 */
class CodeQr
{
    /**
     * Le code QR d'une adresse, en donnée embarquée (`data:image/svg+xml`).
     *
     * Embarqué et non lié : nos rendus PDF tournent avec `isRemoteEnabled` à
     * false, donc le moteur n'ira chercher aucune URL. Et à l'écran, cela évite
     * une route de plus à garder.
     */
    public function svg(?string $adresse, int $cote = 110): ?string
    {
        $adresse = trim((string) $adresse);

        if ($adresse === '') {
            return null;
        }

        try {
            $rendu = new ImageRenderer(new RendererStyle($cote, 1), new SvgImageBackEnd());
            $svg = (new Writer($rendu))->writeString($adresse);
        } catch (Throwable $e) {
            return null;
        }

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /** La bibliothèque est-elle réellement installée sur cette instance ? */
    public function disponible(): bool
    {
        return class_exists(Writer::class);
    }
}
