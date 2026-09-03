<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Emission et relecture des releves de notes LMD.
 *
 * Le patron est celui de OfficialDocumentService pour le PV de jury : une serie
 * versionnee par portee, un seul document valide a la fois, remplacement motive,
 * verification d'integrite a chaque lecture. Ce qui change :
 *
 *  - la portee d'une serie est (etudiant, annee), pas un jury ;
 *  - la numerotation est deterministe et ne consomme aucun compteur (voir
 *    `numberFor`) ;
 *  - l'emission ne modifie rien d'autre : elle n'ecrit dans aucune table metier,
 *    ne verrouille aucune decision. Un releve constate, il ne decide pas.
 */
class LmdTranscriptService
{
    public function __construct(
        private readonly OfficialDocumentStorage $storage,
        private readonly OfficialDocumentEventRecorder $events,
        private readonly OfficialDocumentIntegrityService $integrity,
        private readonly LmdTranscriptIssuanceGuard $guard,
        private readonly LmdTranscriptSnapshotBuilder $snapshots,
        private readonly LmdTranscriptRenderer $renderer,
    ) {}

    public function existing(ESBTPEtudiant $student, ESBTPAnneeUniversitaire $year): ?OfficialDocument
    {
        return $this->series($student->id, $year->id)
            ->whereIn('status', [OfficialDocument::STATUS_VALID, OfficialDocument::STATUS_LEGACY])
            ->orderByDesc('version')
            ->first();
    }

