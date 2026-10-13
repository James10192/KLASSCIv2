<?php

namespace App\Services\Notifications;

use App\Models\ESBTPEtudiant;
use App\Services\Admissions\StudentAccessInvitation;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Send transactional school correspondence to the ONE student account's
 * complete family contact list. One email per unique address, never one CC/BCC
 * with another family's personal information or a password.
 */
final class FamilyWorkflowMailer
{
    public function __construct(private StudentAccessInvitation $access) {}

    /**
     * @param  callable(): Mailable  $message  Fresh mailable for each address.
     * @return int Number of messages accepted by the configured mail transport.
     */
    public function send(ESBTPEtudiant $student, string $event, callable $message): int
    {
        if (! $student->user) {
            Log::warning('Family workflow mail skipped: student has no account', [
                'event' => $event, 'student_id' => $student->id,
            ]);
            return 0;
        }

        $recipients = $this->access->destinataires($student->user);
        $guardian = $student->tuteur;
        if ($guardian && $guardian->email) {
            $recipient = mb_strtolower(trim((string) $guardian->email));
            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $recipient;
            }
        }

        $recipients = array_values(array_unique($recipients));
        $sent = 0;

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send($message());
                $sent++;
            } catch (Throwable $e) {
                // A broken parent address must not prevent the other family
                // addresses receiving their own correspondence.
                Log::warning('Family workflow mail transport refused recipient', [
                    'event' => $event,
                    'student_id' => $student->id,
                    'exception' => $e::class,
                ]);
            }
        }

        Log::info('Family workflow email submitted', [
            'event' => $event,
            'student_id' => $student->id,
            'recipient_count' => count($recipients),
            'accepted_count' => $sent,
        ]);

        return $sent;
    }
}
