<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Models\ESBTPAnneeUniversitaire;
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

    /**
     * Libelle de l'annee tel qu'il doit apparaitre sur un document legal.
     *
     * On passe par l'accesseur du modele (`display_name`) et non par une lecture
     * directe de `libelle` : cette colonne n'est renseignee par aucune ecriture
     * applicative (elle est absente du `$fillable`), donc la lecture directe
     * retombait sur l'identifiant technique et produisait « PV-7-YAKRO-0001 ».
     * L'accesseur, lui, essaie `name`, puis `libelle`, puis reconstruit
     * « 2025-2026 » a partir des dates.
     *
     * Le nettoyage d'origine est conserve tel quel : les separateurs sont
     * retires, « 2025-2026 » devient « 20252026 ». Deux tests figent cette
     * forme (OfficialDocumentServiceTest, ExamenSchedulingNumeroConvocationTest)
     * alors que .claude/rules/jury-deliberation-uemoa.md documente la forme
     * avec tirets — cet ecart est anterieur et se tranche a part, changer la
     * forme d'un numero legal n'est pas la meme decision que corriger sa source.
     */
    private function yearLabel(int $yearId): string
    {
        $annee = ESBTPAnneeUniversitaire::query()->withTrashed()->find($yearId);
        $label = trim((string) ($annee?->display_name ?? ''));

        if ($label === '') {
            $label = (string) $yearId;
        }

        $label = preg_replace('/[^A-Za-z0-9]/', '', $label);

        return $label !== '' ? $label : (string) $yearId;
    }
}
