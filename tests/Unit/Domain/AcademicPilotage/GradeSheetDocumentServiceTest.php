<?php

namespace Tests\Unit\Domain\AcademicPilotage;

use App\Domain\AcademicPilotage\Enums\GradeSheetStatus;
use App\Domain\AcademicPilotage\Exceptions\AcademicPilotageException;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Domain\AcademicPilotage\Models\GradeSheetEvent;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentContentValidator;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentDownloadService;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentService;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentStorage;
use App\Domain\AcademicPilotage\Services\GradeSheetEventRecorder;
use App\Http\Requests\AcademicPilotage\UploadGradeSheetDocumentRequest;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Mockery;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class GradeSheetDocumentServiceTest extends AcademicPilotageDatabaseTestCase
{
    private GradeSheetDocumentService $service;

    private GradeSheetDocumentDownloadService $downloads;

    private GradeSheetDocumentStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->storage = new GradeSheetDocumentStorage;
        $this->service = new GradeSheetDocumentService(
            new GradeSheetEventRecorder,
            new GradeSheetDocumentContentValidator,
            $this->storage,
        );
        $this->downloads = new GradeSheetDocumentDownloadService($this->storage);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function test_store_uses_private_local_directory_and_server_metadata(): void
    {
        Carbon::setTestNow('2026-07-11 10:00:00');
        $contents = "%PDF-1.4\nprivate-grade-sheet";
        $file = UploadedFile::fake()->createWithContent('notes-finales.pdf', $contents);
        $sheet = $this->createGradeSheet();

        $result = $this->service->store($sheet, $file, $this->actor(7), 1);

        $this->assertSame('local', $result->disk);
        $this->assertStringStartsWith(
            "academic-pilotage/grade-sheets/{$sheet->id}/",
            $result->path,
        );
        $this->assertSame('notes-finales.pdf', $result->original_name);
        $this->assertSame($file->getMimeType(), $result->mime_type);
        $this->assertSame(strlen($contents), $result->size_bytes);
        $this->assertSame(hash('sha256', $contents), $result->checksum_sha256);
        $this->assertTrue($result->uploaded_at->equalTo(now()));
        $this->assertSame(7, $result->uploaded_by);
        $this->assertSame(2, $sheet->fresh()->lock_version);
        $this->assertSame('document_uploaded', GradeSheetEvent::query()->sole()->event_type);
        Storage::disk('local')->assertExists($result->path);
    }

    public function test_store_deletes_new_file_when_database_save_fails(): void
    {
        $file = UploadedFile::fake()->createWithContent('notes.pdf', '%PDF-1.4');
        $sheet = $this->createGradeSheet();
        Schema::drop('esbtp_grade_sheet_documents');

        try {
            $this->service->store($sheet, $file, $this->actor(3), 1);
            $this->fail('The database exception should be rethrown.');
        } catch (Throwable) {
            $this->assertTrue(true);
        }

        $this->assertSame([], Storage::disk('local')->allFiles(
            "academic-pilotage/grade-sheets/{$sheet->id}"
        ));
    }

    public function test_signed_download_url_expires_after_five_minutes_by_default(): void
    {
        Carbon::setTestNow('2026-07-11 10:00:00');
        $document = new GradeSheetDocument;
        $document->setAttribute('id', 19);

        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->with(
                'esbtp.academic-sheets.documents.download',
                Mockery::on(fn ($expiration): bool => $expiration->equalTo(now()->addMinutes(5))),
                Mockery::on(fn (array $parameters): bool => $parameters['document'] === 19
                    && $parameters['download_expires'] === now()->addMinutes(5)->getTimestamp()
                    && strlen($parameters['document_token']) === 64)
            )
            ->andReturn('https://klassci.test/document/19?signature=valid');

        $this->assertSame(
            'https://klassci.test/document/19?signature=valid',
            $this->downloads->signedUrl($document)
        );
    }

    public function test_stream_downloads_from_local_disk_with_original_name_and_mime_type(): void
    {
        $path = 'academic-pilotage/grade-sheets/42/document.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4');
        $document = $this->document([
            'id' => 12,
            'grade_sheet_id' => 42,
            'disk' => 'remote-disk-is-ignored',
            'path' => $path,
            'original_name' => 'releve-original.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $response = $this->storage->stream($document);

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'releve-original.pdf',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    public function test_stream_logs_identifiers_and_path_then_returns_404_when_file_is_missing(): void
    {
        $path = 'academic-pilotage/grade-sheets/42/missing.pdf';
        $document = $this->document([
            'id' => 17,
            'grade_sheet_id' => 42,
            'path' => $path,
            'original_name' => 'missing.pdf',
            'mime_type' => 'application/pdf',
        ]);

        Log::shouldReceive('warning')->once()->with(
            'Grade sheet document missing from local storage.',
            [
                'grade_sheet_document_id' => 17,
                'grade_sheet_id' => 42,
                'path' => $path,
            ]
        );

        $this->expectException(NotFoundHttpException::class);

        $this->storage->stream($document);
    }

    public function test_upload_request_only_accepts_the_document_with_strong_file_rules(): void
    {
        $rules = (new UploadGradeSheetDocumentRequest)->rules();

        $this->assertSame(['expected_lock_version', 'document'], array_keys($rules));
        $this->assertSame(['required', 'integer', 'min:1'], $rules['expected_lock_version']);
        $this->assertContains('required', $rules['document']);
        $this->assertContains('mimes:pdf,jpg,jpeg,png,xls,xlsx', $rules['document']);
        $this->assertContains(
            'mimetypes:application/pdf,image/jpeg,image/png,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $rules['document']
        );
        $this->assertContains('max:10240', $rules['document']);
    }

    public function test_store_rejects_stale_or_closed_grade_sheet_before_writing_file(): void
    {
        $sheet = $this->createGradeSheet(['lock_version' => 2]);

        foreach ([
            [1, GradeSheetStatus::EXPECTED, AcademicPilotageException::STALE_GRADE_SHEET],
            [2, GradeSheetStatus::VALIDATED, AcademicPilotageException::MUTATION_NOT_ALLOWED],
        ] as [$version, $status, $errorCode]) {
            $sheet->forceFill(['status' => $status->value])->save();

            try {
                $this->service->store(
                    $sheet->fresh(),
                    UploadedFile::fake()->createWithContent('notes.pdf', '%PDF-1.4'),
                    $this->actor(3),
                    $version,
                );
                $this->fail('The upload should have been rejected.');
            } catch (AcademicPilotageException $exception) {
                $this->assertSame($errorCode, $exception->errorCode);
            }
        }

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_store_rejects_a_fake_pdf_even_when_extension_and_mime_are_allowed(): void
    {
        $sheet = $this->createGradeSheet();

        $this->expectException(AcademicPilotageException::class);
        $this->expectExceptionMessage('contenu du document');

        $this->service->store(
            $sheet,
            UploadedFile::fake()->createWithContent('fiche.pdf', '<script>alert(1)</script>'),
            $this->actor(3),
            1,
        );
    }

    private function document(array $attributes): GradeSheetDocument
    {
        $document = new GradeSheetDocument;
        $document->setRawAttributes($attributes, true);

        return $document;
    }
}
