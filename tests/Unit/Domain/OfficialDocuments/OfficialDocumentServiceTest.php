<?php

namespace Tests\Unit\Domain\OfficialDocuments;

use App\Domain\OfficialDocuments\Exceptions\JuryPvNotIssuableException;
use App\Domain\OfficialDocuments\Http\OfficialDocumentController;
use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Domain\OfficialDocuments\Models\OfficialDocumentEvent;
use App\Domain\OfficialDocuments\Services\JuryPvSnapshotBuilder;
use App\Domain\OfficialDocuments\Services\LegacyJuryPvReconciliationService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentDownloadService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentEventRecorder;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityException;
use App\Domain\OfficialDocuments\Services\OfficialDocumentIntegrityService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentStorage;
use App\Domain\OfficialDocuments\Services\PvNumberSequenceService;
use App\Models\User;
use App\Services\JuryDeliberationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OfficialDocumentServiceTest extends OfficialDocumentDatabaseTestCase
{
    public function test_issue_is_idempotent_and_projects_legacy_columns(): void
    {
        $jury = $this->seedIssuableJury();
        $first = $this->documents()->issueJuryPv($jury, $this->actor());
        $second = $this->documents()->issueJuryPv($jury, $this->actor());

        self::assertTrue($first->is($second));
        self::assertSame(1, $first->version);
        self::assertSame(OfficialDocument::STATUS_VALID, $first->status);
        self::assertSame('clos', $jury->fresh()->status);
        self::assertSame($first->path, $jury->fresh()->pv_path);
        self::assertNotNull($jury->fresh()->pv_genere_at);
    }

    public function test_snapshot_is_complete_canonical_and_pdf_uses_its_identity(): void
    {
        $document = $this->documents()->issueJuryPv($this->seedIssuableJury(), $this->actor());
        $snapshot = $document->snapshot;
        $canonical = app(JuryPvSnapshotBuilder::class)->canonicalJson($snapshot);

        self::assertSame('lmd-jury-pv-snapshot-v2', $snapshot['schema']);
        self::assertSame($document->reference, $snapshot['document']['reference']);
        self::assertSame($document->version, $snapshot['document']['version']);
        self::assertSame($document->snapshot_sha256, hash('sha256', $canonical));
        self::assertSame(2, $snapshot['statistics']['total']);
        self::assertStringStartsWith('%PDF-', Storage::disk('local')->get($document->path));
    }

    public function test_payload_and_event_log_are_append_only(): void
    {
        $document = $this->documents()->issueJuryPv($this->seedIssuableJury(), $this->actor());
        $document->snapshot = ['tampered' => true];

        $this->assertExceptionRaised(fn () => $document->save(), \LogicException::class);

        $event = $document->events()->firstOrFail();
        $event->reason = 'altération';
        $this->assertExceptionRaised(fn () => $event->save(), \LogicException::class);
        $this->assertExceptionRaised(fn () => $event->fresh()->delete(), \LogicException::class);
    }

    public function test_signature_preserves_user_ip_agent_order_and_cannot_be_overwritten(): void
    {
        $jury = $this->seedIssuableJury();
        $member = $jury->membres()->where('user_id', 2)->firstOrFail();
        $member->forceFill(['signature_data' => null, 'signature_at' => null, 'signature_ip' => null])->save();
        $service = app(JuryDeliberationService::class);
        $signed = $service->enregistrerSignature($member, $this->signatureData(), 2, '192.0.2.10', 'Agent Test');

        self::assertSame($this->signatureData(), $signed->signature_data);
        self::assertSame('192.0.2.10', $signed->signature_ip);
        self::assertSame('Agent Test', $signed->signature_user_agent);
        self::assertNotNull($signed->signature_at);
        $this->assertExceptionRaised(
            fn () => $service->enregistrerSignature($signed, 'replacement', 2),
            \LogicException::class,
        );
    }

    public function test_signature_cannot_be_written_by_another_user(): void
    {
        $jury = $this->seedIssuableJury();
        $member = $jury->membres()->where('user_id', 2)->firstOrFail();
        $member->forceFill(['signature_data' => null, 'signature_at' => null])->save();

        $this->assertExceptionRaised(
            fn () => app(JuryDeliberationService::class)
                ->enregistrerSignature($member, $this->signatureData(), 1),
            \LogicException::class,
        );
        self::assertNull($member->fresh()->signature_data);
    }

    public function test_guard_rejects_missing_signature_incomplete_cohort_and_unvalidated_sheet(): void
    {
        $jury = $this->seedIssuableJury();
        $member = $jury->membres()->where('user_id', 2)->firstOrFail();
        $member->forceFill(['signature_data' => null, 'signature_at' => null])->save();
        $this->assertIssuanceRejected($jury->id, 'doivent signer');

        $member->forceFill(['signature_data' => $this->signatureData(), 'signature_at' => now()])->save();
        $decision = $jury->decisions()->where('etudiant_id', 11)->firstOrFail();
        $decision->delete();
        $this->assertIssuanceRejected($jury->id, 'exactement une décision');

        $decision->restore();
        DB::table('esbtp_grade_sheets')->where('id', 1)->update(['status' => 'controlled']);
        $this->assertIssuanceRejected($jury->id, 'validées et verrouillées');
    }

    public function test_revocation_allows_next_version_with_explicit_supersession_link(): void
    {
        $jury = $this->seedIssuableJury();
        $first = $this->documents()->issueJuryPv($jury, $this->actor());
        $this->documents()->revoke($first, 'Erreur matérielle', $this->actor());
        $second = $this->documents()->issueJuryPv($jury->fresh(), $this->actor(), 'Rectification approuvée');

        self::assertSame(OfficialDocument::STATUS_REVOKED, $first->fresh()->status);
        self::assertSame(2, $second->version);
        self::assertSame($first->id, $second->supersedes_document_id);
        self::assertSame('clos', $jury->fresh()->status);
    }

    public function test_explicit_replacement_supersedes_same_series_without_cycle(): void
    {
        $jury = $this->seedIssuableJury();
        $first = $this->documents()->issueJuryPv($jury, $this->actor());
        $second = $this->documents()->issueJuryPv($jury->fresh(), $this->actor(), 'Correction du procès-verbal');

        self::assertSame(OfficialDocument::STATUS_SUPERSEDED, $first->fresh()->status);
        self::assertSame('Correction du procès-verbal', $first->fresh()->lifecycle_metadata['supersession_reason']);
        self::assertSame(OfficialDocument::STATUS_VALID, $second->status);
        self::assertSame($first->series_key, $second->series_key);
        self::assertSame($first->id, $second->supersedes_document_id);
        self::assertNull($first->supersedes_document_id);
    }

    public function test_integrity_mismatch_refuses_old_url_and_records_event(): void
    {
        $document = $this->documents()->issueJuryPv($this->seedIssuableJury(), $this->actor());
        $url = app(OfficialDocumentDownloadService::class)->signedUrl($document);
        Storage::disk('local')->put($document->path, '%PDF-tampered');

        self::assertNotSame('', $url);
        $this->assertExceptionRaised(
            fn () => app(OfficialDocumentIntegrityService::class)->assertValidAndIntact($document),
            OfficialDocumentIntegrityException::class,
        );
        self::assertDatabaseHas('esbtp_official_document_events', [
            'official_document_id' => $document->id,
            'event_type' => 'integrity_failed',
        ]);
    }

    public function test_revoked_document_is_rejected_even_with_previously_issued_url(): void
    {
        $document = $this->documents()->issueJuryPv($this->seedIssuableJury(), $this->actor());
        $url = app(OfficialDocumentDownloadService::class)->signedUrl($document);
        $this->documents()->revoke($document, 'Annulation motivée', $this->actor());

        self::assertNotSame('', $url);
        $this->assertExceptionRaised(
            fn () => app(OfficialDocumentIntegrityService::class)->assertValidAndIntact($document->fresh()),
            OfficialDocumentIntegrityException::class,
        );
    }

    public function test_sequence_is_monotonic_per_tenant_and_year(): void
    {
        config(['app.tenant_code' => 'tenant-a']);
        $sequence = app(PvNumberSequenceService::class);

        self::assertSame('PV-20252026-TENANT-A-0001', $sequence->next(1));
        self::assertSame('PV-20252026-TENANT-A-0002', $sequence->next(1));
    }

    public function test_legacy_reconciliation_hashes_existing_pdf_without_snapshot_or_regeneration(): void
    {
        $jury = $this->seedIssuableJury();
        $issued = $this->documents()->issueJuryPv($jury, $this->actor());
        $contents = Storage::disk('local')->get($issued->path);
        DB::table('esbtp_official_document_events')->delete();
        DB::table('esbtp_official_documents')->delete();

        $legacy = app(LegacyJuryPvReconciliationService::class)->reconcile($jury->fresh(), $this->actor());

        self::assertSame(OfficialDocument::STATUS_LEGACY, $legacy->status);
        self::assertSame(hash('sha256', $contents), $legacy->checksum_sha256);
        self::assertNull($legacy->snapshot);
        self::assertNull($legacy->snapshot_sha256);
        self::assertSame($issued->path, $legacy->path);
    }

    public function test_public_verification_accepts_only_correct_code_and_intact_valid_document(): void
    {
        $this->seedIssuableJury();
        $code = str_repeat('A', 48);
        $document = OfficialDocument::query()->create($this->knownDocumentAttributes($code));
        Storage::disk('local')->put($document->path, '%PDF-known');

        $verified = $this->documents()->verify($document->reference, $code, 'fingerprint');

        self::assertTrue($document->is($verified));
        self::assertSame(1, $verified->snapshot['document']['version']);
        self::assertNull($this->documents()->verify($document->reference, 'wrong-code', 'fingerprint'));
        self::assertDatabaseHas('esbtp_official_document_events', [
            'official_document_id' => $document->id,
            'event_type' => 'verified',
        ]);
    }

    public function test_storage_is_compensated_when_database_persistence_fails(): void
    {
        $jury = $this->seedIssuableJury();
        $events = \Mockery::mock(OfficialDocumentEventRecorder::class);
        $events->shouldReceive('record')->once()->andThrow(new \RuntimeException('Échec journal')); 
        $this->app->instance(OfficialDocumentEventRecorder::class, $events);

        $this->assertExceptionRaised(
            fn () => app(OfficialDocumentService::class)->issueJuryPv($jury, $this->actor()),
            \RuntimeException::class,
        );
        self::assertSame([], Storage::disk('local')->allFiles('official-documents'));
        self::assertDatabaseCount('esbtp_official_documents', 0);
    }

    public function test_publication_projects_bulletins_and_jury_status_atomically(): void
    {
        $jury = $this->seedIssuableJury();
        $this->documents()->issueJuryPv($jury, $this->actor());
        app(JuryDeliberationService::class)->publierDecisions($jury->fresh());

        self::assertSame('publie', $jury->fresh()->status);
        self::assertSame(2, DB::table('esbtp_lmd_bulletins')->where('is_published', true)->count());
        self::assertSame('admis', DB::table('esbtp_lmd_bulletins')->where('id', 100)->value('decision_deliberation'));
        self::assertSame('admis_sous_condition', DB::table('esbtp_lmd_bulletins')->where('id', 101)->value('decision_deliberation'));
        self::assertSame(2, DB::table('esbtp_lmd_jury_decisions')->where('locked', true)->count());
    }

    public function test_publication_refuses_tampered_document_without_partial_projection(): void
    {
        $jury = $this->seedIssuableJury();
        $document = $this->documents()->issueJuryPv($jury, $this->actor());
        Storage::disk('local')->put($document->path, '%PDF-tampered');

        $this->assertExceptionRaised(
            fn () => app(JuryDeliberationService::class)->publierDecisions($jury->fresh()),
            OfficialDocumentIntegrityException::class,
        );
        self::assertSame('clos', $jury->fresh()->status);
        self::assertSame(0, DB::table('esbtp_lmd_bulletins')->where('is_published', true)->count());
        self::assertNull(DB::table('esbtp_lmd_bulletins')->where('id', 100)->value('decision_deliberation'));
    }

    public function test_controller_dependencies_resolve_from_container(): void
    {
        self::assertInstanceOf(OfficialDocumentController::class, app(OfficialDocumentController::class));
        self::assertInstanceOf(OfficialDocumentService::class, app(OfficialDocumentService::class));
        self::assertInstanceOf(OfficialDocumentDownloadService::class, app(OfficialDocumentDownloadService::class));
        self::assertInstanceOf(OfficialDocumentIntegrityService::class, app(OfficialDocumentIntegrityService::class));
        self::assertInstanceOf(OfficialDocumentStorage::class, app(OfficialDocumentStorage::class));
    }

    public function test_first_issue_requires_active_deliberation_and_published_replacement_is_forbidden(): void
    {
        $jury = $this->seedIssuableJury();
        DB::table('esbtp_lmd_jurys')->where('id', $jury->id)->update(['status' => 'preparation']);

        $this->assertLogicException(
            fn () => $this->documents()->issueJuryPv($jury->fresh(), $this->actor()),
            'La première émission du PV exige un jury en cours.',
        );

        DB::table('esbtp_lmd_jurys')->where('id', $jury->id)->update(['status' => 'en_cours']);
        $first = $this->documents()->issueJuryPv($jury->fresh(), $this->actor());
        self::assertSame(1, $first->version);
        self::assertSame('clos', $jury->fresh()->status);

        $this->documents()->revoke($first, 'Rectification nécessaire', $this->actor());
        $second = $this->documents()->issueJuryPv($jury->fresh(), $this->actor(), 'Version corrigée');
        self::assertSame(2, $second->version);
        self::assertSame($first->id, $second->supersedes_document_id);
        self::assertSame('clos', $jury->fresh()->status);

        foreach (['publie', 'archive'] as $status) {
            DB::table('esbtp_lmd_jurys')->where('id', $jury->id)->update(['status' => $status]);
            $this->assertLogicException(
                fn () => $this->documents()->issueJuryPv($jury->fresh(), $this->actor(), 'Nouvelle version'),
                'Une rectification atomique est requise pour ce jury publié ou archivé.',
            );
        }
    }

    public function test_snapshot_tamper_is_rejected_and_recorded(): void
    {
        $document = $this->documents()->issueJuryPv($this->seedIssuableJury(), $this->actor());
        $snapshot = $document->snapshot;
        $snapshot['document']['version'] = 99;
        DB::table('esbtp_official_documents')->where('id', $document->id)->update([
            'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
        ]);

        $this->assertThrows(
            fn () => app(OfficialDocumentIntegrityService::class)->assertValidAndIntact($document->fresh()),
            OfficialDocumentIntegrityException::class,
        );
        self::assertDatabaseHas('esbtp_official_document_events', [
            'official_document_id' => $document->id,
            'event_type' => 'integrity_failed',
        ]);
    }

    public function test_unknown_and_wrong_public_codes_are_non_enumerating(): void
    {
        self::assertNull($this->documents()->verify('PV-INCONNU', 'code-invalide', 'test'));

        $document = $this->documents()->issueJuryPv($this->seedIssuableJury(), $this->actor());
        self::assertNull($this->documents()->verify($document->reference, 'code-invalide', 'test'));
        self::assertDatabaseMissing('esbtp_official_document_events', ['event_type' => 'verified']);
    }

    public function test_mutations_reread_jury_and_refuse_stale_models_after_issue(): void
    {
        $jury = $this->seedIssuableJury();
        $staleJury = $jury->fresh();
        $member = $jury->membres()->firstOrFail();
        $decision = $jury->decisions()->firstOrFail();
        $student = \App\Models\ESBTPEtudiant::query()->findOrFail($decision->etudiant_id);
        $this->documents()->issueJuryPv($jury, $this->actor());
        $service = app(JuryDeliberationService::class);

        $mutations = [
            fn () => $service->addOrUpdateMembre($staleJury, ['user_id' => $member->user_id, 'role' => $member->role, 'present' => true]),
            fn () => $service->removeMembre($staleJury, $member),
            fn () => $service->enregistrerSignature($member, $this->signatureData(), $member->user_id),
            fn () => $service->overrideDecision($staleJury, $student, 'ajourne', 'Décision test verrouillée'),
        ];

        foreach ($mutations as $mutation) {
            $this->assertExceptionRaised($mutation, \LogicException::class);
        }
    }

    public function test_sequence_isolated_by_tenant_and_academic_year(): void
    {
        $sequence = app(PvNumberSequenceService::class);
        config(['app.tenant_code' => 'tenant-a']);
        $a1 = $sequence->next(1);
        config(['app.tenant_code' => 'tenant-b']);
        $b1 = $sequence->next(1);
        config(['app.tenant_code' => 'tenant-a']);
        $a2 = $sequence->next(2);

        self::assertStringContainsString('TENANT-A', $a1);
        self::assertStringContainsString('TENANT-B', $b1);
        self::assertStringContainsString('20262027', $a2);
        self::assertNotSame($a1, $a2);
    }

    public function test_mysql_sequence_locking_when_integration_dsn_is_available(): void
    {
        $dsn = getenv('KLASSCI_TEST_MYSQL_DSN');
        if (! is_string($dsn) || $dsn === '') {
            self::markTestSkipped('KLASSCI_TEST_MYSQL_DSN absent, concurrence MySQL non exécutée.');
        }

        $user = (string) getenv('KLASSCI_TEST_MYSQL_USER');
        $password = (string) getenv('KLASSCI_TEST_MYSQL_PASSWORD');
        $first = new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $second = new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
        $table = 'od_sequence_lock_'.bin2hex(random_bytes(4));

        try {
            $first->exec("CREATE TABLE $table (tenant VARCHAR(32), year_id BIGINT, value INT, PRIMARY KEY (tenant, year_id)) ENGINE=InnoDB");
            $first->exec("INSERT INTO $table VALUES ('a', 1, 0)");
            $first->beginTransaction();
            $first->query("SELECT value FROM $table WHERE tenant='a' AND year_id=1 FOR UPDATE");
            $second->exec('SET SESSION innodb_lock_wait_timeout = 1');
            $second->beginTransaction();
            $this->assertMysqlLockBlocks($second, $table);
            $first->exec("UPDATE $table SET value=value+1 WHERE tenant='a' AND year_id=1");
            $first->commit();
            self::assertSame(1, (int) $first->query("SELECT value FROM $table")->fetchColumn());
        } finally {
            if ($first->inTransaction()) {
                $first->rollBack();
            }
            if ($second->inTransaction()) {
                $second->rollBack();
            }
            $first->exec("DROP TABLE IF EXISTS $table");
        }
    }

    private function documents(): OfficialDocumentService
    {
        return app(OfficialDocumentService::class);
    }

    private function actor(): User
    {
        return User::query()->findOrFail(1);
    }

    private function assertExceptionRaised(callable $callback, string $exceptionClass): void
    {
        try {
            $callback();
            self::fail("L'exception $exceptionClass était attendue.");
        } catch (\Throwable $exception) {
            self::assertInstanceOf($exceptionClass, $exception);
        }
    }

    private function assertLogicException(callable $callback, string $message): void
    {
        try {
            $callback();
            self::fail('Une LogicException était attendue.');
        } catch (\LogicException $exception) {
            self::assertSame($message, $exception->getMessage());
        }
    }

    private function assertIssuanceRejected(int $juryId, string $reason): void
    {
        try {
            app(\App\Domain\OfficialDocuments\Services\JuryPvIssuanceGuard::class)->assertIssuable($juryId);
            self::fail('Le guard aurait dû refuser le jury.');
        } catch (JuryPvNotIssuableException $exception) {
            self::assertStringContainsString($reason, implode(' ', $exception->reasons));
        }
    }

    private function assertMysqlLockBlocks(\PDO $connection, string $table): void
    {
        try {
            $connection->exec("UPDATE $table SET value=value+1 WHERE tenant='a' AND year_id=1");
            self::fail('La seconde transaction ne devait pas franchir le verrou.');
        } catch (\PDOException) {
            $connection->rollBack();
            self::assertTrue(true);
        }
    }

    private function knownDocumentAttributes(string $code): array
    {
        $snapshot = ['document' => ['reference' => 'DOC-PV-KNOWN', 'version' => 1]];

        return [
            'document_type' => OfficialDocument::TYPE_LMD_JURY_PV,
            'source_type' => 'test',
            'source_id' => 999,
            'series_key' => 'test-series',
            'version' => 1,
            'reference' => 'DOC-PV-KNOWN',
            'status' => OfficialDocument::STATUS_VALID,
            'valid_series_key' => 'test-series',
            'disk' => 'local',
            'path' => 'official-documents/test.pdf',
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 10,
            'checksum_sha256' => hash('sha256', '%PDF-known'),
            'snapshot' => $snapshot,
            'snapshot_sha256' => hash('sha256', json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'rules_version' => 'v1',
            'template_version' => 'v1',
            'renderer_version' => 'v1',
            'verification_code_digest' => hash('sha256', $code),
            'issued_by' => 1,
            'issued_at' => now(),
        ];
    }
}
