<?php

namespace App\Domain\OfficialDocuments\Services;

use App\Domain\OfficialDocuments\Models\OfficialDocument;
use App\Helpers\SettingsHelper;
use App\Http\Controllers\ESBTPBulletinController;
use App\Http\Controllers\ESBTPLMDBulletinController;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPLMDBulletin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Throwable;

/**
 * Un bulletin publié est une pièce: on fige les octets exacts, pas seulement
 * sa moyenne. Même mécanisme de stockage privé, version et contrôle SHA-256
 * que les PV de jury et les relevés LMD (pas de seconde table d'archives).
 *
 * Les bulletins historiques publiés avant cette fonctionnalité restent
 * NON archivés : ne jamais fabriquer a posteriori un faux original.
 */
final class BulletinArchiveService
{
    public const TYPE_BTS = 'bts_bulletin';
    public const TYPE_LMD = 'lmd_bulletin';

    public function __construct(
        private readonly OfficialDocumentStorage $storage,
        private readonly OfficialDocumentIntegrityService $integrity,
        private readonly OfficialDocumentEventRecorder $events,
        private readonly OfficialDocumentSnapshotIntegrity $snapshots,
    ) {}

    /** @return OfficialDocument|null Archive émise lors de la publication. */
    public function archive(ESBTPBulletin|ESBTPLMDBulletin $bulletin): ?OfficialDocument
    {
        return $this->serie($bulletin)
            ->where('status', OfficialDocument::STATUS_VALID)
            ->first();
    }

    /**
     * La publication et l'archivage sont atomiques côté base. Si le PDF n'a
     * pu être enregistré et relu, la publication est annulée.
     *
     * Il ne s'agit pas de régénération : un bulletin déjà publié mais sans
     * archive est historique et doit faire l'objet d'une régularisation.
     */
    public function publier(ESBTPBulletin|ESBTPLMDBulletin $bulletin, User $acteur): OfficialDocument
    {
        $storedPath = null;
        try {
            return DB::transaction(function () use ($bulletin, $acteur, &$storedPath): OfficialDocument {
                $locked = $bulletin::query()->lockForUpdate()->findOrFail($bulletin->getKey());
                $current = $this->serie($locked)->lockForUpdate()->first();

                if ($locked->is_published) {
                    if ($current) {
                        return $this->integrity->assertValidAndIntact($current, false, $acteur->id);
                    }
                    throw new LogicException(
                        'Bulletin historique publié sans archive. Une nouvelle émission ne peut pas reconstituer rétroactivement son original.'
                    );
                }

                if ($current) {
                    throw new LogicException('Une archive officielle existe déjà : publication non autorisée sans rectification versionnée.');
                }
                if ($locked->moyenne_generale === null) {
                    throw new RuntimeException('Moyenne du bulletin absente. Générez et contrôlez les résultats avant publication.');
                }

                // Le modèle LMD fige ses résultats ECUE au moment précis
                // de cette transition. Le PDF est produit APRÈS cette étape.
                $locked->is_published = true;
                $locked->save();

                $isBts = $locked instanceof ESBTPBulletin;
                $type = $isBts ? self::TYPE_BTS : self::TYPE_LMD;
                $contents = $isBts
                    ? app(ESBTPBulletinController::class)->buildBulletinPdf($locked, false)->output()
                    : app(ESBTPLMDBulletinController::class)->rendrePdfPourExport($locked)->output();

                if (! str_starts_with($contents, '%PDF')) {
                    throw new RuntimeException('Le rendu du bulletin ne produit pas un PDF valide.');
                }

                $reference = sprintf('DOC-%s-%d-V1-%s', strtoupper($type), $locked->id, strtoupper(Str::random(8)));
                $seriesKey = $this->serieKey($locked);
                $storedPath = $this->storage->storePdf($seriesKey, 1, $reference, $contents);
                $sha = hash('sha256', $contents);
                if (! hash_equals($sha, $this->storage->checksum($storedPath))) {
                    throw new RuntimeException('L’empreinte du PDF conservé ne correspond pas au document produit.');
                }

                $snapshot = $this->snapshot($locked, $reference);
                $document = OfficialDocument::query()->create([
                    'document_type' => $type,
                    'source_type' => $locked::class,
                    'source_id' => $locked->id,
                    'series_key' => $seriesKey,
                    'valid_series_key' => $seriesKey,
                    'version' => 1,
                    'reference' => $reference,
                    'status' => OfficialDocument::STATUS_VALID,
                    'disk' => OfficialDocumentStorage::DISK,
                    'path' => $storedPath,
                    'original_name' => sprintf('bulletin_%s_%d_S%s_v1.pdf', $isBts ? 'bts' : 'lmd', $locked->etudiant_id, $isBts ? $locked->periode : $locked->semestre),
                    'mime_type' => 'application/pdf',
                    'size_bytes' => strlen($contents),
                    'checksum_sha256' => $sha,
                    'snapshot' => $snapshot,
                    'snapshot_sha256' => hash('sha256', $this->snapshots->canonicalJson($snapshot)),
                    'rules_version' => 'bulletin-v1',
                    'template_version' => $isBts ? SettingsHelper::get('bulletin_style', 'yakro') : 'lmd-bulletin-v1',
                    'renderer_version' => 'dompdf-v2',
                    'issued_by' => $acteur->id,
                    'issued_at' => now(),
                ]);

                $this->events->record($document, 'issued', $acteur->id, [
                    'type' => $type,
                    'bulletin_id' => $locked->id,
                    'year_id' => $locked->annee_universitaire_id,
                ]);

                return $document;
            });
        } catch (Throwable $e) {
            // L'objet physique n'est pas transactionnel SQL : l'effacer si la
            // transaction échoue, pour ne laisser aucun faux original orphelin.
            if ($storedPath !== null) {
                $this->storage->delete($storedPath);
            }
            throw $e;
        }
    }

