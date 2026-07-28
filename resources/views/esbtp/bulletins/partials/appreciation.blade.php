{{ app(\App\Services\AppreciationScaleService::class)->labelFor($moyenne === null ? null : (float) $moyenne, 'bts', '-') }}
