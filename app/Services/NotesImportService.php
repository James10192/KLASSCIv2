<?php

namespace App\Services;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPEvaluation;
use App\Models\ESBTPInscription;
use App\Models\ESBTPNote;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToArray;

/**
 * Service de parsing & application des imports Excel de notes.
 *
 * Pipeline en 3 phases :
 *  1. parseFile()  → lit la feuille, extrait métadonnées + lignes brutes
 *  2. dryRun()     → calcule un diff Avant/Après + erreurs (lecture seule)
 *  3. apply()      → exécute les changements en transaction (création/mise à jour)
 *
 * Validation par cellule : note ∈ [0, bareme], etudiant dans la classe,
 * évaluation existe, format numérique français accepté (',' → '.').
 */
class NotesImportService
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_UNCHANGED = 'unchanged';

    /**
     * Lit le fichier et retourne les lignes brutes.
     *
     * @return array{rows: array, meta: array|null}
     */
    public function parseFile(UploadedFile $file): array
    {
        $reader = new class implements ToArray {
            public array $data = [];

            public function array(array $array): void
            {
                $this->data = $array;
            }
        };

        Excel::import($reader, $file);

        $rows = $reader->data ?? [];

        // Meta (ligne 1 : marker + JSON)
        $meta = null;
        if (isset($rows[0][0]) && $rows[0][0] === '__KLASSCI_NOTES_EXPORT__') {
            $rawJson = $rows[0][1] ?? null;
            if (is_string($rawJson)) {
                try {
                    $decoded = json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
                    $meta = $decoded;
                } catch (\Throwable) {
                    $meta = null;
                }
            }
            // Retirer la ligne meta des données
            array_shift($rows);
        }

        return ['rows' => $rows, 'meta' => $meta];
    }

    /**
     * Dry-run : calcule le diff sans persister.
     *
     * @return array{summary: array, changes: array, errors: array, evaluations: array}
     */
    public function dryRun(array $parsed, int $classeId, int $matiereId, string $periode, ?int $anneeUniversitaireId = null): array
    {
        $rows = $parsed['rows'] ?? [];
        $meta = $parsed['meta'] ?? null;

        $errors = [];
        $changes = [];
        $summary = [
            'will_create' => 0,
            'will_update' => 0,
            'unchanged' => 0,
            'errors' => 0,
        ];

        if (empty($rows)) {
            return [
                'summary' => $summary,
                'changes' => [],
                'errors' => [['row' => 0, 'col' => '-', 'reason' => 'Le fichier est vide.']],
                'evaluations' => [],
            ];
        }

        // L'année courante si non fournie
        if ($anneeUniversitaireId === null) {
            $current = ESBTPAnneeUniversitaire::where('is_current', 1)->first();
            $anneeUniversitaireId = $current?->id;
        }

        // Header (1ère ligne après meta)
        $headerRow = $rows[0];
        $dataRows = array_slice($rows, 1);

        // Mapper les évaluations à partir du meta (priorité) ou du header
        $evalColumns = $this->resolveEvaluations($headerRow, $meta, $classeId, $matiereId, $periode, $anneeUniversitaireId);

        if (empty($evalColumns)) {
            return [
                'summary' => $summary,
                'changes' => [],
                'errors' => [['row' => 1, 'col' => '-', 'reason' => 'Aucune évaluation reconnue. Réexportez le modèle Excel.']],
                'evaluations' => [],
            ];
        }

        // Une colonne porteuse de notes que nulle évaluation ne réclame était
        // jusqu'ici perdue en silence — voir `erreursDesColonnesIgnorees()`.
        foreach ($this->erreursDesColonnesIgnorees($headerRow, $dataRows, $evalColumns, $meta !== null) as $erreur) {
            $errors[] = $erreur;
            $summary['errors']++;
        }

        // Charger les inscriptions actives de la classe pour mapping matricule → etudiant
        $inscriptions = ESBTPInscription::query()
            ->with(['etudiant:id,matricule,nom,prenoms'])
            ->where('classe_id', $classeId)
            ->where('status', 'active')
            ->where('workflow_step', 'etudiant_cree')
            ->when($anneeUniversitaireId, fn ($q) => $q->where('annee_universitaire_id', $anneeUniversitaireId))
            ->get();

        $matriculeMap = [];
        foreach ($inscriptions as $i) {
            $etu = $i->etudiant;
            if ($etu && $etu->matricule) {
                $matriculeMap[trim($etu->matricule)] = $etu;
            }
        }

        // Charger les notes existantes
        $evalIds = collect($evalColumns)->pluck('id')->all();
        $etuIds = collect($matriculeMap)->pluck('id')->all();
        $existingNotes = [];
        if ($evalIds && $etuIds) {
            ESBTPNote::whereIn('evaluation_id', $evalIds)
                ->whereIn('etudiant_id', $etuIds)
                ->get()
                ->each(function ($n) use (&$existingNotes) {
                    $existingNotes[$n->etudiant_id . '_' . $n->evaluation_id] = $n;
                });
        }

        // Parcourir les lignes
        $rowOffset = $meta ? 3 : 2; // ligne Excel réelle (meta ligne 1, header ligne 2 si meta)
        foreach ($dataRows as $rowIdx => $row) {
            $excelRow = $rowOffset + $rowIdx;

            $matricule = trim((string) ($row[0] ?? ''));
            if ($matricule === '') {
                continue; // ignore lignes vides
            }

            $etudiant = $matriculeMap[$matricule] ?? null;
            if (! $etudiant) {
                $errors[] = [
                    'row' => $excelRow,
                    'col' => 'A',
                    'matricule' => $matricule,
                    'reason' => "Aucun étudiant actif trouvé avec ce matricule dans la classe sélectionnée.",
                ];
                $summary['errors']++;
                continue;
            }

            // Pour chaque colonne d'évaluation
            foreach ($evalColumns as $colIndex => $evalCol) {
                $cellValue = $row[$colIndex] ?? null;
                $evalId = $evalCol['id'];
                $bareme = (float) ($evalCol['bareme'] ?? 20);
                $titre = $evalCol['titre'] ?? ('Eval #' . $evalId);

                $parsed = $this->parseCellValue($cellValue);
                if ($parsed === null) {
                    // Cellule vide : pas de modification
                    continue;
                }

                $colLetter = $this->columnLetter($colIndex + 1);

                if ($parsed === 'invalid') {
                    $errors[] = [
                        'row' => $excelRow,
                        'col' => $colLetter,
                        'matricule' => $matricule,
                        'evaluation' => $titre,
                        'reason' => "Valeur '" . (string) $cellValue . "' non reconnue. Utilisez un nombre, ABS ou laissez vide.",
                    ];
                    $summary['errors']++;
                    continue;
                }

                $isAbsent = ($parsed === 'absent');
                $noteValue = $isAbsent ? 0.0 : (float) $parsed;

                // Validation barème
                if (! $isAbsent && ($noteValue < 0 || $noteValue > $bareme)) {
                    $errors[] = [
                        'row' => $excelRow,
                        'col' => $colLetter,
                        'matricule' => $matricule,
                        'evaluation' => $titre,
                        'reason' => sprintf("Note %s hors barème [0 — %s].", $noteValue, $bareme),
                    ];
                    $summary['errors']++;
                    continue;
                }

                $key = $etudiant->id . '_' . $evalId;
                $existing = $existingNotes[$key] ?? null;

                if (! $existing) {
                    $changes[] = [
                        'etudiant_id' => $etudiant->id,
                        'matricule' => $matricule,
                        'etudiant_nom' => trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')),
                        'evaluation_id' => $evalId,
                        'evaluation' => $titre,
                        'before' => null,
                        'after' => $isAbsent ? 'ABS' : $noteValue,
                        'is_absent' => $isAbsent,
                        'action' => self::ACTION_CREATE,
                        'row' => $excelRow,
                        'col' => $colLetter,
                    ];
                    $summary['will_create']++;
                } else {
                    $beforeIsAbsent = (bool) $existing->is_absent;
                    $beforeValue = $beforeIsAbsent ? 'ABS' : (float) $existing->note;
                    $afterValue = $isAbsent ? 'ABS' : $noteValue;

                    $changed = ($beforeIsAbsent !== $isAbsent) || ($beforeValue !== $afterValue);
                    if (! $changed) {
                        $summary['unchanged']++;
                        continue;
                    }

                    $changes[] = [
                        'etudiant_id' => $etudiant->id,
                        'matricule' => $matricule,
                        'etudiant_nom' => trim(($etudiant->nom ?? '') . ' ' . ($etudiant->prenoms ?? '')),
                        'evaluation_id' => $evalId,
                        'evaluation' => $titre,
                        'before' => $beforeValue,
                        'after' => $afterValue,
                        'is_absent' => $isAbsent,
                        'action' => self::ACTION_UPDATE,
                        'row' => $excelRow,
                        'col' => $colLetter,
                    ];
                    $summary['will_update']++;
                }
            }
        }

        return [
            'summary' => $summary,
            'changes' => $changes,
            'errors' => $errors,
            'evaluations' => array_values($evalColumns),
        ];
    }

    /**
     * Applique les changements (transaction). Repasse par dryRun() pour cohérence.
     *
     * @return array{created: int, updated: int, errors: int}
     */
    public function apply(array $parsed, int $classeId, int $matiereId, string $periode, ?int $anneeUniversitaireId = null): array
    {
        $diff = $this->dryRun($parsed, $classeId, $matiereId, $periode, $anneeUniversitaireId);

        if (! empty($diff['errors'])) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => count($diff['errors']),
                'error_details' => $diff['errors'],
            ];
        }

        $created = 0;
        $updated = 0;
        $userId = Auth::id();

        // Précharger les évaluations pour les méta (classe_id, matiere_id, periode, annee)
        $evalIds = collect($diff['changes'])->pluck('evaluation_id')->unique()->all();
        $evaluations = ESBTPEvaluation::whereIn('id', $evalIds)->get()->keyBy('id');

        DB::transaction(function () use ($diff, $evaluations, $userId, &$created, &$updated) {
            foreach ($diff['changes'] as $change) {
                $eval = $evaluations->get($change['evaluation_id']);
                if (! $eval) {
                    continue;
                }

                $note = ESBTPNote::where('etudiant_id', $change['etudiant_id'])
                    ->where('evaluation_id', $change['evaluation_id'])
                    ->first();

                $isNew = ($note === null);
                if ($isNew) {
                    $note = new ESBTPNote();
                    $note->etudiant_id = $change['etudiant_id'];
                    $note->evaluation_id = $change['evaluation_id'];
                    $note->classe_id = $eval->classe_id;
                    $note->matiere_id = $eval->matiere_id;
                    $note->semestre = $eval->periode;
                    $note->annee_universitaire = optional($eval->anneeUniversitaire)->name ?? 'N/A';
                    $note->type_evaluation = $eval->type;
                    $note->created_by = $userId;
                }

                $note->is_absent = $change['is_absent'] ? 1 : 0;
                $note->note = $change['is_absent'] ? 0 : (float) $change['after'];
                $note->updated_by = $userId;
                $note->save();

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                }
            }
        });

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => 0,
        ];
    }

    /**
     * Convertit une cellule en valeur :
     *  - null si vide
     *  - 'absent' si ABS / Absent
     *  - float si numérique
     *  - 'invalid' sinon
     */
    private function parseCellValue($value)
    {
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        $upper = mb_strtoupper($str, 'UTF-8');

        if (in_array($upper, ['ABS', 'ABSENT', 'ABSENTE', 'A'], true)) {
            return 'absent';
        }

        // Format français : 12,5 → 12.5
        $normalized = str_replace([',', ' '], ['.', ''], $str);
        if (is_numeric($normalized)) {
            return (float) $normalized;
        }

        return 'invalid';
    }

    /**
     * Résout les colonnes d'évaluation : depuis le meta JSON (préféré)
     * sinon best-effort match par titre.
     *
     * @return array<int, array{id: int, titre: string, bareme: float, coefficient: float}>
     *         Indexé par index de colonne (0-based : 0=matricule, 1=nom, 2=eval1, ...)
     */
    private function resolveEvaluations(array $headerRow, ?array $meta, int $classeId, int $matiereId, string $periode, ?int $anneeUniversitaireId): array
    {
        $resolved = [];

        // Évaluations actives en BDD
        $dbEvals = ESBTPEvaluation::query()
            ->where('classe_id', $classeId)
            ->where('matiere_id', $matiereId)
            ->where('periode', $periode)
            ->when($anneeUniversitaireId, fn ($q) => $q->where('annee_universitaire_id', $anneeUniversitaireId))
            ->where('is_published', 1)
            ->get()
            ->keyBy('id');

        // Stratégie 1 : meta JSON
        if ($meta && isset($meta['evaluations']) && is_array($meta['evaluations'])) {
            $colIndex = 2; // skip matricule + nom
            foreach ($meta['evaluations'] as $metaEval) {
                $evalId = (int) ($metaEval['id'] ?? 0);
                if ($evalId && $dbEvals->has($evalId)) {
                    $eval = $dbEvals->get($evalId);
                    $resolved[$colIndex] = [
                        'id' => $eval->id,
                        'titre' => $eval->titre,
                        'bareme' => (float) $eval->bareme,
                        'coefficient' => (float) $eval->coefficient,
                    ];
                }
                $colIndex++;
            }

            return $resolved;
        }

        // Stratégie 2 : match par titre (header)
        // Skip A (matricule) + B (nom) + dernière (moyenne)
        $headerCount = count($headerRow);
        for ($colIndex = 2; $colIndex < $headerCount - 1; $colIndex++) {
            $title = trim((string) ($headerRow[$colIndex] ?? ''));
            if ($title === '') {
                continue;
            }

            // Extraire le titre brut (avant " (/")
            $rawTitle = preg_replace('/\s*\(.*\)\s*$/', '', $title);

            $match = $dbEvals->first(fn ($e) => Str::lower(trim($e->titre)) === Str::lower($rawTitle));
            if ($match) {
                $resolved[$colIndex] = [
                    'id' => $match->id,
                    'titre' => $match->titre,
                    'bareme' => (float) $match->bareme,
                    'coefficient' => (float) $match->coefficient,
                ];
            }
        }

        return $resolved;
    }

    /**
     * Les colonnes qui portent des notes sans qu'aucune évaluation ne les réclame.
     *
     * Elles disparaissaient sans un mot : le tableau de prévisualisation
     * annonçait « 3 notes à créer », l'utilisateur validait, et une colonne
     * entière n'était jamais écrite.
     *
     * Le cas n'est pas théorique. L'export et l'import posent la MÊME requête —
     * même classe, même matière, même période, même année, `is_published = 1` —
     * donc un fichier fraîchement exporté se relit entièrement. Mais entre
     * l'export et l'import, l'évaluation a pu être dépubliée, supprimée, ou
     * changer de période : son identifiant reste dans les métadonnées du fichier,
     * la base ne le rend plus, et `resolveEvaluations()` laisse un trou à cet
     * index. Les autres colonnes ne se décalent pas — l'incrément de colonne y
     * est hors du `if` — mais celle-là est perdue.
     *
     * Le contrôle est posé ici, au niveau du fichier, et non dans
     * `resolveEvaluations()`, parce qu'il couvre ainsi les DEUX stratégies d'un
     * seul geste : celle par métadonnées, où un identifiant absent laisse un
     * trou, et celle par titre, explicitement « best-effort », qui ne peut pas
     * juger elle-même si un non-appariement est grave.
     *
     * C'est la présence de valeurs qui tranche, et c'est elle qui évite les
     * fausses alertes : une colonne vide — décorative, séparatrice — ne fait rien
     * perdre, donc on se tait. Une colonne qui porte ne serait-ce qu'une valeur
     * en fait perdre, donc on parle.
     *
     * Signalé comme erreur et non comme avertissement, parce qu'`apply()` est
     * tout-ou-rien : une erreur refuse le fichier entier. C'est la bonne conduite
     * ici — écrire deux colonnes sur trois en annonçant un succès laisserait une
     * matière à moitié saisie que personne n'irait rechercher.
     *
     * @param  array<int, mixed>  $headerRow
     * @param  array<int, array<int, mixed>>  $dataRows
     * @param  array<int, array{id: int, titre: string, bareme: float, coefficient: float}>  $evalColumns
     * @return list<array{row: int, col: string, evaluation: string, reason: string}>
     */
    private function erreursDesColonnesIgnorees(array $headerRow, array $dataRows, array $evalColumns, bool $avecMeta): array
    {
        $erreurs = [];
        // Borne droite alignée sur la stratégie par titre : la dernière colonne
        // du modèle est la moyenne calculée, jamais une évaluation.
        $derniereColonne = count($headerRow) - 1;

        for ($colIndex = 2; $colIndex < $derniereColonne; $colIndex++) {
            if (isset($evalColumns[$colIndex])) {
                continue;
            }

            $porteuse = false;
            foreach ($dataRows as $row) {
                if (trim((string) ($row[$colIndex] ?? '')) !== '') {
                    $porteuse = true;
                    break;
                }
            }

            if (! $porteuse) {
                continue;
            }

            // Le titre tel qu'il figure dans le fichier — « Devoir 1 (/20) » —
            // parce que c'est celui que l'utilisateur a sous les yeux.
            $titre = trim((string) ($headerRow[$colIndex] ?? ''));

            $erreurs[] = [
                'row' => $avecMeta ? 2 : 1,
                'col' => $this->columnLetter($colIndex + 1),
                'evaluation' => $titre,
                'reason' => $titre === ''
                    ? 'Cette colonne porte des notes mais aucun en-tête : impossible de savoir à quelle évaluation les rattacher.'
                    : sprintf(
                        'L\'évaluation « %s » porte des notes dans le fichier mais n\'existe plus pour cette classe, cette matière et cette période — dépubliée, supprimée ou déplacée depuis l\'export. Republiez-la, ou réexportez le modèle.',
                        $titre
                    ),
            ];
        }

        return $erreurs;
    }

    private function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }
}