    /**
     * Restitution BINAIRE: ni recalcul, ni re-rendu, ni consultation de notes.
     * La vérification inclut le snapshot et les octets exacts du PDF.
     */
    public function telecharger(OfficialDocument $document, bool $inline = false)
    {
        if (! in_array($document->document_type, [self::TYPE_BTS, self::TYPE_LMD], true)) {
            throw new LogicException('Le type de document demandé n’est pas un bulletin.');
        }
        $this->integrity->assertValidAndIntact($document, false, auth()->id());

        return $this->storage->stream($document, $inline);
    }

    /** Octets immuables pour les exports groupés et le chatbot autorisés. */
    public function octets(OfficialDocument $document): string
    {
        if (! in_array($document->document_type, [self::TYPE_BTS, self::TYPE_LMD], true)) {
            throw new LogicException('Type de document incompatible avec un bulletin.');
        }
        $this->integrity->assertValidAndIntact($document, false, auth()->id());

        return $this->storage->contents($document->path);
    }

    private function serie(ESBTPBulletin|ESBTPLMDBulletin $bulletin)
    {
        return OfficialDocument::query()
            ->forSource($bulletin::class, (int) $bulletin->getKey())
            ->where('document_type', $bulletin instanceof ESBTPBulletin ? self::TYPE_BTS : self::TYPE_LMD)
            ->where('series_key', $this->serieKey($bulletin));
    }

    private function serieKey(ESBTPBulletin|ESBTPLMDBulletin $bulletin): string
    {
        return sprintf('%s:%d', $bulletin instanceof ESBTPBulletin ? 'bts-bulletin' : 'lmd-bulletin', $bulletin->id);
    }

    /** @return array<string, mixed> */
    private function snapshot(ESBTPBulletin|ESBTPLMDBulletin $bulletin, string $reference): array
    {
        $isBts = $bulletin instanceof ESBTPBulletin;
        $reglages = $isBts
            ? ['bulletin_moyenne_mode', 'bulletin_bloc_general_coef', 'bulletin_bloc_professionnel_coef', 'bulletin_show_attendance_note']
            : ['lmd_ponderation_cc_examen', 'lmd_cc_weight', 'lmd_exam_weight', 'lmd_compensation_inter_ue', 'lmd_compensation_intra_ue', 'lmd_validation_threshold'];

        $config = [];
        foreach ($reglages as $cle) {
            $config[$cle] = SettingsHelper::get($cle);
        }

        // Capturer les valeurs effectivement enregistrées, et non une nouvelle
        // projection qui pourrait dépendre des réglages d'une autre année.
        $resultats = $isBts
            ? $bulletin->resultats()->get()->map(fn ($m) => $m->getAttributes())->all()
            : [
                'ue' => $bulletin->resultatsUEs()->get()->map(fn ($m) => $m->getAttributes())->all(),
                'ecue' => $bulletin->resultatsECUEs()->get()->map(fn ($m) => $m->getAttributes())->all(),
            ];

        return [
            'document' => ['reference' => $reference, 'version' => 1],
            'bulletin' => $bulletin->getAttributes(),
            'resultats' => $resultats,
            'reglages' => $config,
            'issued_at' => now()->toIso8601String(),
        ];
    }
}
