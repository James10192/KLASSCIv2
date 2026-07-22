<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Models\ESBTPLMDJury;
use Illuminate\Support\Facades\DB;

class PvNumberSequenceService
{
    public function next(int $yearId): string
    {
        $tenant = $this->tenantCode();
        $this->ensureSequenceExists($tenant, $yearId);

        $value = DB::transaction(function () use ($tenant, $yearId): int {
            $row = DB::table('esbtp_pv_sequences')
                ->where('tenant_code', $tenant)
                ->where('annee_universitaire_id', $yearId)
                ->lockForUpdate()->first();
            $next = ((int) $row->last_value) + 1;
            DB::table('esbtp_pv_sequences')->where('id', $row->id)
                ->update(['last_value' => $next, 'updated_at' => now()]);
            return $next;
        }, 3);

        return sprintf('PV-%s-%s-%04d', $this->yearLabel($yearId), $tenant, $value);
    }

    private function ensureSequenceExists(string $tenant, int $yearId): void
    {
        DB::table('esbtp_pv_sequences')->insertOrIgnore([
            'tenant_code' => $tenant,
            'annee_universitaire_id' => $yearId,
            'last_value' => $this->existingMaximum($tenant, $yearId),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function existingMaximum(string $tenant, int $yearId): int
    {
        return ESBTPLMDJury::query()->where('annee_universitaire_id', $yearId)
            ->where('pv_numero', 'like', '%-'.$tenant.'-%')->pluck('pv_numero')
            ->map(fn (?string $number): int => preg_match('/-(\d{4})$/', (string) $number, $match) ? (int) $match[1] : 0)
            ->max() ?? 0;
    }

    private function tenantCode(): string
    {
        return strtoupper((string) (config('app.tenant_code') ?? env('TENANT_CODE', 'PRES')));
    }

    private function yearLabel(int $yearId): string
    {
        $label = DB::table('esbtp_annee_universitaires')->where('id', $yearId)->value('libelle') ?? (string) $yearId;
        return preg_replace('/[^A-Za-z0-9]/', '', $label);
    }
}
