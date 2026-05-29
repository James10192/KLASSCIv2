<?php

namespace App\Domain\Notifications;

use App\Helpers\SettingsHelper;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPParent;
use App\Models\ParentNotificationLog;
use App\Models\ParentNotificationPreference;
use App\Services\SmsService;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Orchestrateur multi-canal pour notifications parents.
 *
 * Extrait de NotificationService::sendMultiChannelNotification (god-class).
 * Service partagé utilisé par tous les Notifiers parents (Inscription, Paiement,
 * Absence, Bulletin, etc.) pour dispatcher selon les préférences :
 *
 *  - Email (toujours prioritaire, 0 FCFA)
 *  - WhatsApp (Meta Cloud API, ~2.4 FCFA Utility Côte d'Ivoire 2026)
 *  - SMS (fallback si pas WhatsApp ou WA échoué, ~6-7 FCFA)
 *
 * Pour chaque canal, log un ParentNotificationLog (audit + tracking coûts).
 *
 * @see app/Services/NotificationService.php lignes 2315-2486 (legacy)
 * @see app/Models/ParentNotificationLog.php (tracking persistance)
 */
class MultiChannelDispatcher
{
    /**
     * Mapping notification_type → Mailable class côté email.
     *
     * À étendre quand de nouveaux types métier sont créés.
     */
    private const EMAIL_MAILABLES = [
        'inscription' => \App\Mail\Parents\InscriptionConfirmationMail::class,
        'reinscription' => \App\Mail\Parents\ReinscriptionConfirmationMail::class,
        'paiement_created' => \App\Mail\Parents\PaiementCreatedMail::class,
        'paiement_valide' => \App\Mail\Parents\PaiementValideMail::class,
        'paiement_rejete' => \App\Mail\Parents\PaiementRejeteMail::class,
        'paiement_relance' => \App\Mail\Parents\PaiementRelanceMail::class,
        'absence' => \App\Mail\Parents\AbsenceNotificationMail::class,
        'low_attendance' => \App\Mail\Parents\LowAttendanceMail::class,
        'bulletin_publie' => \App\Mail\Parents\BulletinPublishedMail::class,
        'notes_faibles' => \App\Mail\Parents\LowGradesMail::class,
        'note_publiee' => \App\Mail\Parents\NotePublishedMail::class,
    ];

    public function __construct(
        protected WhatsAppService $whatsappService,
        protected SmsService $smsService,
    ) {
    }

    /**
     * Dispatch une notification sur tous les canaux activés du parent.
     *
     * Ordre : Email → WhatsApp → SMS (fallback si WA off ou échoué).
     *
     * @return array{email: bool, whatsapp: bool, sms: bool}
     */
    public function dispatch(
        ESBTPParent $tuteur,
        ESBTPEtudiant $etudiant,
        string $notificationType,
        array $data,
        ParentNotificationPreference $preferences,
    ): array {
        $results = [
            'email' => false,
            'whatsapp' => false,
            'sms' => false,
        ];

        try {
            // 1. EMAIL — toujours prioritaire si activé + tuteur a une adresse
            if ($preferences->hasChannel('email') && $tuteur->email) {
                $results['email'] = $this->sendEmail($tuteur, $etudiant, $notificationType, $data);
            }

            // 2. WHATSAPP — si activé globalement + préférence parent + téléphone
            if (env('WHATSAPP_ENABLED', false)
                && $preferences->hasChannel('whatsapp')
                && $tuteur->telephone) {
                $results['whatsapp'] = $this->sendWhatsApp($tuteur, $etudiant, $notificationType, $data);
            }

            // 3. SMS — fallback uniquement si WA échoué OU parent n'a pas WhatsApp activé
            if (env('SMS_ENABLED', false)
                && $preferences->hasChannel('sms')
                && $tuteur->telephone
                && (! $preferences->hasChannel('whatsapp') || ! $results['whatsapp'])) {
                $results['sms'] = $this->sendSms($tuteur, $etudiant, $notificationType, $data);
            }

            return $results;
        } catch (Throwable $e) {
            Log::error('[multi-channel] Erreur dispatch', [
                'type' => $notificationType,
                'parent_id' => $tuteur->id,
                'error' => $e->getMessage(),
            ]);

            return $results;
        }
    }

    /**
     * Charge les paramètres de l'établissement (school name, logo, etc.) pour
     * enrichissement des templates email/WhatsApp.
     *
     * Logo : public_path() pour $message->embed() (CID attachment Gmail-compatible).
     */
    public function getSchoolSettings(): array
    {
        $logoPath = SettingsHelper::get('school_logo', '');
        $logoFullPath = null;

        if ($logoPath) {
            $publicPath = public_path('storage/' . $logoPath);
            if (file_exists($publicPath)) {
                $logoFullPath = $publicPath;
            }
        }

        return [
            'school_name' => SettingsHelper::get('school_name', 'KLASSCI'),
            'school_address' => SettingsHelper::get('school_address', ''),
            'school_phone' => SettingsHelper::get('school_phone', ''),
            'school_email' => SettingsHelper::get('school_email', ''),
            'school_logo' => null,
            'schoolLogoPath' => $logoFullPath,
        ];
    }

    private function sendEmail(
        ESBTPParent $tuteur,
        ESBTPEtudiant $etudiant,
        string $notificationType,
        array $data,
    ): bool {
        $log = null;

        try {
            $log = ParentNotificationLog::create([
                'parent_id' => $tuteur->id,
                'etudiant_id' => $etudiant->id,
                'notification_type' => $notificationType,
                'channel' => 'email',
                'status' => 'pending',
                'recipient' => $tuteur->email,
                'cost_fcfa' => 0,
            ]);

            $mailClass = self::EMAIL_MAILABLES[$notificationType] ?? null;

            if (! $mailClass) {
                $log->markAsFailed('Type de notification non mappé vers Mailable: ' . $notificationType);

                return false;
            }

            Mail::to($tuteur->email)->send(new $mailClass($data));
            $log->markAsSent();

            return true;
        } catch (Throwable $e) {
            $log?->markAsFailed($e->getMessage());
            Log::error('[multi-channel] Email échec', [
                'type' => $notificationType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendWhatsApp(
        ESBTPParent $tuteur,
        ESBTPEtudiant $etudiant,
        string $notificationType,
        array $data,
    ): bool {
        $log = null;

        try {
            $log = ParentNotificationLog::create([
                'parent_id' => $tuteur->id,
                'etudiant_id' => $etudiant->id,
                'notification_type' => $notificationType,
                'channel' => 'whatsapp',
                'status' => 'pending',
                'recipient' => $tuteur->telephone,
                // Meta Utility 'Rest of Africa' 2026 : $0.0040/msg ≈ 2.4 FCFA (taux ~600 XOF/USD)
                'cost_fcfa' => 2.4,
            ]);

            $result = match ($notificationType) {
                'inscription' => $this->whatsappService->sendInscriptionNotification($tuteur->telephone, $data),
                'paiement_valide' => $this->whatsappService->sendPaiementValideNotification($tuteur->telephone, $data),
                'paiement_rejete' => $this->whatsappService->sendPaiementRejeteNotification($tuteur->telephone, $data),
                'absence' => $this->whatsappService->sendAbsenceNotification($tuteur->telephone, $data),
                'bulletin_publie' => $this->whatsappService->sendBulletinPublishedNotification($tuteur->telephone, $data),
                'notes_faibles' => $this->whatsappService->sendLowGradesNotification($tuteur->telephone, $data),
                default => false,
            };

            if ($result) {
                $externalId = is_array($result) ? ($result['messages'][0]['id'] ?? null) : null;
                $log->markAsSent($externalId);

                return true;
            }

            $log->markAsFailed('Échec envoi WhatsApp');

            return false;
        } catch (Throwable $e) {
            $log?->markAsFailed($e->getMessage());
            Log::error('[multi-channel] WhatsApp échec', [
                'type' => $notificationType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function sendSms(
        ESBTPParent $tuteur,
        ESBTPEtudiant $etudiant,
        string $notificationType,
        array $data,
    ): bool {
        $log = null;

        try {
            $log = ParentNotificationLog::create([
                'parent_id' => $tuteur->id,
                'etudiant_id' => $etudiant->id,
                'notification_type' => $notificationType,
                'channel' => 'sms',
                'status' => 'pending',
                'recipient' => $tuteur->telephone,
                // SMS Côte d'Ivoire bulk 2026 : 5-8 FCFA selon provider, médiane ~7
                'cost_fcfa' => 7,
            ]);

            $result = match ($notificationType) {
                'inscription' => $this->smsService->sendInscriptionNotification($tuteur->telephone, $data),
                'paiement_valide' => $this->smsService->sendPaiementValideNotification($tuteur->telephone, $data),
                'paiement_rejete' => $this->smsService->sendPaiementRejeteNotification($tuteur->telephone, $data),
                'absence' => $this->smsService->sendAbsenceNotification($tuteur->telephone, $data),
                'bulletin_publie' => $this->smsService->sendBulletinPublishedNotification($tuteur->telephone, $data),
                'notes_faibles' => $this->smsService->sendLowGradesNotification($tuteur->telephone, $data),
                default => false,
            };

            if ($result) {
                $log->markAsSent();

                return true;
            }

            $log->markAsFailed('Échec envoi SMS');

            return false;
        } catch (Throwable $e) {
            $log?->markAsFailed($e->getMessage());
            Log::error('[multi-channel] SMS échec', [
                'type' => $notificationType,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
