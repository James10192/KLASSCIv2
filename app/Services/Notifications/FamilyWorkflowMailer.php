<?php

namespace App\Services\Notifications;

use App\Models\ESBTPEtudiant;
use App\Services\Emails\AnalyseurEmail;
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
    public function __construct(private AnalyseurEmail $emailAnalyzer) {}

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

        // Deliberately scope to this student. StudentAccessInvitation::destinataires()
        // is for LOGIN security messages and traverses every student sharing the
        // account; reusing it for grades/fees would disclose another student's
        // confidential records to parents who are not linked to this student.
        $student->loadMissing('parents');
        $candidates = [
            $student->user->email,
            $student->email_personnel,
            $student->email,
        ];
        foreach ($student->parents as $parent) {
            $candidates[] = $parent->email;
        }

        $recipients = [];
        foreach ($candidates as $candidate) {
            $email = mb_strtolower(trim((string) $candidate));
            if ($email !== '' && $this->emailAnalyzer->analyser($email)->joignable()) {
                $recipients[$email] = $email;
            }
        }
        $recipients = array_values($recipients);
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