    public function issue(
        ESBTPEtudiant $student,
        ESBTPAnneeUniversitaire $year,
        User $actor,
        ?string $supersessionReason = null,
    ): OfficialDocument {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($student, $year, $actor, $supersessionReason, &$storedPath): OfficialDocument {
                $series = $this->series($student->id, $year->id)
                    ->orderByDesc('version')
                    ->lockForUpdate()
                    ->get();

                $current = $series->firstWhere('status', OfficialDocument::STATUS_VALID);

                // Deja emis et rien a rectifier : on rend l'existant apres l'avoir
                // verifie, plutot que d'empiler un doublon a chaque clic.
                if ($current && $supersessionReason === null) {
                    return $this->integrity->assertValidAndIntact($current, false, $actor->id);
                }

                $this->assertReplacementReason($current, $supersessionReason);

                $state = $this->guard->assertIssuable($student->id, $year->id);
                $identity = $this->newIdentity(
                    $this->numberFor($state['student'], $state['year']),
                    $this->nextVersion($series),
                );

                $snapshot = $this->snapshots->build($state, $identity, $actor, now());
                $contents = $this->renderer->render($snapshot, $identity['verification_code']);
                $this->storeAndVerify($this->seriesKey($student->id, $year->id), $identity, $contents, $storedPath);

                $previous = $series->sortByDesc('version')->first();
                if ($current) {
                    $this->markSuperseded($current, $supersessionReason, $actor);
                }

                return $this->persist($student, $year, $previous, $identity, $snapshot, $contents, $storedPath, $actor);
            });
        } catch (OfficialDocumentIntegrityException $exception) {
            if ($storedPath) {
                $this->storage->delete($storedPath);
            }
            $this->events->record(new OfficialDocument(['reference' => $exception->reference]), 'integrity_failed', $actor->id);
            throw $exception;
        } catch (Throwable $exception) {
            if ($storedPath) {
                $this->storage->delete($storedPath);
            }
            throw $exception;
        }
    }

    private function series(int $studentId, int $yearId)
    {
        return OfficialDocument::query()
            ->forSource(ESBTPEtudiant::class, $studentId)
            ->where('document_type', OfficialDocument::TYPE_LMD_TRANSCRIPT)
            ->where('series_key', $this->seriesKey($studentId, $yearId));
    }

    private function seriesKey(int $studentId, int $yearId): string
    {
        return sprintf('lmd-releve:%d:%d', $studentId, $yearId);
    }

    /**
     * Numero du releve.
     *
     * Deterministe : (annee, etablissement, etudiant) identifie deja la serie de
     * facon unique, il n'y a donc rien a compter. C'est un choix, pas un raccourci :
     * la seule table de sequence existante (`esbtp_pv_sequences`) est unique par
     * (tenant, annee) et sert au compteur legal des PV de jury. La partager
     * ferait avancer la numerotation des PV a chaque releve emis, ce qui trouerait
     * une suite que la loi veut continue. Creer une table demanderait une
     * migration, hors perimetre ici.
     */
    private function numberFor(ESBTPEtudiant $student, ESBTPAnneeUniversitaire $year): string
    {
        return sprintf('REL-%s-%s-%06d', $this->yearLabel($year), $this->tenantCode(), $student->id);
    }

    private function yearLabel(ESBTPAnneeUniversitaire $year): string
    {
        $label = preg_replace('/[^A-Za-z0-9]/', '', trim((string) ($year->display_name ?? '')));

        return $label !== '' ? $label : (string) $year->id;
    }

    private function tenantCode(): string
    {
        return strtoupper((string) (config('app.tenant_code') ?? env('TENANT_CODE', 'PRES')));
    }

    private function newIdentity(string $number, int $version): array
    {
        return [
            'number' => $number,
            'version' => $version,
            'reference' => sprintf('DOC-%s-V%d-%s', $number, $version, Str::upper(Str::random(10))),
            'verification_code' => Str::upper(Str::random(48)),
        ];
    }

    private function nextVersion($series): int
    {
        return max(1, ((int) $series->max('version')) + 1);
    }

    private function assertReplacementReason(?OfficialDocument $current, ?string $reason): void
    {
        if ($current && trim((string) $reason) === '') {
            throw new \InvalidArgumentException('Le motif de remplacement est obligatoire.');
        }
    }

    private function storeAndVerify(string $seriesKey, array $identity, string $contents, ?string &$storedPath): void
    {
        $storedPath = $this->storage->storePdf($seriesKey, $identity['version'], $identity['reference'], $contents);

        if (! hash_equals(hash('sha256', $contents), $this->storage->checksum($storedPath))) {
            throw new OfficialDocumentIntegrityException(0, $identity['reference']);
        }
    }

    private function markSuperseded(OfficialDocument $current, ?string $reason, User $actor): void
    {
        $current->forceFill([
            'status' => OfficialDocument::STATUS_SUPERSEDED,
            'valid_series_key' => null,
            'lifecycle_metadata' => ['supersession_reason' => $reason],
        ])->save();

        $this->events->record(
            $current,
            'superseded',
            $actor->id,
            [],
            $reason,
            OfficialDocument::STATUS_VALID,
            OfficialDocument::STATUS_SUPERSEDED,
        );
    }

    private function persist(
        ESBTPEtudiant $student,
        ESBTPAnneeUniversitaire $year,
        ?OfficialDocument $previous,
        array $identity,
        array $snapshot,
        string $contents,
        string $path,
        User $actor,
    ): OfficialDocument {
        $seriesKey = $this->seriesKey($student->id, $year->id);

        $document = OfficialDocument::query()->create([
            'document_type' => OfficialDocument::TYPE_LMD_TRANSCRIPT,
            'source_type' => ESBTPEtudiant::class,
            'source_id' => $student->id,
            'series_key' => $seriesKey,
            'version' => $identity['version'],
            'reference' => $identity['reference'],
            'status' => OfficialDocument::STATUS_VALID,
            'valid_series_key' => $seriesKey,
            'disk' => OfficialDocumentStorage::DISK,
            'path' => $path,
            'original_name' => $identity['number'].'-v'.$identity['version'].'.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => strlen($contents),
            'checksum_sha256' => hash('sha256', $contents),
            'snapshot' => $snapshot,
            'snapshot_sha256' => hash('sha256', $this->snapshots->canonicalJson($snapshot)),
            'rules_version' => LmdTranscriptSnapshotBuilder::RULES_VERSION,
            'template_version' => 'lmd-releve-notes-v1',
            'renderer_version' => 'dompdf-v2',
            'verification_code_digest' => hash('sha256', $identity['verification_code']),
            'issued_by' => $actor->id,
            'issued_at' => $snapshot['issuance']['issued_at'],
            'supersedes_document_id' => $previous?->id,
        ]);

        $this->events->record($document, 'issued', $actor->id, [
            'student_id' => $student->id,
            'annee_universitaire_id' => $year->id,
            'version' => $document->version,
        ]);

        return $document;
    }
}
