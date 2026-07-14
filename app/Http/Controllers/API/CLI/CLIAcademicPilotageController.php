<?php

namespace App\Http\Controllers\API\CLI;

use App\Domain\AcademicPilotage\Services\AcademicPilotageBackfillService;
use App\Http\Controllers\API\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CLIAcademicPilotageController extends BaseApiController
{
    public function diagnose(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:read')) {
            return $this->errorResponse('Token missing cli:read ability', [], 403);
        }

        $validated = $request->validate($this->scopeRules());

        return $this->runJsonCommand('academic-pilotage:diagnose', $this->scopeParams($validated));
    }

    public function backfill(Request $request, AcademicPilotageBackfillService $backfill): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate($this->scopeRules() + [
            'dry_run' => ['sometimes', 'boolean'],
            'confirm' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:5000'],
        ]);
        $dryRun = (bool) ($validated['dry_run'] ?? true);

        if (! $dryRun && ! ($validated['confirm'] ?? false)) {
            return $this->errorResponse(
                'Real backfill requires confirm=true. Run dry_run=true first.',
                [],
                422,
            );
        }

        $result = $backfill->run([
            'dry_run' => $dryRun,
            'year_id' => $validated['year_id'] ?? null,
            'class_id' => $validated['class_id'] ?? null,
            'period' => $validated['period'] ?? null,
            'limit' => $validated['limit'] ?? 500,
        ], $request->user());

        $status = $result['failed'] > 0 ? 207 : 200;

        return $this->successResponse($result, 'Academic pilotage backfill completed', [], $status);
    }

    public function refresh(Request $request): JsonResponse
    {
        if (! $request->user()->tokenCan('cli:admin')) {
            return $this->errorResponse('Token missing cli:admin ability', [], 403);
        }

        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:1000'],
        ]);

        try {
            $exitCode = Artisan::call('academic-pilotage:refresh', [
                '--limit' => (int) ($validated['limit'] ?? 100),
            ]);

            if ($exitCode !== 0) {
                return $this->errorResponse('Academic pilotage refresh failed.', [
                    'exit_code' => $exitCode,
                    'output' => Artisan::output(),
                ], 500);
            }

            return $this->successResponse([
                'exit_code' => $exitCode,
                'output' => Artisan::output(),
            ], 'Academic pilotage refresh completed');
        } catch (\Throwable $exception) {
            Log::error('CLI: academic pilotage refresh failed', [
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResponse('Academic pilotage refresh failed. Check server logs.', [], 500);
        }
    }

    private function runJsonCommand(string $command, array $params): JsonResponse
    {
        try {
            $exitCode = Artisan::call($command, $params + ['--json' => true]);
            $payload = json_decode(Artisan::output(), true);

            if ($exitCode !== 0 || ! is_array($payload)) {
                return $this->errorResponse('Academic pilotage diagnosis failed.', [
                    'exit_code' => $exitCode,
                    'output' => Str::limit(trim(Artisan::output()), 2000),
                ], 500);
            }

            return $this->successResponse($payload, 'Academic pilotage diagnosis completed');
        } catch (\Throwable $exception) {
            Log::error('CLI: academic pilotage diagnosis failed', [
                'error' => $exception->getMessage(),
            ]);

            return $this->errorResponse('Academic pilotage diagnosis failed. Check server logs.', [], 500);
        }
    }

    private function scopeRules(): array
    {
        return [
            'year_id' => ['sometimes', 'integer', 'min:1'],
            'class_id' => ['sometimes', 'integer', 'min:1'],
            'period' => ['sometimes', 'string', 'in:semestre1,semestre2,annuel'],
        ];
    }

    private function scopeParams(array $validated): array
    {
        return array_filter([
            '--year-id' => $validated['year_id'] ?? null,
            '--class-id' => $validated['class_id'] ?? null,
            '--period' => $validated['period'] ?? null,
        ], static fn ($value) => $value !== null);
    }
}
