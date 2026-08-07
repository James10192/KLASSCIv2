<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `note_publiee` and `rappel_paiement` were already produced by
 * MailPulseParentNotificationLog::notificationType() but were missing from the
 * column definition. On MySQL in strict mode the outbox insert therefore threw,
 * and the caller swallowed it: every published-grade and fee-reminder parent
 * notification was silently dropped.
 */
return new class extends Migration
{
    private const VALUES = [
        'inscription',
        'reinscription',
        'paiement_valide',
        'paiement_rejete',
        'absence',
        'bulletin_publie',
        'notes_faibles',
        'annonce',
        'note_publiee',
        'rappel_paiement',
    ];

    private const PREVIOUS_VALUES = [
        'inscription',
        'reinscription',
        'paiement_valide',
        'paiement_rejete',
        'absence',
        'bulletin_publie',
        'notes_faibles',
        'annonce',
    ];

    public function up(): void
    {
        $this->setEnum(self::VALUES);
    }

    public function down(): void
    {
        // Rows already carrying a widened value would break the narrower
        // definition, so they are folded into the generic type first.
        if (DB::getDriverName() === 'mysql') {
            DB::table('parent_notification_logs')
                ->whereIn('notification_type', ['note_publiee', 'rappel_paiement'])
                ->update(['notification_type' => 'annonce']);
        }

        $this->setEnum(self::PREVIOUS_VALUES);
    }

    /**
     * @param  array<int, string>  $values
     */
    private function setEnum(array $values): void
    {
        // SQLite stores an enum as free text, so there is no constraint to widen.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $list = implode(', ', array_map(static fn (string $value): string => "'".$value."'", $values));

        DB::statement(
            "ALTER TABLE `parent_notification_logs` MODIFY `notification_type` ENUM({$list}) NOT NULL COMMENT 'Type de notification envoyée'"
        );
    }
};
