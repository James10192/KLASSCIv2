<?php

namespace App\Services\MailPulse;

use App\Models\ESBTPParent;
use App\Models\ParentChatbotLink;
use App\Models\Setting;
use App\Services\ParentChatbot\ParentChatbotPublicationPolicy;
use Closure;
use Illuminate\Support\Facades\DB;

class MailPulseWorkflowPolicy
{
    public function __construct(private ParentChatbotPublicationPolicy $publicationPolicy) {}

    public function realWorkflowsEnabled(): bool
    {
        $enabled = $this->setting('mailpulse_enabled', 'enabled', '1');
        $value = $this->setting('mailpulse_real_workflows_enabled', 'real_workflows_enabled', '0');

        return filter_var($enabled, FILTER_VALIDATE_BOOLEAN)
            && filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function parentAllows(ESBTPParent $parent, string $event, string $channel): bool
    {
        if (! $this->realWorkflowsEnabled()) {
            return false;
        }

        if ($this->isStoppedForMessaging($parent, $channel)) {
            return false;
        }

        $preferences = $parent->notificationPreferences()->first() ?? $parent->getOrCreateNotificationPreferences();

        return $preferences->isNotificationEnabled($this->preferenceType($event))
            && in_array($channel, $preferences->preferred_channels ?? ['app', 'email'], true);
    }

    /**
     * Serialize STOP with the external emission. The callback only runs while
     * the parent messaging links are locked and all disclosure gates hold.
     *
     * @return array{allowed: bool, publication_eligible: bool, result: mixed}
     */
    public function dispatchIfAllowed(
        ESBTPParent $parent,
        int $studentId,
        string $event,
        string $channel,
        array $payload,
        Closure $dispatch,
    ): array {
        return DB::transaction(function () use ($parent, $studentId, $event, $channel, $payload, $dispatch): array {
            ParentChatbotLink::query()
                ->where('parent_id', $parent->id)
                ->lockForUpdate()
                ->get(['id']);

            $parent = ESBTPParent::query()->find($parent->id);
            if ($parent === null || ! $this->parentAllows($parent, $event, $channel)) {
                return ['allowed' => false, 'publication_eligible' => true, 'result' => null];
            }

            $gate = $this->publicationPolicy->submitIfStillPublishable(
                $this->workflowDisclosure($event, $studentId, $payload),
                $dispatch,
            );

            return ['allowed' => true] + $gate;
        }, 3);
    }

    private function workflowDisclosure(string $event, int $studentId, array $payload): ?array
    {
        if (! in_array($event, ['grade_published', 'bulletin_published'], true)) {
            return null;
        }

        $metadata = $payload['metadata'] ?? null;
        if (! is_array($metadata)) {
            return [];
        }

        $resourceId = $event === 'grade_published'
            ? ($metadata['note_id'] ?? null)
            : ($metadata['bulletin_id'] ?? null);

        return [
            'type' => $event === 'grade_published' ? 'grades' : 'report_card',
            'student_id' => $studentId,
            'resource_ids' => [$resourceId],
        ];
    }

    private function isStoppedForMessaging(ESBTPParent $parent, string $channel): bool
    {
        if (! in_array($channel, ['whatsapp', 'sms'], true)) {
            return false;
        }

        return ParentChatbotLink::query()
            ->where('parent_id', $parent->id)
            ->where('status', ParentChatbotLink::STATUS_STOPPED)
            ->exists();
    }

    public function preferenceType(string $event): string
    {
        return match ($event) {
            'payment_received', 'fee_reminder' => 'paiements',
            'absence_reported' => 'absences',
            'grade_published' => 'notes',
            'bulletin_published' => 'bulletins',
            default => 'annonces',
        };
    }

    private function setting(string $settingKey, string $configKey, string $default = ''): string
    {
        try {
            $value = Setting::get($settingKey, null);
            if ($value !== null && $value !== '') {
                return is_string($value) ? trim($value) : (string) $value;
            }
        } catch (\Throwable) {
        }

        $configValue = config('services.mailpulse.' . $configKey, $default);

        if (is_bool($configValue)) {
            return $configValue ? '1' : '0';
        }

        return is_string($configValue) ? trim($configValue) : (string) $configValue;
    }
}
