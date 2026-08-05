<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Fusionne N bulletins (rendus individuellement par le pipeline DomPDF existant)
 * en un seul PDF via FPDI. Reprend le pattern chunk+merge éprouvé du repo
 * (voir ESBTPStudentController / pdf-export-patterns) : chaque bulletin est écrit
 * dans un fichier temporaire, puis tous sont concaténés page à page.
 *
 * Le rendu par bulletin est délégué au caller via un closure `$renderer` afin de
 * réutiliser EXACTEMENT le rendu du téléchargement unitaire (aucune source de
 * vérité dupliquée). Un bulletin qui échoue est ignoré et consigné, sans casser
 * l'export global.
 */
class BulletinBulkPdfExporter
{
    /** Nombre de bulletins entre deux passes de garbage collection. */
    private int $gcEvery = 25;

    /**
     * @param  Collection  $bulletins  Collection ORDONNÉE de bulletins à exporter.
     * @param  callable  $renderer  fn(ESBTPBulletin): \Barryvdh\DomPDF\PDF — rend un bulletin.
     * @return array{path: string, rendered: int, failed: array<int, array{id: int, message: string}>}
     */
    public function export(Collection $bulletins, callable $renderer): array
    {
        // Gardes mémoire/temps : un export groupé est plus lourd qu'un bulletin seul.
        // On n'ÉLÈVE la limite mémoire que si la valeur courante est plus basse
        // (ne jamais rabaisser un serveur mieux doté).
        $this->raiseMemoryLimit('512M');
        @set_time_limit(300);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir) && ! mkdir($tempDir, 0755, true) && ! is_dir($tempDir)) {
            throw new \RuntimeException("Impossible de créer le dossier temporaire d'export : $tempDir");
        }

        $tempFiles = [];
        $failed = [];
        $rendered = 0;
        $i = 0;

        foreach ($bulletins as $bulletin) {
            try {
                $pdf = $renderer($bulletin);
                $tempPath = $tempDir.'/blt_'.uniqid('', true).'.pdf';
                file_put_contents($tempPath, $pdf->output());
                $tempFiles[] = $tempPath;
                $rendered++;
                unset($pdf);
            } catch (\Throwable $e) {
                $failed[] = ['id' => (int) $bulletin->id, 'message' => $e->getMessage()];
                Log::error('BulletinBulkPdfExporter: rendu échoué bulletin #'.$bulletin->id.' — '.$e->getMessage());
            }

            if ((++$i % $this->gcEvery) === 0) {
                gc_collect_cycles();
            }
        }

        if (empty($tempFiles)) {
            throw new \RuntimeException("Aucun bulletin n'a pu être rendu.");
        }

        try {
            $finalPath = $this->mergePdfs($tempFiles, $tempDir);
        } finally {
            foreach ($tempFiles as $file) {
                @unlink($file);
            }
        }

        return ['path' => $finalPath, 'rendered' => $rendered, 'failed' => $failed];
    }

    /**
     * Fusionne 1:1 les PDF temporaires en préservant marges/format de chaque page.
     *
     * @param  array<int, string>  $tempFiles
     */
    private function mergePdfs(array $tempFiles, string $tempDir): string
    {
        $merger = new \setasign\Fpdi\Fpdi();
        $merger->SetAutoPageBreak(false); // OBLIGATOIRE : sinon sauts de page intempestifs.

        foreach ($tempFiles as $file) {
            $pageCount = $merger->setSourceFile($file);
            for ($p = 1; $p <= $pageCount; $p++) {
                $tpl = $merger->importPage($p);
                $size = $merger->getTemplateSize($tpl);
                $merger->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $merger->useTemplate($tpl, 0, 0, $size['width'], $size['height']); // x,y,w,h OBLIGATOIRES.
            }
        }

        $finalPath = $tempDir.'/bulletins_export_'.uniqid('', true).'.pdf';
        $merger->Output('F', $finalPath);
        unset($merger);

        return $finalPath;
    }

    /**
     * Élève la limite mémoire vers $target uniquement si la valeur courante est
     * plus basse. Ne rabaisse jamais un serveur mieux doté (-1 = illimité).
     */
    private function raiseMemoryLimit(string $target): void
    {
        $current = $this->parseBytes((string) ini_get('memory_limit'));
        if ($current === -1) {
            return; // illimité : ne rien changer.
        }
        if ($current < $this->parseBytes($target)) {
            @ini_set('memory_limit', $target);
        }
    }

    /** Convertit une valeur ini ("512M", "1G", "-1") en octets. -1 => illimité. */
    private function parseBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $unit = strtolower($value[strlen($value) - 1]);
        $num = (int) $value;

        return match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $value,
        };
    }
}
