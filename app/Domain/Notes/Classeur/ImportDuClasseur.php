<?php

namespace App\Domain\Notes\Classeur;

use App\Services\NotesImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Réimport d'une fiche de notes Excel complète : chaque feuille porte sa ligne
 * cachée (classe, matière, semestre, évaluations) et passe par l'import de
 * notes existant. Tout ou rien : une seule feuille en erreur, rien n'est écrit.
 */
class ImportDuClasseur
{
    private const MARQUEUR = '__KLASSCI_NOTES_EXPORT__';

    public function __construct(private NotesImportService $import) {}

    public function simuler(UploadedFile $fichier, int $classeId): array
    {
        $feuilles = [];
        foreach ($this->feuilles($fichier, $classeId) as $nom => $f) {
            $diff = $this->import->dryRun($f['parsed'], $classeId, $f['matiere_id'], $f['periode'], $f['annee_id']);
            $feuilles[] = ['feuille' => $nom, 'summary' => $diff['summary'], 'errors' => $diff['errors'],
                'changes' => $diff['changes'], 'evaluations_a_creer' => $this->evaluationsACreer($diff)];
        }

        return ['feuilles' => $feuilles, 'total' => [
            'will_create' => array_sum(array_map(fn ($f) => $f['summary']['will_create'], $feuilles)),
            'will_update' => array_sum(array_map(fn ($f) => $f['summary']['will_update'], $feuilles)),
            'errors' => array_sum(array_map(fn ($f) => count($f['errors']), $feuilles)),
        ]];
    }

    /**
     * Les colonnes ajoutées qui seront réellement créées : celles qui portent
     * au moins une note. Une colonne vide n'est pas annoncée, elle ne crée rien.
     */
    private function evaluationsACreer(array $diff): array
    {
        $remplies = collect($diff['changes'])->pluck('evaluation_id')->unique()->all();

        return collect($diff['evaluations'])->where('nouvelle', true)
            ->filter(fn ($e) => in_array($e['id'], $remplies, true))->pluck('titre')->values()->all();
    }

    public function appliquer(UploadedFile $fichier, int $classeId): array
    {
        $simulation = $this->simuler($fichier, $classeId);
        if ($simulation['total']['errors'] > 0) {
            return ['applique' => false] + $simulation;
        }

        $resultats = DB::transaction(function () use ($fichier, $classeId) {
            $r = [];
            foreach ($this->feuilles($fichier, $classeId) as $nom => $f) {
                $r[$nom] = $this->import->apply($f['parsed'], $classeId, $f['matiere_id'], $f['periode'], $f['annee_id']);
            }

            return $r;
        });

        return ['applique' => true, 'feuilles' => $resultats];
    }

    /** @return array<string, array{parsed: array, matiere_id: int, periode: string, annee_id: ?int}> */
    private function feuilles(UploadedFile $fichier, int $classeId): array
    {
        $classeur = Excel::toArray(new class {}, $fichier);
        $noms = $this->nomsDesFeuilles($fichier);
        $feuilles = [];
        foreach ($classeur as $i => $rows) {
            if (($rows[0][0] ?? null) !== self::MARQUEUR) {
                continue; // feuille ajoutée à la main : ignorée
            }
            $meta = json_decode((string) ($rows[0][1] ?? ''), true);
            if (! is_array($meta) || (int) ($meta['classe_id'] ?? 0) !== $classeId) {
                throw new \InvalidArgumentException('Ce fichier a été exporté pour une autre classe : réexportez la fiche de cette classe.');
            }
            array_shift($rows);
            $feuilles[$noms[$i] ?? ('Feuille '.($i + 1))] = [
                'parsed' => ['rows' => $rows, 'meta' => $meta],
                'matiere_id' => (int) $meta['matiere_id'],
                'periode' => (string) $meta['periode'],
                'annee_id' => isset($meta['annee_universitaire_id']) ? (int) $meta['annee_universitaire_id'] : null,
            ];
        }
        if ($feuilles === []) {
            throw new \InvalidArgumentException('Aucune feuille de notes KLASSCI reconnue : utilisez la fiche Excel téléchargée depuis KLASSCI.');
        }

        return $feuilles;
    }

    private function nomsDesFeuilles(UploadedFile $fichier): array
    {
        return \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($fichier->getRealPath())->listWorksheetNames($fichier->getRealPath());
    }
}
