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
    /**
     * Durée de vie des restes d'un export, en minutes.
     *
     * Un export abandonné laisse son dossier de travail, parfois son PDF
     * assemblé. Au-delà de ce délai, la purge les balaie.
     */
    public const DUREE_VIE_MINUTES = 60;

    /**
     * Dossier de travail d'un export découpé.
     *
     * Un export d'une classe entière ne tient pas dans une requête : sept
     * bulletins consomment déjà trente secondes. Chaque tranche écrit donc ses
     * PDF dans un dossier propre à la session, et l'assemblage n'a plus qu'à
     * les concaténer.
     */
    public function dossierDeSession(string $jeton): string
    {
        $chemin = storage_path('app/temp/export_'.preg_replace('/[^a-z0-9]/i', '', $jeton));

        if (! is_dir($chemin) && ! mkdir($chemin, 0755, true) && ! is_dir($chemin)) {
            throw new \RuntimeException("Impossible de créer le dossier de session d'export : $chemin");
        }

        return $chemin;
    }

    /**
     * Rend une tranche de bulletins dans le dossier de session.
     *
     * Les fichiers sont numérotés sur l'ordre global pour que l'assemblage
     * respecte l'ordre demandé, quel que soit l'ordre d'arrivée des tranches.
     *
     * @return array{rendus: int, echecs: array<int, array{id: int, message: string}>}
     */
    /**
     * Assemble les tranches déjà rendues en un seul PDF, page de garde comprise.
     *
     * @param  array<int, array{id: int, message: string}>  $echecs
     */
    public function assembler(string $dossier, ?callable $coverBuilder = null, array $echecs = []): string
    {
        // Concatener soixante-dix PDF demande autant de marge que les rendre.
        //
        // L'assemblage est la seule etape non decoupee, et c'est assume :
        // mesure sur esbtp-yakro, il prend 0,8 s pour six bulletins et 2,5 s
        // pour soixante-dix -- deux ordres de grandeur sous la limite, la ou
        // le RENDU des memes soixante-dix demande douze tranches. Concatener
        // est sans commune mesure avec produire. Le plafond de PLAFOND_EXPORT
        // borne le pire cas ; au-dela, il faudra decouper ici aussi.
        $this->raiseMemoryLimit('512M');
        @set_time_limit(300);

        $fichiers = glob($dossier.'/blt_*.pdf') ?: [];
        sort($fichiers, SORT_STRING);

        if ($fichiers === []) {
            throw new \RuntimeException("Aucun bulletin n'a pu être rendu.");
        }

        if ($coverBuilder !== null) {
            $garde = $this->rendreLaPageDeGarde($coverBuilder, $echecs, $dossier);
            if ($garde !== null) {
                array_unshift($fichiers, $garde);
            }
        }

        try {
            // Le PDF final est ecrit dans le dossier PARENT, pas dans celui de
            // la session : celle-ci est supprimee juste apres l assemblage, et
            // le document servi disparaissait avec elle.
            return $this->mergePdfs($fichiers, $this->dossierTemporaire());
        } finally {
            foreach ($fichiers as $f) {
                @unlink($f);
            }
        }
    }

    /**
     * Balaie les restes des exports abandonnés — dossiers de tranches et PDF
     * assemblés que personne n'est venu chercher.
     *
     * Le service possède ces conventions de nommage ; la commande ne fait que
     * l'appeler. Le jour où le préfixe change, rien à retrouver ailleurs.
     *
     * @return array<int, string> les chemins supprimés, ou à supprimer
     */
    public function purger(?int $minutes = null, bool $simulation = false): array
    {
        $limite = now()->subMinutes($minutes ?? self::DUREE_VIE_MINUTES)->getTimestamp();
        $racine = storage_path('app/temp');

        if (! is_dir($racine)) {
            return [];
        }

        $restes = array_merge(
            glob($racine.'/export_*', GLOB_ONLYDIR) ?: [],
            glob($racine.'/bulletins_export_*.pdf') ?: [],
        );

        $supprimes = [];

        foreach ($restes as $reste) {
            if (filemtime($reste) > $limite) {
                continue;
            }

            $supprimes[] = $reste;

            if ($simulation) {
                continue;
            }

            is_dir($reste) ? $this->oublierLaSession($reste) : @unlink($reste);
        }

        return $supprimes;
    }

    /** Supprime le dossier de session et tout ce qu'il contient encore. */
    public function oublierLaSession(string $dossier): void
    {
        foreach (glob($dossier.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dossier);
    }

    private function dossierTemporaire(): string
    {
        $chemin = storage_path('app/temp');

        if (! is_dir($chemin) && ! mkdir($chemin, 0755, true) && ! is_dir($chemin)) {
            throw new \RuntimeException("Impossible de créer le dossier temporaire d'export : $chemin");
        }

        return $chemin;
    }

    /**
     * Rend une tranche de bulletins dans le dossier de session.
     *
     * Les fichiers sont numérotés sur l'ordre global : l'assemblage respecte
     * l'ordre demandé quel que soit l'ordre d'arrivée des tranches, et une
     * tranche rejouée ne désordonne pas le document.
     *
     * @return array{rendus: int, echecs: array<int, array{id: int, message: string}>}
     */
    public function rendreTranche(Collection $bulletins, callable $renderer, string $dossier, int $depart = 0): array
    {
        // Gardes mémoire/temps : un export groupé est plus lourd qu'un bulletin
        // seul. On n'ÉLÈVE la limite mémoire que si la valeur courante est plus
        // basse — ne jamais rabaisser un serveur mieux doté.
        $this->raiseMemoryLimit('512M');
        @set_time_limit(300);

        $echecs = [];
        $rendus = 0;

        foreach ($bulletins->values() as $position => $bulletin) {
            // Rang global : c'est lui qui porte l'ordre du document final.
            $rang = $depart + $position;

            try {
                $pdf = $renderer($bulletin);
                $chemin = sprintf('%s/blt_%06d_%s.pdf', $dossier, $rang, uniqid('', true));
                file_put_contents($chemin, $pdf->output());
                $rendus++;
                unset($pdf);
            } catch (\Throwable $e) {
                $echecs[] = ['id' => (int) $bulletin->id, 'message' => $e->getMessage()];
                Log::error('BulletinBulkPdfExporter: rendu échoué bulletin #'.$bulletin->id.' — '.$e->getMessage());
            }
        }

        return ['rendus' => $rendus, 'echecs' => $echecs];
    }

    /** @param array<int, array{id: int, message: string}> $echecs */
    private function rendreLaPageDeGarde(callable $coverBuilder, array $echecs, string $dossier): ?string
    {
        try {
            $pdf = $coverBuilder($echecs);
            if ($pdf === null) {
                return null;
            }

            // C'est array_unshift qui place la garde en tête, pas le tri : le
            // nom ne doit surtout pas laisser croire l'inverse à un relecteur.
            $chemin = $dossier.'/garde_'.uniqid('', true).'.pdf';
            file_put_contents($chemin, $pdf->output());

            return $chemin;
        } catch (\Throwable $e) {
            Log::error('BulletinBulkPdfExporter: page de garde non générée — '.$e->getMessage());

            return null;
        }
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
