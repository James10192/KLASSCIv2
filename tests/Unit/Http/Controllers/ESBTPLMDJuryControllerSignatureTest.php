<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\ESBTPLMDJuryController;
use App\Domain\OfficialDocuments\Services\LegacyJuryPvReconciliationService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentDownloadService;
use App\Domain\OfficialDocuments\Services\OfficialDocumentService;
use App\Services\JuryDeliberationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ESBTPLMDJuryControllerSignatureTest extends TestCase
{
    public function test_signature_rejects_non_png_data_url_and_invalid_base64(): void
    {
        $controller = new ESBTPLMDJuryController(
            $this->createMock(JuryDeliberationService::class),
            $this->createMock(OfficialDocumentService::class),
            $this->createMock(OfficialDocumentDownloadService::class),
            $this->createMock(LegacyJuryPvReconciliationService::class),
        );
        $method = new ReflectionMethod($controller, 'decodePngSignature');
        $method->setAccessible(true);

        $this->expectException(InvalidArgumentException::class);
        $method->invoke($controller, 'data:image/png;base64,not-base64!');
    }
}
