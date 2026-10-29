<?php

namespace App\Http\Controllers\AcademicPilotage;

use App\Domain\AcademicPilotage\Models\GradeSheet;
use App\Domain\AcademicPilotage\Models\GradeSheetDocument;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentDownloadService;
use App\Domain\AcademicPilotage\Services\GradeSheetDocumentService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicPilotage\UploadGradeSheetDocumentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GradeSheetDocumentController extends Controller
{
    public function upload(
        UploadGradeSheetDocumentRequest $request,
        GradeSheet $sheet,
        GradeSheetDocumentService $service,
        GradeSheetDocumentDownloadService $downloads,
    ): JsonResponse {
        $this->authorize('uploadDocument', $sheet);

        $document = $service->store(
            $sheet,
            $request->file('document'),
            $request->user(),
            $request->integer('expected_lock_version'),
        );

        return response()->json([
            'ok' => true,
            'message' => 'Le document a été ajouté au bordereau.',
            'document' => [
                'id' => $document->getKey(),
                'grade_sheet_id' => $document->grade_sheet_id,
                'original_name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'size_bytes' => $document->size_bytes,
                'checksum_sha256' => $document->checksum_sha256,
                'uploaded_by' => $document->uploaded_by,
                'uploaded_at' => $document->uploaded_at?->toIso8601String(),
                'signed_url' => $downloads->signedUrl($document),
            ],
            'grade_sheet_lock_version' => $sheet->refresh()->lock_version,
        ], 201);
    }

    public function download(
        GradeSheetDocument $document,
        GradeSheetDocumentDownloadService $downloads,
        Request $request,
    ): StreamedResponse {
        $this->authorize('view', $document);

        return $downloads->download(
            $document,
            $request->integer('download_expires'),
            $request->query('document_token'),
        );
    }
}
