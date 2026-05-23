<?php

namespace App\Services;

use App\Enums\JustificationStatus;
use App\Models\ESBTPAttendance;
use App\Models\ESBTPEtudiant;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Service workflow justification d'absence.
 *
 * Centralise :
 *   - soumission étudiant (PENDING)
 *   - traitement admin (APPROVED / REJECTED)
 *   - upload document privé (disk local)
 *   - URL signée temporaire pour visualisation/download
 *
 * Aucun appel direct au controller — passe par Policy + dispatch notifications
 * via NotificationService.
 */
class AbsenceJustificationService
{
    /**
     * Disque de stockage : PRIVÉ (storage/app/) pour sécurité PII médicale.
     * Les certificats médicaux NE doivent PAS être servis via URL publique.
     */
    private const STORAGE_DISK = 'local';

    /**
     * Dossier sous storage/app/.
     */
    private const STORAGE_PATH = 'absences/justifications';

    /**
     * Taille max upload (5 MB).
     * Certificats médicaux scannés font typiquement 2-4 MB.
     */
    public const MAX_DOCUMENT_SIZE_KB = 5120;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Soumission ou re-soumission d'une justification par l'étudiant.
     *
     * Précondition (vérifiée par Policy::submit) : étudiant est proprio + statut != APPROVED.
     *
     * @param  array{justification: string, document?: UploadedFile}  $data
     */
    public function submitJustification(ESBTPAttendance $absence, User $student, array $data): ESBTPAttendance
    {
        return DB::transaction(function () use ($absence, $student, $data) {
            // Upload optional document on PRIVATE disk
            $documentPath = $absence->document_path; // garde l'existant si pas de nouveau fichier
            if (isset($data['document']) && $data['document'] instanceof UploadedFile) {
                $newPath = $data['document']->store(self::STORAGE_PATH, self::STORAGE_DISK);
                if ($newPath) {
                    // Supprimer l'ancien si présent (purge anti orphan)
                    if ($absence->document_path && Storage::disk(self::STORAGE_DISK)->exists($absence->document_path)) {
                        Storage::disk(self::STORAGE_DISK)->delete($absence->document_path);
                    }
                    $documentPath = $newPath;
                }
            }

            $absence->commentaire = trim((string) $data['justification']);
            $absence->document_path = $documentPath;
            $absence->justified_at = now();
            $absence->justification_status = JustificationStatus::PENDING;

            // Re-soumission : on nettoie le commentaire admin précédent
            // (gardé en historique audits via OwenIt\Auditing)
            $absence->admin_comment = null;
            $absence->processed_at = null;
            $absence->processed_by_id = null;

            $absence->save();

            // Notify admins — Phase 8b strangler fig via AbsenceNotifier
            $etudiant = ESBTPEtudiant::find($absence->etudiant_id);
            if ($etudiant) {
                app(\App\Domain\Notifications\Notifiers\AbsenceNotifier::class)
                    ->justificationSoumise($absence, $etudiant);
            }

            Log::info('Justification submitted', [
                'absence_id' => $absence->id,
                'etudiant_id' => $absence->etudiant_id,
                'student_user_id' => $student->id,
                'has_document' => $documentPath !== null,
            ]);

            return $absence->refresh();
        });
    }

    /**
     * Traitement de la justification par l'admin (approve OU reject).
     *
     * Précondition (vérifiée par Policy::process) : permission attendances.justify_process.
     */
    public function processJustification(
        ESBTPAttendance $absence,
        User $admin,
        JustificationStatus $newStatus,
        ?string $adminComment = null
    ): ESBTPAttendance {
        if (!in_array($newStatus, [JustificationStatus::APPROVED, JustificationStatus::REJECTED], true)) {
            throw new \InvalidArgumentException('processJustification accepts only APPROVED or REJECTED.');
        }

        return DB::transaction(function () use ($absence, $admin, $newStatus, $adminComment) {
            $absence->justification_status = $newStatus;
            $absence->admin_comment = $adminComment;
            $absence->processed_at = now();
            $absence->processed_by_id = $admin->id;

            // APPROVED : marquer absence comme "excuse" pour cohérence avec stats existantes
            if ($newStatus === JustificationStatus::APPROVED) {
                $absence->statut = 'excuse';
            }

            $absence->save();

            $etudiant = ESBTPEtudiant::find($absence->etudiant_id);
            if ($etudiant) {
                // Phase 8b strangler fig via AbsenceNotifier
                $absenceNotifier = app(\App\Domain\Notifications\Notifiers\AbsenceNotifier::class);
                if ($newStatus === JustificationStatus::APPROVED) {
                    $absenceNotifier->justificationApprouvee($absence, $etudiant, $admin);
                } else {
                    $absenceNotifier->justificationRejetee($absence, $etudiant, $adminComment, $admin);
                }
            }

            Log::info('Justification processed', [
                'absence_id' => $absence->id,
                'admin_user_id' => $admin->id,
                'new_status' => $newStatus->value,
                'has_admin_comment' => !empty($adminComment),
            ]);

            return $absence->refresh();
        });
    }

    /**
     * Un étudiant peut re-soumettre uniquement si rejeté ou jamais soumis (PENDING).
     * Si APPROVED → bloqué.
     */
    public function canResubmit(ESBTPAttendance $absence): bool
    {
        if ($absence->justification_status === null) {
            return true;
        }
        return $absence->justification_status->isEditableByStudent();
    }

    /**
     * URL signée temporaire vers le document. Default 5 min pour limiter
     * la fenêtre de partage accidentel d'une URL.
     */
    public function viewDocumentSignedUrl(ESBTPAttendance $absence, int $minutes = 5): ?string
    {
        if (empty($absence->document_path)) {
            return null;
        }

        return URL::temporarySignedRoute(
            'esbtp.justifications.document',
            now()->addMinutes($minutes),
            ['absence' => $absence->id]
        );
    }

    /**
     * Stream du fichier private. Appelée uniquement après authorize Policy::viewDocument.
     */
    public function streamDocument(ESBTPAttendance $absence)
    {
        if (empty($absence->document_path)) {
            abort(404, 'Document non disponible.');
        }
        if (!Storage::disk(self::STORAGE_DISK)->exists($absence->document_path)) {
            Log::warning('Justification document missing on disk', [
                'absence_id' => $absence->id,
                'path' => $absence->document_path,
            ]);
            abort(404, 'Document non trouvé sur le serveur.');
        }
        return Storage::disk(self::STORAGE_DISK)->download($absence->document_path);
    }
}
