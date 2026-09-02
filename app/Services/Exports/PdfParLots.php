<?php

namespace App\Services\Exports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sert un long document PDF, en le decoupant si besoin.
 *
 * DomPDF construit tout le document en memoire avant d'ecrire quoi que ce
 * soit : au-dela de quelques centaines de lignes il epuise la limite PHP et le
 * processus meurt sans rien laisser — ni message, ni journal. La parade
 * existait deja dans l'export du suivi par categorie, ecrite a la main dans le
 * controleur ; elle vit ici pour que les autres exports en beneficient au lieu
 * de la recopier.
 *
 * L'appelant ne choisit PAS entre les deux modes : il demande une reponse et
 * la recoit. Faire remonter le mode — « voici un PDF, ou un chemin de fichier,
 * debrouille-toi » — obligeait chaque appelant a brancher, pour une decision
 * qui ne le regarde pas.
 *
 * Les trois reperes de decoupage (`isFirstChunk`, `isLastChunk`, `rowOffset`)
 * sont TOUJOURS passes a la vue, y compris en rendu direct. Les vues n'ont donc
 * aucun defaut a se donner : un defaut declare apres sa premiere lecture est
 * precisement le defaut qui a casse l'export des petits volumes.
 */
class PdfParLots
{
    /**
     * Au-dela de ce nombre de lignes, on decoupe.
     *
     * En dessous, le rendu direct est plus rapide et produit un fichier plus
     * propre : FPDI reimporte chaque page, ce qui n'est pas gratuit.
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
     */
    public function reponse(
        string $vue,
        Collection $lignes,
        array $donnees,
        string $cleLignes,
        string $filename,
        bool $inline = false,
        string $orientation = 'portrait'
    ): Response {
        if ($lignes->count() <= self::SEUIL_DECOUPAGE) {
            $pdf = Pdf::loadView($vue, $donnees + [
                $cleLignes => $lignes,
                'isFirstChunk' => true,
                'isLastChunk' => true,
                'rowOffset' => 0,
            ])->setPaper('a4', $orientation);

            if (! $inline) {
                return $pdf->download($filename);
            }

            return new \Illuminate\Http\Response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
        }

        // Le decoupage echange de la memoire contre du temps : sans ces deux
        // relevements, on troquerait un mode de panne contre un autre.
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $chemin = $this->fusionner($vue, $lignes, $donnees, $cleLignes, $orientation);

        // Un gros document passe par un fichier temporaire : le relire d'un
        // bloc en memoire annulerait le benefice du decoupage, on le diffuse.
        if ($inline) {
            return response()->file($chemin, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                'X-Robots-Tag' => 'noindex, nofollow',
            ])->deleteFileAfterSend(true);
        }

        return response()->download($chemin, $filename)->deleteFileAfterSend(true);
    }

    /**
     * @param  Collection<int, mixed>  $lignes
     * @param  array<string, mixed>  $donnees
     * @return string Chemin du fichier fusionne.
     */
    private function fusionner(
        string $vue,
        Collection $lignes,
        array $donnees,
        string $cleLignes,
        string $orientation
    ): string {
        $dossier = storage_path('app/temp');
        if (! is_dir($dossier)) {
            mkdir($dossier, 0755, true);
        }

        $lots = $lignes->chunk(self::LIGNES_PAR_LOT)->values();
        $dernier = $lots->count() - 1;
        $fichiers = [];

        foreach ($lots as $index => $lot) {
            $pdf = Pdf::loadView($vue, $donnees + [
                $cleLignes => $lot,
                'isFirstChunk' => $index === 0,
                'isLastChunk' => $index === $dernier,
                'rowOffset' => $index * self::LIGNES_PAR_LOT,
            ])->setPaper('a4', $orientation);

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
