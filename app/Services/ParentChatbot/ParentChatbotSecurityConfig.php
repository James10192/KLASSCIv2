<?php

namespace App\Services\ParentChatbot;

use App\Models\Setting;

final class ParentChatbotSecurityConfig
{
    public static function serviceSecret(): string
    {
        return self::required('parent_chatbot_service_secret', 'MAILPULSE_PARENT_CHATBOT_SERVICE_SECRET');
    }

    public static function codePepper(): string
    {
        return self::required(
            'parent_chatbot_code_pepper',
            'MAILPULSE_PARENT_CHATBOT_CODE_PEPPER',
            ['parent_chatbot_service_secret', 'api_key'],
        );
    }

    public static function phoneHashKey(): string
    {
        return self::required(
            'parent_chatbot_phone_hash_key',
            'MAILPULSE_PARENT_CHATBOT_PHONE_HASH_KEY',
            ['parent_chatbot_service_secret', 'api_key'],
        );
    }

    public static function webhookSecret(): string
    {
        return self::required(
            'parent_chatbot_webhook_secret',
            'MAILPULSE_PARENT_CHATBOT_WEBHOOK_SECRET',
            ['parent_chatbot_service_secret', 'api_key'],
        );
    }

    public static function has(string $key): bool
    {
        return strlen(self::value($key)) >= 32;
    }

    /**
     * @param array<int, string> $fallbackKeys
     */
    private static function required(string $key, string $label, array $fallbackKeys = []): string
    {
        $configured = (string) config("services.mailpulse.{$key}", '');
        if ($configured !== '') {
            if (strlen($configured) < 32) {
                throw new \LogicException("{$label} must contain at least 32 characters.");
            }

            return $configured;
        }

        foreach ($fallbackKeys as $fallbackKey) {
            $fallback = self::value($fallbackKey);
            if (strlen($fallback) >= 32) {
                return hash_hmac('sha256', $key, $fallback);
            }
        }

        throw new \LogicException("{$label} must contain at least 32 characters.");
    }

    private static function value(string $key): string
    {
        if ($key === 'api_key') {
            return self::mailPulseApiKey();
        }

        return (string) config("services.mailpulse.{$key}", '');
    }

    private static function mailPulseApiKey(): string
    {
        if ((string) config('app.tenant_code', '') !== 'presentation') {
            return '';
        }

        try {
            $settingValue = Setting::where('key', 'mailpulse_api_key')
                ->where('is_active', true)
                ->value('value');
            if (is_string($settingValue) && trim($settingValue) !== '') {
                return trim($settingValue);
            }
        } catch (\Throwable) {
            // Keep early bootstrap and unit tests independent from the settings table.
        }

        return (string) config('services.mailpulse.api_key', '');
    }
}
