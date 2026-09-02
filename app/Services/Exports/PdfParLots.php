<?php

namespace App\Services\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use setasign\Fpdi\Fpdi;

/**
 * Rend un long document PDF en morceaux, puis les recolle.
 *
 * DomPDF construit tout le document en memoire avant d'ecrire quoi que ce
 * soit : au-dela de quelques centaines de lignes il epuise la limite PHP et le
 * processus meurt sans rien laisser — ni message, ni journal. La parade
 * existait deja dans l'export du suivi par categorie, ecrite a la main dans le
 * controleur ; elle vit ici pour que les autres exports en beneficient au lieu
 * de la recopier.
 *
 * La vue recoit `isFirstChunk`, `isLastChunk` et `rowOffset` : a elle de ne
 * poser ses indicateurs qu'en tete, son recapitulatif qu'en fin, et de
 * numeroter ses lignes en continu. Une vue qui ignore ces variables reste
 * correcte — elle repetera simplement ses entetes.
 */
class PdfParLots
{
    /**
     * Au-dela de ce nombre de lignes, on decoupe.
     *
     * En dessous, le rendu direct est plus rapide et produit un fichier plus
     * propre (pas de passage par FPDI, qui reimporte chaque page).
     */
    public const SEUIL_DECOUPAGE = 400;

    /**
     * Nombre de lignes par morceau.
     */
    public const LIGNES_PAR_LOT = 200;

    /**
     * @param  Collection<int, mixed>  $lignes  Ce qui doit etre reparti entre les lots.
     * @param  array<string, mixed>  $donnees  Passe tel quel a la vue, a chaque lot.
     * @param  string  $cleLignes  Nom sous lequel la vue attend le lot courant.
     * @return string Chemin du fichier fusionne. A l'appelant de le servir avec
     *                `deleteFileAfterSend`.
     */
    public function rendre(
        string $vue,
        Collection $lignes,
        array $donnees,
        string $cleLignes,
        string $orientation = 'portrait'
    ): string {
        $dossier = storage_path('app/temp');
        if (! is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        $lots = $lignes->chunk(self::LIGNES_PAR_LOT)->values();
        $dernier = $lots->count() - 1;
        $fichiers = [];

        foreach ($lots as $index => $lot) {
            $pdf = Pdf::loadView($vue, array_merge($donnees, [
                $cleLignes => $lot,
                'isFirstChunk' => $index === 0,
                'isLastChunk' => $index === $dernier,
                'rowOffset' => $index * self::LIGNES_PAR_LOT,
            ]))->setPaper('a4', $orientation);

            $chemin = $dossier.'/lot_'.uniqid('', true).'_'.$index.'.pdf';
            file_put_contents($chemin, $pdf->output());
            $fichiers[] = $chemin;

            // Rendre la memoire entre deux lots : sans ca, decouper ne servirait
            // a rien, tous les lots s'accumuleraient.
            unset($pdf);
        }

        $fusion = new Fpdi();
        foreach ($fichiers as $fichier) {
            $pages = $fusion->setSourceFile($fichier);
            for ($page = 1; $page <= $pages; $page++) {
                $modele = $fusion->importPage($page);
                $taille = $fusion->getTemplateSize($modele);
                $fusion->AddPage($taille['orientation'], [$taille['width'], $taille['height']]);
                $fusion->useTemplate($modele);
            }
        }

        $final = $dossier.'/fusion_'.uniqid('', true).'.pdf';
        $fusion->Output('F', $final);
        unset($fusion);

        foreach ($fichiers as $fichier) {
            @unlink($fichier);
        }

        return $final;
    }
}
